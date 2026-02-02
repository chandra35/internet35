<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PppProfile;
use App\Models\IpPool;
use App\Models\Router;
use App\Models\User;
use App\Helpers\Mikrotik\MikrotikService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PppProfileController extends Controller
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
     * Display a listing of profiles
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
        $profiles = collect();
        
        if ($popId) {
            $routers = Router::where('pop_id', $popId)->orderBy('name')->get();
            
            if ($request->filled('router_id')) {
                $selectedRouter = Router::find($request->router_id);
                if ($selectedRouter && $selectedRouter->pop_id === $popId) {
                    $profiles = PppProfile::where('router_id', $selectedRouter->id)
                        ->orderBy('name')
                        ->get();
                }
            }
        }

        return view('admin.ppp-profiles.index', compact(
            'pops', 'popId', 'routers', 'selectedRouter', 'profiles'
        ));
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

            $mikrotikProfiles = $mikrotikService->getPppProfiles();
            $existingProfiles = PppProfile::withTrashed()->where('router_id', $router->id)->get()->keyBy('name');
            
            $new = [];
            $update = [];
            $unchanged = [];
            $restore = []; // Profiles that exist in Mikrotik but soft deleted in DB
            $mikrotikNames = [];
            
            foreach ($mikrotikProfiles as $mtProfile) {
                $name = $mtProfile['name'] ?? '';
                $mikrotikNames[] = $name;
                
                if ($existingProfiles->has($name)) {
                    $existing = $existingProfiles->get($name);
                    $profileData = PppProfile::fromMikrotikData($mtProfile, $router->id, $router->pop_id);
                    
                    // If soft deleted, mark for restore
                    if ($existing->trashed()) {
                        $restore[] = array_merge($mtProfile, ['db_id' => $existing->id]);
                        continue;
                    }
                    
                    // Check if there are changes
                    $hasChanges = false;
                    foreach (['local_address', 'remote_address', 'rate_limit', 'dns_server'] as $field) {
                        if ($existing->$field != ($profileData[$field] ?? null)) {
                            $hasChanges = true;
                            break;
                        }
                    }
                    
                    if ($hasChanges) {
                        $update[] = $mtProfile;
                    } else {
                        $unchanged[] = $mtProfile;
                    }
                } else {
                    $new[] = $mtProfile;
                }
            }
            
            // Find profiles not in Mikrotik (only active ones, exclude soft deleted)
            $notInMikrotik = $existingProfiles->filter(function($profile) use ($mikrotikNames) {
                return !in_array($profile->name, $mikrotikNames) && !$profile->trashed();
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
            Log::error("Profile preview error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync profiles from Mikrotik
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

            // Get profiles from Mikrotik
            $mikrotikProfiles = $mikrotikService->getPppProfiles();
            
            $syncedCount = 0;
            $updatedCount = 0;
            $newCount = 0;

            foreach ($mikrotikProfiles as $mtProfile) {
                $profileData = PppProfile::fromMikrotikData($mtProfile, $router->id, $router->pop_id);
                
                // Check including soft deleted
                $existing = PppProfile::withTrashed()
                    ->where('router_id', $router->id)
                    ->where('name', $profileData['name'])
                    ->first();

                if ($existing) {
                    // Restore if soft deleted
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->update($profileData);
                    $updatedCount++;
                } else {
                    PppProfile::create($profileData);
                    $newCount++;
                }
                $syncedCount++;
            }

            // Mark profiles not in Mikrotik as not synced
            $mikrotikNames = array_column($mikrotikProfiles, 'name');
            PppProfile::where('router_id', $router->id)
                ->whereNotIn('name', $mikrotikNames)
                ->update(['is_synced' => false]);

            $this->activityLog->log('sync', 'ppp_profiles', "Synced {$syncedCount} profiles from router {$router->name}");

            return response()->json([
                'success' => true,
                'message' => "Berhasil sync {$syncedCount} profile ({$newCount} baru, {$updatedCount} diupdate)",
                'data' => [
                    'total' => $syncedCount,
                    'new' => $newCount,
                    'updated' => $updatedCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Profile sync error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get profile details
     */
    public function show(PppProfile $profile)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('superadmin') && $profile->pop_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $profile->load('router');
        
        return response()->json([
            'success' => true,
            'data' => $profile
        ]);
    }

    /**
     * Create profile in Mikrotik
     */
    public function store(Request $request, Router $router)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'local_address' => 'nullable|string|max:50',
            'remote_address' => 'nullable|string|max:50',
            'rate_limit' => 'nullable|string|max:50',
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
            $params = ['name' => $request->name];
            if ($request->filled('local_address')) $params['local-address'] = $request->local_address;
            if ($request->filled('remote_address')) $params['remote-address'] = $request->remote_address;
            if ($request->filled('rate_limit')) $params['rate-limit'] = $request->rate_limit;
            if ($request->filled('comment')) $params['comment'] = $request->comment;

            $result = $mikrotikService->addPppProfile($params);

            // Get mikrotik_id from result
            $mikrotikId = $result['ret'] ?? null;

            // Save to database
            $profile = PppProfile::create([
                'router_id' => $router->id,
                'pop_id' => $router->pop_id,
                'mikrotik_id' => $mikrotikId,
                'name' => $request->name,
                'local_address' => $request->local_address,
                'remote_address' => $request->remote_address,
                'rate_limit' => $request->rate_limit,
                'comment' => $request->comment,
                'is_synced' => true,
                'last_synced_at' => now(),
            ]);

            $this->activityLog->log('create', 'ppp_profiles', "Created profile {$request->name} on router {$router->name}");

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil dibuat',
                'data' => $profile
            ]);

        } catch (\Exception $e) {
            Log::error("Profile create error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update profile in Mikrotik
     */
    public function update(Request $request, PppProfile $profile)
    {
        $request->validate([
            'local_address' => 'nullable|string|max:50',
            'remote_address' => 'nullable|string|max:50',
            'rate_limit' => 'nullable|string|max:50',
            'comment' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        
        if (!$user->hasRole('superadmin') && $profile->pop_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $router = $profile->router;
            $mikrotikService = new MikrotikService();
            
            if (!$mikrotikService->connectRouter($router)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal terhubung ke router'
                ]);
            }

            // Build params for Mikrotik
            $params = [];
            if ($request->filled('local_address')) $params['local-address'] = $request->local_address;
            if ($request->filled('remote_address')) $params['remote-address'] = $request->remote_address;
            if ($request->filled('rate_limit')) $params['rate-limit'] = $request->rate_limit;
            if ($request->filled('comment')) $params['comment'] = $request->comment;

            $mikrotikService->updatePppProfile($profile->mikrotik_id ?? $profile->name, $params);

            // Update database
            $profile->update([
                'local_address' => $request->local_address,
                'remote_address' => $request->remote_address,
                'rate_limit' => $request->rate_limit,
                'comment' => $request->comment,
                'last_synced_at' => now(),
            ]);

            $this->activityLog->log('update', 'ppp_profiles', "Updated profile {$profile->name}");

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diupdate',
                'data' => $profile
            ]);

        } catch (\Exception $e) {
            Log::error("Profile update error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete profile from Mikrotik
     */
    public function destroy(PppProfile $profile)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('superadmin') && $profile->pop_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if profile is used by packages
        $packagesCount = $profile->packages()->count();
        if ($packagesCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Profile digunakan oleh {$packagesCount} paket. Hapus atau ubah paket terlebih dahulu."
            ]);
        }

        try {
            $router = $profile->router;
            $mikrotikService = new MikrotikService();
            
            if (!$mikrotikService->connectRouter($router)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal terhubung ke router'
                ]);
            }

            $mikrotikService->removePppProfile($profile->mikrotik_id ?? $profile->name);

            $profileName = $profile->name;
            $profile->delete();

            $this->activityLog->log('delete', 'ppp_profiles', "Deleted profile {$profileName}");

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error("Profile delete error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete profiles
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'uuid|exists:ppp_profiles,id',
            'delete_from_mikrotik' => 'boolean'
        ]);

        $user = auth()->user();
        $deleteFromMikrotik = $request->boolean('delete_from_mikrotik');
        $deletedCount = 0;
        $errors = [];

        $profiles = PppProfile::whereIn('id', $request->ids)->get();
        
        // Group by router for batch processing
        $profilesByRouter = $profiles->groupBy('router_id');
        
        foreach ($profilesByRouter as $routerId => $routerProfiles) {
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

            foreach ($routerProfiles as $profile) {
                // Check if used by packages
                if ($profile->packages()->count() > 0) {
                    $errors[] = "Profile {$profile->name} digunakan oleh paket";
                    continue;
                }

                try {
                    // Delete from Mikrotik if requested
                    if ($deleteFromMikrotik && $mikrotikService && $profile->mikrotik_id) {
                        $mikrotikService->removePppProfile($profile->mikrotik_id);
                    }
                    
                    $profileName = $profile->name;
                    $profile->delete();
                    $deletedCount++;
                    
                    $this->activityLog->log('delete', 'ppp_profiles', "Deleted profile {$profileName}");
                } catch (\Exception $e) {
                    $errors[] = "Gagal hapus {$profile->name}: " . $e->getMessage();
                }
            }
        }

        $message = "Berhasil menghapus {$deletedCount} profile";
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
