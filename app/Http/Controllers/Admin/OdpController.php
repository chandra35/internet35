<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\OltPonPort;
use App\Models\User;
use App\Models\SplitterRatio;
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
        
        // Get existing ODPs for cascade selection - include power data
        $parentOdps = Odp::where('pop_id', $popId)
            ->where('status', 'active')
            ->whereNull('parent_odp_id') // Only first level ODPs can be parent
            ->orderBy('name')
            ->get()
            ->map(function($odp) {
                return [
                    'id' => $odp->id,
                    'code' => $odp->code,
                    'name' => $odp->name,
                    'splitter_level' => $odp->splitter_level ?? 1,
                    'cascade_output_power' => $odp->cascade_output_power,
                    'output_power' => $odp->output_power,
                    'splitter_ratio' => $odp->splitter_ratio,
                ];
            });
        
        // Pre-select connection type
        $connectionType = $request->input('connection_type', 'odc');
        $selectedOdc = $request->input('odc_id');
        $selectedOlt = $request->input('olt_id');
        $nextCode = null;
        
        if ($selectedOdc) {
            $nextCode = Odp::generateCode($selectedOdc);
        }
        
        // Get used PON ports per OLT (for protection against duplicate selection)
        $usedPonPorts = Odp::whereNotNull('olt_id')
            ->whereNotNull('olt_pon_port')
            ->whereNull('odc_id') // Only direct OLT connections
            ->get()
            ->groupBy('olt_id')
            ->map(function($odps) {
                return $odps->map(function($odp) {
                    return [
                        'port' => $odp->olt_pon_port,
                        'odp_name' => $odp->name,
                        'odp_code' => $odp->code,
                    ];
                })->keyBy('port');
            });
        
        // Get splitter options from database
        $equalSplitters = SplitterRatio::equal()->active()->sorted()->get();
        $unequalSplitters = SplitterRatio::unequal()->active()->sorted()->get();
        
        return view('admin.odps.create', compact('odcs', 'olts', 'parentOdps', 'nextCode', 'popId', 'selectedOdc', 'selectedOlt', 'connectionType', 'equalSplitters', 'unequalSplitters', 'usedPonPorts'));
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
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
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
            
            // Validate PON port is not already used by another ODP on the same OLT
            $oltId = $request->input('olt_id');
            $ponPort = $request->input('olt_pon_port');
            $existingOdp = Odp::where('olt_id', $oltId)
                ->where('olt_pon_port', $ponPort)
                ->whereNull('odc_id') // Only direct OLT connections
                ->first();
            
            if ($existingOdp) {
                return back()->withInput()->withErrors([
                    'olt_pon_port' => "PON Port {$ponPort} sudah digunakan oleh ODP: {$existingOdp->code} ({$existingOdp->name})"
                ]);
            }
        } elseif ($connectionType === 'cascade') {
            $rules['parent_odp_id'] = 'required|uuid|exists:odps,id';
            $rules['splitter_level'] = 'nullable|integer|min:2|max:3';
        }
        
        // Add optical power fields validation
        $rules['input_power'] = 'nullable|numeric|between:-50,20';
        $rules['fiber_distance'] = 'nullable|numeric|min:0|max:100';
        $rules['fiber_loss_per_km'] = 'nullable|numeric|min:0|max:2';
        $rules['splitter_ratio'] = 'nullable|string|max:30';
        $rules['is_power_manual'] = 'nullable|boolean';
        
        // Add splitter configuration fields
        $rules['splitter_config_type'] = 'nullable|in:equal,cascade';
        $rules['unequal_ratio'] = 'nullable|string|max:10';
        $rules['branch_splitter'] = 'nullable|string|max:10';
        $rules['fiber_loss'] = 'nullable|numeric|min:0|max:100';
        $rules['unequal_loss'] = 'nullable|numeric|min:0|max:30';
        $rules['branch_loss'] = 'nullable|numeric|min:0|max:30';
        $rules['total_loss'] = 'nullable|numeric|min:0|max:100';
        $rules['output_power'] = 'nullable|numeric|between:-60,20';
        $rules['cascade_output_power'] = 'nullable|numeric|between:-60,20';
        
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
        
        // Set default for is_power_manual if not provided
        $validated['is_power_manual'] = $validated['is_power_manual'] ?? false;
        
        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = Odp::generateCode(
                $validated['odc_id'] ?? null,
                $validated['olt_id'] ?? null,
                $popId
            );
        }
        
        // Use output_power and cascade_output_power from JavaScript if available
        // JavaScript calculates these values based on database splitter data
        if (!empty($validated['output_power']) || !empty($validated['cascade_output_power'])) {
            // Values already calculated by JavaScript, ensure they are set
            $validated['output_power'] = $validated['output_power'] ?? null;
            $validated['cascade_output_power'] = $validated['cascade_output_power'] ?? $validated['output_power'];
            
            // Calculate fiber loss for storage
            $fiberDistance = $validated['fiber_distance'] ?? 0;
            $fiberLossPerKm = $validated['fiber_loss_per_km'] ?? 0.35;
            $validated['fiber_loss'] = round($fiberDistance * $fiberLossPerKm, 2);
            $validated['fiber_loss_per_km'] = $fiberLossPerKm;
            
        } elseif (!empty($validated['input_power'])) {
            // Fallback: calculate if not provided by JavaScript
            $fiberDistance = $validated['fiber_distance'] ?? 0;
            $fiberLossPerKm = $validated['fiber_loss_per_km'] ?? 0.35;
            $splitterConfigType = $validated['splitter_config_type'] ?? 'equal';
            
            $fiberLoss = $fiberDistance * $fiberLossPerKm;
            $powerAfterFiber = $validated['input_power'] - $fiberLoss;
            
            if ($splitterConfigType === 'cascade' && !empty($validated['unequal_loss'])) {
                // Unequal splitter: unequal_loss = relay loss (small)
                $relayLoss = (float) ($validated['unequal_loss'] ?? 0);
                $validated['cascade_output_power'] = round($powerAfterFiber - $relayLoss, 2);
                // Use total_loss for output_power calculation
                $totalLoss = (float) ($validated['total_loss'] ?? $relayLoss);
                $validated['output_power'] = round($validated['input_power'] - $totalLoss, 2);
            } else {
                // Equal splitter
                $branchLoss = (float) ($validated['branch_loss'] ?? 10.5);
                $validated['output_power'] = round($powerAfterFiber - $branchLoss, 2);
                $validated['cascade_output_power'] = $validated['output_power'];
            }
            
            $validated['fiber_loss_per_km'] = $fiberLossPerKm;
            $validated['fiber_loss'] = round($fiberLoss, 2);
        }
        
        try {
            DB::beginTransaction();
            
            // Remove photos from validated to handle separately
            $photoFiles = $request->file('photos', []);
            unset($validated['photos']);
            
            $odp = Odp::create($validated);
            
            // Upload photos if provided
            if (!empty($photoFiles)) {
                $odp->addPhotos($photoFiles);
            }
            
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
        
        // Get used PON ports per OLT (exclude current ODP for edit)
        $usedPonPorts = Odp::whereNotNull('olt_id')
            ->whereNotNull('olt_pon_port')
            ->whereNull('odc_id') // Only direct OLT connections
            ->where('id', '!=', $odp->id) // Exclude current ODP being edited
            ->get()
            ->groupBy('olt_id')
            ->map(function($odps) {
                return $odps->map(function($o) {
                    return [
                        'port' => $o->olt_pon_port,
                        'odp_name' => $o->name,
                        'odp_code' => $o->code,
                    ];
                })->keyBy('port');
            });
        
        // Get splitter options from database
        $equalSplitters = SplitterRatio::equal()->active()->sorted()->get();
        $unequalSplitters = SplitterRatio::unequal()->active()->sorted()->get();
        
        return view('admin.odps.edit', compact('odp', 'odcs', 'olts', 'parentOdps', 'connectionType', 'equalSplitters', 'unequalSplitters', 'usedPonPorts'));
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
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'remove_photos' => 'nullable|array',
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
            
            // Validate PON port is not already used by another ODP on the same OLT (exclude current ODP)
            $oltId = $request->input('olt_id');
            $ponPort = $request->input('olt_pon_port');
            $existingOdp = Odp::where('olt_id', $oltId)
                ->where('olt_pon_port', $ponPort)
                ->whereNull('odc_id') // Only direct OLT connections
                ->where('id', '!=', $odp->id) // Exclude current ODP being edited
                ->first();
            
            if ($existingOdp) {
                return back()->withInput()->withErrors([
                    'olt_pon_port' => "PON Port {$ponPort} sudah digunakan oleh ODP: {$existingOdp->code} ({$existingOdp->name})"
                ]);
            }
        } elseif ($connectionType === 'cascade') {
            $rules['parent_odp_id'] = 'required|uuid|exists:odps,id';
            $rules['splitter_level'] = 'nullable|integer|min:2|max:3';
        }
        
        // Add optical power fields validation
        $rules['input_power'] = 'nullable|numeric|between:-50,20';
        $rules['fiber_distance'] = 'nullable|numeric|min:0|max:100';
        $rules['fiber_loss_per_km'] = 'nullable|numeric|min:0|max:2';
        $rules['splitter_ratio'] = 'nullable|string|max:30';
        $rules['is_power_manual'] = 'nullable|boolean';
        
        // Add splitter configuration fields
        $rules['splitter_config_type'] = 'nullable|in:equal,cascade';
        $rules['unequal_ratio'] = 'nullable|string|max:10';
        $rules['branch_splitter'] = 'nullable|string|max:10';
        $rules['fiber_loss'] = 'nullable|numeric|min:0|max:100';
        $rules['unequal_loss'] = 'nullable|numeric|min:0|max:30';
        $rules['branch_loss'] = 'nullable|numeric|min:0|max:30';
        $rules['total_loss'] = 'nullable|numeric|min:0|max:100';
        $rules['output_power'] = 'nullable|numeric|between:-60,20';
        $rules['cascade_output_power'] = 'nullable|numeric|between:-60,20';
        
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
        
        // Set default for is_power_manual if not provided
        $validated['is_power_manual'] = $validated['is_power_manual'] ?? false;
        
        // Use output_power and cascade_output_power from JavaScript if available
        // JavaScript calculates these values based on database splitter data
        if (!empty($validated['output_power']) || !empty($validated['cascade_output_power'])) {
            // Values already calculated by JavaScript, ensure they are set
            $validated['output_power'] = $validated['output_power'] ?? null;
            $validated['cascade_output_power'] = $validated['cascade_output_power'] ?? $validated['output_power'];
            
            // Calculate fiber loss for storage
            $fiberDistance = $validated['fiber_distance'] ?? 0;
            $fiberLossPerKm = $validated['fiber_loss_per_km'] ?? 0.35;
            $validated['fiber_loss'] = round($fiberDistance * $fiberLossPerKm, 2);
            $validated['fiber_loss_per_km'] = $fiberLossPerKm;
            
        } elseif (!empty($validated['input_power'])) {
            // Fallback: calculate if not provided by JavaScript
            $fiberDistance = $validated['fiber_distance'] ?? 0;
            $fiberLossPerKm = $validated['fiber_loss_per_km'] ?? 0.35;
            $splitterConfigType = $validated['splitter_config_type'] ?? 'equal';
            
            $fiberLoss = $fiberDistance * $fiberLossPerKm;
            $powerAfterFiber = $validated['input_power'] - $fiberLoss;
            
            if ($splitterConfigType === 'cascade' && !empty($validated['unequal_loss'])) {
                // Unequal splitter: unequal_loss = relay loss (small)
                $relayLoss = (float) ($validated['unequal_loss'] ?? 0);
                $validated['cascade_output_power'] = round($powerAfterFiber - $relayLoss, 2);
                // Use total_loss for output_power calculation
                $totalLoss = (float) ($validated['total_loss'] ?? $relayLoss);
                $validated['output_power'] = round($validated['input_power'] - $totalLoss, 2);
            } else {
                // Equal splitter
                $branchLoss = (float) ($validated['branch_loss'] ?? 10.5);
                $validated['output_power'] = round($powerAfterFiber - $branchLoss, 2);
                $validated['cascade_output_power'] = $validated['output_power'];
            }
            
            $validated['fiber_loss_per_km'] = $fiberLossPerKm;
            $validated['fiber_loss'] = round($fiberLoss, 2);
        }
        
        try {
            DB::beginTransaction();
            
            // Handle photo removal
            if ($request->has('remove_photos')) {
                foreach ($request->remove_photos as $filename) {
                    $odp->removePhoto($filename);
                }
            }
            
            // Handle new photos
            $photoFiles = $request->file('photos', []);
            if (!empty($photoFiles)) {
                $odp->addPhotos($photoFiles);
            }
            
            // Remove photo fields from validated
            unset($validated['photos'], $validated['remove_photos']);
            
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
     * Get OLT PON ports with TX power (AJAX)
     */
    public function getOltPonPorts(Request $request)
    {
        $oltId = $request->input('olt_id');
        
        if (!$oltId) {
            return response()->json(['success' => false, 'message' => 'OLT ID diperlukan']);
        }
        
        $olt = Olt::find($oltId);
        if (!$olt) {
            return response()->json(['success' => false, 'message' => 'OLT tidak ditemukan']);
        }
        
        // Get PON ports from database
        $ponPorts = OltPonPort::where('olt_id', $oltId)
            ->orderBy('slot')
            ->orderBy('port')
            ->get(['slot', 'port', 'name', 'tx_power', 'status']);
        
        // Default TX power if no data
        $defaultTxPower = 4.0;
        
        // If no PON ports in database, create default list based on OLT config
        if ($ponPorts->isEmpty()) {
            $totalPorts = $olt->total_pon_ports ?? 8;
            $ponPorts = collect();
            for ($i = 1; $i <= $totalPorts; $i++) {
                $ponPorts->push([
                    'slot' => 0,
                    'port' => $i,
                    'name' => "PON {$i}",
                    'tx_power' => null,
                    'status' => 'unknown'
                ]);
            }
        }
        
        return response()->json([
            'success' => true,
            'olt' => [
                'id' => $olt->id,
                'name' => $olt->name,
                'brand' => $olt->brand,
                'default_tx_power' => $defaultTxPower,
            ],
            'pon_ports' => $ponPorts->map(function($port) use ($defaultTxPower) {
                return [
                    'slot' => $port['slot'] ?? $port->slot ?? 0,
                    'port' => $port['port'] ?? $port->port ?? 1,
                    'name' => $port['name'] ?? $port->name ?? "PON " . ($port['port'] ?? $port->port ?? 1),
                    'tx_power' => $port['tx_power'] ?? $port->tx_power ?? null,
                    'tx_power_display' => ($port['tx_power'] ?? $port->tx_power ?? null) !== null 
                        ? ($port['tx_power'] ?? $port->tx_power) . ' dBm' 
                        : 'Default ' . $defaultTxPower . ' dBm',
                    'status' => $port['status'] ?? $port->status ?? 'unknown',
                    'has_data' => ($port['tx_power'] ?? $port->tx_power ?? null) !== null,
                ];
            }),
        ]);
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

    /**
     * Get source power for optical calculation (AJAX)
     */
    public function getSourcePower(Request $request)
    {
        $connectionType = $request->input('connection_type'); // olt, odc, odp
        $oltId = $request->input('olt_id');
        $odcId = $request->input('odc_id');
        $parentOdpId = $request->input('parent_odp_id');
        $ponPort = $request->input('pon_port');
        
        $result = [
            'success' => false,
            'source_power' => null,
            'source_type' => null,
            'source_name' => null,
            'is_auto' => false,
            'message' => ''
        ];
        
        try {
            if ($connectionType === 'olt' && $oltId) {
                // Get TX power from OLT PON port
                $olt = \App\Models\Olt::find($oltId);
                if ($olt) {
                    $result['source_name'] = $olt->name;
                    $result['source_type'] = 'OLT PON ' . ($ponPort ?: 'Port');
                    
                    // Try to read TX power from OLT via SNMP
                    try {
                        $txPower = $this->getOltPonTxPower($olt, $ponPort);
                        if ($txPower !== null) {
                            $result['success'] = true;
                            $result['source_power'] = $txPower;
                            $result['is_auto'] = true;
                            $result['message'] = "TX Power dari {$olt->name} PON {$ponPort}: {$txPower} dBm";
                        } else {
                            // Use default TX power for PON SFP (typically +3 to +7 dBm)
                            $result['success'] = true;
                            $result['source_power'] = 4.0; // Default TX power
                            $result['is_auto'] = false;
                            $result['message'] = "Menggunakan TX Power default +4 dBm. Silakan refresh OLT untuk mendapatkan nilai aktual.";
                        }
                    } catch (\Exception $e) {
                        $result['success'] = true;
                        $result['source_power'] = 4.0; // Default TX power
                        $result['is_auto'] = false;
                        $result['message'] = "Menggunakan TX Power default +4 dBm. Error: " . $e->getMessage();
                    }
                }
            } elseif ($connectionType === 'odc' && $odcId) {
                // Get output power from ODC
                $odc = \App\Models\Odc::with('olt')->find($odcId);
                if ($odc) {
                    $result['source_name'] = $odc->name;
                    $result['source_type'] = 'ODC';
                    
                    // ODC should have output power calculated
                    if ($odc->output_power !== null) {
                        $result['success'] = true;
                        $result['source_power'] = $odc->output_power;
                        $result['is_auto'] = true;
                        $result['message'] = "Output Power dari ODC {$odc->name}: {$odc->output_power} dBm";
                    } else {
                        // Calculate from OLT if ODC doesn't have power data
                        $defaultOdcOutput = 0.0; // Typical ODC output after splitter
                        $result['success'] = true;
                        $result['source_power'] = $defaultOdcOutput;
                        $result['is_auto'] = false;
                        $result['message'] = "ODC belum memiliki data power. Gunakan nilai default atau input manual.";
                    }
                }
            } elseif ($connectionType === 'odp' && $parentOdpId) {
                // Get cascade output power from parent ODP
                $parentOdp = Odp::find($parentOdpId);
                if ($parentOdp) {
                    $result['source_name'] = $parentOdp->name;
                    $result['source_type'] = 'ODP (Cascade)';
                    
                    if ($parentOdp->cascade_output_power !== null) {
                        $result['success'] = true;
                        $result['source_power'] = $parentOdp->cascade_output_power;
                        $result['is_auto'] = true;
                        $result['message'] = "Cascade Output dari ODP {$parentOdp->name}: {$parentOdp->cascade_output_power} dBm";
                    } elseif ($parentOdp->output_power !== null) {
                        $result['success'] = true;
                        $result['source_power'] = $parentOdp->output_power;
                        $result['is_auto'] = true;
                        $result['message'] = "Output Power dari ODP {$parentOdp->name}: {$parentOdp->output_power} dBm";
                    } else {
                        $result['success'] = true;
                        $result['source_power'] = -10.0; // Typical ODP output
                        $result['is_auto'] = false;
                        $result['message'] = "Parent ODP belum memiliki data power. Gunakan input manual.";
                    }
                }
            } else {
                $result['message'] = 'Parameter tidak lengkap';
            }
        } catch (\Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }
        
        return response()->json($result);
    }

    /**
     * Get OLT PON TX Power via SNMP
     */
    private function getOltPonTxPower($olt, $ponPort)
    {
        // First, try to get TX power from database (cached from last sync)
        try {
            // Parse PON port (e.g., "1/1/1" -> slot/pon or just "1" -> slot 0, pon 1)
            $portParts = explode('/', $ponPort);
            $slot = 0;
            $port = 1;
            
            if (count($portParts) >= 3) {
                // Format: slot/subslot/port (e.g., "0/1/1")
                $slot = (int)$portParts[1]; // Use subslot as slot
                $port = (int)$portParts[2];
            } elseif (count($portParts) == 2) {
                // Format: slot/port (e.g., "1/1")
                $slot = (int)$portParts[0];
                $port = (int)$portParts[1];
            } else {
                // Just port number
                $port = (int)$portParts[0];
            }
            
            // Query database for cached TX power
            $ponPortRecord = \App\Models\OltPonPort::where('olt_id', $olt->id)
                ->where(function($q) use ($slot, $port) {
                    // Try exact match first
                    $q->where('slot', $slot)->where('port', $port);
                })
                ->orWhere(function($q) use ($olt, $port) {
                    // Fallback: slot 0 with port number
                    $q->where('olt_id', $olt->id)
                      ->where('slot', 0)
                      ->where('port', $port);
                })
                ->first();
            
            if ($ponPortRecord && $ponPortRecord->tx_power !== null) {
                \Log::info("Got OLT TX power from database: {$ponPortRecord->tx_power} dBm for OLT {$olt->name} PON {$ponPort}");
                return $ponPortRecord->tx_power;
            }
            
        } catch (\Exception $e) {
            \Log::warning("Failed to get OLT TX power from database: " . $e->getMessage());
        }
        
        // Fallback: Try live SNMP query
        if (!$olt->ip_address || !$olt->snmp_community) {
            return null;
        }
        
        try {
            // Parse PON port (e.g., "1/1/1" -> slot/pon)
            $portParts = explode('/', $ponPort);
            
            // Different OLT brands have different OIDs for TX power
            $brand = strtolower($olt->brand ?? '');
            
            if (strpos($brand, 'hioso') !== false || strpos($brand, 'vsol') !== false) {
                // HIOSO/VSOL OLT OID for PON TX power
                // OID: 1.3.6.1.4.1.37970.2.1.2.8.1.4 (PON optical TX power)
                $baseOid = '1.3.6.1.4.1.37970.2.1.2.8.1.4';
            } elseif (strpos($brand, 'zte') !== false) {
                // ZTE OLT 
                $baseOid = '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.2';
            } elseif (strpos($brand, 'huawei') !== false) {
                // Huawei OLT
                $baseOid = '1.3.6.1.4.1.2011.6.128.1.1.2.23.1.4';
            } else {
                // Generic attempt
                return null;
            }
            
            // Build full OID with port index
            if (count($portParts) >= 2) {
                $ponIndex = (int)$portParts[count($portParts) - 1];
                $slotIndex = count($portParts) >= 3 ? (int)$portParts[0] : 1;
                $oidIndex = ($slotIndex * 100) + $ponIndex;
            } else {
                $oidIndex = (int)($portParts[0] ?? 1);
            }
            
            $fullOid = $baseOid . '.' . $oidIndex;
            
            // SNMP Get
            snmp_set_quick_print(true);
            snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
            
            $result = @snmpget(
                $olt->ip_address,
                $olt->snmp_community,
                $fullOid,
                1000000, // 1 second timeout
                1 // 1 retry
            );
            
            if ($result !== false) {
                // Convert to dBm (many OLTs report in 0.01 dBm or 0.001 dBm)
                $value = floatval($result);
                if (abs($value) > 100) {
                    $value = $value / 100; // Convert from 0.01 dBm
                }
                if (abs($value) > 50) {
                    $value = $value / 10; // Additional conversion if still too large
                }
                return round($value, 2);
            }
        } catch (\Exception $e) {
            \Log::warning("Failed to get OLT TX power via SNMP: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Calculate optical power (AJAX)
     */
    public function calculateOpticalPower(Request $request)
    {
        $inputPower = floatval($request->input('input_power', 0));
        $fiberDistance = floatval($request->input('fiber_distance', 0));
        $fiberLossPerKm = floatval($request->input('fiber_loss_per_km', 0.35));
        $splitterRatio = $request->input('splitter_ratio', '1:8');
        
        // Calculate fiber loss
        $fiberLoss = $fiberDistance * $fiberLossPerKm;
        $powerAfterFiber = $inputPower - $fiberLoss;
        
        // Get splitter loss data
        $splitterLossData = Odp::getSplitterLoss($splitterRatio);
        
        // Determine if unequal splitter
        $isUnequal = strpos($splitterRatio, ':') !== false && !str_starts_with($splitterRatio, '1:');
        $outputLoss = $isUnequal ? $splitterLossData['branch'] : $splitterLossData['main'];
        $cascadeLoss = $splitterLossData['main'];
        
        // Calculate output power
        $outputPower = $powerAfterFiber - $outputLoss;
        $cascadeOutput = $powerAfterFiber - $cascadeLoss;
        
        // Check thresholds
        $status = 'good';
        $statusMessage = 'Power level optimal';
        
        if ($outputPower < -30) {
            $status = 'critical';
            $statusMessage = 'CRITICAL: Power terlalu rendah! ONU tidak akan sync.';
        } elseif ($outputPower < -28) {
            $status = 'warning';
            $statusMessage = 'WARNING: Power mendekati batas minimum ONU.';
        } elseif ($outputPower < -25) {
            $status = 'caution';
            $statusMessage = 'Power cukup, tapi ada margin terbatas.';
        }
        
        return response()->json([
            'success' => true,
            'calculation' => [
                'input_power' => round($inputPower, 2),
                'fiber_distance' => $fiberDistance,
                'fiber_loss_per_km' => $fiberLossPerKm,
                'fiber_loss' => round($fiberLoss, 2),
                'power_after_fiber' => round($powerAfterFiber, 2),
                'splitter_ratio' => $splitterRatio,
                'splitter_loss' => round($outputLoss, 2),
                'output_power' => round($outputPower, 2),
                'cascade_output' => round($cascadeOutput, 2),
            ],
            'status' => $status,
            'status_message' => $statusMessage,
            'thresholds' => [
                'good' => '-25 dBm or higher',
                'caution' => '-25 to -28 dBm',
                'warning' => '-28 to -30 dBm',
                'critical' => 'Below -30 dBm'
            ]
        ]);
    }
}
