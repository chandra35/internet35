<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Olt;
use App\Models\Router;
use App\Models\User;
use App\Models\Customer;
use App\Helpers\Olt\OltFactory;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Exception;

class OltController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:olts.view', only: ['index', 'show']),
            new Middleware('permission:olts.create', only: ['create', 'store']),
            new Middleware('permission:olts.edit', only: ['edit', 'update']),
            new Middleware('permission:olts.delete', only: ['destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    protected function getPopId(Request $request)
    {
        $user = auth()->user();
        
        if ($user->hasRole('superadmin')) {
            return $request->input('pop_id') ?: $request->session()->get('manage_pop_id');
        }
        
        return $user->id;
    }

    /**
     * Display OLT list
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $popId = $this->getPopId($request);
        
        $popUsers = null;
        if ($user->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
            
            if ($request->has('pop_id')) {
                $request->session()->put('manage_pop_id', $request->input('pop_id'));
                $popId = $request->input('pop_id');
            }
        }
        
        $query = Olt::with(['pop', 'router', 'creator'])
            ->withCount(['onus', 'odcs', 'ponPorts'])
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->when($request->router_id, fn($q, $r) => $q->where('router_id', $r))
            ->when($request->brand, fn($q, $b) => $q->where('brand', $b))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, function($q, $s) {
                $q->where(function($sq) use ($s) {
                    $sq->where('name', 'like', "%{$s}%")
                       ->orWhere('code', 'like', "%{$s}%")
                       ->orWhere('ip_address', 'like', "%{$s}%");
                });
            });
        
        $routers = Router::when($popId, fn($q) => $q->where('pop_id', $popId))
            ->orderBy('name')->get();
        
        $olts = $query->orderBy('name')->paginate(15)->withQueryString();
        
        return view('admin.olts.index', compact('olts', 'popUsers', 'popId', 'routers'));
    }

    /**
     * Show create form
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        $popId = $this->getPopId($request);
        
        $popUsers = null;
        if ($user->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
        }
        
        $routers = Router::when($popId, fn($q) => $q->where('pop_id', $popId))
            ->orderBy('name')->get();
        
        $brands = Olt::BRANDS;
        
        return view('admin.olts.create', compact('popUsers', 'popId', 'routers', 'brands'));
    }

    /**
     * Store new OLT
     */
    public function store(Request $request)
    {
        $request->validate([
            'pop_id' => 'required|exists:users,id',
            'router_id' => 'nullable|exists:routers,id',
            'name' => 'required|string|max:255',
            'brand' => 'required|in:' . implode(',', array_keys(Olt::BRANDS)),
            'model' => 'nullable|string|max:100',
            'ip_address' => 'required|ip',
            'snmp_port' => 'nullable|integer|min:1|max:65535',
            'snmp_community' => 'nullable|string|max:100',
            'telnet_enabled' => 'nullable|boolean',
            'telnet_port' => 'nullable|integer|min:1|max:65535',
            'telnet_username' => 'nullable|string|max:100',
            'telnet_password' => 'nullable|string|max:255',
            'ssh_enabled' => 'nullable|boolean',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_username' => 'nullable|string|max:100',
            'ssh_password' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'total_pon_ports' => 'nullable|integer|min:1|max:64',
            'status' => 'nullable|in:active,inactive,maintenance',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        try {
            DB::beginTransaction();
            
            $popId = $request->pop_id;
            
            $olt = Olt::create([
                'pop_id' => $popId,
                'router_id' => $request->router_id,
                'name' => $request->name,
                'code' => Olt::generateCode($popId),
                'brand' => $request->brand,
                'model' => $request->model,
                'ip_address' => $request->ip_address,
                'snmp_port' => $request->snmp_port ?? 161,
                'snmp_community' => $request->snmp_community ?? 'public',
                'telnet_enabled' => $request->boolean('telnet_enabled'),
                'telnet_port' => $request->telnet_port ?? 23,
                'telnet_username' => $request->telnet_username,
                'telnet_password' => $request->telnet_password,
                'ssh_enabled' => $request->boolean('ssh_enabled'),
                'ssh_port' => $request->ssh_port ?? 22,
                'ssh_username' => $request->ssh_username,
                'ssh_password' => $request->ssh_password,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'total_pon_ports' => $request->total_pon_ports ?? 8,
                'total_uplink_ports' => $request->total_uplink_ports ?? 4,
                'max_onu_per_port' => $request->max_onu_per_port ?? 128,
                'status' => $request->status ?? 'active',
                'address' => $request->address,
                'description' => $request->description,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);
            
            // Upload photos if provided
            if ($request->hasFile('photos')) {
                $olt->uploadPhotos($request->file('photos'));
            }
            
            $this->activityLog->log('olts', "Created OLT: {$olt->name} ({$olt->code})");
            
            DB::commit();
            
            return redirect()->route('admin.olts.show', $olt)
                ->with('success', 'OLT created successfully');
                
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create OLT: ' . $e->getMessage());
        }
    }

    /**
     * Show OLT detail
     */
    public function show(Olt $olt)
    {
        // Load only essential relations
        $olt->load(['pop', 'router', 'creator', 'ponPorts', 'odcs']);
        
        // Load ONUs with pagination-friendly query (only first 100 for display)
        // Full list can be loaded via AJAX if needed
        $onus = $olt->onus()
            ->with('customer:id,name,customer_id')
            ->orderBy('port')
            ->orderBy('onu_id')
            ->limit(100)
            ->get();
        
        // Assign to OLT for view compatibility
        $olt->setRelation('onus', $onus);
        
        // Get ONU statistics with efficient queries (no full load)
        $onuStats = [
            'total' => $olt->onus()->count(),
            'online' => $olt->onus()->where('status', 'online')->count(),
            'offline' => $olt->onus()->whereIn('status', ['offline', 'los', 'dying_gasp'])->count(),
            'weak_signal' => $olt->onus()->where('olt_rx_power', '<', -26)->count(),
        ];
        
        // Get profiles for this OLT
        $profiles = \App\Models\OltProfile::where('olt_id', $olt->id)
            ->orderBy('name')
            ->get();
        
        // Skip loading customers here - use AJAX search instead
        $customers = collect();
        
        return view('admin.olts.show', compact('olt', 'onuStats', 'profiles', 'customers'));
    }

    /**
     * Show edit form
     */
    public function edit(Olt $olt)
    {
        $user = auth()->user();
        
        $popUsers = null;
        if ($user->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
        }
        
        $routers = Router::where('pop_id', $olt->pop_id)->orderBy('name')->get();
        $brands = Olt::BRANDS;
        
        return view('admin.olts.edit', compact('olt', 'popUsers', 'routers', 'brands'));
    }

    /**
     * Update OLT
     */
    public function update(Request $request, Olt $olt)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'required|in:' . implode(',', array_keys(Olt::BRANDS)),
            'model' => 'nullable|string|max:100',
            'ip_address' => 'required|ip',
            'snmp_community' => 'nullable|string|max:100',
            'telnet_enabled' => 'nullable|boolean',
            'telnet_username' => 'nullable|string|max:100',
            'ssh_enabled' => 'nullable|boolean',
            'ssh_username' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'nullable|in:active,inactive,maintenance,offline',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        try {
            DB::beginTransaction();
            
            $data = [
                'name' => $request->name,
                'router_id' => $request->router_id,
                'brand' => $request->brand,
                'model' => $request->model,
                'ip_address' => $request->ip_address,
                'snmp_port' => $request->snmp_port ?? 161,
                'snmp_community' => $request->snmp_community,
                'telnet_enabled' => $request->boolean('telnet_enabled'),
                'telnet_port' => $request->telnet_port ?? 23,
                'telnet_username' => $request->telnet_username,
                'ssh_enabled' => $request->boolean('ssh_enabled'),
                'ssh_port' => $request->ssh_port ?? 22,
                'ssh_username' => $request->ssh_username,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'total_pon_ports' => $request->total_pon_ports,
                'status' => $request->status,
                'address' => $request->address,
                'description' => $request->description,
                'notes' => $request->notes,
            ];
            
            // Only update passwords if provided
            if ($request->filled('telnet_password')) {
                $data['telnet_password'] = $request->telnet_password;
            }
            if ($request->filled('ssh_password')) {
                $data['ssh_password'] = $request->ssh_password;
            }
            
            $olt->update($data);
            
            // Handle photo removal
            if ($request->has('remove_photos')) {
                foreach ($request->remove_photos as $photo) {
                    $olt->removePhoto($photo);
                }
            }
            
            // Upload new photos
            if ($request->hasFile('photos')) {
                $olt->addPhotos($request->file('photos'));
            }
            
            $this->activityLog->log('olts', "Updated OLT: {$olt->name} ({$olt->code})");
            
            DB::commit();
            
            return redirect()->route('admin.olts.show', $olt)
                ->with('success', 'OLT updated successfully');
                
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update OLT: ' . $e->getMessage());
        }
    }

    /**
     * Delete OLT
     */
    public function destroy(Olt $olt)
    {
        try {
            DB::beginTransaction();
            
            $name = $olt->name;
            $code = $olt->code;
            
            $olt->delete();
            
            $this->activityLog->log('olts', "Deleted OLT: {$name} ({$code})");
            
            DB::commit();
            
            return redirect()->route('admin.olts.index')
                ->with('success', 'OLT deleted successfully');
                
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete OLT: ' . $e->getMessage());
        }
    }

    /**
     * Test connection to OLT with progress
     */
    public function testConnection(Olt $olt)
    {
        try {
            $helper = OltFactory::make($olt);
            $result = $helper->testConnection();
            
            return response()->json($result);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Test connection with streaming progress
     */
    public function testConnectionStream(Olt $olt)
    {
        return response()->stream(function() use ($olt) {
            // Disable output buffering
            if (ob_get_level()) ob_end_clean();
            
            $this->sendProgress('Memulai test koneksi...', 0);
            
            try {
                // Step 1: Ping test
                $this->sendProgress('Mengecek konektivitas ke ' . $olt->ip_address . '...', 10);
                sleep(1);
                
                // Step 2: SNMP test
                $this->sendProgress('Menguji koneksi SNMP (port ' . $olt->snmp_port . ')...', 30);
                
                $sysName = @snmp2_get($olt->ip_address, $olt->snmp_community, '.1.3.6.1.2.1.1.5.0', 3000000, 2);
                
                if ($sysName !== false) {
                    $this->sendProgress('SNMP berhasil! System Name: ' . $sysName, 50);
                } else {
                    $this->sendProgress('SNMP tidak merespon, melanjutkan...', 50, 'warning');
                }
                
                // Step 3: Telnet test (if enabled)
                if ($olt->telnet_enabled) {
                    $this->sendProgress('Menguji koneksi Telnet (port ' . $olt->telnet_port . ')...', 70);
                    
                    $telnet = @fsockopen($olt->ip_address, $olt->telnet_port, $errno, $errstr, 5);
                    if ($telnet) {
                        fclose($telnet);
                        $this->sendProgress('Telnet berhasil terhubung!', 80);
                    } else {
                        $this->sendProgress('Telnet tidak dapat terhubung: ' . $errstr, 80, 'warning');
                    }
                }
                
                // Step 4: SSH test (if enabled)
                if ($olt->ssh_enabled) {
                    $this->sendProgress('Menguji koneksi SSH (port ' . $olt->ssh_port . ')...', 90);
                    
                    $ssh = @fsockopen($olt->ip_address, $olt->ssh_port, $errno, $errstr, 5);
                    if ($ssh) {
                        fclose($ssh);
                        $this->sendProgress('SSH berhasil terhubung!', 95);
                    } else {
                        $this->sendProgress('SSH tidak dapat terhubung: ' . $errstr, 95, 'warning');
                    }
                }
                
                // Complete
                $this->sendProgress('Test koneksi selesai!', 100, 'success');
                $this->sendComplete(true, 'Koneksi ke OLT berhasil');
                
            } catch (Exception $e) {
                $this->sendProgress('Error: ' . $e->getMessage(), 100, 'error');
                $this->sendComplete(false, $e->getMessage());
            }
            
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Sync OLT data with streaming progress
     */
    public function syncStream(Olt $olt)
    {
        return response()->stream(function() use ($olt) {
            // Disable output buffering
            if (ob_get_level()) ob_end_clean();
            
            // Set max execution time
            set_time_limit(300);
            
            $this->sendProgress('Memulai sinkronisasi OLT...', 0);
            
            try {
                $helper = OltFactory::make($olt);
                
                // Step 1: Test connection first
                $this->sendProgress('Menguji koneksi SNMP ke ' . $olt->ip_address . '...', 5);
                
                // Step 2: Get PON ports
                $this->sendProgress('Mengambil informasi PON ports via SNMP...', 10);
                $ponPorts = $helper->getPonPorts();
                $ponCount = count($ponPorts);
                $this->sendProgress("Ditemukan {$ponCount} PON ports", 15);
                
                // Step 3: Get all ONUs
                $this->sendProgress('Mengambil daftar ONU dari OLT...', 20);
                $this->sendProgress('Mencoba SNMP OIDs...', 25);
                
                $allOnus = $helper->getAllOnus();
                $total = count($allOnus);
                
                if ($total === 0) {
                    $this->sendProgress('Tidak ada ONU ditemukan di OLT', 30, 'warning');
                } else {
                    $this->sendProgress("Ditemukan {$total} ONU", 30);
                }
                
                // Step 4: Run full sync
                $this->sendProgress('Menjalankan sinkronisasi penuh...', 35);
                
                $result = $helper->syncAll();
                
                // Step 5: Report results
                $this->sendProgress('Memperbarui status OLT...', 90);
                
                // Complete
                $onusSynced = $result['onus_synced'] ?? 0;
                $signalsRecorded = $result['signals_recorded'] ?? 0;
                $errors = $result['errors'] ?? [];
                
                $message = "Sync selesai. ONU: {$onusSynced}, Signal: {$signalsRecorded}";
                if (!empty($errors)) {
                    $message .= " (dengan " . count($errors) . " error)";
                    foreach (array_slice($errors, 0, 3) as $err) {
                        $this->sendProgress($err, 95, 'warning');
                    }
                }
                
                $this->sendProgress($message, 100, 'success');
                $this->sendComplete($result['success'] ?? true, $message, [
                    'onus_synced' => $onusSynced,
                    'signals_recorded' => $signalsRecorded,
                    'errors' => $errors,
                ]);
                
            } catch (Exception $e) {
                $this->sendProgress('Error: ' . $e->getMessage(), 100, 'error');
                $this->sendComplete(false, $e->getMessage());
            }
            
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Send SSE progress message
     */
    protected function sendProgress(string $message, int $percent, string $status = 'info'): void
    {
        echo "data: " . json_encode([
            'type' => 'progress',
            'message' => $message,
            'percent' => $percent,
            'status' => $status,
            'time' => now()->format('H:i:s'),
        ]) . "\n\n";
        
        if (ob_get_level()) ob_flush();
        flush();
    }

    /**
     * Send SSE complete message
     */
    protected function sendComplete(bool $success, string $message, array $data = []): void
    {
        echo "data: " . json_encode(array_merge([
            'type' => 'complete',
            'success' => $success,
            'message' => $message,
        ], $data)) . "\n\n";
        
        if (ob_get_level()) ob_flush();
        flush();
    }

    /**
     * Sync OLT data (original - redirect version)
     */
    public function sync(Olt $olt)
    {
        try {
            $helper = OltFactory::make($olt);
            $result = $helper->syncAll();
            
            if ($result['success']) {
                return back()->with('success', "Sync completed. ONUs: {$result['onus_synced']}, Signals recorded: {$result['signals_recorded']}");
            } else {
                return back()->with('warning', 'Sync completed with errors: ' . implode(', ', $result['errors']));
            }
            
        } catch (Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Get OLTs by router (AJAX)
     */
    public function getByRouter(Request $request)
    {
        $routerId = $request->router_id;
        
        $olts = Olt::where('router_id', $routerId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'brand']);
        
        return response()->json($olts);
    }

    /**
     * Generate code for OLT
     */
    public function generateCode(Request $request)
    {
        $popId = $request->pop_id;
        $code = Olt::generateCode($popId);
        
        return response()->json(['code' => $code]);
    }

    /**
     * Get unregistered ONUs
     */
    public function getUnregisteredOnus(Olt $olt)
    {
        try {
            $helper = OltFactory::make($olt);
            $onus = $helper->getUnregisteredOnus();
            
            return response()->json([
                'success' => true,
                'onus' => $onus,
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get ONU list for OLT
     */
    public function getOnus(Olt $olt, Request $request)
    {
        $query = $olt->onus()
            ->with(['customer', 'odp'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->port, fn($q, $p) => $q->where('port', $p))
            ->when($request->search, function($q, $s) {
                $q->where(function($sq) use ($s) {
                    $sq->where('serial_number', 'like', "%{$s}%")
                       ->orWhere('name', 'like', "%{$s}%")
                       ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$s}%"));
                });
            });
        
        $onus = $query->orderBy('port')
            ->orderBy('onu_id')
            ->paginate(20)
            ->withQueryString();
        
        if ($request->ajax()) {
            return response()->json($onus);
        }
        
        return view('admin.olts.onus', compact('olt', 'onus'));
    }

    /**
     * Get signal history for chart
     */
    public function getSignalHistory(Olt $olt, Request $request)
    {
        $onuId = $request->onu_id;
        $hours = $request->hours ?? 24;
        
        $history = $olt->signalHistories()
            ->when($onuId, fn($q) => $q->where('onu_id', $onuId))
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at')
            ->get(['recorded_at', 'olt_rx_power', 'rx_power', 'tx_power', 'status']);
        
        return response()->json($history);
    }

    /**
     * Identify OLT - Auto-detect brand, model, and board info
     */
    public function identify(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'connection_method' => 'nullable|in:snmp,telnet,ssh',
            'brand' => 'nullable|string|in:zte,huawei,vsol,hioso,hsgq',
            'snmp_port' => 'nullable|integer|min:1|max:65535',
            'snmp_community' => 'nullable|string|max:100',
            'telnet_enabled' => 'nullable',
            'telnet_port' => 'nullable|integer|min:1|max:65535',
            'telnet_username' => 'nullable|string|max:100',
            'telnet_password' => 'nullable|string|max:255',
            'ssh_enabled' => 'nullable',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_username' => 'nullable|string|max:100',
            'ssh_password' => 'nullable|string|max:255',
        ]);

        try {
            $credentials = [];
            $connectionMethod = $request->input('connection_method', 'snmp');
            
            // Pass brand hint if provided
            if ($request->filled('brand')) {
                $credentials['brand'] = $request->brand;
            }
            
            // Check telnet_enabled
            $telnetEnabled = filter_var($request->input('telnet_enabled'), FILTER_VALIDATE_BOOLEAN);
            if ($telnetEnabled || $connectionMethod === 'telnet') {
                $credentials['telnet_enabled'] = true;
                $credentials['telnet_port'] = $request->telnet_port ?? 23;
                $credentials['telnet_username'] = $request->telnet_username;
                $credentials['telnet_password'] = $request->telnet_password;
            }

            // Check ssh_enabled
            $sshEnabled = filter_var($request->input('ssh_enabled'), FILTER_VALIDATE_BOOLEAN);
            if ($sshEnabled || $connectionMethod === 'ssh') {
                $credentials['ssh_enabled'] = true;
                $credentials['ssh_port'] = $request->ssh_port ?? 22;
                $credentials['ssh_username'] = $request->ssh_username;
                $credentials['ssh_password'] = $request->ssh_password;
            }

            $result = OltFactory::identify(
                $request->ip_address,
                $request->snmp_port ?? 161,
                $request->snmp_community ?? 'public',
                $credentials
            );

            return response()->json($result);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get interface traffic statistics
     */
    public function getTrafficStats(Olt $olt, Request $request)
    {
        try {
            // Support force refresh by clearing cache
            if ($request->has('refresh') || $request->has('force')) {
                $cacheKey = "olt_traffic_{$olt->id}";
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
            }
            
            $helper = OltFactory::make($olt);
            $summary = $helper->getTrafficSummary();
            
            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
