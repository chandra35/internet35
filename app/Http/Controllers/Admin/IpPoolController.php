<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpPool;
use App\Models\Router;
use App\Models\User;
use App\Helpers\Mikrotik\MikrotikService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IpPoolController extends Controller
{
    protected $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Get POP ID for current user or selected POP
     */
    private function getPopId(Request $request)
    {
        $user = auth()->user();
        
        if ($user->hasRole('superadmin') && $request->filled('pop_id')) {
            return $request->pop_id;
        }
        
        return $user->hasRole('admin-pop') ? $user->id : null;
    }

    /**
     * Display a listing of IP pools
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $popId = $this->getPopId($request);
        
        // Get POPs for superadmin selector
        $pops = null;
        if ($user->hasRole('superadmin')) {
            $pops = User::role('admin-pop')->orderBy('name')->get();
        }

        // Get routers for selected POP
        $routers = collect();
        $selectedRouter = null;
        $pools = collect();
        
        if ($popId) {
            $routers = Router::where('pop_id', $popId)->orderBy('name')->get();
            
            if ($request->filled('router_id')) {
                $selectedRouter = Router::find($request->router_id);
                if ($selectedRouter && $selectedRouter->pop_id === $popId) {
                    $pools = IpPool::where('router_id', $selectedRouter->id)
                        ->orderBy('name')
                        ->get();
                }
            }
        }

        return view('admin.ip-pools.index', compact(
            'pops', 'popId', 'routers', 'selectedRouter', 'pools'
        ));
    }

    /**
     * List pools for a router (AJAX for dropdowns)
     */
    public function listForRouter(Router $router)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('superadmin') && $router->pop_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $pools = IpPool::where('router_id', $router->id)
            ->orderBy('name')
            ->get(['id', 'name', 'ranges']);

        return response()->json([
            'success' => true,
            'data' => $pools
        ]);
    }

    /**
     * Preview sync from Mikrotik before saving
     */
    public function preview(Request $request, Router $router)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('superadmin') && $router->pop_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this router'
            ], 403);
        }

        try {
            $mikrotikService = new MikrotikService();
            
            if (!$mikrotikService->connectRouter($router)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal terhubung ke router'
                ]);
            }

            $mikrotikPools = $mikrotikService->getIpPools();
            $existingPools = IpPool::withTrashed()->where('router_id', $router->id)->get()->keyBy('name');
            
            $new = [];
            $update = [];
            $unchanged = [];
            $restore = []; // Pools that exist in Mikrotik but soft deleted in DB
            $mikrotikNames = [];
            
            foreach ($mikrotikPools as $mtPool) {
                $name = $mtPool['name'] ?? '';
                $mikrotikNames[] = $name;
                
                if ($existingPools->has($name)) {
                    $existing = $existingPools->get($name);
                    $poolData = IpPool::fromMikrotikData($mtPool, $router->id, $router->pop_id);
                    
                    // If soft deleted, mark for restore
                    if ($existing->trashed()) {
                        $restore[] = array_merge($mtPool, ['db_id' => $existing->id]);
                        continue;
                    }
                    
                    // Check if there are changes
                    $hasChanges = $existing->ranges != ($poolData['ranges'] ?? null) ||
                                  $existing->next_pool != ($poolData['next_pool'] ?? null);
                    
                    if ($hasChanges) {
                        $update[] = $mtPool;
                    } else {
                        $unchanged[] = $mtPool;
                    }
                } else {
                    $new[] = $mtPool;
                }
            }
            
            // Find pools not in Mikrotik (only active ones, exclude soft deleted)
            $notInMikrotik = $existingPools->filter(function($pool) use ($mikrotikNames) {
                return !in_array($pool->name, $mikrotikNames) && !$pool->trashed();
            })->values()->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'new' => $new,
                    'update' => $update,
                    'unchanged' => $unchanged,
                    'restore' => $restore,
                    'not_in_mikrotik' => $notInMikrotik
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Pool preview error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync pools from Mikrotik
     */
    public function sync(Request $request, Router $router)
    {
        $user = auth()->user();
        
        // Validate access
        if (!$user->hasRole('superadmin') && $router->pop_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this router'
            ], 403);
        }

        try {
            $mikrotikService = new MikrotikService();
            
            if (!$mikrotikService->connectRouter($router)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal terhubung ke router'
                ]);
            }

            // Get pools from Mikrotik
            $mikrotikPools = $mikrotikService->getIpPools();
            
            $syncedCount = 0;
            $updatedCount = 0;
            $newCount = 0;

            foreach ($mikrotikPools as $mtPool) {
                $poolData = IpPool::fromMikrotikData($mtPool, $router->id, $router->pop_id);
                
                // Check including soft deleted
                $existing = IpPool::withTrashed()
                    ->where('router_id', $router->id)
                    ->where('name', $poolData['name'])
                    ->first();

                if ($existing) {
                    // Restore if soft deleted
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->update($poolData);
                    $updatedCount++;
                } else {
                    IpPool::create($poolData);
                    $newCount++;
                }
                $syncedCount++;
            }

            // Mark pools not in Mikrotik as not synced
            $mikrotikNames = array_column($mikrotikPools, 'name');
            IpPool::where('router_id', $router->id)
                ->whereNotIn('name', $mikrotikNames)
                ->update(['is_synced' => false]);

            $this->activityLog->log('sync', 'ip_pools', "Synced {$syncedCount} IP pools from router {$router->name}");

            return response()->json([
                'success' => true,
                'message' => "Berhasil sync {$syncedCount} IP pool ({$newCount} baru, {$updatedCount} diupdate)",
                'data' => [
                    'total' => $syncedCount,
                    'new' => $newCount,
                    'updated' => $updatedCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("IP Pool sync error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pool details
     */
    public function show(IpPool $pool)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('superadmin') && $pool->pop_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $pool->load('router');
        
        return response()->json([
            'success' => true,
            'data' => $pool
        ]);
    }

    /**
     * Create pool in Mikrotik
     */
    public function store(Request $request, Router $router)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ranges' => 'required|string|max:500',
            'next_pool' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        
        if (!$user->hasRole('superadmin') && $router->pop_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $mikrotikService = new MikrotikService();
            
            if (!$mikrotikService->connectRouter($router)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal terhubung ke router'
                ]);
            }

            // Build params for Mikrotik
            $params = [
                'name' => $request->name,
                'ranges' => $request->ranges,
            ];
            if ($request->filled('next_pool')) $params['next-pool'] = $request->next_pool;
            if ($request->filled('comment')) $params['comment'] = $request->comment;

            $result = $mikrotikService->addIpPool($params);

            // Get mikrotik_id from result
            $mikrotikId = $result['ret'] ?? null;

            // Save to database
            $pool = IpPool::create([
                'router_id' => $router->id,
                'pop_id' => $router->pop_id,
                'mikrotik_id' => $mikrotikId,
                'name' => $request->name,
                'ranges' => $request->ranges,
                'next_pool' => $request->next_pool,
                'comment' => $request->comment,
                'is_synced' => true,
                'last_synced_at' => now(),
            ]);

            $this->activityLog->log('create', 'ip_pools', "Created IP pool {$request->name} on router {$router->name}");

            return response()->json([
                'success' => true,
                'message' => 'IP Pool berhasil dibuat',
                'data' => $pool
            ]);

        } catch (\Exception $e) {
            Log::error("IP Pool create error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update pool in Mikrotik
     */
    public function update(Request $request, IpPool $pool)
    {
        $request->validate([
            'ranges' => 'required|string|max:500',
            'next_pool' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        
        if (!$user->hasRole('superadmin') && $pool->pop_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $router = $pool->router;
            $mikrotikService = new MikrotikService();
            
            if (!$mikrotikService->connectRouter($router)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal terhubung ke router'
                ]);
            }

            // Build params for Mikrotik
            $params = ['ranges' => $request->ranges];
            if ($request->filled('next_pool')) $params['next-pool'] = $request->next_pool;
            if ($request->filled('comment')) $params['comment'] = $request->comment;

            $mikrotikService->updateIpPool($pool->mikrotik_id ?? $pool->name, $params);

            // Update database
            $pool->update([
                'ranges' => $request->ranges,
                'next_pool' => $request->next_pool,
                'comment' => $request->comment,
                'last_synced_at' => now(),
            ]);

            $this->activityLog->log('update', 'ip_pools', "Updated IP pool {$pool->name}");

            return response()->json([
                'success' => true,
                'message' => 'IP Pool berhasil diupdate',
                'data' => $pool
            ]);

        } catch (\Exception $e) {
            Log::error("IP Pool update error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete pool from Mikrotik
     */
    public function destroy(IpPool $pool)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('superadmin') && $pool->pop_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if pool is used by profiles
        $profilesCount = $pool->profiles()->count();
        if ($profilesCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "IP Pool digunakan oleh {$profilesCount} profile. Ubah profile terlebih dahulu."
            ]);
        }

        try {
            $router = $pool->router;
            $mikrotikService = new MikrotikService();
            
            if (!$mikrotikService->connectRouter($router)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal terhubung ke router'
                ]);
            }

            $mikrotikService->removeIpPool($pool->mikrotik_id ?? $pool->name);

            $poolName = $pool->name;
            $pool->delete();

            $this->activityLog->log('delete', 'ip_pools', "Deleted IP pool {$poolName}");

            return response()->json([
                'success' => true,
                'message' => 'IP Pool berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error("IP Pool delete error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get used IPs count from Mikrotik
     */
    public function getUsedIps(IpPool $pool)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('superadmin') && $pool->pop_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $router = $pool->router;
            $mikrotikService = new MikrotikService();
            
            if (!$mikrotikService->connectRouter($router)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal terhubung ke router'
                ]);
            }

            $usedIps = $mikrotikService->getIpPoolUsed($pool->name);

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $pool->total_ips,
                    'used' => count($usedIps),
                    'available' => $pool->total_ips - count($usedIps),
                    'ips' => $usedIps
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete IP pools
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'uuid|exists:ip_pools,id',
            'delete_from_mikrotik' => 'boolean'
        ]);

        $user = auth()->user();
        $deleteFromMikrotik = $request->boolean('delete_from_mikrotik');
        $deletedCount = 0;
        $errors = [];

        $pools = IpPool::whereIn('id', $request->ids)->get();
        
        // Group by router for batch processing
        $poolsByRouter = $pools->groupBy('router_id');
        
        foreach ($poolsByRouter as $routerId => $routerPools) {
            $router = Router::find($routerId);
            
            if (!$router) continue;
            
            // Check access
            if (!$user->hasRole('superadmin') && $router->pop_id !== $user->id) {
                $errors[] = "Tidak memiliki akses ke router {$router->name}";
                continue;
            }

            $mikrotikService = null;
            if ($deleteFromMikrotik) {
                $mikrotikService = new MikrotikService();
                if (!$mikrotikService->connectRouter($router)) {
                    $errors[] = "Gagal terhubung ke router {$router->name}";
                    $mikrotikService = null;
                }
            }

            foreach ($routerPools as $pool) {
                // Check if used by profiles
                if ($pool->profiles()->count() > 0) {
                    $errors[] = "IP Pool {$pool->name} digunakan oleh profile";
                    continue;
                }

                try {
                    // Delete from Mikrotik if requested
                    if ($deleteFromMikrotik && $mikrotikService && $pool->mikrotik_id) {
                        $mikrotikService->removeIpPool($pool->mikrotik_id);
                    }
                    
                    $poolName = $pool->name;
                    $pool->delete();
                    $deletedCount++;
                    
                    $this->activityLog->log('delete', 'ip_pools', "Deleted IP pool {$poolName}");
                } catch (\Exception $e) {
                    $errors[] = "Gagal hapus {$pool->name}: " . $e->getMessage();
                }
            }
        }

        $message = "Berhasil menghapus {$deletedCount} IP pool";
        if (count($errors) > 0) {
            $message .= ". Errors: " . implode(', ', array_slice($errors, 0, 3));
        }

        return response()->json([
            'success' => $deletedCount > 0,
            'message' => $message,
            'data' => [
                'deleted' => $deletedCount,
                'errors' => $errors
            ]
        ]);
    }
}
