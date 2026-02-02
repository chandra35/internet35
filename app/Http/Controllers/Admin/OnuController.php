<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Onu;
use App\Models\Olt;
use App\Models\Odp;
use App\Models\Customer;
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
            new Middleware('permission:onus.create', only: ['create', 'store', 'register']),
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
        $onu->load(['olt', 'customer', 'odp', 'ponPort', 'creator']);
        
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
        
        return view('admin.onus.show', compact('onu', 'signalHistory', 'customers', 'chartLabels', 'chartRxData', 'chartTxData'));
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
     * Register ONU on OLT
     */
    public function register(Request $request, Olt $olt)
    {
        $request->validate([
            'serial_number' => 'required|string|max:20',
            'slot' => 'required|integer|min:0',
            'port' => 'required|integer|min:1',
            'onu_id' => 'nullable|integer|min:1|max:128',
            'name' => 'nullable|string|max:100',
            'customer_id' => 'nullable|exists:customers,id',
            'odp_id' => 'nullable|exists:odps,id',
            'odp_port' => 'nullable|integer|min:1',
            'vlan' => 'nullable|integer|min:1|max:4094',
        ]);

        try {
            $helper = OltFactory::make($olt);
            
            $params = [
                'serial_number' => strtoupper($request->serial_number),
                'slot' => $request->slot,
                'port' => $request->port,
                'onu_id' => $request->onu_id,
                'name' => $request->name ?? $request->serial_number,
            ];
            
            // Add VLAN if provided
            if ($request->filled('vlan')) {
                $params['vlan'] = $request->vlan;
            }
            
            $result = $helper->registerOnu($params);
            
            if ($result['success']) {
                // Save to database
                $onu = Onu::create([
                    'olt_id' => $olt->id,
                    'serial_number' => strtoupper($request->serial_number),
                    'slot' => $request->slot,
                    'port' => $request->port,
                    'onu_id' => $result['onu_id'],
                    'name' => $request->name,
                    'customer_id' => $request->customer_id,
                    'odp_id' => $request->odp_id,
                    'odp_port' => $request->odp_port,
                    'config_status' => 'registered',
                    'status' => 'unknown',
                    'created_by' => auth()->id(),
                ]);
                
                $this->activityLog->log('onus', "Registered ONU: {$onu->serial_number} on {$olt->name}");
                
                return redirect()->route('admin.onus.show', $onu)
                    ->with('success', $result['message']);
            } else {
                return back()->withInput()
                    ->with('error', $result['message']);
            }
            
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Registration failed: ' . $e->getMessage());
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
                $serial = $onu->serial_number;
                $onu->delete();
                
                $this->activityLog->log('onus', "Unregistered ONU: {$serial}");
                
                return redirect()->route('admin.olts.show', $onu->olt_id)
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
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'rx_power' => $opticalInfo['rx_power'],
                    'tx_power' => $opticalInfo['tx_power'],
                    'olt_rx_power' => $opticalInfo['olt_rx_power'],
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
}
