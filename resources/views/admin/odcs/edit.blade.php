@extends('layouts.admin')

@section('title', 'Edit ODC')

@section('page-title', 'Edit ODC: ' . $odc->code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.odcs.index', ['pop_id' => $odc->pop_id]) }}">ODC</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map { height: 300px; border-radius: 5px; }
</style>
@endsection

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
                                <label for="router_id">Router <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('router_id') is-invalid @enderror" 
                                        id="router_id" name="router_id" required style="width: 100%;">
                                    <option value="">-- Pilih Router --</option>
                                    @foreach($routers as $router)
                                        <option value="{{ $router->id }}" {{ old('router_id', $odc->router_id) == $router->id ? 'selected' : '' }}>
                                            {{ $router->name }} ({{ $router->host }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('router_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
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

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(function() {
    // Initialize map
    const defaultLat = {{ old('latitude', $odc->latitude ?? -6.2088) }};
    const defaultLng = {{ old('longitude', $odc->longitude ?? 106.8456) }};
    
    const map = L.map('map').setView([defaultLat, defaultLng], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    let marker = null;
    
    // Add marker if coordinates exist
    if ($('#latitude').val() && $('#longitude').val()) {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());
        marker = L.marker([lat, lng]).addTo(map);
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
            marker = L.marker(e.latlng).addTo(map);
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
                marker = L.marker([lat, lng]).addTo(map);
            }
            map.setView([lat, lng], 15);
        }
    });
});
</script>
@endsection
