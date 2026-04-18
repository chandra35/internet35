@extends('layouts.admin')

@section('title', 'Detail ONU - ' . $onu->serial_number)

@section('page-title', 'Detail ONU')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.onus.index') }}">ONU</a></li>
    <li class="breadcrumb-item active">{{ $onu->serial_number }}</li>
@endsection

@push('css')
<style>
    .onu-hero { border-left: 4px solid; }
    .onu-hero.online { border-left-color: #28a745; }
    .onu-hero.offline { border-left-color: #dc3545; }
    .onu-hero.los { border-left-color: #ffc107; }
    .stat-card { border-radius: 8px; padding: 12px 16px; transition: all .2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,.1); }
    .nav-tabs-custom .nav-link { font-size: 13px; font-weight: 500; color: #6c757d; }
    .nav-tabs-custom .nav-link.active { color: #007bff; border-bottom: 2px solid #007bff; }
    .nav-tabs-custom .nav-link i { margin-right: 5px; }
    .acs-section-header { background: #f8f9fa; padding: 10px 15px; border-bottom: 1px solid #dee2e6; font-weight: 600; font-size: 13px; cursor: pointer; }
    .acs-section-header:hover { background: #e9ecef; }
    .acs-section-header .badge { font-size: 11px; }
    .acs-card { border: 1px solid #dee2e6; border-radius: 6px; margin-bottom: 12px; overflow: hidden; }
    .acs-card .card-body { padding: 10px 15px; }
    .table-acs td, .table-acs th { font-size: 13px; padding: 6px 10px; }
    .btn-acs-action { font-size: 12px; padding: 4px 12px; border-radius: 4px; }
    .host-row td { font-size: 12px; }
    .security-item { padding: 8px 12px; border-bottom: 1px solid #f0f0f0; }
    .security-item:last-child { border-bottom: none; }
    .quick-action-btn { min-width: 100px; }
    .wan-connection-card { border-left: 3px solid; border-radius: 4px; margin-bottom: 8px; padding: 10px 14px; background: #fafbfc; }
    .wan-connection-card.connected { border-left-color: #28a745; }
    .wan-connection-card.disconnected { border-left-color: #dc3545; }
    .wan-connection-card.connecting { border-left-color: #ffc107; }
    .wifi-card { background: #fafbfc; border-radius: 6px; padding: 12px; margin-bottom: 8px; }
    .firmware-current { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 8px; padding: 15px; }
    #tr069-loading-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,.85); z-index: 10; display: flex; align-items: center; justify-content: center; }
</style>
@endpush

@section('content')
{{-- TOP: ONU Header --}}
<div class="card onu-hero {{ $onu->status ?? 'offline' }} shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="d-flex align-items-center">
                    <div class="mr-3">
                        <i class="fas fa-hdd fa-2x text-{{ $onu->status == 'online' ? 'success' : ($onu->status == 'los' ? 'warning' : 'danger') }}"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 font-weight-bold">{{ $onu->name ?? $onu->description ?? $onu->serial_number }}</h4>
                        <div class="text-muted small">
                            <code>{{ $onu->serial_number }}</code>
                            <span class="mx-1">|</span>
                            <span>{{ $onu->onu_type ?? 'Unknown Type' }}</span>
                            <span class="mx-1">|</span>
                            <a href="{{ route('admin.olts.show', $onu->olt) }}">{{ $onu->olt->name }}</a>
                            <span class="mx-1">&rarr;</span>
                            PON {{ $onu->port ?? '-' }} / ONU {{ $onu->onu_id ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-lg-right mt-2 mt-lg-0">
                @php
                    $onuRx = $onu->rx_power;
                    $oltRx = $onu->olt_rx_power;
                    $rxDisplay = $onuRx ?? $oltRx;
                    $rxClass = 'secondary';
                    if ($rxDisplay !== null) {
                        $rxClass = $rxDisplay >= -25 ? 'success' : ($rxDisplay >= -27 ? 'warning' : 'danger');
                    }
                    $dist = $onu->distance;
                    $distFormatted = '';
                    if ($dist) {
                        $distFormatted = $dist >= 1000 ? number_format($dist / 1000, 2) . 'km' : $dist . 'm';
                    }
                @endphp
                <span class="badge badge-{{ $onu->status == 'online' ? 'success' : ($onu->status == 'los' ? 'warning' : 'danger') }} px-3 py-2 mr-2" style="font-size:13px" id="onu-status">
                    <i class="fas fa-circle mr-1" style="font-size:8px;vertical-align:middle"></i>
                    {{ strtoupper($onu->status ?? 'unknown') }}
                </span>
                <span class="badge badge-{{ $rxClass }} px-3 py-2" style="font-size:13px" id="onu-signal">
                    <i class="fas fa-signal mr-1"></i>
                    @if($onuRx !== null || $oltRx !== null)
                        {{ $onuRx !== null ? number_format($onuRx, 2) : '-' }} / {{ $oltRx !== null ? number_format($oltRx, 2) : '-' }} dBm
                        @if($distFormatted) ({{ $distFormatted }}) @endif
                    @else
                        Memuat...
                    @endif
                </span>
            </div>
        </div>
    </div>
</div>

{{-- QUICK STATS ROW --}}
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-success shadow-sm">
            <div class="inner">
                <h4 id="traffic-rx-rate-val">-</h4>
                <p>Download</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-down"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-info shadow-sm">
            <div class="inner">
                <h4 id="traffic-tx-rate-val">-</h4>
                <p>Upload</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-up"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-warning shadow-sm">
            <div class="inner">
                <h4>{{ $onu->customer ? $onu->customer->name : '-' }}</h4>
                <p>Pelanggan</p>
            </div>
            <div class="icon"><i class="fas fa-user"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-secondary shadow-sm">
            <div class="inner">
                <h4>{{ $onu->last_online_at ? $onu->last_online_at->diffForHumans(null, true) : '-' }}</h4>
                <p>Last Online</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
</div>

{{-- TABBED CONTENT --}}
<div class="card shadow-sm">
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs nav-tabs-custom px-3 pt-2" id="onu-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-overview" data-toggle="tab" href="#pane-overview" role="tab">
                    <i class="fas fa-info-circle"></i>Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-acs" data-toggle="tab" href="#pane-acs" role="tab">
                    <i class="fas fa-satellite-dish"></i>ACS Management
                    <span class="badge badge-secondary ml-1" id="tr069-status-badge" style="font-size:10px">-</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-signal" data-toggle="tab" href="#pane-signal" role="tab">
                    <i class="fas fa-chart-line"></i>Signal &amp; Traffic
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-config" data-toggle="tab" href="#pane-config" role="tab">
                    <i class="fas fa-cogs"></i>Konfigurasi
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body tab-content" id="onu-tab-content">

        {{-- ======== TAB: OVERVIEW ======== --}}
        <div class="tab-pane fade show active" id="pane-overview" role="tabpanel">
            <div class="row">
                <div class="col-lg-6">
                    <h6 class="text-muted text-uppercase mb-3"><i class="fas fa-server mr-1"></i>Informasi ONU</h6>
                    <table class="table table-sm table-striped">
                        <tr><td width="35%"><strong>OLT</strong></td><td><a href="{{ route('admin.olts.show', $onu->olt) }}">{{ $onu->olt->name }}</a></td></tr>
                        <tr><td><strong>PON Port</strong></td><td>{{ $onu->port ?? '-' }}</td></tr>
                        <tr><td><strong>ONU ID</strong></td><td>{{ $onu->onu_id ?? '-' }}</td></tr>
                        <tr><td><strong>Serial Number</strong></td><td><code>{{ $onu->serial_number }}</code></td></tr>
                        <tr><td><strong>Tipe ONU</strong></td><td>{{ $onu->onu_type ?? '-' }}</td></tr>
                        <tr><td><strong>Zone</strong></td><td>{{ $onu->zone->name ?? '-' }}</td></tr>
                        <tr><td><strong>ODP</strong></td><td>{{ $onu->odp->name ?? '-' }}</td></tr>
                        <tr><td><strong>Line Profile</strong></td><td>{{ $onu->line_profile ?? '-' }}</td></tr>
                        <tr><td><strong>Service Profile</strong></td><td>{{ $onu->service_profile ?? '-' }}</td></tr>
                        <tr>
                            <td><strong>Pelanggan</strong></td>
                            <td>
                                @if($onu->customer)
                                    <a href="{{ route('admin.customers.show', $onu->customer) }}">{{ $onu->customer->name }}</a>
                                    @if($onu->pppoe_username)
                                        <br><small class="text-muted"><i class="fas fa-user mr-1"></i>{{ $onu->pppoe_username }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">Belum dipasangkan</span>
                                @endif
                            </td>
                        </tr>
                        @if(!empty($onu->vlan_config))
                        <tr>
                            <td><strong>VLAN Config</strong></td>
                            <td>
                                @if(is_array($onu->vlan_config))
                                    {{ collect($onu->vlan_config)->filter(fn($v) => $v !== null && $v !== '')->map(fn($v, $k) => ucfirst(str_replace('_', ' ', $k)) . ': ' . $v)->implode(', ') ?: '-' }}
                                @else
                                    {{ $onu->vlan_config }}
                                @endif
                            </td>
                        </tr>
                        @endif
                        @if($onu->mgmt_ip)
                        <tr><td><strong>Management IP</strong></td><td><code>{{ $onu->mgmt_ip }}</code></td></tr>
                        @endif
                        <tr><td><strong>Last Online</strong></td><td>{{ $onu->last_online_at ? $onu->last_online_at->format('d/m/Y H:i') . ' (' . $onu->last_online_at->diffForHumans() . ')' : '-' }}</td></tr>
                        <tr><td><strong>Last Sync</strong></td><td>{{ $onu->last_sync_at ? $onu->last_sync_at->format('d/m/Y H:i') . ' (' . $onu->last_sync_at->diffForHumans() . ')' : '-' }}</td></tr>
                    </table>
                </div>
                <div class="col-lg-6">
                    {{-- Quick Actions --}}
                    <h6 class="text-muted text-uppercase mb-3"><i class="fas fa-bolt mr-1"></i>Aksi Cepat</h6>
                    <div class="mb-3">
                        <button type="button" class="btn btn-info btn-sm quick-action-btn btn-refresh-signal mb-1" data-id="{{ $onu->id }}">
                            <i class="fas fa-signal mr-1"></i>Refresh Signal
                        </button>
                        @can('onus.reboot')
                        <button type="button" class="btn btn-warning btn-sm quick-action-btn btn-reboot-onu mb-1" data-id="{{ $onu->id }}">
                            <i class="fas fa-sync mr-1"></i>Reboot ONU
                        </button>
                        @endcan
                        @can('onus.unregister')
                        <button type="button" class="btn btn-danger btn-sm quick-action-btn btn-unregister-onu mb-1"
                                data-id="{{ $onu->id }}" data-sn="{{ $onu->serial_number }}">
                            <i class="fas fa-trash mr-1"></i>Unregister
                        </button>
                        @endcan
                    </div>

                    {{-- Assign Customer --}}
                    @if(!$onu->customer)
                    <div class="callout callout-info">
                        <h6><i class="fas fa-user-plus mr-1"></i>Assign ke Pelanggan</h6>
                        <form action="{{ route('admin.onus.assign-customer', $onu) }}" method="POST" class="mt-2">
                            @csrf
                            <div class="input-group input-group-sm">
                                <select name="customer_id" class="form-control select2-customer" style="width:100%" required>
                                    <option value="">-- Pilih Pelanggan --</option>
                                </select>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-link"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endif

                    {{-- Traffic Realtime Mini --}}
                    <h6 class="text-muted text-uppercase mb-3 mt-3"><i class="fas fa-tachometer-alt mr-1"></i>Traffic Realtime</h6>
                    <div class="row">
                        <div class="col-6">
                            <div class="stat-card bg-light">
                                <div class="text-muted small"><i class="fas fa-arrow-down text-success mr-1"></i>Download</div>
                                <div class="font-weight-bold" id="traffic-rx">-</div>
                                <div class="progress mt-1" style="height:3px"><div class="progress-bar bg-success" id="traffic-rx-bar" style="width:0%"></div></div>
                                <div class="text-muted small mt-1" id="traffic-rx-rate"><i class="fas fa-clock mr-1"></i>Menghitung...</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card bg-light">
                                <div class="text-muted small"><i class="fas fa-arrow-up text-info mr-1"></i>Upload</div>
                                <div class="font-weight-bold" id="traffic-tx">-</div>
                                <div class="progress mt-1" style="height:3px"><div class="progress-bar bg-info" id="traffic-tx-bar" style="width:0%"></div></div>
                                <div class="text-muted small mt-1" id="traffic-tx-rate"><i class="fas fa-clock mr-1"></i>Menghitung...</div>
                            </div>
                        </div>
                    </div>
                    <div class="text-muted small text-center mt-2" id="traffic-updated">
                        <i class="fas fa-clock mr-1"></i>Auto-refresh setiap 5 detik
                    </div>
                </div>
            </div>
        </div>

        {{-- ======== TAB: ACS MANAGEMENT ======== --}}
        <div class="tab-pane fade" id="pane-acs" role="tabpanel">
            <div class="position-relative" style="min-height:200px">
                {{-- Loading overlay --}}
                <div id="tr069-loading-overlay">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary mb-2 d-block"></i>
                        <span class="text-muted">Memuat data ACS...</span>
                    </div>
                </div>

                {{-- Not Found --}}
                <div id="tr069-not-found" style="display:none" class="text-center py-5">
                    <i class="fas fa-times-circle fa-3x text-warning mb-3 d-block"></i>
                    <h5>ONU belum terdaftar di GenieACS</h5>
                    <p class="text-muted">Pastikan ACS URL sudah dikonfigurasi di ONU dan ONU dalam keadaan online.</p>
                    <button class="btn btn-primary btn-sm btn-refresh-tr069"><i class="fas fa-sync mr-1"></i>Coba Lagi</button>
                </div>

                {{-- Unavailable --}}
                <div id="tr069-unavailable" style="display:none" class="text-center py-5">
                    <i class="fas fa-server fa-3x text-danger mb-3 d-block"></i>
                    <h5>GenieACS Server Tidak Tersedia</h5>
                    <p class="text-muted">Periksa koneksi ke server GenieACS.</p>
                </div>

                {{-- Main ACS Content --}}
                <div id="tr069-main" style="display:none">
                    {{-- ACS Toolbar --}}
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <div>
                            <span class="badge badge-success mr-2" id="tr069-conn-badge">
                                <i class="fas fa-check-circle mr-1"></i>Connected
                            </span>
                            <span class="text-muted small" id="tr069-last-inform">-</span>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-primary btn-refresh-tr069 mr-1">
                                <i class="fas fa-sync mr-1"></i>Refresh Data
                            </button>
                            <button class="btn btn-sm btn-outline-warning btn-tr069-reboot mr-1">
                                <i class="fas fa-redo mr-1"></i>Reboot TR069
                            </button>
                            <a href="#" id="tr069-ui-link" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-external-link-alt mr-1"></i>GenieACS UI
                            </a>
                        </div>
                    </div>

                    {{-- Pending Tasks Alert --}}
                    <div id="tr069-pending-tasks" style="display:none" class="mb-3">
                        <div class="alert alert-warning alert-dismissible py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-clock mr-1"></i><strong>Pending Tasks</strong> <span class="badge badge-warning" id="tr069-task-count">0</span></span>
                                <button type="button" class="btn btn-xs btn-outline-danger btn-clear-all-tasks">
                                    <i class="fas fa-times mr-1"></i>Hapus Semua
                                </button>
                            </div>
                            <div id="tr069-task-list" class="mt-2 small"></div>
                        </div>
                    </div>

                    {{-- ACS Sub-tabs --}}
                    <ul class="nav nav-pills nav-sm mb-3" id="acs-subtabs">
                        <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#acs-general"><i class="fas fa-home mr-1"></i>General</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#acs-wan"><i class="fas fa-globe mr-1"></i>WAN <span class="badge badge-light" id="tr069-wan-count">0</span></a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#acs-wifi"><i class="fas fa-wifi mr-1"></i>WiFi <span class="badge badge-light" id="tr069-wifi-count">0</span></a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#acs-lan"><i class="fas fa-ethernet mr-1"></i>LAN</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#acs-users"><i class="fas fa-users mr-1"></i>Users <span class="badge badge-light" id="tr069-host-count">0</span></a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#acs-security"><i class="fas fa-shield-alt mr-1"></i>Security</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#acs-firmware"><i class="fas fa-microchip mr-1"></i>Firmware</a></li>
                    </ul>

                    <div class="tab-content" id="acs-subtab-content">
                        {{-- General --}}
                        <div class="tab-pane fade show active" id="acs-general">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="acs-card">
                                        <div class="acs-section-header"><i class="fas fa-info-circle mr-1 text-primary"></i>Device Information</div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm table-striped table-acs mb-0" id="tr069-general-table"></table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="acs-card">
                                        <div class="acs-section-header"><i class="fas fa-heartbeat mr-1 text-success"></i>System Status</div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm table-striped table-acs mb-0" id="tr069-system-table">
                                                <tr><td width="40%">CPU Usage</td><td id="tr069-cpu">-</td></tr>
                                                <tr><td>Memory</td><td id="tr069-memory">-</td></tr>
                                                <tr><td>Uptime</td><td id="tr069-uptime">-</td></tr>
                                                <tr><td>Last Inform</td><td id="tr069-inform-time">-</td></tr>
                                                <tr><td>Registered</td><td id="tr069-registered">-</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- WAN --}}
                        <div class="tab-pane fade" id="acs-wan">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0"><i class="fas fa-globe mr-1"></i>WAN Connections</h6>
                                <button type="button" class="btn btn-sm btn-success" id="btn-setup-pppoe">
                                    <i class="fas fa-plus mr-1"></i>Setup PPPoE WAN
                                </button>
                            </div>
                            <div id="tr069-wan-list"></div>
                            <div id="tr069-wan-empty" class="text-center py-4 text-muted" style="display:none">
                                <i class="fas fa-globe fa-2x mb-2 d-block text-secondary"></i>
                                Belum ada WAN connection yang dikonfigurasi.<br>
                                <small>Klik "Setup PPPoE WAN" untuk membuat koneksi baru.</small>
                            </div>
                        </div>

                        {{-- WiFi --}}
                        <div class="tab-pane fade" id="acs-wifi">
                            <h6 class="mb-3"><i class="fas fa-wifi mr-1"></i>Wireless LAN</h6>
                            <div id="tr069-wifi-list"></div>
                            <div id="tr069-wifi-empty" class="text-center py-4 text-muted" style="display:none">
                                <i class="fas fa-wifi fa-2x mb-2 d-block text-secondary"></i>
                                Tidak ada data WiFi
                            </div>
                        </div>

                        {{-- LAN --}}
                        <div class="tab-pane fade" id="acs-lan">
                            <h6 class="mb-3"><i class="fas fa-ethernet mr-1"></i>LAN Ports</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-bordered" id="tr069-lan-table">
                                    <thead class="thead-light">
                                        <tr><th>Port</th><th>Status</th><th>Speed</th><th>MAC Address</th></tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Users / Connected Hosts --}}
                        <div class="tab-pane fade" id="acs-users">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0"><i class="fas fa-users mr-1"></i>Connected Devices / Hosts</h6>
                                <button class="btn btn-sm btn-outline-primary btn-refresh-users">
                                    <i class="fas fa-sync mr-1"></i>Refresh
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-bordered" id="tr069-hosts-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Hostname</th>
                                            <th>IP Address</th>
                                            <th>MAC Address</th>
                                            <th>Interface</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div id="tr069-users-empty" class="text-center py-4 text-muted" style="display:none">
                                <i class="fas fa-users fa-2x mb-2 d-block text-secondary"></i>
                                Tidak ada host/device yang terdeteksi
                            </div>
                        </div>

                        {{-- Security --}}
                        <div class="tab-pane fade" id="acs-security">
                            <h6 class="mb-3"><i class="fas fa-shield-alt mr-1"></i>Security &amp; ACS Configuration</h6>
                            <div id="tr069-security-loading" class="text-center py-3">
                                <i class="fas fa-spinner fa-spin"></i> Memuat data security...
                            </div>
                            <div id="tr069-security-content" style="display:none">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="acs-card">
                                            <div class="acs-section-header"><i class="fas fa-server mr-1 text-primary"></i>ACS Server (TR-069)</div>
                                            <div class="card-body p-0">
                                                <table class="table table-sm table-striped table-acs mb-0" id="tr069-acs-table"></table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="acs-card">
                                            <div class="acs-section-header"><i class="fas fa-lock mr-1 text-warning"></i>Network Security</div>
                                            <div class="card-body p-0">
                                                <table class="table table-sm table-striped table-acs mb-0" id="tr069-security-table"></table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Firmware --}}
                        <div class="tab-pane fade" id="acs-firmware">
                            <h6 class="mb-3"><i class="fas fa-microchip mr-1"></i>Firmware Management</h6>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="firmware-current mb-3">
                                        <div class="small text-white-50 mb-1">FIRMWARE SAAT INI</div>
                                        <h5 class="mb-1" id="fw-current-version">-</h5>
                                        <div class="small text-white-50">
                                            <span id="fw-model">-</span> | <span id="fw-hw-version">-</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="acs-card">
                                        <div class="acs-section-header"><i class="fas fa-cloud-upload-alt mr-1 text-primary"></i>Upgrade Firmware via TR-069</div>
                                        <div class="card-body">
                                            <form id="form-firmware-upgrade">
                                                <div class="form-group mb-2">
                                                    <label class="small font-weight-bold">File URL (HTTP/FTP)</label>
                                                    <input type="url" name="file_url" class="form-control form-control-sm" placeholder="http://server/firmware.bin" required>
                                                    <small class="text-muted">URL firmware harus bisa diakses oleh ONU</small>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm btn-block">
                                                    <i class="fas fa-cloud-upload-alt mr-1"></i>Mulai Upgrade
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="acs-card mt-2">
                                        <div class="acs-section-header"><i class="fas fa-exclamation-triangle mr-1 text-danger"></i>Danger Zone</div>
                                        <div class="card-body">
                                            <button type="button" class="btn btn-outline-danger btn-sm btn-block btn-factory-reset">
                                                <i class="fas fa-undo mr-1"></i>Factory Reset via TR-069
                                            </button>
                                            <small class="text-muted d-block mt-1">Mengembalikan ONU ke pengaturan pabrik. Semua konfigurasi akan hilang.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ======== TAB: SIGNAL & TRAFFIC ======== --}}
        <div class="tab-pane fade" id="pane-signal" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <h6 class="text-muted text-uppercase mb-3"><i class="fas fa-chart-line mr-1"></i>Histori Signal</h6>
                    <div class="mb-2">
                        <select id="chart-period" class="form-control form-control-sm d-inline-block" style="width:auto">
                            <option value="24h">24 Jam</option>
                            <option value="7d" selected>7 Hari</option>
                            <option value="30d">30 Hari</option>
                        </select>
                    </div>
                    <canvas id="signal-chart" style="height:300px;"></canvas>
                </div>
                <div class="col-lg-4">
                    <h6 class="text-muted text-uppercase mb-3"><i class="fas fa-history mr-1"></i>Riwayat Terbaru</h6>
                    <div class="table-responsive" style="max-height:380px;overflow-y:auto">
                        <table class="table table-sm table-striped mb-0">
                            <thead><tr><th>Waktu</th><th>RX</th><th>TX</th></tr></thead>
                            <tbody>
                                @forelse($signalHistory as $history)
                                <tr>
                                    <td class="small">{{ $history->recorded_at->format('d/m H:i') }}</td>
                                    <td>
                                        @php
                                            $histRx = $history->rx_power;
                                            $histRxClass = $histRx >= -25 ? 'success' : ($histRx >= -27 ? 'warning' : 'danger');
                                        @endphp
                                        <span class="badge badge-{{ $histRxClass }}">{{ number_format($histRx, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $history->tx_power ? number_format($history->tx_power, 2) : '-' }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted small">Belum ada data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ======== TAB: KONFIGURASI ======== --}}
        <div class="tab-pane fade" id="pane-config" role="tabpanel">
            <div class="row">
                {{-- Management IP --}}
                <div class="col-lg-6 mb-3">
                    <div class="acs-card">
                        <div class="acs-section-header">
                            <i class="fas fa-network-wired mr-1 text-warning"></i>Management IP
                            <span class="badge badge-{{ $onu->mgmt_ip ? 'success' : 'secondary' }} float-right">{{ $onu->mgmt_ip ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <div class="card-body">
                            @if($onu->mgmt_ip)
                            <p class="mb-2">Current: <code>{{ $onu->mgmt_ip }}</code></p>
                            @endif
                            <form id="form-management">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Management VLAN ID</label>
                                    <input type="number" name="mgmt_vlan" class="form-control form-control-sm" min="1" max="4094" value="111">
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">IP Mode</label>
                                    <select name="mgmt_ip_mode" class="form-control form-control-sm">
                                        <option value="dhcp">DHCP</option>
                                        <option value="static">Static IP</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="form-group mb-2" id="mgmt-static-ip" style="display:none">
                                    <label class="small font-weight-bold">Static IP</label>
                                    <input type="text" name="mgmt_ip" class="form-control form-control-sm" placeholder="172.16.x.x">
                                </div>
                                <button type="submit" class="btn btn-warning btn-sm btn-block"><i class="fas fa-save mr-1"></i>Update Management</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- WAN Setup (OMCI) --}}
                <div class="col-lg-6 mb-3">
                    <div class="acs-card">
                        <div class="acs-section-header">
                            <i class="fas fa-globe mr-1 text-success"></i>WAN Setup (OMCI)
                            @php $wanVlan = $onu->vlan_config['vlan_id'] ?? null; @endphp
                            <span class="badge badge-{{ $wanVlan ? 'info' : 'secondary' }} float-right">{{ $wanVlan ? 'VLAN ' . $wanVlan : 'Not Set' }}</span>
                        </div>
                        <div class="card-body">
                            <form id="form-wan-setup">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">WAN VLAN-ID</label>
                                    <input type="number" name="wan_vlan" class="form-control form-control-sm" min="1" max="4094" value="{{ $wanVlan ?? '' }}">
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">ONU Mode</label>
                                    <div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" name="wan_mode" value="routing" class="custom-control-input" id="wan-mode-routing" checked>
                                            <label class="custom-control-label" for="wan-mode-routing">Routing</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" name="wan_mode" value="bridging" class="custom-control-input" id="wan-mode-bridging">
                                            <label class="custom-control-label" for="wan-mode-bridging">Bridging</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">WAN Type</label>
                                    <select name="wan_type" id="wan-type-select" class="form-control form-control-sm">
                                        <option value="manual">Setup via ONU webpage</option>
                                        <option value="dhcp">DHCP</option>
                                        <option value="pppoe">PPPoE</option>
                                        <option value="static">Static IP</option>
                                    </select>
                                </div>
                                <div id="wan-pppoe-fields" style="display:none">
                                    <div class="form-group mb-2">
                                        <label class="small font-weight-bold">PPPoE Username</label>
                                        <input type="text" name="pppoe_username" class="form-control form-control-sm" value="{{ $onu->pppoe_username }}" placeholder="username@isp">
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="small font-weight-bold">PPPoE Password</label>
                                        <input type="password" name="pppoe_password" class="form-control form-control-sm" placeholder="password">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm btn-block"><i class="fas fa-save mr-1"></i>Update WAN</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ======== MODALS ======== --}}

{{-- PPPoE Setup Modal --}}
<div class="modal fade" id="modal-pppoe" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white"><i class="fas fa-globe mr-2"></i>Setup PPPoE WAN via TR-069</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="form-pppoe-setup">
                <div class="modal-body">
                    <div class="form-group">
                        <label>WAN VLAN-ID</label>
                        <select class="form-control" name="vlan" id="pppoe-vlan">
                            @if($onu->vlan_config['vlan_id'] ?? null)
                                <option value="{{ $onu->vlan_config['vlan_id'] }}" selected>{{ $onu->vlan_config['vlan_id'] }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label>PPPoE Username</label>
                        <input type="text" name="pppoe_username" class="form-control" placeholder="username@isp" value="{{ $onu->pppoe_username }}" required>
                    </div>
                    <div class="form-group">
                        <label>PPPoE Password</label>
                        <input type="text" name="pppoe_password" class="form-control" placeholder="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane mr-1"></i>Terapkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- WiFi Edit Modal --}}
<div class="modal fade" id="modal-wifi" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white"><i class="fas fa-wifi mr-2"></i>Edit Wireless</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="form-wifi-setup">
                <input type="hidden" name="wlan_path" id="wifi-path">
                <div class="modal-body">
                    <div class="form-group">
                        <label>SSID</label>
                        <input type="text" name="ssid" id="wifi-ssid" class="form-control" maxlength="32">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="text" name="password" id="wifi-password" class="form-control" minlength="8" maxlength="63" placeholder="Min 8 karakter">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="enabled" id="wifi-enabled" class="form-control">
                            <option value="1">Enabled</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.0.0"></script>
<script>
$(function() {
    // ========== Select2 Customer ==========
    $('.select2-customer').select2({
        theme: 'bootstrap4',
        ajax: {
            url: '{{ route("admin.customers.search") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term, pop_id: '{{ $onu->olt->pop_id }}', without_onu: true };
            },
            processResults: function(data) {
                return {
                    results: (data.results || []).map(function(item) {
                        return { id: item.id, text: item.customer_id + ' - ' + item.name };
                    })
                };
            }
        }
    });

    // ========== Signal Chart ==========
    var ctx = document.getElementById('signal-chart').getContext('2d');
    var signalChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels ?? []) !!},
            datasets: [{
                label: 'RX Power (dBm)',
                data: {!! json_encode($chartRxData ?? []) !!},
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.3, fill: true
            }, {
                label: 'TX Power (dBm)',
                data: {!! json_encode($chartTxData ?? []) !!},
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.3, fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { title: { display: true, text: 'dBm' } }
            },
            plugins: {
                annotation: {
                    annotations: {
                        warningLine: {
                            type: 'line', yMin: -25, yMax: -25,
                            borderColor: 'orange', borderWidth: 1, borderDash: [5,5],
                            label: { enabled: true, content: 'Warning (-25dBm)' }
                        },
                        criticalLine: {
                            type: 'line', yMin: -27, yMax: -27,
                            borderColor: 'red', borderWidth: 1, borderDash: [5,5],
                            label: { enabled: true, content: 'Critical (-27dBm)' }
                        }
                    }
                }
            }
        }
    });

    $('#chart-period').change(function() {
        $.get('{{ route("admin.onus.signal-history", $onu) }}', { period: $(this).val() }, function(res) {
            signalChart.data.labels = res.labels;
            signalChart.data.datasets[0].data = res.rx_data;
            signalChart.data.datasets[1].data = res.tx_data;
            signalChart.update();
        });
    });

    // ========== Traffic ==========
    var lastTrafficRx = null, lastTrafficTx = null, lastTrafficTime = null;

    function rxBadgeClass(val) {
        if (val === null || val === undefined) return 'secondary';
        if (val >= -25) return 'success';
        if (val >= -27) return 'warning';
        return 'danger';
    }

    function refreshTraffic() {
        $.post('/admin/onus/{{ $onu->id }}/refresh-signal', { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res.success && res.data) {
                    var now = new Date();
                    var onuRx = res.data.rx_power, oltRx = res.data.olt_rx_power, dist = res.data.distance;
                    var rxDisplay = onuRx ?? oltRx;
                    var rc = rxBadgeClass(rxDisplay);
                    var onuRxText = (onuRx !== null && onuRx !== undefined) ? parseFloat(onuRx).toFixed(2) : '-';
                    var oltRxText = (oltRx !== null && oltRx !== undefined) ? parseFloat(oltRx).toFixed(2) : '-';
                    var distText = '';
                    if (dist !== null && dist !== undefined && dist > 0) {
                        distText = ' (' + (dist >= 1000 ? (dist / 1000).toFixed(2) + 'km' : dist + 'm') + ')';
                    }
                    $('#onu-signal').removeClass().addClass('badge badge-' + rc + ' px-3 py-2').css('font-size','13px')
                        .html('<i class="fas fa-signal mr-1"></i>' + onuRxText + ' / ' + oltRxText + ' dBm' + distText);

                    $('#traffic-rx').text(res.data.in_octets_formatted || '-');
                    $('#traffic-tx').text(res.data.out_octets_formatted || '-');

                    if (lastTrafficTime !== null && lastTrafficRx !== null) {
                        var timeDiff = (now - lastTrafficTime) / 1000;
                        if (timeDiff > 0) {
                            var rxRate = Math.max(0, ((res.data.in_octets - lastTrafficRx) * 8 / timeDiff / 1000000)).toFixed(2);
                            var txRate = Math.max(0, ((res.data.out_octets - lastTrafficTx) * 8 / timeDiff / 1000000)).toFixed(2);
                            $('#traffic-rx-rate').html('<i class="fas fa-tachometer-alt mr-1"></i>' + rxRate + ' Mbps');
                            $('#traffic-tx-rate').html('<i class="fas fa-tachometer-alt mr-1"></i>' + txRate + ' Mbps');
                            $('#traffic-rx-rate-val').text(rxRate + ' Mbps');
                            $('#traffic-tx-rate-val').text(txRate + ' Mbps');
                            $('#traffic-rx-bar').css('width', Math.min(100, rxRate) + '%');
                            $('#traffic-tx-bar').css('width', Math.min(100, txRate) + '%');
                        }
                    } else {
                        $('#traffic-rx-rate, #traffic-tx-rate').html('<i class="fas fa-clock mr-1"></i>Menghitung...');
                    }
                    lastTrafficRx = res.data.in_octets;
                    lastTrafficTx = res.data.out_octets;
                    lastTrafficTime = now;
                    $('#traffic-updated').html('<i class="fas fa-clock mr-1"></i>Update: ' + now.toLocaleTimeString());
                }
            });
    }
    refreshTraffic();
    setInterval(refreshTraffic, 5000);

    // ========== ONU Actions ==========
    $('.btn-refresh-signal').click(function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Refreshing...');
        $.post('/admin/onus/' + $(this).data('id') + '/refresh-signal', { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                Swal.fire('Berhasil', res.message || 'Signal berhasil di-refresh', 'success').then(function() { location.reload(); });
            })
            .fail(function(xhr) { Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal refresh signal', 'error'); })
            .always(function() { btn.prop('disabled', false).html('<i class="fas fa-signal mr-1"></i>Refresh Signal'); });
    });

    $('.btn-reboot-onu').click(function() {
        var id = $(this).data('id'), btn = $(this);
        Swal.fire({
            title: 'Konfirmasi Reboot', text: 'Apakah Anda yakin ingin me-reboot ONU ini?',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#f39c12', confirmButtonText: 'Ya, Reboot!'
        }).then(function(result) {
            if (result.isConfirmed) {
                btn.prop('disabled', true);
                $.post('/admin/onus/' + id + '/reboot', { _token: '{{ csrf_token() }}' })
                    .done(function(res) { Swal.fire('Berhasil', res.message || 'ONU sedang di-reboot', 'success'); })
                    .fail(function(xhr) { Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal me-reboot ONU', 'error'); })
                    .always(function() { btn.prop('disabled', false); });
            }
        });
    });

    $('.btn-unregister-onu').click(function() {
        var id = $(this).data('id'), sn = $(this).data('sn');
        Swal.fire({
            title: 'Konfirmasi Unregister',
            html: 'Apakah Anda yakin ingin menghapus ONU <strong>' + sn + '</strong>?<br><br><small class="text-danger">ONU akan dihapus dari OLT!</small>',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Ya, Hapus!'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.post('/admin/onus/' + id + '/unregister', { _token: '{{ csrf_token() }}' })
                    .done(function(res) { Swal.fire('Berhasil', res.message, 'success').then(function() { window.location.href = '{{ route("admin.onus.index") }}'; }); })
                    .fail(function(xhr) { Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal menghapus ONU', 'error'); });
            }
        });
    });

    // ========== Config Forms ==========
    $('select[name="mgmt_ip_mode"]').change(function() { $('#mgmt-static-ip').toggle($(this).val() === 'static'); });

    $('#form-management').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Processing...');
        $.ajax({
            url: '/admin/onus/{{ $onu->id }}/configure-management', method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) {
                if (res.success) Swal.fire('Berhasil', res.message, 'success').then(function() { location.reload(); });
                else Swal.fire('Gagal', res.message, 'error');
            },
            error: function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Gagal konfigurasi', 'error'); },
            complete: function() { btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Update Management'); }
        });
    });

    $('#wan-type-select').change(function() { $('#wan-pppoe-fields').toggle($(this).val() === 'pppoe'); });

    $('#form-wan-setup').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Processing...');
        $.ajax({
            url: '/admin/onus/{{ $onu->id }}/configure-wan', method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) {
                if (res.success) Swal.fire('Berhasil', res.message, 'success').then(function() { location.reload(); });
                else Swal.fire('Gagal', res.message, 'error');
            },
            error: function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Gagal konfigurasi WAN', 'error'); },
            complete: function() { btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Update WAN'); }
        });
    });

    // ========== TR069 / ACS Management ==========
    var tr069Data = null;
    var tr069Loaded = false;

    // Load ACS data when tab is shown
    $('a[href="#pane-acs"]').on('shown.bs.tab', function() {
        if (!tr069Loaded) {
            loadTr069Summary();
            tr069Loaded = true;
        }
    });

    function loadTr069Summary() {
        $('#tr069-loading-overlay').show();
        $('#tr069-main, #tr069-not-found, #tr069-unavailable').hide();
        $('#tr069-status-badge').removeClass().addClass('badge badge-secondary ml-1').css('font-size','10px').text('Loading...');

        $.get('/admin/onus/{{ $onu->id }}/tr069-summary')
            .done(function(res) {
                $('#tr069-loading-overlay').hide();

                if (!res.success && !res.available) {
                    $('#tr069-unavailable').show();
                    $('#tr069-status-badge').removeClass().addClass('badge badge-danger ml-1').css('font-size','10px').text('Unavailable');
                    return;
                }
                if (!res.found) {
                    $('#tr069-not-found').show();
                    $('#tr069-status-badge').removeClass().addClass('badge badge-warning ml-1').css('font-size','10px').text('Not Found');
                    return;
                }

                tr069Data = res.data;
                renderTr069(res.data);
                $('#tr069-main').show();
                $('#tr069-status-badge').removeClass().addClass('badge badge-success ml-1').css('font-size','10px').text('Connected');

                if (res.genieacs_ui_url) {
                    $('#tr069-ui-link').attr('href', res.genieacs_ui_url);
                }
            })
            .fail(function() {
                $('#tr069-loading-overlay').hide();
                $('#tr069-unavailable').show();
                $('#tr069-status-badge').removeClass().addClass('badge badge-danger ml-1').css('font-size','10px').text('Error');
            });
    }

    function renderTr069(data) {
        var dev = data.device || {};

        // General Info
        var rows = '';
        if (dev.manufacturer) rows += '<tr><td width="40%">Manufacturer</td><td><strong>' + dev.manufacturer + '</strong></td></tr>';
        if (dev.model) rows += '<tr><td>Model</td><td><strong>' + dev.model + '</strong></td></tr>';
        if (dev.serial_number) rows += '<tr><td>Serial Number</td><td><code>' + dev.serial_number + '</code></td></tr>';
        if (dev.software_version) rows += '<tr><td>Firmware</td><td><span class="badge badge-primary">' + dev.software_version + '</span></td></tr>';
        if (dev.hardware_version) rows += '<tr><td>Hardware Ver.</td><td>' + dev.hardware_version + '</td></tr>';
        if (dev.provisioning_code) rows += '<tr><td>Provisioning</td><td>' + dev.provisioning_code + '</td></tr>';
        if (dev.device_id) rows += '<tr><td>Device ID</td><td><code class="small">' + dev.device_id + '</code></td></tr>';
        $('#tr069-general-table').html(rows);

        // System status
        $('#tr069-cpu').html(dev.cpu_usage ? '<div class="progress" style="height:16px"><div class="progress-bar bg-' + (dev.cpu_usage > 80 ? 'danger' : dev.cpu_usage > 50 ? 'warning' : 'success') + '" style="width:' + dev.cpu_usage + '%">' + dev.cpu_usage + '%</div></div>' : '-');
        $('#tr069-memory').text(dev.memory_status ? (Math.round(dev.memory_status / 1024)) + ' MB' : '-');
        $('#tr069-uptime').text(dev.uptime ? formatUptime(dev.uptime) : '-');
        $('#tr069-inform-time').text(dev.last_inform ? new Date(dev.last_inform).toLocaleString('id-ID') : '-');
        $('#tr069-last-inform').text(dev.last_inform ? 'Last inform: ' + new Date(dev.last_inform).toLocaleString('id-ID') : '');
        $('#tr069-registered').text(dev.registered ? new Date(dev.registered).toLocaleString('id-ID') : '-');

        // Firmware
        $('#fw-current-version').text(dev.software_version || 'Unknown');
        $('#fw-model').text((dev.manufacturer || '') + ' ' + (dev.model || ''));
        $('#fw-hw-version').text('HW: ' + (dev.hardware_version || '-'));

        // WAN
        var wans = data.wan_connections || [];
        $('#tr069-wan-count').text(wans.length);
        if (wans.length === 0) {
            $('#tr069-wan-list').empty();
            $('#tr069-wan-empty').show();
        } else {
            $('#tr069-wan-empty').hide();
            var wanHtml = '';
            wans.forEach(function(wan, i) {
                var sc = wan.status === 'Connected' ? 'connected' : (wan.status === 'Connecting' ? 'connecting' : 'disconnected');
                var statusBadge = wan.status === 'Connected' ? 'success' : (wan.status === 'Connecting' ? 'warning' : 'secondary');
                wanHtml += '<div class="wan-connection-card ' + sc + '">';
                wanHtml += '<div class="d-flex justify-content-between align-items-center">';
                wanHtml += '<div>';
                wanHtml += '<strong><i class="fas fa-globe mr-1"></i>' + (wan.type || 'WAN') + ' ' + (i + 1) + '</strong>';
                if (wan.name) wanHtml += ' <small class="text-muted">(' + wan.name + ')</small>';
                wanHtml += '</div>';
                wanHtml += '<span class="badge badge-' + statusBadge + '">' + (wan.status || 'Unknown') + '</span>';
                wanHtml += '</div>';
                wanHtml += '<div class="mt-2 small">';
                if (wan.username) wanHtml += '<div><i class="fas fa-user text-muted mr-1"></i>Username: <strong>' + wan.username + '</strong></div>';
                if (wan.external_ip) wanHtml += '<div><i class="fas fa-network-wired text-muted mr-1"></i>IP: <code>' + wan.external_ip + '</code></div>';
                if (wan.gateway) wanHtml += '<div><i class="fas fa-route text-muted mr-1"></i>Gateway: <code>' + wan.gateway + '</code></div>';
                if (wan.dns) wanHtml += '<div><i class="fas fa-server text-muted mr-1"></i>DNS: ' + wan.dns + '</div>';
                if (wan.vlan_id) wanHtml += '<div><i class="fas fa-tag text-muted mr-1"></i>VLAN: ' + wan.vlan_id + '</div>';
                if (wan.uptime) wanHtml += '<div><i class="fas fa-clock text-muted mr-1"></i>Uptime: ' + formatUptime(wan.uptime) + '</div>';
                wanHtml += '</div></div>';
            });
            $('#tr069-wan-list').html(wanHtml);
        }

        // WiFi
        var wifis = data.wifi || [];
        $('#tr069-wifi-count').text(wifis.length);
        if (wifis.length === 0) {
            $('#tr069-wifi-list').empty();
            $('#tr069-wifi-empty').show();
        } else {
            $('#tr069-wifi-empty').hide();
            var wifiHtml = '';
            wifis.forEach(function(wifi, i) {
                var enabledClass = wifi.enabled ? 'success' : 'secondary';
                wifiHtml += '<div class="wifi-card">';
                wifiHtml += '<div class="d-flex justify-content-between align-items-center">';
                wifiHtml += '<div>';
                wifiHtml += '<strong><i class="fas fa-wifi text-' + enabledClass + ' mr-1"></i>' + (wifi.ssid || 'SSID ' + (i + 1)) + '</strong>';
                wifiHtml += ' <span class="badge badge-' + enabledClass + '">' + (wifi.enabled ? 'Enabled' : 'Disabled') + '</span>';
                if (wifi.channel) wifiHtml += ' <small class="text-muted ml-1">Ch.' + wifi.channel + '</small>';
                if (wifi.total_associations) wifiHtml += ' <span class="badge badge-light ml-1"><i class="fas fa-laptop mr-1"></i>' + wifi.total_associations + '</span>';
                wifiHtml += '</div>';
                wifiHtml += '<button type="button" class="btn btn-xs btn-outline-info btn-edit-wifi" data-path="' + (wifi.path || '') + '" data-ssid="' + (wifi.ssid || '') + '" data-enabled="' + (wifi.enabled ? '1' : '0') + '"><i class="fas fa-edit mr-1"></i>Edit</button>';
                wifiHtml += '</div>';
                if (wifi.standard || wifi.security_mode) {
                    wifiHtml += '<div class="mt-1 small text-muted">';
                    if (wifi.standard) wifiHtml += '<span class="mr-2">Standard: ' + wifi.standard + '</span>';
                    if (wifi.security_mode) wifiHtml += '<span>Security: ' + wifi.security_mode + '</span>';
                    wifiHtml += '</div>';
                }
                wifiHtml += '</div>';
            });
            $('#tr069-wifi-list').html(wifiHtml);
        }

        // LAN Ports
        var ports = data.lan_ports || [];
        var portHtml = '';
        if (ports.length === 0) {
            portHtml = '<tr><td colspan="4" class="text-center text-muted">Tidak ada data LAN port</td></tr>';
        } else {
            ports.forEach(function(port) {
                var sb = port.status === 'Up' ? 'success' : 'secondary';
                portHtml += '<tr>';
                portHtml += '<td><strong>' + (port.name || '-') + '</strong></td>';
                portHtml += '<td><span class="badge badge-' + sb + '">' + (port.status || '-') + '</span></td>';
                portHtml += '<td>' + (port.max_bit_rate || '-') + '</td>';
                portHtml += '<td><code>' + (port.mac_address || '-') + '</code></td>';
                portHtml += '</tr>';
            });
        }
        $('#tr069-lan-table tbody').html(portHtml);

        // Hosts
        var hosts = data.lan_hosts || [];
        $('#tr069-host-count').text(hosts.length);
        var hostHtml = '';
        if (hosts.length === 0) {
            $('#tr069-hosts-table').closest('.table-responsive').hide();
            $('#tr069-users-empty').show();
        } else {
            $('#tr069-hosts-table').closest('.table-responsive').show();
            $('#tr069-users-empty').hide();
            hosts.forEach(function(host) {
                var active = host.active === true || host.active === '1' || host.active === 1;
                hostHtml += '<tr class="host-row">';
                hostHtml += '<td>' + (host.hostname || host.host_name || '<em class="text-muted">Unknown</em>') + '</td>';
                hostHtml += '<td><code>' + (host.ip || '-') + '</code></td>';
                hostHtml += '<td><code>' + (host.mac || '-') + '</code></td>';
                hostHtml += '<td>' + (host.interface || host.layer2_interface || '-') + '</td>';
                hostHtml += '<td><span class="badge badge-' + (active ? 'success' : 'secondary') + '">' + (active ? 'Active' : 'Inactive') + '</span></td>';
                hostHtml += '</tr>';
            });
        }
        $('#tr069-hosts-table tbody').html(hostHtml);

        // Tasks
        var tasks = data.tasks || [];
        if (tasks.length > 0) {
            $('#tr069-pending-tasks').show();
            $('#tr069-task-count').text(tasks.length);
            var taskHtml = '';
            tasks.forEach(function(task) {
                taskHtml += '<div class="d-flex justify-content-between align-items-center py-1">';
                taskHtml += '<span><i class="fas fa-hourglass-half mr-1 text-warning"></i>' + (task.name || 'Task') + '</span>';
                taskHtml += '<button type="button" class="btn btn-xs btn-outline-danger btn-delete-task" data-task-id="' + task._id + '"><i class="fas fa-times"></i></button>';
                taskHtml += '</div>';
            });
            $('#tr069-task-list').html(taskHtml);
        } else {
            $('#tr069-pending-tasks').hide();
        }
    }

    function formatUptime(seconds) {
        if (!seconds) return '-';
        var d = Math.floor(seconds / 86400);
        var h = Math.floor((seconds % 86400) / 3600);
        var m = Math.floor((seconds % 3600) / 60);
        var parts = [];
        if (d > 0) parts.push(d + 'd');
        if (h > 0) parts.push(h + 'h');
        if (m > 0) parts.push(m + 'm');
        return parts.join(' ') || '< 1m';
    }

    // Refresh TR069
    $(document).on('click', '.btn-refresh-tr069, .btn-refresh-tr069-data', function() {
        var btn = $(this);
        btn.find('i').addClass('fa-spin');
        $.post('/admin/onus/{{ $onu->id }}/tr069-refresh', { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res.success) {
                    setTimeout(function() { loadTr069Summary(); }, 3000);
                } else {
                    Swal.fire('Info', res.message || 'Tidak dapat refresh', 'info');
                }
            })
            .fail(function() { loadTr069Summary(); })
            .always(function() { setTimeout(function() { btn.find('i').removeClass('fa-spin'); }, 1000); });
    });

    // Reboot via TR069
    $('.btn-tr069-reboot').click(function() {
        Swal.fire({
            title: 'Reboot via TR-069?', text: 'ONU akan di-reboot melalui TR-069 protocol.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Reboot'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.post('/admin/onus/{{ $onu->id }}/tr069-reboot', { _token: '{{ csrf_token() }}' })
                    .done(function(res) { Swal.fire('Berhasil', res.message, res.success ? 'success' : 'error'); })
                    .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Gagal', 'error'); });
            }
        });
    });

    // Factory Reset
    $('.btn-factory-reset').click(function() {
        Swal.fire({
            title: 'Factory Reset via TR-069?',
            html: '<div class="text-danger"><strong>PERINGATAN!</strong><br>Semua konfigurasi ONU akan dihapus dan dikembalikan ke pengaturan pabrik.</div>',
            icon: 'error', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Ya, Factory Reset!'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.post('/admin/onus/{{ $onu->id }}/tr069-factory-reset', { _token: '{{ csrf_token() }}' })
                    .done(function(res) { Swal.fire('Berhasil', res.message, res.success ? 'success' : 'error'); })
                    .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Gagal', 'error'); });
            }
        });
    });

    // Clear all tasks
    $('.btn-clear-all-tasks').click(function() {
        var btn = $(this);
        btn.prop('disabled', true);
        $.post('/admin/onus/{{ $onu->id }}/tr069-clear-tasks', { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res.success) {
                    $('#tr069-pending-tasks').fadeOut();
                    Swal.fire('Berhasil', res.message, 'success');
                }
            })
            .always(function() { btn.prop('disabled', false); });
    });

    // Delete single task
    $(document).on('click', '.btn-delete-task', function() {
        var taskId = $(this).data('task-id'), el = $(this).closest('.d-flex');
        $.post('/admin/onus/{{ $onu->id }}/tr069-task-delete', { _token: '{{ csrf_token() }}', task_id: taskId })
            .done(function(res) {
                if (res.success) {
                    el.fadeOut(300, function() { $(this).remove(); });
                    var count = parseInt($('#tr069-task-count').text()) - 1;
                    $('#tr069-task-count').text(Math.max(0, count));
                    if (count <= 0) $('#tr069-pending-tasks').fadeOut();
                }
            });
    });

    // PPPoE Setup
    $('#btn-setup-pppoe').click(function() { $('#modal-pppoe').modal('show'); });

    $('#form-pppoe-setup').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menerapkan...');
        $.post('/admin/onus/{{ $onu->id }}/tr069-wan', {
            _token: '{{ csrf_token() }}',
            pppoe_username: $(this).find('[name="pppoe_username"]').val(),
            pppoe_password: $(this).find('[name="pppoe_password"]').val(),
            vlan: $(this).find('[name="vlan"]').val(),
        })
        .done(function(res) {
            if (res.success) {
                $('#modal-pppoe').modal('hide');
                Swal.fire('Berhasil', 'PPPoE WAN task dikirim. Tunggu beberapa saat lalu refresh.', 'success');
                setTimeout(loadTr069Summary, 5000);
            } else {
                Swal.fire('Gagal', res.message || 'Gagal mengirim task', 'error');
            }
        })
        .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Server error', 'error'); })
        .always(function() { btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i>Terapkan'); });
    });

    // WiFi Edit
    $(document).on('click', '.btn-edit-wifi', function() {
        $('#wifi-path').val($(this).data('path'));
        $('#wifi-ssid').val($(this).data('ssid'));
        $('#wifi-enabled').val($(this).data('enabled'));
        $('#wifi-password').val('');
        $('#modal-wifi').modal('show');
    });

    $('#form-wifi-setup').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');
        $.post('/admin/onus/{{ $onu->id }}/tr069-wifi', {
            _token: '{{ csrf_token() }}',
            wlan_path: $('#wifi-path').val(),
            ssid: $('#wifi-ssid').val(),
            password: $('#wifi-password').val() || undefined,
            enabled: $('#wifi-enabled').val(),
        })
        .done(function(res) {
            if (res.success) {
                $('#modal-wifi').modal('hide');
                Swal.fire('Berhasil', 'Konfigurasi WiFi dikirim.', 'success');
                setTimeout(loadTr069Summary, 5000);
            } else {
                Swal.fire('Gagal', res.message || 'Gagal mengirim task', 'error');
            }
        })
        .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Server error', 'error'); })
        .always(function() { btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan'); });
    });

    // Security tab: load on demand
    $('a[href="#acs-security"]').on('shown.bs.tab', function() {
        if ($('#tr069-security-content').is(':hidden')) {
            loadSecurityInfo();
        }
    });

    function loadSecurityInfo() {
        $('#tr069-security-loading').show();
        $('#tr069-security-content').hide();
        $.get('/admin/onus/{{ $onu->id }}/tr069-security')
            .done(function(res) {
                if (res.success && res.data) {
                    var d = res.data;
                    // ACS Info
                    var acsRows = '';
                    if (d.acs) {
                        acsRows += '<tr><td width="40%">ACS URL</td><td><code class="small">' + (d.acs.url || '-') + '</code></td></tr>';
                        acsRows += '<tr><td>Username</td><td>' + (d.acs.username || '-') + '</td></tr>';
                        acsRows += '<tr><td>Periodic Inform</td><td><span class="badge badge-' + (d.acs.periodic_inform ? 'success' : 'secondary') + '">' + (d.acs.periodic_inform ? 'Enabled' : 'Disabled') + '</span></td></tr>';
                        acsRows += '<tr><td>Inform Interval</td><td>' + (d.acs.periodic_interval ? d.acs.periodic_interval + 's' : '-') + '</td></tr>';
                        acsRows += '<tr><td>Connection Req. URL</td><td><code class="small">' + (d.acs.connection_request_url || '-') + '</code></td></tr>';
                    }
                    $('#tr069-acs-table').html(acsRows);

                    // Security info
                    var secRows = '';
                    secRows += '<tr><td width="40%">Firewall</td><td>' + (d.firewall_level || '<span class="text-muted">N/A</span>') + '</td></tr>';
                    secRows += '<tr><td>Default Gateway</td><td><code>' + (d.default_gateway || '-') + '</code></td></tr>';
                    secRows += '<tr><td>DNS Servers</td><td>';
                    if (d.dns_servers && d.dns_servers.length > 0) {
                        d.dns_servers.forEach(function(dns) { secRows += '<code>' + dns.trim() + '</code> '; });
                    } else {
                        secRows += '-';
                    }
                    secRows += '</td></tr>';
                    if (d.remote_access) {
                        secRows += '<tr><td>Remote Access</td><td><span class="badge badge-' + (d.remote_access.enabled ? 'danger' : 'success') + '">' + (d.remote_access.enabled ? 'Enabled' : 'Disabled') + '</span>';
                        if (d.remote_access.port) secRows += ' Port: ' + d.remote_access.port;
                        secRows += '</td></tr>';
                    }
                    $('#tr069-security-table').html(secRows);
                }
                $('#tr069-security-loading').hide();
                $('#tr069-security-content').show();
            })
            .fail(function() {
                $('#tr069-security-loading').html('<span class="text-danger"><i class="fas fa-times mr-1"></i>Gagal memuat data security</span>');
            });
    }

    // Users tab: refresh
    $('.btn-refresh-users').click(function() {
        var btn = $(this);
        btn.find('i').addClass('fa-spin');
        loadTr069Summary();
        setTimeout(function() { btn.find('i').removeClass('fa-spin'); }, 2000);
    });

    // Firmware upgrade
    $('#form-firmware-upgrade').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        var fileUrl = $(this).find('[name="file_url"]').val();

        Swal.fire({
            title: 'Konfirmasi Upgrade Firmware',
            html: 'Download firmware dari:<br><code>' + $('<div>').text(fileUrl).html() + '</code>',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Mulai Upgrade'
        }).then(function(result) {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Mengirim...');
                $.post('/admin/onus/{{ $onu->id }}/tr069-firmware', {
                    _token: '{{ csrf_token() }}',
                    file_url: fileUrl,
                })
                .done(function(res) {
                    Swal.fire(res.success ? 'Berhasil' : 'Gagal', res.message, res.success ? 'success' : 'error');
                })
                .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Gagal', 'error'); })
                .always(function() { btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt mr-1"></i>Mulai Upgrade'); });
            }
        });
    });
});
</script>
@endpush
@extends('layouts.admin')

@section('title', 'Detail ONU - ' . $onu->serial_number)

@section('page-title', 'Detail ONU: ' . $onu->serial_number)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.onus.index') }}">ONU</a></li>
    <li class="breadcrumb-item active">{{ $onu->serial_number }}</li>
@endsection

@section('content')
<div class="row">
    <!-- ONU Info -->
    <div class="col-lg-4">
        <!-- Status Card -->
        <div class="card card-widget widget-user-2 shadow">
            <div class="widget-user-header bg-{{ $onu->status == 'online' ? 'success' : ($onu->status == 'los' ? 'warning' : 'danger') }}">
                <div class="widget-user-image">
                    <i class="fas fa-hdd fa-3x"></i>
                </div>
                <h3 class="widget-user-username">{{ $onu->serial_number }}</h3>
                <h5 class="widget-user-desc">{{ $onu->name ?? $onu->description ?? '' }}</h5>
            </div>
            <div class="card-footer p-0">
                @php
                    $onuRx = $onu->rx_power;
                    $oltRx = $onu->olt_rx_power;
                    $rxDisplay = $onuRx ?? $oltRx;
                    $rxClass = 'secondary';
                    if ($rxDisplay !== null) {
                        $rxClass = $rxDisplay >= -25 ? 'success' : ($rxDisplay >= -27 ? 'warning' : 'danger');
                    }
                    $dist = $onu->distance;
                    $distFormatted = '';
                    if ($dist) {
                        $distFormatted = $dist >= 1000 ? number_format($dist / 1000, 2) . 'km' : $dist . 'm';
                    }
                @endphp
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-circle text-{{ $onu->status == 'online' ? 'success' : ($onu->status == 'los' ? 'warning' : 'danger') }} mr-1" style="font-size:8px;vertical-align:middle"></i>
                            Status
                            <span class="float-right" id="onu-status">
                                @if($onu->status == 'online')
                                    <span class="badge badge-success">Online</span>
                                @elseif($onu->status == 'offline')
                                    <span class="badge badge-danger">Offline</span>
                                @elseif($onu->status == 'los')
                                    <span class="badge badge-warning">LOS</span>
                                @else
                                    <span class="badge badge-secondary">{{ ucfirst($onu->status ?? 'unknown') }}</span>
                                @endif
                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-signal text-{{ $rxClass }} mr-1" style="font-size:10px"></i>
                            ONU/OLT Rx signal
                            <span class="float-right" id="onu-signal">
                                @if($onuRx !== null || $oltRx !== null)
                                    <span class="text-{{ $rxClass }}">
                                        {{ $onuRx !== null ? number_format($onuRx, 2) : '-' }} dBm
                                        / {{ $oltRx !== null ? number_format($oltRx, 2) : '-' }} dBm
                                        @if($distFormatted) ({{ $distFormatted }}) @endif
                                    </span>
                                    <i class="fas fa-signal text-{{ $rxClass }} ml-1" style="font-size:10px"></i>
                                @else
                                    <span class="text-muted">Memuat...</span>
                                @endif
                            </span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi ONU</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <tr>
                        <td width="40%"><strong>OLT</strong></td>
                        <td>
                            <a href="{{ route('admin.olts.show', $onu->olt) }}">
                                {{ $onu->olt->name }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>PON Port</strong></td>
                        <td>{{ $onu->port ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>ONU ID</strong></td>
                        <td>{{ $onu->onu_id ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Serial Number</strong></td>
                        <td><code>{{ $onu->serial_number }}</code></td>
                    </tr>
                    <tr>
                        <td><strong>ONU Type</strong></td>
                        <td>{{ $onu->onu_type ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Zone</strong></td>
                        <td>{{ $onu->zone->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>ODP</strong></td>
                        <td>{{ $onu->odp->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Line Profile</strong></td>
                        <td>{{ $onu->line_profile ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Service Profile</strong></td>
                        <td>{{ $onu->service_profile ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Pelanggan</strong></td>
                        <td>
                            @if($onu->customer)
                                <a href="{{ route('admin.customers.show', $onu->customer) }}">
                                    {{ $onu->customer->name }}
                                </a>
                            @else
                                <span class="text-muted">Belum dipasangkan</span>
                            @endif
                        </td>
                    </tr>
                    @if(!empty($onu->vlan_config))
                    <tr>
                        <td><strong>VLAN Config</strong></td>
                        <td>
                            @if(is_array($onu->vlan_config))
                                {{ collect($onu->vlan_config)->filter(fn($value) => $value !== null && $value !== '')->map(function($value, $key) {
                                    return ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                                })->implode(', ') ?: '-' }}
                            @else
                                {{ $onu->vlan_config }}
                            @endif
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Last Online</strong></td>
                        <td>{{ $onu->last_online_at ? $onu->last_online_at->diffForHumans() : '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Last Sync</strong></td>
                        <td>{{ $onu->last_sync_at ? $onu->last_sync_at->diffForHumans() : '-' }}</td>
                    </tr>
                </table>
            </div>
            <div class="card-footer">
                @can('onus.reboot')
                <button type="button" class="btn btn-warning btn-sm btn-reboot-onu" data-id="{{ $onu->id }}">
                    <i class="fas fa-sync"></i> Reboot
                </button>
                @endcan
                <button type="button" class="btn btn-info btn-sm btn-refresh-signal" data-id="{{ $onu->id }}">
                    <i class="fas fa-signal"></i> Refresh Signal
                </button>
                @can('onus.unregister')
                <button type="button" class="btn btn-danger btn-sm btn-unregister-onu" 
                        data-id="{{ $onu->id }}" data-sn="{{ $onu->serial_number }}">
                    <i class="fas fa-trash"></i> Unregister
                </button>
                @endcan
            </div>
        </div>

        <!-- Assign Customer -->
        @if(!$onu->customer)
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>Assign ke Pelanggan</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.onus.assign-customer', $onu) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Pilih Pelanggan</label>
                        <select name="customer_id" class="form-control select2-customer" style="width:100%" required>
                            <option value="">-- Pilih Pelanggan --</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-link"></i> Assign
                    </button>
                </form>
            </div>
        </div>
        @endif

        <!-- Management VLAN/IP Config -->
        <div class="card card-outline card-warning collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Management IP</h3>
                <div class="card-tools">
                    <span class="badge badge-{{ $onu->mgmt_ip ? 'success' : 'secondary' }} mr-2" id="mgmt-status-badge">
                        {{ $onu->mgmt_ip ? 'Active' : 'Inactive' }}
                    </span>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                </div>
            </div>
            <div class="card-body" style="display:none;">
                @if($onu->mgmt_ip)
                <p class="mb-2"><strong>Current:</strong> <code>{{ $onu->mgmt_ip }}</code></p>
                @endif
                <form id="form-management">
                    <div class="form-group">
                        <label>Management VLAN ID</label>
                        <input type="number" name="mgmt_vlan" class="form-control form-control-sm" min="1" max="4094" value="111" placeholder="e.g. 111">
                    </div>
                    <div class="form-group">
                        <label>IP Mode</label>
                        <select name="mgmt_ip_mode" class="form-control form-control-sm">
                            <option value="dhcp">DHCP</option>
                            <option value="static">Static IP</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group" id="mgmt-static-ip" style="display:none;">
                        <label>Static IP</label>
                        <input type="text" name="mgmt_ip" class="form-control form-control-sm" placeholder="172.16.x.x">
                    </div>
                    <button type="submit" class="btn btn-warning btn-sm btn-block">
                        <i class="fas fa-save mr-1"></i>Update Management
                    </button>
                </form>
            </div>
        </div>

        <!-- WAN Setup -->
        <div class="card card-outline card-success collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-globe mr-2"></i>WAN Setup</h3>
                <div class="card-tools">
                    @php
                        $wanVlan = $onu->vlan_config['vlan_id'] ?? null;
                    @endphp
                    <span class="badge badge-{{ $wanVlan ? 'info' : 'secondary' }} mr-2">
                        {{ $wanVlan ? 'VLAN ' . $wanVlan : 'Not Set' }}
                    </span>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                </div>
            </div>
            <div class="card-body" style="display:none;">
                <form id="form-wan-setup">
                    <div class="form-group">
                        <label>WAN VLAN-ID</label>
                        <input type="number" name="wan_vlan" class="form-control form-control-sm" min="1" max="4094" value="{{ $wanVlan ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label>ONU Mode</label>
                        <div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" name="wan_mode" value="routing" class="custom-control-input" id="wan-mode-routing" checked>
                                <label class="custom-control-label" for="wan-mode-routing">Routing</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" name="wan_mode" value="bridging" class="custom-control-input" id="wan-mode-bridging">
                                <label class="custom-control-label" for="wan-mode-bridging">Bridging</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>WAN Type</label>
                        <select name="wan_type" id="wan-type-select" class="form-control form-control-sm">
                            <option value="manual">Setup via ONU webpage</option>
                            <option value="dhcp">DHCP</option>
                            <option value="pppoe">PPPoE</option>
                            <option value="static">Static IP</option>
                        </select>
                    </div>
                    <div id="wan-pppoe-fields" style="display:none;">
                        <div class="form-group">
                            <label>PPPoE Username</label>
                            <input type="text" name="pppoe_username" class="form-control form-control-sm" value="{{ $onu->pppoe_username }}" placeholder="username@isp">
                        </div>
                        <div class="form-group">
                            <label>PPPoE Password</label>
                            <input type="password" name="pppoe_password" class="form-control form-control-sm" placeholder="password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm btn-block">
                        <i class="fas fa-save mr-1"></i>Update WAN
                    </button>
                </form>
            </div>
        </div>

        <!-- TR069 / ACS Management -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-satellite-dish mr-2"></i>TR069 / ACS Management</h3>
                <div class="card-tools">
                    <span class="badge badge-secondary mr-2" id="tr069-status-badge">Loading...</span>
                    <button type="button" class="btn btn-tool btn-refresh-tr069" title="Refresh TR069"><i class="fas fa-sync"></i></button>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body p-0" id="tr069-content">
                {{-- Loading state --}}
                <div class="text-center py-3" id="tr069-loading">
                    <i class="fas fa-spinner fa-spin"></i> Memuat info TR069...
                </div>

                {{-- Not found --}}
                <div id="tr069-not-found" style="display:none;" class="text-center py-3 text-muted">
                    <i class="fas fa-times-circle fa-2x mb-2 d-block"></i>
                    ONU belum terdaftar di GenieACS.<br>
                    <small>Pastikan ACS URL sudah ter-set dan ONU online.</small>
                </div>

                {{-- Unavailable --}}
                <div id="tr069-unavailable" style="display:none;" class="text-center py-3 text-muted">
                    <i class="fas fa-server fa-2x mb-2 d-block"></i>
                    GenieACS server tidak tersedia
                </div>

                {{-- Main content (shown when device found) --}}
                <div id="tr069-main" style="display:none;">

                    {{-- Pending Tasks --}}
                    <div id="tr069-pending-tasks" style="display:none;">
                        <div class="callout callout-warning m-2 py-2 px-3">
                            <h6 class="mb-1"><i class="fas fa-clock mr-1"></i>Pending Actions <span class="badge badge-warning" id="tr069-task-count">0</span></h6>
                            <div id="tr069-task-list" class="small"></div>
                        </div>
                    </div>

                    {{-- General Info --}}
                    <div class="tr069-section">
                        <a class="d-block p-2 px-3 bg-light border-bottom text-dark" data-toggle="collapse" href="#tr069-general">
                            <i class="fas fa-home mr-2"></i>General
                            <i class="fas fa-chevron-down float-right mt-1"></i>
                        </a>
                        <div class="collapse show" id="tr069-general">
                            <table class="table table-sm table-striped mb-0" id="tr069-general-table"></table>
                        </div>
                    </div>

                    {{-- PPP Interface / WAN --}}
                    <div class="tr069-section">
                        <a class="d-block p-2 px-3 bg-light border-bottom text-dark" data-toggle="collapse" href="#tr069-wan-section">
                            <i class="fas fa-globe mr-2"></i>PPP Interface (WAN)
                            <span class="badge badge-info float-right mt-1 mr-3" id="tr069-wan-count">0</span>
                        </a>
                        <div class="collapse show" id="tr069-wan-section">
                            <div id="tr069-wan-list"></div>
                            <div class="p-2 border-top">
                                <button type="button" class="btn btn-sm btn-success btn-block" id="btn-setup-pppoe">
                                    <i class="fas fa-plus mr-1"></i>Setup PPPoE WAN
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- WiFi --}}
                    <div class="tr069-section">
                        <a class="d-block p-2 px-3 bg-light border-bottom text-dark" data-toggle="collapse" href="#tr069-wifi-section">
                            <i class="fas fa-wifi mr-2"></i>Wireless LAN
                            <span class="badge badge-info float-right mt-1 mr-3" id="tr069-wifi-count">0</span>
                        </a>
                        <div class="collapse" id="tr069-wifi-section">
                            <div id="tr069-wifi-list"></div>
                        </div>
                    </div>

                    {{-- LAN Ports --}}
                    <div class="tr069-section">
                        <a class="d-block p-2 px-3 bg-light border-bottom text-dark" data-toggle="collapse" href="#tr069-lan-section">
                            <i class="fas fa-ethernet mr-2"></i>LAN Ports
                        </a>
                        <div class="collapse" id="tr069-lan-section">
                            <table class="table table-sm table-striped mb-0" id="tr069-lan-table"></table>
                        </div>
                    </div>

                    {{-- Connected Hosts --}}
                    <div class="tr069-section">
                        <a class="d-block p-2 px-3 bg-light border-bottom text-dark" data-toggle="collapse" href="#tr069-hosts-section">
                            <i class="fas fa-laptop mr-2"></i>Hosts
                            <span class="badge badge-info float-right mt-1 mr-3" id="tr069-host-count">0</span>
                        </a>
                        <div class="collapse" id="tr069-hosts-section">
                            <table class="table table-sm table-striped mb-0" id="tr069-hosts-table"></table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer" id="tr069-actions" style="display:none;">
                <a href="#" id="tr069-ui-link" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-external-link-alt mr-1"></i>GenieACS
                </a>
                <button type="button" class="btn btn-sm btn-outline-info btn-refresh-tr069-data">
                    <i class="fas fa-sync mr-1"></i>Refresh
                </button>
            </div>
        </div>

        {{-- PPPoE Setup Modal --}}
        <div class="modal fade" id="modal-pppoe" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title text-white"><i class="fas fa-globe mr-2"></i>Setup PPPoE WAN</h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form id="form-pppoe-setup">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>WAN VLAN-ID</label>
                                <select class="form-control" name="vlan" id="pppoe-vlan">
                                    @if($onu->vlan_config['vlan_id'] ?? null)
                                        <option value="{{ $onu->vlan_config['vlan_id'] }}" selected>{{ $onu->vlan_config['vlan_id'] }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Config Method</label>
                                <div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" class="custom-control-input" name="config_method" id="method-tr069" value="tr069" checked>
                                        <label class="custom-control-label" for="method-tr069">TR069</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>WAN Mode</label>
                                <div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" class="custom-control-input" name="wan_mode" id="wan-pppoe" value="pppoe" checked>
                                        <label class="custom-control-label" for="wan-pppoe">PPPoE</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" class="custom-control-input" name="wan_mode" id="wan-dhcp" value="dhcp">
                                        <label class="custom-control-label" for="wan-dhcp">DHCP</label>
                                    </div>
                                </div>
                            </div>
                            <div id="pppoe-fields">
                                <div class="form-group">
                                    <label>PPPoE Username</label>
                                    <input type="text" name="pppoe_username" class="form-control" placeholder="username@isp" value="{{ $onu->pppoe_username }}" required>
                                </div>
                                <div class="form-group">
                                    <label>PPPoE Password</label>
                                    <input type="text" name="pppoe_password" class="form-control" placeholder="password" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane mr-1"></i>Terapkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- WiFi Edit Modal --}}
        <div class="modal fade" id="modal-wifi" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-info">
                        <h5 class="modal-title text-white"><i class="fas fa-wifi mr-2"></i>Edit Wireless</h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form id="form-wifi-setup">
                        <input type="hidden" name="wlan_path" id="wifi-path">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>SSID</label>
                                <input type="text" name="ssid" id="wifi-ssid" class="form-control" maxlength="32">
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="text" name="password" id="wifi-password" class="form-control" minlength="8" maxlength="63" placeholder="Min 8 karakter">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="enabled" id="wifi-enabled" class="form-control">
                                    <option value="1">Enabled</option>
                                    <option value="0">Disabled</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i>Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Signal Chart & Details -->
    <div class="col-lg-8">
        <!-- Signal History Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Histori Signal (7 Hari Terakhir)</h3>
                <div class="card-tools">
                    <select id="chart-period" class="form-control form-control-sm">
                        <option value="24h">24 Jam</option>
                        <option value="7d" selected>7 Hari</option>
                        <option value="30d">30 Hari</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <canvas id="signal-chart" style="height: 300px;"></canvas>
            </div>
        </div>

        <!-- Traffic Realtime -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tachometer-alt mr-2"></i>Traffic Realtime</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool btn-refresh-traffic" title="Refresh Traffic">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Download (RX)</span>
                                <span class="info-box-number" id="traffic-rx">-</span>
                                <div class="progress">
                                    <div class="progress-bar" id="traffic-rx-bar" style="width: 0%"></div>
                                </div>
                                <span class="progress-description" id="traffic-rx-rate">Memuat...</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Upload (TX)</span>
                                <span class="info-box-number" id="traffic-tx">-</span>
                                <div class="progress">
                                    <div class="progress-bar" id="traffic-tx-bar" style="width: 0%"></div>
                                </div>
                                <span class="progress-description" id="traffic-tx-rate">Memuat...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-muted small text-center" id="traffic-updated">
                    <i class="fas fa-clock mr-1"></i>Terakhir update: -
                </div>
            </div>
        </div>

        <!-- Recent Signal History -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Riwayat Signal Terbaru</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>RX Power</th>
                                <th>TX Power</th>
                                <th>Status</th>
                                <th>Distance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($signalHistory as $history)
                            <tr>
                                <td>{{ $history->recorded_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @php
                                        $histRx = $history->rx_power;
                                        $histRxClass = $histRx >= -25 ? 'success' : ($histRx >= -27 ? 'warning' : 'danger');
                                    @endphp
                                    <span class="badge badge-{{ $histRxClass }}">
                                        {{ number_format($histRx, 2) }} dBm
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        {{ $history->tx_power ? number_format($history->tx_power, 2) . ' dBm' : '-' }}
                                    </span>
                                </td>
                                <td>
                                    @if($history->status == 'online')
                                        <span class="badge badge-success">Online</span>
                                    @elseif($history->status == 'offline')
                                        <span class="badge badge-danger">Offline</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($history->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $history->distance ? number_format($history->distance, 2) . ' km' : '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Belum ada data histori</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(function() {
    // Select2 for customer
    $('.select2-customer').select2({
        theme: 'bootstrap4',
        ajax: {
            url: '{{ route("admin.customers.search") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    pop_id: '{{ $onu->olt->pop_id }}',
                    without_onu: true
                };
            },
            processResults: function(data) {
                var results = data.results || [];
                return {
                    results: results.map(function(item) {
                        return { id: item.id, text: item.customer_id + ' - ' + item.name };
                    })
                };
            }
        }
    });

    // Signal Chart
    var ctx = document.getElementById('signal-chart').getContext('2d');
    var signalChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels ?? []) !!},
            datasets: [{
                label: 'RX Power (dBm)',
                data: {!! json_encode($chartRxData ?? []) !!},
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.3,
                fill: true
            }, {
                label: 'TX Power (dBm)',
                data: {!! json_encode($chartTxData ?? []) !!},
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    title: {
                        display: true,
                        text: 'Power (dBm)'
                    }
                }
            },
            plugins: {
                annotation: {
                    annotations: {
                        warningLine: {
                            type: 'line',
                            yMin: -25,
                            yMax: -25,
                            borderColor: 'orange',
                            borderWidth: 1,
                            borderDash: [5, 5],
                            label: {
                                enabled: true,
                                content: 'Warning (-25dBm)'
                            }
                        },
                        criticalLine: {
                            type: 'line',
                            yMin: -27,
                            yMax: -27,
                            borderColor: 'red',
                            borderWidth: 1,
                            borderDash: [5, 5],
                            label: {
                                enabled: true,
                                content: 'Critical (-27dBm)'
                            }
                        }
                    }
                }
            }
        }
    });

    // Period change
    $('#chart-period').change(function() {
        var period = $(this).val();
        $.get('{{ route("admin.onus.signal-history", $onu) }}', { period: period }, function(res) {
            signalChart.data.labels = res.labels;
            signalChart.data.datasets[0].data = res.rx_data;
            signalChart.data.datasets[1].data = res.tx_data;
            signalChart.update();
        });
    });

    // Traffic variables for rate calculation
    var lastTrafficRx = null;
    var lastTrafficTx = null;
    var lastTrafficTime = null;

    // Helper: get RX badge class from dBm value
    function rxBadgeClass(val) {
        if (val === null || val === undefined) return 'secondary';
        if (val >= -25) return 'success';
        if (val >= -27) return 'warning';
        return 'danger';
    }

    // Refresh Traffic function
    function refreshTraffic() {
        $.post('/admin/onus/{{ $onu->id }}/refresh-signal', { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res.success && res.data) {
                    var now = new Date();

                    // Update ONU/OLT Rx signal (SmartOLT format)
                    var onuRx = res.data.rx_power;
                    var oltRx = res.data.olt_rx_power;
                    var dist = res.data.distance;
                    var rxDisplay = onuRx ?? oltRx;
                    var rc = rxBadgeClass(rxDisplay);

                    var onuRxText = (onuRx !== null && onuRx !== undefined) ? parseFloat(onuRx).toFixed(2) : '-';
                    var oltRxText = (oltRx !== null && oltRx !== undefined) ? parseFloat(oltRx).toFixed(2) : '-';
                    var distText = '';
                    if (dist !== null && dist !== undefined && dist > 0) {
                        distText = ' (' + (dist >= 1000 ? (dist / 1000).toFixed(2) + 'km' : dist + 'm') + ')';
                    }
                    $('#onu-signal').html(
                        '<span class="text-' + rc + '">' +
                        onuRxText + ' dBm / ' + oltRxText + ' dBm' + distText +
                        '</span> <i class="fas fa-signal text-' + rc + ' ml-1" style="font-size:10px"></i>'
                    );
                    
                    // Update total traffic display
                    $('#traffic-rx').text(res.data.in_octets_formatted || '-');
                    $('#traffic-tx').text(res.data.out_octets_formatted || '-');
                    
                    // Calculate rate if we have previous data
                    if (lastTrafficTime !== null && lastTrafficRx !== null) {
                        var timeDiff = (now - lastTrafficTime) / 1000; // seconds
                        if (timeDiff > 0) {
                            var rxRate = ((res.data.in_octets - lastTrafficRx) * 8 / timeDiff / 1000000).toFixed(2);
                            var txRate = ((res.data.out_octets - lastTrafficTx) * 8 / timeDiff / 1000000).toFixed(2);
                            
                            // Prevent negative rates (counter reset)
                            rxRate = Math.max(0, rxRate);
                            txRate = Math.max(0, txRate);
                            
                            $('#traffic-rx-rate').html('<i class="fas fa-tachometer-alt mr-1"></i>' + rxRate + ' Mbps');
                            $('#traffic-tx-rate').html('<i class="fas fa-tachometer-alt mr-1"></i>' + txRate + ' Mbps');
                            
                            // Update progress bars (max 100 Mbps scale)
                            $('#traffic-rx-bar').css('width', Math.min(100, rxRate) + '%');
                            $('#traffic-tx-bar').css('width', Math.min(100, txRate) + '%');
                        }
                    } else {
                        $('#traffic-rx-rate').html('<i class="fas fa-clock mr-1"></i>Menghitung...');
                        $('#traffic-tx-rate').html('<i class="fas fa-clock mr-1"></i>Menghitung...');
                    }
                    
                    // Save for next calculation
                    lastTrafficRx = res.data.in_octets;
                    lastTrafficTx = res.data.out_octets;
                    lastTrafficTime = now;
                    
                    // Update timestamp
                    $('#traffic-updated').html('<i class="fas fa-clock mr-1"></i>Terakhir update: ' + now.toLocaleTimeString());
                }
            })
            .fail(function(xhr) {
                $('#traffic-rx-rate').html('<span class="text-danger">Error</span>');
                $('#traffic-tx-rate').html('<span class="text-danger">Error</span>');
            });
    }

    // Initial load and auto-refresh every 5 seconds
    refreshTraffic();
    var trafficInterval = setInterval(refreshTraffic, 5000);

    // Manual refresh button
    $('.btn-refresh-traffic').click(function() {
        var btn = $(this);
        btn.find('i').addClass('fa-spin');
        refreshTraffic();
        setTimeout(function() { btn.find('i').removeClass('fa-spin'); }, 500);
    });

    // Refresh Signal
    $('.btn-refresh-signal').click(function() {
        var id = $(this).data('id');
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.post('/admin/onus/' + id + '/refresh-signal', { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                Swal.fire('Berhasil', res.message || 'Signal berhasil di-refresh', 'success')
                    .then(() => location.reload());
            })
            .fail(function(xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal refresh signal', 'error');
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-signal"></i> Refresh Signal');
            });
    });

    // Reboot ONU
    $('.btn-reboot-onu').click(function() {
        var id = $(this).data('id');
        var btn = $(this);
        
        Swal.fire({
            title: 'Konfirmasi Reboot',
            text: 'Apakah Anda yakin ingin me-reboot ONU ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            confirmButtonText: 'Ya, Reboot!'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true);
                $.post('/admin/onus/' + id + '/reboot', { _token: '{{ csrf_token() }}' })
                    .done(function(res) {
                        Swal.fire('Berhasil', res.message || 'ONU sedang di-reboot', 'success');
                    })
                    .fail(function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal me-reboot ONU', 'error');
                    })
                    .always(function() {
                        btn.prop('disabled', false);
                    });
            }
        });
    });

    // Unregister ONU
    $('.btn-unregister-onu').click(function() {
        var id = $(this).data('id');
        var sn = $(this).data('sn');
        
        Swal.fire({
            title: 'Konfirmasi Unregister',
            html: `Apakah Anda yakin ingin menghapus ONU <strong>${sn}</strong>?<br><br><small class="text-danger">ONU akan dihapus dari OLT!</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/onus/' + id + '/unregister',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        Swal.fire('Berhasil', res.message || 'ONU berhasil dihapus', 'success')
                            .then(() => window.location.href = '{{ route("admin.onus.index") }}');
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal menghapus ONU', 'error');
                    }
                });
            }
        });
    });

    // ========== Management VLAN/IP ==========
    $('select[name="mgmt_ip_mode"]').change(function() {
        $('#mgmt-static-ip').toggle($(this).val() === 'static');
    });

    $('#form-management').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Processing...');

        $.ajax({
            url: '/admin/onus/{{ $onu->id }}/configure-management',
            method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) {
                if (res.success) {
                    Swal.fire('Berhasil', res.message || 'Management IP berhasil dikonfigurasi', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal konfigurasi', 'error');
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || 'Gagal konfigurasi management';
                if (xhr.responseJSON?.errors) {
                    msg += '<br>' + Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire('Error', msg, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Update Management');
            }
        });
    });

    // ========== WAN Setup ==========
    $('#wan-type-select').change(function() {
        $('#wan-pppoe-fields').toggle($(this).val() === 'pppoe');
    });

    $('#form-wan-setup').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Processing...');

        $.ajax({
            url: '/admin/onus/{{ $onu->id }}/configure-wan',
            method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) {
                if (res.success) {
                    Swal.fire('Berhasil', res.message || 'WAN berhasil dikonfigurasi', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal konfigurasi WAN', 'error');
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || 'Gagal konfigurasi WAN';
                if (xhr.responseJSON?.errors) {
                    msg += '<br>' + Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire('Error', msg, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Update WAN');
            }
        });
    });

    // ========== TR069 / ACS Management ==========
    var tr069Data = null;

    function loadTr069Summary() {
        $('#tr069-loading').show();
        $('#tr069-main, #tr069-not-found, #tr069-unavailable, #tr069-actions').hide();
        $('#tr069-status-badge').removeClass().addClass('badge badge-secondary mr-2').text('Loading...');

        $.get('/admin/onus/{{ $onu->id }}/tr069-summary')
            .done(function(res) {
                $('#tr069-loading').hide();

                if (!res.success && !res.available) {
                    $('#tr069-unavailable').show();
                    $('#tr069-status-badge').removeClass().addClass('badge badge-danger mr-2').text('Unavailable');
                    return;
                }

                if (!res.found) {
                    $('#tr069-not-found').show();
                    $('#tr069-status-badge').removeClass().addClass('badge badge-warning mr-2').text('Not Found');
                    return;
                }

                tr069Data = res.data;
                renderTr069(res.data);
                $('#tr069-main, #tr069-actions').show();
                $('#tr069-status-badge').removeClass().addClass('badge badge-success mr-2').text('Connected');

                if (res.genieacs_ui_url) {
                    $('#tr069-ui-link').attr('href', res.genieacs_ui_url);
                }
            })
            .fail(function() {
                $('#tr069-loading').hide();
                $('#tr069-unavailable').show();
                $('#tr069-status-badge').removeClass().addClass('badge badge-danger mr-2').text('Error');
            });
    }

    function renderTr069(data) {
        // General Info
        var dev = data.device || {};
        var rows = '';
        if (dev.manufacturer) rows += '<tr><td width="40%">Manufacturer</td><td><strong>' + dev.manufacturer + '</strong></td></tr>';
        if (dev.model) rows += '<tr><td>Model</td><td><strong>' + dev.model + '</strong></td></tr>';
        if (dev.serial_number) rows += '<tr><td>Serial</td><td><code>' + dev.serial_number + '</code></td></tr>';
        if (dev.software_version) rows += '<tr><td>Software</td><td>' + dev.software_version + '</td></tr>';
        if (dev.hardware_version) rows += '<tr><td>Hardware</td><td>' + dev.hardware_version + '</td></tr>';
        if (dev.uptime) rows += '<tr><td>Uptime</td><td>' + formatUptime(dev.uptime) + '</td></tr>';
        if (dev.last_inform) rows += '<tr><td>Last Inform</td><td>' + new Date(dev.last_inform).toLocaleString('id-ID') + '</td></tr>';
        $('#tr069-general-table').html(rows);

        // WAN Connections
        var wans = data.wan_connections || [];
        $('#tr069-wan-count').text(wans.length);
        var wanHtml = '';
        if (wans.length === 0) {
            wanHtml = '<div class="text-muted text-center py-2 small">Belum ada WAN connection</div>';
        } else {
            wans.forEach(function(wan, i) {
                var statusClass = wan.status === 'Connected' ? 'success' : (wan.status === 'Connecting' ? 'warning' : 'secondary');
                wanHtml += '<div class="p-2 border-bottom">';
                wanHtml += '<div class="d-flex justify-content-between align-items-center">';
                wanHtml += '<div><strong><i class="fas fa-globe mr-1"></i>' + (wan.type || 'WAN') + ' ' + (i + 1) + '</strong>';
                if (wan.name) wanHtml += ' <small class="text-muted">(' + wan.name + ')</small>';
                wanHtml += '</div>';
                wanHtml += '<span class="badge badge-' + statusClass + '">' + (wan.status || 'Unknown') + '</span>';
                wanHtml += '</div>';
                wanHtml += '<div class="mt-1 small">';
                if (wan.username) wanHtml += '<span class="mr-3"><i class="fas fa-user mr-1"></i>' + wan.username + '</span>';
                if (wan.external_ip) wanHtml += '<span class="mr-3"><i class="fas fa-network-wired mr-1"></i>' + wan.external_ip + '</span>';
                if (wan.vlan_id) wanHtml += '<span><i class="fas fa-tag mr-1"></i>VLAN ' + wan.vlan_id + '</span>';
                wanHtml += '</div></div>';
            });
        }
        $('#tr069-wan-list').html(wanHtml);

        // WiFi
        var wifis = data.wifi || [];
        $('#tr069-wifi-count').text(wifis.length);
        var wifiHtml = '';
        if (wifis.length === 0) {
            wifiHtml = '<div class="text-muted text-center py-2 small">Tidak ada data WiFi</div>';
        } else {
            wifis.forEach(function(wifi, i) {
                var enabledClass = wifi.enabled ? 'success' : 'secondary';
                var enabledText = wifi.enabled ? 'Up' : 'Down';
                wifiHtml += '<div class="p-2 border-bottom d-flex justify-content-between align-items-center">';
                wifiHtml += '<div><strong><i class="fas fa-wifi mr-1"></i>' + (wifi.ssid || 'SSID ' + (i + 1)) + '</strong>';
                wifiHtml += ' <span class="badge badge-' + enabledClass + ' ml-1">' + enabledText + '</span>';
                if (wifi.total_associations) wifiHtml += ' <small class="text-muted ml-1">' + wifi.total_associations + ' clients</small>';
                wifiHtml += '</div>';
                wifiHtml += '<button type="button" class="btn btn-xs btn-outline-info btn-edit-wifi" data-path="' + wifi.path + '" data-ssid="' + (wifi.ssid || '') + '" data-enabled="' + (wifi.enabled ? '1' : '0') + '"><i class="fas fa-edit"></i></button>';
                wifiHtml += '</div>';
            });
        }
        $('#tr069-wifi-list').html(wifiHtml);

        // LAN Ports
        var ports = data.lan_ports || [];
        var portRows = '';
        if (ports.length === 0) {
            portRows = '<tr><td class="text-center text-muted" colspan="4">Tidak ada data</td></tr>';
        } else {
            portRows = '<thead><tr><th>Port</th><th>Status</th><th>Speed</th><th>MAC</th></tr></thead><tbody>';
            ports.forEach(function(port) {
                var sb = port.status === 'Up' ? 'success' : 'secondary';
                portRows += '<tr><td><strong>' + (port.name || '-') + '</strong></td>';
                portRows += '<td><span class="badge badge-' + sb + '">' + (port.status || '-') + '</span></td>';
                portRows += '<td>' + (port.max_bit_rate || '-') + '</td>';
                portRows += '<td><code>' + (port.mac_address || '-') + '</code></td></tr>';
            });
            portRows += '</tbody>';
        }
        $('#tr069-lan-table').html(portRows);

        // Hosts
        var hosts = data.lan_hosts || [];
        $('#tr069-host-count').text(hosts.length);
        var hostRows = '';
        if (hosts.length === 0) {
            hostRows = '<tr><td class="text-center text-muted" colspan="4">Tidak ada host</td></tr>';
        } else {
            hostRows = '<thead><tr><th>Hostname</th><th>IP</th><th>MAC</th><th>Active</th></tr></thead><tbody>';
            hosts.forEach(function(host) {
                var active = host.active === true || host.active === '1' || host.active === 1;
                hostRows += '<tr><td>' + (host.hostname || '-') + '</td>';
                hostRows += '<td><code>' + (host.ip || '-') + '</code></td>';
                hostRows += '<td><code>' + (host.mac || '-') + '</code></td>';
                hostRows += '<td><span class="badge badge-' + (active ? 'success' : 'secondary') + '">' + (active ? 'Active' : '-') + '</span></td></tr>';
            });
            hostRows += '</tbody>';
        }
        $('#tr069-hosts-table').html(hostRows);

        // Pending Tasks
        var tasks = data.tasks || [];
        if (tasks.length > 0) {
            $('#tr069-pending-tasks').show();
            $('#tr069-task-count').text(tasks.length);
            var taskHtml = '';
            tasks.forEach(function(task) {
                taskHtml += '<div class="d-flex justify-content-between align-items-center mb-1">';
                taskHtml += '<span>' + (task.name || 'Task') + '</span>';
                taskHtml += '<button type="button" class="btn btn-xs btn-outline-danger btn-delete-task" data-task-id="' + task._id + '"><i class="fas fa-times"></i></button>';
                taskHtml += '</div>';
            });
            $('#tr069-task-list').html(taskHtml);
        } else {
            $('#tr069-pending-tasks').hide();
        }
    }

    function formatUptime(seconds) {
        if (!seconds) return '-';
        var d = Math.floor(seconds / 86400);
        var h = Math.floor((seconds % 86400) / 3600);
        var m = Math.floor((seconds % 3600) / 60);
        var parts = [];
        if (d > 0) parts.push(d + 'd');
        if (h > 0) parts.push(h + 'h');
        if (m > 0) parts.push(m + 'm');
        return parts.join(' ') || '< 1m';
    }

    loadTr069Summary();

    // Refresh TR069
    $('.btn-refresh-tr069, .btn-refresh-tr069-data').click(function() {
        var btn = $(this);
        btn.find('i').addClass('fa-spin');
        $.post('/admin/onus/{{ $onu->id }}/tr069-refresh', { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res.success) {
                    setTimeout(loadTr069Summary, 3000);
                } else {
                    Swal.fire('Info', res.message || 'Tidak dapat refresh', 'info');
                }
            })
            .fail(function() { loadTr069Summary(); })
            .always(function() {
                setTimeout(function() { btn.find('i').removeClass('fa-spin'); }, 1000);
            });
    });

    // PPPoE WAN Setup
    $('#btn-setup-pppoe').click(function() { $('#modal-pppoe').modal('show'); });

    $('input[name="wan_mode"]').change(function() {
        $('#pppoe-fields').toggle($(this).val() === 'pppoe');
    });

    $('#form-pppoe-setup').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menerapkan...');
        $.post('/admin/onus/{{ $onu->id }}/tr069-wan', {
            _token: '{{ csrf_token() }}',
            pppoe_username: $(this).find('[name="pppoe_username"]').val(),
            pppoe_password: $(this).find('[name="pppoe_password"]').val(),
            vlan: $(this).find('[name="vlan"]').val(),
        })
        .done(function(res) {
            if (res.success) {
                $('#modal-pppoe').modal('hide');
                Swal.fire('Berhasil', 'PPPoE WAN task dikirim. Tunggu beberapa saat lalu refresh.', 'success');
                setTimeout(loadTr069Summary, 5000);
            } else {
                Swal.fire('Gagal', res.message || 'Gagal mengirim task', 'error');
            }
        })
        .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Server error', 'error'); })
        .always(function() { btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i>Terapkan'); });
    });

    // WiFi Edit
    $(document).on('click', '.btn-edit-wifi', function() {
        $('#wifi-path').val($(this).data('path'));
        $('#wifi-ssid').val($(this).data('ssid'));
        $('#wifi-enabled').val($(this).data('enabled'));
        $('#wifi-password').val('');
        $('#modal-wifi').modal('show');
    });

    $('#form-wifi-setup').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');
        $.post('/admin/onus/{{ $onu->id }}/tr069-wifi', {
            _token: '{{ csrf_token() }}',
            wlan_path: $('#wifi-path').val(),
            ssid: $('#wifi-ssid').val(),
            password: $('#wifi-password').val() || undefined,
            enabled: $('#wifi-enabled').val(),
        })
        .done(function(res) {
            if (res.success) {
                $('#modal-wifi').modal('hide');
                Swal.fire('Berhasil', 'Konfigurasi WiFi dikirim. Tunggu beberapa saat lalu refresh.', 'success');
                setTimeout(loadTr069Summary, 5000);
            } else {
                Swal.fire('Gagal', res.message || 'Gagal mengirim task', 'error');
            }
        })
        .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Server error', 'error'); })
        .always(function() { btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan'); });
    });

    // Delete pending task
    $(document).on('click', '.btn-delete-task', function() {
        var taskId = $(this).data('task-id');
        var el = $(this).closest('.d-flex');
        $.post('/admin/onus/{{ $onu->id }}/tr069-task-delete', { _token: '{{ csrf_token() }}', task_id: taskId })
        .done(function(res) {
            if (res.success) {
                el.fadeOut(300, function() { $(this).remove(); });
                var count = parseInt($('#tr069-task-count').text()) - 1;
                $('#tr069-task-count').text(Math.max(0, count));
            }
        });
    });
});
</script>
@endpush
