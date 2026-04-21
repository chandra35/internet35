<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Onu;
use App\Models\Olt;
use App\Models\Odp;
use App\Models\Customer;
use App\Models\OltProfile;
use App\Helpers\Olt\OltFactory;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Exception;

class OnuController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:onus.view', only: ['index', 'show']),
            new Middleware('permission:onus.create', only: ['create', 'store', 'register', 'registerPage', 'scanUnregistered', 'getOltRegisterData']),
            new Middleware('permission:onus.edit', only: ['edit', 'update', 'assignCustomer']),
            new Middleware('permission:onus.delete', only: ['destroy', 'unregister']),
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
     * Display ONU list
     */
    public function index(Request $request)
    {
        $popId = $this->getPopId($request);
        
        $query = Onu::with(['olt', 'customer', 'odp'])
            ->whereHas('olt', function($q) use ($popId) {
                if ($popId) {
                    $q->where('pop_id', $popId);
                }
            })
            ->when($request->olt_id, fn($q, $o) => $q->where('olt_id', $o))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->signal, function($q, $s) {
                if ($s === 'good') {
                    $q->where('rx_power', '>=', -25);
                } elseif ($s === 'warning') {
                    $q->whereBetween('rx_power', [-27, -25]);
                } elseif ($s === 'bad') {
                    $q->where('rx_power', '<', -27);
                }
            })
            ->when($request->unassigned, fn($q) => $q->whereNull('customer_id'))
            ->when($request->search, function($q, $s) {
                $q->where(function($sq) use ($s) {
                    $sq->where('serial_number', 'like', "%{$s}%")
                       ->orWhere('name', 'like', "%{$s}%")
                       ->orWhere('mac_address', 'like', "%{$s}%");
                });
            });
        
        $onus = $query->orderBy('olt_id')
            ->orderBy('port')
            ->orderBy('onu_id')
            ->paginate(20)
            ->withQueryString();
        
        $olts = Olt::when($popId, fn($q) => $q->where('pop_id', $popId))
            ->orderBy('name')
            ->get();
        
        // Statistics
        $baseQuery = Onu::query();
        if ($popId) {
            $baseQuery->whereHas('olt', fn($q) => $q->where('pop_id', $popId));
        }
        
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'online' => (clone $baseQuery)->where('status', 'online')->count(),
            'offline' => (clone $baseQuery)->where('status', 'offline')->count(),
            'los' => (clone $baseQuery)->where('status', 'los')->count(),
        ];
        
        return view('admin.onus.index', compact('onus', 'olts', 'stats', 'popId'));
    }

    /**
     * Show ONU detail
     */
    public function show(Onu $onu)
    {
        $onu->load(['olt', 'customer', 'odp', 'zone', 'ponPort', 'creator']);
        
        // Get signal history for chart
        $signalHistory = $onu->signalHistories()
            ->where('recorded_at', '>=', now()->subDays(7))
            ->orderBy('recorded_at')
            ->get();
        
        // Prepare chart data
        $chartLabels = $signalHistory->pluck('recorded_at')->map(fn($d) => $d->format('d/m H:i'))->toArray();
        $chartRxData = $signalHistory->pluck('rx_power')->toArray();
        $chartTxData = $signalHistory->pluck('tx_power')->toArray();
        
        // Get customers for assignment modal
        $customers = Customer::where('pop_id', $onu->olt->pop_id)
            ->whereDoesntHave('onu')
            ->orderBy('name')
            ->get();

        // Get OLT profiles (TCONT + Traffic) from DB for re-apply profile form
        $oltTcontProfiles  = \App\Models\OltProfile::where('olt_id', $onu->olt_id)
            ->tcontProfiles()->orderBy('name')->get();
        $oltTrafficProfiles = \App\Models\OltProfile::where('olt_id', $onu->olt_id)
            ->trafficProfiles()->orderBy('name')->get();

        return view('admin.onus.show', compact(
            'onu', 'signalHistory', 'customers',
            'chartLabels', 'chartRxData', 'chartTxData',
            'oltTcontProfiles', 'oltTrafficProfiles'
        ));
    }

    /**
     * Show register ONU form
     */
    public function registerForm(Olt $olt)
    {
        // Get unregistered ONUs from OLT
        try {
            $helper = OltFactory::make($olt);
            $unregisteredOnus = $helper->getUnregisteredOnus();
        } catch (Exception $e) {
            $unregisteredOnus = [];
        }
        
        // Get ODPs for assignment
        $odps = Odp::where('pop_id', $olt->pop_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        
        // Get customers without ONU
        $customers = Customer::where('pop_id', $olt->pop_id)
            ->whereDoesntHave('onu')
            ->orderBy('name')
            ->get();
        
        return view('admin.onus.register', compact('olt', 'unregisteredOnus', 'odps', 'customers'));
    }

    /**
     * Standalone register ONU page - select OLT first
     */
    public function registerPage()
    {
        $user = auth()->user();

        if ($user->hasRole('superadmin')) {
            $olts = Olt::where('status', 'active')->orderBy('name')->get();
        } else {
            $olts = Olt::where('pop_id', $user->id)->where('status', 'active')->orderBy('name')->get();
        }

        return view('admin.onus.register-page', compact('olts'));
    }

    /**
     * Scan unregistered ONUs on specific OLT (AJAX)
     */
    public function scanUnregistered(Olt $olt)
    {
        try {
            $helper = OltFactory::make($olt);
            $unregisteredOnus = $helper->getUnregisteredOnus();

            return response()->json([
                'success' => true,
                'data' => $unregisteredOnus,
                'count' => count($unregisteredOnus),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal scan ONU: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Get OLT-specific data for register form (zones, profiles) via AJAX
     */
    public function getOltRegisterData(Olt $olt)
    {
        $zones = \App\Models\Zone::where('olt_id', $olt->id)->orderBy('name')
            ->get(['id', 'name']);

        $profiles = OltProfile::where('olt_id', $olt->id)->orderBy('name')
            ->get(['id', 'name', 'type', 'config']);

        $vlans = \App\Models\OltVlan::where('olt_id', $olt->id)
            ->orderBy('vlan_id')
            ->get(['vlan_id', 'name', 'type']);

        return response()->json([
            'success' => true,
            'olt' => [
                'id' => $olt->id,
                'name' => $olt->name,
                'brand' => $olt->brand,
                'model' => $olt->model,
            ],
            'zones' => $zones,
            'profiles' => $profiles,
            'vlans' => $vlans,
        ]);
    }

    /**
     * Register ONU on OLT
     */
    public function register(Request $request)
    {
        $request->validate([
            'olt_id' => 'required|exists:olts,id',
            'serial_number' => 'required|string|max:20',
            'onu_type' => 'nullable|string|max:50',
            'slot' => 'nullable|integer|min:0',
            'port' => 'nullable|integer|min:1',
            'pon_port' => 'nullable|string|max:50',
            'onu_id' => 'nullable|integer|min:1|max:128',
            'name' => 'nullable|string|max:100',
            'customer_id' => 'nullable|exists:customers,id',
            'zone_id' => 'nullable|exists:zones,id',
            'odp_id' => 'nullable|exists:odps,id',
            'odp_port' => 'nullable|integer|min:1',
            'vlan' => 'nullable|integer|min:1|max:4094',
            'vlan_id' => 'required|integer|min:1|max:4094',
            'mgmt_vlan' => 'nullable|integer|min:1|max:4094',
            'pppoe_username' => 'nullable|string|max:100',
            'pppoe_password' => 'nullable|string|max:100',
            'gem_port' => 'nullable|integer|min:1',
            'tcont_id' => 'nullable|integer|min:1',
            'service_id' => 'nullable|integer|min:1',
            'service_port_mode' => 'nullable|string|max:50',
            'profile_id' => 'nullable|exists:olt_profiles,id',
            'line_profile' => 'nullable|string|max:255',
            'service_profile' => 'nullable|string|max:255',
            'traffic_profile' => 'nullable|string|max:64',
            'wan_mode' => 'nullable|in:skip,omci,tr069',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            $olt = Olt::findOrFail($request->olt_id);
            [$slot, $port] = $this->resolvePonCoordinates($request);
            [$lineProfile, $serviceProfile] = $this->resolveProvisioningProfiles($request);
            $profileConfig = $this->resolveProfileConfigs($olt->id, $lineProfile, $serviceProfile);

            if ($slot === null || $port === null) {
                return $this->registerResponse(
                    $request,
                    false,
                    'Posisi PON tidak valid. Silakan scan ulang ONU lalu coba lagi.'
                );
            }

            $helper = OltFactory::make($olt);
            
            $params = [
                'serial_number' => strtoupper($request->serial_number),
                'slot' => $slot,
                'port' => $port,
                'onu_id' => $request->onu_id,
                'name' => $request->name ?? $request->serial_number,
                'line_profile' => $lineProfile,
                'service_profile' => $serviceProfile,
                'onu_type' => $request->onu_type ?: null,
            ];
            
            // Pass management VLAN for TR069 DHCP
            if ($request->filled('mgmt_vlan')) {
                $params['mgmt_vlan'] = (int) $request->mgmt_vlan;
            }

            // Pass traffic (downstream shaping) profile
            if ($request->filled('traffic_profile')) {
                $params['traffic_profile'] = $request->traffic_profile;
            }

            // Pass PPPoE credentials only for OMCI mode (ZTE pon-onu-mng)
            $wanMode = $request->input('wan_mode', 'skip');
            if ($wanMode === 'omci' && $request->filled('pppoe_username')) {
                $params['pppoe_username'] = $request->pppoe_username;
                $params['pppoe_password'] = $request->pppoe_password ?? '';
            }

            $resolvedConfig = [
                'vlan' => $request->filled('vlan') ? (int) $request->vlan : null,
                'vlan_id' => $request->filled('vlan_id') ? (int) $request->vlan_id : null,
                'gem_port' => $request->filled('gem_port') ? (int) $request->gem_port : null,
                'tcont_id' => $request->filled('tcont_id') ? (int) $request->tcont_id : null,
                'service_id' => $request->filled('service_id') ? (int) $request->service_id : null,
                'service_port_mode' => $request->filled('service_port_mode') ? $request->service_port_mode : null,
            ];

            foreach ($profileConfig as $key => $value) {
                if (($resolvedConfig[$key] ?? null) === null) {
                    $resolvedConfig[$key] = $value;
                }
            }

            $resolvedConfig = $this->normalizeProvisioningConfig($resolvedConfig);

            $vlanValue = $resolvedConfig['vlan_id'] ?? $resolvedConfig['vlan'];
            if ($vlanValue !== null) {
                $params['vlan'] = (int) $vlanValue;
            }

            foreach (['gem_port', 'tcont_id', 'service_id', 'service_port_mode'] as $configKey) {
                if (($resolvedConfig[$configKey] ?? null) !== null) {
                    $params[$configKey] = $resolvedConfig[$configKey];
                }
            }
            
            $result = $helper->registerOnu($params);
            
            if ($result['success']) {
                // Save to database — handle re-registration of soft-deleted ONU
                $onuData = [
                    'slot' => $slot,
                    'port' => $port,
                    'onu_id' => $result['onu_id'],
                    'name' => $request->name,
                    'onu_type' => $request->onu_type ?: $this->detectOnuType($request->serial_number),
                    'customer_id' => $request->customer_id,
                    'zone_id' => $request->zone_id,
                    'odp_id' => $request->odp_id,
                    'odp_port' => $request->odp_port,
                    'line_profile' => $lineProfile,
                    'service_profile' => $serviceProfile,
                    'vlan_config' => $this->buildVlanConfigPayload([
                        'vlan_id' => $vlanValue,
                        'gem_port' => $resolvedConfig['gem_port'] ?? null,
                        'tcont_id' => $resolvedConfig['tcont_id'] ?? null,
                        'service_id' => $resolvedConfig['service_id'] ?? null,
                        'service_port_mode' => $resolvedConfig['service_port_mode'] ?? null,
                    ]),
                    'description' => $request->description,
                    'mgmt_ip' => $request->filled('mgmt_vlan') ? 'dhcp:vlan:' . $request->mgmt_vlan : null,
                    'pppoe_username' => $request->pppoe_username,
                    'config_status' => 'registered',
                    'status' => 'unknown',
                    'created_by' => auth()->id(),
                ];

                $onu = Onu::withTrashed()
                    ->where('olt_id', $olt->id)
                    ->where('serial_number', strtoupper($request->serial_number))
                    ->first();

                if ($onu) {
                    if ($onu->trashed()) {
                        $onu->restore();
                    }
                    $onu->update($onuData);
                } else {
                    $onu = Onu::create(array_merge($onuData, [
                        'olt_id' => $olt->id,
                        'serial_number' => strtoupper($request->serial_number),
                    ]));
                }
                
                $this->activityLog->log('onus', "Registered ONU: {$onu->serial_number} on {$olt->name}");

                // TR-069 mode: push PPPoE to ACS after ONU is saved in DB
                $acsMessage = '';
                if ($wanMode === 'tr069' && $request->filled('pppoe_username')) {
                    try {
                        $genieacs = new \App\Services\GenieAcsService();
                        $acsDevice = $genieacs->findDeviceBySerial($onu->serial_number);
                        if ($acsDevice) {
                            $acsResult = $genieacs->configureWanPppoe($acsDevice['device_id'], [
                                'username' => $request->pppoe_username,
                                'password' => $request->pppoe_password ?? '',
                                'vlan'     => $vlanValue,
                            ]);
                            $acsMessage = $acsResult['success']
                                ? ' PPPoE WAN berhasil dikonfigurasi via TR-069.'
                                : ' Credentials tersimpan — ONU belum terhubung ke ACS, akan dikonfigurasi otomatis setelah online.';
                        } else {
                            $acsMessage = ' Credentials tersimpan — ONU belum terhubung ke ACS, akan dikonfigurasi otomatis setelah online.';
                        }
                    } catch (\Exception $e) {
                        \Log::warning('TR-069 WAN push post-register failed: ' . $e->getMessage());
                        $acsMessage = ' Credentials tersimpan — push ke ACS gagal, coba lagi dari halaman ONU.';
                    }
                }

                return $this->registerResponse(
                    $request,
                    true,
                    $result['message'] . $acsMessage,
                    ['redirect_url' => route('admin.onus.show', $onu)]
                );
            } else {
                return $this->registerResponse($request, false, $result['message']);
            }
            
        } catch (Exception $e) {
            return $this->registerResponse($request, false, 'Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Unregister ONU from OLT
     */
    public function unregister(Onu $onu)
    {
        try {
            $helper = OltFactory::make($onu->olt);
            $result = $helper->unregisterOnu($onu->slot, $onu->port, $onu->onu_id);
            
            if ($result['success']) {
                $serial  = $onu->serial_number;
                $oltId   = $onu->olt_id;
                $port    = $onu->port;
                $onu->delete();
                
                $this->activityLog->log('onus', "Unregistered ONU: {$serial}");
                
                return redirect()
                    ->route('admin.olts.onus', ['olt' => $oltId, 'port' => $port])
                    ->with('success', $result['message']);
            } else {
                return back()->with('error', $result['message']);
            }
            
        } catch (Exception $e) {
            return back()->with('error', 'Unregister failed: ' . $e->getMessage());
        }
    }

    /**
     * Reboot ONU
     */
    public function reboot(Onu $onu)
    {
        try {
            $helper = OltFactory::make($onu->olt);
            $result = $helper->rebootOnu($onu->slot, $onu->port, $onu->onu_id);
            
            $this->activityLog->log('onus', "Rebooted ONU: {$onu->serial_number}");
            
            if ($result['success']) {
                return back()->with('success', $result['message']);
            } else {
                return back()->with('warning', $result['message']);
            }
            
        } catch (Exception $e) {
            return back()->with('error', 'Reboot failed: ' . $e->getMessage());
        }
    }

    public function factoryReset(Onu $onu)
    {
        try {
            $helper = OltFactory::make($onu->olt);
            $result = $helper->resetOnuFactory($onu->slot, $onu->port, $onu->onu_id);

            $this->activityLog->log('onus', "Factory reset ONU: {$onu->serial_number}");

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Factory reset failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign customer to ONU
     */
    public function assignCustomer(Request $request, Onu $onu)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'odp_id' => 'nullable|exists:odps,id',
            'odp_port' => 'nullable|integer|min:1',
        ]);

        try {
            $onu->update([
                'customer_id' => $request->customer_id,
                'odp_id' => $request->odp_id,
                'odp_port' => $request->odp_port,
            ]);
            
            // Update customer with ONU info
            $customer = Customer::find($request->customer_id);
            $customer->update([
                'odp_id' => $request->odp_id,
                'odp_port' => $request->odp_port,
            ]);
            
            $this->activityLog->log('onus', "Assigned customer {$customer->name} to ONU {$onu->serial_number}");
            
            return back()->with('success', 'Customer assigned successfully');
            
        } catch (Exception $e) {
            return back()->with('error', 'Assignment failed: ' . $e->getMessage());
        }
    }

    /**
     * Refresh ONU data from OLT
     */
    public function refresh(Onu $onu)
    {
        try {
            $helper = OltFactory::make($onu->olt);
            
            // Get ONU info
            $info = $helper->getOnuInfo($onu->slot, $onu->port, $onu->onu_id);
            $traffic = $helper->getOnuTraffic($onu->slot, $onu->port, $onu->onu_id);
            
            // Update ONU
            $onu->update(array_merge($info, $traffic, [
                'last_sync_at' => now(),
            ]));
            
            // Save signal history
            $onu->signalHistories()->create([
                'olt_id' => $onu->olt_id,
                'rx_power' => $info['rx_power'] ?? null,
                'tx_power' => $info['tx_power'] ?? null,
                'olt_rx_power' => $info['olt_rx_power'] ?? null,
                'temperature' => $info['temperature'] ?? null,
                'voltage' => $info['voltage'] ?? null,
                'status' => $info['status'] ?? null,
                'distance' => $info['distance'] ?? null,
                'recorded_at' => now(),
            ]);
            
            return back()->with('success', 'ONU data refreshed');
            
        } catch (Exception $e) {
            return back()->with('error', 'Refresh failed: ' . $e->getMessage());
        }
    }

    /**
     * Get ONU info via AJAX
     */
    public function getInfo(Onu $onu)
    {
        try {
            $helper = OltFactory::make($onu->olt);
            $info = $helper->getOnuInfo($onu->slot, $onu->port, $onu->onu_id);
            
            return response()->json([
                'success' => true,
                'data' => $info,
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get signal history for ONU
     */
    public function signalHistory(Onu $onu, Request $request)
    {
        $period = $request->period ?? '7d';
        
        // Parse period
        $hours = match($period) {
            '24h' => 24,
            '7d' => 24 * 7,
            '30d' => 24 * 30,
            default => 24 * 7,
        };
        
        $history = $onu->signalHistories()
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at')
            ->get();
        
        return response()->json([
            'labels' => $history->pluck('recorded_at')->map(fn($d) => $d->format('d/m H:i'))->toArray(),
            'rx_data' => $history->pluck('rx_power')->toArray(),
            'tx_data' => $history->pluck('tx_power')->toArray(),
        ]);
    }

    /**
     * Refresh ONU signal/optical power via AJAX (realtime)
     */
    public function refreshSignal(Onu $onu)
    {
        try {
            $helper = OltFactory::make($onu->olt);
            
            // Get optical info only (faster than full refresh)
            $opticalInfo = $helper->getOnuOpticalInfo($onu->slot ?? 0, $onu->port, $onu->onu_id);
            
            // Get traffic info
            $trafficInfo = $helper->getOnuTraffic($onu->slot ?? 0, $onu->port, $onu->onu_id);
            
            // Update ONU
            $onu->update([
                'rx_power' => $opticalInfo['rx_power'] ?? null,
                'tx_power' => $opticalInfo['tx_power'] ?? null,
                'olt_rx_power' => $opticalInfo['olt_rx_power'] ?? null,
                'distance' => $opticalInfo['distance'] ?? $onu->distance,
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'rx_power' => $opticalInfo['rx_power'],
                    'tx_power' => $opticalInfo['tx_power'],
                    'olt_rx_power' => $opticalInfo['olt_rx_power'],
                    'distance' => $opticalInfo['distance'] ?? $onu->distance,
                    'in_octets' => $trafficInfo['in_octets'],
                    'out_octets' => $trafficInfo['out_octets'],
                    'in_octets_formatted' => $this->formatBytes($trafficInfo['in_octets']),
                    'out_octets_formatted' => $this->formatBytes($trafficInfo['out_octets']),
                ],
                'message' => 'Signal refreshed',
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Bulk refresh ONUs
     */
    public function bulkRefresh(Request $request, Olt $olt)
    {
        try {
            $helper = OltFactory::make($olt);
            $result = $helper->syncAll();
            
            return response()->json($result);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Bulk sync all ONUs from all OLTs
     */
    public function bulkSync(Request $request)
    {
        try {
            $olts = \App\Models\Olt::where('status', 'active')->get();
            $totalSynced = 0;
            $errors = [];

            foreach ($olts as $olt) {
                try {
                    $helper = OltFactory::make($olt);
                    $result = $helper->syncAll();
                    if (isset($result['onus_synced'])) {
                        $totalSynced += $result['onus_synced'];
                    }
                } catch (Exception $e) {
                    $errors[] = "{$olt->name}: {$e->getMessage()}";
                }
            }

            $message = "Berhasil sync {$totalSynced} ONU dari {$olts->count()} OLT";
            if (!empty($errors)) {
                $message .= ". Errors: " . implode('; ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'synced' => $totalSynced,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    protected function resolvePonCoordinates(Request $request): array
    {
        if ($request->filled('slot') && $request->filled('port')) {
            return [(int) $request->slot, (int) $request->port];
        }

        $ponPort = trim((string) $request->input('pon_port', ''));
        if ($ponPort !== '' && preg_match('/^(\d+)\s*\/\s*(\d+)$/', $ponPort, $matches)) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        return [null, null];
    }

    protected function resolveProvisioningProfiles(Request $request): array
    {
        $lineProfile = $this->normalizeProfileName($request->input('line_profile'));
        $serviceProfile = $this->normalizeProfileName($request->input('service_profile'));

        if ((!$lineProfile || !$serviceProfile) && $request->filled('profile_id')) {
            $profile = OltProfile::find($request->profile_id);
            if ($profile) {
                if (!$lineProfile && $profile->type === OltProfile::TYPE_LINE) {
                    $lineProfile = $profile->name;
                }

                if (!$serviceProfile && $profile->type === OltProfile::TYPE_SERVICE) {
                    $serviceProfile = $profile->name;
                }
            }
        }

        return [$lineProfile, $serviceProfile];
    }

    protected function normalizeProfileName($value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function resolveProfileConfigs(string $oltId, ?string $lineProfile, ?string $serviceProfile): array
    {
        $config = [];

        if ($lineProfile) {
            $line = OltProfile::where('olt_id', $oltId)
                ->where('type', OltProfile::TYPE_LINE)
                ->where('name', $lineProfile)
                ->first();

            if ($line?->config) {
                $config = array_merge($config, $line->config);
            }
        }

        if ($serviceProfile) {
            $service = OltProfile::where('olt_id', $oltId)
                ->where('type', OltProfile::TYPE_SERVICE)
                ->where('name', $serviceProfile)
                ->first();

            if ($service?->config) {
                $config = array_merge($config, $service->config);
            }
        }

        return $config;
    }

    protected function normalizeProvisioningConfig(array $config): array
    {
        foreach (['vlan', 'vlan_id', 'gem_port', 'tcont_id', 'service_id'] as $numericKey) {
            if (array_key_exists($numericKey, $config) && $config[$numericKey] !== null && $config[$numericKey] !== '') {
                $config[$numericKey] = (int) $config[$numericKey];
            }
        }

        if (array_key_exists('service_port_mode', $config) && $config['service_port_mode'] !== null) {
            $config['service_port_mode'] = trim((string) $config['service_port_mode']) ?: null;
        }

        return $config;
    }

    protected function buildVlanConfigPayload(array $config): ?array
    {
        $payload = array_filter($config, fn($value) => $value !== null && $value !== '');

        return !empty($payload) ? $payload : null;
    }

    protected function registerResponse(Request $request, bool $success, string $message, array $extra = [])
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(array_merge([
                'success' => $success,
                'message' => $message,
            ], $extra), $success ? 200 : 422);
        }

        if ($success && isset($extra['redirect_url'])) {
            return redirect($extra['redirect_url'])->with('success', $message);
        }

        return back()->withInput()->with($success ? 'success' : 'error', $message);
    }

    /**
     * Detect ONU type/model from serial number prefix.
     */
    protected function detectOnuType(string $serialNumber): ?string
    {
        if (strlen($serialNumber) < 4) {
            return null;
        }

        $prefix = strtoupper(substr($serialNumber, 0, 4));

        $map = [
            'HWTC' => 'HG8245H',
            'HWTG' => 'HG8245H5',
            'HWTE' => 'EG8145V5',
            'ZTEG' => 'ZTE ONT',   // Generic — actual model comes from CLI output
            'ZICG' => 'F663NV9',
            'PRTS' => 'Proscend',
            'ALCL' => 'Nokia',
            'FHTT' => 'FiberHome',
            'TPLG' => 'TP-Link',
            'DSNW' => 'DASAN',
            'MSTC' => 'ZyXEL',
            'SMBS' => 'SmartRG',
        ];

        return $map[$prefix] ?? null;
    }

    /**
     * Configure management VLAN/IP on ONU via OLT CLI.
     */
    public function configureManagement(Onu $onu, Request $request)
    {
        $request->validate([
            'mgmt_vlan' => 'required|integer|min:1|max:4094',
            'mgmt_ip_mode' => 'required|in:dhcp,static,inactive',
            'mgmt_ip' => 'nullable|ip',
        ]);

        try {
            $helper = \App\Helpers\Olt\OltFactory::make($onu->olt);

            $config = [
                'vlan' => (int) $request->mgmt_vlan,
            ];

            if ($request->mgmt_ip_mode === 'static' && $request->mgmt_ip) {
                $config['ip'] = $request->mgmt_ip;
            }

            $result = $helper->configureOnuManagement($onu->slot, $onu->port, $onu->onu_id, $config);

            if ($result['success']) {
                $onu->update([
                    'mgmt_ip' => $request->mgmt_ip_mode === 'static' ? $request->mgmt_ip : ($request->mgmt_ip_mode === 'dhcp' ? 'dhcp:vlan:' . $request->mgmt_vlan : null),
                ]);
            }

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Configure WAN settings on ONU via OLT CLI.
     */
    public function configureWan(Onu $onu, Request $request)
    {
        $request->validate([
            'wan_vlan' => 'required|integer|min:1|max:4094',
            'wan_mode' => 'required|in:routing,bridging',
            'wan_type' => 'required|in:dhcp,static,pppoe,manual',
            'pppoe_username' => 'nullable|string|max:100',
            'pppoe_password' => 'nullable|string|max:100',
        ]);

        try {
            $helper = \App\Helpers\Olt\OltFactory::make($onu->olt);

            $serviceConfig = [
                'vlan' => (int) $request->wan_vlan,
                'gem_port' => $onu->vlan_config['gem_port'] ?? 1,
                'service_id' => $onu->vlan_config['service_id'] ?? 1,
                'mode' => $onu->vlan_config['service_port_mode'] ?? 'tag',
            ];

            if ($request->wan_type === 'pppoe' && $request->pppoe_username) {
                $serviceConfig['pppoe'] = true;
                $serviceConfig['pppoe_username'] = $request->pppoe_username;
                $serviceConfig['pppoe_password'] = $request->pppoe_password ?? '';
            }

            $result = $helper->applyServiceToOnu($onu->slot, $onu->port, $onu->onu_id, $serviceConfig);

            if ($result['success']) {
                $updateData = [];
                if ($request->wan_type === 'pppoe' && $request->pppoe_username) {
                    $updateData['pppoe_username'] = $request->pppoe_username;
                }
                $vlanConfig = $onu->vlan_config ?? [];
                $vlanConfig['vlan_id'] = (int) $request->wan_vlan;
                $updateData['vlan_config'] = $vlanConfig;
                if (!empty($updateData)) {
                    $onu->update($updateData);
                }
            }

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get TR069 info from GenieACS for this ONU.
     */
    public function getTr069Info(Onu $onu)
    {
        try {
            $genieacs = new \App\Services\GenieAcsService();

            if (!$genieacs->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'GenieACS server tidak tersedia',
                    'available' => false,
                ]);
            }

            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json([
                    'success' => true,
                    'available' => true,
                    'found' => false,
                    'message' => 'ONU belum terdaftar di GenieACS (TR069 belum aktif)',
                ]);
            }

            $deviceInfo = $genieacs->getDeviceInfo($device['device_id']);
            $wanInfo = $genieacs->getWanInfo($device['device_id']);
            $lanHosts = $genieacs->getLanHosts($device['device_id']);
            $tasks = $genieacs->getDeviceTasks($device['device_id']);

            return response()->json([
                'success' => true,
                'available' => true,
                'found' => true,
                'device' => $deviceInfo,
                'wan_connections' => $wanInfo,
                'lan_hosts' => $lanHosts,
                'pending_tasks' => count($tasks),
                'genieacs_ui_url' => config('services.genieacs.ui_url') . '/#/devices/' . urlencode($device['device_id']),
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Refresh TR069 device data.
     */
    public function refreshTr069(Onu $onu)
    {
        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            // Use smartRefresh to avoid task accumulation
            $result = $genieacs->smartRefresh($device['device_id']);

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Configure WAN via TR069 (PPPoE setup).
     */
    public function configureTr069Wan(Onu $onu, Request $request)
    {
        $request->validate([
            'pppoe_username' => 'required|string|max:100',
            'pppoe_password' => 'required|string|max:100',
            'vlan' => 'nullable|integer|min:1|max:4094',
        ]);

        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $result = $genieacs->configureWanPppoe($device['device_id'], [
                'username' => $request->pppoe_username,
                'password' => $request->pppoe_password,
                'vlan' => $request->vlan ?? $onu->vlan_config['vlan_id'] ?? 100,
            ]);

            if ($result['success']) {
                $onu->update(['pppoe_username' => $request->pppoe_username]);
            }

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Re-apply TCONT + Traffic profile on an existing ONU (without unregistering).
     */
    public function reapplyProfiles(Onu $onu, Request $request)
    {
        $request->validate([
            'tcont_profile'   => 'required|string|max:64',
            'traffic_profile' => 'required|string|max:64',
        ]);

        try {
            $helper = OltFactory::make($onu->olt);

            if (!method_exists($helper, 'reapplyProfiles')) {
                return response()->json(['success' => false, 'message' => 'OLT brand ini belum mendukung re-apply profile.'], 422);
            }

            $vlanConfig = $onu->vlan_config ?? [];
            $tcontId = (int) ($vlanConfig['tcont_id'] ?? 1);
            $gemPort = (int) ($vlanConfig['gem_port'] ?? 1);

            $result = $helper->reapplyProfiles(
                (int) $onu->slot,
                (int) $onu->port,
                (int) $onu->onu_id,
                $request->tcont_profile,
                $request->traffic_profile,
                $tcontId,
                $gemPort
            );

            if ($result['success']) {
                $onu->update([
                    'line_profile'    => $request->tcont_profile,
                    'traffic_profile' => $request->traffic_profile,
                ]);
            }

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Edit an existing PPPoE WAN connection via TR-069.
     */
    public function editTr069Wan(Onu $onu, Request $request)
    {
        $request->validate([
            'wan_path'       => 'required|string|max:300',
            'pppoe_username' => 'required|string|max:100',
            'pppoe_password' => 'nullable|string|max:100',
            'vlan'           => 'nullable|integer|min:1|max:4094',
        ]);

        // Server-side protection: only PPPoE paths allowed
        if (!str_contains($request->wan_path, 'WANPPPConnection')) {
            return response()->json(['success' => false, 'message' => 'Hanya WAN PPPoE yang boleh diedit.'], 403);
        }

        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $config = ['username' => $request->pppoe_username];
            if ($request->filled('pppoe_password')) {
                $config['password'] = $request->pppoe_password;
            }
            if ($request->filled('vlan')) {
                $config['vlan'] = $request->vlan;
            }

            $result = $genieacs->updateWanPppoe($device['device_id'], $request->wan_path, $config);

            if ($result['success']) {
                $onu->update(['pppoe_username' => $request->pppoe_username]);
            }

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a PPPoE WAN connection via TR-069.
     */
    public function deleteTr069Wan(Onu $onu, Request $request)
    {
        $request->validate([
            'wan_path' => 'required|string|max:300',
        ]);

        // Server-side protection: only PPPoE paths allowed
        if (!str_contains($request->wan_path, 'WANPPPConnection')) {
            return response()->json(['success' => false, 'message' => 'Hanya WAN PPPoE yang boleh dihapus.'], 403);
        }

        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $result = $genieacs->deleteWanConnection($device['device_id'], $request->wan_path);

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get full TR069 device summary (for management panel).
     */
    public function getTr069Summary(Onu $onu)
    {
        try {
            $genieacs = new \App\Services\GenieAcsService();

            if (!$genieacs->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'available' => false,
                    'message' => 'GenieACS server tidak tersedia',
                ]);
            }

            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json([
                    'success' => true,
                    'available' => true,
                    'found' => false,
                    'message' => 'ONU belum terdaftar di GenieACS',
                ]);
            }

            $summary = $genieacs->getDeviceSummary($device['device_id']);

            return response()->json([
                'success' => true,
                'available' => true,
                'found' => true,
                'data' => $summary,
                'genieacs_ui_url' => config('services.genieacs.ui_url') . '/#/devices/' . urlencode($device['device_id']),
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Save LAN / DHCP configuration via TR069.
     */
    public function setTr069Lan(Onu $onu, Request $request)
    {
        $request->validate([
            'gateway_ip'         => 'nullable|ip',
            'subnet_mask'        => 'nullable|ip',
            'dhcp_server_enable' => 'nullable|boolean',
            'min_address'        => 'nullable|ip',
            'max_address'        => 'nullable|ip',
            'lease_time'         => 'nullable|integer|min:60|max:604800',
            'dns_servers'        => 'nullable|string|max:100',
            'domain_name'        => 'nullable|string|max:64',
        ]);

        try {
            $genieacs = new \App\Services\GenieAcsService();

            if (!$genieacs->isAvailable()) {
                return response()->json(['success' => false, 'message' => 'GenieACS tidak tersedia']);
            }

            $device = $genieacs->findDeviceBySerial($onu->serial_number);
            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $config = array_filter([
                'gateway_ip'         => $request->input('gateway_ip'),
                'subnet_mask'        => $request->input('subnet_mask'),
                'dhcp_server_enable' => $request->has('dhcp_server_enable') ? ($request->boolean('dhcp_server_enable') ? 'true' : 'false') : null,
                'min_address'        => $request->input('min_address'),
                'max_address'        => $request->input('max_address'),
                'lease_time'         => $request->input('lease_time'),
                'dns_servers'        => $request->input('dns_servers'),
                'domain_name'        => $request->input('domain_name'),
            ], fn($v) => $v !== null);

            $result = $genieacs->setLanDhcpConfig($device['device_id'], $config);
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get list of blocked clients for this ONU (stored in DB).
     */
    public function getBlockedClients(Onu $onu)
    {
        return response()->json([
            'success' => true,
            'blocked' => $onu->blocked_clients ?? [],
        ]);
    }

    /**
     * Block a client by MAC address (saves to DB + optionally pushes to device).
     */
    public function blockTr069Client(Onu $onu, Request $request)
    {
        $request->validate([
            'mac'      => 'required|regex:/^([0-9A-Fa-f]{2}[:\-]?){5}[0-9A-Fa-f]{2}$/',
            'hostname' => 'nullable|string|max:64',
            'ip'       => 'nullable|ip',
            'reason'   => 'nullable|string|max:255',
        ]);

        try {
            $mac = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $request->mac));
            $mac = implode(':', str_split($mac, 2));

            $blocked = $onu->blocked_clients ?? [];

            // Check if already blocked
            foreach ($blocked as $entry) {
                if (strtoupper($entry['mac'] ?? '') === $mac) {
                    return response()->json(['success' => false, 'message' => 'MAC sudah ada di daftar blokir.']);
                }
            }

            $blocked[] = [
                'mac'        => $mac,
                'hostname'   => $request->input('hostname', 'Unknown'),
                'ip'         => $request->input('ip', ''),
                'reason'     => $request->input('reason', ''),
                'blocked_at' => now()->toIso8601String(),
            ];

            $onu->update(['blocked_clients' => $blocked]);

            // Attempt to also push to device
            $genieacs = new \App\Services\GenieAcsService();
            $deviceResult = ['device_blocked' => false, 'message' => 'GenieACS tidak tersedia'];
            if ($genieacs->isAvailable()) {
                $device = $genieacs->findDeviceBySerial($onu->serial_number);
                if ($device) {
                    $brand = $genieacs->getBrandByDeviceId($device['device_id']);
                    $deviceResult = $genieacs->blockClientMac($device['device_id'], $mac, $brand);
                }
            }

            return response()->json([
                'success'        => true,
                'device_blocked' => $deviceResult['device_blocked'] ?? false,
                'message'        => 'Client berhasil diblokir. ' . ($deviceResult['message'] ?? ''),
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Unblock a client by MAC address.
     */
    public function unblockTr069Client(Onu $onu, Request $request)
    {
        $request->validate([
            'mac' => 'required|string|max:20',
        ]);

        try {
            $mac = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $request->mac));
            $mac = implode(':', str_split($mac, 2));

            $blocked = $onu->blocked_clients ?? [];
            $blocked = array_values(array_filter($blocked, fn($e) => strtoupper($e['mac'] ?? '') !== $mac));
            $onu->update(['blocked_clients' => $blocked]);

            // Remove from device
            $genieacs = new \App\Services\GenieAcsService();
            $deviceResult = ['device_unblocked' => false];
            if ($genieacs->isAvailable()) {
                $device = $genieacs->findDeviceBySerial($onu->serial_number);
                if ($device) {
                    $brand = $genieacs->getBrandByDeviceId($device['device_id']);
                    $deviceResult = $genieacs->unblockClientMac($device['device_id'], $mac, $brand);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Client berhasil di-unblok.',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Configure WiFi via TR069.
     */
    public function configureTr069Wifi(Onu $onu, Request $request)
    {
        $request->validate([
            'wlan_path' => 'required|string|max:500',
            'ssid' => 'nullable|string|max:32',
            'password' => 'nullable|string|min:8|max:63',
            'enabled' => 'nullable|boolean',
        ]);

        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $config = ['wlan_path' => $request->wlan_path];
            if ($request->filled('ssid')) $config['ssid'] = $request->ssid;
            if ($request->filled('password')) $config['password'] = $request->password;
            if ($request->has('enabled')) $config['enabled'] = $request->boolean('enabled');

            $result = $genieacs->configureWifi($device['device_id'], $config);

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a pending TR069 task.
     */
    public function deleteTr069Task(Onu $onu, Request $request)
    {
        $request->validate(['task_id' => 'required|string']);

        try {
            $genieacs = new \App\Services\GenieAcsService();
            $result = $genieacs->deleteTask($request->task_id);

            return response()->json(['success' => $result]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get security info via TR069.
     */
    public function getTr069Security(Onu $onu)
    {
        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $security = $genieacs->getSecurityInfo($device['device_id']);

            return response()->json(['success' => true, 'data' => $security]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Set security/remote access settings via TR-069.
     */
    public function setTr069Security(Onu $onu, Request $request)
    {
        $request->validate([
            'settings'                => 'required|array',
            'settings.acl_ftp_lan'    => 'nullable|boolean',
            'settings.acl_ftp_wan'    => 'nullable|boolean',
            'settings.acl_http_lan'   => 'nullable|boolean',
            'settings.acl_http_wan'   => 'nullable|boolean',
            'settings.acl_ssh_lan'    => 'nullable|boolean',
            'settings.acl_ssh_wan'    => 'nullable|boolean',
            'settings.acl_samba_lan'  => 'nullable|boolean',
            'settings.acl_samba_wan'  => 'nullable|boolean',
            'settings.acl_telnet_lan' => 'nullable|boolean',
            'settings.acl_telnet_wan' => 'nullable|boolean',
            'settings.acl_icmp_echo'  => 'nullable|boolean',
            'settings.cli_ssh_enable' => 'nullable|boolean',
            'settings.cli_telnet_enable' => 'nullable|boolean',
            'settings.cli_telnet_wan' => 'nullable|boolean',
            'settings.cli_password'   => 'nullable|string|max:30',
            'settings.web_user_enable'    => 'nullable|boolean',
            'settings.web_user_username'  => 'nullable|string|max:30',
            'settings.web_user_password'  => 'nullable|string|max:30',
            'settings.web_admin_enable'   => 'nullable|boolean',
            'settings.web_admin_username' => 'nullable|string|max:30',
            'settings.web_admin_password' => 'nullable|string|max:30',
        ]);

        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            // Clear stuck getParameterValues tasks before sending new settings
            $genieacs->clearDeviceTasks($device['device_id'], 'getParameterValues');

            $result = $genieacs->setSecuritySettings($device['device_id'], $request->input('settings', []));

            return response()->json(array_merge($result, [
                'message' => $result['success']
                    ? ($result['status'] === 200 ? 'Settings berhasil diterapkan ke perangkat.' : 'Settings dikirim, menunggu device check-in.')
                    : ($result['message'] ?? 'Gagal mengirim pengaturan'),
            ]));
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get connected users/hosts via TR069.
     */
    public function getTr069Users(Onu $onu)
    {
        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $users = $genieacs->getConnectedUsers($device['device_id']);

            return response()->json(['success' => true, 'data' => $users]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Reboot ONU via TR069.
     */
    public function rebootTr069(Onu $onu)
    {
        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $result = $genieacs->rebootDevice($device['device_id']);

            return response()->json(array_merge($result, ['message' => 'Perintah reboot dikirim ke ONU']));
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Set TR-069 PeriodicInformInterval on device.
     */
    public function setTr069InformInterval(Onu $onu, Request $request)
    {
        $request->validate([
            'interval' => 'required|integer|min:30|max:86400',
        ]);

        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $interval = (int) $request->interval;
            $result = $genieacs->setParameterValues($device['device_id'], [
                'InternetGatewayDevice.ManagementServer.PeriodicInformEnable' => [true, 'xsd:boolean'],
                'InternetGatewayDevice.ManagementServer.PeriodicInformInterval' => [$interval, 'xsd:unsignedInt'],
            ], true);

            return response()->json(array_merge($result, [
                'message' => $result['success']
                    ? "Inform interval berhasil diset ke {$interval} detik"
                    : ($result['message'] ?? 'Gagal'),
            ]));
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Change web UI user password via TR-069 X_HW_UserInfo.
     */
    public function changeTr069UserPassword(Onu $onu, Request $request)
    {
        $request->validate([
            'username' => 'required|in:admin,telecomadmin',
            'password' => 'required|string|min:6|max:30',
        ]);

        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $result = $genieacs->setWebUserPassword($device['device_id'], $request->username, $request->password);

            return response()->json(array_merge($result, [
                'message' => $result['success']
                    ? "Password user '{$request->username}' berhasil diubah."
                    : ($result['message'] ?? 'Gagal mengubah password'),
            ]));
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Add a new WiFi SSID instance via TR-069 AddObject.
     */
    public function addTr069Wifi(Onu $onu, Request $request)
    {
        $request->validate([
            'ssid'     => 'required|string|max:32',
            'password' => 'nullable|string|min:8|max:63',
            'enabled'  => 'nullable',
        ]);

        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device   = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $deviceId = $device['device_id'];

            // Determine LANDevice parent from existing WiFi list
            $wifis    = $genieacs->getWifiInfo($deviceId);
            $ldNum    = 1;
            $maxIndex = 0;
            foreach ($wifis as $w) {
                if (preg_match('/LANDevice\.(\d+)\.WLANConfiguration\.(\d+)/', $w['path'], $m)) {
                    $ldNum    = (int) $m[1];
                    $maxIndex = max($maxIndex, (int) $m[2]);
                }
            }

            if ($maxIndex >= 4) {
                return response()->json(['success' => false, 'message' => 'Maksimal 4 SSID sudah tercapai']);
            }

            $basePath = "InternetGatewayDevice.LANDevice.{$ldNum}.WLANConfiguration.";

            // Send addObject with waitComplete — GenieACS will return instanceNumber if device responds within timeout
            $addResult = $genieacs->addObject($deviceId, $basePath, true);
            if (!$addResult['success']) {
                return response()->json(['success' => false, 'message' => 'Gagal mengirim addObject task']);
            }

            // If device responded synchronously, use actual instanceNumber; else fall back to prediction
            $newIndex = $addResult['instance'] ?? ($maxIndex + 1);
            $newPath  = $basePath . $newIndex;

            // Set SSID name/password/enabled — only if we have a reliable index
            $params = [
                "{$newPath}.SSID"   => [$request->ssid, 'xsd:string'],
                "{$newPath}.Enable" => [(bool) ($request->input('enabled', true)), 'xsd:boolean'],
            ];
            if ($request->filled('password')) {
                $params["{$newPath}.PreSharedKey.1.PreSharedKey"] = [$request->password, 'xsd:string'];
                $params["{$newPath}.KeyPassphrase"] = [$request->password, 'xsd:string'];
            }
            // connection_request=true: wake device immediately for rename task
            $genieacs->setParameterValues($deviceId, $params, true);

            $sync = $addResult['completed'] ?? false;
            return response()->json([
                'success'   => true,
                'completed' => $sync,
                'wifi_count' => count($wifis) + 1, // expected count after add
                'message'   => $sync
                    ? "SSID \"{$request->ssid}\" berhasil dibuat (index {$newIndex})."
                    : "addObject dikirim. SSID akan muncul setelah device check-in. Nama akan diset otomatis.",
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a WiFi SSID instance via TR-069 DeleteObject.
     */
    public function deleteTr069Wifi(Onu $onu, Request $request)
    {
        $request->validate([
            'wlan_path' => 'required|string',
        ]);

        // Safety: only allow deleting secondary SSIDs (index > 1)
        if (!preg_match('/WLANConfiguration\.(\d+)$/', $request->wlan_path, $m) || (int) $m[1] <= 1) {
            return response()->json(['success' => false, 'message' => 'SSID utama (index 1) tidak boleh dihapus.']);
        }

        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device   = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $result = $genieacs->deleteObject($device['device_id'], $request->wlan_path);

            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => 'Gagal mengirim deleteObject task']);
            }

            return response()->json([
                'success'   => true,
                'completed' => $result['completed'] ?? false,
                'message'   => ($result['completed'] ?? false)
                    ? 'SSID berhasil dihapus.'
                    : 'Perintah hapus dikirim. SSID akan hilang setelah device check-in.',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function factoryResetTr069(Onu $onu)
    {
        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $result = $genieacs->factoryReset($device['device_id']);

            return response()->json(array_merge($result, ['message' => 'Perintah factory reset dikirim ke ONU']));
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Clear all pending TR069 tasks.
     */
    public function clearTr069Tasks(Onu $onu)
    {
        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $cleared = $genieacs->clearDeviceTasks($device['device_id']);

            return response()->json(['success' => true, 'cleared' => $cleared, 'message' => "$cleared task dihapus"]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Download firmware to ONU via TR069.
     */
    public function downloadFirmwareTr069(Onu $onu, Request $request)
    {
        $request->validate([
            'file_url' => 'required|url|max:500',
        ]);

        try {
            $genieacs = new \App\Services\GenieAcsService();
            $device = $genieacs->findDeviceBySerial($onu->serial_number);

            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device tidak ditemukan di GenieACS']);
            }

            $result = $genieacs->downloadFirmware($device['device_id'], $request->file_url);

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
