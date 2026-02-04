@extends('layouts.admin')

@section('title', 'Network Map')

@section('page-title', 'Network Map - Peta Jaringan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Network Map</li>
@endsection

@push('css')
<style>
    #networkMap { 
        height: calc(100vh - 280px); 
        min-height: 500px;
        border-radius: 5px;
        z-index: 1;
    }
    .custom-marker { 
        background: transparent; 
        border: none; 
    }
    .legend {
        background: white;
        padding: 10px;
        border-radius: 5px;
        box-shadow: 0 0 15px rgba(0,0,0,0.2);
    }
    .legend-item {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
    }
    .legend-color {
        width: 20px;
        height: 4px;
        margin-right: 8px;
        border-radius: 2px;
    }
    .legend-icon {
        width: 20px;
        text-align: center;
        margin-right: 8px;
    }
    .stats-card {
        transition: all 0.3s;
    }
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .map-control {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 1000;
        background: white;
        padding: 10px;
        border-radius: 5px;
        box-shadow: 0 0 15px rgba(0,0,0,0.2);
    }
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Superadmin POP Selector -->
        @if($popUsers && auth()->user()->hasRole('superadmin'))
        <div class="card card-outline card-info mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <i class="fas fa-user-shield text-info fa-lg"></i>
                        <strong class="ml-2">Mode Superadmin:</strong>
                    </div>
                    <div class="col">
                        <select class="form-control select2" id="selectPop" style="width: 100%;">
                            <option value="">-- Pilih POP --</option>
                            @foreach($popUsers as $pop)
                                <option value="{{ $pop->id }}" {{ $popId == $pop->id ? 'selected' : '' }}>
                                    {{ $pop->name }} ({{ $pop->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($popId)
        <!-- Statistics -->
        <div class="row" id="statsRow">
            <div class="col-lg-3 col-6">
                <div class="info-box stats-card">
                    <span class="info-box-icon bg-danger"><i class="fas fa-hdd"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">OLT</span>
                        <span class="info-box-number" id="statOlts">-</span>
                        <small class="text-muted"><span id="statOltsCoords">-</span> dengan koordinat</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box stats-card">
                    <span class="info-box-icon bg-info"><i class="fas fa-building"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">ODC</span>
                        <span class="info-box-number" id="statOdcs">-</span>
                        <small class="text-muted"><span id="statOdcsCoords">-</span> dengan koordinat</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box stats-card">
                    <span class="info-box-icon bg-success"><i class="fas fa-box"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">ODP</span>
                        <span class="info-box-number" id="statOdps">-</span>
                        <small class="text-muted"><span id="statOdpsCoords">-</span> dengan koordinat</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box stats-card">
                    <span class="info-box-icon bg-warning"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pelanggan</span>
                        <span class="info-box-number" id="statCustomers">-</span>
                        <small class="text-muted"><span id="statCustomersCoords">-</span> dengan koordinat</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map mr-2"></i>Peta Jaringan</h3>
                <div class="card-tools">
                    <div class="btn-group mr-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnRefresh">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info" id="btnFitBounds">
                            <i class="fas fa-expand"></i> Fit All
                        </button>
                    </div>
                    <div class="custom-control custom-switch d-inline-block ml-3">
                        <input type="checkbox" class="custom-control-input" id="showCustomers">
                        <label class="custom-control-label" for="showCustomers">Tampilkan Pelanggan</label>
                    </div>
                </div>
            </div>
            <div class="card-body p-0" style="position: relative;">
                <div id="networkMap"></div>
                <div class="loading-overlay" id="mapLoading">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-3x text-primary mb-2"></i>
                        <p>Memuat data peta...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Legenda</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <h6>Perangkat</h6>
                        <div class="legend-item">
                            <div class="legend-icon"><i class="fas fa-hdd text-danger"></i></div>
                            <span>OLT (Optical Line Terminal)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-icon"><i class="fas fa-building text-info"></i></div>
                            <span>ODC (Optical Distribution Cabinet)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-icon"><i class="fas fa-box text-success"></i></div>
                            <span>ODP (Optical Distribution Point)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-icon"><i class="fas fa-user text-warning"></i></div>
                            <span>Pelanggan</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <h6>Koneksi</h6>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #007bff;"></div>
                            <span>OLT → ODC</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #28a745;"></div>
                            <span>ODC → ODP</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #17a2b8;"></div>
                            <span>OLT → ODP (Direct)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #ffc107;"></div>
                            <span>ODP → ODP (Cascade)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #fd7e14;"></div>
                            <span>ODP → Pelanggan</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <h6>Status Perangkat</h6>
                        <div class="legend-item">
                            <span class="badge badge-success mr-2">●</span>
                            <span>Aktif/Online</span>
                        </div>
                        <div class="legend-item">
                            <span class="badge badge-warning mr-2">●</span>
                            <span>Maintenance</span>
                        </div>
                        <div class="legend-item">
                            <span class="badge badge-danger mr-2">●</span>
                            <span>Tidak Aktif/Offline</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <h6>Navigasi</h6>
                        <ul class="list-unstyled text-muted small">
                            <li><i class="fas fa-mouse mr-1"></i> Klik marker untuk detail</li>
                            <li><i class="fas fa-mouse mr-1"></i> Scroll untuk zoom</li>
                            <li><i class="fas fa-hand-paper mr-1"></i> Drag untuk geser peta</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-map fa-4x text-muted mb-3"></i>
                <h4>Silakan Pilih POP</h4>
                <p class="text-muted">Pilih POP terlebih dahulu untuk melihat peta jaringan</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('js')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
<script>
$(function() {
    // POP Selector
    $('#selectPop').on('change', function() {
        const popId = $(this).val();
        if (popId) {
            window.location.href = '{{ route("admin.network-map.index") }}?pop_id=' + popId;
        }
    });

    @if($popId)
    // Initialize map
    const defaultCenter = [{{ $defaultCenter['lat'] }}, {{ $defaultCenter['lng'] }}];
    const map = L.map('networkMap').setView(defaultCenter, 13);
    
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
    hybridLayer.addTo(map);
    
    // Layer control for base maps
    L.control.layers({
        "Hybrid": hybridLayer,
        "Satelit": satelliteLayer,
        "Peta": osmLayer
    }, null, { position: 'topright' }).addTo(map);
    
    // Layer groups
    const routerLayer = L.layerGroup().addTo(map);
    const oltLayer = L.layerGroup().addTo(map);
    const odcLayer = L.layerGroup().addTo(map);
    const odpLayer = L.layerGroup().addTo(map);
    const customerLayer = L.layerGroup();
    const lineLayer = L.layerGroup().addTo(map);
    
    // Icon definitions
    const icons = {
        router: function(status) {
            const color = status === 'online' ? 'text-primary' : 'text-secondary';
            return L.divIcon({
                className: 'custom-marker',
                html: `<i class="fas fa-server fa-2x ${color}"></i>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });
        },
        olt: function(status) {
            const color = status === 'active' ? 'text-danger' : (status === 'maintenance' ? 'text-warning' : 'text-secondary');
            return L.divIcon({
                className: 'custom-marker',
                html: `<i class="fas fa-hdd fa-2x ${color}"></i>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });
        },
        odc: function(status) {
            const color = status === 'active' ? 'text-info' : (status === 'maintenance' ? 'text-warning' : 'text-secondary');
            return L.divIcon({
                className: 'custom-marker',
                html: `<i class="fas fa-building fa-2x ${color}"></i>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });
        },
        odp: function(status) {
            const color = status === 'active' ? 'text-success' : (status === 'maintenance' ? 'text-warning' : 'text-secondary');
            return L.divIcon({
                className: 'custom-marker',
                html: `<i class="fas fa-box fa-lg ${color}"></i>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });
        },
        customer: function(status) {
            const color = status === 'active' ? 'text-warning' : 'text-secondary';
            return L.divIcon({
                className: 'custom-marker',
                html: `<i class="fas fa-user ${color}"></i>`,
                iconSize: [15, 15],
                iconAnchor: [7, 7]
            });
        }
    };
    
    let allBounds = [];
    
    // Load map data
    function loadMapData() {
        $('#mapLoading').show();
        
        const showCustomers = $('#showCustomers').is(':checked');
        
        $.get('{{ route("admin.network-map.data") }}', {
            pop_id: '{{ $popId }}',
            show_customers: showCustomers ? 1 : 0
        }, function(data) {
            // Clear layers
            routerLayer.clearLayers();
            oltLayer.clearLayers();
            odcLayer.clearLayers();
            odpLayer.clearLayers();
            customerLayer.clearLayers();
            lineLayer.clearLayers();
            allBounds = [];
            
            // Add routers
            data.routers.forEach(function(router) {
                const marker = L.marker([router.lat, router.lng], {
                    icon: icons.router(router.status)
                });
                marker.bindPopup(`
                    <div style="min-width: 200px;">
                        <h6><i class="fas fa-server mr-1"></i> ${router.name}</h6>
                        <table class="table table-sm mb-0">
                            <tr><td>Identity</td><td>${router.identity || '-'}</td></tr>
                            <tr><td>Status</td><td><span class="badge badge-${router.status === 'online' ? 'success' : 'secondary'}">${router.status}</span></td></tr>
                        </table>
                    </div>
                `);
                routerLayer.addLayer(marker);
                allBounds.push([router.lat, router.lng]);
            });
            
            // Add OLTs
            data.olts.forEach(function(olt) {
                const marker = L.marker([olt.lat, olt.lng], {
                    icon: icons.olt(olt.status)
                });
                marker.bindPopup(`
                    <div style="min-width: 200px;">
                        <h6><i class="fas fa-hdd mr-1"></i> ${olt.name}</h6>
                        <p class="mb-1"><code>${olt.code}</code></p>
                        <table class="table table-sm mb-0">
                            <tr><td>Brand</td><td>${olt.brand || '-'}</td></tr>
                            <tr><td>Status</td><td><span class="badge badge-${olt.status === 'active' ? 'success' : (olt.status === 'maintenance' ? 'warning' : 'secondary')}">${olt.status}</span></td></tr>
                        </table>
                        <a href="/admin/olts/${olt.id}" class="btn btn-xs btn-info mt-2">Detail</a>
                    </div>
                `);
                oltLayer.addLayer(marker);
                allBounds.push([olt.lat, olt.lng]);
            });
            
            // Add ODCs
            data.odcs.forEach(function(odc) {
                const marker = L.marker([odc.lat, odc.lng], {
                    icon: icons.odc(odc.status)
                });
                marker.bindPopup(`
                    <div style="min-width: 200px;">
                        <h6><i class="fas fa-building mr-1"></i> ${odc.code}</h6>
                        <p class="mb-1">${odc.name}</p>
                        <table class="table table-sm mb-0">
                            <tr><td>Port</td><td>${odc.used_ports}/${odc.total_ports}</td></tr>
                            <tr><td>Status</td><td><span class="badge badge-${odc.status === 'active' ? 'success' : (odc.status === 'maintenance' ? 'warning' : 'secondary')}">${odc.status}</span></td></tr>
                        </table>
                        <a href="/admin/odcs/${odc.id}" class="btn btn-xs btn-info mt-2">Detail</a>
                    </div>
                `);
                odcLayer.addLayer(marker);
                allBounds.push([odc.lat, odc.lng]);
            });
            
            // Add ODPs
            data.odps.forEach(function(odp) {
                const marker = L.marker([odp.lat, odp.lng], {
                    icon: icons.odp(odp.status)
                });
                marker.bindPopup(`
                    <div style="min-width: 200px;">
                        <h6><i class="fas fa-box mr-1"></i> ${odp.code}</h6>
                        <p class="mb-1">${odp.name}</p>
                        <table class="table table-sm mb-0">
                            <tr><td>Port</td><td>${odp.used_ports}/${odp.total_ports}</td></tr>
                            <tr><td>Status</td><td><span class="badge badge-${odp.status === 'active' ? 'success' : (odp.status === 'maintenance' ? 'warning' : 'secondary')}">${odp.status}</span></td></tr>
                        </table>
                        <a href="/admin/odps/${odp.id}" class="btn btn-xs btn-info mt-2">Detail</a>
                    </div>
                `);
                odpLayer.addLayer(marker);
                allBounds.push([odp.lat, odp.lng]);
            });
            
            // Add customers if enabled
            if (showCustomers) {
                data.customers.forEach(function(customer) {
                    const marker = L.marker([customer.lat, customer.lng], {
                        icon: icons.customer(customer.status)
                    });
                    marker.bindPopup(`
                        <div style="min-width: 180px;">
                            <h6><i class="fas fa-user mr-1"></i> ${customer.name}</h6>
                            <p class="mb-1"><code>${customer.customer_id}</code></p>
                            <span class="badge badge-${customer.status === 'active' ? 'success' : 'secondary'}">${customer.status}</span>
                            <a href="/admin/customers/${customer.id}" class="btn btn-xs btn-info mt-2 d-block">Detail</a>
                        </div>
                    `);
                    customerLayer.addLayer(marker);
                    allBounds.push([customer.lat, customer.lng]);
                });
                customerLayer.addTo(map);
            } else {
                map.removeLayer(customerLayer);
            }
            
            // Draw lines
            data.lines.forEach(function(line) {
                L.polyline([
                    [line.from.lat, line.from.lng],
                    [line.to.lat, line.to.lng]
                ], {
                    color: line.color,
                    weight: line.weight,
                    opacity: 0.7
                }).addTo(lineLayer);
            });
            
            $('#mapLoading').hide();
        }).fail(function() {
            $('#mapLoading').hide();
            Swal.fire('Error', 'Gagal memuat data peta', 'error');
        });
    }
    
    // Load statistics
    function loadStats() {
        $.get('{{ route("admin.network-map.stats") }}', {
            pop_id: '{{ $popId }}'
        }, function(data) {
            $('#statOlts').text(data.olts ? data.olts.total : 0);
            $('#statOltsCoords').text(data.olts ? data.olts.with_coords : 0);
            $('#statOdcs').text(data.odcs.total);
            $('#statOdcsCoords').text(data.odcs.with_coords);
            $('#statOdps').text(data.odps.total);
            $('#statOdpsCoords').text(data.odps.with_coords);
            $('#statCustomers').text(data.customers.total);
            $('#statCustomersCoords').text(data.customers.with_coords);
        });
    }
    
    // Initial load
    loadMapData();
    loadStats();
    
    // Refresh button
    $('#btnRefresh').on('click', function() {
        loadMapData();
        loadStats();
    });
    
    // Fit bounds button
    $('#btnFitBounds').on('click', function() {
        if (allBounds.length > 0) {
            map.fitBounds(allBounds, {padding: [50, 50]});
        }
    });
    
    // Toggle customers
    $('#showCustomers').on('change', function() {
        loadMapData();
    });
    @endif
});
</script>
@endpush
