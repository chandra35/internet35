@extends('layouts.admin')

@section('title', 'Kelola Router - ' . $router->name)
@section('page-title', $router->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.routers.index') }}">Router</a></li>
    <li class="breadcrumb-item active">{{ $router->name }}</li>
@endsection

@push('css')
<style>
    .sidebar-menu {
        background: #2c3e50;
        border-radius: 0.5rem;
    }
    .sidebar-menu .menu-group {
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .sidebar-menu .menu-header {
        color: #fff;
        padding: 10px 15px;
        cursor: pointer;
        font-weight: 500;
        font-size: 0.85rem;
        background: rgba(0,0,0,0.2);
        transition: background 0.2s;
    }
    .sidebar-menu .menu-header:hover {
        background: rgba(0,0,0,0.3);
    }
    .sidebar-menu .menu-header i:first-child {
        width: 20px;
        margin-right: 5px;
    }
    .sidebar-menu .menu-header .fa-chevron-down {
        font-size: 0.7rem;
        transition: transform 0.2s;
    }
    .sidebar-menu .menu-header[aria-expanded="false"] .fa-chevron-down {
        transform: rotate(-90deg);
    }
    .sidebar-menu .nav-link {
        color: #bdc3c7;
        padding: 8px 15px 8px 35px;
        font-size: 0.8rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .sidebar-menu .nav-link:hover,
    .sidebar-menu .nav-link.active {
        background: rgba(52, 152, 219, 0.3);
        color: #fff;
    }
    .sidebar-menu .nav-link i {
        width: 20px;
        font-size: 0.75rem;
    }
    .data-table {
        font-size: 0.875rem;
    }
    .data-table th {
        white-space: nowrap;
        background: #f8f9fa;
    }
    .data-table td {
        vertical-align: middle;
    }
    .btn-action {
        padding: 2px 6px;
        font-size: 0.75rem;
    }
    .status-badge {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }
    .status-badge.running { background: #28a745; }
    .status-badge.disabled { background: #dc3545; }
    .status-badge.dynamic { background: #17a2b8; }
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
        z-index: 100;
    }
    .resource-card {
        transition: all 0.3s;
        overflow: hidden;
    }
    .resource-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .interface-item {
        cursor: pointer;
        transition: background 0.2s;
    }
    .interface-item:hover {
        background: #f8f9fa;
    }
    .interface-item.disabled {
        opacity: 0.6;
    }
    .clickable-row {
        cursor: pointer;
    }
    .clickable-row:hover {
        background-color: #e9ecef !important;
    }
    .edit-inline {
        cursor: pointer;
        border-bottom: 1px dashed #007bff;
    }
    .edit-inline:hover {
        background: #e9ecef;
    }
    /* Progress bar styles */
    .progress-vertical {
        width: 100%;
        height: 6px;
        border-radius: 3px;
    }
    .resource-progress {
        height: 8px;
        border-radius: 4px;
        margin-top: 8px;
        background: rgba(0,0,0,0.1);
    }
    .resource-progress .progress-bar {
        transition: width 0.5s ease, background-color 0.3s ease;
    }
    .stat-value {
        font-size: 1.5rem;
        font-weight: 600;
        line-height: 1.2;
    }
    .stat-value-sm {
        font-size: 1.1rem;
        font-weight: 600;
        line-height: 1.2;
    }
    .stat-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .resource-item {
        border-right: 1px solid #eee;
        padding: 0 12px;
    }
    .resource-item:last-child {
        border-right: none;
    }
    .resource-progress-sm {
        height: 5px;
        border-radius: 3px;
        margin-top: 5px;
        background: rgba(0,0,0,0.1);
    }
    .resource-progress-sm .progress-bar {
        transition: width 0.5s ease, background-color 0.3s ease;
    }
    @media (max-width: 767px) {
        .resource-item {
            border-right: none;
            border-bottom: 1px solid #eee;
            padding: 10px 12px;
            margin-bottom: 5px;
        }
        .resource-item:last-child {
            border-bottom: none;
        }
    }
    /* Traffic card */
    .traffic-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .traffic-card .traffic-value {
        font-size: 1.1rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .traffic-card .traffic-rate {
        font-size: 0.7rem;
        opacity: 0.8;
    }
    .blink-dot {
        animation: blink 1s infinite;
    }
    @keyframes blink {
        0%, 50% { opacity: 1; }
        51%, 100% { opacity: 0.3; }
    }
    .isp-badge {
        background: rgba(255,255,255,0.2);
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.7rem;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .traffic-card .fa-arrow-up { color: #4ade80; }
    .traffic-card .fa-arrow-down { color: #60a5fa; }
    /* Ping card styles */
    .ping-card {
        border-left: 3px solid #6c757d;
    }
    .ping-card.ping-good { border-left-color: #28a745; }
    .ping-card.ping-medium { border-left-color: #ffc107; }
    .ping-card.ping-bad { border-left-color: #dc3545; }
    .ping-card.ping-timeout { border-left-color: #6c757d; }
    .ping-stat {
        text-align: center;
    }
    .ping-label {
        display: block;
        font-size: 0.7rem;
        color: #6c757d;
        text-transform: uppercase;
    }
    .ping-value {
        display: block;
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
    }
    .ping-detail {
        border-top: 1px solid #eee;
        padding-top: 8px;
    }
</style>
@endpush

@section('content')
@if(!$connected)
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Koneksi Gagal!</strong> {{ $error ?? 'Tidak dapat terhubung ke router.' }}
        <a href="{{ route('admin.routers.manage', $router) }}" class="btn btn-sm btn-outline-danger ml-3">
            <i class="fas fa-sync-alt"></i> Coba Lagi
        </a>
    </div>
@else
    <!-- Router Info Header -->
    <div class="row mb-3">
        <!-- Router Identity -->
        <div class="col-xl-2 col-lg-3 col-md-6 mb-3">
            <div class="card resource-card bg-gradient-primary text-white h-100">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="stat-label text-white-50">Router</span>
                            <h5 class="mb-0 mt-1">{{ $router->identity }}</h5>
                            <small class="text-white-50">{{ $router->board_name }}</small>
                        </div>
                        <div>
                            <i class="fas fa-server fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="badge badge-light">ROS {{ $router->ros_version }}</span>
                        <span class="badge badge-{{ $router->status === 'online' ? 'success' : 'danger' }}">
                            <i class="fas fa-circle blink-dot mr-1" style="font-size: 6px;"></i>{{ ucfirst($router->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Resources (CPU, Memory, Storage, Uptime combined) -->
        <div class="col-xl-7 col-lg-6 col-md-6 mb-3">
            <div class="card resource-card h-100">
                <div class="card-body py-3">
                    <div class="row">
                        <!-- CPU Load -->
                        <div class="col-md-3 col-6 resource-item">
                            <span class="stat-label">CPU Load</span>
                            <div class="d-flex align-items-center mt-1">
                                <span class="stat-value-sm" id="cpuLoad">-</span>
                                <small class="text-muted ml-2" id="cpuInfo">{{ $router->cpu }}</small>
                            </div>
                            <div class="progress resource-progress-sm">
                                <div class="progress-bar bg-info" id="cpuProgress" style="width: 0%"></div>
                            </div>
                            <small class="text-muted d-block" id="cpuFreq"></small>
                        </div>
                        <!-- Memory -->
                        <div class="col-md-3 col-6 resource-item">
                            <span class="stat-label">Memory</span>
                            <div class="d-flex align-items-center mt-1">
                                <span class="stat-value-sm" id="memoryPercent">{{ $router->memory_usage_percent }}%</span>
                                <small class="text-muted ml-2" id="memoryInfo">{{ $router->formatted_free_memory }}</small>
                            </div>
                            <div class="progress resource-progress-sm">
                                <div class="progress-bar bg-warning" id="memoryProgress" style="width: {{ $router->memory_usage_percent }}%"></div>
                            </div>
                            <small class="text-muted d-block" id="memoryTotal">Total: {{ $router->formatted_total_memory }}</small>
                        </div>
                        <!-- Storage -->
                        <div class="col-md-3 col-6 resource-item">
                            <span class="stat-label">Storage</span>
                            <div class="d-flex align-items-center mt-1">
                                <span class="stat-value-sm" id="hddPercent">{{ $router->hdd_usage_percent }}%</span>
                                <small class="text-muted ml-2" id="hddInfo">{{ $router->formatted_free_hdd }}</small>
                            </div>
                            <div class="progress resource-progress-sm">
                                <div class="progress-bar bg-success" id="hddProgress" style="width: {{ $router->hdd_usage_percent }}%"></div>
                            </div>
                            <small class="text-muted d-block" id="hddTotal">Total: {{ $router->formatted_total_hdd }}</small>
                        </div>
                        <!-- Uptime -->
                        <div class="col-md-3 col-6 resource-item">
                            <span class="stat-label">Uptime</span>
                            <div class="d-flex align-items-center mt-1">
                                <span class="stat-value-sm" id="uptime">{{ $router->uptime }}</span>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted d-block" id="archInfo"><i class="fas fa-microchip mr-1"></i>{{ $router->architecture }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gateway Traffic -->
        <div class="col-xl-3 col-lg-3 col-md-12 mb-3">
            <div class="card resource-card traffic-card h-100">
                <div class="card-body py-3">
                    <span class="stat-label text-white-50">Gateway Traffic</span>
                    <div class="row mt-2">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-arrow-up mr-2" style="color: #4ade80;"></i>
                                <div>
                                    <div class="traffic-value" id="gwTxRate">0 bps</div>
                                    <div class="traffic-rate">TX <span id="gwTxTotal">0 B</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-arrow-down mr-2" style="color: #60a5fa;"></i>
                                <div>
                                    <div class="traffic-value" id="gwRxRate">0 bps</div>
                                    <div class="traffic-rate">RX <span id="gwRxTotal">0 B</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 pt-2" style="border-top: 1px solid rgba(255,255,255,0.2); font-size: 0.7rem;">
                        <div class="text-white-50"><i class="fas fa-ethernet mr-1"></i>Interface: <span id="gwInterface" class="text-white">-</span></div>
                        <div class="text-white-50"><i class="fas fa-globe mr-1"></i>Public IP: <span id="publicIp" class="text-white">-</span></div>
                        <div class="text-white-50"><i class="fas fa-building mr-1"></i>ISP: <span id="ispName" class="text-white">Detecting...</span></div>
                        <div class="text-white-50" id="ispLocationRow" style="display:none;"><i class="fas fa-map-marker-alt mr-1"></i><span id="ispLocation"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ping Statistics Row -->
    <div class="row mb-3">
        <!-- Ping 8.8.8.8 (Google DNS) -->
        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
            <div class="card resource-card ping-card h-100">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="stat-label">Ping 8.8.8.8</span>
                        <span class="badge badge-secondary" id="ping8888Status">-</span>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6">
                            <div class="ping-stat">
                                <span class="ping-label">Latency</span>
                                <span class="ping-value" id="ping8888Avg">-</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="ping-stat">
                                <span class="ping-label">Jitter</span>
                                <span class="ping-value" id="ping8888Jitter">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="ping-detail mt-2">
                        <small class="text-muted">
                            Min: <span id="ping8888Min">-</span> | 
                            Max: <span id="ping8888Max">-</span> | 
                            Loss: <span id="ping8888Loss">-</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Menu -->
        <div class="col-md-3 col-lg-2">
            <div class="sidebar-menu" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                <nav class="nav flex-column">
                    <!-- Interfaces -->
                    <div class="menu-group">
                        <div class="menu-header" data-toggle="collapse" data-target="#menuInterfaces">
                            <i class="fas fa-ethernet"></i> Interfaces
                            <i class="fas fa-chevron-down float-right"></i>
                        </div>
                        <div class="collapse show" id="menuInterfaces">
                            <a class="nav-link active" href="#" data-section="interfaces">
                                <i class="fas fa-list"></i> Interface List
                            </a>
                            <a class="nav-link" href="#" data-section="ethernets">
                                <i class="fas fa-plug"></i> Ethernet
                            </a>
                            <a class="nav-link" href="#" data-section="bridges">
                                <i class="fas fa-project-diagram"></i> Bridge
                            </a>
                            <a class="nav-link" href="#" data-section="bridge-ports">
                                <i class="fas fa-sitemap"></i> Bridge Ports
                            </a>
                            <a class="nav-link" href="#" data-section="vlans">
                                <i class="fas fa-layer-group"></i> VLAN
                            </a>
                        </div>
                    </div>

                    <!-- Wireless -->
                    <div class="menu-group">
                        <div class="menu-header" data-toggle="collapse" data-target="#menuWireless">
                            <i class="fas fa-wifi"></i> Wireless
                            <i class="fas fa-chevron-down float-right"></i>
                        </div>
                        <div class="collapse" id="menuWireless">
                            <a class="nav-link" href="#" data-section="wireless">
                                <i class="fas fa-broadcast-tower"></i> WiFi Interfaces
                            </a>
                            <a class="nav-link" href="#" data-section="wireless-registration">
                                <i class="fas fa-users"></i> Registration
                            </a>
                        </div>
                    </div>

                    <!-- IP -->
                    <div class="menu-group">
                        <div class="menu-header" data-toggle="collapse" data-target="#menuIp">
                            <i class="fas fa-network-wired"></i> IP
                            <i class="fas fa-chevron-down float-right"></i>
                        </div>
                        <div class="collapse show" id="menuIp">
                            <a class="nav-link" href="#" data-section="ip-addresses">
                                <i class="fas fa-map-marker-alt"></i> Addresses
                            </a>
                            <a class="nav-link" href="#" data-section="routes">
                                <i class="fas fa-route"></i> Routes
                            </a>
                            <a class="nav-link" href="#" data-section="arp">
                                <i class="fas fa-table"></i> ARP
                            </a>
                            <a class="nav-link" href="#" data-section="dns">
                                <i class="fas fa-globe"></i> DNS
                            </a>
                            <a class="nav-link" href="#" data-section="dhcp-clients">
                                <i class="fas fa-laptop"></i> DHCP Client
                            </a>
                            <a class="nav-link" href="#" data-section="dhcp-servers">
                                <i class="fas fa-server"></i> DHCP Server
                            </a>
                            <a class="nav-link" href="#" data-section="dhcp-leases">
                                <i class="fas fa-address-card"></i> DHCP Leases
                            </a>
                            <a class="nav-link" href="#" data-section="ip-pools">
                                <i class="fas fa-swimming-pool"></i> Pool
                            </a>
                        </div>
                    </div>

                    <!-- Firewall -->
                    <div class="menu-group">
                        <div class="menu-header" data-toggle="collapse" data-target="#menuFirewall">
                            <i class="fas fa-shield-alt"></i> Firewall
                            <i class="fas fa-chevron-down float-right"></i>
                        </div>
                        <div class="collapse" id="menuFirewall">
                            <a class="nav-link" href="#" data-section="firewall-filter">
                                <i class="fas fa-filter"></i> Filter Rules
                            </a>
                            <a class="nav-link" href="#" data-section="firewall-nat">
                                <i class="fas fa-random"></i> NAT
                            </a>
                            <a class="nav-link" href="#" data-section="firewall-mangle">
                                <i class="fas fa-tags"></i> Mangle
                            </a>
                            <a class="nav-link" href="#" data-section="firewall-address-list">
                                <i class="fas fa-list-alt"></i> Address Lists
                            </a>
                        </div>
                    </div>

                    <!-- PPP -->
                    <div class="menu-group">
                        <div class="menu-header" data-toggle="collapse" data-target="#menuPpp">
                            <i class="fas fa-user-lock"></i> PPP
                            <i class="fas fa-chevron-down float-right"></i>
                        </div>
                        <div class="collapse show" id="menuPpp">
                            <a class="nav-link" href="#" data-section="pppoe-clients">
                                <i class="fas fa-plug"></i> PPPoE Client
                            </a>
                            <a class="nav-link" href="#" data-section="ppp-secrets">
                                <i class="fas fa-key"></i> Secrets
                            </a>
                            <a class="nav-link" href="#" data-section="ppp-active">
                                <i class="fas fa-user-check"></i> Active
                            </a>
                            <a class="nav-link" href="#" data-section="ppp-profiles">
                                <i class="fas fa-id-card"></i> Profiles
                            </a>
                        </div>
                    </div>

                    <!-- Hotspot -->
                    <div class="menu-group">
                        <div class="menu-header" data-toggle="collapse" data-target="#menuHotspot">
                            <i class="fas fa-fire"></i> Hotspot
                            <i class="fas fa-chevron-down float-right"></i>
                        </div>
                        <div class="collapse" id="menuHotspot">
                            <a class="nav-link" href="#" data-section="hotspot-users">
                                <i class="fas fa-users"></i> Users
                            </a>
                            <a class="nav-link" href="#" data-section="hotspot-active">
                                <i class="fas fa-user-clock"></i> Active
                            </a>
                        </div>
                    </div>

                    <!-- Queues -->
                    <div class="menu-group">
                        <div class="menu-header" data-toggle="collapse" data-target="#menuQueues">
                            <i class="fas fa-tachometer-alt"></i> Queues
                            <i class="fas fa-chevron-down float-right"></i>
                        </div>
                        <div class="collapse" id="menuQueues">
                            <a class="nav-link" href="#" data-section="queues">
                                <i class="fas fa-stream"></i> Simple Queues
                            </a>
                        </div>
                    </div>

                    <!-- System -->
                    <div class="menu-group">
                        <div class="menu-header" data-toggle="collapse" data-target="#menuSystem">
                            <i class="fas fa-cog"></i> System
                            <i class="fas fa-chevron-down float-right"></i>
                        </div>
                        <div class="collapse" id="menuSystem">
                            <a class="nav-link" href="#" data-section="packages">
                                <i class="fas fa-box"></i> Packages
                            </a>
                            <a class="nav-link" href="#" data-section="scheduler">
                                <i class="fas fa-clock"></i> Scheduler
                            </a>
                            <a class="nav-link" href="#" data-section="scripts">
                                <i class="fas fa-code"></i> Scripts
                            </a>
                            <a class="nav-link" href="#" data-section="system-users">
                                <i class="fas fa-user-cog"></i> Users
                            </a>
                            <a class="nav-link" href="#" data-section="logs">
                                <i class="fas fa-file-alt"></i> Logs
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>

        <!-- Content Area -->
        <div class="col-md-9 col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0" id="sectionTitle">
                        <i class="fas fa-ethernet mr-1"></i> Interfaces
                    </h3>
                    <div id="sectionActions">
                        <button type="button" class="btn btn-sm btn-primary" id="btnAdd" style="display:none;">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                        <button type="button" class="btn btn-sm btn-info" id="btnRefresh">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body position-relative" id="contentArea" style="min-height: 400px;">
                    <div class="loading-overlay" id="loadingOverlay">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                            <p class="mt-2">Memuat data...</p>
                        </div>
                    </div>
                    <div id="dataContent"></div>
                </div>
            </div>
        </div>
    </div>
@endif

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalTitle">Edit</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="editModalBody">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
const routerId = '{{ $router->id }}';
const routerUrl = '{{ url("admin/routers") }}/' + routerId;
let currentSection = 'interfaces';
let currentData = [];

$(document).ready(function() {
    @if($connected)
    // Load initial section
    loadSection('interfaces');

    // Section navigation
    $('.sidebar-menu .nav-link').on('click', function(e) {
        e.preventDefault();
        const section = $(this).data('section');
        
        $('.sidebar-menu .nav-link').removeClass('active');
        $(this).addClass('active');
        
        loadSection(section);
    });

    // Refresh button
    $('#btnRefresh').on('click', function() {
        loadSection(currentSection);
    });

    // Auto refresh resource every 10 seconds (reduced from 5s)
    setInterval(refreshResource, 10000);
    
    // Refresh gateway traffic every 3 seconds (reduced from 2s)
    refreshGatewayTraffic();
    setInterval(refreshGatewayTraffic, 3000);
    
    // Refresh ping statistics every 15 seconds (reduced from 10s)
    refreshAllPings();
    setInterval(refreshAllPings, 15000);
    @endif
});

// Ping targets configuration
const pingTargets = [
    { id: '8888', target: '8.8.8.8', name: 'Google DNS' }
];

// Store previous traffic bytes for rate calculation
let prevGwTraffic = { tx: 0, rx: 0, time: Date.now() };
let gatewayInterface = null;
let publicIpFetched = false;
let ispInfo = null;

function refreshAllPings() {
    pingTargets.forEach((target, index) => {
        // Stagger the ping requests to avoid overloading the router
        setTimeout(() => {
            refreshPing(target.id, target.target);
        }, index * 1000); // Increased from 500ms to 1s between pings
    });
}

function refreshPing(id, target) {
    $.get(routerUrl + '/data?type=ping&target=' + encodeURIComponent(target) + '&count=3', function(response) {
        const card = $(`#ping${id}Avg`).closest('.ping-card');
        
        if (response.success && response.data) {
            const data = response.data;
            
            // Update values
            if (data.avg !== null) {
                $(`#ping${id}Avg`).text(data.avg + ' ms');
                $(`#ping${id}Jitter`).text(data.jitter + ' ms');
                $(`#ping${id}Min`).text(data.min + ' ms');
                $(`#ping${id}Max`).text(data.max + ' ms');
                $(`#ping${id}Loss`).text(data.loss + '%');
                
                // Update status badge and card color
                let statusClass = 'success';
                let statusText = 'Good';
                let cardClass = 'ping-good';
                
                if (data.loss >= 50) {
                    statusClass = 'danger';
                    statusText = 'Bad';
                    cardClass = 'ping-bad';
                } else if (data.avg > 100 || data.loss > 0) {
                    statusClass = 'warning';
                    statusText = 'Medium';
                    cardClass = 'ping-medium';
                } else if (data.avg > 50) {
                    statusClass = 'warning';
                    statusText = 'Medium';
                    cardClass = 'ping-medium';
                }
                
                $(`#ping${id}Status`).removeClass('badge-secondary badge-success badge-warning badge-danger')
                    .addClass('badge-' + statusClass).text(statusText);
                card.removeClass('ping-good ping-medium ping-bad ping-timeout').addClass(cardClass);
            } else {
                // Timeout
                $(`#ping${id}Avg`).text('Timeout');
                $(`#ping${id}Jitter`).text('-');
                $(`#ping${id}Min`).text('-');
                $(`#ping${id}Max`).text('-');
                $(`#ping${id}Loss`).text('100%');
                $(`#ping${id}Status`).removeClass('badge-secondary badge-success badge-warning badge-danger')
                    .addClass('badge-danger').text('Timeout');
                card.removeClass('ping-good ping-medium ping-bad ping-timeout').addClass('ping-timeout');
            }
        } else {
            // Error
            $(`#ping${id}Status`).removeClass('badge-secondary badge-success badge-warning badge-danger')
                .addClass('badge-secondary').text('Error');
        }
    }).fail(function() {
        $(`#ping${id}Status`).removeClass('badge-secondary badge-success badge-warning badge-danger')
            .addClass('badge-secondary').text('Error');
    });
}

function refreshResource() {
    $.get(routerUrl + '/data?type=resource', function(response) {
        if (response.success && response.data) {
            const data = response.data;
            const cpuLoad = parseInt(data['cpu-load']) || 0;
            
            // CPU
            $('#cpuLoad').text(cpuLoad + '%');
            $('#cpuProgress').css('width', cpuLoad + '%');
            
            // Change progress bar color based on load
            const cpuBar = $('#cpuProgress');
            cpuBar.removeClass('bg-info bg-warning bg-danger');
            if (cpuLoad > 80) cpuBar.addClass('bg-danger');
            else if (cpuLoad > 50) cpuBar.addClass('bg-warning');
            else cpuBar.addClass('bg-info');
            
            $('#cpuFreq').text(data['cpu-frequency'] ? data['cpu-frequency'] + ' MHz' : '');
            
            // Memory
            if (data['free-memory'] && data['total-memory']) {
                const freeMem = parseInt(data['free-memory']);
                const totalMem = parseInt(data['total-memory']);
                const usedPercent = Math.round((1 - freeMem / totalMem) * 100);
                
                $('#memoryPercent').text(usedPercent + '%');
                $('#memoryProgress').css('width', usedPercent + '%');
                $('#memoryInfo').text(formatBytes(freeMem) + ' free');
                
                // Change progress bar color based on usage
                const memBar = $('#memoryProgress');
                memBar.removeClass('bg-warning bg-danger bg-success');
                if (usedPercent > 85) memBar.addClass('bg-danger');
                else if (usedPercent > 60) memBar.addClass('bg-warning');
                else memBar.addClass('bg-success');
            }
            
            // HDD
            if (data['free-hdd-space'] && data['total-hdd-space']) {
                const freeHdd = parseInt(data['free-hdd-space']);
                const totalHdd = parseInt(data['total-hdd-space']);
                const usedPercent = Math.round((1 - freeHdd / totalHdd) * 100);
                
                $('#hddPercent').text(usedPercent + '%');
                $('#hddProgress').css('width', usedPercent + '%');
                $('#hddInfo').text(formatBytes(freeHdd) + ' free');
            }
            
            // Uptime
            $('#uptime').text(data['uptime']);
        }
    });
}

function refreshGatewayTraffic() {
    $.get(routerUrl + '/data?type=gateway-traffic', function(response) {
        if (response.success && response.data) {
            const data = response.data;
            
            if (data.gateway || data.interface) {
                gatewayInterface = data.interface;
                $('#gwInterface').text(data.interface || 'Unknown');
                
                // Check if gateway is a private IP
                const gwIp = data.gateway;
                const isPrivateIp = gwIp && isPrivateIpAddress(gwIp);
                
                if (isPrivateIp || !gwIp) {
                    // Gateway is local IP, need to fetch public IP
                    if (!publicIpFetched) {
                        $('#ispName').text('Detecting...');
                        $('#publicIp').text('Detecting...');
                        fetchPublicIpAndIsp();
                    }
                } else {
                    // Gateway is already a public IP
                    $('#publicIp').text(gwIp);
                    if (!publicIpFetched) {
                        lookupIsp(gwIp);
                    }
                }
                
                // Use traffic data directly from response (no extra API call needed)
                if (data.traffic) {
                    updateTrafficDisplay(data.traffic);
                }
            } else {
                // No default route found
                $('#gwInterface').text('No gateway');
                $('#ispName').text('-');
                $('#publicIp').text('-');
                // Still try to get public IP
                if (!publicIpFetched) {
                    fetchPublicIpAndIsp();
                }
            }
        }
    }).fail(function() {
        // On error, still try to get public IP from external
        if (!publicIpFetched) {
            fetchPublicIpExternal();
        }
    });
}

function updateTrafficDisplay(traffic) {
    const now = Date.now();
    const txBytes = parseInt(traffic['tx-byte']) || 0;
    const rxBytes = parseInt(traffic['rx-byte']) || 0;
    
    // Always update totals
    $('#gwTxTotal').text(formatBytes(txBytes));
    $('#gwRxTotal').text(formatBytes(rxBytes));
    
    // Calculate rate (bytes per second) only if we have previous data
    const timeDiff = (now - prevGwTraffic.time) / 1000;
    if (timeDiff > 0 && timeDiff < 10 && prevGwTraffic.tx > 0) {
        const txRate = Math.max(0, (txBytes - prevGwTraffic.tx) / timeDiff);
        const rxRate = Math.max(0, (rxBytes - prevGwTraffic.rx) / timeDiff);
        
        // Only update if there's actual change (avoid negative or huge spikes)
        if (txRate >= 0 && txRate < 10000000000) {
            $('#gwTxRate').text(formatBitsPerSecond(txRate * 8));
        }
        if (rxRate >= 0 && rxRate < 10000000000) {
            $('#gwRxRate').text(formatBitsPerSecond(rxRate * 8));
        }
    } else if (prevGwTraffic.tx === 0) {
        // First fetch, just show 0
        $('#gwTxRate').text('0 bps');
        $('#gwRxRate').text('0 bps');
    }
    
    // Store for next calculation
    prevGwTraffic = { tx: txBytes, rx: rxBytes, time: now };
}

function isPrivateIpAddress(ip) {
    // Check if IP is private (RFC1918) or link-local
    const parts = ip.split('.').map(Number);
    if (parts.length !== 4) return true; // Not a valid IP, treat as private
    
    // 10.0.0.0/8
    if (parts[0] === 10) return true;
    // 172.16.0.0/12
    if (parts[0] === 172 && parts[1] >= 16 && parts[1] <= 31) return true;
    // 192.168.0.0/16
    if (parts[0] === 192 && parts[1] === 168) return true;
    // 169.254.0.0/16 (link-local)
    if (parts[0] === 169 && parts[1] === 254) return true;
    // 127.0.0.0/8 (loopback)
    if (parts[0] === 127) return true;
    
    return false;
}

function fetchPublicIpAndIsp() {
    // Try to get public IP from router first
    $.get(routerUrl + '/data?type=public-ip', function(response) {
        if (response.success && response.data && response.data.public_ip) {
            const publicIp = response.data.public_ip;
            publicIpFetched = true;
            lookupIsp(publicIp);
        } else {
            // Fallback: try external API directly
            fetchPublicIpExternal();
        }
    }).fail(function() {
        fetchPublicIpExternal();
    });
}

function fetchPublicIpExternal() {
    // Use ipinfo.io which also provides ISP info
    $.get('https://ipinfo.io/json', function(data) {
        if (data && data.ip) {
            publicIpFetched = true;
            ispInfo = {
                ip: data.ip,
                org: data.org || '',
                city: data.city || '',
                region: data.region || '',
                country: data.country || ''
            };
            updateIspDisplay();
        }
    }).fail(function() {
        // Try another service
        $.get('https://api.ipify.org?format=json', function(data) {
            if (data && data.ip) {
                publicIpFetched = true;
                lookupIsp(data.ip);
            } else {
                $('#ispName').text('Unknown');
            }
        }).fail(function() {
            $('#ispName').text('Unknown');
        });
    });
}

function lookupIsp(ip) {
    // Use ip-api.com for ISP lookup (free, no API key needed)
    $.get('http://ip-api.com/json/' + ip + '?fields=status,isp,org,as,query,city,regionName,country', function(data) {
        if (data && data.status === 'success') {
            ispInfo = {
                ip: data.query,
                isp: data.isp || '',
                org: data.org || '',
                as: data.as || '',
                city: data.city || '',
                region: data.regionName || '',
                country: data.country || ''
            };
            updateIspDisplay();
        } else {
            // Fallback to just showing the IP
            $('#ispName').text(ip);
            $('#ispInfo').attr('title', 'Public IP: ' + ip);
        }
    }).fail(function() {
        $('#ispName').text(ip);
        $('#ispInfo').attr('title', 'Public IP: ' + ip);
    });
}

function updateIspDisplay() {
    if (!ispInfo) return;
    
    // Show public IP
    $('#publicIp').text(ispInfo.ip || '-');
    
    // Show ISP name
    let ispName = ispInfo.isp || ispInfo.org || '-';
    // Shorten if too long
    if (ispName.length > 25) {
        ispName = ispName.substring(0, 23) + '..';
    }
    $('#ispName').text(ispName);
    
    // Show location if available
    const locationParts = [ispInfo.city, ispInfo.country].filter(Boolean);
    if (locationParts.length > 0) {
        $('#ispLocation').text(locationParts.join(', '));
        $('#ispLocationRow').show();
    }
}

function formatBitsPerSecond(bps) {
    if (bps >= 1000000000) return (bps / 1000000000).toFixed(2) + ' Gbps';
    if (bps >= 1000000) return (bps / 1000000).toFixed(2) + ' Mbps';
    if (bps >= 1000) return (bps / 1000).toFixed(2) + ' Kbps';
    return bps.toFixed(0) + ' bps';
}

function loadSection(section) {
    currentSection = section;
    $('#loadingOverlay').show();
    
    // Update title and actions
    const titles = {
        'interfaces': '<i class="fas fa-list mr-1"></i> Interface List',
        'ethernets': '<i class="fas fa-plug mr-1"></i> Ethernet',
        'bridges': '<i class="fas fa-project-diagram mr-1"></i> Bridge',
        'bridge-ports': '<i class="fas fa-sitemap mr-1"></i> Bridge Ports',
        'vlans': '<i class="fas fa-layer-group mr-1"></i> VLAN',
        'wireless': '<i class="fas fa-broadcast-tower mr-1"></i> Wireless Interfaces',
        'wireless-registration': '<i class="fas fa-users mr-1"></i> Wireless Registration',
        'ip-addresses': '<i class="fas fa-map-marker-alt mr-1"></i> IP Addresses',
        'routes': '<i class="fas fa-route mr-1"></i> Routes',
        'arp': '<i class="fas fa-table mr-1"></i> ARP Table',
        'dns': '<i class="fas fa-globe mr-1"></i> DNS',
        'dhcp-clients': '<i class="fas fa-laptop mr-1"></i> DHCP Client',
        'dhcp-servers': '<i class="fas fa-server mr-1"></i> DHCP Server',
        'dhcp-leases': '<i class="fas fa-address-card mr-1"></i> DHCP Leases',
        'ip-pools': '<i class="fas fa-swimming-pool mr-1"></i> IP Pool',
        'firewall-filter': '<i class="fas fa-filter mr-1"></i> Firewall Filter',
        'firewall-nat': '<i class="fas fa-random mr-1"></i> Firewall NAT',
        'firewall-mangle': '<i class="fas fa-tags mr-1"></i> Firewall Mangle',
        'firewall-address-list': '<i class="fas fa-list-alt mr-1"></i> Address Lists',
        'pppoe-clients': '<i class="fas fa-plug mr-1"></i> PPPoE Client',
        'ppp-secrets': '<i class="fas fa-key mr-1"></i> PPP Secrets',
        'ppp-active': '<i class="fas fa-user-check mr-1"></i> PPP Active',
        'ppp-profiles': '<i class="fas fa-id-card mr-1"></i> PPP Profiles',
        'hotspot-users': '<i class="fas fa-users mr-1"></i> Hotspot Users',
        'hotspot-active': '<i class="fas fa-user-clock mr-1"></i> Hotspot Active',
        'queues': '<i class="fas fa-stream mr-1"></i> Simple Queues',
        'packages': '<i class="fas fa-box mr-1"></i> Packages',
        'scheduler': '<i class="fas fa-clock mr-1"></i> Scheduler',
        'scripts': '<i class="fas fa-code mr-1"></i> Scripts',
        'system-users': '<i class="fas fa-user-cog mr-1"></i> System Users',
        'logs': '<i class="fas fa-file-alt mr-1"></i> Logs'
    };
    $('#sectionTitle').html(titles[section] || section);

    // Show/hide add button based on section
    const addableSections = ['ip-addresses', 'ppp-secrets'];
    if (addableSections.includes(section)) {
        $('#btnAdd').show().off('click').on('click', function() {
            showAddForm(section);
        });
    } else {
        $('#btnAdd').hide();
    }

    // Load data
    $.get(routerUrl + '/data?type=' + section, function(response) {
        $('#loadingOverlay').hide();
        
        if (response.success) {
            currentData = response.data;
            renderSection(section, response.data);
        } else {
            $('#dataContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    ${response.message || 'Gagal memuat data'}
                </div>
            `);
        }
    }).fail(function() {
        $('#loadingOverlay').hide();
        $('#dataContent').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Gagal terhubung ke server
            </div>
        `);
    });
}

function renderSection(section, data) {
    let html = '';
    
    switch(section) {
        case 'interfaces':
            html = renderInterfaces(data);
            break;
        case 'ethernets':
            html = renderEthernets(data);
            break;
        case 'bridges':
            html = renderBridges(data);
            break;
        case 'bridge-ports':
            html = renderBridgePorts(data);
            break;
        case 'vlans':
            html = renderVlans(data);
            break;
        case 'wireless':
            html = renderWireless(data);
            break;
        case 'wireless-registration':
            html = renderWirelessRegistration(data);
            break;
        case 'ip-addresses':
            html = renderIpAddresses(data);
            break;
        case 'routes':
            html = renderRoutes(data);
            break;
        case 'arp':
            html = renderArp(data);
            break;
        case 'dns':
            html = renderDns(data);
            break;
        case 'dhcp-clients':
            html = renderDhcpClients(data);
            break;
        case 'dhcp-servers':
            html = renderDhcpServers(data);
            break;
        case 'dhcp-leases':
            html = renderDhcpLeases(data);
            break;
        case 'ip-pools':
            html = renderIpPools(data);
            break;
        case 'firewall-filter':
            html = renderFirewallRules(data, 'Filter');
            break;
        case 'firewall-nat':
            html = renderFirewallRules(data, 'NAT');
            break;
        case 'firewall-mangle':
            html = renderFirewallRules(data, 'Mangle');
            break;
        case 'firewall-address-list':
            html = renderAddressList(data);
            break;
        case 'pppoe-clients':
            html = renderPppoeClients(data);
            break;
        case 'ppp-secrets':
            html = renderPppSecrets(data);
            break;
        case 'ppp-active':
            html = renderPppActive(data);
            break;
        case 'ppp-profiles':
            html = renderPppProfiles(data);
            break;
        case 'hotspot-users':
            html = renderHotspotUsers(data);
            break;
        case 'hotspot-active':
            html = renderHotspotActive(data);
            break;
        case 'queues':
            html = renderQueues(data);
            break;
        case 'packages':
            html = renderPackages(data);
            break;
        case 'scheduler':
            html = renderScheduler(data);
            break;
        case 'scripts':
            html = renderScripts(data);
            break;
        case 'system-users':
            html = renderSystemUsers(data);
            break;
        case 'logs':
            html = renderLogs(data);
            break;
        default:
            html = renderGenericTable(data);
    }
    
    $('#dataContent').html(html);
    initializeTableActions();
}

function renderInterfaces(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada interface</p>';
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-hover data-table">
                <thead>
                    <tr>
                        <th width="30"></th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>MAC Address</th>
                        <th>TX/RX</th>
                        <th>Status</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        const isRunning = item.running === 'true';
        html += `
            <tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
                <td>
                    <span class="status-badge ${isRunning ? 'running' : (isDisabled ? 'disabled' : '')}"></span>
                </td>
                <td>
                    <strong class="edit-inline" data-field="name" data-value="${item.name}">${item.name}</strong>
                    ${item.comment ? '<br><small class="text-muted">' + item.comment + '</small>' : ''}
                </td>
                <td>${item.type || '-'}</td>
                <td><code>${item['mac-address'] || '-'}</code></td>
                <td>
                    <small>
                        <i class="fas fa-arrow-up text-success"></i> ${formatBytes(item['tx-byte'] || 0)}
                        <i class="fas fa-arrow-down text-primary ml-2"></i> ${formatBytes(item['rx-byte'] || 0)}
                    </small>
                </td>
                <td>
                    ${isRunning ? '<span class="badge badge-success">Running</span>' : ''}
                    ${isDisabled ? '<span class="badge badge-danger">Disabled</span>' : ''}
                </td>
                <td>
                    ${isDisabled ? 
                        `<button class="btn btn-success btn-action btn-enable" data-action="interface/enable" data-id="${item['.id']}" title="Enable">
                            <i class="fas fa-play"></i>
                        </button>` :
                        `<button class="btn btn-warning btn-action btn-disable" data-action="interface/disable" data-id="${item['.id']}" title="Disable">
                            <i class="fas fa-pause"></i>
                        </button>`
                    }
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderIpAddresses(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada IP Address</p>';
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-hover data-table">
                <thead>
                    <tr>
                        <th width="30"></th>
                        <th>Address</th>
                        <th>Network</th>
                        <th>Interface</th>
                        <th>Comment</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        const isDynamic = item.dynamic === 'true';
        html += `
            <tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
                <td>
                    <span class="status-badge ${isDynamic ? 'dynamic' : (isDisabled ? 'disabled' : 'running')}"></span>
                </td>
                <td>
                    <code class="edit-inline" data-field="address" data-value="${item.address}">${item.address}</code>
                    ${isDynamic ? '<span class="badge badge-info ml-1">D</span>' : ''}
                </td>
                <td><code>${item.network || '-'}</code></td>
                <td>${item.interface}</td>
                <td><small class="edit-inline" data-field="comment" data-value="${item.comment || ''}">${item.comment || '-'}</small></td>
                <td>
                    ${!isDynamic ? `
                        ${isDisabled ? 
                            `<button class="btn btn-success btn-action btn-enable" data-action="ip/address/enable" data-id="${item['.id']}" title="Enable">
                                <i class="fas fa-play"></i>
                            </button>` :
                            `<button class="btn btn-warning btn-action btn-disable" data-action="ip/address/disable" data-id="${item['.id']}" title="Disable">
                                <i class="fas fa-pause"></i>
                            </button>`
                        }
                        <button class="btn btn-danger btn-action btn-remove" data-action="ip/address/remove" data-id="${item['.id']}" title="Remove">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : '<span class="text-muted">Dynamic</span>'}
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderPppSecrets(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada PPP Secret</p>';
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-hover data-table">
                <thead>
                    <tr>
                        <th width="30"></th>
                        <th>Name</th>
                        <th>Service</th>
                        <th>Profile</th>
                        <th>Local Address</th>
                        <th>Remote Address</th>
                        <th>Comment</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        html += `
            <tr class="${isDisabled ? 'table-secondary' : ''} clickable-row" data-id="${item['.id']}" data-item='${JSON.stringify(item)}'>
                <td>
                    <span class="status-badge ${isDisabled ? 'disabled' : 'running'}"></span>
                </td>
                <td><strong>${item.name}</strong></td>
                <td><span class="badge badge-info">${item.service || 'any'}</span></td>
                <td>${item.profile || 'default'}</td>
                <td><code>${item['local-address'] || '-'}</code></td>
                <td><code>${item['remote-address'] || '-'}</code></td>
                <td><small>${item.comment || '-'}</small></td>
                <td>
                    ${isDisabled ? 
                        `<button class="btn btn-success btn-action btn-enable" data-action="ppp/secret/enable" data-id="${item['.id']}" title="Enable">
                            <i class="fas fa-play"></i>
                        </button>` :
                        `<button class="btn btn-warning btn-action btn-disable" data-action="ppp/secret/disable" data-id="${item['.id']}" title="Disable">
                            <i class="fas fa-pause"></i>
                        </button>`
                    }
                    <button class="btn btn-info btn-action btn-edit-ppp" data-id="${item['.id']}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-action btn-remove" data-action="ppp/secret/remove" data-id="${item['.id']}" data-name="${item.name}" title="Remove">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderPppActive(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada koneksi PPP aktif</p>';
    }
    
    let html = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            Total koneksi aktif: <strong>${data.length}</strong>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Service</th>
                        <th>Caller ID</th>
                        <th>Address</th>
                        <th>Uptime</th>
                        <th width="80">Aksi</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.forEach(item => {
        html += `
            <tr data-id="${item['.id']}">
                <td><strong>${item.name}</strong></td>
                <td><span class="badge badge-success">${item.service || '-'}</span></td>
                <td><code>${item['caller-id'] || '-'}</code></td>
                <td><code>${item.address || '-'}</code></td>
                <td>${item.uptime || '-'}</td>
                <td>
                    <button class="btn btn-danger btn-action btn-disconnect" data-action="ppp/active/remove" data-id="${item['.id']}" data-name="${item.name}" title="Disconnect">
                        <i class="fas fa-user-slash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderRoutes(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada route</p>';
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-hover data-table">
                <thead>
                    <tr>
                        <th width="30"></th>
                        <th>Dst. Address</th>
                        <th>Gateway</th>
                        <th>Distance</th>
                        <th>Interface</th>
                        <th>Comment</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        const isDynamic = item.dynamic === 'true';
        const isActive = item.active === 'true';
        html += `
            <tr class="${isDisabled ? 'table-secondary' : (isActive ? '' : 'table-warning')}">
                <td>
                    ${isDynamic ? '<span class="badge badge-info">D</span>' : ''}
                    ${isActive ? '<span class="badge badge-success">A</span>' : ''}
                </td>
                <td><code>${item['dst-address']}</code></td>
                <td><code>${item.gateway || '-'}</code></td>
                <td>${item.distance || '-'}</td>
                <td>${item['vrf-interface'] || item.interface || '-'}</td>
                <td><small>${item.comment || '-'}</small></td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderDhcpLeases(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada DHCP lease</p>';
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-hover data-table">
                <thead>
                    <tr>
                        <th width="30"></th>
                        <th>Address</th>
                        <th>MAC Address</th>
                        <th>Host Name</th>
                        <th>Server</th>
                        <th>Status</th>
                        <th>Expires</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.forEach(item => {
        const isDynamic = item.dynamic === 'true';
        const status = item.status || 'bound';
        html += `
            <tr>
                <td>${isDynamic ? '<span class="badge badge-info">D</span>' : ''}</td>
                <td><code>${item.address}</code></td>
                <td><code>${item['mac-address'] || '-'}</code></td>
                <td>${item['host-name'] || '-'}</td>
                <td>${item.server || '-'}</td>
                <td><span class="badge badge-${status === 'bound' ? 'success' : 'warning'}">${status}</span></td>
                <td><small>${item['expires-after'] || '-'}</small></td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderArp(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada ARP entry</p>';
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-hover data-table">
                <thead>
                    <tr>
                        <th width="30"></th>
                        <th>Address</th>
                        <th>MAC Address</th>
                        <th>Interface</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.forEach(item => {
        const isDynamic = item.dynamic === 'true';
        const isComplete = item.complete === 'true';
        html += `
            <tr>
                <td>${isDynamic ? '<span class="badge badge-info">D</span>' : ''}</td>
                <td><code>${item.address}</code></td>
                <td><code>${item['mac-address'] || '-'}</code></td>
                <td>${item.interface || '-'}</td>
                <td><span class="badge badge-${isComplete ? 'success' : 'warning'}">${isComplete ? 'Complete' : 'Incomplete'}</span></td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderQueues(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada queue</p>';
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-hover data-table">
                <thead>
                    <tr>
                        <th width="30"></th>
                        <th>Name</th>
                        <th>Target</th>
                        <th>Rate (Up/Down)</th>
                        <th>Bytes (Up/Down)</th>
                        <th>Comment</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        html += `
            <tr class="${isDisabled ? 'table-secondary' : ''}">
                <td><span class="status-badge ${isDisabled ? 'disabled' : 'running'}"></span></td>
                <td><strong>${item.name}</strong></td>
                <td><code>${item.target || '-'}</code></td>
                <td>
                    <small>
                        <i class="fas fa-arrow-up"></i> ${item['max-limit'] ? item['max-limit'].split('/')[0] : '-'}
                        <i class="fas fa-arrow-down ml-2"></i> ${item['max-limit'] ? item['max-limit'].split('/')[1] : '-'}
                    </small>
                </td>
                <td>
                    <small>
                        ${formatBytes(item.bytes ? item.bytes.split('/')[0] : 0)} / ${formatBytes(item.bytes ? item.bytes.split('/')[1] : 0)}
                    </small>
                </td>
                <td><small>${item.comment || '-'}</small></td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderLogs(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada log</p>';
    }
    
    let html = '<div class="log-container" style="max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 0.85rem;">';
    
    data.forEach(item => {
        const topics = item.topics || '';
        let colorClass = 'text-dark';
        if (topics.includes('error')) colorClass = 'text-danger';
        else if (topics.includes('warning')) colorClass = 'text-warning';
        else if (topics.includes('info')) colorClass = 'text-info';
        else if (topics.includes('system')) colorClass = 'text-primary';
        
        html += `
            <div class="log-entry border-bottom py-1 ${colorClass}">
                <span class="text-muted">${item.time || ''}</span>
                <span class="badge badge-secondary ml-2">${topics}</span>
                <span class="ml-2">${item.message || ''}</span>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}

// =========== NEW RENDER FUNCTIONS ===========

function renderGenericTable(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada data</p>';
    }
    
    // Get all unique keys
    const keys = [...new Set(data.flatMap(obj => Object.keys(obj)))].filter(k => !k.startsWith('.'));
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>`;
    keys.forEach(key => {
        html += `<th>${key}</th>`;
    });
    html += `</tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id'] || ''}">`;
        keys.forEach(key => {
            let val = item[key] || '-';
            if (typeof val === 'object') val = JSON.stringify(val);
            html += `<td>${val}</td>`;
        });
        html += `</tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderEthernets(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada ethernet interface</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>Name</th><th>MAC Address</th><th>Speed</th><th>MTU</th><th>Status</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        const isRunning = item.running === 'true';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${isRunning ? 'running' : (isDisabled ? 'disabled' : '')}"></span></td>
            <td><strong>${item.name}</strong>${item.comment ? '<br><small class="text-muted">' + item.comment + '</small>' : ''}</td>
            <td><code>${item['mac-address'] || '-'}</code></td>
            <td>${item.speed || 'auto'}</td>
            <td>${item.mtu || item['actual-mtu'] || '-'}</td>
            <td>${isRunning ? '<span class="badge badge-success">Running</span>' : ''}${isDisabled ? '<span class="badge badge-danger">Disabled</span>' : ''}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderBridges(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada bridge</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>Name</th><th>MAC Address</th><th>Protocol Mode</th><th>VLAN Filtering</th><th>Status</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        const isRunning = item.running === 'true';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${isRunning ? 'running' : (isDisabled ? 'disabled' : '')}"></span></td>
            <td><strong>${item.name}</strong></td>
            <td><code>${item['mac-address'] || '-'}</code></td>
            <td>${item['protocol-mode'] || '-'}</td>
            <td>${item['vlan-filtering'] === 'true' ? '<span class="badge badge-info">Yes</span>' : 'No'}</td>
            <td>${isRunning ? '<span class="badge badge-success">Running</span>' : ''}${isDisabled ? '<span class="badge badge-danger">Disabled</span>' : ''}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderBridgePorts(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada bridge port</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>Interface</th><th>Bridge</th><th>PVID</th><th>Priority</th><th>Status</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${isDisabled ? 'disabled' : 'running'}"></span></td>
            <td><strong>${item.interface}</strong></td>
            <td>${item.bridge}</td>
            <td>${item.pvid || '-'}</td>
            <td>${item.priority || '-'}</td>
            <td>${isDisabled ? '<span class="badge badge-danger">Disabled</span>' : '<span class="badge badge-success">Active</span>'}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderVlans(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada VLAN</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>Name</th><th>VLAN ID</th><th>Interface</th><th>MTU</th><th>Status</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        const isRunning = item.running === 'true';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${isRunning ? 'running' : (isDisabled ? 'disabled' : '')}"></span></td>
            <td><strong>${item.name}</strong></td>
            <td><span class="badge badge-primary">${item['vlan-id']}</span></td>
            <td>${item.interface}</td>
            <td>${item.mtu || '-'}</td>
            <td>${isRunning ? '<span class="badge badge-success">Running</span>' : ''}${isDisabled ? '<span class="badge badge-danger">Disabled</span>' : ''}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderWireless(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada wireless interface</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>Name</th><th>SSID</th><th>Mode</th><th>Band</th><th>Channel</th><th>Status</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        const isRunning = item.running === 'true';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${isRunning ? 'running' : (isDisabled ? 'disabled' : '')}"></span></td>
            <td><strong>${item.name}</strong></td>
            <td>${item.ssid || '-'}</td>
            <td>${item.mode || '-'}</td>
            <td>${item.band || '-'}</td>
            <td>${item.frequency || item.channel || '-'}</td>
            <td>${isRunning ? '<span class="badge badge-success">Running</span>' : ''}${isDisabled ? '<span class="badge badge-danger">Disabled</span>' : ''}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderWirelessRegistration(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada client terdaftar</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th>Interface</th><th>MAC Address</th><th>AP</th><th>Signal</th><th>TX/RX Rate</th><th>Uptime</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const signal = parseInt(item['signal-strength']) || 0;
        let signalClass = 'success';
        if (signal > -50) signalClass = 'success';
        else if (signal > -70) signalClass = 'warning';
        else signalClass = 'danger';
        
        html += `<tr data-id="${item['.id']}">
            <td>${item.interface}</td>
            <td><code>${item['mac-address']}</code></td>
            <td>${item.ap === 'true' ? 'Yes' : 'No'}</td>
            <td><span class="badge badge-${signalClass}">${item['signal-strength'] || '-'} dBm</span></td>
            <td><small>${item['tx-rate'] || '-'} / ${item['rx-rate'] || '-'}</small></td>
            <td>${item.uptime || '-'}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderDns(data) {
    if (!data) {
        return '<p class="text-muted">Tidak dapat memuat DNS</p>';
    }
    
    let html = `<div class="card"><div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-server mr-2"></i>DNS Servers</h6>
                <p><code>${data.servers || '-'}</code></p>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-server mr-2"></i>Dynamic Servers</h6>
                <p><code>${data['dynamic-servers'] || '-'}</code></p>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-4">
                <small class="text-muted">Allow Remote Requests</small>
                <p>${data['allow-remote-requests'] === 'true' ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>'}</p>
            </div>
            <div class="col-md-4">
                <small class="text-muted">Cache Size</small>
                <p>${data['cache-size'] || '-'}</p>
            </div>
            <div class="col-md-4">
                <small class="text-muted">Cache Used</small>
                <p>${data['cache-used'] || '-'}</p>
            </div>
        </div>
    </div></div>`;
    
    return html;
}

function renderDhcpClients(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada DHCP Client</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>Interface</th><th>Address</th><th>Gateway</th><th>DNS</th><th>Status</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        const status = item.status || 'unknown';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${status === 'bound' ? 'running' : (isDisabled ? 'disabled' : '')}"></span></td>
            <td><strong>${item.interface}</strong></td>
            <td><code>${item.address || '-'}</code></td>
            <td><code>${item.gateway || '-'}</code></td>
            <td><code>${item['primary-dns'] || '-'}</code></td>
            <td>
                ${status === 'bound' ? '<span class="badge badge-success">Bound</span>' : ''}
                ${status === 'searching' ? '<span class="badge badge-warning">Searching</span>' : ''}
                ${isDisabled ? '<span class="badge badge-danger">Disabled</span>' : ''}
            </td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderDhcpServers(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada DHCP Server</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>Name</th><th>Interface</th><th>Address Pool</th><th>Lease Time</th><th>Status</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${isDisabled ? 'disabled' : 'running'}"></span></td>
            <td><strong>${item.name}</strong></td>
            <td>${item.interface}</td>
            <td>${item['address-pool'] || '-'}</td>
            <td>${item['lease-time'] || '-'}</td>
            <td>${isDisabled ? '<span class="badge badge-danger">Disabled</span>' : '<span class="badge badge-success">Active</span>'}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderIpPools(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada IP Pool</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th>Name</th><th>Ranges</th><th>Next Pool</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        html += `<tr data-id="${item['.id']}">
            <td><strong>${item.name}</strong></td>
            <td><code>${item.ranges || '-'}</code></td>
            <td>${item['next-pool'] || '-'}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderFirewallRules(data, type) {
    if (!data || data.length === 0) {
        return `<p class="text-muted">Tidak ada ${type} rules</p>`;
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>#</th><th>Chain</th><th>Action</th><th>Src</th><th>Dst</th><th>Protocol</th><th>Bytes</th><th>Comment</th>
    </tr></thead><tbody>`;
    
    data.forEach((item, index) => {
        const isDisabled = item.disabled === 'true';
        const isDynamic = item.dynamic === 'true';
        let actionClass = 'secondary';
        if (item.action === 'accept') actionClass = 'success';
        else if (item.action === 'drop') actionClass = 'danger';
        else if (item.action === 'reject') actionClass = 'warning';
        else if (item.action === 'masquerade') actionClass = 'info';
        else if (item.action === 'mark-packet' || item.action === 'mark-connection' || item.action === 'mark-routing') actionClass = 'primary';
        
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${isDisabled ? 'disabled' : (isDynamic ? 'dynamic' : 'running')}"></span></td>
            <td>${index + 1}</td>
            <td><strong>${item.chain || '-'}</strong></td>
            <td><span class="badge badge-${actionClass}">${item.action || '-'}</span></td>
            <td><small>${item['src-address'] || item['src-address-list'] || '*'}</small></td>
            <td><small>${item['dst-address'] || item['dst-address-list'] || '*'}</small></td>
            <td><small>${item.protocol || 'all'}${item['dst-port'] ? ':' + item['dst-port'] : ''}</small></td>
            <td><small>${formatBytes(item.bytes || 0)}</small></td>
            <td><small class="text-muted">${item.comment || '-'}</small></td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderAddressList(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada Address List</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>List</th><th>Address</th><th>Creation Time</th><th>Timeout</th><th>Comment</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        const isDynamic = item.dynamic === 'true';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${isDisabled ? 'disabled' : (isDynamic ? 'dynamic' : 'running')}"></span></td>
            <td><strong>${item.list}</strong></td>
            <td><code>${item.address}</code></td>
            <td><small>${item['creation-time'] || '-'}</small></td>
            <td>${item.timeout || 'permanent'}</td>
            <td><small class="text-muted">${item.comment || '-'}</small></td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderPppoeClients(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada PPPoE Client</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>Name</th><th>Interface</th><th>User</th><th>AC Name</th><th>Status</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        const isRunning = item.running === 'true';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${isRunning ? 'running' : (isDisabled ? 'disabled' : '')}"></span></td>
            <td><strong>${item.name}</strong></td>
            <td>${item.interface}</td>
            <td>${item.user || '-'}</td>
            <td>${item['ac-name'] || '-'}</td>
            <td>
                ${isRunning ? '<span class="badge badge-success">Connected</span>' : ''}
                ${isDisabled ? '<span class="badge badge-danger">Disabled</span>' : ''}
                ${!isRunning && !isDisabled ? '<span class="badge badge-warning">Disconnected</span>' : ''}
            </td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderPppProfiles(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada PPP Profile</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th>Name</th><th>Local Address</th><th>Remote Address</th><th>Rate Limit</th><th>DNS</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        html += `<tr data-id="${item['.id']}">
            <td><strong>${item.name}</strong></td>
            <td><code>${item['local-address'] || '-'}</code></td>
            <td><code>${item['remote-address'] || '-'}</code></td>
            <td><small>${item['rate-limit'] || '-'}</small></td>
            <td><small>${item['dns-server'] || '-'}</small></td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderHotspotUsers(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada Hotspot User</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>Name</th><th>Profile</th><th>Limit Uptime</th><th>Limit Bytes</th><th>Comment</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${isDisabled ? 'disabled' : 'running'}"></span></td>
            <td><strong>${item.name}</strong></td>
            <td>${item.profile || 'default'}</td>
            <td>${item['limit-uptime'] || '-'}</td>
            <td>${item['limit-bytes-total'] ? formatBytes(item['limit-bytes-total']) : '-'}</td>
            <td><small class="text-muted">${item.comment || '-'}</small></td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderHotspotActive(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada user aktif</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th>User</th><th>Address</th><th>MAC</th><th>Uptime</th><th>TX/RX</th><th>Aksi</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        html += `<tr data-id="${item['.id']}">
            <td><strong>${item.user}</strong></td>
            <td><code>${item.address}</code></td>
            <td><code>${item['mac-address'] || '-'}</code></td>
            <td>${item.uptime || '-'}</td>
            <td><small><i class="fas fa-arrow-up text-success"></i> ${formatBytes(item['bytes-out'] || 0)} <i class="fas fa-arrow-down text-primary ml-1"></i> ${formatBytes(item['bytes-in'] || 0)}</small></td>
            <td>
                <button class="btn btn-danger btn-action btn-disconnect" data-action="ip/hotspot/active/remove" data-id="${item['.id']}" data-name="${item.user}" title="Disconnect">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderPackages(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada package</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>Name</th><th>Version</th><th>Build Time</th><th>Status</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${isDisabled ? 'disabled' : 'running'}"></span></td>
            <td><strong>${item.name}</strong></td>
            <td><span class="badge badge-info">${item.version}</span></td>
            <td><small>${item['build-time'] || '-'}</small></td>
            <td>${isDisabled ? '<span class="badge badge-danger">Disabled</span>' : '<span class="badge badge-success">Active</span>'}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderScheduler(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada scheduler</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>Name</th><th>Start Time</th><th>Interval</th><th>Next Run</th><th>Run Count</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${isDisabled ? 'disabled' : 'running'}"></span></td>
            <td><strong>${item.name}</strong></td>
            <td>${item['start-time'] || '-'}</td>
            <td>${item.interval || '-'}</td>
            <td><small>${item['next-run'] || '-'}</small></td>
            <td>${item['run-count'] || 0}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderScripts(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada script</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th>Name</th><th>Owner</th><th>Last Started</th><th>Run Count</th><th>Policy</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        html += `<tr data-id="${item['.id']}">
            <td><strong>${item.name}</strong></td>
            <td>${item.owner || '-'}</td>
            <td><small>${item['last-started'] || 'never'}</small></td>
            <td>${item['run-count'] || 0}</td>
            <td><small class="text-muted">${item.policy || '-'}</small></td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function renderSystemUsers(data) {
    if (!data || data.length === 0) {
        return '<p class="text-muted">Tidak ada user</p>';
    }
    
    let html = `<div class="table-responsive"><table class="table table-sm table-hover data-table"><thead><tr>
        <th width="30"></th><th>Name</th><th>Group</th><th>Address</th><th>Last Logged In</th>
    </tr></thead><tbody>`;
    
    data.forEach(item => {
        const isDisabled = item.disabled === 'true';
        html += `<tr class="${isDisabled ? 'table-secondary' : ''}" data-id="${item['.id']}">
            <td><span class="status-badge ${isDisabled ? 'disabled' : 'running'}"></span></td>
            <td><strong>${item.name}</strong></td>
            <td><span class="badge badge-info">${item.group}</span></td>
            <td><code>${item.address || '*'}</code></td>
            <td><small>${item['last-logged-in'] || 'never'}</small></td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    return html;
}

// =========== END NEW RENDER FUNCTIONS ===========

function initializeTableActions() {
    // Enable/Disable buttons
    $('.btn-enable, .btn-disable').off('click').on('click', function(e) {
        e.stopPropagation();
        const btn = $(this);
        const action = btn.data('action');
        const id = btn.data('id');
        const actionText = action.includes('enable') ? 'mengaktifkan' : 'menonaktifkan';
        
        Swal.fire({
            title: 'Konfirmasi',
            text: `Apakah Anda yakin ingin ${actionText} item ini?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                executeCommand(action, { id: id });
            }
        });
    });

    // Remove buttons
    $('.btn-remove, .btn-disconnect').off('click').on('click', function(e) {
        e.stopPropagation();
        const btn = $(this);
        const action = btn.data('action');
        const id = btn.data('id');
        const name = btn.data('name') || 'item';
        
        Swal.fire({
            title: 'Konfirmasi Hapus',
            html: `Apakah Anda yakin ingin menghapus <strong>${name}</strong>?<br><small class="text-danger">Tindakan ini tidak dapat dibatalkan!</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                executeCommand(action, { id: id });
            }
        });
    });

    // Inline edit
    $('.edit-inline').off('click').on('click', function(e) {
        e.stopPropagation();
        const el = $(this);
        const field = el.data('field');
        const value = el.data('value');
        const row = el.closest('tr');
        const id = row.data('id');
        
        Swal.fire({
            title: 'Edit ' + field,
            input: 'text',
            inputValue: value,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value) return 'Nilai tidak boleh kosong';
            }
        }).then((result) => {
            if (result.isConfirmed && result.value !== value) {
                const data = {};
                data[field] = result.value;
                executeCommand(currentSection.replace('-', '/') + '/update', {
                    id: id,
                    data: data
                });
            }
        });
    });
}

function showAddForm(section) {
    let formHtml = '';
    
    if (section === 'ip-addresses') {
        formHtml = `
            <form id="addForm">
                <div class="form-group">
                    <label>Address <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="address" placeholder="192.168.1.1/24" required>
                </div>
                <div class="form-group">
                    <label>Interface <span class="text-danger">*</span></label>
                    <select class="form-control select2" name="interface" required>
                        <option value="">-- Pilih Interface --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Comment</label>
                    <input type="text" class="form-control" name="comment">
                </div>
                <div class="modal-footer px-0 pb-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                </div>
            </form>
        `;
        
        $('#editModalTitle').text('Tambah IP Address');
        $('#editModalBody').html(formHtml);
        $('#editModal').modal('show');
        
        // Initialize select2 for dropdown
        $('#editModal .select2').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#editModal')
        });
        
        // Load interfaces for dropdown
        $.get(routerUrl + '/data?type=interfaces', function(response) {
            if (response.success) {
                response.data.forEach(iface => {
                    $('select[name="interface"]').append(`<option value="${iface.name}">${iface.name}</option>`);
                });
            }
        });
        
        $('#addForm').on('submit', function(e) {
            e.preventDefault();
            executeCommand('ip/address/add', {
                address: $('input[name="address"]').val(),
                interface: $('select[name="interface"]').val(),
                comment: $('input[name="comment"]').val()
            });
            $('#editModal').modal('hide');
        });
    } else if (section === 'ppp-secrets') {
        formHtml = `
            <form id="addForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Password <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="password" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Service</label>
                            <select class="form-control select2" name="service">
                                <option value="any">Any</option>
                                <option value="pppoe">PPPoE</option>
                                <option value="pptp">PPTP</option>
                                <option value="l2tp">L2TP</option>
                                <option value="ovpn">OVPN</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Profile</label>
                            <select class="form-control select2" name="profile">
                                <option value="default">default</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Local Address</label>
                            <input type="text" class="form-control" name="local-address" placeholder="Optional">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Remote Address</label>
                            <input type="text" class="form-control" name="remote-address" placeholder="Optional">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Comment</label>
                    <input type="text" class="form-control" name="comment">
                </div>
                <div class="modal-footer px-0 pb-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                </div>
            </form>
        `;
        
        $('#editModalTitle').text('Tambah PPP Secret');
        $('#editModalBody').html(formHtml);
        $('#editModal').modal('show');
        
        // Initialize select2 for dropdowns
        $('#editModal .select2').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#editModal')
        });
        
        // Load profiles
        $.get(routerUrl + '/data?type=ppp-profiles', function(response) {
            if (response.success) {
                $('select[name="profile"]').empty();
                response.data.forEach(profile => {
                    $('select[name="profile"]').append(`<option value="${profile.name}">${profile.name}</option>`);
                });
            }
        });
        
        $('#addForm').on('submit', function(e) {
            e.preventDefault();
            const data = {};
            $(this).serializeArray().forEach(item => {
                if (item.value) data[item.name] = item.value;
            });
            executeCommand('ppp/secret/add', { data: data });
            $('#editModal').modal('hide');
        });
    }
}

function executeCommand(action, params) {
    Swal.fire({
        title: 'Memproses...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    $.ajax({
        url: routerUrl + '/execute',
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        contentType: 'application/json',
        data: JSON.stringify({ action: action, params: params }),
        success: function(response) {
            Swal.close();
            if (response.success) {
                toastr.success(response.message);
                loadSection(currentSection);
            } else {
                Swal.fire('Gagal', response.message, 'error');
            }
        },
        error: function(xhr) {
            Swal.close();
            Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
        }
    });
}

function formatBytes(bytes) {
    if (!bytes || bytes == 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>
@endpush
