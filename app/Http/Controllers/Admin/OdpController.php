<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class OdpController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:odps.view', only: ['index', 'show']),
            new Middleware('permission:odps.create', only: ['create', 'store']),
            new Middleware('permission:odps.edit', only: ['edit', 'update']),
            new Middleware('permission:odps.delete', only: ['destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
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
     * Display ODP list
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
        
        // Build query
        $query = Odp::with(['pop', 'odc.router', 'creator'])
            ->withCount('customers')
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->when($request->odc_id, fn($q, $o) => $q->where('odc_id', $o))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, function($q, $s) {
                $q->where(function($sq) use ($s) {
                    $sq->where('name', 'like', "%{$s}%")
                       ->orWhere('code', 'like', "%{$s}%")
                       ->orWhere('address', 'like', "%{$s}%")
                       ->orWhere('pole_number', 'like', "%{$s}%");
                });
            });
        
        $odps = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Get ODCs for filter
        $odcs = Odc::when($popId, fn($q) => $q->where('pop_id', $popId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        
        // Statistics
        $stats = [
            'total' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))->count(),
            'active' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'active')->count(),
            'maintenance' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'maintenance')->count(),
            'inactive' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'inactive')->count(),
        ];
        
        return view('admin.odps.index', compact('odps', 'popUsers', 'popId', 'odcs', 'stats'));
    }

    /**
     * Show create form
     */
    public function create(Request $request)
    {
        $popId = $this->getPopId($request);
        
        if (!$popId && auth()->user()->hasRole('superadmin')) {
            return back()->with('error', 'Pilih POP terlebih dahulu');
        }
        
        $odcs = Odc::where('pop_id', $popId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        
        // Pre-select ODC if provided
        $selectedOdc = $request->input('odc_id');
        $nextCode = null;
        
        if ($selectedOdc) {
            $nextCode = Odp::generateCode($selectedOdc);
        }
        
        return view('admin.odps.create', compact('odcs', 'nextCode', 'popId', 'selectedOdc'));
    }

    /**
     * Store new ODP
     */
    public function store(Request $request)
    {
        $popId = $this->getPopId($request);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:odps,code',
            'odc_id' => 'required|uuid|exists:odcs,id',
            'odc_port' => 'required|integer|min:1',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'total_ports' => 'required|integer|min:1|max:100',
            'status' => 'required|in:active,maintenance,inactive',
            'box_type' => 'nullable|string|max:100',
            'splitter_type' => 'nullable|string|max:100',
            'pole_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        // Validate ODC port is available
        $odc = Odc::findOrFail($validated['odc_id']);
        
        if ($validated['odc_port'] > $odc->total_ports) {
            return back()
                ->withInput()
                ->with('error', 'Port ODC yang dipilih melebihi total port ODC (' . $odc->total_ports . ')');
        }
        
        // Check if port is already used
        $portUsed = Odp::where('odc_id', $validated['odc_id'])
            ->where('odc_port', $validated['odc_port'])
            ->exists();
            
        if ($portUsed) {
            return back()
                ->withInput()
                ->with('error', 'Port ODC ' . $validated['odc_port'] . ' sudah digunakan');
        }
        
        $validated['pop_id'] = $popId;
        $validated['created_by'] = auth()->id();
        $validated['used_ports'] = 0;
        
        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = Odp::generateCode($validated['odc_id']);
        }
        
        try {
            DB::beginTransaction();
            
            $odp = Odp::create($validated);
            
            // Update ODC used_ports
            $odc->increment('used_ports');
            
            $this->activityLog->log(
                'odp_created',
                "ODP {$odp->code} berhasil dibuat",
                $odp
            );
            
            DB::commit();
            
            return redirect()
                ->route('admin.odps.index', ['pop_id' => $popId])
                ->with('success', 'ODP berhasil ditambahkan');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan ODP: ' . $e->getMessage());
        }
    }

    /**
     * Show ODP detail
     */
    public function show(Odp $odp)
    {
        $odp->load(['pop', 'odc.router', 'customers', 'creator']);
        
        return view('admin.odps.show', compact('odp'));
    }

    /**
     * Show edit form
     */
    public function edit(Odp $odp)
    {
        $odcs = Odc::where('pop_id', $odp->pop_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        
        return view('admin.odps.edit', compact('odp', 'odcs'));
    }

    /**
     * Update ODP
     */
    public function update(Request $request, Odp $odp)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:odps,code,' . $odp->id,
            'odc_id' => 'required|uuid|exists:odcs,id',
            'odc_port' => 'required|integer|min:1',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'total_ports' => 'required|integer|min:1|max:100',
            'status' => 'required|in:active,maintenance,inactive',
            'box_type' => 'nullable|string|max:100',
            'splitter_type' => 'nullable|string|max:100',
            'pole_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        // Validate total_ports >= used_ports
        if ($validated['total_ports'] < $odp->used_ports) {
            return back()
                ->withInput()
                ->with('error', 'Total port tidak boleh kurang dari port yang sudah digunakan (' . $odp->used_ports . ')');
        }
        
        $odc = Odc::findOrFail($validated['odc_id']);
        
        if ($validated['odc_port'] > $odc->total_ports) {
            return back()
                ->withInput()
                ->with('error', 'Port ODC yang dipilih melebihi total port ODC (' . $odc->total_ports . ')');
        }
        
        // Check if port is already used (exclude current ODP)
        $portUsed = Odp::where('odc_id', $validated['odc_id'])
            ->where('odc_port', $validated['odc_port'])
            ->where('id', '!=', $odp->id)
            ->exists();
            
        if ($portUsed) {
            return back()
                ->withInput()
                ->with('error', 'Port ODC ' . $validated['odc_port'] . ' sudah digunakan');
        }
        
        try {
            DB::beginTransaction();
            
            $oldOdcId = $odp->odc_id;
            $oldData = $odp->toArray();
            
            // Update ODC used_ports if ODC changed
            if ($oldOdcId != $validated['odc_id']) {
                Odc::where('id', $oldOdcId)->decrement('used_ports');
                $odc->increment('used_ports');
            }
            
            $odp->update($validated);
            
            $this->activityLog->log(
                'odp_updated',
                "ODP {$odp->code} berhasil diperbarui",
                $odp,
                ['old' => $oldData, 'new' => $validated]
            );
            
            DB::commit();
            
            return redirect()
                ->route('admin.odps.index', ['pop_id' => $odp->pop_id])
                ->with('success', 'ODP berhasil diperbarui');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui ODP: ' . $e->getMessage());
        }
    }

    /**
     * Delete ODP
     */
    public function destroy(Odp $odp)
    {
        // Check if has customers
        if ($odp->customers()->count() > 0) {
            return back()->with('error', 'Tidak dapat menghapus ODP yang masih memiliki pelanggan');
        }
        
        try {
            DB::beginTransaction();
            
            // Decrease ODC used_ports
            Odc::where('id', $odp->odc_id)->decrement('used_ports');
            
            $this->activityLog->log(
                'odp_deleted',
                "ODP {$odp->code} berhasil dihapus",
                $odp
            );
            
            $popId = $odp->pop_id;
            $odp->delete();
            
            DB::commit();
            
            return redirect()
                ->route('admin.odps.index', ['pop_id' => $popId])
                ->with('success', 'ODP berhasil dihapus');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus ODP: ' . $e->getMessage());
        }
    }

    /**
     * Get ODPs by ODC (AJAX)
     */
    public function getByOdc(Request $request)
    {
        $odcId = $request->input('odc_id');
        
        $odps = Odp::where('odc_id', $odcId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function($odp) {
                return [
                    'id' => $odp->id,
                    'name' => $odp->name,
                    'code' => $odp->code,
                    'available_ports' => $odp->available_ports,
                ];
            });
        
        return response()->json($odps);
    }

    /**
     * Generate code (AJAX)
     */
    public function generateCode(Request $request)
    {
        $odcId = $request->input('odc_id');
        
        if (!$odcId) {
            return response()->json(['code' => '']);
        }
        
        $code = Odp::generateCode($odcId);
        
        return response()->json(['code' => $code]);
    }
}
