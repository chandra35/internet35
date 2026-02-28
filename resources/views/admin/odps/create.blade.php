@extends('layouts.admin')

@section('title', 'Tambah ODP')

@section('page-title', 'Tambah ODP Baru')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.odps.index', ['pop_id' => $popId]) }}">ODP</a></li>
    <li class="breadcrumb-item active">Tambah</li>
@endsection

@push('css')
<style>
    #map { height: 400px; border-radius: 5px; }
    .connection-type-card { 
        cursor: pointer !important; 
        transition: all 0.3s ease !important; 
        border: 3px solid #dee2e6 !important; 
        user-select: none;
        position: relative;
        overflow: visible !important;
        background: #fff !important;
    }
    .connection-type-card:hover { 
        transform: translateY(-5px) !important; 
        box-shadow: 0 8px 20px rgba(0,0,0,0.2) !important; 
        border-color: #6c757d !important; 
    }
    .connection-type-card.active { 
        border: 4px solid #28a745 !important; 
        background: #d4edda !important;
        box-shadow: 0 0 0 5px rgba(40,167,69,0.4), 0 10px 25px rgba(40,167,69,0.3) !important;
        transform: translateY(-3px) scale(1.02) !important;
    }
    .connection-type-card.active::after {
        content: '✓ DIPILIH';
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: #28a745;
        color: white;
        padding: 3px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        z-index: 100;
        white-space: nowrap;
    }
    .connection-type-card.active .card-body {
        background: transparent !important;
    }
    .connection-type-card.active h6 {
        color: #155724 !important;
        font-weight: bold !important;
    }
    .connection-type-card.active i {
        color: #28a745 !important;
    }
    .connection-type-card .badge { font-size: 0.7rem; }
    .connection-type-card i { transition: transform 0.3s; }
    .connection-type-card:hover i { transform: scale(1.2); }
    .connection-type-card.active i { transform: scale(1.3) !important; }
    .connection-type-card .card-body { pointer-events: none; }
    .custom-odp-marker { background: transparent; border: none; }
    .leaflet-control-layers { border-radius: 8px; }
    .leaflet-control-layers-toggle { width: 36px; height: 36px; }
    
    /* Cascade Splitter Config Styles */
    #cascade-splitter-config {
        border: 2px solid #ffc107 !important;
        background: linear-gradient(to bottom, #fff9e6, #ffffff) !important;
    }
    #cascade-calc-result .card {
        border: 1px solid #17a2b8;
        background: linear-gradient(to bottom, #e8f7fa, #ffffff);
    }
    #cascade-recommendation {
        border-left: 4px solid;
    }
</style>
@endpush

@section('content')
<script>
function setConnectionType(type) {
    // Set hidden input value
    document.getElementById('connection_type').value = type;
    
    // Get all cards
    var cards = ['odc', 'olt', 'cascade'];
    
    // Reset all cards - remove active class and inline styles
    cards.forEach(function(t) {
        var card = document.getElementById('card-' + t);
        card.classList.remove('active');
        card.style.border = '';
        card.style.background = '';
        card.style.boxShadow = '';
        card.style.transform = '';
        // Remove any existing selected label
        var existingLabels = card.querySelectorAll('.selected-label');
        existingLabels.forEach(function(label) {
            label.remove();
        });
    });
    
    // Apply active class to selected card
    var activeCard = document.getElementById('card-' + type);
    activeCard.classList.add('active');
    
    // Hide all connection fields
    document.getElementById('odc-fields').style.display = 'none';
    document.getElementById('olt-fields').style.display = 'none';
    document.getElementById('cascade-fields').style.display = 'none';
    
    // Show selected fields
    document.getElementById(type + '-fields').style.display = 'block';
    
    // Show/hide optical power calculator based on connection type
    // For cascade, we use the inline calculator instead
    var powerCalcCard = document.querySelector('.card-warning');
    if (powerCalcCard && type === 'cascade') {
        // Hide the main optical power card for cascade - we use inline one
        powerCalcCard.style.display = 'none';
    } else if (powerCalcCard) {
        powerCalcCard.style.display = 'block';
    }
    
    // Generate new code
    if (typeof generateCode === 'function') {
        generateCode();
    }
}
</script>
<form action="{{ route('admin.odps.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="pop_id" value="{{ $popId }}">
    <input type="hidden" name="connection_type" id="connection_type" value="{{ old('connection_type', $connectionType) }}">
    
    <div class="row">
        <div class="col-md-8">
            <!-- Connection Type Selection -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Jenis Koneksi</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <div class="card connection-type-card {{ old('connection_type', $connectionType) == 'odc' ? 'active' : '' }}" 
                                 id="card-odc" onclick="setConnectionType('odc')">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-box fa-2x text-primary mb-2"></i>
                                    <h6 class="mb-1">Via ODC</h6>
                                    <small class="text-muted">OLT → ODC → ODP</small>
                                    <br><span class="badge badge-primary">Standard</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="card connection-type-card {{ old('connection_type', $connectionType) == 'olt' ? 'active' : '' }}" 
                                 id="card-olt" onclick="setConnectionType('olt')">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-server fa-2x text-success mb-2"></i>
                                    <h6 class="mb-1">Direct OLT</h6>
                                    <small class="text-muted">OLT → ODP</small>
                                    <br><span class="badge badge-success">Tanpa ODC</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="card connection-type-card {{ old('connection_type', $connectionType) == 'cascade' ? 'active' : '' }}" 
                                 id="card-cascade" onclick="setConnectionType('cascade')">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-sitemap fa-2x text-warning mb-2"></i>
                                    <h6 class="mb-1">Cascade/Relay</h6>
                                    <small class="text-muted">ODP → ODP</small>
                                    <br><span class="badge badge-warning">Estafet Splitter</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Info -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi Dasar</h3>
                </div>
                <div class="card-body">
                    <!-- ODC Selection (shown when connection_type = odc) -->
                    <div id="odc-fields" class="connection-fields" style="{{ old('connection_type', $connectionType) != 'odc' ? 'display:none;' : '' }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="odc_id">ODC <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('odc_id') is-invalid @enderror" 
                                            id="odc_id" name="odc_id" style="width: 100%;">
                                        <option value="">-- Pilih ODC --</option>
                                        @foreach($odcs as $odc)
                                            <option value="{{ $odc->id }}" 
                                                    data-total-ports="{{ $odc->total_ports }}"
                                                    data-used-ports="{{ $odc->used_ports }}"
                                                    {{ old('odc_id', $selectedOdc) == $odc->id ? 'selected' : '' }}>
                                                {{ $odc->code }} - {{ $odc->name }} ({{ $odc->available_ports }} port tersedia)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('odc_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="odc_port">Port ODC <span class="text-danger">*</span></label>
                                    <select class="form-control @error('odc_port') is-invalid @enderror" 
                                            id="odc_port" name="odc_port">
                                        <option value="">-- Pilih ODC Dulu --</option>
                                    </select>
                                    @error('odc_port')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        {{-- ODC Splitter Configuration --}}
                        @include('admin.odps.partials.splitter-config', [
                            'prefix' => 'odc',
                            'inputPowerDefault' => -3,
                            'inputPowerLabel' => 'Power dari ODC (dBm)',
                            'equalSplitters' => $equalSplitters,
                            'unequalSplitters' => $unequalSplitters,
                        ])
                    </div>

                    <!-- OLT Selection (shown when connection_type = olt) -->
                    <div id="olt-fields" class="connection-fields" style="{{ old('connection_type', $connectionType) != 'olt' ? 'display:none;' : '' }}">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="olt_id">OLT <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('olt_id') is-invalid @enderror" 
                                            id="olt_id" name="olt_id" style="width: 100%;">
                                        <option value="">-- Pilih OLT --</option>
                                        @foreach($olts as $olt)
                                            <option value="{{ $olt->id }}" 
                                                    data-pon-ports="{{ $olt->pon_ports }}"
                                                    {{ old('olt_id', $selectedOlt) == $olt->id ? 'selected' : '' }}>
                                                {{ $olt->name }} ({{ $olt->pon_ports }} PON)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('olt_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="olt_pon_port">PON Port <span class="text-danger">*</span></label>
                                    <select class="form-control @error('olt_pon_port') is-invalid @enderror" 
                                            id="olt_pon_port" name="olt_pon_port">
                                        <option value="">-- Pilih OLT Dulu --</option>
                                    </select>
                                    <small class="text-muted" id="pon_port_info"></small>
                                    @error('olt_pon_port')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="olt_slot">Slot (Opsional)</label>
                                    <input type="number" class="form-control @error('olt_slot') is-invalid @enderror" 
                                           id="olt_slot" name="olt_slot" value="{{ old('olt_slot') }}" min="0">
                                    @error('olt_slot')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        {{-- OLT Splitter Configuration --}}
                        @include('admin.odps.partials.splitter-config', [
                            'prefix' => 'olt',
                            'inputPowerDefault' => 4,
                            'inputPowerLabel' => 'TX Power OLT (dBm)',
                            'equalSplitters' => $equalSplitters,
                            'unequalSplitters' => $unequalSplitters,
                        ])
                    </div>

                    <!-- Parent ODP Selection (shown when connection_type = cascade) -->
                    <div id="cascade-fields" class="connection-fields" style="{{ old('connection_type', $connectionType) != 'cascade' ? 'display:none;' : '' }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="parent_odp_id">Parent ODP <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('parent_odp_id') is-invalid @enderror" 
                                            id="parent_odp_id" name="parent_odp_id" style="width: 100%;">
                                        <option value="">-- Pilih ODP Parent --</option>
                                        @foreach($parentOdps as $podp)
                                            <option value="{{ $podp['id'] }}" 
                                                    data-cascade-power="{{ $podp['cascade_output_power'] ?? '' }}"
                                                    data-output-power="{{ $podp['output_power'] ?? '' }}"
                                                    data-splitter-level="{{ $podp['splitter_level'] ?? 1 }}"
                                                    data-splitter-ratio="{{ $podp['splitter_ratio'] ?? '' }}"
                                                    {{ old('parent_odp_id') == $podp['id'] ? 'selected' : '' }}>
                                                {{ $podp['code'] }} - {{ $podp['name'] }} 
                                                @if($podp['cascade_output_power'])
                                                    ({{ $podp['cascade_output_power'] }} dBm)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_odp_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Sisa Power dari Parent</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control font-weight-bold" id="parent_power_display" readonly value="-- dBm">
                                        <div class="input-group-append">
                                            <span class="input-group-text" id="parent_power_status">⏳</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Splitter Level</label>
                                    <input type="text" class="form-control" id="splitter_level_display" readonly value="Auto">
                                </div>
                            </div>
                        </div>
                        
                        {{-- Cascade Splitter Configuration (only shown when parent selected) --}}
                        <div id="cascade-splitter-wrapper" style="display:none;">
                            @include('admin.odps.partials.splitter-config', [
                                'prefix' => 'cascade',
                                'inputPowerDefault' => 0,
                                'inputPowerLabel' => 'Sisa Power dari Parent (dBm)',
                                'equalSplitters' => $equalSplitters,
                                'unequalSplitters' => $unequalSplitters,
                            ])
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code">Kode ODP</label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                       id="code" name="code" value="{{ old('code', $nextCode) }}" readonly>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Kode otomatis, double-click untuk ubah manual</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nama ODP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select class="form-control @error('status') is-invalid @enderror" 
                                        id="status" name="status" required>
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pole_number">Nomor Tiang</label>
                                <input type="text" class="form-control @error('pole_number') is-invalid @enderror" 
                                       id="pole_number" name="pole_number" value="{{ old('pole_number') }}" 
                                       placeholder="Contoh: T-001">
                                @error('pole_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Alamat</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  id="address" name="address" rows="2">{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Lokasi</h3>
                </div>
                <div class="card-body">
                    <div id="map"></div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="latitude">Latitude</label>
                                <input type="text" class="form-control @error('latitude') is-invalid @enderror" 
                                       id="latitude" name="latitude" value="{{ old('latitude') }}" step="any">
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="longitude">Longitude</label>
                                <input type="text" class="form-control @error('longitude') is-invalid @enderror" 
                                       id="longitude" name="longitude" value="{{ old('longitude') }}" step="any">
                                @error('longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">Klik pada peta untuk menentukan lokasi atau masukkan koordinat manual</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Port Configuration -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plug mr-2"></i>Konfigurasi Port</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="total_ports">Total Port <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('total_ports') is-invalid @enderror" 
                               id="total_ports" name="total_ports" value="{{ old('total_ports', 8) }}" min="1" max="100" required>
                        @error('total_ports')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Physical Specifications -->
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cogs mr-2"></i>Spesifikasi</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="box_type">Tipe Box</label>
                        <input type="text" class="form-control @error('box_type') is-invalid @enderror" 
                               id="box_type" name="box_type" value="{{ old('box_type') }}" 
                               placeholder="Contoh: ODP 8 Core">
                        @error('box_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="splitter_type">Tipe Splitter</label>
                        <input type="text" class="form-control @error('splitter_type') is-invalid @enderror" 
                               id="splitter_type" name="splitter_type" value="{{ old('splitter_type') }}"
                               placeholder="Contoh: 1:8">
                        @error('splitter_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Hidden fields for optical power data --}}
            <input type="hidden" name="input_power" id="input_power" value="{{ old('input_power', 4) }}">
            <input type="hidden" name="fiber_distance" id="fiber_distance" value="{{ old('fiber_distance', 0) }}">
            <input type="hidden" name="fiber_loss_per_km" id="fiber_loss_per_km" value="{{ old('fiber_loss_per_km', 0.35) }}">
            <input type="hidden" name="splitter_ratio" id="splitter_ratio" value="{{ old('splitter_ratio', '1:8') }}">
            <input type="hidden" name="output_power" id="output_power" value="{{ old('output_power') }}">
            <input type="hidden" name="cascade_output_power" id="cascade_output_power" value="{{ old('cascade_output_power') }}">
            <input type="hidden" name="splitter_config_type" id="splitter_config_type" value="{{ old('splitter_config_type', 'equal') }}">
            <input type="hidden" name="unequal_ratio" id="unequal_ratio" value="{{ old('unequal_ratio') }}">
            <input type="hidden" name="branch_splitter" id="branch_splitter" value="{{ old('branch_splitter') }}">
            <input type="hidden" name="fiber_loss" id="fiber_loss" value="{{ old('fiber_loss') }}">
            <input type="hidden" name="unequal_loss" id="unequal_loss" value="{{ old('unequal_loss') }}">
            <input type="hidden" name="branch_loss" id="branch_loss" value="{{ old('branch_loss') }}">
            <input type="hidden" name="total_loss" id="total_loss" value="{{ old('total_loss') }}">

            <!-- Notes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Catatan</h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" name="notes" rows="4" placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Photos -->
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-camera mr-2"></i>Foto Dokumentasi</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Upload Foto <small class="text-muted">(Maks. 10 foto, masing-masing maks. 5MB)</small></label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input @error('photos.*') is-invalid @enderror" 
                                       id="photos" name="photos[]" accept="image/*" multiple>
                                <label class="custom-file-label" for="photos">Pilih foto...</label>
                            </div>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info" id="btn-camera" title="Ambil foto dari kamera">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                        </div>
                        @error('photos.*')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        <small class="form-text text-muted">
                            <i class="fas fa-mobile-alt mr-1"></i> Di perangkat mobile, tombol kamera akan membuka kamera langsung.
                        </small>
                    </div>
                    <div id="photo-preview" class="d-flex flex-wrap mt-2"></div>
                </div>
            </div>

            <!-- Submit -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i> Simpan ODP
                    </button>
                    <a href="{{ route('admin.odps.index', ['pop_id' => $popId]) }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('js')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/splitter-calculator.js') }}"></script>
<script>
function generateCode() {
    var type = document.getElementById('connection_type').value;
    var params = 'pop_id={{ $popId }}';
    
    if (type === 'odc') {
        var odcSelect = document.getElementById('odc_id');
        if (odcSelect && odcSelect.value) {
            params += '&odc_id=' + odcSelect.value;
        }
    } else if (type === 'olt') {
        var oltSelect = document.getElementById('olt_id');
        if (oltSelect && oltSelect.value) {
            params += '&olt_id=' + oltSelect.value;
        }
    }
    
    fetch('{{ route("admin.odps.generate-code") }}?' + params)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            document.getElementById('code').value = data.code;
        });
}
</script>
<script>
$(function() {
    // Initialize map
    var defaultLat = {{ old('latitude', -7.9666) }};
    var defaultLng = {{ old('longitude', 110.6283) }};
    
    var map = L.map('map').setView([defaultLat, defaultLng], 16);
    
    // Layer Satellite dari Google
    const googleSat = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '© Google Satellite'
    });
    
    // Layer Hybrid (Satellite + Labels)
    const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '© Google Hybrid'
    });
    
    // Layer Street dari Google
    const googleStreet = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '© Google Maps'
    });
    
    // Layer OpenStreetMap
    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    });
    
    // Default to Hybrid view
    googleHybrid.addTo(map);
    
    // Layer control
    const baseMaps = {
        "🛰️ Satelit + Label": googleHybrid,
        "🛰️ Satelit": googleSat,
        "🗺️ Street": googleStreet,
        "🗺️ OpenStreetMap": osm
    };
    
    L.control.layers(baseMaps, null, { position: 'topright' }).addTo(map);
    
    // Add scale control
    L.control.scale({ imperial: false }).addTo(map);
    
    let marker = null;
    
    // Custom icon for ODP
    const odpIcon = L.divIcon({
        className: 'custom-odp-marker',
        html: '<div style="background: #007bff; color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fas fa-box"></i> ODP</div>',
        iconSize: [60, 30],
        iconAnchor: [30, 30]
    });
    
    // Add marker if coordinates exist
    if ($('#latitude').val() && $('#longitude').val()) {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());
        marker = L.marker([lat, lng], { icon: odpIcon, draggable: true }).addTo(map);
        map.setView([lat, lng], 18);
        
        // Enable dragging marker
        marker.on('dragend', function(e) {
            const latlng = e.target.getLatLng();
            $('#latitude').val(latlng.lat.toFixed(8));
            $('#longitude').val(latlng.lng.toFixed(8));
        });
    }
    
    // Click on map to set location
    map.on('click', function(e) {
        const lat = e.latlng.lat.toFixed(8);
        const lng = e.latlng.lng.toFixed(8);
        
        $('#latitude').val(lat);
        $('#longitude').val(lng);
        
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng, { icon: odpIcon, draggable: true }).addTo(map);
            marker.on('dragend', function(e) {
                const latlng = e.target.getLatLng();
                $('#latitude').val(latlng.lat.toFixed(8));
                $('#longitude').val(latlng.lng.toFixed(8));
            });
        }
    });
    
    // Update marker when coordinates change manually
    $('#latitude, #longitude').on('change', function() {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());
        
        if (!isNaN(lat) && !isNaN(lng)) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { icon: odpIcon, draggable: true }).addTo(map);
                marker.on('dragend', function(e) {
                    const latlng = e.target.getLatLng();
                    $('#latitude').val(latlng.lat.toFixed(8));
                    $('#longitude').val(latlng.lng.toFixed(8));
                });
            }
            map.setView([lat, lng], 18);
        }
    });
    
    // Geolocation button
    if (navigator.geolocation) {
        const locateBtn = L.control({ position: 'topleft' });
        locateBtn.onAdd = function() {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            div.innerHTML = '<a href="#" title="Lokasi Saya" style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;background:white;font-size:16px;"><i class="fas fa-crosshairs"></i></a>';
            div.onclick = function(e) {
                e.preventDefault();
                const btn = div.querySelector('a');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                navigator.geolocation.getCurrentPosition(function(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    const accuracy = pos.coords.accuracy;
                    map.setView([lat, lng], 18);
                    btn.innerHTML = '<i class="fas fa-crosshairs"></i>';
                    toastr.success('Lokasi ditemukan (akurasi: ' + Math.round(accuracy) + 'm)');
                    
                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = L.marker([lat, lng], { icon: odpIcon, draggable: true }).addTo(map);
                        marker.on('dragend', function(e) {
                            const latlng = e.target.getLatLng();
                            $('#latitude').val(latlng.lat.toFixed(8));
                            $('#longitude').val(latlng.lng.toFixed(8));
                        });
                    }
                    
                    $('#latitude').val(lat.toFixed(8));
                    $('#longitude').val(lng.toFixed(8));
                }, function(err) {
                    btn.innerHTML = '<i class="fas fa-crosshairs"></i>';
                    let msg = 'Tidak dapat mengakses lokasi';
                    if (err.code === 1) msg = 'Izin lokasi ditolak. Aktifkan GPS dan izinkan akses lokasi di browser.';
                    else if (err.code === 2) msg = 'Lokasi tidak tersedia. Pastikan GPS aktif.';
                    else if (err.code === 3) msg = 'Waktu permintaan lokasi habis. Coba lagi.';
                    toastr.error(msg);
                }, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                });
                return false;
            };
            return div;
        };
        locateBtn.addTo(map);
    }
    
    // Enable code editing
    $('#code').on('dblclick', function() {
        $(this).prop('readonly', false).focus();
    });

    // ODC change handler - update port options and generate code
    $('#odc_id').on('change', function() {
        const odcId = $(this).val();
        const $portSelect = $('#odc_port');
        
        $portSelect.html('<option value="">Memuat...</option>');
        
        if (!odcId) {
            $portSelect.html('<option value="">-- Pilih ODC Dulu --</option>');
            $('#code').val('');
            return;
        }
        
        const $selected = $(this).find(':selected');
        const totalPorts = parseInt($selected.data('total-ports')) || 12;
        
        // Get used ports from server
        $.get('{{ route("admin.odps.by-odc") }}', { odc_id: odcId }, function(usedOdps) {
            const usedPorts = usedOdps.map(o => o.odc_port);
            
            let options = '<option value="">-- Pilih Port --</option>';
            for (let i = 1; i <= totalPorts; i++) {
                const isUsed = usedPorts.includes(i);
                options += `<option value="${i}" ${isUsed ? 'disabled' : ''}>${i} ${isUsed ? '(Terpakai)' : ''}</option>`;
            }
            $portSelect.html(options);
        }).fail(function() {
            // Fallback if endpoint fails
            let options = '<option value="">-- Pilih Port --</option>';
            for (let i = 1; i <= totalPorts; i++) {
                options += `<option value="${i}">${i}</option>`;
            }
            $portSelect.html(options);
        });
        
        generateCode();
    });
    
    // Used PON ports data from controller
    var usedPonPorts = @json($usedPonPorts ?? []);
    
    // OLT change handler - populate PON port dropdown with protection
    $('#olt_id').on('change', function() {
        const $selected = $(this).find(':selected');
        const oltId = $(this).val();
        const ponPorts = parseInt($selected.data('pon-ports')) || 8;
        const $ponSelect = $('#olt_pon_port');
        const $ponInfo = $('#pon_port_info');
        
        // Get used ports for this OLT
        const usedForOlt = usedPonPorts[oltId] || {};
        const usedCount = Object.keys(usedForOlt).length;
        const availableCount = ponPorts - usedCount;
        
        // Build options
        let options = '<option value="">-- Pilih PON Port --</option>';
        for (let i = 1; i <= ponPorts; i++) {
            const usedData = usedForOlt[i];
            if (usedData) {
                // Port is used - disable it and show info
                options += `<option value="${i}" disabled class="text-danger">PON ${i} - ⛔ Digunakan: ${usedData.odp_code}</option>`;
            } else {
                options += `<option value="${i}">PON ${i} - ✅ Tersedia</option>`;
            }
        }
        $ponSelect.html(options);
        
        // Update info text
        if (oltId) {
            if (availableCount > 0) {
                $ponInfo.html(`<span class="text-success"><i class="fas fa-info-circle"></i> ${availableCount} dari ${ponPorts} PON port tersedia</span>`);
            } else {
                $ponInfo.html(`<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Semua PON port sudah digunakan!</span>`);
            }
        } else {
            $ponInfo.html('');
        }
        
        generateCode();
    });
    
    // Parent ODP change handler
    $('#parent_odp_id').on('change', function() {
        generateCode();
        
        var $selected = $(this).find(':selected');
        var cascadePower = parseFloat($selected.data('cascade-power')) || null;
        var outputPower = parseFloat($selected.data('output-power')) || null;
        var splitterLevel = parseInt($selected.data('splitter-level')) || 1;
        
        // Use cascade power if available, otherwise output power
        var parentPower = cascadePower !== null ? cascadePower : outputPower;
        
        if (parentPower !== null && !isNaN(parentPower)) {
            $('#parent_power_display').val(parentPower.toFixed(2) + ' dBm');
            $('#splitter_level_display').val('Level ' + (splitterLevel + 1));
            
            // Update status indicator
            if (parentPower >= -15) {
                $('#parent_power_status').html('✅').attr('title', 'Power bagus');
                $('#parent_power_display').removeClass('text-warning text-danger').addClass('text-success');
            } else if (parentPower >= -25) {
                $('#parent_power_status').html('⚠️').attr('title', 'Power cukup');
                $('#parent_power_display').removeClass('text-success text-danger').addClass('text-warning');
            } else {
                $('#parent_power_status').html('❌').attr('title', 'Power rendah');
                $('#parent_power_display').removeClass('text-success text-warning').addClass('text-danger');
            }
            
            // Show cascade splitter config
            $('#cascade-splitter-config').show();
            
            // Store parent power for calculations
            window.parentOdpPower = parentPower;
        } else {
            $('#parent_power_display').val('-- dBm');
            $('#parent_power_status').html('❓').attr('title', 'Belum ada data power');
            $('#splitter_level_display').val('Level ' + (splitterLevel + 1));
            $('#cascade-splitter-config').hide();
            window.parentOdpPower = null;
        }
    });
    
    // Trigger change if pre-selected
    @if($selectedOdc)
    $('#odc_id').trigger('change');
    @endif
    @if($selectedOlt)
    $('#olt_id').trigger('change');
    @endif
    
    // Initialize connection type on page load
    var initialConnectionType = '{{ old("connection_type", $connectionType) }}';
    if (initialConnectionType) {
        setConnectionType(initialConnectionType);
    }
    
    // Photo handling
    var photoInput = document.getElementById('photos');
    var photoPreview = document.getElementById('photo-preview');
    
    $('#photos').on('change', function() {
        var files = this.files;
        if (files.length > 0) {
            $(this).next('.custom-file-label').text(files.length + ' foto dipilih');
            updatePhotoPreview();
        }
    });
    
    function updatePhotoPreview() {
        photoPreview.innerHTML = '';
        var files = photoInput.files;
        
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = (function(f, idx) {
                    return function(e) {
                        var div = document.createElement('div');
                        div.className = 'position-relative mr-2 mb-2';
                        div.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;">' +
                            '<button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 6px;" onclick="removePhoto(' + idx + ')"><i class="fas fa-times"></i></button>';
                        photoPreview.appendChild(div);
                    };
                })(file, i);
                reader.readAsDataURL(file);
            }
        }
    }
    
    // Camera capture
    $('#btn-camera').on('click', function() {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.capture = 'environment';
        input.onchange = function(e) {
            if (e.target.files.length > 0) {
                var dt = new DataTransfer();
                var existingFiles = photoInput.files;
                for (var i = 0; i < existingFiles.length; i++) {
                    dt.items.add(existingFiles[i]);
                }
                dt.items.add(e.target.files[0]);
                photoInput.files = dt.files;
                $('#photos').next('.custom-file-label').text(dt.files.length + ' foto dipilih');
                updatePhotoPreview();
            }
        };
        input.click();
    });
});

// Remove photo from preview
window.removePhoto = function(idx) {
    var dt = new DataTransfer();
    var files = document.getElementById('photos').files;
    for (var i = 0; i < files.length; i++) {
        if (i !== idx) {
            dt.items.add(files[i]);
        }
    }
    document.getElementById('photos').files = dt.files;
    $('#photos').next('.custom-file-label').text(dt.files.length > 0 ? dt.files.length + ' foto dipilih' : 'Pilih foto...');
    
    var photoPreview = document.getElementById('photo-preview');
    photoPreview.innerHTML = '';
    for (var i = 0; i < dt.files.length; i++) {
        var file = dt.files[i];
        if (file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = (function(f, idx) {
                return function(e) {
                    var div = document.createElement('div');
                    div.className = 'position-relative mr-2 mb-2';
                    div.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;">' +
                        '<button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 6px;" onclick="removePhoto(' + idx + ')"><i class="fas fa-times"></i></button>';
                    photoPreview.appendChild(div);
                };
            })(file, i);
            reader.readAsDataURL(file);
        }
    }
};

// Splitter calculators are initialized in splitter-calculator.js
// The script handles all splitter type changes, calculations, and form field updates automatically

// Fetch power from source
$('#btn-fetch-power').on('click', function() {
    var btn = $(this);
    var connectionType = $('#connection_type').val();
    var oltId = $('#olt_id').val();
    var odcId = $('#odc_id').val();
    var parentOdpId = $('#parent_odp_id').val();
    var ponPort = $('#olt_pon_port').val();
    
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Membaca...');
    
    $.ajax({
        url: '{{ route("admin.odps.source-power") }}',
        method: 'GET',
        data: {
            connection_type: connectionType,
            olt_id: oltId,
            odc_id: odcId,
            parent_odp_id: parentOdpId,
            pon_port: ponPort
        },
        success: function(response) {
            if (response.success && response.source_power !== null) {
                $('#input_power').val(response.source_power);
                $('#is_power_manual').val(response.is_auto ? 0 : 1);
                $('#power-source-info').html('<span class="text-success"><i class="fas fa-check mr-1"></i>' + response.message + '</span>');
                
                // Trigger recalculation based on connection type
                if (connectionType === 'odc') {
                    SplitterCalculator.setInputPower('odc', response.source_power);
                } else if (connectionType === 'olt') {
                    SplitterCalculator.setInputPower('olt', response.source_power);
                } else if (connectionType === 'cascade') {
                    SplitterCalculator.setInputPower('cascade', response.source_power);
                }
            } else {
                $('#power-source-info').html('<span class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>' + response.message + '</span>');
            }
        },
        error: function() {
            $('#power-source-info').html('<span class="text-danger"><i class="fas fa-times mr-1"></i>Gagal membaca power dari sumber</span>');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i> Baca Power Otomatis');
        }
    });
});
</script>
@endpush
