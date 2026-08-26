<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Odp;
use App\Models\Package;
use App\Models\PopResidentAccess;
use App\Models\PopSetting;
use App\Models\Router;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Helpers\Mikrotik\MikrotikService;
use App\Services\CustomerUnsuspendService;
use App\Services\CustomerConnectivityService;
use App\Services\NotificationService;
use App\Services\RadiusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Laravolt\Indonesia\Models\Province;
use App\Imports\CustomerImport;
use App\Exports\CustomerImportTemplate;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:customers.view', only: ['index', 'show', 'getData']),
            new Middleware('permission:customers.create', only: ['create', 'store', 'import', 'processImport', 'previewImport', 'downloadTemplate']),
            new Middleware('permission:customers.edit', only: ['edit', 'update', 'syncMikrotik', 'bulkToggleAutoIsolir', 'isolir', 'bukaIsolir', 'matchAcsDevice', 'updateWifi']),
            new Middleware('permission:customers.delete', only: ['destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Get POP ID based on user role
     */
    protected function getPopId(Request $request)
    {
        $user = auth()->user();
        
        if ($user->hasRole('superadmin')) {
            // Superadmin can select which POP to manage
            return $request->input('pop_id') ?: $request->session()->get('manage_pop_id');
        }
        
        // Admin POP uses their own ID
        return $user->id;
    }

    /**
     * Display customers list
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $popId = $this->getPopId($request);
        
        // For superadmin, get list of POPs
        $popUsers = null;
        if ($user->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
            
            // Store selected POP in session
            if ($request->has('pop_id')) {
                $request->session()->put('manage_pop_id', $request->input('pop_id'));
                $popId = $request->input('pop_id');
            }
        }
        
        // Build query
        $query = Customer::with(['router', 'package', 'province', 'city', 'nextUnpaidInvoice'])
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->router_id, fn($q, $r) => $q->where('router_id', $r))
            ->when($request->package_id, fn($q, $p) => $q->where('package_id', $p))
            ->when($request->city_code, fn($q, $c) => $q->where('city_code', $c))
            ->when($request->has('auto_isolir') && $request->auto_isolir !== '', fn($q) => $q->where('auto_isolir', $request->boolean('auto_isolir')))
            ->when($request->search, function($q, $s) {
                $q->where(function($sq) use ($s) {
                    $sq->where('name', 'like', "%{$s}%")
                       ->orWhere('nickname', 'like', "%{$s}%")
                       ->orWhere('customer_id', 'like', "%{$s}%")
                       ->orWhere('phone', 'like', "%{$s}%")
                       ->orWhere('email', 'like', "%{$s}%")
                       ->orWhere('pppoe_username', 'like', "%{$s}%");
                });
            });
        
        $customers = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Get routers for filter
        $routers = Router::when($popId, fn($q) => $q->where('pop_id', $popId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get packages for filter (by routers milik POP ini)
        $routerIds = $routers->pluck('id');
        $packages = Package::whereIn('router_id', $routerIds)
            ->orderBy('name')
            ->get();

        // Get distinct cities used by customers in this POP
        $usedCityCodes = Customer::when($popId, fn($q) => $q->where('pop_id', $popId))
            ->whereNotNull('city_code')
            ->distinct()
            ->pluck('city_code');
        $filterCities = \Laravolt\Indonesia\Models\City::whereIn('code', $usedCityCodes)
            ->orderBy('name')
            ->get();
        
        // Statistics
        $stats = [
            'total' => Customer::when($popId, fn($q) => $q->where('pop_id', $popId))->count(),
            'active' => Customer::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'active')->count(),
            'pending' => Customer::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'pending')->count(),
            'suspended' => Customer::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'suspended')->count(),
        ];
        
        if ($request->ajax()) {
            return view('admin.customers._table', compact('customers', 'popId', 'routers', 'packages', 'filterCities'));
        }
        return view('admin.customers.index', compact('customers', 'popUsers', 'popId', 'routers', 'packages', 'filterCities', 'stats'));
    }

    /**
     * Return table partial for AJAX requests (live search / filter)
     * @deprecated — AJAX detection is now in index(); remove this method later.
     */
    public function getTableData(Request $request)
    {
        $user = auth()->user();
        $popId = $this->getPopId($request);

        $query = Customer::with(['router', 'package', 'province', 'city', 'nextUnpaidInvoice'])
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->router_id, fn($q, $r) => $q->where('router_id', $r))
            ->when($request->package_id, fn($q, $p) => $q->where('package_id', $p))
            ->when($request->city_code, fn($q, $c) => $q->where('city_code', $c))
            ->when($request->has('auto_isolir') && $request->auto_isolir !== '', fn($q) => $q->where('auto_isolir', $request->boolean('auto_isolir')))
            ->when($request->search, function($q, $s) {
                $q->where(function($sq) use ($s) {
                    $sq->where('name', 'like', "%{$s}%")
                       ->orWhere('nickname', 'like', "%{$s}%")
                       ->orWhere('customer_id', 'like', "%{$s}%")
                       ->orWhere('phone', 'like', "%{$s}%")
                       ->orWhere('email', 'like', "%{$s}%")
                       ->orWhere('pppoe_username', 'like', "%{$s}%");
                });
            });

        $customers = $query->orderBy('created_at', 'desc')->paginate(20);

        $routers = Router::when($popId, fn($q) => $q->where('pop_id', $popId))
            ->where('is_active', true)->orderBy('name')->get();
        $routerIds = $routers->pluck('id');
        $packages = Package::whereIn('router_id', $routerIds)->orderBy('name')->get();
        $usedCityCodes = Customer::when($popId, fn($q) => $q->where('pop_id', $popId))
            ->whereNotNull('city_code')->distinct()->pluck('city_code');
        $filterCities = \Laravolt\Indonesia\Models\City::whereIn('code', $usedCityCodes)->orderBy('name')->get();

        return view('admin.customers._table', compact('customers', 'popId', 'routers', 'packages', 'filterCities'));
    }

    /**
     * Search customers (AJAX for select2/autocomplete)
     */
    public function search(Request $request)
    {
        $popId = $request->input('pop_id');
        $search = $request->input('q', $request->input('search', ''));
        $withoutOnu = $request->boolean('without_onu', false);
        
        $query = Customer::query()
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->when($search, function($q, $s) {
                $q->where(function($sq) use ($s) {
                    $sq->where('name', 'like', "%{$s}%")
                       ->orWhere('customer_id', 'like', "%{$s}%")
                       ->orWhere('phone', 'like', "%{$s}%")
                       ->orWhere('pppoe_username', 'like', "%{$s}%");
                });
            })
            ->when($withoutOnu, fn($q) => $q->whereDoesntHave('onu'))
            ->where('status', '!=', 'terminated')
            ->orderBy('name')
            ->limit(20);
        
        $customers = $query->get()->map(function($c) {
            return [
                'id' => $c->id,
                'text' => "{$c->customer_id} - {$c->name}",
                'name' => $c->name,
                'customer_id' => $c->customer_id,
                'phone' => $c->phone,
                'pppoe_username' => $c->pppoe_username,
            ];
        });
        
        return response()->json([
            'results' => $customers,
            'pagination' => ['more' => false]
        ]);
    }

    /**
     * Show create form
     */
    public function create(Request $request)
    {
        $popId = $this->getPopId($request);
        
        if (!$popId && auth()->user()->hasRole('superadmin')) {
            return back()->with('error', 'Pilih POP terlebih dahulu');
        }
        
        $routers = Router::where('pop_id', $popId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $packages = Package::whereIn('router_id', $routers->pluck('id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $provinces = Province::orderBy('name')->get();
        
        // Get POP settings for prefix display
        $popSetting = PopSetting::where('user_id', $popId)->first();
        
        // Generate customer ID
        $nextCustomerId = Customer::generateCustomerId($popId);
        
        // Get ODPs for this POP
        $odps = Odp::where('pop_id', $popId)
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'total_ports', 'used_ports']);
        
        // Get used ODP ports per ODP
        $usedOdpPorts = Customer::whereNotNull('odp_id')
            ->whereNotNull('odp_port')
            ->get()
            ->groupBy('odp_id')
            ->map(function($customers) {
                return $customers->map(function($c) {
                    return [
                        'port' => $c->odp_port,
                        'customer_name' => $c->name,
                        'customer_id' => $c->customer_id,
                    ];
                })->keyBy('port');
            });
        
        // Check if POP has resident data access
        $user = auth()->user();
        $hasResidentAccess = $user->hasRole('superadmin') || PopResidentAccess::where('pop_id', $popId)->exists();

        return view('admin.customers.create', compact('routers', 'packages', 'provinces', 'nextCustomerId', 'popId', 'popSetting', 'odps', 'usedOdpPorts', 'hasResidentAccess'));
    }

    /**
     * Store new customer
     */
    public function store(Request $request)
    {
        $popId = $this->getPopId($request);
        
        $request->validate([
            // Required fields - hanya data minimal
            'name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'phone' => 'required|string|max:20',
            'router_id' => 'required|uuid|exists:routers,id',
            'package_id' => 'required|uuid|exists:packages,id',
            // Optional fields - bisa dilengkapi nanti
            'email' => 'nullable|email|max:255',
            'phone_alt' => 'nullable|string|max:20',
            'nik' => 'nullable|string|max:16',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string',
            'province_code' => 'nullable|string',
            'city_code' => 'nullable|string',
            'district_code' => 'nullable|string',
            'village_code' => 'nullable|string',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            // PPPoE credentials - auto-generated if not provided
            'pppoe_username' => 'nullable|string|max:255|unique:customers,pppoe_username',
            'pppoe_password' => 'nullable|string|max:255',
            'service_type' => 'nullable|in:pppoe,hotspot,static',
            'installation_date' => 'nullable|date',
            'monthly_fee' => 'nullable|numeric|min:0',
            'installation_fee' => 'nullable|numeric|min:0',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'create_user_account' => 'boolean',
            'activate_now' => 'boolean',
            'sync_mikrotik' => 'boolean',
            'sync_radius' => 'boolean',
            'imported_from_mikrotik' => 'boolean', // Flag if imported from existing Mikrotik secret
            'photo_ktp' => 'nullable|string',
            'photo_selfie' => 'nullable|string',
            'photo_house' => 'nullable|string',
            // ODP connection (optional)
            'odp_id' => 'nullable|uuid|exists:odps,id',
            'odp_port' => 'nullable|integer|min:1',
        ]);

        if ($request->boolean('create_user_account') && !$request->filled('email')) {
            return response()->json([
                'success' => false,
                'message' => 'Email wajib diisi jika ingin membuat akun portal pelanggan.',
                'errors' => [
                    'email' => ['Email wajib diisi jika ingin membuat akun portal pelanggan.'],
                ],
            ], 422);
        }

        if ($request->filled('email') && User::where('email', $request->email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Email {$request->email} sudah digunakan oleh akun lain. Gunakan email lain.",
                'errors' => [
                    'email' => ["Email {$request->email} sudah digunakan oleh akun lain. Gunakan email lain."],
                ],
            ], 422);
        }
        
        // Validate ODP port is not already used (server-side protection)
        if ($request->odp_id && $request->odp_port) {
            $existingCustomer = Customer::where('odp_id', $request->odp_id)
                ->where('odp_port', $request->odp_port)
                ->first();
            
            if ($existingCustomer) {
                return response()->json([
                    'success' => false,
                    'message' => "Port {$request->odp_port} pada ODP ini sudah digunakan oleh pelanggan: {$existingCustomer->customer_id} ({$existingCustomer->name})"
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // Handle photos (base64)
            $photoKtp = $this->saveBase64Image($request->photo_ktp, 'customers/ktp');
            $photoSelfie = $this->saveBase64Image($request->photo_selfie, 'customers/selfie');
            $photoHouse = $this->saveBase64Image($request->photo_house, 'customers/house');

            // Get package for default values
            $package = Package::with('router')->find($request->package_id);
            $router = Router::find($request->router_id);
            
            // Get POP settings for sync options
            $popSetting = PopSetting::where('user_id', $popId)->first();
            $prefix = $popSetting?->pop_prefix ?? '';
            $usePrefix = $request->use_prefix !== '0'; // default true
            
            // PPPoE username - format: PREFIX-username (unless use_prefix is disabled)
            $pppoeUsername = $request->pppoe_username;
            if (!$pppoeUsername) {
                // Auto-generate: PREFIX-123456
                $randomDigits = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $pppoeUsername = ($usePrefix && $prefix) ? $prefix . '-' . $randomDigits : $randomDigits;
            } else {
                // Add prefix if not already present and prefix is enabled
                if ($usePrefix && $prefix && !str_starts_with($pppoeUsername, $prefix . '-')) {
                    $pppoeUsername = $prefix . '-' . $pppoeUsername;
                }
            }
            
            // Generate PPPoE password if not provided (default: 12345)
            $pppoePassword = $request->pppoe_password;
            if (!$pppoePassword) {
                $pppoePassword = '12345'; // Default password for easy remembering
            }

            // Create customer
            $customer = Customer::create([
                'pop_id' => $popId,
                'customer_id' => Customer::generateCustomerId($popId),
                'name' => $request->name,
                'nickname' => $request->nickname,
                'email' => $request->email,
                'phone' => $request->phone,
                'phone_alt' => $request->phone_alt,
                'nik' => $request->nik,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'address' => $request->address,
                'province_code' => $request->province_code,
                'city_code' => $request->city_code,
                'district_code' => $request->district_code,
                'village_code' => $request->village_code,
                'postal_code' => $request->postal_code,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'photo_ktp' => $photoKtp,
                'photo_selfie' => $photoSelfie,
                'photo_house' => $photoHouse,
                'router_id' => $request->router_id,
                'package_id' => $request->package_id,
                'odp_id' => $request->odp_id,
                'odp_port' => $request->odp_port,
                'pppoe_username' => $pppoeUsername,
                'pppoe_password' => $pppoePassword,
                'service_type' => $request->service_type ?? 'pppoe',
                'installation_date' => $request->installation_date ?? now()->toDateString(),
                'monthly_fee' => $request->monthly_fee ?? ($package->price ?? 0),
                'installation_fee' => $request->installation_fee ?? 0,
                'billing_day' => $request->billing_day ?? (int) now()->day,
                'status' => $request->boolean('activate_now') ? 'active' : 'pending',
                'notes' => $request->notes,
                'internal_notes' => $request->internal_notes,
                'registered_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]);
            
            // Update ODP used ports count if assigned
            if ($request->odp_id) {
                $odp = Odp::find($request->odp_id);
                if ($odp) {
                    $odp->increment('used_ports');
                }
            }

            // Create user account if requested
            if ($request->boolean('create_user_account') && $request->email) {
                $userPassword = $pppoePassword; // Use same password
                
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => Hash::make($userPassword),
                    'plain_password' => $userPassword,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);
                
                $user->assignRole('client');
                
                $customer->update(['user_id' => $user->id]);
            }

            $this->activityLog->log('customers', "Menambah pelanggan baru: {$customer->name} ({$customer->customer_id})");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan pelanggan: ' . $e->getMessage(),
            ], 500);
        }

        // ============================================================
        // SYNC OPERATIONS (outside DB transaction to prevent rollback)
        // Customer is already saved to database at this point
        // ============================================================
        $syncResults = [];
        
        try {
            // Check if imported from Mikrotik (migration mode - don't create, just mark as synced)
            $importedFromMikrotik = $request->boolean('imported_from_mikrotik');
            
            // Determine sync targets
            $syncToMikrotik = $request->boolean('sync_mikrotik') && $popSetting?->mikrotik_sync_enabled && $router;
            
            if ($importedFromMikrotik) {
                // Mark as already synced since we imported from existing Mikrotik secret
                $customer->update([
                    'mikrotik_synced' => true, 
                    'mikrotik_synced_at' => now(),
                    'internal_notes' => ($customer->internal_notes ? $customer->internal_notes . "\n" : '') . "[MIGRASI] Diimport dari PPP Secret Mikrotik yang sudah ada."
                ]);
                $syncResults['mikrotik'] = 'imported (existing)';
                
                // Still sync to Radius if requested (for hybrid backup)
                $syncToMikrotik = false; // Don't create in Mikrotik since imported
            }
            
            // Sync to Mikrotik PPP Secret if requested
            if ($syncToMikrotik) {
                try {
                    $mikrotikService = new MikrotikService();
                    
                    if (!$mikrotikService->connectRouter($router)) {
                        $syncResults['mikrotik'] = 'failed: Gagal terhubung ke router';
                        Log::warning("Mikrotik connection failed for customer {$customer->id}");
                    } else {
                        // Pre-check: username already exists in Mikrotik?
                        if ($mikrotikService->pppSecretExists($pppoeUsername)) {
                            $syncResults['mikrotik'] = 'skipped: Username sudah ada di Mikrotik';
                            // Mark as synced since it already exists
                            $customer->update([
                                'mikrotik_synced' => true, 
                                'mikrotik_synced_at' => now(),
                                'internal_notes' => ($customer->internal_notes ? $customer->internal_notes . "\n" : '') . '[AUTO] PPP Secret sudah ada di Mikrotik, skip pembuatan.'
                            ]);
                            Log::info("Mikrotik PPP Secret '{$pppoeUsername}' already exists, skipping creation for customer {$customer->id}");
                        } else {
                            $params = [
                                'name' => $pppoeUsername,
                                'password' => $pppoePassword,
                                'profile' => $package->profile_name ?? $package->name,
                                'comment' => '[billing] ' . $customer->name . ' - ' . $customer->customer_id,
                            ];
                            
                            $mikrotikResult = $mikrotikService->addPppSecret($params);
                            
                            // Check for success: response contains ret=*ID from !done sentence
                            // Check for error: response contains _error from !trap sentence
                            $hasError = isset($mikrotikResult[0]['_error']) || isset($mikrotikResult[0]['error']);
                            $hasRet = isset($mikrotikResult[0]['ret']) || isset($mikrotikResult['ret']);
                            
                            if ($hasRet && !$hasError) {
                                $syncResults['mikrotik'] = 'success';
                                $customer->update(['mikrotik_synced' => true, 'mikrotik_synced_at' => now()]);
                            } elseif ($hasError) {
                                $errorMsg = $mikrotikResult[0]['message'] ?? ($mikrotikResult[0]['detail'] ?? 'Router returned an error');
                                $syncResults['mikrotik'] = 'failed: ' . $errorMsg;
                                Log::warning("Mikrotik sync failed for customer {$customer->id}: {$errorMsg}");
                            } elseif (empty($mikrotikResult)) {
                                // Empty result but no error — likely success (some ROS versions)
                                $syncResults['mikrotik'] = 'success';
                                $customer->update(['mikrotik_synced' => true, 'mikrotik_synced_at' => now()]);
                            } else {
                                $errorMsg = $mikrotikResult[0]['message'] ?? 'Unexpected response';
                                $syncResults['mikrotik'] = 'failed: ' . $errorMsg;
                                Log::warning("Mikrotik sync unexpected response for customer {$customer->id}: " . json_encode($mikrotikResult));
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $syncResults['mikrotik'] = 'error: ' . $e->getMessage();
                    Log::error("Mikrotik sync error for customer {$customer->id}: " . $e->getMessage());
                }
            }
            
            // Sync to FreeRadius if requested
            if ($request->boolean('sync_radius') && $popSetting?->radius_enabled) {
                try {
                    $radiusService = new RadiusService();
                    
                    if (!$radiusService->connect($popSetting)) {
                        $syncResults['radius'] = 'failed: Gagal terhubung ke database Radius';
                        Log::warning("Radius connection failed for customer {$customer->id}");
                    } else {
                        $radiusResult = $radiusService->createUser($customer, $package);
                        
                        if ($radiusResult['success']) {
                            $syncResults['radius'] = 'success';
                            $customer->update(['radius_synced' => true, 'radius_synced_at' => now()]);
                        } else {
                            $syncResults['radius'] = 'failed: ' . ($radiusResult['message'] ?? 'Unknown error');
                            Log::warning("Radius sync failed for customer {$customer->id}: " . ($radiusResult['message'] ?? 'Unknown'));
                        }
                    }
                } catch (\Exception $e) {
                    $syncResults['radius'] = 'error: ' . $e->getMessage();
                    Log::error("Radius sync error for customer {$customer->id}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error("Sync error for customer {$customer->id}: " . $e->getMessage());
        }

        // Build response message
        $message = 'Pelanggan berhasil ditambahkan';
        if (!empty($syncResults)) {
            $syncMessages = [];
            if (isset($syncResults['mikrotik'])) {
                $syncMessages[] = 'Mikrotik: ' . $syncResults['mikrotik'];
            }
            if (isset($syncResults['radius'])) {
                $syncMessages[] = 'Radius: ' . $syncResults['radius'];
            }
            $message .= '. Sync: ' . implode(', ', $syncMessages);
        }

        // Send welcome notification to new customer
        try {
            app(NotificationService::class)->sendWelcome($customer);
        } catch (\Exception $e) {
            Log::warning('Failed to send welcome notification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'customer' => $customer,
            'sync_results' => $syncResults,
        ]);
    }

    /**
     * Show customer detail
     */
    public function show(Customer $customer)
    {
        $this->authorizeCustomer($customer);
        
        $customer->load(['router', 'package', 'province', 'city', 'district', 'village', 'user', 'invoices', 'payments', 'onu']);
        
        return view('admin.customers.show', compact('customer'));
    }

    public function connectivity(Customer $customer, CustomerConnectivityService $connectivity)
    {
        $this->authorizeCustomer($customer);

        return response()->json([
            'success' => true,
            'data' => $connectivity->summary($customer, true),
        ]);
    }

    public function matchAcsDevice(Customer $customer, CustomerConnectivityService $connectivity)
    {
        $this->authorizeCustomer($customer);

        return response()->json($connectivity->autoMatchAcsDevice($customer));
    }

    public function updateWifi(Customer $customer, Request $request, CustomerConnectivityService $connectivity)
    {
        $this->authorizeCustomer($customer);

        $validated = $request->validate([
            'wlan_path' => 'required|string|max:500',
            'ssid' => 'required|string|min:1|max:32',
            'password' => 'nullable|string|min:8|max:63',
        ]);

        return response()->json($connectivity->updateWifi($customer, $validated));
    }

    /**
     * Show edit form
     */
    public function edit(Customer $customer)
    {
        $this->authorizeCustomer($customer);
        
        $routers = Router::where('pop_id', $customer->pop_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $packages = Package::whereIn('router_id', $routers->pluck('id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $provinces = Province::orderBy('name')->get();
        
        // Load existing region data based on province_code
        $cities = $customer->province_code 
            ? \Laravolt\Indonesia\Models\City::where('province_code', $customer->province_code)->orderBy('name')->get()
            : collect();
        $districts = $customer->city_code 
            ? \Laravolt\Indonesia\Models\District::where('city_code', $customer->city_code)->orderBy('name')->get()
            : collect();
        $villages = $customer->district_code 
            ? \Laravolt\Indonesia\Models\Village::where('district_code', $customer->district_code)->orderBy('name')->get()
            : collect();
        
        // Get ODPs for this POP
        $odps = Odp::where('pop_id', $customer->pop_id)
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'total_ports', 'used_ports']);
        
        // Get used ODP ports per ODP (exclude current customer for edit)
        $usedOdpPorts = Customer::whereNotNull('odp_id')
            ->whereNotNull('odp_port')
            ->where('id', '!=', $customer->id)
            ->get()
            ->groupBy('odp_id')
            ->map(function($customers) {
                return $customers->map(function($c) {
                    return [
                        'port' => $c->odp_port,
                        'customer_name' => $c->name,
                        'customer_id' => $c->customer_id,
                    ];
                })->keyBy('port');
            });
        
        // Check if POP has resident data access
        $user = auth()->user();
        $hasResidentAccess = $user->hasRole('superadmin') || PopResidentAccess::where('pop_id', $customer->pop_id)->exists();

        return view('admin.customers.edit', compact('customer', 'routers', 'packages', 'provinces', 'cities', 'districts', 'villages', 'odps', 'usedOdpPorts', 'hasResidentAccess'));
    }

    /**
     * Update customer
     */
    public function update(Request $request, Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $request->validate([
            // Required fields
            'name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'phone' => 'required|string|max:20',
            'router_id' => 'required|uuid|exists:routers,id',
            'package_id' => 'required|uuid|exists:packages,id',
            // Optional fields
            'email' => 'nullable|email|max:255',
            'phone_alt' => 'nullable|string|max:20',
            'nik' => 'nullable|string|max:16',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string',
            'province_code' => 'nullable|string',
            'city_code' => 'nullable|string',
            'district_code' => 'nullable|string',
            'village_code' => 'nullable|string',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'pppoe_username' => 'nullable|string|max:255|unique:customers,pppoe_username,' . $customer->id,
            'pppoe_password' => 'nullable|string|max:255',
            'service_type' => 'nullable|in:pppoe,hotspot,static',
            'monthly_fee' => 'nullable|numeric|min:0',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'active_until' => 'nullable|date',
            // ODP connection (optional)
            'odp_id' => 'nullable|uuid|exists:odps,id',
            'odp_port' => 'nullable|integer|min:1',
        ]);
        
        // Validate ODP port is not already used (server-side protection)
        if ($request->odp_id && $request->odp_port) {
            $existingCustomer = Customer::where('odp_id', $request->odp_id)
                ->where('odp_port', $request->odp_port)
                ->where('id', '!=', $customer->id)
                ->first();
            
            if ($existingCustomer) {
                return back()->withInput()->withErrors([
                    'odp_port' => "Port {$request->odp_port} pada ODP ini sudah digunakan oleh pelanggan: {$existingCustomer->customer_id} ({$existingCustomer->name})"
                ]);
            }
        }

        DB::beginTransaction();
        try {
            $data = $request->only([
                'name', 'nickname', 'email', 'phone', 'phone_alt', 'nik', 'birth_date', 'gender',
                'address', 'province_code', 'city_code', 'district_code', 'village_code',
                'postal_code', 'latitude', 'longitude', 'router_id', 'package_id',
                'pppoe_username', 'service_type', 'monthly_fee', 'billing_day', 
                'notes', 'internal_notes', 'remote_address', 'mac_address', 'active_until',
                'odp_id', 'odp_port',
            ]);
            
            // Handle ODP change - update used_ports count
            $oldOdpId = $customer->odp_id;
            $newOdpId = $request->odp_id;
            
            // If ODP changed, update counts
            if ($oldOdpId !== $newOdpId) {
                // Decrement old ODP if had one
                if ($oldOdpId) {
                    $oldOdp = Odp::find($oldOdpId);
                    if ($oldOdp && $oldOdp->used_ports > 0) {
                        $oldOdp->decrement('used_ports');
                    }
                }
                // Increment new ODP if assigned one
                if ($newOdpId) {
                    $newOdp = Odp::find($newOdpId);
                    if ($newOdp) {
                        $newOdp->increment('used_ports');
                    }
                }
            }

            // Track PPPoE username change for Mikrotik sync
            if (
                $request->filled('pppoe_username') &&
                $customer->pppoe_username &&
                $customer->pppoe_username !== $request->pppoe_username
            ) {
                $data['previous_pppoe_username'] = $customer->pppoe_username;
                $data['mikrotik_synced'] = false;
            }

            // Handle password change
            if ($request->filled('pppoe_password')) {
                $data['pppoe_password'] = $request->pppoe_password;
            }

            // Handle photos - support base64 upload, removal, or keep existing
            foreach (['photo_ktp', 'photo_selfie', 'photo_house'] as $photoField) {
                $photoValue = $request->input($photoField);
                
                if ($photoValue === 'removed') {
                    // User wants to remove the photo
                    if ($customer->$photoField) {
                        $folder = str_replace('photo_', '', $photoField);
                        Storage::delete('public/customers/' . $folder . '/' . $customer->$photoField);
                    }
                    $data[$photoField] = null;
                } elseif ($photoValue && str_starts_with($photoValue, 'data:image')) {
                    // New base64 image uploaded
                    if ($customer->$photoField) {
                        $folder = str_replace('photo_', '', $photoField);
                        Storage::delete('public/customers/' . $folder . '/' . $customer->$photoField);
                    }
                    $folder = 'customers/' . str_replace('photo_', '', $photoField);
                    $data[$photoField] = $this->saveBase64Image($photoValue, $folder);
                }
                // If not set or empty, keep existing value (don't include in $data)
            }

            $data['updated_by'] = auth()->id();
            $customer->update($data);

            // Update linked user if exists
            if ($customer->user && $request->filled('email') && $request->email !== $customer->user->email) {
                $customer->user->update(['email' => $request->email]);
            }

            $this->activityLog->log('customers', "Mengupdate pelanggan: {$customer->name} ({$customer->customer_id})");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pelanggan berhasil diupdate',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate pelanggan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete customer
     */
    public function destroy(Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $name = $customer->name;
        $customerId = $customer->customer_id;
        
        $customer->delete();
        
        $this->activityLog->log('customers', "Menghapus pelanggan: {$name} ({$customerId})");

        return response()->json([
            'success' => true,
            'message' => 'Pelanggan berhasil dihapus',
        ]);
    }

    /**
     * Get PPPoE password
     */
    public function getPassword(Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $this->activityLog->log('customers', "Melihat password PPPoE: {$customer->name} ({$customer->customer_id})");

        return response()->json([
            'success' => true,
            'password' => $customer->decrypted_password,
        ]);
    }

    /**
     * Change customer status
     */
    public function changeStatus(Request $request, Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $request->validate([
            'status' => 'required|in:pending,active,suspended,terminated,expired',
            'reason' => 'nullable|string|max:500',
        ]);

        $oldStatus = $customer->status;
        $data = ['status' => $request->status];

        if ($request->status === 'suspended') {
            $data['suspended_at'] = now();
            $data['suspend_reason'] = $request->reason;
        } elseif ($request->status === 'terminated') {
            $data['terminated_at'] = now();
            $data['terminate_reason'] = $request->reason;
        } elseif ($request->status === 'active' && $oldStatus === 'suspended') {
            $data['suspended_at'] = null;
            $data['suspend_reason'] = null;
        }

        $customer->update($data);

        $this->activityLog->log('customers', "Mengubah status pelanggan {$customer->name}: {$oldStatus} -> {$request->status}");

        return response()->json([
            'success' => true,
            'message' => 'Status pelanggan berhasil diubah',
        ]);
    }

    /**
     * Isolir customer: change PPPoE profile to 'isolir' and disconnect
     */
    public function isolir(Request $request, Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if ($customer->status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan sudah dalam status suspended/isolir.',
            ], 400);
        }

        if (!$customer->router_id || !$customer->pppoe_username) {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan belum memiliki konfigurasi router/PPPoE.',
            ], 400);
        }

        $service = app(CustomerUnsuspendService::class);
        $result = $service->isolir($customer);

        $resultMessages = [
            'isolated' => 'Pelanggan berhasil di-isolir. Profile PPPoE diubah ke isolir dan koneksi diputus.',
            'no_router' => 'Router tidak tersedia atau tidak aktif.',
            'not_found' => 'PPP Secret tidak ditemukan di Mikrotik.',
            'not_connected' => 'Tidak dapat terhubung ke router.',
            'error' => 'Terjadi error saat melakukan isolir di Mikrotik.',
        ];

        if ($result === 'isolated') {
            // Update customer status to suspended
            $customer->update([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspend_reason' => $request->reason ?? 'Isolir manual oleh admin',
            ]);

            $this->activityLog->log('customers', "Isolir pelanggan {$customer->name} ({$customer->pppoe_username}) — profile diubah ke isolir");

            // Send isolation notification
            try {
                app(NotificationService::class)->sendIsolated($customer, [
                    'isolate_reason' => $request->reason ?? 'Isolir manual oleh admin',
                    'isolate_date' => now()->format('d F Y H:i'),
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to send isolir notification: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => $resultMessages[$result],
                'mikrotik_result' => $result,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $resultMessages[$result] ?? 'Gagal melakukan isolir.',
            'mikrotik_result' => $result,
        ], 500);
    }

    /**
     * Buka isolir customer: restore PPPoE profile to package profile and reconnect
     */
    public function bukaIsolir(Customer $customer)
    {
        $this->authorizeCustomer($customer);

        if ($customer->status !== 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan tidak dalam status suspended/isolir.',
            ], 400);
        }

        if (!$customer->router_id || !$customer->pppoe_username) {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan belum memiliki konfigurasi router/PPPoE.',
            ], 400);
        }

        $service = app(CustomerUnsuspendService::class);
        $result = $service->unsuspend($customer);

        $resultMessages = [
            'unsuspended' => 'Isolir berhasil dibuka. Profile PPPoE dikembalikan ke paket semula dan koneksi diputus untuk reconnect.',
            'no_router' => 'Router tidak tersedia atau tidak aktif. Status DB tetap diubah ke aktif.',
            'not_found' => 'PPP Secret tidak ditemukan di Mikrotik. Status DB tetap diubah ke aktif.',
            'not_connected' => 'Tidak dapat terhubung ke router. Status DB tetap diubah ke aktif.',
            'error' => 'Terjadi error di Mikrotik. Status DB tetap diubah ke aktif.',
        ];

        $this->activityLog->log('customers', "Buka isolir pelanggan {$customer->name} ({$customer->pppoe_username}) — [mikrotik: {$result}]");

        // Send activation notification
        try {
            app(NotificationService::class)->sendActivated($customer, [
                'activation_date' => now()->format('d F Y H:i'),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send buka isolir notification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => $result === 'unsuspended',
            'message' => $resultMessages[$result] ?? 'Isolir berhasil dibuka.',
            'mikrotik_result' => $result,
            'partial' => $result !== 'unsuspended',
        ]);
    }

    /**
     * Bulk toggle auto-isolir for multiple customers
     */
    /**
     * Resolve customer query from either specific IDs or select-all-pages mode with optional filters.
     */
    private function resolveCustomerQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();
        if ($request->boolean('select_all')) {
            $query = Customer::query();
            if (!$user->hasRole('superadmin')) {
                $query->where('pop_id', $user->id);
            } elseif ($request->filled('pop_id')) {
                $query->where('pop_id', $request->pop_id);
            }
            if ($request->filled('filter_status')) {
                $query->where('status', $request->filter_status);
            }
            if ($request->filled('filter_router_id')) {
                $query->where('router_id', $request->filter_router_id);
            }
            if ($request->filled('filter_package_id')) {
                $query->where('package_id', $request->filter_package_id);
            }
            if ($request->filled('filter_city_code')) {
                $query->where('city_code', $request->filter_city_code);
            }
            return $query;
        }
        $query = Customer::whereIn('id', $request->customer_ids ?? []);
        if (!$user->hasRole('superadmin')) {
            $query->where('pop_id', $user->id);
        }
        return $query;
    }

    public function bulkToggleAutoIsolir(Request $request)
    {
        $request->validate([
            'customer_ids' => 'required_without:select_all|array',
            'auto_isolir' => 'required|boolean',
        ]);

        $autoIsolir = $request->boolean('auto_isolir');
        $affected = $this->resolveCustomerQuery($request)->update(['auto_isolir' => $autoIsolir]);

        $action = $autoIsolir ? 'Mengaktifkan' : 'Menonaktifkan';
        $this->activityLog->log('customers', "{$action} auto-isolir untuk {$affected} pelanggan");

        return response()->json([
            'success' => true,
            'message' => "{$action} auto-isolir berhasil untuk {$affected} pelanggan.",
            'affected' => $affected,
        ]);
    }

    /**
     * Bulk activate pending customers
     */
    public function bulkActivate(Request $request)
    {
        $request->validate([
            'customer_ids' => 'required_without:select_all|array',
        ]);

        $affected = $this->resolveCustomerQuery($request)
            ->where('status', 'pending')
            ->update(['status' => 'active']);

        $this->activityLog->log('customers', "Mengaktifkan {$affected} pelanggan (bulk activate)");

        return response()->json([
            'success' => true,
            'message' => "Berhasil mengaktifkan {$affected} pelanggan.",
            'affected' => $affected,
        ]);
    }

    /**
     * Bulk sync customers to Mikrotik
     */
    public function bulkSyncMikrotik(Request $request)
    {
        if (!$request->boolean('select_all') && empty($request->customer_ids)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada pelanggan dipilih'], 422);
        }

        $customers = $this->resolveCustomerQuery($request)
            ->with(['router', 'package'])
            ->get();
        $details = [];
        $successCount = 0;
        $failCount = 0;

        // Group by router to reuse connections
        $grouped = $customers->groupBy('router_id');

        foreach ($grouped as $routerId => $group) {
            if (!$routerId) {
                foreach ($group as $customer) {
                    $details[] = ['name' => $customer->name, 'success' => false, 'status' => 'Belum ada router'];
                    $failCount++;
                }
                continue;
            }

            $router = $group->first()->router;
            $popSetting = PopSetting::where('user_id', $group->first()->pop_id)->first();

            if (!$popSetting?->mikrotik_sync_enabled) {
                foreach ($group as $customer) {
                    $details[] = ['name' => $customer->name, 'success' => false, 'status' => 'Mikrotik sync tidak aktif'];
                    $failCount++;
                }
                continue;
            }

            try {
                $mikrotikService = new MikrotikService();
                if (!$mikrotikService->connectRouter($router)) {
                    foreach ($group as $customer) {
                        $details[] = ['name' => $customer->name, 'success' => false, 'status' => 'Gagal terhubung ke router'];
                        $failCount++;
                    }
                    continue;
                }

                foreach ($group as $customer) {
                    // Skip if already synced
                    if ($customer->mikrotik_synced) {
                        $details[] = ['name' => $customer->name, 'success' => true, 'status' => 'Sudah tersinkronisasi'];
                        $successCount++;
                        continue;
                    }

                    // Skip if no username
                    if (empty($customer->pppoe_username)) {
                        $details[] = ['name' => $customer->name, 'success' => false, 'status' => 'Username PPPoE kosong'];
                        $failCount++;
                        continue;
                    }

                    try {
                        // Build params
                        $profileName = $customer->package?->mikrotik_profile_name ?? $customer->package?->name ?? 'default';
                        $password = $customer->decrypted_pppoe_password ?? '12345';

                        // Check if already exists in Mikrotik
                        $existingSecret = $mikrotikService->getPppSecretByName($customer->pppoe_username);

                        // Fallback: search by previous username (username was changed)
                        $wasRenamed = false;
                        if (!$existingSecret && $customer->previous_pppoe_username) {
                            $existingSecret = $mikrotikService->getPppSecretByName($customer->previous_pppoe_username);
                            if ($existingSecret) {
                                $wasRenamed = true;
                            }
                        }

                        if ($existingSecret) {
                            // Update existing secret (and rename if username changed)
                            $updateParams = [
                                'password' => $password,
                                'profile' => $profileName,
                                'comment' => '[billing] ' . $customer->name . ' - ' . $customer->customer_id,
                            ];

                            if ($wasRenamed) {
                                $updateParams['name'] = $customer->pppoe_username;
                            }

                            $mikrotikService->updatePppSecret($existingSecret['.id'], $updateParams);

                            $customer->update([
                                'mikrotik_synced' => true,
                                'mikrotik_synced_at' => now(),
                                'last_sync_error' => null,
                                'previous_pppoe_username' => null,
                            ]);

                            $statusText = $wasRenamed
                                ? "Di-rename dari '{$customer->previous_pppoe_username}' dan diupdate"
                                : 'Sudah ada di Mikrotik, diupdate';
                            $details[] = ['name' => $customer->name, 'success' => true, 'status' => $statusText];
                            $successCount++;
                            continue;
                        }

                        // Create PPP Secret
                        $params = [
                            'name' => $customer->pppoe_username,
                            'password' => $password,
                            'profile' => $profileName,
                            'comment' => '[billing] ' . $customer->name . ' - ' . $customer->customer_id,
                        ];

                        $result = $mikrotikService->addPppSecret($params);

                        if (isset($result[0]['ret']) || isset($result['ret'])) {
                            $customer->update([
                                'mikrotik_synced' => true,
                                'mikrotik_synced_at' => now(),
                                'last_sync_error' => null,
                            ]);
                            $details[] = ['name' => $customer->name, 'success' => true, 'status' => 'PPP Secret berhasil dibuat'];
                            $successCount++;
                        } else {
                            $errorMsg = $result[0]['message'] ?? 'Unknown error';
                            $customer->update(['last_sync_error' => $errorMsg]);
                            $details[] = ['name' => $customer->name, 'success' => false, 'status' => $errorMsg];
                            $failCount++;
                        }
                    } catch (\Exception $e) {
                        $details[] = ['name' => $customer->name, 'success' => false, 'status' => $e->getMessage()];
                        $failCount++;
                    }
                }
            } catch (\Exception $e) {
                foreach ($group as $customer) {
                    $details[] = ['name' => $customer->name, 'success' => false, 'status' => 'Error: ' . $e->getMessage()];
                    $failCount++;
                }
            }
        }

        $this->activityLog->log('customers', "Bulk sync Mikrotik: {$successCount} berhasil, {$failCount} gagal dari " . count($customers) . " pelanggan");

        return response()->json([
            'success' => $failCount === 0,
            'message' => "Sync selesai: {$successCount} berhasil, {$failCount} gagal.",
            'details' => $details,
        ]);
    }

    /**
     * Get packages by router
     */
    public function getPackagesByRouter(Router $router)
    {
        $packages = Package::where('router_id', $router->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'speed_up', 'speed_down', 'rate_limit']);

        return response()->json($packages);
    }

    /**
     * Check if PPPoE username is available (DB + Mikrotik)
     */
    public function checkUsername(Request $request)
    {
        $popId = auth()->user()->hasRole('superadmin') ? $request->pop_id : auth()->id();
        $popSetting = PopSetting::where('user_id', $popId)->first();
        $prefix = $popSetting?->pop_prefix ?? '';

        // Build full username with prefix (unless use_prefix=0)
        $username = $request->username;
        $usePrefix = $request->use_prefix !== '0'; // default true
        if ($usePrefix && $prefix && !str_starts_with($username, $prefix . '-')) {
            $fullUsername = $prefix . '-' . $username;
        } else {
            $fullUsername = $username;
        }

        $response = [
            'available' => true,
            'db_exists' => false,
            'mikrotik_exists' => false,
            'full_username' => $fullUsername,
        ];

        // 1. Check database (only active customers block registration)
        $existingCustomer = Customer::where('pppoe_username', $fullUsername)
            ->when($request->exclude_id, fn($q, $id) => $q->where('id', '!=', $id))
            ->first();

        if ($existingCustomer) {
            $response['available'] = false;
            $response['db_exists'] = true;
            $response['message'] = 'Username sudah digunakan oleh: ' . $existingCustomer->name . ' (' . $existingCustomer->customer_id . ')';
        } else {
            // Check soft-deleted — informational only, doesn't block
            $trashedCustomer = Customer::onlyTrashed()
                ->where('pppoe_username', $fullUsername)
                ->first();
            if ($trashedCustomer) {
                $response['was_deleted'] = true;
                $response['deleted_info'] = 'Username ini pernah digunakan oleh pelanggan yang sudah dihapus';
            }
        }

        // 2. Check Mikrotik (if router selected and sync enabled)
        if ($request->router_id && $popSetting?->mikrotik_sync_enabled) {
            try {
                $router = Router::find($request->router_id);
                if ($router) {
                    $mikrotikService = new MikrotikService();
                    if ($mikrotikService->connectRouter($router)) {
                        $existsInMikrotik = $mikrotikService->pppSecretExists($fullUsername);
                        $response['mikrotik_exists'] = $existsInMikrotik;
                        $response['mikrotik_checked'] = true;

                        if ($existsInMikrotik && !$existingCustomer) {
                            // Exists in Mikrotik but NOT in DB — likely orphaned secret
                            $response['available'] = false;
                            $response['message'] = 'Username sudah ada di Mikrotik router! Gunakan fitur import jika ingin mengambil alih.';
                            $response['mikrotik_only'] = true;
                        } elseif ($existsInMikrotik && $existingCustomer) {
                            $response['message'] .= ' (juga ada di Mikrotik)';
                        }
                    } else {
                        $response['mikrotik_checked'] = false;
                        $response['mikrotik_error'] = 'Gagal terhubung ke router';
                    }
                }
            } catch (\Exception $e) {
                $response['mikrotik_checked'] = false;
                $response['mikrotik_error'] = 'Error: ' . $e->getMessage();
            }
        } else {
            $response['mikrotik_checked'] = false;
        }

        return response()->json($response);
    }

    /**
     * Check if portal email is available.
     */
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        return response()->json([
            'available' => !$user,
            'message' => $user
                ? "Email {$request->email} sudah digunakan oleh akun lain."
                : 'Email tersedia.',
        ]);
    }

    /**
     * Authorize customer belongs to current POP
     */
    protected function authorizeCustomer(Customer $customer): void
    {
        $user = auth()->user();
        
        if ($user->hasRole('superadmin')) {
            return; // Superadmin can access all
        }
        
        if ($customer->pop_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Show import form
     */
    public function import(Request $request)
    {
        $user = auth()->user();
        $popId = $this->getPopId($request);

        // For superadmin, get list of POPs
        $popUsers = null;
        if ($user->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
        }

        // Get routers and packages for reference
        $routers = collect();
        $packages = collect();
        if ($popId) {
            $routers = Router::where('pop_id', $popId)->orderBy('name')->get();
            $packages = Package::whereIn('router_id', $routers->pluck('id'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        return view('admin.customers.import', compact('popUsers', 'popId', 'routers', 'packages'));
    }

    /**
     * Preview import data (AJAX)
     */
    public function previewImport(Request $request)
    {
        $user = auth()->user();

        if ($user->hasRole('superadmin')) {
            $request->validate([
                'pop_id' => 'required|uuid|exists:users,id',
                'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            ]);
            $popId = $request->pop_id;
        } else {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            ]);
            $popId = $user->id;
        }

        try {
            $import = new CustomerImport($popId, true); // preview mode
            Excel::import($import, $request->file('file'));

            $preview = $import->getPreviewRows();
            $results = $import->getResults();

            // Collect available packages for default selection
            $routers = Router::where('pop_id', $popId)->pluck('id');
            $availablePackages = Package::whereIn('router_id', $routers)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'price', 'router_id'])
                ->map(function ($pkg) {
                    $pkg->router_name = Router::find($pkg->router_id)?->name;
                    return $pkg;
                });

            return response()->json([
                'success' => true,
                'preview' => $preview,
                'summary' => [
                    'valid' => $results['success_count'],
                    'errors' => $results['failed_count'],
                    'skipped' => $results['skipped_count'],
                    'total' => $results['total_processed'],
                ],
                'errors' => $results['errors'],
                'packages' => $availablePackages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process Excel import
     */
    public function processImport(Request $request)
    {
        $user = auth()->user();

        // Determine pop_id
        if ($user->hasRole('superadmin')) {
            $request->validate([
                'pop_id' => 'required|uuid|exists:users,id',
                'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            ], [
                'pop_id.required' => 'Pilih POP terlebih dahulu.',
                'file.required' => 'File Excel wajib diupload.',
                'file.mimes' => 'Format file harus .xlsx, .xls, atau .csv',
                'file.max' => 'Ukuran file maksimal 5MB.',
            ]);
            $popId = $request->pop_id;
        } else {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            ], [
                'file.required' => 'File Excel wajib diupload.',
                'file.mimes' => 'Format file harus .xlsx, .xls, atau .csv',
                'file.max' => 'Ukuran file maksimal 5MB.',
            ]);
            $popId = $user->id;
        }

        try {
            $defaultPackageId = $request->default_package_id ?: null;
            $activateNow = $request->boolean('activate_now');
            $import = new CustomerImport($popId, false, $defaultPackageId, $activateNow);
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            $this->activityLog->log('customers', "Import pelanggan dari Excel: {$results['success_count']} berhasil, {$results['failed_count']} gagal, {$results['skipped_count']} dilewati");

            $message = "Import selesai: {$results['success_count']} pelanggan berhasil diimport.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'results' => $results,
                ]);
            }

            return redirect()->route('admin.customers.import')
                ->with('import_results', $results)
                ->with('success', $message);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
            }
            $errorMsg = 'Validasi gagal: ' . implode('; ', array_slice($errorMessages, 0, 5));

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }

            return redirect()->route('admin.customers.import')
                ->with('error', $errorMsg);
        } catch (\Exception $e) {
            Log::error('Customer import failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Import gagal: ' . $e->getMessage()], 500);
            }

            return redirect()->route('admin.customers.import')
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    /**
     * Download import template
     */
    public function downloadTemplate(Request $request)
    {
        $user = auth()->user();
        $popId = $user->hasRole('superadmin') ? $request->pop_id : $user->id;

        $routers = [];
        $packages = [];

        if ($popId) {
            $routerModels = Router::where('pop_id', $popId)->orderBy('name')->get();
            $routers = $routerModels->pluck('name')->toArray();
            $packages = Package::whereIn('router_id', $routerModels->pluck('id'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn($p) => "{$p->name} ({$p->router->name})")
                ->toArray();
        }

        return Excel::download(
            new CustomerImportTemplate($routers, $packages),
            'template_import_pelanggan.xlsx'
        );
    }

    /**
     * Sync customer PPP Secret to Mikrotik (for unsynced customers)
     */
    public function syncMikrotik(Request $request, Customer $customer)
    {
        $user = auth()->user();

        // Authorization check
        if (!$user->hasRole('superadmin')) {
            if ($customer->pop_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        // Must have router assigned
        if (!$customer->router_id) {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan belum memiliki router. Silakan edit pelanggan terlebih dahulu.',
            ], 422);
        }

        // Load relationships
        $customer->load(['router', 'package']);
        $router = $customer->router;
        $package = $customer->package;

        // Check POP settings
        $popSetting = PopSetting::where('user_id', $customer->pop_id)->first();
        if (!$popSetting?->mikrotik_sync_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Sinkronisasi Mikrotik tidak diaktifkan pada pengaturan POP.',
            ], 422);
        }

        try {
            $mikrotikService = new MikrotikService();

            if (!$mikrotikService->connectRouter($router)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal terhubung ke router ' . $router->name,
                ], 500);
            }

            // Build params
            $profileName = $package?->mikrotik_profile_name ?? $package?->name ?? 'default';
            $password = $customer->decrypted_pppoe_password ?? '12345';

            // Check if already exists in Mikrotik
            $existingSecret = $mikrotikService->getPppSecretByName($customer->pppoe_username);

            // Fallback: search by previous username (username was changed)
            $wasRenamed = false;
            if (!$existingSecret && $customer->previous_pppoe_username) {
                $existingSecret = $mikrotikService->getPppSecretByName($customer->previous_pppoe_username);
                if ($existingSecret) {
                    $wasRenamed = true;
                }
            }

            if ($existingSecret) {
                // Already exists — update password, profile, comment (and name if renamed)
                $updateParams = [
                    'password' => $password,
                    'profile' => $profileName,
                    'comment' => '[billing] ' . $customer->name . ' - ' . $customer->customer_id,
                ];

                if ($wasRenamed) {
                    $updateParams['name'] = $customer->pppoe_username;
                }

                $updated = $mikrotikService->updatePppSecret($existingSecret['.id'], $updateParams);

                $customer->update([
                    'mikrotik_synced' => true,
                    'mikrotik_synced_at' => now(),
                    'mikrotik_status' => 'enabled',
                    'last_sync_error' => null,
                    'previous_pppoe_username' => null,
                ]);

                $statusMsg = $wasRenamed
                    ? "direnamed dari '{$customer->previous_pppoe_username}' dan diupdate"
                    : ($updated ? 'diupdate' : 'ditandai synced (update gagal)');
                $this->activityLog->log('customers', "Sync Mikrotik: {$customer->name} ({$customer->pppoe_username}) — PPP Secret {$statusMsg}");

                $responseMsg = $wasRenamed
                    ? "PPP Secret berhasil di-rename dari '{$customer->previous_pppoe_username}' ke '{$customer->pppoe_username}' dan diupdate."
                    : "PPP Secret '{$customer->pppoe_username}' sudah ada di Mikrotik dan berhasil diupdate (password, profile, comment).";

                return response()->json([
                    'success' => true,
                    'message' => $responseMsg,
                    'already_exists' => true,
                ]);
            }

            // Create PPP Secret
            $params = [
                'name' => $customer->pppoe_username,
                'password' => $password,
                'profile' => $profileName,
                'comment' => '[billing] ' . $customer->name . ' - ' . $customer->customer_id,
            ];

            $result = $mikrotikService->addPppSecret($params);

            if (isset($result[0]['ret']) || isset($result['ret'])) {
                $customer->update([
                    'mikrotik_synced' => true,
                    'mikrotik_synced_at' => now(),
                    'mikrotik_status' => 'enabled',
                    'last_sync_error' => null,
                ]);

                $this->activityLog->log('customers', "Sync Mikrotik berhasil: {$customer->name} ({$customer->pppoe_username})");

                return response()->json([
                    'success' => true,
                    'message' => "PPP Secret '{$customer->pppoe_username}' berhasil dibuat di Mikrotik.",
                ]);
            } else {
                $errorMsg = $result[0]['message'] ?? ($result['message'] ?? 'Unknown error');
                $customer->update(['last_sync_error' => $errorMsg]);

                return response()->json([
                    'success' => false,
                    'message' => "Gagal membuat PPP Secret: {$errorMsg}",
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error("Mikrotik sync error for customer {$customer->id}: " . $e->getMessage());
            $customer->update(['last_sync_error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error sinkronisasi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save base64 image
     */
    protected function saveBase64Image(?string $base64, string $path): ?string
    {
        if (!$base64 || !str_contains($base64, 'base64,')) {
            return null;
        }

        $imageData = explode(',', $base64)[1];
        $image = base64_decode($imageData);
        
        // Get image type
        $f = finfo_open();
        $mimeType = finfo_buffer($f, $image, FILEINFO_MIME_TYPE);
        finfo_close($f);
        
        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $filename = Str::uuid() . '.' . $extension;
        
        Storage::put("public/{$path}/{$filename}", $image);
        
        return $filename;
    }

    /**
     * Generate portal account for a single customer
     */
    public function generatePortalAccount(Customer $customer)
    {
        $this->authorizeCustomer($customer);

        if ($customer->user_id) {
            return response()->json(['success' => false, 'message' => 'Pelanggan sudah memiliki akun portal.'], 422);
        }

        // Use email if available, otherwise generate from customer_id
        $email = $customer->email ?: $customer->customer_id . '@portal.local';

        // Check if email already used by another user
        if (User::where('email', $email)->exists()) {
            return response()->json(['success' => false, 'message' => "Email {$email} sudah digunakan oleh akun lain."], 422);
        }

        $password = $customer->decrypted_pppoe_password ?: Str::random(8);
        $loginId = $customer->customer_id;

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $customer->name,
                'email' => $email,
                'phone' => $customer->phone,
                'password' => Hash::make($password),
                'plain_password' => $password,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            $user->assignRole('client');
            $customer->update(['user_id' => $user->id]);

            $this->activityLog->log('customers', "Generate akun portal: {$customer->name} (Login: {$loginId})");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Akun portal berhasil dibuat. Login menggunakan ID Pelanggan: {$loginId}",
                'login_id' => $loginId,
                'password' => $password,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Generate portal account error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal membuat akun portal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get portal account password
     */
    public function getPortalPassword(Customer $customer)
    {
        $this->authorizeCustomer($customer);

        if (!$customer->user_id) {
            return response()->json(['success' => false, 'message' => 'Pelanggan belum memiliki akun portal.']);
        }

        $password = $customer->user->decrypted_password;

        if (!$password) {
            return response()->json(['success' => false, 'message' => 'Password tidak tersedia.']);
        }

        $this->activityLog->log('customers', "Lihat password portal: {$customer->name}");

        return response()->json(['success' => true, 'password' => $password]);
    }

    /**
     * Reset portal account password
     */
    public function resetPortalPassword(Customer $customer)
    {
        $this->authorizeCustomer($customer);

        if (!$customer->user_id || !$customer->user) {
            return response()->json(['success' => false, 'message' => 'Pelanggan belum memiliki akun portal.'], 422);
        }

        $syncPppoe = request()->boolean('sync_pppoe');
        $password = $syncPppoe ? ($customer->decrypted_pppoe_password ?: Str::random(8)) : Str::random(8);

        $customer->user->update([
            'password' => Hash::make($password),
            'plain_password' => $password,
        ]);

        $this->activityLog->log('customers', "Reset password portal: {$customer->name}");

        return response()->json([
            'success' => true,
            'message' => 'Password portal berhasil direset.',
            'password' => $password,
        ]);
    }

    /**
     * Toggle portal account active/inactive
     */
    public function togglePortalStatus(Customer $customer)
    {
        $this->authorizeCustomer($customer);

        if (!$customer->user_id || !$customer->user) {
            return response()->json(['success' => false, 'message' => 'Pelanggan belum memiliki akun portal.'], 422);
        }

        $user = $customer->user;
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->activityLog->log('customers', "Akun portal {$status}: {$customer->name}");

        return response()->json([
            'success' => true,
            'message' => "Akun portal berhasil {$status}.",
        ]);
    }

    /**
     * Delete portal account
     */
    public function deletePortalAccount(Customer $customer)
    {
        $this->authorizeCustomer($customer);

        if (!$customer->user_id || !$customer->user) {
            return response()->json(['success' => false, 'message' => 'Pelanggan belum memiliki akun portal.'], 422);
        }

        try {
            DB::beginTransaction();
            $userName = $customer->user->name;
            $customer->user->delete();
            $customer->update(['user_id' => null]);
            $this->activityLog->log('customers', "Hapus akun portal: {$userName} ({$customer->customer_id})");
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Akun portal berhasil dihapus.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menghapus akun portal.'], 500);
        }
    }

    /**
     * Bulk generate portal accounts
     */
    public function bulkGeneratePortalAccount(Request $request)
    {
        $request->validate(['customer_ids' => 'required_without:select_all|array']);

        $customers = $this->resolveCustomerQuery($request)->get();

        if ($customers->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Tidak ada pelanggan yang ditemukan.'], 422);
        }

        $details = [];
        $successCount = 0;
        $skippedCount = 0;

        foreach ($customers as $customer) {
            // Skip yang sudah punya akun portal
            if ($customer->user_id) {
                $details[] = ['name' => $customer->name, 'success' => false, 'status' => 'Sudah punya akun portal'];
                $skippedCount++;
                continue;
            }

            $email = $customer->email ?: $customer->customer_id . '@portal.local';

            // Skip if email already used
            if (User::where('email', $email)->exists()) {
                $details[] = ['name' => $customer->name, 'success' => false, 'status' => "Email/ID {$email} sudah digunakan"];
                continue;
            }

            try {
                $password = $customer->decrypted_pppoe_password ?: Str::random(8);

                $portalUser = User::create([
                    'name' => $customer->name,
                    'email' => $email,
                    'phone' => $customer->phone,
                    'password' => Hash::make($password),
                    'plain_password' => $password,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);

                $portalUser->assignRole('client');
                $customer->update(['user_id' => $portalUser->id]);

                $details[] = ['name' => $customer->name, 'success' => true, 'status' => 'Akun dibuat'];
                $successCount++;
            } catch (\Exception $e) {
                $details[] = ['name' => $customer->name, 'success' => false, 'status' => $e->getMessage()];
            }
        }

        $this->activityLog->log('customers', "Bulk generate akun portal: {$successCount} berhasil, {$skippedCount} dilewati");

        $total = $customers->count();
        $message = "{$successCount} akun portal berhasil dibuat.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} dilewati (sudah punya akun).";
        }

        return response()->json([
            'success' => $successCount > 0,
            'message' => $message,
            'details' => $details,
        ]);
    }
}
