<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PppProfile;
use App\Models\Router;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DataMaintenanceController extends Controller
{
    protected $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Show maintenance dashboard
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $popId = $this->getPopId($request);

        // For superadmin, get list of POPs
        $popUsers = null;
        if ($user->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
        }

        // Count existing data for this POP
        $stats = [];
        if ($popId) {
            $routerIds = Router::where('pop_id', $popId)->pluck('id');

            $stats['customers_total'] = Customer::where('pop_id', $popId)->count();
            $stats['customers_active'] = Customer::where('pop_id', $popId)->where('status', 'active')->count();
            $stats['customers_synced'] = Customer::where('pop_id', $popId)->where('mikrotik_synced', true)->count();
            $stats['packages_total'] = Package::whereIn('router_id', $routerIds)->count();
            $stats['profiles_total'] = PppProfile::whereIn('router_id', $routerIds)->count();
        }

        return view('admin.data-maintenance.index', compact('popUsers', 'popId', 'stats'));
    }

    /**
     * Clear all customers (soft delete)
     */
    public function clearCustomers(Request $request)
    {
        $user = auth()->user();
        $popId = $this->getPopId($request);

        if (!$popId) {
            return response()->json(['success' => false, 'message' => 'POP tidak ditemukan'], 422);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Password salah'], 403);
        }

        // Verify confirmation text
        if ($request->confirmation !== 'KOSONGKAN PELANGGAN') {
            return response()->json(['success' => false, 'message' => 'Teks konfirmasi tidak sesuai'], 422);
        }

        $deleteMikrotik = $request->boolean('delete_from_mikrotik');
        $customers = Customer::where('pop_id', $popId)->get();
        $totalCount = $customers->count();

        if ($totalCount === 0) {
            return response()->json(['success' => false, 'message' => 'Tidak ada data pelanggan untuk dihapus'], 422);
        }

        $mikrotikResults = ['success' => 0, 'failed' => 0, 'skipped' => 0];

        // Delete from Mikrotik if requested
        if ($deleteMikrotik) {
            $syncedCustomers = $customers->where('mikrotik_synced', true)->whereNotNull('pppoe_username');
            $grouped = $syncedCustomers->groupBy('router_id');

            foreach ($grouped as $routerId => $group) {
                if (!$routerId) {
                    $mikrotikResults['skipped'] += $group->count();
                    continue;
                }

                $router = Router::find($routerId);
                if (!$router) {
                    $mikrotikResults['skipped'] += $group->count();
                    continue;
                }

                try {
                    $mikrotikService = new MikrotikService();
                    if (!$mikrotikService->connect($router)) {
                        $mikrotikResults['failed'] += $group->count();
                        Log::warning("DataMaintenance: Failed to connect to router {$router->name}");
                        continue;
                    }

                    foreach ($group as $customer) {
                        try {
                            $result = $mikrotikService->deletePPPSecret($customer->pppoe_username);
                            if ($result['success']) {
                                $mikrotikResults['success']++;
                            } else {
                                $mikrotikResults['failed']++;
                            }
                        } catch (\Exception $e) {
                            $mikrotikResults['failed']++;
                        }
                    }
                } catch (\Exception $e) {
                    $mikrotikResults['failed'] += $group->count();
                    Log::error("DataMaintenance: Mikrotik error for router {$routerId}: " . $e->getMessage());
                }
            }
        }

        // Soft delete all customers
        Customer::where('pop_id', $popId)->delete();

        $this->activityLog->log(
            'maintenance',
            'customers',
            "KOSONGKAN PELANGGAN: {$totalCount} pelanggan dihapus (soft delete)" .
            ($deleteMikrotik ? ". Mikrotik: {$mikrotikResults['success']} berhasil, {$mikrotikResults['failed']} gagal, {$mikrotikResults['skipped']} dilewati" : '')
        );

        $message = "{$totalCount} pelanggan berhasil dihapus (dapat dipulihkan dalam 30 hari).";
        if ($deleteMikrotik) {
            $message .= " Mikrotik: {$mikrotikResults['success']} PPP Secret dihapus.";
            if ($mikrotikResults['failed'] > 0) {
                $message .= " {$mikrotikResults['failed']} gagal.";
            }
        }

        return response()->json(['success' => true, 'message' => $message, 'count' => $totalCount, 'mikrotik' => $mikrotikResults]);
    }

    /**
     * Clear all packages (soft delete)
     */
    public function clearPackages(Request $request)
    {
        $user = auth()->user();
        $popId = $this->getPopId($request);

        if (!$popId) {
            return response()->json(['success' => false, 'message' => 'POP tidak ditemukan'], 422);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Password salah'], 403);
        }

        // Verify confirmation text
        if ($request->confirmation !== 'KOSONGKAN PAKET') {
            return response()->json(['success' => false, 'message' => 'Teks konfirmasi tidak sesuai'], 422);
        }

        $routerIds = Router::where('pop_id', $popId)->pluck('id');
        $packages = Package::whereIn('router_id', $routerIds)->get();
        $totalCount = $packages->count();

        if ($totalCount === 0) {
            return response()->json(['success' => false, 'message' => 'Tidak ada data paket untuk dihapus'], 422);
        }

        // Check if any active customer uses these packages
        $usedCount = Customer::whereIn('package_id', $packages->pluck('id'))->count();

        // Soft delete
        Package::whereIn('router_id', $routerIds)->delete();

        $this->activityLog->log(
            'maintenance',
            'packages',
            "KOSONGKAN PAKET: {$totalCount} paket dihapus (soft delete). {$usedCount} pelanggan kehilangan referensi paket."
        );

        $message = "{$totalCount} paket berhasil dihapus.";
        if ($usedCount > 0) {
            $message .= " Perhatian: {$usedCount} pelanggan menggunakan paket ini — referensi paket mereka tetap tersimpan.";
        }

        return response()->json(['success' => true, 'message' => $message, 'count' => $totalCount]);
    }

    /**
     * Clear all PPP profiles (soft delete + optional Mikrotik removal)
     */
    public function clearProfiles(Request $request)
    {
        $user = auth()->user();
        $popId = $this->getPopId($request);

        if (!$popId) {
            return response()->json(['success' => false, 'message' => 'POP tidak ditemukan'], 422);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Password salah'], 403);
        }

        // Verify confirmation text
        if ($request->confirmation !== 'KOSONGKAN PROFILE') {
            return response()->json(['success' => false, 'message' => 'Teks konfirmasi tidak sesuai'], 422);
        }

        $deleteMikrotik = $request->boolean('delete_from_mikrotik');
        $routerIds = Router::where('pop_id', $popId)->pluck('id');
        $profiles = PppProfile::whereIn('router_id', $routerIds)->get();
        $totalCount = $profiles->count();

        if ($totalCount === 0) {
            return response()->json(['success' => false, 'message' => 'Tidak ada data profile untuk dihapus'], 422);
        }

        $mikrotikResults = ['success' => 0, 'failed' => 0, 'skipped' => 0];

        // Delete from Mikrotik if requested
        if ($deleteMikrotik) {
            $grouped = $profiles->groupBy('router_id');

            foreach ($grouped as $routerId => $group) {
                $router = Router::find($routerId);
                if (!$router) {
                    $mikrotikResults['skipped'] += $group->count();
                    continue;
                }

                try {
                    $mikrotikService = new MikrotikService();
                    if (!$mikrotikService->connect($router)) {
                        $mikrotikResults['failed'] += $group->count();
                        continue;
                    }

                    foreach ($group as $profile) {
                        // Skip default/built-in profiles
                        if (in_array($profile->name, ['default', 'default-encryption'])) {
                            $mikrotikResults['skipped']++;
                            continue;
                        }

                        try {
                            $result = $mikrotikService->deletePPPProfile($profile->name);
                            if ($result['success']) {
                                $mikrotikResults['success']++;
                            } else {
                                $mikrotikResults['failed']++;
                            }
                        } catch (\Exception $e) {
                            $mikrotikResults['failed']++;
                        }
                    }
                } catch (\Exception $e) {
                    $mikrotikResults['failed'] += $group->count();
                    Log::error("DataMaintenance: Mikrotik error for router {$routerId}: " . $e->getMessage());
                }
            }
        }

        // Soft delete
        PppProfile::whereIn('router_id', $routerIds)->delete();

        $this->activityLog->log(
            'maintenance',
            'ppp_profiles',
            "KOSONGKAN PROFILE: {$totalCount} profile dihapus (soft delete)" .
            ($deleteMikrotik ? ". Mikrotik: {$mikrotikResults['success']} berhasil, {$mikrotikResults['failed']} gagal, {$mikrotikResults['skipped']} dilewati" : '')
        );

        $message = "{$totalCount} profile berhasil dihapus.";
        if ($deleteMikrotik) {
            $message .= " Mikrotik: {$mikrotikResults['success']} profile dihapus dari router.";
            if ($mikrotikResults['failed'] > 0) {
                $message .= " {$mikrotikResults['failed']} gagal.";
            }
            if ($mikrotikResults['skipped'] > 0) {
                $message .= " {$mikrotikResults['skipped']} dilewati (default/tidak terkoneksi).";
            }
        }

        return response()->json(['success' => true, 'message' => $message, 'count' => $totalCount, 'mikrotik' => $mikrotikResults]);
    }

    /**
     * Get POP ID based on user role
     */
    private function getPopId(Request $request): ?string
    {
        $user = auth()->user();
        if ($user->hasRole('superadmin') && $request->filled('pop_id')) {
            return $request->pop_id;
        }
        return $user->hasRole('admin-pop') ? $user->id : null;
    }
}
