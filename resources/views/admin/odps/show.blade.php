@extends('layouts.admin')

@section('title', 'Detail ODP')

@section('page-title', 'Detail ODP: ' . $odp->code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.odps.index', ['pop_id' => $odp->pop_id]) }}">ODP</a></li>
    <li class="breadcrumb-item active">Detail</li>
@endsection

@push('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
<style>
    #map { height: 400px; border-radius: 5px; }
    .custom-marker { background: transparent; border: none; }
    .leaflet-control-layers { border-radius: 8px; }
    .leaflet-control-layers-toggle { width: 36px; height: 36px; }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-8">
        <!-- Basic Info -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi Dasar</h3>
                <div class="card-tools">
                    @can('odps.edit')
                    <a href="{{ route('admin.odps.edit', $odp) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td width="150"><strong>Kode</strong></td>
                        <td><code class="text-lg">{{ $odp->code }}</code></td>
                    </tr>
                    <tr>
                        <td><strong>Nama</strong></td>
                        <td>{{ $odp->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Tipe Koneksi</strong></td>
                        <td>
                            @if($odp->odc)
                                <span class="badge badge-primary"><i class="fas fa-box mr-1"></i>Via ODC</span>
                            @elseif($odp->parentOdp)
                                <span class="badge badge-warning"><i class="fas fa-sitemap mr-1"></i>Cascade/Relay</span>
                            @elseif($odp->olt)
                                <span class="badge badge-success"><i class="fas fa-server mr-1"></i>Direct OLT</span>
                            @else
                                <span class="badge badge-secondary">-</span>
                            @endif
                            @if($odp->splitter_level)
                                <span class="badge badge-info ml-1">Level {{ $odp->splitter_level }}</span>
                            @endif
                        </td>
                    </tr>
                    @if($odp->odc)
                    <tr>
                        <td><strong>ODC</strong></td>
                        <td>
                            <a href="{{ route('admin.odcs.show', $odp->odc) }}">
                                {{ $odp->odc->code }} - {{ $odp->odc->name }}
                            </a>
                            <span class="badge badge-info ml-2">Port {{ $odp->odc_port }}</span>
                        </td>
                    </tr>
                    @endif
                    @if($odp->parentOdp)
                    <tr>
                        <td><strong>Parent ODP</strong></td>
                        <td>
                            <a href="{{ route('admin.odps.show', $odp->parentOdp) }}">
                                {{ $odp->parentOdp->code }} - {{ $odp->parentOdp->name }}
                            </a>
                        </td>
                    </tr>
                    @endif
                    @if($odp->olt)
                    <tr>
                        <td><strong>OLT</strong></td>
                        <td>
                            <a href="{{ route('admin.olts.show', $odp->olt) }}">
                                {{ $odp->olt->name }}
                            </a>
                            @if($odp->olt_pon_port)
                                <span class="badge badge-info ml-1">PON {{ $odp->olt_pon_port }}</span>
                            @endif
                            @if($odp->olt_slot)
                                <span class="badge badge-secondary ml-1">Slot {{ $odp->olt_slot }}</span>
                            @endif
                        </td>
                    </tr>
                    @elseif($odp->odc && $odp->odc->olt)
                    <tr>
                        <td><strong>OLT (via ODC)</strong></td>
                        <td>
                            <a href="{{ route('admin.olts.show', $odp->odc->olt) }}">
                                {{ $odp->odc->olt->name }}
                            </a>
                            @if($odp->odc->olt_pon_port)
                                <span class="badge badge-info ml-1">PON {{ $odp->odc->olt_pon_port }}</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                    @if($odp->childOdps && $odp->childOdps->count() > 0)
                    <tr>
                        <td><strong>ODP Turunan</strong></td>
                        <td>
                            @foreach($odp->childOdps as $child)
                                <a href="{{ route('admin.odps.show', $child) }}" class="badge badge-warning mr-1 mb-1">
                                    {{ $child->code }}
                                </a>
                            @endforeach
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Status</strong></td>
                        <td><span class="badge badge-{{ $odp->status_badge }} badge-lg">{{ $odp->status_label }}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Nomor Tiang</strong></td>
                        <td>{{ $odp->pole_number ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Alamat</strong></td>
                        <td>{{ $odp->address ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Dibuat Oleh</strong></td>
                        <td>{{ $odp->creator->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Dibuat Pada</strong></td>
                        <td>{{ $odp->created_at->format('d M Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Location Map -->
        @if($odp->hasCoordinates())
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Lokasi</h3>
                <div class="card-tools">
                    <a href="https://www.google.com/maps?q={{ $odp->latitude }},{{ $odp->longitude }}" 
                       target="_blank" class="btn btn-info btn-sm">
                        <i class="fas fa-external-link-alt"></i> Buka di Google Maps
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="map"></div>
                <div class="mt-2">
                    <small class="text-muted">
                        Koordinat: {{ $odp->latitude }}, {{ $odp->longitude }}
                    </small>
                </div>
            </div>
        </div>
        @endif

        <!-- Customer List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i>Daftar Pelanggan ({{ $odp->customers->count() }})</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Port ODP</th>
                            <th>Paket</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($odp->customers as $customer)
                        <tr>
                            <td><code>{{ $customer->customer_id }}</code></td>
                            <td>{{ $customer->name }}</td>
                            <td><span class="badge badge-info">Port {{ $customer->odp_port ?? '-' }}</span></td>
                            <td>{{ $customer->package->name ?? '-' }}</td>
                            <td>
                                <span class="badge badge-{{ $customer->status == 'active' ? 'success' : ($customer->status == 'suspended' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($customer->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-xs btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Belum ada pelanggan di ODP ini
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Port Usage -->
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plug mr-2"></i>Penggunaan Port</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h2 class="mb-0">{{ $odp->used_ports }}/{{ $odp->total_ports }}</h2>
                    <small class="text-muted">Port Terpakai</small>
                </div>
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar bg-{{ $odp->port_usage_percent > 80 ? 'danger' : ($odp->port_usage_percent > 50 ? 'warning' : 'success') }}" 
                         style="width: {{ $odp->port_usage_percent }}%">
                        {{ $odp->port_usage_percent }}%
                    </div>
                </div>
                <div class="mt-2 text-center">
                    <span class="badge badge-success">{{ $odp->available_ports }} port tersedia</span>
                </div>
            </div>
        </div>

        <!-- Specifications -->
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cogs mr-2"></i>Spesifikasi</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Tipe Box</strong></td>
                        <td>{{ $odp->box_type ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Tipe Splitter</strong></td>
                        <td>{{ $odp->splitter_type ?: '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Notes -->
        @if($odp->notes)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Catatan</h3>
            </div>
            <div class="card-body">
                {{ $odp->notes }}
            </div>
        </div>
        @endif

        <!-- Photos -->
        @if($odp->photos && count($odp->photos) > 0)
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-images mr-2"></i>Foto Dokumentasi ({{ count($odp->photos) }})</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($odp->photos as $photo)
                    <div class="col-6 mb-2">
                        <a href="{{ $odp->getPhotoUrl($photo) }}" data-lightbox="odp-photos" data-title="{{ $odp->code }}">
                            <img src="{{ $odp->getThumbnailUrl($photo) }}" class="img-thumbnail w-100" 
                                 style="height:100px;object-fit:cover;" alt="Foto ODP">
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Actions -->
        <div class="card">
            <div class="card-body">
                <a href="{{ route('admin.odps.index', ['pop_id' => $odp->pop_id]) }}" class="btn btn-secondary btn-block">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
                </a>
                @if($odp->odc)
                <a href="{{ route('admin.odcs.show', $odp->odc) }}" class="btn btn-primary btn-block">
                    <i class="fas fa-server mr-1"></i> Lihat ODC
                </a>
                @endif
                @can('network-map.view')
                <a href="{{ route('admin.network-map.index', ['pop_id' => $odp->pop_id]) }}" class="btn btn-info btn-block">
                    <i class="fas fa-map mr-1"></i> Lihat di Network Map
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
@if($odp->hasCoordinates())
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(function() {
    const lat = {{ $odp->latitude }};
    const lng = {{ $odp->longitude }};
    
    const map = L.map('map').setView([lat, lng], 18);
    
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
    
    // ODP marker with custom label
    const odpIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background: #28a745; color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.3); white-space: nowrap;"><i class="fas fa-box"></i> {{ $odp->code }}</div>',
        iconSize: [100, 30],
        iconAnchor: [50, 30]
    });
    
    const odpMarker = L.marker([lat, lng], {icon: odpIcon})
        .addTo(map)
        .bindPopup('<strong>{{ $odp->code }}</strong><br>{{ $odp->name }}<br><small class="text-muted">{{ $odp->address }}</small>');
    
    odpMarker.openPopup();
    
    // Add Parent connection marker and line
    @if($odp->odc && $odp->odc->hasCoordinates())
    const odcIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background: #007bff; color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.3); white-space: nowrap;"><i class="fas fa-box-open"></i> {{ $odp->odc->code }}</div>',
        iconSize: [100, 30],
        iconAnchor: [50, 30]
    });
    
    L.marker([{{ $odp->odc->latitude }}, {{ $odp->odc->longitude }}], {icon: odcIcon})
        .addTo(map)
        .bindPopup('<strong>ODC: {{ $odp->odc->code }}</strong><br>{{ $odp->odc->name }}');
    
    // Draw line from ODC to ODP
    L.polyline([
        [{{ $odp->odc->latitude }}, {{ $odp->odc->longitude }}],
        [lat, lng]
    ], {color: '#28a745', weight: 3, dashArray: '10, 5'}).addTo(map);
    
    // Fit bounds to show both markers
    map.fitBounds([
        [lat, lng],
        [{{ $odp->odc->latitude }}, {{ $odp->odc->longitude }}]
    ], {padding: [50, 50]});
    @endif
    
    @if($odp->parentOdp && $odp->parentOdp->hasCoordinates())
    const parentIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background: #ffc107; color: #333; padding: 5px 10px; border-radius: 5px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.3); white-space: nowrap;"><i class="fas fa-sitemap"></i> {{ $odp->parentOdp->code }}</div>',
        iconSize: [100, 30],
        iconAnchor: [50, 30]
    });
    
    L.marker([{{ $odp->parentOdp->latitude }}, {{ $odp->parentOdp->longitude }}], {icon: parentIcon})
        .addTo(map)
        .bindPopup('<strong>Parent ODP: {{ $odp->parentOdp->code }}</strong><br>{{ $odp->parentOdp->name }}');
    
    // Draw line from Parent ODP to this ODP
    L.polyline([
        [{{ $odp->parentOdp->latitude }}, {{ $odp->parentOdp->longitude }}],
        [lat, lng]
    ], {color: '#ffc107', weight: 3, dashArray: '10, 5'}).addTo(map);
    
    // Fit bounds
    map.fitBounds([
        [lat, lng],
        [{{ $odp->parentOdp->latitude }}, {{ $odp->parentOdp->longitude }}]
    ], {padding: [50, 50]});
    @endif
    
    @if($odp->olt && !$odp->odc && $odp->olt->hasCoordinates())
    const oltIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background: #17a2b8; color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.3); white-space: nowrap;"><i class="fas fa-server"></i> {{ $odp->olt->name }}</div>',
        iconSize: [120, 30],
        iconAnchor: [60, 30]
    });
    
    L.marker([{{ $odp->olt->latitude }}, {{ $odp->olt->longitude }}], {icon: oltIcon})
        .addTo(map)
        .bindPopup('<strong>OLT: {{ $odp->olt->name }}</strong><br>PON Port: {{ $odp->olt_pon_port }}');
    
    // Draw line from OLT to ODP
    L.polyline([
        [{{ $odp->olt->latitude }}, {{ $odp->olt->longitude }}],
        [lat, lng]
    ], {color: '#17a2b8', weight: 3, dashArray: '10, 5'}).addTo(map);
    
    // Fit bounds
    map.fitBounds([
        [lat, lng],
        [{{ $odp->olt->latitude }}, {{ $odp->olt->longitude }}]
    ], {padding: [50, 50]});
    @endif
});
</script>
@endif
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
@endpush
