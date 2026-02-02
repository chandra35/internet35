<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Mikrotik\MikrotikService;
use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RouterController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:routers.view', only: ['index', 'show']),
            new Middleware('permission:routers.create', only: ['create', 'store', 'testConnection']),
            new Middleware('permission:routers.edit', only: ['edit', 'update']),
            new Middleware('permission:routers.delete', only: ['destroy']),
            new Middleware('permission:routers.manage', only: ['manage', 'executeCommand']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Display a listing of routers
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Query routers based on user role
        $query = Router::with(['pop', 'creator']);
        
        // Admin-pop can only see their own routers
        if ($user->hasRole('admin-pop')) {
            $query->where('pop_id', $user->id);
        }

        $routers = $query->orderBy('created_at', 'desc')->get();
        
        // Get POPs for filter (admin-pop users)
        $pops = User::role('admin-pop')->get();

        return view('admin.routers.index', compact('routers', 'pops'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $pops = User::role('admin-pop')->get();
        
        return response()->json([
            'success' => true,
            'html' => view('admin.routers._form', [
                'router' => null,
                'pops' => $pops,
            ])->render(),
        ]);
    }

    /**
     * Test connection before saving
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'api_port' => 'required|integer',
            'use_ssl' => 'boolean',
            'api_ssl_port' => 'nullable|integer',
        ]);

        $mikrotik = new MikrotikService();
        $port = $request->boolean('use_ssl') ? $request->api_ssl_port : $request->api_port;
        
        $result = $mikrotik->testConnection(
            $request->host,
            $request->username,
            $request->password,
            $port,
            $request->boolean('use_ssl')
        );

        return response()->json($result);
    }

    /**
     * Store a new router
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'api_port' => 'required|integer|min:1|max:65535',
            'api_ssl_port' => 'nullable|integer|min:1|max:65535',
            'use_ssl' => 'boolean',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'pop_id' => 'nullable|uuid|exists:users,id',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $user = auth()->user();
        
        // Test connection first
        $mikrotik = new MikrotikService();
        $port = $request->boolean('use_ssl') ? ($request->api_ssl_port ?? 8729) : $request->api_port;
        $testResult = $mikrotik->testConnection(
            $request->host,
            $request->username,
            $request->password,
            $port,
            $request->boolean('use_ssl')
        );

        if (!$testResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke router gagal: ' . $testResult['message'],
            ], 422);
        }

        // Set pop_id - admin-pop automatically owns the router
        $popId = $request->pop_id;
        if ($user->hasRole('admin-pop')) {
            $popId = $user->id;
        }

        $router = Router::create([
            'name' => $request->name,
            'host' => $request->host,
            'api_port' => $request->api_port,
            'api_ssl_port' => $request->api_ssl_port ?? 8729,
            'use_ssl' => $request->boolean('use_ssl'),
            'username' => $request->username,
            'password' => $request->password,
            'identity' => $testResult['data']['identity'] ?? null,
            'ros_version' => $testResult['data']['version'] ?? null,
            'ros_major_version' => $testResult['data']['major_version'] ?? null,
            'board_name' => $testResult['data']['board_name'] ?? null,
            'architecture' => $testResult['data']['architecture'] ?? null,
            'cpu' => $testResult['data']['cpu'] ?? null,
            'total_memory' => $testResult['data']['total_memory'] ?? null,
            'free_memory' => $testResult['data']['free_memory'] ?? null,
            'uptime' => $testResult['data']['uptime'] ?? null,
            'status' => 'online',
            'last_connected_at' => now(),
            'notes' => $request->notes,
            'is_active' => $request->boolean('is_active', true),
            'pop_id' => $popId,
            'created_by' => $user->id,
        ]);

        $this->activityLog->logCreate('routers', "Created router: {$router->name}", $router->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Router berhasil ditambahkan!',
        ]);
    }

    /**
     * Show router details
     */
    public function show(Router $router)
    {
        $this->authorizeRouter($router);
        
        return response()->json([
            'success' => true,
            'router' => $router->load(['pop', 'creator']),
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(Router $router)
    {
        $this->authorizeRouter($router);
        
        $pops = User::role('admin-pop')->get();
        
        return response()->json([
            'success' => true,
            'html' => view('admin.routers._form', [
                'router' => $router,
                'pops' => $pops,
            ])->render(),
        ]);
    }

    /**
     * Update router
     */
    public function update(Request $request, Router $router)
    {
        $this->authorizeRouter($router);

        $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'api_port' => 'required|integer|min:1|max:65535',
            'api_ssl_port' => 'nullable|integer|min:1|max:65535',
            'use_ssl' => 'boolean',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string',
            'pop_id' => 'nullable|uuid|exists:users,id',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $user = auth()->user();
        $password = $request->password ?: $router->decrypted_password;

        // Test connection if host/port/credentials changed
        if ($router->host !== $request->host || 
            $router->api_port !== $request->api_port ||
            $router->username !== $request->username ||
            $request->password) {
            
            $mikrotik = new MikrotikService();
            $port = $request->boolean('use_ssl') ? ($request->api_ssl_port ?? 8729) : $request->api_port;
            $testResult = $mikrotik->testConnection(
                $request->host,
                $request->username,
                $password,
                $port,
                $request->boolean('use_ssl')
            );

            if (!$testResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Koneksi ke router gagal: ' . $testResult['message'],
                ], 422);
            }

            $router->identity = $testResult['data']['identity'] ?? $router->identity;
            $router->ros_version = $testResult['data']['version'] ?? $router->ros_version;
            $router->ros_major_version = $testResult['data']['major_version'] ?? $router->ros_major_version;
            $router->board_name = $testResult['data']['board_name'] ?? $router->board_name;
            $router->architecture = $testResult['data']['architecture'] ?? $router->architecture;
            $router->cpu = $testResult['data']['cpu'] ?? $router->cpu;
            $router->total_memory = $testResult['data']['total_memory'] ?? $router->total_memory;
            $router->free_memory = $testResult['data']['free_memory'] ?? $router->free_memory;
            $router->status = 'online';
            $router->last_connected_at = now();
        }

        // Set pop_id
        $popId = $request->pop_id;
        if ($user->hasRole('admin-pop')) {
            $popId = $user->id;
        }

        $router->name = $request->name;
        $router->host = $request->host;
        $router->api_port = $request->api_port;
        $router->api_ssl_port = $request->api_ssl_port ?? 8729;
        $router->use_ssl = $request->boolean('use_ssl');
        $router->username = $request->username;
        if ($request->password) {
            $router->password = $request->password;
        }
        $router->notes = $request->notes;
        $router->is_active = $request->boolean('is_active', true);
        $router->pop_id = $popId;
        $router->save();

        $this->activityLog->logUpdate('routers', "Updated router: {$router->name}", $router->getOriginal(), $router->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Router berhasil diperbarui!',
        ]);
    }

    /**
     * Delete router
     */
    public function destroy(Router $router)
    {
        $this->authorizeRouter($router);

        $routerName = $router->name;
        $router->delete();

        $this->activityLog->logDelete('routers', "Deleted router: {$routerName}");

        return response()->json([
            'success' => true,
            'message' => 'Router berhasil dihapus!',
        ]);
    }

    /**
     * Manage router - Winbox-like interface
     */
    public function manage(Router $router)
    {
        $this->authorizeRouter($router);

        // Try to connect
        $mikrotik = new MikrotikService();
        $connected = false;
        $error = null;

        try {
            $connected = $mikrotik->connectRouter($router);
            $router->refresh(); // Refresh to get updated info
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $router->update(['status' => 'offline']);
        }

        if (!$connected && !$error) {
            $error = 'Gagal koneksi ke router';
            $router->update(['status' => 'offline']);
        }

        return view('admin.routers.manage', compact('router', 'connected', 'error'));
    }

    /**
     * Get router data via AJAX
     */
    public function getData(Request $request, Router $router)
    {
        $this->authorizeRouter($router);

        $type = $request->get('type', 'resource');
        $mikrotik = new MikrotikService();

        try {
            $connected = $mikrotik->connectRouter($router);
            
            if (!$connected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal koneksi ke router',
                ]);
            }

            $data = [];
            
            switch ($type) {
                case 'resource':
                    $data = $mikrotik->getSystemResource();
                    break;
                case 'interfaces':
                    $data = $mikrotik->getInterfaces();
                    break;
                case 'ip-addresses':
                    $data = $mikrotik->getIpAddresses();
                    break;
                case 'ppp-secrets':
                    $data = $mikrotik->getPppSecrets();
                    break;
                case 'ppp-active':
                    $data = $mikrotik->getPppActive();
                    break;
                case 'ppp-profiles':
                    $data = $mikrotik->getPppProfiles();
                    break;
                case 'routes':
                    $data = $mikrotik->getRoutes();
                    break;
                case 'dhcp-leases':
                    $data = $mikrotik->getDhcpLeases();
                    break;
                case 'arp':
                    $data = $mikrotik->getArpList();
                    break;
                case 'queues':
                    $data = $mikrotik->getQueues();
                    break;
                case 'firewall-filter':
                    $data = $mikrotik->getFirewallFilter();
                    break;
                case 'firewall-nat':
                    $data = $mikrotik->getFirewallNat();
                    break;
                case 'logs':
                    $data = $mikrotik->getLogs();
                    break;
                case 'gateway-traffic':
                    // Get default gateway route and its interface traffic in ONE request
                    $routes = $mikrotik->getRoutes();
                    $interfaces = $mikrotik->getInterfaces(); // Get interfaces data at the same time
                    
                    // Find default gateway (0.0.0.0/0)
                    $defaultGateway = null;
                    $gatewayIp = null;
                    $gatewayInterfaceName = null;
                    
                    foreach ($routes as $route) {
                        if (isset($route['dst-address']) && $route['dst-address'] === '0.0.0.0/0' && 
                            (!isset($route['disabled']) || $route['disabled'] !== 'true') &&
                            (isset($route['active']) ? $route['active'] === 'true' : true)) {
                            $defaultGateway = $route;
                            $gatewayIp = $route['gateway'] ?? $route['immediate-gw'] ?? $route['pref-src'] ?? null;
                            
                            // Try different interface fields
                            $gatewayInterfaceName = $route['interface'] ?? $route['vrf-interface'] ?? $route['actual-interface'] ?? null;
                            
                            // For PPPoE, the gateway field contains the interface name
                            if ($gatewayInterfaceName === null && $gatewayIp && !filter_var($gatewayIp, FILTER_VALIDATE_IP)) {
                                $gatewayInterfaceName = $gatewayIp;
                            }
                            
                            // Extract interface from immediate-gw if available (format: "IP%interface")
                            if ($gatewayInterfaceName === null && isset($route['immediate-gw'])) {
                                $parts = explode('%', $route['immediate-gw']);
                                if (count($parts) === 2) {
                                    $gatewayInterfaceName = $parts[1];
                                }
                            }
                            
                            break;
                        }
                    }
                    
                    // If still no interface, try to get from DHCP client or PPPoE client
                    if ($gatewayInterfaceName === null) {
                        $dhcpClients = $mikrotik->getDhcpClients();
                        foreach ($dhcpClients as $client) {
                            if (isset($client['gateway']) && isset($client['interface']) &&
                                (!isset($client['disabled']) || $client['disabled'] !== 'true')) {
                                $gatewayInterfaceName = $client['interface'];
                                $gatewayIp = $client['gateway'];
                                break;
                            }
                        }
                    }
                    
                    if ($gatewayInterfaceName === null) {
                        $pppoeClients = $mikrotik->getPppoeClients();
                        foreach ($pppoeClients as $client) {
                            if (isset($client['name']) && isset($client['running']) && $client['running'] === 'true') {
                                $gatewayInterfaceName = $client['name'];
                                break;
                            }
                        }
                    }
                    
                    // Find interface traffic data
                    $interfaceTraffic = null;
                    if ($gatewayInterfaceName) {
                        foreach ($interfaces as $iface) {
                            if (isset($iface['name']) && (
                                $iface['name'] === $gatewayInterfaceName ||
                                strtolower($iface['name']) === strtolower($gatewayInterfaceName) ||
                                strpos($iface['name'], $gatewayInterfaceName) !== false
                            )) {
                                $interfaceTraffic = [
                                    'tx-byte' => $iface['tx-byte'] ?? 0,
                                    'rx-byte' => $iface['rx-byte'] ?? 0,
                                    'name' => $iface['name'],
                                ];
                                break;
                            }
                        }
                    }
                    
                    $data = [
                        'gateway' => $gatewayIp,
                        'interface' => $gatewayInterfaceName,
                        'route' => $defaultGateway,
                        'traffic' => $interfaceTraffic,
                    ];
                    break;
                case 'public-ip':
                    // Try to get public IP
                    $publicIp = $mikrotik->detectPublicIp();
                    $data = ['public_ip' => $publicIp];
                    break;
                case 'ping':
                    $target = $request->get('target', '8.8.8.8');
                    $count = min((int) $request->get('count', 3), 5); // Max 5 pings
                    $pingResults = $mikrotik->ping($target, $count);
                    
                    // Calculate statistics
                    $times = [];
                    $sent = $count;
                    $received = 0;
                    
                    foreach ($pingResults as $result) {
                        // Mikrotik returns time in format like "17ms649us" or "1ms" or "500us"
                        if (isset($result['time'])) {
                            $timeStr = trim($result['time']);
                            $timeMs = 0;
                            
                            // Parse format: "17ms649us" or "17ms" or "649us"
                            if (preg_match('/(\d+)ms(\d+)us/', $timeStr, $matches)) {
                                // Format: 17ms649us = 17 + 0.649 = 17.649ms
                                $timeMs = (float) $matches[1] + ((float) $matches[2] / 1000);
                            } elseif (preg_match('/(\d+(?:\.\d+)?)ms/', $timeStr, $matches)) {
                                // Format: 17ms or 17.5ms
                                $timeMs = (float) $matches[1];
                            } elseif (preg_match('/(\d+)us/', $timeStr, $matches)) {
                                // Format: 649us (only microseconds)
                                $timeMs = (float) $matches[1] / 1000;
                            }
                            
                            if ($timeMs > 0) {
                                $times[] = $timeMs;
                                $received++;
                            }
                        }
                        // Some versions return 'timeout' for lost packets
                        if (isset($result['status']) && $result['status'] === 'timeout') {
                            // Packet lost, don't add to times
                        }
                        // Get sent/received from summary if available
                        if (isset($result['sent'])) {
                            $sent = (int) $result['sent'];
                        }
                        if (isset($result['received'])) {
                            $received = (int) $result['received'];
                        }
                    }
                    
                    // Calculate jitter (variation in latency)
                    $jitter = 0;
                    if (count($times) > 1) {
                        $diffs = [];
                        for ($i = 1; $i < count($times); $i++) {
                            $diffs[] = abs($times[$i] - $times[$i - 1]);
                        }
                        $jitter = count($diffs) > 0 ? array_sum($diffs) / count($diffs) : 0;
                    }
                    
                    // Calculate loss based on actual received count
                    $actualReceived = count($times) > 0 ? count($times) : $received;
                    $loss = $sent > 0 ? round((($sent - $actualReceived) / $sent) * 100, 1) : 0;
                    
                    $data = [
                        'target' => $target,
                        'sent' => $sent,
                        'received' => $actualReceived,
                        'loss' => $loss,
                        'min' => count($times) > 0 ? round(min($times), 2) : null,
                        'max' => count($times) > 0 ? round(max($times), 2) : null,
                        'avg' => count($times) > 0 ? round(array_sum($times) / count($times), 2) : null,
                        'jitter' => round($jitter, 2),
                        'times' => $times,
                    ];
                    break;
                case 'dhcp-clients':
                    $data = $mikrotik->getDhcpClients();
                    break;
                case 'pppoe-clients':
                    $data = $mikrotik->getPppoeClients();
                    break;
                case 'dns':
                    $data = $mikrotik->getDnsServers();
                    break;
                case 'firewall-mangle':
                    $data = $mikrotik->getFirewallMangle();
                    break;
                case 'firewall-address-list':
                    $data = $mikrotik->getFirewallAddressList();
                    break;
                case 'hotspot-users':
                    $data = $mikrotik->getHotspotUsers();
                    break;
                case 'hotspot-active':
                    $data = $mikrotik->getHotspotActive();
                    break;
                case 'bridges':
                    $data = $mikrotik->getBridges();
                    break;
                case 'bridge-ports':
                    $data = $mikrotik->getBridgePorts();
                    break;
                case 'vlans':
                    $data = $mikrotik->getVlans();
                    break;
                case 'ethernets':
                    $data = $mikrotik->getEthernets();
                    break;
                case 'wireless':
                    $data = $mikrotik->getWirelessInterfaces();
                    break;
                case 'wireless-registration':
                    $data = $mikrotik->getWirelessRegistration();
                    break;
                case 'scheduler':
                    $data = $mikrotik->getScheduler();
                    break;
                case 'scripts':
                    $data = $mikrotik->getScripts();
                    break;
                case 'system-users':
                    $data = $mikrotik->getSystemUsers();
                    break;
                case 'packages':
                    $data = $mikrotik->getPackages();
                    break;
                case 'ip-pools':
                    $data = $mikrotik->getIpPools();
                    break;
                case 'dhcp-servers':
                    $data = $mikrotik->getDhcpServers();
                    break;
                case 'dhcp-networks':
                    $data = $mikrotik->getDhcpNetworks();
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'ros_version' => $router->ros_major_version,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Execute command on router
     */
    public function executeCommand(Request $request, Router $router)
    {
        $this->authorizeRouter($router);

        $request->validate([
            'action' => 'required|string',
            'params' => 'nullable|array',
        ]);

        $mikrotik = new MikrotikService();

        try {
            $connected = $mikrotik->connectRouter($router);
            
            if (!$connected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal koneksi ke router',
                ]);
            }

            $action = $request->action;
            $params = $request->params ?? [];
            $result = null;
            $success = false;
            $message = '';

            // Handle different actions
            switch ($action) {
                // Interface actions
                case 'interface/enable':
                    $success = $mikrotik->enableInterface($params['id']);
                    $message = $success ? 'Interface berhasil diaktifkan' : 'Gagal mengaktifkan interface';
                    break;
                case 'interface/disable':
                    $success = $mikrotik->disableInterface($params['id']);
                    $message = $success ? 'Interface berhasil dinonaktifkan' : 'Gagal menonaktifkan interface';
                    break;
                case 'interface/update':
                    $success = $mikrotik->updateInterface($params['id'], $params['data']);
                    $message = $success ? 'Interface berhasil diupdate' : 'Gagal mengupdate interface';
                    break;

                // IP Address actions
                case 'ip/address/add':
                    $result = $mikrotik->addIpAddress($params['address'], $params['interface'], $params['comment'] ?? null);
                    $success = !isset($result[0]['_error']);
                    $message = $success ? 'IP Address berhasil ditambahkan' : 'Gagal menambahkan IP Address';
                    break;
                case 'ip/address/remove':
                    $success = $mikrotik->removeIpAddress($params['id']);
                    $message = $success ? 'IP Address berhasil dihapus' : 'Gagal menghapus IP Address';
                    break;
                case 'ip/address/enable':
                    $success = $mikrotik->enableIpAddress($params['id']);
                    $message = $success ? 'IP Address berhasil diaktifkan' : 'Gagal mengaktifkan IP Address';
                    break;
                case 'ip/address/disable':
                    $success = $mikrotik->disableIpAddress($params['id']);
                    $message = $success ? 'IP Address berhasil dinonaktifkan' : 'Gagal menonaktifkan IP Address';
                    break;
                case 'ip/address/update':
                    $success = $mikrotik->updateIpAddress($params['id'], $params['data']);
                    $message = $success ? 'IP Address berhasil diupdate' : 'Gagal mengupdate IP Address';
                    break;

                // PPP Secret actions
                case 'ppp/secret/add':
                    $result = $mikrotik->addPppSecret($params['data']);
                    $success = !isset($result[0]['_error']);
                    $message = $success ? 'PPP Secret berhasil ditambahkan' : 'Gagal menambahkan PPP Secret';
                    break;
                case 'ppp/secret/update':
                    $success = $mikrotik->updatePppSecret($params['id'], $params['data']);
                    $message = $success ? 'PPP Secret berhasil diupdate' : 'Gagal mengupdate PPP Secret';
                    break;
                case 'ppp/secret/remove':
                    $success = $mikrotik->removePppSecret($params['id']);
                    $message = $success ? 'PPP Secret berhasil dihapus' : 'Gagal menghapus PPP Secret';
                    break;
                case 'ppp/secret/enable':
                    $success = $mikrotik->enablePppSecret($params['id']);
                    $message = $success ? 'PPP Secret berhasil diaktifkan' : 'Gagal mengaktifkan PPP Secret';
                    break;
                case 'ppp/secret/disable':
                    $success = $mikrotik->disablePppSecret($params['id']);
                    $message = $success ? 'PPP Secret berhasil dinonaktifkan' : 'Gagal menonaktifkan PPP Secret';
                    break;

                // PPP Active (disconnect)
                case 'ppp/active/remove':
                    $success = $mikrotik->disconnectPppUser($params['id']);
                    $message = $success ? 'User berhasil di-disconnect' : 'Gagal disconnect user';
                    break;

                // Custom command
                case 'custom':
                    $result = $mikrotik->executeCommand($params['command'], $params['args'] ?? []);
                    $success = !isset($result[0]['_error']);
                    $message = $success ? 'Command berhasil dijalankan' : 'Gagal menjalankan command';
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Action tidak dikenali',
                    ]);
            }

            // Log the action
            $this->activityLog->log('router_command', 'routers', "Executed {$action} on router: {$router->name}", $params);

            return response()->json([
                'success' => $success,
                'message' => $message,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Refresh router status
     */
    public function refreshStatus(Router $router)
    {
        $this->authorizeRouter($router);

        $mikrotik = new MikrotikService();

        try {
            $connected = $mikrotik->connectRouter($router);
            $router->refresh();

            return response()->json([
                'success' => true,
                'status' => $router->status,
                'router' => $router,
            ]);
        } catch (\Exception $e) {
            $router->update(['status' => 'offline']);
            
            return response()->json([
                'success' => false,
                'status' => 'offline',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Authorize router access for admin-pop
     */
    private function authorizeRouter(Router $router): void
    {
        $user = auth()->user();
        
        if ($user->hasRole('admin-pop') && $router->pop_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke router ini.');
        }
    }

    /**
     * Get PPP Secrets from Mikrotik (for import/migration)
     */
    public function getPPPSecrets(Router $router)
    {
        $this->authorizeRouter($router);

        try {
            $mikrotik = new MikrotikService();
            $connected = $mikrotik->connectRouter($router);

            if (!$connected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat terhubung ke router',
                ]);
            }

            // Get all PPP Secrets
            $secrets = $mikrotik->executeCommand('/ppp/secret/print');

            return response()->json([
                'success' => true,
                'secrets' => $secrets ?? [],
                'count' => count($secrets ?? []),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
