<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Router;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class NetworkMapController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:network-map.view'),
        ];
    }

    /**
     * Get POP ID based on user role
     */
    protected function getPopId(Request $request)
    {
        $user = auth()->user();
        
        if ($user->hasRole('superadmin')) {
            return $request->input('pop_id') ?: $request->session()->get('manage_pop_id');
        }
        
        return $user->id;
    }

    /**
     * Display network map
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $popId = $this->getPopId($request);
        
        // For superadmin, get list of POPs
        $popUsers = null;
        if ($user->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
            
            if ($request->has('pop_id')) {
                $request->session()->put('manage_pop_id', $request->input('pop_id'));
                $popId = $request->input('pop_id');
            }
        }
        
        // Get default center from POP setting or first router with coordinates
        $defaultCenter = ['lat' => -6.2088, 'lng' => 106.8456]; // Jakarta default
        
        if ($popId) {
            $router = Router::where('pop_id', $popId)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->first();
            
            if ($router) {
                $defaultCenter = ['lat' => $router->latitude, 'lng' => $router->longitude];
            } else {
                $odc = Odc::where('pop_id', $popId)
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->first();
                    
                if ($odc) {
                    $defaultCenter = ['lat' => $odc->latitude, 'lng' => $odc->longitude];
                }
            }
        }
        
        return view('admin.network-map.index', compact('popUsers', 'popId', 'defaultCenter'));
    }

    /**
     * Get map data (AJAX)
     */
    public function getData(Request $request)
    {
        $popId = $this->getPopId($request);
        
        if (!$popId) {
            return response()->json([
                'routers' => [],
                'odcs' => [],
                'odps' => [],
                'customers' => [],
                'lines' => [],
            ]);
        }
        
        // Get routers with coordinates
        $routers = Router::where('pop_id', $popId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function($router) {
                return [
                    'id' => $router->id,
                    'name' => $router->name,
                    'identity' => $router->identity,
                    'lat' => (float) $router->latitude,
                    'lng' => (float) $router->longitude,
                    'status' => $router->status,
                    'is_active' => $router->is_active,
                    'type' => 'router',
                ];
            });
        
        // Get ODCs with coordinates
        $odcs = Odc::where('pop_id', $popId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('router')
            ->get()
            ->map(function($odc) {
                return [
                    'id' => $odc->id,
                    'olt_id' => $odc->olt_id,
                    'name' => $odc->name,
                    'code' => $odc->code,
                    'lat' => (float) $odc->latitude,
                    'lng' => (float) $odc->longitude,
                    'status' => $odc->status,
                    'total_ports' => $odc->total_ports,
                    'used_ports' => $odc->used_ports,
                    'type' => 'odc',
                ];
            });
        
        // Get ODPs with coordinates
        $odps = Odp::where('pop_id', $popId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('odc')
            ->get()
            ->map(function($odp) {
                return [
                    'id' => $odp->id,
                    'odc_id' => $odp->odc_id,
                    'name' => $odp->name,
                    'code' => $odp->code,
                    'lat' => (float) $odp->latitude,
                    'lng' => (float) $odp->longitude,
                    'status' => $odp->status,
                    'total_ports' => $odp->total_ports,
                    'used_ports' => $odp->used_ports,
                    'type' => 'odp',
                ];
            });
        
        // Get customers with coordinates
        $customers = Customer::where('pop_id', $popId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('odp')
            ->get()
            ->map(function($customer) {
                return [
                    'id' => $customer->id,
                    'odp_id' => $customer->odp_id,
                    'name' => $customer->name,
                    'customer_id' => $customer->customer_id,
                    'lat' => (float) $customer->latitude,
                    'lng' => (float) $customer->longitude,
                    'status' => $customer->status,
                    'type' => 'customer',
                ];
            });
        
        // Build connection lines
        $lines = [];
        
        // Router to ODC lines (blue)
        foreach ($odcs as $odc) {
            $router = $routers->firstWhere('id', $odc['router_id']);
            if ($router) {
                $lines[] = [
                    'from' => ['lat' => $router['lat'], 'lng' => $router['lng']],
                    'to' => ['lat' => $odc['lat'], 'lng' => $odc['lng']],
                    'type' => 'router-odc',
                    'color' => '#007bff', // Blue
                    'weight' => 3,
                ];
            }
        }
        
        // ODC to ODP lines (green)
        foreach ($odps as $odp) {
            $odc = $odcs->firstWhere('id', $odp['odc_id']);
            if ($odc) {
                $lines[] = [
                    'from' => ['lat' => $odc['lat'], 'lng' => $odc['lng']],
                    'to' => ['lat' => $odp['lat'], 'lng' => $odp['lng']],
                    'type' => 'odc-odp',
                    'color' => '#28a745', // Green
                    'weight' => 2,
                ];
            }
        }
        
        // ODP to Customer lines (orange) - only show if filter enabled
        if ($request->input('show_customers', false)) {
            foreach ($customers as $customer) {
                $odp = $odps->firstWhere('id', $customer['odp_id']);
                if ($odp) {
                    $lines[] = [
                        'from' => ['lat' => $odp['lat'], 'lng' => $odp['lng']],
                        'to' => ['lat' => $customer['lat'], 'lng' => $customer['lng']],
                        'type' => 'odp-customer',
                        'color' => '#fd7e14', // Orange
                        'weight' => 1,
                    ];
                }
            }
        }
        
        return response()->json([
            'routers' => $routers->values(),
            'odcs' => $odcs->values(),
            'odps' => $odps->values(),
            'customers' => $request->input('show_customers', false) ? $customers->values() : [],
            'lines' => $lines,
        ]);
    }

    /**
     * Get statistics for dashboard widgets
     */
    public function getStats(Request $request)
    {
        $popId = $this->getPopId($request);
        
        $stats = [
            'routers' => [
                'total' => Router::when($popId, fn($q) => $q->where('pop_id', $popId))->count(),
                'online' => Router::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'online')->count(),
                'with_coords' => Router::when($popId, fn($q) => $q->where('pop_id', $popId))
                    ->whereNotNull('latitude')->whereNotNull('longitude')->count(),
            ],
            'odcs' => [
                'total' => Odc::when($popId, fn($q) => $q->where('pop_id', $popId))->count(),
                'active' => Odc::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'active')->count(),
                'with_coords' => Odc::when($popId, fn($q) => $q->where('pop_id', $popId))
                    ->whereNotNull('latitude')->whereNotNull('longitude')->count(),
            ],
            'odps' => [
                'total' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))->count(),
                'active' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'active')->count(),
                'with_coords' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))
                    ->whereNotNull('latitude')->whereNotNull('longitude')->count(),
            ],
            'customers' => [
                'total' => Customer::when($popId, fn($q) => $q->where('pop_id', $popId))->count(),
                'active' => Customer::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'active')->count(),
                'with_coords' => Customer::when($popId, fn($q) => $q->where('pop_id', $popId))
                    ->whereNotNull('latitude')->whereNotNull('longitude')->count(),
            ],
        ];
        
        return response()->json($stats);
    }
}
