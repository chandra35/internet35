<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PppProfile;
use App\Models\Router;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PackageController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:packages.view', only: ['index', 'show', 'getProfilesForRouter']),
            new Middleware('permission:packages.create', only: ['store']),
            new Middleware('permission:packages.edit', only: ['update']),
            new Middleware('permission:packages.delete', only: ['destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Display a listing of packages
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get routers for filter
        $routersQuery = Router::where('is_active', true);
        if ($user->hasRole('admin-pop')) {
            $routersQuery->where('pop_id', $user->id);
        }
        $routers = $routersQuery->orderBy('name')->get();

        // Query packages
        $query = Package::with(['router']);
        
        // Filter by router
        if ($request->filled('router_id')) {
            $query->where('router_id', $request->router_id);
        } elseif ($user->hasRole('admin-pop')) {
            // Admin-pop can only see packages from their routers
            $routerIds = $routers->pluck('id');
            $query->whereIn('router_id', $routerIds);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mikrotik_profile_name', 'like', "%{$search}%");
            });
        }

        $packages = $query->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.packages.index', compact('packages', 'routers'));
    }

    /**
     * Get package detail for edit (AJAX)
     */
    public function show(Package $package)
    {
        $package->load('router');
        
        return response()->json([
            'success' => true,
            'data' => $package
        ]);
    }

    /**
     * Get PPP Profiles for a router (for creating package)
     */
    public function getProfilesForRouter(Router $router)
    {
        $user = auth()->user();
        
        // Check access
        if ($user->hasRole('admin-pop') && $router->pop_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Get profiles from ppp_profiles table
        $profiles = PppProfile::where('router_id', $router->id)
            ->where('is_synced', true)
            ->orderBy('name')
            ->get();

        // Get existing packages profile names for this router
        $existingPackages = Package::where('router_id', $router->id)
            ->pluck('mikrotik_profile_name')
            ->toArray();

        // Map profiles with has_package flag
        $data = $profiles->map(function ($profile) use ($existingPackages, $router) {
            return [
                'id' => $profile->id,
                'name' => $profile->name,
                'rate_limit' => $profile->rate_limit,
                'local_address' => $profile->local_address,
                'remote_address' => $profile->remote_address,
                'router_id' => $profile->router_id,
                'router_name' => $router->name,
                'has_package' => in_array($profile->name, $existingPackages),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Store a new package from PPP Profile
     */
    public function store(Request $request)
    {
        $request->validate([
            'profile_id' => 'required|uuid|exists:ppp_profiles,id',
            'router_id' => 'required|uuid|exists:routers,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
            'is_public' => 'nullable',
        ]);

        // Get the profile
        $profile = PppProfile::findOrFail($request->profile_id);

        // Check if package already exists for this profile
        $existingPackage = Package::withTrashed()
            ->where('router_id', $request->router_id)
            ->where('mikrotik_profile_name', $profile->name)
            ->first();

        if ($existingPackage) {
            if ($existingPackage->trashed()) {
                // Restore soft deleted package and update
                $existingPackage->restore();
                $existingPackage->update([
                    'name' => $request->name,
                    'price' => $request->price,
                    'validity_days' => $request->validity_days,
                    'description' => $request->description,
                    'is_active' => $request->has('is_active'),
                    'is_public' => $request->has('is_public'),
                    // Sync from profile
                    'rate_limit' => $profile->rate_limit,
                    'local_address' => $profile->local_address,
                    'remote_address' => $profile->remote_address,
                    'speed_down' => $this->parseSpeedDown($profile->rate_limit),
                    'speed_up' => $this->parseSpeedUp($profile->rate_limit),
                ]);

                $this->activityLog->logCreate('packages', "Restored and updated package: {$existingPackage->name}");

                return response()->json([
                    'success' => true,
                    'message' => 'Paket berhasil direstore dan diupdate!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Profile ini sudah memiliki paket!',
            ], 422);
        }

        // Create new package from profile
        $package = Package::create([
            'router_id' => $request->router_id,
            'name' => $request->name,
            'mikrotik_profile_name' => $profile->name,
            'mikrotik_profile_id' => $profile->mikrotik_id,
            'rate_limit' => $profile->rate_limit,
            'local_address' => $profile->local_address,
            'remote_address' => $profile->remote_address,
            'dns_server' => $profile->dns_server,
            'parent_queue' => $profile->parent_queue,
            'address_list' => $profile->address_list,
            'only_one' => $profile->only_one === 'yes' || $profile->only_one === true,
            'price' => $request->price,
            'validity_days' => $request->validity_days,
            'speed_down' => $this->parseSpeedDown($profile->rate_limit),
            'speed_up' => $this->parseSpeedUp($profile->rate_limit),
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
            'is_public' => $request->has('is_public'),
            'sync_status' => 'synced', // Already synced from profile
            'last_synced_at' => now(),
            'created_by' => auth()->id(),
        ]);

        $this->activityLog->logCreate('packages', "Created package: {$package->name} from profile: {$profile->name}");

        return response()->json([
            'success' => true,
            'message' => 'Paket berhasil dibuat!',
        ]);
    }

    /**
     * Update package (only business info, not technical)
     */
    public function update(Request $request, Package $package)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
            'is_public' => 'nullable',
        ]);

        $oldData = $package->toArray();

        $package->update([
            'name' => $request->name,
            'price' => $request->price,
            'validity_days' => $request->validity_days,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
            'is_public' => $request->has('is_public'),
        ]);

        $this->activityLog->logUpdate('packages', "Updated package: {$package->name}", $oldData, $package->fresh()->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Paket berhasil diupdate!',
        ]);
    }

    /**
     * Delete a package
     */
    public function destroy(Package $package)
    {
        $name = $package->name;
        $oldData = $package->toArray();
        
        $package->delete();

        $this->activityLog->logDelete('packages', "Deleted package: {$name}", $oldData);

        return response()->json([
            'success' => true,
            'message' => 'Paket berhasil dihapus!',
        ]);
    }

    /**
     * Parse download speed from rate_limit
     */
    private function parseSpeedDown(?string $rateLimit): ?int
    {
        if (!$rateLimit) return null;
        
        $parts = explode(' ', $rateLimit);
        $maxLimit = $parts[0];
        $speedParts = explode('/', $maxLimit);
        
        return Package::speedToKbps($speedParts[0] ?? null);
    }

    /**
     * Parse upload speed from rate_limit
     */
    private function parseSpeedUp(?string $rateLimit): ?int
    {
        if (!$rateLimit) return null;
        
        $parts = explode(' ', $rateLimit);
        $maxLimit = $parts[0];
        $speedParts = explode('/', $maxLimit);
        
        return Package::speedToKbps($speedParts[1] ?? $speedParts[0] ?? null);
    }
}
