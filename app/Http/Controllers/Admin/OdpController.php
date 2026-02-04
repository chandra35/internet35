<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
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
        $query = Odp::with(['pop', 'odc.olt', 'olt', 'parentOdp', 'creator'])
            ->withCount('customers')
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->when($request->odc_id, fn($q, $o) => $q->where('odc_id', $o))
            ->when($request->olt_id, fn($q, $o) => $q->where('olt_id', $o))
            ->when($request->connection_type, function($q, $type) {
                if ($type === 'odc') {
                    $q->whereNotNull('odc_id');
                } elseif ($type === 'olt') {
                    $q->whereNotNull('olt_id')->whereNull('odc_id');
                } elseif ($type === 'cascade') {
                    $q->whereNotNull('parent_odp_id');
                }
            })
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
        
        // Get OLTs for filter
        $olts = Olt::when($popId, fn($q) => $q->where('pop_id', $popId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        
        // Statistics
        $stats = [
            'total' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))->count(),
            'active' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'active')->count(),
            'maintenance' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'maintenance')->count(),
            'inactive' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))->where('status', 'inactive')->count(),
            'via_odc' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))->whereNotNull('odc_id')->count(),
            'direct_olt' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))->whereNotNull('olt_id')->whereNull('odc_id')->count(),
            'cascade' => Odp::when($popId, fn($q) => $q->where('pop_id', $popId))->whereNotNull('parent_odp_id')->count(),
        ];
        
        return view('admin.odps.index', compact('odps', 'popUsers', 'popId', 'odcs', 'olts', 'stats'));
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
        
        $olts = Olt::where('pop_id', $popId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        
        // Get existing ODPs for cascade selection
        $parentOdps = Odp::where('pop_id', $popId)
            ->where('status', 'active')
            ->whereNull('parent_odp_id') // Only first level ODPs can be parent
            ->orderBy('name')
            ->get();
        
        // Pre-select connection type
        $connectionType = $request->input('connection_type', 'odc');
        $selectedOdc = $request->input('odc_id');
        $selectedOlt = $request->input('olt_id');
        $nextCode = null;
        
        if ($selectedOdc) {
            $nextCode = Odp::generateCode($selectedOdc);
        }
        
        return view('admin.odps.create', compact('odcs', 'olts', 'parentOdps', 'nextCode', 'popId', 'selectedOdc', 'selectedOlt', 'connectionType'));
    }

    /**
     * Store new ODP
     */
    public function store(Request $request)
    {
        $popId = $this->getPopId($request);
        
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:odps,code',
            'connection_type' => 'required|in:odc,olt,cascade',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'total_ports' => 'required|integer|min:1|max:100',
            'status' => 'required|in:active,maintenance,inactive',
            'box_type' => 'nullable|string|max:100',
            'splitter_type' => 'nullable|string|max:100',
            'pole_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ];
        
        // Add conditional validation based on connection type
        $connectionType = $request->input('connection_type');
        
        if ($connectionType === 'odc') {
            $rules['odc_id'] = 'required|uuid|exists:odcs,id';
            $rules['odc_port'] = 'required|integer|min:1';
        } elseif ($connectionType === 'olt') {
            $rules['olt_id'] = 'required|uuid|exists:olts,id';
            $rules['olt_pon_port'] = 'required|integer|min:1';
            $rules['olt_slot'] = 'nullable|integer|min:0';
            $rules['splitter_level'] = 'nullable|integer|min:1|max:3';
        } elseif ($connectionType === 'cascade') {
            $rules['parent_odp_id'] = 'required|uuid|exists:odps,id';
            $rules['splitter_level'] = 'nullable|integer|min:2|max:3';
        }
        
        $validated = $request->validate($rules);
        
        // Validate based on connection type
        if ($connectionType === 'odc') {
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
        } elseif ($connectionType === 'olt') {
            $olt = Olt::findOrFail($validated['olt_id']);
            $validated['splitter_level'] = $validated['splitter_level'] ?? 1;
        } elseif ($connectionType === 'cascade') {
            $parentOdp = Odp::findOrFail($validated['parent_odp_id']);
            // Cascade inherits OLT from parent if parent has OLT
            if ($parentOdp->olt_id) {
                $validated['olt_id'] = $parentOdp->olt_id;
            }
            $validated['splitter_level'] = ($parentOdp->splitter_level ?? 1) + 1;
        }
        
        $validated['pop_id'] = $popId;
        $validated['created_by'] = auth()->id();
        $validated['used_ports'] = 0;
        
        // Remove connection_type from validated (not a DB field)
        unset($validated['connection_type']);
        
        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = Odp::generateCode(
                $validated['odc_id'] ?? null,
                $validated['olt_id'] ?? null,
                $popId
            );
        }
        
        try {
            DB::beginTransaction();
            
            $odp = Odp::create($validated);
            
            // Update ODC used_ports if via ODC
            if ($connectionType === 'odc' && isset($odc)) {
                $odc->increment('used_ports');
            }
            
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
        $odp->load(['pop', 'odc.olt', 'olt', 'parentOdp', 'childOdps', 'customers', 'creator']);
        
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
        
        $olts = Olt::where('pop_id', $odp->pop_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        
        // Get existing ODPs for cascade selection (exclude self and children)
        $parentOdps = Odp::where('pop_id', $odp->pop_id)
            ->where('status', 'active')
            ->where('id', '!=', $odp->id)
            ->whereNull('parent_odp_id') // Only first level ODPs can be parent
            ->orderBy('name')
            ->get();
        
        // Determine connection type
        $connectionType = 'odc';
        if ($odp->parent_odp_id) {
            $connectionType = 'cascade';
        } elseif ($odp->olt_id && !$odp->odc_id) {
            $connectionType = 'olt';
        }
        
        return view('admin.odps.edit', compact('odp', 'odcs', 'olts', 'parentOdps', 'connectionType'));
    }

    /**
     * Update ODP
     */
    public function update(Request $request, Odp $odp)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:odps,code,' . $odp->id,
            'connection_type' => 'required|in:odc,olt,cascade',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'total_ports' => 'required|integer|min:1|max:100',
            'status' => 'required|in:active,maintenance,inactive',
            'box_type' => 'nullable|string|max:100',
            'splitter_type' => 'nullable|string|max:100',
            'pole_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ];
        
        $connectionType = $request->input('connection_type');
        
        if ($connectionType === 'odc') {
            $rules['odc_id'] = 'required|uuid|exists:odcs,id';
            $rules['odc_port'] = 'required|integer|min:1';
        } elseif ($connectionType === 'olt') {
            $rules['olt_id'] = 'required|uuid|exists:olts,id';
            $rules['olt_pon_port'] = 'required|integer|min:1';
            $rules['olt_slot'] = 'nullable|integer|min:0';
            $rules['splitter_level'] = 'nullable|integer|min:1|max:3';
        } elseif ($connectionType === 'cascade') {
            $rules['parent_odp_id'] = 'required|uuid|exists:odps,id';
            $rules['splitter_level'] = 'nullable|integer|min:2|max:3';
        }
        
        $validated = $request->validate($rules);
        
        // Validate total_ports >= used_ports
        if ($validated['total_ports'] < $odp->used_ports) {
            return back()
                ->withInput()
                ->with('error', 'Total port tidak boleh kurang dari port yang sudah digunakan (' . $odp->used_ports . ')');
        }
        
        $odc = null;
        
        if ($connectionType === 'odc') {
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
            
            // Clear OLT and parent ODP fields
            $validated['olt_id'] = null;
            $validated['olt_pon_port'] = null;
            $validated['olt_slot'] = null;
            $validated['parent_odp_id'] = null;
            $validated['splitter_level'] = 1;
        } elseif ($connectionType === 'olt') {
            $validated['odc_id'] = null;
            $validated['odc_port'] = null;
            $validated['parent_odp_id'] = null;
            $validated['splitter_level'] = $validated['splitter_level'] ?? 1;
        } elseif ($connectionType === 'cascade') {
            $parentOdp = Odp::findOrFail($validated['parent_odp_id']);
            
            // Prevent circular reference
            if ($validated['parent_odp_id'] === $odp->id) {
                return back()
                    ->withInput()
                    ->with('error', 'ODP tidak boleh menjadi parent dari dirinya sendiri');
            }
            
            // Inherit OLT from parent if available
            if ($parentOdp->olt_id) {
                $validated['olt_id'] = $parentOdp->olt_id;
            }
            
            $validated['odc_id'] = null;
            $validated['odc_port'] = null;
            $validated['splitter_level'] = ($parentOdp->splitter_level ?? 1) + 1;
        }
        
        // Remove connection_type
        unset($validated['connection_type']);
        
        try {
            DB::beginTransaction();
            
            $oldOdcId = $odp->odc_id;
            $oldData = $odp->toArray();
            
            // Update ODC used_ports if ODC changed
            if ($connectionType === 'odc') {
                if ($oldOdcId && $oldOdcId != $validated['odc_id']) {
                    Odc::where('id', $oldOdcId)->decrement('used_ports');
                    $odc->increment('used_ports');
                } elseif (!$oldOdcId) {
                    $odc->increment('used_ports');
                }
            } elseif ($oldOdcId) {
                // Switching away from ODC
                Odc::where('id', $oldOdcId)->decrement('used_ports');
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
        
        // Check if has child ODPs (cascade)
        if ($odp->childOdps()->count() > 0) {
            return back()->with('error', 'Tidak dapat menghapus ODP yang masih memiliki ODP turunan');
        }
        
        try {
            DB::beginTransaction();
            
            // Decrease ODC used_ports if via ODC
            if ($odp->odc_id) {
                Odc::where('id', $odp->odc_id)->decrement('used_ports');
            }
            
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
     * Get ODPs by OLT (AJAX)
     */
    public function getByOlt(Request $request)
    {
        $oltId = $request->input('olt_id');
        
        $odps = Odp::where('olt_id', $oltId)
            ->whereNull('odc_id') // Direct OLT connection only
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function($odp) {
                return [
                    'id' => $odp->id,
                    'name' => $odp->name,
                    'code' => $odp->code,
                    'pon_port' => $odp->olt_pon_port,
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
        $oltId = $request->input('olt_id');
        $popId = $request->input('pop_id');
        
        $code = Odp::generateCode($odcId, $oltId, $popId);
        
        return response()->json(['code' => $code]);
    }
}
