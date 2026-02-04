@extends('layouts.admin')

@section('title', 'Edit ODC')

@section('page-title', 'Edit ODC: ' . $odc->code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.odcs.index', ['pop_id' => $odc->pop_id]) }}">ODC</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@push('css')
<style>
    #map { height: 300px; border-radius: 5px; }
</style>
@endpush

@section('content')
<form action="{{ route('admin.odcs.update', $odc) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="row">
        <div class="col-md-8">
            <!-- Basic Info -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi Dasar</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code">Kode ODC</label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                       id="code" name="code" value="{{ old('code', $odc->code) }}">
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nama ODC <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $odc->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="olt_id">OLT <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('olt_id') is-invalid @enderror" 
                                        id="olt_id" name="olt_id" required style="width: 100%;">
                                    <option value="">-- Pilih OLT --</option>
                                    @foreach($olts as $olt)
                                        <option value="{{ $olt->id }}" 
                                                data-pon-ports="{{ $olt->ponPorts->toJson() }}"
                                                {{ old('olt_id', $odc->olt_id) == $olt->id ? 'selected' : '' }}>
                                            {{ $olt->name }} ({{ $olt->ip_address }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('olt_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="olt_slot">Slot</label>
                                <input type="number" class="form-control @error('olt_slot') is-invalid @enderror" 
                                       id="olt_slot" name="olt_slot" value="{{ old('olt_slot', $odc->olt_slot ?? 0) }}" min="0">
                                @error('olt_slot')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="olt_pon_port">PON Port</label>
                                <select class="form-control @error('olt_pon_port') is-invalid @enderror" 
                                        id="olt_pon_port" name="olt_pon_port">
                                    <option value="">-- Pilih --</option>
                                </select>
                                @error('olt_pon_port')
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
                                    <option value="active" {{ old('status', $odc->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="maintenance" {{ old('status', $odc->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                    <option value="inactive" {{ old('status', $odc->status) == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="text-muted small" id="pon-port-info"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Alamat</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  id="address" name="address" rows="2">{{ old('address', $odc->address) }}</textarea>
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
                    <div class="row mb-2">
                        <div class="col-md-10">
                            <div class="input-group">
                                <input type="text" id="searchAddress" class="form-control" placeholder="Cari alamat atau lokasi...">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-primary" id="btnSearch">
                                        <i class="fas fa-search"></i> Cari
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-success btn-block" id="btnMyLocation">
                                <i class="fas fa-crosshairs"></i>
                            </button>
                        </div>
                    </div>
                    <div id="map"></div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="latitude">Latitude</label>
                                <input type="text" class="form-control @error('latitude') is-invalid @enderror" 
                                       id="latitude" name="latitude" value="{{ old('latitude', $odc->latitude) }}" step="any">
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="longitude">Longitude</label>
                                <input type="text" class="form-control @error('longitude') is-invalid @enderror" 
                                       id="longitude" name="longitude" value="{{ old('longitude', $odc->longitude) }}" step="any">
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
                               id="total_ports" name="total_ports" value="{{ old('total_ports', $odc->total_ports) }}" 
                               min="{{ $odc->used_ports }}" max="1000" required>
                        @error('total_ports')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            Port terpakai: {{ $odc->used_ports }} | Tersedia: {{ $odc->available_ports }}
                        </small>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-{{ $odc->port_usage_percent > 80 ? 'danger' : ($odc->port_usage_percent > 50 ? 'warning' : 'success') }}" 
                             style="width: {{ $odc->port_usage_percent }}%">
                            {{ $odc->port_usage_percent }}%
                        </div>
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
                        <label for="cabinet_type">Tipe Cabinet</label>
                        <input type="text" class="form-control @error('cabinet_type') is-invalid @enderror" 
                               id="cabinet_type" name="cabinet_type" value="{{ old('cabinet_type', $odc->cabinet_type) }}" 
                               placeholder="Contoh: Outdoor 144 Core">
                        @error('cabinet_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="cable_type">Tipe Kabel</label>
                        <input type="text" class="form-control @error('cable_type') is-invalid @enderror" 
                               id="cable_type" name="cable_type" value="{{ old('cable_type', $odc->cable_type) }}"
                               placeholder="Contoh: Fiber Optic Single Mode">
                        @error('cable_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="cable_core">Jumlah Core</label>
                        <input type="number" class="form-control @error('cable_core') is-invalid @enderror" 
                               id="cable_core" name="cable_core" value="{{ old('cable_core', $odc->cable_core) }}" min="1">
                        @error('cable_core')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="cable_distance">Jarak Kabel (meter)</label>
                        <input type="number" class="form-control @error('cable_distance') is-invalid @enderror" 
                               id="cable_distance" name="cable_distance" value="{{ old('cable_distance', $odc->cable_distance) }}" min="0" step="0.01">
                        @error('cable_distance')
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
                                  id="notes" name="notes" rows="4" placeholder="Catatan tambahan...">{{ old('notes', $odc->notes) }}</textarea>
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
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.odcs.index', ['pop_id' => $odc->pop_id]) }}" class="btn btn-secondary btn-block">
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
$(function() {
    // Initialize map
    const defaultLat = {{ old('latitude', $odc->latitude ?? -6.2088) }};
    const defaultLng = {{ old('longitude', $odc->longitude ?? 106.8456) }};
    
    const map = L.map('map').setView([defaultLat, defaultLng], 16);
    
    // Define base layers - Google Satellite
    var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    });
    
    var satelliteLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '© Google'
    });
    
    var hybridLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '© Google'
    });
    
    // Add default layer
    satelliteLayer.addTo(map);
    
    // Layer control
    var baseLayers = {
        "Satelit": satelliteLayer,
        "Peta": osmLayer,
        "Hybrid": hybridLayer
    };
    L.control.layers(baseLayers, null, { position: 'topright' }).addTo(map);
    
    let marker = null;
    
    // Function to set marker
    function setMarker(lat, lng) {
        $('#latitude').val(lat.toFixed(8));
        $('#longitude').val(lng.toFixed(8));
        
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function(ev) {
                var ll = ev.target.getLatLng();
                $('#latitude').val(ll.lat.toFixed(8));
                $('#longitude').val(ll.lng.toFixed(8));
            });
        }
    }
    
    // Add marker if coordinates exist
    if ($('#latitude').val() && $('#longitude').val()) {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', function(ev) {
            var ll = ev.target.getLatLng();
            $('#latitude').val(ll.lat.toFixed(8));
            $('#longitude').val(ll.lng.toFixed(8));
        });
    }
    
    // Click on map to set location
    map.on('click', function(e) {
        setMarker(e.latlng.lat, e.latlng.lng);
    });
    
    // Update marker when coordinates change manually
    $('#latitude, #longitude').on('change', function() {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());
        
        if (!isNaN(lat) && !isNaN(lng)) {
            setMarker(lat, lng);
            map.setView([lat, lng], 17);
        }
    });
    
    // Search address using Nominatim
    function searchAddress() {
        var query = $('#searchAddress').val().trim();
        if (!query) return;
        
        $('#btnSearch').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: 'https://nominatim.openstreetmap.org/search',
            data: { q: query, format: 'json', limit: 1, countrycodes: 'id' },
            success: function(data) {
                if (data && data.length > 0) {
                    var lat = parseFloat(data[0].lat);
                    var lng = parseFloat(data[0].lon);
                    setMarker(lat, lng);
                    map.setView([lat, lng], 17);
                } else {
                    alert('Lokasi tidak ditemukan');
                }
            },
            error: function() { alert('Gagal mencari lokasi'); },
            complete: function() {
                $('#btnSearch').prop('disabled', false).html('<i class="fas fa-search"></i> Cari');
            }
        });
    }
    
    $('#btnSearch').on('click', searchAddress);
    $('#searchAddress').on('keypress', function(e) {
        if (e.which === 13) { e.preventDefault(); searchAddress(); }
    });
    
    // My location button
    $('#btnMyLocation').on('click', function() {
        if (!navigator.geolocation) { alert('Browser tidak mendukung geolocation'); return; }
        
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                setMarker(position.coords.latitude, position.coords.longitude);
                map.setView([position.coords.latitude, position.coords.longitude], 17);
                btn.prop('disabled', false).html('<i class="fas fa-crosshairs"></i>');
            },
            function(error) {
                alert('Gagal mendapatkan lokasi');
                btn.prop('disabled', false).html('<i class="fas fa-crosshairs"></i>');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
    
    setTimeout(function() { map.invalidateSize(); }, 200);
    
    // Handle OLT change - populate PON ports
    var currentPonPort = '{{ old('olt_pon_port', $odc->olt_pon_port) }}';
    
    $('#olt_id').on('change', function() {
        var $selected = $(this).find(':selected');
        var ponPorts = $selected.data('pon-ports') || [];
        var $ponSelect = $('#olt_pon_port');
        
        $ponSelect.empty().append('<option value="">-- Pilih --</option>');
        $('#pon-port-info').html('');
        
        if (ponPorts && ponPorts.length > 0) {
            ponPorts.forEach(function(port) {
                var label = 'PON ' + port.port;
                if (port.slot > 0) {
                    label = 'Slot ' + port.slot + ' / PON ' + port.port;
                }
                var status = port.status === 'up' ? '✓' : '✗';
                var selected = (port.port == currentPonPort) ? 'selected' : '';
                $ponSelect.append('<option value="' + port.port + '" data-slot="' + port.slot + '" ' + selected + '>' + label + ' [' + status + ']</option>');
            });
            $('#pon-port-info').html('<i class="fas fa-info-circle"></i> ' + ponPorts.length + ' PON ports tersedia');
        } else {
            $('#pon-port-info').html('<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Tidak ada PON port terdaftar</span>');
        }
    });
    
    // Update slot when PON port is selected
    $('#olt_pon_port').on('change', function() {
        var $selected = $(this).find(':selected');
        var slot = $selected.data('slot') || 0;
        $('#olt_slot').val(slot);
    });
    
    // Trigger OLT change on load if already selected
    if ($('#olt_id').val()) {
        $('#olt_id').trigger('change');
    }
});
</script>
@endpush
