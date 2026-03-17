<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\ResidentImport;
use App\Models\PopResidentAccess;
use App\Models\Resident;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravolt\Indonesia\Models\Province;
use Maatwebsite\Excel\Facades\Excel;

class ResidentController extends Controller
{
    protected ActivityLogService $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * List residents with search + access management
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperadmin = $user->hasRole('superadmin');

        $query = Resident::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nik', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%")
                  ->orWhere('no_kk', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kelurahan')) {
            $query->where('kelurahan', $request->kelurahan);
        }

        if ($request->filled('dusun')) {
            $query->where('dusun', $request->dusun);
        }

        if ($request->filled('data_status')) {
            $query->where('data_status', $request->data_status);
        }

        $residents = $query->orderBy('nama')->paginate(25)->withQueryString();
        $totalResidents = Resident::count();
        $kelurahans = Resident::select('kelurahan')->distinct()->whereNotNull('kelurahan')->where('kelurahan', '!=', '')->orderBy('kelurahan')->pluck('kelurahan');

        // Access management - get POP list with access status
        $popAccess = [];
        if ($isSuperadmin) {
            $pops = User::role('admin-pop')->orderBy('name')->get(['id', 'name']);
            $allAccess = PopResidentAccess::with('village')->get();
            foreach ($pops as $pop) {
                $popEntries = $allAccess->where('pop_id', $pop->id);
                $hasAllAccess = $popEntries->contains(fn($e) => $e->village_code === null);
                $villages = $popEntries->filter(fn($e) => $e->village_code !== null)->map(fn($e) => [
                    'code' => $e->village_code,
                    'name' => $e->village?->name ?? $e->village_code,
                ]);
                $popAccess[] = [
                    'id' => $pop->id,
                    'name' => $pop->name,
                    'has_access' => $popEntries->isNotEmpty(),
                    'has_all_access' => $hasAllAccess,
                    'villages' => $villages->values()->toArray(),
                ];
            }
        }

        // Get distinct villages from residents for granting access
        $residentVillages = Resident::select('village_code')
            ->distinct()
            ->whereNotNull('village_code')
            ->where('village_code', '!=', '')
            ->pluck('village_code')
            ->map(function ($code) {
                $village = \Laravolt\Indonesia\Models\Village::where('code', $code)->first();
                return ['code' => $code, 'name' => $village?->name ?? $code];
            })
            ->sortBy('name')
            ->values();

        $provinces = Province::orderBy('name')->get();

        return view('admin.residents.index', compact('residents', 'totalResidents', 'kelurahans', 'popAccess', 'isSuperadmin', 'provinces', 'residentVillages'));
    }

    /**
     * Preview Excel file before import - reads and returns sample data + stats
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $preview = new \App\Imports\ResidentPreviewImport();
            Excel::import($preview, $request->file('file'));

            $results = $preview->getResults();

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Resident preview failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Import residents from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'province_code' => 'required|string',
            'city_code' => 'required|string',
            'district_code' => 'required|string',
            'village_code' => 'required|string',
        ]);

        try {
            $import = new ResidentImport(
                auth()->id(),
                $request->province_code,
                $request->city_code,
                $request->district_code,
                $request->village_code
            );
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            $this->activityLog->log(
                'import',
                'residents',
                "Impor data kependudukan: {$results['success']} baru, {$results['updated']} diperbarui, {$results['auto_corrected']} auto-koreksi, {$results['flagged']} perlu update, {$results['failed']} gagal, {$results['skipped']} dilewati",
            );

            return response()->json([
                'success' => true,
                'message' => 'Impor data kependudukan berhasil',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Resident import failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Impor gagal: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a resident
     */
    public function destroy(Resident $resident)
    {
        $resident->delete();

        $this->activityLog->log('delete', 'residents', "Hapus data penduduk: {$resident->nama} ({$resident->nik})");

        return response()->json(['success' => true, 'message' => 'Data penduduk berhasil dihapus']);
    }

    /**
     * Bulk delete residents
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'uuid']);

        $count = Resident::whereIn('id', $request->ids)->count();
        Resident::whereIn('id', $request->ids)->delete();

        $this->activityLog->log('delete', 'residents', "Hapus bulk {$count} data penduduk");

        return response()->json(['success' => true, 'message' => "{$count} data penduduk berhasil dihapus"]);
    }

    /**
     * Clear all residents
     */
    public function clearAll()
    {
        $count = Resident::count();
        Resident::query()->forceDelete();

        $this->activityLog->log('delete', 'residents', "Hapus semua data penduduk ({$count} record)");

        return response()->json(['success' => true, 'message' => "{$count} data penduduk berhasil dihapus"]);
    }

    /**
     * Grant POP access to resident data (optionally limited to a village)
     */
    public function grantAccess(Request $request)
    {
        $request->validate([
            'pop_id' => 'required|uuid|exists:users,id',
            'village_code' => 'nullable|string',
        ]);

        $villageCode = $request->village_code ?: null;

        $existing = PopResidentAccess::where('pop_id', $request->pop_id)
            ->where('village_code', $villageCode)
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Akses wilayah ini sudah diberikan'], 422);
        }

        // If granting "all", remove individual village entries
        if ($villageCode === null) {
            PopResidentAccess::where('pop_id', $request->pop_id)->delete();
        }

        // If POP already has "all" access, don't add specific village
        $hasAll = PopResidentAccess::where('pop_id', $request->pop_id)
            ->whereNull('village_code')
            ->exists();
        if ($hasAll && $villageCode !== null) {
            return response()->json(['success' => false, 'message' => 'POP sudah memiliki akses semua wilayah'], 422);
        }

        PopResidentAccess::create([
            'pop_id' => $request->pop_id,
            'village_code' => $villageCode,
            'granted_by' => auth()->id(),
        ]);

        $pop = User::find($request->pop_id);
        $villageName = $villageCode
            ? (\Laravolt\Indonesia\Models\Village::where('code', $villageCode)->first()?->name ?? $villageCode)
            : 'Semua Wilayah';
        $this->activityLog->log('create', 'pop_resident_access', "Berikan akses data penduduk ke POP: {$pop->name} ({$villageName})");

        return response()->json(['success' => true, 'message' => "Akses {$villageName} diberikan ke {$pop->name}"]);
    }

    /**
     * Revoke POP access to resident data
     */
    public function revokeAccess(Request $request)
    {
        $request->validate([
            'pop_id' => 'required|uuid|exists:users,id',
            'village_code' => 'nullable|string',
            'revoke_all' => 'nullable|boolean',
        ]);

        if ($request->boolean('revoke_all')) {
            $count = PopResidentAccess::where('pop_id', $request->pop_id)->delete();
            if ($count === 0) {
                return response()->json(['success' => false, 'message' => 'POP tidak memiliki akses'], 422);
            }
        } else {
            $villageCode = $request->village_code ?: null;
            $access = PopResidentAccess::where('pop_id', $request->pop_id)
                ->where('village_code', $villageCode)
                ->first();
            if (!$access) {
                return response()->json(['success' => false, 'message' => 'Akses tidak ditemukan'], 422);
            }
            $access->delete();
        }

        $pop = User::find($request->pop_id);
        $this->activityLog->log('delete', 'pop_resident_access', "Cabut akses data penduduk dari POP: {$pop->name}");

        return response()->json(['success' => true, 'message' => "Akses dicabut dari {$pop->name}"]);
    }

    /**
     * Search residents by NIK or name (API for customer assign)
     */
    public function search(Request $request)
    {
        $user = auth()->user();

        // Check if POP has access and get allowed villages
        $allowedVillages = null; // null = all
        if (!$user->hasRole('superadmin')) {
            $accessEntries = PopResidentAccess::where('pop_id', $user->id)->get();
            if ($accessEntries->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Tidak memiliki akses data penduduk'], 403);
            }
            // If any entry has null village_code, POP has access to all
            $hasAll = $accessEntries->contains(fn($e) => $e->village_code === null);
            if (!$hasAll) {
                $allowedVillages = $accessEntries->pluck('village_code')->filter()->toArray();
            }
        }

        $request->validate(['q' => 'required|string|min:2']);

        $q = $request->q;
        $query = Resident::where(function ($qry) use ($q) {
            $qry->where('nik', 'like', "%{$q}%")
                ->orWhere('nama', 'like', "%{$q}%")
                ->orWhere('no_kk', 'like', "%{$q}%");
        });

        // Filter by allowed villages
        if ($allowedVillages !== null) {
            $query->whereIn('village_code', $allowedVillages);
        }

        $residents = $query->limit(20)
            ->get(['id', 'nik', 'nama', 'no_kk', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'dusun', 'rw', 'rt', 'kelurahan', 'province_code', 'city_code', 'district_code', 'village_code']);

        return response()->json(['success' => true, 'data' => $residents]);
    }

    /**
     * Check if current user's POP has resident data access
     */
    public function checkAccess()
    {
        $user = auth()->user();

        if ($user->hasRole('superadmin')) {
            return response()->json(['has_access' => true, 'all_villages' => true]);
        }

        $entries = PopResidentAccess::where('pop_id', $user->id)->get();
        $hasAccess = $entries->isNotEmpty();
        $hasAll = $entries->contains(fn($e) => $e->village_code === null);

        return response()->json([
            'has_access' => $hasAccess,
            'all_villages' => $hasAll,
            'villages' => $hasAll ? [] : $entries->pluck('village_code')->filter()->values(),
        ]);
    }
}
