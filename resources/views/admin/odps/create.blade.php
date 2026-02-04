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
</style>
@endpush

@section('content')
<script>
function setConnectionType(type) {
    // Set hidden input value
    document.getElementById('connection_type').value = type;
    
    // Get all cards
    var cards = ['odc', 'olt', 'cascade'];
    
    // Reset all cards style
    cards.forEach(function(t) {
        var card = document.getElementById('card-' + t);
        card.style.border = '3px solid #dee2e6';
        card.style.background = '#fff';
        card.style.boxShadow = 'none';
        card.style.transform = 'none';
        // Remove label if exists
        var label = card.querySelector('.selected-label');
        if (label) label.remove();
    });
    
    // Apply active style to selected card
    var activeCard = document.getElementById('card-' + type);
    activeCard.style.border = '4px solid #28a745';
    activeCard.style.background = '#d4edda';
    activeCard.style.boxShadow = '0 0 0 5px rgba(40,167,69,0.4), 0 10px 25px rgba(40,167,69,0.3)';
    activeCard.style.transform = 'translateY(-3px) scale(1.02)';
    
    // Add "DIPILIH" label
    var label = document.createElement('div');
    label.className = 'selected-label';
    label.innerHTML = '✓ DIPILIH';
    label.style.cssText = 'position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:#28a745;color:white;padding:3px 12px;border-radius:12px;font-size:11px;font-weight:bold;z-index:100;white-space:nowrap;';
    activeCard.style.position = 'relative';
    activeCard.style.overflow = 'visible';
    activeCard.appendChild(label);
    
    // Hide all fields
    document.getElementById('odc-fields').style.display = 'none';
    document.getElementById('olt-fields').style.display = 'none';
    document.getElementById('cascade-fields').style.display = 'none';
    
    // Show selected fields
    document.getElementById(type + '-fields').style.display = 'block';
    
    // Generate new code
    if (typeof generateCode === 'function') {
        generateCode();
    }
}
</script>
<form action="{{ route('admin.odps.store') }}" method="POST">
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
                                    <input type="number" class="form-control @error('olt_pon_port') is-invalid @enderror" 
                                           id="olt_pon_port" name="olt_pon_port" value="{{ old('olt_pon_port', 1) }}" min="1">
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
                    </div>

                    <!-- Parent ODP Selection (shown when connection_type = cascade) -->
                    <div id="cascade-fields" class="connection-fields" style="{{ old('connection_type', $connectionType) != 'cascade' ? 'display:none;' : '' }}">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="parent_odp_id">Parent ODP <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('parent_odp_id') is-invalid @enderror" 
                                            id="parent_odp_id" name="parent_odp_id" style="width: 100%;">
                                        <option value="">-- Pilih ODP Parent --</option>
                                        @foreach($parentOdps as $podp)
                                            <option value="{{ $podp->id }}" 
                                                    {{ old('parent_odp_id') == $podp->id ? 'selected' : '' }}>
                                                {{ $podp->code }} - {{ $podp->name }} (Level {{ $podp->splitter_level ?? 1 }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_odp_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">ODP ini akan menjadi turunan dari parent (estafet splitter)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="splitter_level_display">Splitter Level</label>
                                    <input type="text" class="form-control" id="splitter_level_display" readonly value="Auto (Level 2+)">
                                </div>
                            </div>
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
                navigator.geolocation.getCurrentPosition(function(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    map.setView([lat, lng], 18);
                    
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
    
    // OLT change handler
    $('#olt_id').on('change', function() {
        const $selected = $(this).find(':selected');
        const ponPorts = parseInt($selected.data('pon-ports')) || 8;
        $('#olt_pon_port').attr('max', ponPorts);
        generateCode();
    });
    
    // Parent ODP change handler
    $('#parent_odp_id').on('change', function() {
        generateCode();
    });
    
    // Trigger change if pre-selected
    @if($selectedOdc)
    $('#odc_id').trigger('change');
    @endif
    @if($selectedOlt)
    $('#olt_id').trigger('change');
    @endif
});
</script>
@endpush
