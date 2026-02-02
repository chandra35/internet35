@extends('layouts.admin')

@section('title', 'Detail ODP')

@section('page-title', 'Detail ODP: ' . $odp->code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.odps.index', ['pop_id' => $odp->pop_id]) }}">ODP</a></li>
    <li class="breadcrumb-item active">Detail</li>
@endsection

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map { height: 300px; border-radius: 5px; }
    .custom-marker { background: transparent; border: none; }
</style>
@endsection

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
                        <td><strong>ODC</strong></td>
                        <td>
                            @if($odp->odc)
                            <a href="{{ route('admin.odcs.show', $odp->odc) }}">
                                {{ $odp->odc->code }} - {{ $odp->odc->name }}
                            </a>
                            <span class="badge badge-info ml-2">Port {{ $odp->odc_port }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Router</strong></td>
                        <td>
                            @if($odp->odc && $odp->odc->router)
                            {{ $odp->odc->router->name }}
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
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

@section('scripts')
@if($odp->hasCoordinates())
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(function() {
    const lat = {{ $odp->latitude }};
    const lng = {{ $odp->longitude }};
    
    const map = L.map('map').setView([lat, lng], 16);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // ODP marker (green)
    const odpIcon = L.divIcon({
        className: 'custom-marker',
        html: '<i class="fas fa-box fa-2x text-success"></i>',
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });
    
    L.marker([lat, lng], {icon: odpIcon})
        .addTo(map)
        .bindPopup('<strong>{{ $odp->code }}</strong><br>{{ $odp->name }}');
    
    // Add ODC marker and line if available
    @if($odp->odc && $odp->odc->hasCoordinates())
    const odcIcon = L.divIcon({
        className: 'custom-marker',
        html: '<i class="fas fa-server fa-2x text-primary"></i>',
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });
    
    L.marker([{{ $odp->odc->latitude }}, {{ $odp->odc->longitude }}], {icon: odcIcon})
        .addTo(map)
        .bindPopup('<strong>{{ $odp->odc->code }}</strong><br>{{ $odp->odc->name }}');
    
    // Draw line from ODC to ODP
    L.polyline([
        [{{ $odp->odc->latitude }}, {{ $odp->odc->longitude }}],
        [lat, lng]
    ], {color: '#28a745', weight: 2}).addTo(map);
    
    // Fit bounds to show both markers
    map.fitBounds([
        [lat, lng],
        [{{ $odp->odc->latitude }}, {{ $odp->odc->longitude }}]
    ], {padding: [50, 50]});
    @endif
});
</script>
@endif
@endsection
