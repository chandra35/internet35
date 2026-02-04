@extends('layouts.admin')

@section('title', 'Edit ODP')

@section('page-title', 'Edit ODP: ' . $odp->code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.odps.index', ['pop_id' => $odp->pop_id]) }}">ODP</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@push('css')
<style>
    #map { height: 400px; border-radius: 5px; }
    .connection-type-card { cursor: pointer; transition: all 0.3s; border: 2px solid #dee2e6; }
    .connection-type-card:hover { transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); border-color: #adb5bd; }
    .connection-type-card.active { border-color: #007bff !important; background-color: #e7f1ff; box-shadow: 0 0 0 3px rgba(0,123,255,0.25); }
    .connection-type-card .badge { font-size: 0.7rem; }
    .connection-type-card i { transition: transform 0.3s; }
    .connection-type-card:hover i { transform: scale(1.1); }
    .custom-odp-marker { background: transparent; border: none; }
    .leaflet-control-layers { border-radius: 8px; }
    .leaflet-control-layers-toggle { width: 36px; height: 36px; }
</style>
@endpush

@section('content')
<form action="{{ route('admin.odps.update', $odp) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
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
                        <div class="col-md-4">
                            <div class="card connection-type-card {{ old('connection_type', $connectionType) == 'odc' ? 'active' : '' }}" 
                                 data-type="odc" role="button" tabindex="0">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-box fa-2x text-primary mb-2"></i>
                                    <h6 class="mb-1">Via ODC</h6>
                                    <small class="text-muted">OLT → ODC → ODP</small>
                                    <br><span class="badge badge-primary">Standard</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card connection-type-card {{ old('connection_type', $connectionType) == 'olt' ? 'active' : '' }}" 
                                 data-type="olt" role="button" tabindex="0">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-server fa-2x text-success mb-2"></i>
                                    <h6 class="mb-1">Direct OLT</h6>
                                    <small class="text-muted">OLT → ODP</small>
                                    <br><span class="badge badge-success">Tanpa ODC</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card connection-type-card {{ old('connection_type', $connectionType) == 'cascade' ? 'active' : '' }}" 
                                 data-type="cascade" role="button" tabindex="0">
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
                                                    {{ old('odc_id', $odp->odc_id) == $odc->id ? 'selected' : '' }}>
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
                                    <input type="number" class="form-control @error('odc_port') is-invalid @enderror" 
                                           id="odc_port" name="odc_port" value="{{ old('odc_port', $odp->odc_port) }}" min="1">
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
                                                    {{ old('olt_id', $odp->olt_id) == $olt->id ? 'selected' : '' }}>
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
                                           id="olt_pon_port" name="olt_pon_port" value="{{ old('olt_pon_port', $odp->olt_pon_port ?? 1) }}" min="1">
                                    @error('olt_pon_port')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="olt_slot">Slot (Opsional)</label>
                                    <input type="number" class="form-control @error('olt_slot') is-invalid @enderror" 
                                           id="olt_slot" name="olt_slot" value="{{ old('olt_slot', $odp->olt_slot) }}" min="0">
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
                                                    {{ old('parent_odp_id', $odp->parent_odp_id) == $podp->id ? 'selected' : '' }}>
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
                                    <input type="text" class="form-control" id="splitter_level_display" readonly 
                                           value="Level {{ $odp->splitter_level ?? 2 }}">
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
                                       id="code" name="code" value="{{ old('code', $odp->code) }}">
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nama ODP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $odp->name) }}" required>
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
                                    <option value="active" {{ old('status', $odp->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="maintenance" {{ old('status', $odp->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                    <option value="inactive" {{ old('status', $odp->status) == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
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
                                       id="pole_number" name="pole_number" value="{{ old('pole_number', $odp->pole_number) }}" 
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
                                  id="address" name="address" rows="2">{{ old('address', $odp->address) }}</textarea>
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
                                       id="latitude" name="latitude" value="{{ old('latitude', $odp->latitude) }}" step="any">
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="longitude">Longitude</label>
                                <input type="text" class="form-control @error('longitude') is-invalid @enderror" 
                                       id="longitude" name="longitude" value="{{ old('longitude', $odp->longitude) }}" step="any">
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
                               id="total_ports" name="total_ports" value="{{ old('total_ports', $odp->total_ports) }}" 
                               min="{{ $odp->used_ports }}" max="100" required>
                        @error('total_ports')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimal {{ $odp->used_ports }} (sudah terpakai)</small>
                    </div>
                    
                    <div class="alert alert-info mb-0">
                        <small>
                            <strong>Port Terpakai:</strong> {{ $odp->used_ports }} / {{ $odp->total_ports }}<br>
                            <strong>Port Tersedia:</strong> {{ $odp->available_ports }}
                        </small>
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
                               id="box_type" name="box_type" value="{{ old('box_type', $odp->box_type) }}" 
                               placeholder="Contoh: ODP 8 Core">
                        @error('box_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="splitter_type">Tipe Splitter</label>
                        <input type="text" class="form-control @error('splitter_type') is-invalid @enderror" 
                               id="splitter_type" name="splitter_type" value="{{ old('splitter_type', $odp->splitter_type) }}"
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
                                  id="notes" name="notes" rows="4" placeholder="Catatan tambahan...">{{ old('notes', $odp->notes) }}</textarea>
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
                    @if($odp->photos && count($odp->photos) > 0)
                    <div class="mb-3">
                        <label class="mb-2"><strong>Foto Saat Ini:</strong></label>
                        <div class="d-flex flex-wrap">
                            @foreach($odp->photos as $idx => $photo)
                            <div id="photo-{{ $idx }}" class="position-relative mr-2 mb-2">
                                <img src="{{ $odp->getThumbnailUrl($photo) }}" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;">
                                <input type="hidden" name="keep_photos[]" id="keep-{{ $idx }}" value="{{ $photo }}">
                                <button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 6px;" 
                                        onclick="markPhotoForRemoval('{{ $photo }}', {{ $idx }})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    <div class="form-group">
                        <label>Tambah Foto Baru <small class="text-muted">(Maks. 10 foto total, masing-masing maks. 5MB)</small></label>
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
                    </div>
                    <div id="photo-preview" class="d-flex flex-wrap mt-2"></div>
                </div>
            </div>

            <!-- Submit -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.odps.show', $odp) }}" class="btn btn-info btn-block">
                        <i class="fas fa-eye mr-1"></i> Lihat Detail
                    </a>
                    <a href="{{ route('admin.odps.index', ['pop_id' => $odp->pop_id]) }}" class="btn btn-secondary btn-block">
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
function setConnectionType(type) {
    console.log('Setting connection type:', type);
    $('#connection_type').val(type);
    
    // Update card styles
    $('.connection-type-card').removeClass('active');
    $('.connection-type-card[data-type="' + type + '"]').addClass('active');
    
    // Show/hide fields
    $('.connection-fields').hide();
    $('#' + type + '-fields').show();
}

$(function() {
    // Connection type card click handler (more robust)
    $(document).on('click', '.connection-type-card', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const type = $(this).data('type');
        setConnectionType(type);
    });

    // Initialize map with satellite view
    const defaultLat = {{ old('latitude', $odp->latitude ?? -7.9666) }};
    const defaultLng = {{ old('longitude', $odp->longitude ?? 110.6283) }};
    
    const map = L.map('map').setView([defaultLat, defaultLng], 16);
    
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
        html: '<div style="background: #ffc107; color: #333; padding: 5px 10px; border-radius: 5px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fas fa-box"></i> {{ $odp->code }}</div>',
        iconSize: [80, 30],
        iconAnchor: [40, 30]
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
    
    // OLT change handler
    $('#olt_id').on('change', function() {
        const $selected = $(this).find(':selected');
        const ponPorts = parseInt($selected.data('pon-ports')) || 8;
        $('#olt_pon_port').attr('max', ponPorts);
    });
    
    // Photo handling
    var photoInput = document.getElementById('photos');
    var photoPreview = document.getElementById('photo-preview');
    
    $('#photos').on('change', function() {
        var files = this.files;
        if (files.length > 0) {
            $(this).next('.custom-file-label').text(files.length + ' foto baru dipilih');
            updatePhotoPreview();
        } else {
            $(this).next('.custom-file-label').text('Pilih foto...');
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
                            '<span class="badge badge-success position-absolute" style="top:5px;left:5px;">Baru</span>' +
                            '<button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 6px;" onclick="removePreviewPhoto(' + idx + ')"><i class="fas fa-times"></i></button>';
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
                $('#photos').next('.custom-file-label').text(dt.files.length + ' foto baru dipilih');
                updatePhotoPreview();
            }
        };
        input.click();
    });
});

// Mark existing photo for removal
window.markPhotoForRemoval = function(filename, idx) {
    if (confirm('Hapus foto ini?')) {
        $('#photo-' + idx).hide();
        $('#keep-' + idx).remove();
        $('<input>').attr({
            type: 'hidden',
            name: 'remove_photos[]',
            value: filename
        }).appendTo('form');
    }
};

// Remove new photo from preview
window.removePreviewPhoto = function(idx) {
    var dt = new DataTransfer();
    var files = document.getElementById('photos').files;
    for (var i = 0; i < files.length; i++) {
        if (i !== idx) {
            dt.items.add(files[i]);
        }
    }
    document.getElementById('photos').files = dt.files;
    $('#photos').next('.custom-file-label').text(dt.files.length > 0 ? dt.files.length + ' foto baru dipilih' : 'Pilih foto...');
    
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
                        '<span class="badge badge-success position-absolute" style="top:5px;left:5px;">Baru</span>' +
                        '<button type="button" class="btn btn-danger btn-xs position-absolute" style="top:-5px;right:-5px;padding:2px 6px;" onclick="removePreviewPhoto(' + idx + ')"><i class="fas fa-times"></i></button>';
                    photoPreview.appendChild(div);
                };
            })(file, i);
            reader.readAsDataURL(file);
        }
    }
};
</script>
@endpush
