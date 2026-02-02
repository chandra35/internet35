<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Odc;
use App\Models\Router;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class OdcController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:odcs.view', only: ['index', 'show']),
            new Middleware('permission:odcs.create', only: ['create', 'store']),
            new Middleware('permission:odcs.edit', only: ['edit', 'update']),
            new Middleware('permission:odcs.delete', only: ['destroy']),
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
     * Display ODC list
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
        $query = Odc::with(['pop', 'router', 'creator'])
            ->withCount('odps')
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->when($request->router_id, fn($q, $r) => $q->where('router_id', $r))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, function($q, $s) {
                $q->where(function($sq) use ($s) {
                    $sq->where('name', 'like', "%{$s}%")
                       ->orWhere('code', 'like', "%{$s}%")
                       ->orWhere('address', 'like', "%{$s}%");
                });
            });
        
        $odcs = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Get routers for filter
        $routers = Router::when($popId, fn($q) => $q->where('pop_id', $popId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Statistics
        $stats = [
            'total' => Odc::when($popId, fn($q) => $q->where('pop_id', $popId))->count(),
            'active' => Odc::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'active')->count(),
            'maintenance' => Odc::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'maintenance')->count(),
            'inactive' => Odc::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'inactive')->count(),
        ];
        
        return view('admin.odcs.index', compact('odcs', 'popUsers', 'popId', 'routers', 'stats'));
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
        
        $routers = Router::where('pop_id', $popId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Generate ODC code
        $nextCode = Odc::generateCode($popId);
        
        return view('admin.odcs.create', compact('routers', 'nextCode', 'popId'));
    }

    /**
     * Store new ODC
     */
    public function store(Request $request)
    {
        $popId = $this->getPopId($request);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:odcs,code',
            'router_id' => 'required|uuid|exists:routers,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'total_ports' => 'required|integer|min:1|max:1000',
            'status' => 'required|in:active,maintenance,inactive',
            'cabinet_type' => 'nullable|string|max:100',
            'cable_type' => 'nullable|string|max:100',
            'cable_core' => 'nullable|integer|min:1',
            'cable_distance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $validated['pop_id'] = $popId;
        $validated['created_by'] = auth()->id();
        $validated['used_ports'] = 0;
        
        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = Odc::generateCode($popId);
        }
        
        try {
            DB::beginTransaction();
            
            $odc = Odc::create($validated);
            
            $this->activityLog->log(
                'odc_created',
                "ODC {$odc->code} berhasil dibuat",
                $odc
            );
            
            DB::commit();
            
            return redirect()
                ->route('admin.odcs.index', ['pop_id' => $popId])
                ->with('success', 'ODC berhasil ditambahkan');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan ODC: ' . $e->getMessage());
        }
    }

    /**
     * Show ODC detail
     */
    public function show(Odc $odc)
    {
        $odc->load(['pop', 'router', 'odps.customers', 'creator']);
        
        return view('admin.odcs.show', compact('odc'));
    }

    /**
     * Show edit form
     */
    public function edit(Odc $odc)
    {
        $routers = Router::where('pop_id', $odc->pop_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('admin.odcs.edit', compact('odc', 'routers'));
    }

    /**
     * Update ODC
     */
    public function update(Request $request, Odc $odc)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:odcs,code,' . $odc->id,
            'router_id' => 'required|uuid|exists:routers,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'total_ports' => 'required|integer|min:1|max:1000',
            'status' => 'required|in:active,maintenance,inactive',
            'cabinet_type' => 'nullable|string|max:100',
            'cable_type' => 'nullable|string|max:100',
            'cable_core' => 'nullable|integer|min:1',
            'cable_distance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        // Validate total_ports >= used_ports
        if ($validated['total_ports'] < $odc->used_ports) {
            return back()
                ->withInput()
                ->with('error', 'Total port tidak boleh kurang dari port yang sudah digunakan (' . $odc->used_ports . ')');
        }
        
        try {
            DB::beginTransaction();
            
            $oldData = $odc->toArray();
            $odc->update($validated);
            
            $this->activityLog->log(
                'odc_updated',
                "ODC {$odc->code} berhasil diperbarui",
                $odc,
                ['old' => $oldData, 'new' => $validated]
            );
            
            DB::commit();
            
            return redirect()
                ->route('admin.odcs.index', ['pop_id' => $odc->pop_id])
                ->with('success', 'ODC berhasil diperbarui');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui ODC: ' . $e->getMessage());
        }
    }

    /**
     * Delete ODC
     */
    public function destroy(Odc $odc)
    {
        // Check if has ODPs
        if ($odc->odps()->count() > 0) {
            return back()->with('error', 'Tidak dapat menghapus ODC yang masih memiliki ODP');
        }
        
        try {
            DB::beginTransaction();
            
            $this->activityLog->log(
                'odc_deleted',
                "ODC {$odc->code} berhasil dihapus",
                $odc
            );
            
            $popId = $odc->pop_id;
            $odc->delete();
            
            DB::commit();
            
            return redirect()
                ->route('admin.odcs.index', ['pop_id' => $popId])
                ->with('success', 'ODC berhasil dihapus');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus ODC: ' . $e->getMessage());
        }
    }

    /**
     * Get ODCs by Router (AJAX)
     */
    public function getByRouter(Request $request)
    {
        $routerId = $request->input('router_id');
        
        $odcs = Odc::where('router_id', $routerId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'available_ports' => DB::raw('total_ports - used_ports')]);
        
        return response()->json($odcs);
    }
}
