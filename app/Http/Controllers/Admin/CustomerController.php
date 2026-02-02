<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PopSetting;
use App\Models\Router;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Helpers\Mikrotik\MikrotikService;
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

class CustomerController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:customers.view', only: ['index', 'show', 'getData']),
            new Middleware('permission:customers.create', only: ['create', 'store']),
            new Middleware('permission:customers.edit', only: ['edit', 'update']),
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
        $query = Customer::with(['router', 'package', 'province', 'city'])
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->router_id, fn($q, $r) => $q->where('router_id', $r))
            ->when($request->search, function($q, $s) {
                $q->where(function($sq) use ($s) {
                    $sq->where('name', 'like', "%{$s}%")
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
        
        // Statistics
        $stats = [
            'total' => Customer::when($popId, fn($q) => $q->where('pop_id', $popId))->count(),
            'active' => Customer::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'active')->count(),
            'pending' => Customer::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'pending')->count(),
            'suspended' => Customer::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'suspended')->count(),
        ];
        
        return view('admin.customers.index', compact('customers', 'popUsers', 'popId', 'routers', 'stats'));
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
        
        return view('admin.customers.create', compact('routers', 'packages', 'provinces', 'nextCustomerId', 'popId', 'popSetting'));
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
            // PPPoE credentials - username wajib diisi manual
            'pppoe_username' => 'required|string|max:255',
            'pppoe_password' => 'nullable|string|max:255',
            'service_type' => 'nullable|in:pppoe,hotspot,static',
            'installation_date' => 'nullable|date',
            'monthly_fee' => 'nullable|numeric|min:0',
            'installation_fee' => 'nullable|numeric|min:0',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'create_user_account' => 'boolean',
            'sync_mikrotik' => 'boolean',
            'sync_radius' => 'boolean',
            'imported_from_mikrotik' => 'boolean', // Flag if imported from existing Mikrotik secret
            'photo_ktp' => 'nullable|string',
            'photo_selfie' => 'nullable|string',
            'photo_house' => 'nullable|string',
        ]);

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
            
            // PPPoE username - format: PREFIX-username
            $pppoeUsername = $request->pppoe_username;
            if (!$pppoeUsername) {
                // Auto-generate: PREFIX-123456
                $randomDigits = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $pppoeUsername = $prefix ? $prefix . '-' . $randomDigits : $randomDigits;
            } else {
                // Add prefix if not already present (format: PREFIX-username)
                if ($prefix && !str_starts_with($pppoeUsername, $prefix . '-')) {
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
                'pppoe_username' => $pppoeUsername,
                'pppoe_password' => $pppoePassword,
                'service_type' => $request->service_type ?? 'pppoe',
                'installation_date' => $request->installation_date ?? now()->toDateString(),
                'monthly_fee' => $request->monthly_fee ?? ($package->price ?? 0),
                'installation_fee' => $request->installation_fee ?? 0,
                'billing_day' => $request->billing_day ?? 1,
                'status' => 'pending',
                'notes' => $request->notes,
                'internal_notes' => $request->internal_notes,
                'registered_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]);

            // Sync tracking
            $syncResults = [];
            
            // Check if imported from Mikrotik (migration mode - don't create, just mark as synced)
            $importedFromMikrotik = $request->boolean('imported_from_mikrotik');
            
            // Determine sync targets
            $syncToMikrotik = $request->boolean('sync_mikrotik') && $popSetting?->mikrotik_sync_enabled && $router;
            $syncToRadius = $request->boolean('sync_radius') && $popSetting?->radius_enabled;
            
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
                        $params = [
                            'name' => $pppoeUsername,
                            'password' => $pppoePassword,
                            'profile' => $package->profile_name ?? $package->name,
                            'comment' => $customer->address,
                        ];
                        
                        $mikrotikResult = $mikrotikService->addPppSecret($params);
                        
                        if (isset($mikrotikResult['ret'])) {
                            $syncResults['mikrotik'] = 'success';
                            $customer->update(['mikrotik_synced' => true, 'mikrotik_synced_at' => now()]);
                        } else {
                            $syncResults['mikrotik'] = 'failed: ' . ($mikrotikResult[0]['message'] ?? 'Unknown error');
                            Log::warning("Mikrotik sync failed for customer {$customer->id}");
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
                    $radiusService = new RadiusService([
                        'host' => $popSetting->radius_host,
                        'port' => $popSetting->radius_port ?? 3306,
                        'database' => $popSetting->radius_database ?? 'radius',
                        'username' => $popSetting->radius_username,
                        'password' => $popSetting->decrypted_radius_password,
                    ]);
                    
                    // Get bandwidth from package
                    $bandwidth = null;
                    if ($package) {
                        $bandwidth = [
                            'download' => $package->download_rate . ($package->rate_unit ?? 'M'),
                            'upload' => $package->upload_rate . ($package->rate_unit ?? 'M'),
                        ];
                    }
                    
                    $radiusResult = $radiusService->createUser(
                        $pppoeUsername,
                        $pppoePassword,
                        $package->profile_name ?? $package->name,
                        $bandwidth,
                        [
                            'name' => $customer->name,
                            'phone' => $customer->phone,
                            'email' => $customer->email,
                            'address' => $customer->address,
                            'customer_id' => $customer->customer_id,
                        ]
                    );
                    
                    if ($radiusResult['success']) {
                        $syncResults['radius'] = 'success';
                        $customer->update(['radius_synced' => true, 'radius_synced_at' => now()]);
                    } else {
                        $syncResults['radius'] = 'failed: ' . ($radiusResult['error'] ?? 'Unknown error');
                        Log::warning("Radius sync failed for customer {$customer->id}: " . ($radiusResult['error'] ?? 'Unknown'));
                    }
                } catch (\Exception $e) {
                    $syncResults['radius'] = 'error: ' . $e->getMessage();
                    Log::error("Radius sync error for customer {$customer->id}: " . $e->getMessage());
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

            return response()->json([
                'success' => true,
                'message' => $message,
                'customer' => $customer,
                'sync_results' => $syncResults,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan pelanggan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show customer detail
     */
    public function show(Customer $customer)
    {
        $this->authorizeCustomer($customer);
        
        $customer->load(['router', 'package', 'province', 'city', 'district', 'village', 'user', 'invoices', 'payments']);
        
        return view('admin.customers.show', compact('customer'));
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
        
        return view('admin.customers.edit', compact('customer', 'routers', 'packages', 'provinces', 'cities', 'districts', 'villages'));
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
            'pppoe_username' => 'nullable|string|max:255',
            'pppoe_password' => 'nullable|string|max:255',
            'service_type' => 'nullable|in:pppoe,hotspot,static',
            'monthly_fee' => 'nullable|numeric|min:0',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'active_until' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->only([
                'name', 'email', 'phone', 'phone_alt', 'nik', 'birth_date', 'gender',
                'address', 'province_code', 'city_code', 'district_code', 'village_code',
                'postal_code', 'latitude', 'longitude', 'router_id', 'package_id',
                'pppoe_username', 'service_type', 'monthly_fee', 'billing_day', 
                'notes', 'internal_notes', 'remote_address', 'mac_address', 'active_until',
            ]);

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
            if ($customer->user && $request->email !== $customer->user->email) {
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
     * Check if PPPoE username is available
     */
    public function checkUsername(Request $request)
    {
        $existingCustomer = Customer::withTrashed()
            ->where('pppoe_username', $request->username)
            ->when($request->exclude_id, fn($q, $id) => $q->where('id', '!=', $id))
            ->first();

        $response = ['available' => !$existingCustomer];
        
        if ($existingCustomer && $existingCustomer->trashed()) {
            $response['message'] = 'Username ini pernah digunakan oleh pelanggan yang sudah dihapus';
            $response['was_deleted'] = true;
        }

        return response()->json($response);
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
}
