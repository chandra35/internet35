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
    .wifi-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .wifi-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,.08); transform: translateY(-1px); }
    .wifi-card.wifi-disabled { opacity: 0.6; background: #f8f9fa; }
    .wifi-card .wifi-status-bar { position: absolute; top: 0; left: 0; right: 0; height: 3px; }
    .wifi-card .wifi-status-bar.active { background: linear-gradient(90deg, #28a745, #20c997); }
    .wifi-card .wifi-status-bar.inactive { background: #dee2e6; }
    .wifi-band-header { padding: 8px 14px; border-radius: 8px; margin-bottom: 12px; }
    .wifi-band-header.band-24 { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: #fff; }
    .wifi-band-header.band-5 { background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); color: #fff; }
    .wifi-band-header.band-unknown { background: #6c757d; color: #fff; }
    .wifi-signal-icon { font-size: 1.5rem; }
    .wifi-ssid-name { font-size: 1.05rem; font-weight: 600; }
    .wifi-meta-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 6px; margin-top: 10px; }
    .wifi-meta-item { font-size: 11.5px; color: #6c757d; display: flex; align-items: center; gap: 5px; }
    .wifi-meta-item i { width: 14px; text-align: center; }
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
                        <button type="button" class="btn btn-dark btn-sm quick-action-btn btn-olt-factory-reset mb-1" data-id="{{ $onu->id }}" data-sn="{{ $onu->serial_number }}">
                            <i class="fas fa-undo mr-1"></i>Factory Reset
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
                    <div id="tr069-acs-toolbar" class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
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
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#acs-clients"><i class="fas fa-laptop mr-1"></i>Clients <span class="badge badge-light" id="tr069-host-count">0</span></a></li>
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
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0"><i class="fas fa-wifi mr-1"></i>Wireless LAN</h6>
                                <button type="button" class="btn btn-sm btn-outline-success btn-add-ssid" id="btn-add-ssid">
                                    <i class="fas fa-plus mr-1"></i>Tambah SSID
                                </button>
                            </div>
                            <div id="tr069-wifi-list"></div>
                            <div id="tr069-wifi-empty" class="text-center py-4 text-muted" style="display:none">
                                <i class="fas fa-wifi fa-2x mb-2 d-block text-secondary"></i>
                                Tidak ada data WiFi
                            </div>
                        </div>

                        {{-- LAN --}}
                        <div class="tab-pane fade" id="acs-lan">
                            <h6 class="mb-3"><i class="fas fa-ethernet mr-1"></i>LAN Ports</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-striped table-bordered" id="tr069-lan-table">
                                    <thead class="thead-light">
                                        <tr><th>Port</th><th>Nama</th><th>Status</th><th>Speed</th><th>Duplex</th><th>MAC Address</th></tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <h6 class="mb-2"><i class="fas fa-server mr-1"></i>Konfigurasi IP & DHCP</h6>
                            <div class="card card-outline card-primary mb-3" id="dhcp-config-card">
                                <div class="card-header p-2 d-flex justify-content-between align-items-center">
                                    <span class="font-weight-bold small">IP LAN &amp; DHCP Server</span>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" id="btn-edit-lan-config">
                                        <i class="fas fa-pen mr-1"></i>Edit
                                    </button>
                                </div>
                                <div class="card-body p-2">
                                    {{-- Read-only display --}}
                                    <div id="lan-config-display">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="text-uppercase text-muted small font-weight-bold mb-1" style="font-size:0.7rem;letter-spacing:.05em"><i class="fas fa-network-wired mr-1"></i>Konfigurasi IP LAN</p>
                                                <table class="table table-sm table-borderless mb-2" id="tr069-ip-table"></table>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="text-uppercase text-muted small font-weight-bold mb-1" style="font-size:0.7rem;letter-spacing:.05em"><i class="fas fa-server mr-1"></i>DHCP Server</p>
                                                <table class="table table-sm table-borderless mb-0" id="tr069-dhcp-table"></table>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Edit form (hidden by default) --}}
                                    <div id="lan-config-form" style="display:none">
                                        {{-- Section 1: IP Config --}}
                                        <p class="text-uppercase text-muted small font-weight-bold mb-2" style="font-size:0.7rem;letter-spacing:.05em"><i class="fas fa-network-wired mr-1"></i>Konfigurasi IP LAN</p>
                                        <div class="row mb-2">
                                            <div class="col-md-6">
                                                <div class="form-group row mb-2">
                                                    <label class="col-sm-5 col-form-label col-form-label-sm">IP Address LAN</label>
                                                    <div class="col-sm-7">
                                                        <input type="text" class="form-control form-control-sm" id="lan-gateway-ip" placeholder="192.168.1.1">
                                                        <small class="text-muted">IP perangkat &amp; default gateway client</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row mb-2">
                                                    <label class="col-sm-5 col-form-label col-form-label-sm">Subnet Mask</label>
                                                    <div class="col-sm-7"><input type="text" class="form-control form-control-sm" id="lan-subnet-mask" placeholder="255.255.255.0"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <hr class="my-2">
                                        {{-- Section 2: DHCP --}}
                                        <p class="text-uppercase text-muted small font-weight-bold mb-2" style="font-size:0.7rem;letter-spacing:.05em"><i class="fas fa-server mr-1"></i>DHCP Server</p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group row mb-2">
                                                    <label class="col-sm-5 col-form-label col-form-label-sm">DHCP Server</label>
                                                    <div class="col-sm-7">
                                                        <select class="form-control form-control-sm" id="lan-dhcp-enable">
                                                            <option value="true">Enabled</option>
                                                            <option value="false">Disabled</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-2">
                                                    <label class="col-sm-5 col-form-label col-form-label-sm">IP Mulai</label>
                                                    <div class="col-sm-7"><input type="text" class="form-control form-control-sm" id="lan-min-address" placeholder="192.168.1.100"></div>
                                                </div>
                                                <div class="form-group row mb-2">
                                                    <label class="col-sm-5 col-form-label col-form-label-sm">IP Akhir</label>
                                                    <div class="col-sm-7"><input type="text" class="form-control form-control-sm" id="lan-max-address" placeholder="192.168.1.200"></div>
                                                </div>
                                                <div class="form-group row mb-0">
                                                    <div class="col-sm-12">
                                                        <button type="button" class="btn btn-xs btn-outline-info" id="btn-auto-dhcp-range">
                                                            <i class="fas fa-magic mr-1"></i>Auto Hitung Range dari IP
                                                        </button>
                                                        <span id="lan-range-hint" class="ml-2 small text-muted"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row mb-2">
                                                    <label class="col-sm-5 col-form-label col-form-label-sm">Lease Time (det)</label>
                                                    <div class="col-sm-7"><input type="number" class="form-control form-control-sm" id="lan-lease-time" min="60" max="604800" placeholder="86400"></div>
                                                </div>
                                                <div class="form-group row mb-2">
                                                    <label class="col-sm-5 col-form-label col-form-label-sm">DNS Servers</label>
                                                    <div class="col-sm-7"><input type="text" class="form-control form-control-sm" id="lan-dns-servers" placeholder="8.8.8.8,8.8.4.4"></div>
                                                </div>
                                                <div class="form-group row mb-2">
                                                    <label class="col-sm-5 col-form-label col-form-label-sm">Domain Name</label>
                                                    <div class="col-sm-7"><input type="text" class="form-control form-control-sm" id="lan-domain-name" placeholder="local"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end mt-2">
                                            <button type="button" class="btn btn-sm btn-secondary mr-2" id="btn-cancel-lan-config">Batal</button>
                                            <button type="button" class="btn btn-sm btn-primary" id="btn-save-lan-config">
                                                <i class="fas fa-save mr-1"></i>Simpan ke Perangkat
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Clients / Connected Hosts --}}
                        <div class="tab-pane fade" id="acs-clients">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0"><i class="fas fa-laptop mr-1"></i>Client Terkoneksi</h6>
                                <button class="btn btn-sm btn-outline-primary btn-refresh-clients">
                                    <i class="fas fa-sync mr-1"></i>Refresh
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-bordered" id="tr069-hosts-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Perangkat</th>
                                            <th>IP Address</th>
                                            <th>MAC Address</th>
                                            <th>Koneksi</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div id="tr069-clients-empty" class="text-center py-4 text-muted" style="display:none">
                                <i class="fas fa-laptop fa-2x mb-2 d-block text-secondary"></i>
                                Tidak ada client yang terkoneksi
                            </div>

                            {{-- Blocked Clients List --}}
                            <div class="card card-outline card-danger mt-3" id="blocked-clients-card">
                                <div class="card-header p-2 d-flex justify-content-between align-items-center">
                                    <span class="font-weight-bold small text-danger">
                                        <i class="fas fa-ban mr-1"></i>Daftar Blokir
                                        <span class="badge badge-danger ml-1" id="blocked-count">0</span>
                                    </span>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" id="btn-refresh-blocked">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div id="blocked-empty" class="text-center py-3 text-muted small" style="display:none">
                                        Tidak ada client yang diblokir
                                    </div>
                                    <table class="table table-sm table-borderless mb-0" id="blocked-clients-table">
                                        <thead class="thead-light" style="display:none">
                                            <tr><th>Perangkat</th><th>MAC</th><th>IP Terakhir</th><th>Alasan</th><th>Waktu</th><th></th></tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Security --}}
                        <div class="tab-pane fade" id="acs-security">
                            <div id="tr069-security-loading" class="text-center py-3">
                                <i class="fas fa-spinner fa-spin"></i> Memuat data security...
                            </div>
                            <div id="tr069-security-content" style="display:none">
                                <div id="security-brand-bar" class="mb-2"></div>

                                {{-- Row 1: ACL Services + ACS Server --}}
                                <div class="row">
                                    <div class="col-lg-7">
                                        <div class="acs-card mb-3" id="security-acl-card">
                                            <div class="acs-section-header d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-shield-alt mr-1 text-warning"></i>Remote Access Control</span>
                                                <button class="btn btn-xs btn-primary" id="btn-save-security">
                                                    <i class="fas fa-save mr-1"></i>Simpan Semua
                                                </button>
                                            </div>
                                            <div class="card-body p-0">
                                                <table class="table table-sm table-bordered mb-0" id="tr069-acl-table">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th style="width:45%">Fitur</th>
                                                            <th class="text-center" style="width:27.5%">WAN (Remote)</th>
                                                            <th class="text-center" style="width:27.5%">LAN (Local)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td><i class="fas fa-folder-open fa-fw mr-1 text-muted"></i>FTP Access</td>
                                                            <td class="text-center"><div class="custom-control custom-switch d-inline-block"><input type="checkbox" class="custom-control-input acl-toggle" id="acl_ftp_wan" data-key="acl_ftp_wan"><label class="custom-control-label" for="acl_ftp_wan"></label></div></td>
                                                            <td class="text-center"><div class="custom-control custom-switch d-inline-block"><input type="checkbox" class="custom-control-input acl-toggle" id="acl_ftp_lan" data-key="acl_ftp_lan"><label class="custom-control-label" for="acl_ftp_lan"></label></div></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-globe fa-fw mr-1 text-muted"></i>Web UI Access</td>
                                                            <td class="text-center"><div class="custom-control custom-switch d-inline-block"><input type="checkbox" class="custom-control-input acl-toggle" id="acl_http_wan" data-key="acl_http_wan"><label class="custom-control-label" for="acl_http_wan"></label></div></td>
                                                            <td class="text-center"><div class="custom-control custom-switch d-inline-block"><input type="checkbox" class="custom-control-input acl-toggle" id="acl_http_lan" data-key="acl_http_lan"><label class="custom-control-label" for="acl_http_lan"></label></div></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-terminal fa-fw mr-1 text-muted"></i>SSH Access</td>
                                                            <td class="text-center"><div class="custom-control custom-switch d-inline-block"><input type="checkbox" class="custom-control-input acl-toggle" id="acl_ssh_wan" data-key="acl_ssh_wan"><label class="custom-control-label" for="acl_ssh_wan"></label></div></td>
                                                            <td class="text-center"><div class="custom-control custom-switch d-inline-block"><input type="checkbox" class="custom-control-input acl-toggle" id="acl_ssh_lan" data-key="acl_ssh_lan"><label class="custom-control-label" for="acl_ssh_lan"></label></div></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-network-wired fa-fw mr-1 text-muted"></i>Samba Access</td>
                                                            <td class="text-center"><div class="custom-control custom-switch d-inline-block"><input type="checkbox" class="custom-control-input acl-toggle" id="acl_samba_wan" data-key="acl_samba_wan"><label class="custom-control-label" for="acl_samba_wan"></label></div></td>
                                                            <td class="text-center"><div class="custom-control custom-switch d-inline-block"><input type="checkbox" class="custom-control-input acl-toggle" id="acl_samba_lan" data-key="acl_samba_lan"><label class="custom-control-label" for="acl_samba_lan"></label></div></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-keyboard fa-fw mr-1 text-muted"></i>Telnet Access</td>
                                                            <td class="text-center"><div class="custom-control custom-switch d-inline-block"><input type="checkbox" class="custom-control-input acl-toggle" id="acl_telnet_wan" data-key="acl_telnet_wan"><label class="custom-control-label" for="acl_telnet_wan"></label></div></td>
                                                            <td class="text-center"><div class="custom-control custom-switch d-inline-block"><input type="checkbox" class="custom-control-input acl-toggle" id="acl_telnet_lan" data-key="acl_telnet_lan"><label class="custom-control-label" for="acl_telnet_lan"></label></div></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-exchange-alt fa-fw mr-1 text-muted"></i>WAN ICMP Echo Reply</td>
                                                            <td class="text-center"><div class="custom-control custom-switch d-inline-block"><input type="checkbox" class="custom-control-input acl-toggle" id="acl_icmp_echo" data-key="acl_icmp_echo"><label class="custom-control-label" for="acl_icmp_echo"></label></div></td>
                                                            <td class="text-center text-muted">-</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="card-body border-top py-2">
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <span class="small"><i class="fas fa-terminal fa-fw mr-1 text-muted"></i>SSH Service</span>
                                                            <div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input acl-toggle" id="acl_cli_ssh" data-key="cli_ssh_enable"><label class="custom-control-label" for="acl_cli_ssh"></label></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <span class="small"><i class="fas fa-keyboard fa-fw mr-1 text-muted"></i>Telnet Service</span>
                                                            <div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input acl-toggle" id="acl_cli_telnet" data-key="cli_telnet_enable"><label class="custom-control-label" for="acl_cli_telnet"></label></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- CLI Credentials --}}
                                        <div class="acs-card mb-3" id="security-cli-card">
                                            <div class="acs-section-header"><i class="fas fa-terminal mr-1 text-info"></i>CLI Credentials <small class="text-muted">(SSH / Telnet)</small></div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-sm-4">
                                                        <div class="form-group mb-2">
                                                            <label class="small font-weight-bold">Username</label>
                                                            <input type="text" class="form-control form-control-sm" id="cli-username" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-8">
                                                        <div class="form-group mb-2">
                                                            <label class="small font-weight-bold">Password Baru</label>
                                                            <div class="input-group input-group-sm">
                                                                <input type="password" class="form-control" id="cli-password" placeholder="Kosongkan jika tidak diubah" autocomplete="new-password">
                                                                <div class="input-group-append">
                                                                    <button class="btn btn-outline-info" type="button" id="btn-save-cli-pw">
                                                                        <i class="fas fa-key mr-1"></i>Ganti
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Web UI Accounts --}}
                                        <div class="acs-card">
                                            <div class="acs-section-header"><i class="fas fa-globe mr-1 text-success"></i>Web UI Accounts</div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6 border-right">
                                                        <div class="small font-weight-bold mb-2 text-primary"><i class="fas fa-user-shield mr-1"></i>Web Admin</div>
                                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                                            <span class="small">Status</span>
                                                            <div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input acl-toggle" id="web_admin_enable" data-key="web_admin_enable"><label class="custom-control-label" for="web_admin_enable"></label></div>
                                                        </div>
                                                        <div class="form-group mb-2">
                                                            <label class="small">Username</label>
                                                            <div class="input-group input-group-sm">
                                                                <input type="text" class="form-control" id="web-admin-name" placeholder="Username" autocomplete="off">
                                                                <div class="input-group-append">
                                                                    <button class="btn btn-outline-secondary btn-save-web-username" data-target="web_admin_username" type="button" title="Simpan Username"><i class="fas fa-user-edit"></i></button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-group mb-0">
                                                            <label class="small">Password Baru</label>
                                                            <div class="input-group input-group-sm">
                                                                <input type="password" class="form-control" id="web-admin-password" placeholder="Password baru" autocomplete="new-password">
                                                                <div class="input-group-append">
                                                                    <button class="btn btn-outline-primary btn-save-web-pw" data-target="web_admin_password" type="button"><i class="fas fa-key"></i></button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="small font-weight-bold mb-2 text-success"><i class="fas fa-user mr-1"></i>Web User</div>
                                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                                            <span class="small">Status</span>
                                                            <div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input acl-toggle" id="web_user_enable" data-key="web_user_enable"><label class="custom-control-label" for="web_user_enable"></label></div>
                                                        </div>
                                                        <div class="form-group mb-2">
                                                            <label class="small">Username</label>
                                                            <div class="input-group input-group-sm">
                                                                <input type="text" class="form-control" id="web-user-name" placeholder="Username" autocomplete="off">
                                                                <div class="input-group-append">
                                                                    <button class="btn btn-outline-secondary btn-save-web-username" data-target="web_user_username" type="button" title="Simpan Username"><i class="fas fa-user-edit"></i></button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-group mb-0">
                                                            <label class="small">Password Baru</label>
                                                            <div class="input-group input-group-sm">
                                                                <input type="password" class="form-control" id="web-user-password" placeholder="Password baru" autocomplete="new-password">
                                                                <div class="input-group-append">
                                                                    <button class="btn btn-outline-success btn-save-web-pw" data-target="web_user_password" type="button"><i class="fas fa-key"></i></button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-5">
                                        {{-- ACS Server --}}
                                        <div class="acs-card mb-3">
                                            <div class="acs-section-header"><i class="fas fa-server mr-1 text-primary"></i>ACS Server (TR-069)</div>
                                            <div class="card-body p-0">
                                                <table class="table table-sm table-striped table-acs mb-0" id="tr069-acs-table"></table>
                                            </div>
                                            <div class="card-body border-top pt-2 pb-2" id="tr069-inform-form-wrap" style="display:none">
                                                <div class="small font-weight-bold mb-1"><i class="fas fa-clock mr-1 text-info"></i>Edit Periodic Inform Interval</div>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" id="tr069-inform-input" class="form-control" min="30" max="86400" placeholder="detik (min 30, max 86400)" style="max-width:200px">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-primary btn-sm" id="btn-save-inform-interval" type="button">
                                                            <i class="fas fa-save mr-1"></i>Simpan
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="small text-muted mt-1" id="tr069-inform-hint"></div>
                                            </div>
                                        </div>

                                        {{-- Firewall --}}
                                        <div class="acs-card">
                                            <div class="acs-section-header"><i class="fas fa-fire mr-1 text-danger"></i>Firewall &amp; Network Info</div>
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
                                            <button type="button" class="btn btn-outline-danger btn-sm btn-block btn-tr069-factory-reset">
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
                {{-- Re-apply OLT Profile (TCONT + Traffic) --}}
                @if($onu->olt->brand === 'zte')
                <div class="col-lg-6 mb-3">
                    <div class="acs-card">
                        <div class="acs-section-header">
                            <i class="fas fa-sliders-h mr-1 text-primary"></i>Re-apply OLT Profile
                            @php
                                $currentTcont   = $onu->line_profile ?? 'default';
                                $currentTraffic = $onu->traffic_profile ?? null;
                                $profileWarning = $currentTcont === 'default' || $currentTcont === '';
                            @endphp
                            @if($profileWarning)
                            <span class="badge badge-danger float-right"><i class="fas fa-exclamation-triangle mr-1"></i>Profile Bermasalah</span>
                            @else
                            <span class="badge badge-success float-right"><i class="fas fa-check mr-1"></i>OK</span>
                            @endif
                        </div>
                        <div class="card-body">
                            @if($profileWarning)
                            <div class="alert alert-warning alert-sm py-2 mb-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                TCONT profile <strong>{{ $currentTcont ?: 'tidak terset' }}</strong> tidak mendukung bandwidth penuh.
                                Ganti ke profile yang sesuai agar PPPoE bisa dial.
                            </div>
                            @endif
                            <p class="small text-muted mb-2">
                                Saat ini: TCONT = <code>{{ $currentTcont ?: '-' }}</code> | Traffic = <code>{{ $currentTraffic ?: '-' }}</code>
                            </p>
                            <form id="form-reapply-profile">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">TCONT Profile (Upstream)</label>
                                    <select name="tcont_profile" id="sel-tcont-profile" class="form-control form-control-sm" required>
                                        @foreach($oltTcontProfiles as $p)
                                        <option value="{{ $p->name }}" {{ $p->name === $currentTcont ? 'selected' : '' }}>
                                            {{ $p->name }}
                                        </option>
                                        @endforeach
                                        @if($oltTcontProfiles->isEmpty())
                                        <option value="">-- Sync profile dari OLT terlebih dahulu --</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Traffic Profile (Downstream)</label>
                                    <select name="traffic_profile" id="sel-traffic-profile" class="form-control form-control-sm" required>
                                        @foreach($oltTrafficProfiles as $p)
                                        <option value="{{ $p->name }}" {{ $p->name === $currentTraffic ? 'selected' : '' }}>
                                            {{ $p->name }}
                                        </option>
                                        @endforeach
                                        @if($oltTrafficProfiles->isEmpty())
                                        <option value="">-- Sync profile dari OLT terlebih dahulu --</option>
                                        @endif
                                    </select>
                                </div>
                                @if($oltTcontProfiles->isEmpty() || $oltTrafficProfiles->isEmpty())
                                <a href="{{ route('admin.olts.profiles.sync', $onu->olt) }}" class="btn btn-outline-secondary btn-sm btn-block mb-2">
                                    <i class="fas fa-sync mr-1"></i>Sync Profile dari OLT
                                </a>
                                @endif
                                <button type="submit" class="btn btn-primary btn-sm btn-block"
                                    {{ ($oltTcontProfiles->isEmpty() || $oltTrafficProfiles->isEmpty()) ? 'disabled' : '' }}>
                                    <i class="fas fa-check mr-1"></i>Terapkan Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

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
                        @php
                            $currentVlan = $onu->vlan_config['vlan_id'] ?? null;
                            $vlans = collect($vlanOptions ?? []);
                            if ($currentVlan && !$vlans->contains((int) $currentVlan)) {
                                $vlans = $vlans->push((int) $currentVlan)->sort()->values();
                            }
                        @endphp
                        <select class="form-control" name="vlan" id="pppoe-vlan" required>
                            @if($vlans->isEmpty())
                                <option value="" disabled selected>-- Belum ada VLAN terdaftar di OLT ini --</option>
                            @else
                                @foreach($vlans as $v)
                                    <option value="{{ $v }}" {{ ((int) $currentVlan === (int) $v) ? 'selected' : '' }}>VLAN {{ $v }}</option>
                                @endforeach
                            @endif
                        </select>
                        <small class="form-text text-muted">Daftar VLAN yang sudah dipakai ONU lain di OLT ini.</small>
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

{{-- ProvisioningCode Edit Modal --}}
<div class="modal fade" id="modal-provcode" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); border: none;">
                <h5 class="modal-title text-white"><i class="fas fa-tag mr-2"></i>Edit ProvisioningCode</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="form-provcode">
                <div class="modal-body">
                    <p class="text-muted small mb-2">
                        Tag bebas (TR-069 <code>InternetGatewayDevice.DeviceInfo.ProvisioningCode</code>) yang tersimpan di firmware ONU.
                        Biasanya dipakai untuk menandai siapa yang melakukan provisioning.
                    </p>
                    <div class="form-group mb-2">
                        <label><i class="fas fa-pen text-primary mr-1"></i>Provisioning Code</label>
                        <input type="text" name="code" id="provcode-input" class="form-control" maxlength="64" placeholder="contoh: internet35-init" autocomplete="off" required>
                        <small class="form-text text-muted">Maksimal 64 karakter.</small>
                    </div>
                    <div class="form-group mb-0">
                        <label class="text-muted small mb-1">Nilai saat ini</label>
                        <div><code id="provcode-current">-</code></div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-provcode"><i class="fas fa-save mr-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- WiFi Edit Modal --}}
<div class="modal fade" id="modal-wifi" tabindex="-1">    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); border: none;">
                <h5 class="modal-title text-white"><i class="fas fa-wifi mr-2"></i>Edit Wireless</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="form-wifi-setup">
                <input type="hidden" name="wlan_path" id="wifi-path">
                <div class="modal-body">
                    <div class="form-group">
                        <label><i class="fas fa-broadcast-tower text-info mr-1"></i>SSID</label>
                        <input type="text" name="ssid" id="wifi-ssid" class="form-control" maxlength="32" placeholder="Nama jaringan WiFi">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-key text-warning mr-1"></i>Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="wifi-password" class="form-control" minlength="8" maxlength="63" placeholder="Kosongkan jika tidak diubah">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary btn-toggle-pass" data-target="#wifi-password" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label><i class="fas fa-power-off text-success mr-1"></i>Status</label>
                        <select name="enabled" id="wifi-enabled" class="form-control">
                            <option value="1">Enabled</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add SSID Modal --}}
<div class="modal fade" id="modal-add-ssid" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white"><i class="fas fa-plus-circle mr-2"></i>Tambah SSID Baru</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="form-add-ssid">
                <div class="modal-body">
                    <div class="alert alert-info py-2 small"><i class="fas fa-info-circle mr-1"></i>SSID baru akan dibuat setelah device check-in ke ACS. Maks 4 SSID.</div>
                    <div class="form-group">
                        <label>SSID <span class="text-danger">*</span></label>
                        <input type="text" name="ssid" id="add-ssid-name" class="form-control" maxlength="32" required placeholder="Nama WiFi">
                    </div>
                    <div class="form-group">
                        <label>Password <small class="text-muted">(min 8 karakter)</small></label>
                        <input type="text" name="password" id="add-ssid-password" class="form-control" minlength="8" maxlength="63" placeholder="Kosongkan = tidak diset">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="enabled" id="add-ssid-enabled" class="form-control">
                            <option value="1">Enabled</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-plus mr-1"></i>Tambah SSID</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- WAN Edit Modal --}}
<div class="modal fade" id="modal-wan-edit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit PPPoE WAN</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="form-wan-edit">
                <input type="hidden" id="wan-edit-path">
                <div class="modal-body">
                    <div class="form-group">
                        <label>WAN VLAN-ID</label>
                        @php
                            $editVlans = collect($vlanOptions ?? []);
                        @endphp
                        <select id="wan-edit-vlan" class="form-control">
                            <option value="">-- Tidak diubah --</option>
                            @foreach($editVlans as $v)
                                <option value="{{ $v }}">VLAN {{ $v }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Pilih VLAN baru, atau biarkan "Tidak diubah" untuk hanya mengubah username/password.</small>
                    </div>
                    <div class="form-group">
                        <label>PPPoE Username</label>
                        <input type="text" id="wan-edit-username" class="form-control" placeholder="username@isp" required>
                    </div>
                    <div class="form-group">
                        <label>PPPoE Password <small class="text-muted">(kosongkan jika tidak ingin mengubah)</small></label>
                        <input type="text" id="wan-edit-password" class="form-control" placeholder="Password baru (opsional)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan Perubahan</button>
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

    $('.btn-olt-factory-reset').click(function() {
        var id = $(this).data('id'), sn = $(this).data('sn'), btn = $(this);
        Swal.fire({
            title: 'Factory Reset via OLT',
            html: 'Reset ONU <strong>' + sn + '</strong> ke default setting?<br><br><small class="text-danger">Semua konfigurasi ONU akan dihapus!</small>',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#343a40', confirmButtonText: 'Ya, Reset!'
        }).then(function(result) {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Resetting...');
                $.post('/admin/onus/' + id + '/factory-reset', { _token: '{{ csrf_token() }}' })
                    .done(function(res) { Swal.fire('Berhasil', res.message || 'Perintah factory reset dikirim ke ONU', res.success ? 'success' : 'error').then(function() { location.reload(); }); })
                    .fail(function(xhr) { Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal factory reset ONU', 'error'); })
                    .always(function() { btn.prop('disabled', false).html('<i class="fas fa-undo mr-1"></i>Factory Reset'); });
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
                    .done(function(res) { Swal.fire('Berhasil', res.message, 'success').then(function() { window.location.href = res.redirect_url || '{{ route("admin.onus.index") }}'; }); })
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

    // ========== Re-apply OLT Profile ==========
    $('#form-reapply-profile').submit(function(e) {
        e.preventDefault();
        var tcont   = $('#sel-tcont-profile').val();
        var traffic = $('#sel-traffic-profile').val();
        if (!tcont || !traffic) {
            Swal.fire('Error', 'Pilih TCONT dan Traffic profile terlebih dahulu.', 'error');
            return;
        }
        Swal.fire({
            title: 'Konfirmasi Re-apply Profile',
            html: 'Terapkan:<br><strong>TCONT:</strong> ' + tcont + '<br><strong>Traffic:</strong> ' + traffic + '<br><br><small class="text-muted">ONU akan di-update tanpa unregister.</small>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Terapkan!',
            cancelButtonText: 'Batal',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            var btn = $('#form-reapply-profile button[type="submit"]');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menerapkan...');
            $.post('/admin/onus/{{ $onu->id }}/reapply-profiles', {
                _token: '{{ csrf_token() }}',
                tcont_profile: tcont,
                traffic_profile: traffic,
            })
            .done(function(res) {
                if (res.success) {
                    Swal.fire('Berhasil', res.message, 'success').then(function() { location.reload(); });
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal menerapkan profile', 'error');
                }
            })
            .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Server error', 'error'); })
            .always(function() { btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Terapkan Profile'); });
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
        if (dev.provisioning_code) rows += '<tr><td>Provisioning</td><td><span id="tr069-provcode-text">' + dev.provisioning_code + '</span> <button type="button" class="btn btn-xs btn-link p-0 ml-1" id="btn-edit-provcode" title="Ubah ProvisioningCode"><i class="fas fa-pen text-muted"></i></button></td></tr>';
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
                var isPppoe = wan.type === 'PPPoE';
                wanHtml += '<div class="wan-connection-card ' + sc + '">';
                wanHtml += '<div class="d-flex justify-content-between align-items-center">';
                wanHtml += '<div>';
                wanHtml += '<strong><i class="fas fa-globe mr-1"></i>' + (wan.type || 'WAN') + ' ' + (i + 1) + '</strong>';
                if (wan.name) wanHtml += ' <small class="text-muted">(' + wan.name + ')</small>';
                wanHtml += '</div>';
                wanHtml += '<div class="d-flex align-items-center">';
                wanHtml += '<span class="badge badge-' + statusBadge + ' mr-2">' + (wan.status || 'Unknown') + '</span>';
                if (isPppoe) {
                    wanHtml += '<button type="button" class="btn btn-xs btn-outline-primary mr-1 btn-edit-wan" '
                        + 'data-path="' + (wan.path || '') + '" '
                        + 'data-username="' + (wan.username || '') + '" '
                        + 'data-vlan="' + (wan.vlan_id || '') + '">'
                        + '<i class="fas fa-edit"></i></button>';
                    wanHtml += '<button type="button" class="btn btn-xs btn-outline-danger btn-delete-wan" '
                        + 'data-path="' + (wan.path || '') + '" '
                        + 'data-name="' + (wan.name || ('WAN ' + (i + 1))) + '">'
                        + '<i class="fas fa-trash"></i></button>';
                } else {
                    wanHtml += '<span class="badge badge-light" title="WAN management — tidak dapat diedit/dihapus"><i class="fas fa-lock mr-1"></i>Protected</span>';
                }
                wanHtml += '</div></div>';
                wanHtml += '<div class="mt-2 small">';
                if (wan.username) wanHtml += '<div><i class="fas fa-user text-muted mr-1"></i>Username: <strong>' + wan.username + '</strong></div>';
                var ip = wan.external_ip;
                if (ip && ip !== '0.0.0.0') {
                    wanHtml += '<div><i class="fas fa-network-wired text-muted mr-1"></i>IP: <code>' + ip + '</code></div>';
                } else if (ip === '0.0.0.0') {
                    wanHtml += '<div><i class="fas fa-network-wired text-warning mr-1"></i>IP: <span class="text-warning">Belum terhubung</span></div>';
                }
                if (wan.gateway && wan.gateway !== '0.0.0.0') wanHtml += '<div><i class="fas fa-route text-muted mr-1"></i>Gateway: <code>' + wan.gateway + '</code></div>';
                if (wan.dns && wan.dns.trim()) wanHtml += '<div><i class="fas fa-server text-muted mr-1"></i>DNS: ' + wan.dns.trim() + '</div>';
                if (wan.vlan_id) wanHtml += '<div><i class="fas fa-tag text-muted mr-1"></i>VLAN: ' + wan.vlan_id + '</div>';
                if (wan.uptime && parseInt(wan.uptime) > 0) wanHtml += '<div><i class="fas fa-clock text-muted mr-1"></i>Uptime: ' + formatUptime(wan.uptime) + '</div>';
                wanHtml += '</div></div>';
            });
            $('#tr069-wan-list').html(wanHtml);
        }

        // WiFi
        var wifis = data.wifi || [];
        $('#tr069-wifi-count').text(wifis.length);
        if (wifis.length >= 4) {
            $('#btn-add-ssid').hide();
        } else {
            $('#btn-add-ssid').show();
        }
        if (wifis.length === 0) {
            $('#tr069-wifi-list').empty();
            $('#tr069-wifi-empty').show();
        } else {
            $('#tr069-wifi-empty').hide();

            var bands = {};
            wifis.forEach(function(wifi, i) {
                var b = wifi.band || 'Unknown';
                if (!bands[b]) bands[b] = [];
                bands[b].push({ wifi: wifi, i: i });
            });

            var bandOrder = ['2.4GHz', '5GHz', 'Unknown'];
            var bandConfig = {
                '2.4GHz': { cssClass: 'band-24', icon: 'fa-wifi', label: '2.4 GHz', gradient: '#17a2b8' },
                '5GHz':   { cssClass: 'band-5',  icon: 'fa-broadcast-tower', label: '5 GHz', gradient: '#6f42c1' },
                'Unknown': { cssClass: 'band-unknown', icon: 'fa-wifi', label: 'Unknown Band', gradient: '#6c757d' }
            };

            var wifiHtml = '';
            bandOrder.forEach(function(band) {
                if (!bands[band]) return;
                var entries = bands[band];
                var cfg = bandConfig[band];

                wifiHtml += '<div class="mb-4">';
                wifiHtml += '<div class="wifi-band-header ' + cfg.cssClass + ' d-flex align-items-center justify-content-between">';
                wifiHtml += '<div><i class="fas ' + cfg.icon + ' mr-2"></i><strong>' + cfg.label + '</strong></div>';
                wifiHtml += '<span class="badge badge-light">' + entries.length + ' SSID</span>';
                wifiHtml += '</div>';

                entries.forEach(function(entry) {
                    var wifi = entry.wifi, i = entry.i;
                    var isEnabled = wifi.enabled;
                    var wlanIndex = 1;
                    var idxMatch = (wifi.path || '').match(/WLANConfiguration\.(\d+)/);
                    if (idxMatch) wlanIndex = parseInt(idxMatch[1], 10);
                    var canDelete = wlanIndex > 1;

                    wifiHtml += '<div class="wifi-card' + (!isEnabled ? ' wifi-disabled' : '') + '">';
                    wifiHtml += '<div class="wifi-status-bar ' + (isEnabled ? 'active' : 'inactive') + '"></div>';
                    wifiHtml += '<div class="d-flex justify-content-between align-items-start">';
                    wifiHtml += '<div class="d-flex align-items-center">';
                    wifiHtml += '<div class="wifi-signal-icon mr-3 text-' + (isEnabled ? 'success' : 'secondary') + '">';
                    wifiHtml += '<i class="fas fa-wifi"></i>';
                    wifiHtml += '</div>';
                    wifiHtml += '<div>';
                    wifiHtml += '<div class="wifi-ssid-name">' + (wifi.ssid || 'SSID ' + (i + 1)) + '</div>';
                    wifiHtml += '<div class="mt-1">';
                    wifiHtml += '<span class="badge badge-' + (isEnabled ? 'success' : 'secondary') + ' badge-pill mr-1">';
                    wifiHtml += '<i class="fas fa-' + (isEnabled ? 'check-circle' : 'times-circle') + ' mr-1"></i>';
                    wifiHtml += (isEnabled ? 'Active' : 'Disabled') + '</span>';
                    if (wifi.channel) wifiHtml += '<span class="badge badge-outline-secondary badge-pill mr-1">Ch ' + wifi.channel + '</span>';
                    if (wifi.total_associations !== null && wifi.total_associations !== undefined && wifi.total_associations !== '') {
                        var assocCount = parseInt(wifi.total_associations) || 0;
                        wifiHtml += '<span class="badge badge-' + (assocCount > 0 ? 'info' : 'light') + ' badge-pill">';
                        wifiHtml += '<i class="fas fa-laptop mr-1"></i>' + assocCount + ' device' + (assocCount !== 1 ? 's' : '') + '</span>';
                    }
                    wifiHtml += '</div></div></div>';

                    // Action buttons
                    wifiHtml += '<div class="btn-group btn-group-sm">';
                    wifiHtml += '<button type="button" class="btn btn-sm btn-outline-info btn-edit-wifi"'
                        + ' data-path="' + (wifi.path || '') + '"'
                        + ' data-ssid="' + (wifi.ssid || '') + '"'
                        + ' data-enabled="' + (wifi.enabled ? '1' : '0') + '"'
                        + ' title="Edit WiFi"><i class="fas fa-cog"></i></button>';
                    if (canDelete) {
                        wifiHtml += '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-wifi"'
                            + ' data-path="' + (wifi.path || '') + '"'
                            + ' data-ssid="' + (wifi.ssid || 'SSID ' + wlanIndex) + '"'
                            + ' title="Hapus SSID"><i class="fas fa-trash-alt"></i></button>';
                    }
                    wifiHtml += '</div></div>';

                    // Meta info grid
                    var hasMeta = wifi.standard || wifi.security_mode || wifi.encryption || wifi.mac_address;
                    if (hasMeta) {
                        wifiHtml += '<div class="wifi-meta-grid">';
                        if (wifi.security_mode) wifiHtml += '<div class="wifi-meta-item"><i class="fas fa-shield-alt text-success"></i>' + wifi.security_mode + '</div>';
                        if (wifi.encryption) wifiHtml += '<div class="wifi-meta-item"><i class="fas fa-lock text-info"></i>' + wifi.encryption + '</div>';
                        if (wifi.standard) wifiHtml += '<div class="wifi-meta-item"><i class="fas fa-tachometer-alt text-primary"></i>' + wifi.standard + '</div>';
                        if (wifi.mac_address) wifiHtml += '<div class="wifi-meta-item"><i class="fas fa-fingerprint text-muted"></i><code class="small">' + wifi.mac_address + '</code></div>';
                        wifiHtml += '</div>';
                    }
                    wifiHtml += '</div>';
                });

                wifiHtml += '</div>';
            });
            $('#tr069-wifi-list').html(wifiHtml);
        }

        // LAN Ports
        var ports = data.lan_ports || [];
        var portHtml = '';
        if (ports.length === 0) {
            portHtml = '<tr><td colspan="6" class="text-center text-muted">Tidak ada data LAN port — klik Refresh Data untuk memuat</td></tr>';
        } else {
            ports.forEach(function(port) {
                var sb = port.status === 'Up' ? 'success' : 'secondary';
                var speed = port.hw_speed || port.max_bit_rate || '-';
                // Format speed: Auto_1000 → 1000 Mbps, Auto_10 → 10 Mbps
                speed = speed.replace('Auto_', '').replace('Auto','Auto');
                if (!isNaN(speed)) speed = speed + ' Mbps';
                var duplex = port.hw_duplex || port.duplex_mode || '-';
                duplex = duplex.replace('Auto_', '');
                portHtml += '<tr>';
                portHtml += '<td><strong>Port ' + (port.index || '-') + '</strong></td>';
                portHtml += '<td><small class="text-muted">' + (port.name || '-') + '</small></td>';
                portHtml += '<td><span class="badge badge-' + sb + '">' + (port.status || '-') + '</span></td>';
                portHtml += '<td>' + speed + '</td>';
                portHtml += '<td>' + duplex + '</td>';
                portHtml += '<td><code class="small">' + (port.mac_address || '-') + '</code></td>';
                portHtml += '</tr>';
            });
        }
        $('#tr069-lan-table tbody').html(portHtml);

        // IP LAN & DHCP Server info — split display + pre-fill edit form
        var dhcp = data.lan_dhcp || {};
        var noData = Object.keys(dhcp).length === 0;
        var needsFetch = dhcp.needs_fetch === true;
        var dhcpEnable = !noData && (dhcp.dhcp_server_enable === true || dhcp.dhcp_server_enable === 'true' || dhcp.dhcp_server_enable === '1');

        // If data is "structurally present" but values haven't been fetched yet,
        // auto-start polling so the UI refreshes when device checks in.
        if (needsFetch && !pollTimer) {
            var $refreshBtn = $('.btn-refresh-tr069, .btn-refresh-tr069-data').first();
            $refreshBtn.find('i').addClass('fa-spin');
            startPoll($refreshBtn, 'refresh');
        }

        // IP config display table
        var ipHtml = noData
            ? '<tr><td class="text-muted text-center small">Belum tersedia</td></tr>'
            : needsFetch
                ? '<tr><td class="text-muted text-center small"><i class="fas fa-sync fa-spin mr-1"></i>Mengambil data dari device, tunggu sebentar…</td></tr>'
            : [
                ['IP Address LAN', dhcp.ip_interface_address ? '<code>' + dhcp.ip_interface_address + '</code>' : '<span class="text-muted">-</span>'],
                ['Subnet Mask', dhcp.subnet_mask || '-'],
                ['MAC LAN', dhcp.gateway_mac ? '<code class="small">' + dhcp.gateway_mac + '</code>' : '-'],
              ].map(function(r) { return '<tr><td class="text-muted small" width="45%">' + r[0] + '</td><td class="small">' + r[1] + '</td></tr>'; }).join('');
        $('#tr069-ip-table').html(ipHtml);

        // DHCP display table
        var dhcpHtml = noData
            ? '<tr><td class="text-muted text-center small">Belum tersedia</td></tr>'
            : needsFetch
                ? '<tr><td class="text-muted text-center small"><i class="fas fa-sync fa-spin mr-1"></i>Mengambil data dari device, tunggu sebentar…</td></tr>'
            : [
                ['DHCP Server', dhcpEnable ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-secondary">Disabled</span>'],
                ['Range IP', (dhcp.min_address||'-') + ' &ndash; ' + (dhcp.max_address||'-')],
                ['Lease Time', dhcp.lease_time ? dhcp.lease_time + 's (' + Math.round(dhcp.lease_time/3600) + 'j)' : '-'],
                ['DNS Servers', dhcp.dns_servers || '-'],
              ].map(function(r) { return '<tr><td class="text-muted small" width="45%">' + r[0] + '</td><td class="small">' + r[1] + '</td></tr>'; }).join('');
        $('#tr069-dhcp-table').html(dhcpHtml);

        // Pre-fill edit form
        if (!noData) {
            $('#lan-gateway-ip').val(dhcp.ip_interface_address || '');
            $('#lan-subnet-mask').val(dhcp.subnet_mask || '');
            $('#lan-dhcp-enable').val(dhcpEnable ? 'true' : 'false');
            $('#lan-min-address').val(dhcp.min_address || '');
            $('#lan-max-address').val(dhcp.max_address || '');
            $('#lan-lease-time').val(dhcp.lease_time || '');
            $('#lan-dns-servers').val(dhcp.dns_servers || '');
            $('#lan-domain-name').val(dhcp.domain_name || '');
        }

        // Clients (connected hosts) with device name detection + block action
        var hosts = data.lan_hosts || [];
        $('#tr069-host-count').text(hosts.length);
        var hostHtml = '';
        if (hosts.length === 0) {
            $('#tr069-hosts-table').closest('.table-responsive').hide();
            $('#tr069-clients-empty').show();
        } else {
            $('#tr069-hosts-table').closest('.table-responsive').show();
            $('#tr069-clients-empty').hide();
            hosts.forEach(function(host) {
                var active = host.active === true || host.active === '1' || host.active === 1;
                var ifType = (host.interface || host.layer2_interface || '').toLowerCase();
                var connBadge;
                if (ifType.indexOf('802.11') >= 0 || ifType.indexOf('wifi') >= 0 || ifType.indexOf('wlan') >= 0 || ifType.indexOf('wireless') >= 0) {
                    connBadge = '<span class="badge badge-info"><i class="fas fa-wifi mr-1"></i>WiFi</span>';
                } else if (ifType.indexOf('ethernet') >= 0 || ifType.indexOf('eth') >= 0) {
                    connBadge = '<span class="badge badge-warning"><i class="fas fa-ethernet mr-1"></i>LAN</span>';
                } else {
                    connBadge = '<span class="badge badge-secondary">' + (host.interface || '-') + '</span>';
                }
                var mac = host.mac || '';
                var vendor = ouiLookup(mac);
                var hostname = host.hostname || host.host_name || '';
                var deviceLabel = hostname
                    ? ('<strong>' + hostname + '</strong>' + (vendor ? ' <small class="text-muted">(' + vendor + ')</small>' : ''))
                    : (vendor ? '<em class="text-muted">' + vendor + '</em>' : '<em class="text-muted">Unknown</em>');
                var isBlocked = blockedMacs.indexOf(normalizeMac(mac)) >= 0;
                var blockBtn = isBlocked
                    ? '<span class="badge badge-danger"><i class="fas fa-ban mr-1"></i>Blocked</span>'
                    : '<button type="button" class="btn btn-xs btn-outline-danger btn-block-client"'
                        + ' data-mac="' + mac + '"'
                        + ' data-hostname="' + (hostname || '') + '"'
                        + ' data-ip="' + (host.ip || '') + '">'
                        + '<i class="fas fa-ban mr-1"></i>Blokir</button>';
                hostHtml += '<tr class="host-row">';
                hostHtml += '<td>' + deviceLabel + '</td>';
                hostHtml += '<td><code>' + (host.ip || '-') + '</code></td>';
                hostHtml += '<td><code class="small">' + (mac || '-') + '</code></td>';
                hostHtml += '<td>' + connBadge + '</td>';
                hostHtml += '<td><span class="badge badge-' + (active ? 'success' : 'secondary') + '">' + (active ? 'Active' : 'Inactive') + '</span></td>';
                hostHtml += '<td>' + blockBtn + '</td>';
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

    // ── OUI vendor lookup (common consumer devices) ──
    var OUI_MAP = {
        // Apple
        '000A27':'Apple','000A95':'Apple','000D93':'Apple','001124':'Apple','001451':'Apple',
        '001B63':'Apple','001E52':'Apple','001EC2':'Apple','002312':'Apple','002500':'Apple',
        '00261C':'Apple','003065':'Apple','006171':'Apple','040CCE':'Apple','04F7E4':'Apple',
        '088667':'Apple','0C1539':'Apple','0C3E9F':'Apple','0C4DE9':'Apple','0C7722':'Apple',
        '10411A':'Apple','109ADD':'Apple','18AF8F':'Apple','1C91ED':'Apple','20A2E4':'Apple',
        '247290':'Apple','28CFE9':'Apple','2C1F23':'Apple','2CB43A':'Apple','34363B':'Apple',
        '38C986':'Apple','3C07DA':'Apple','44D884':'Apple','48D705':'Apple','4C57CA':'Apple',
        '5404A6':'Apple','6C40A9':'Apple','6CAB31':'Apple','70ECE4':'Apple','749EC5':'Apple',
        '7824AF':'Apple','8089AC':'Apple','80ED2C':'Apple','84388F':'Apple','8CAB8E':'Apple',
        '90B21F':'Apple','98B8E3':'Apple','A021B7':'Apple','A4C361':'Apple','A4D18C':'Apple',
        'AC3C0B':'Apple','B0D0E7':'Apple','B8098A':'Apple','BC926B':'Apple','C82A14':'Apple',
        'D023DB':'Apple','D89695':'Apple','DC2B2A':'Apple','E0AC9B':'Apple','E48B7F':'Apple',
        'F0B479':'Apple','F0DCE2':'Apple','F82793':'Apple',
        // Samsung
        '001247':'Samsung','001599':'Samsung','0015B9':'Samsung','001A8A':'Samsung',
        '001EB2':'Samsung','001FCC':'Samsung','002566':'Samsung','0026E2':'Samsung',
        '00E3B2':'Samsung','0816EE':'Samsung','0C7172':'Samsung','10D542':'Samsung',
        '149182':'Samsung','18AF8F':'Samsung','1C62B8':'Samsung','1C66AA':'Samsung',
        '201CF1':'Samsung','24C69B':'Samsung','286C07':'Samsung','28987B':'Samsung',
        '2C2202':'Samsung','2CB53F':'Samsung','30C7AE':'Samsung','340804':'Samsung',
        '38E7D8':'Samsung','3C62BE':'Samsung','44F459':'Samsung','4844F7':'Samsung',
        '4C3C16':'Samsung','5001BB':'Samsung','502B73':'Samsung','58C38B':'Samsung',
        '5C0A5B':'Samsung','60D0A9':'Samsung','6416F0':'Samsung','68EBAE':'Samsung',
        '6C2F2C':'Samsung','70F927':'Samsung','741BB4':'Samsung','78D6F0':'Samsung',
        '7C1C4E':'Samsung','842519':'Samsung','84558C':'Samsung','8CC8CD':'Samsung',
        '8CE748':'Samsung','903469':'Samsung','90E7C4':'Samsung','948011':'Samsung',
        '9C3AAF':'Samsung','9C65F9':'Samsung','A00798':'Samsung','A4A6A9':'Samsung',
        'A8F274':'Samsung','AC5F3E':'Samsung','B4EF39':'Samsung','B80C75':'Samsung',
        'B857D8':'Samsung','BC4486':'Samsung','BCF5AC':'Samsung','C4AE12':'Samsung',
        'C4933A':'Samsung','C82A69':'Samsung','CC07AB':'Samsung','D022BE':'Samsung',
        'D4E8B2':'Samsung','D87495':'Samsung','DC0BFC':'Samsung','E498D1':'Samsung',
        'E4B021':'Samsung','E8039A':'Samsung','EC1F72':'Samsung','EC9BF3':'Samsung',
        'F025B7':'Samsung','F0E77E':'Samsung','F49F54':'Samsung','F4F5DB':'Samsung',
        // Xiaomi / Redmi
        '0C1DAF':'Xiaomi','1860B4':'Xiaomi','285FDB':'Xiaomi','34CE00':'Xiaomi',
        '50EC50':'Xiaomi','58440E':'Xiaomi','64B473':'Xiaomi','7811DC':'Xiaomi',
        '8C97EA':'Xiaomi','9AF1CC':'Xiaomi','AC2296':'Xiaomi','D4970B':'Xiaomi',
        'F48B32':'Xiaomi','FC64BA':'Xiaomi',
        // Oppo / Realme / OnePlus
        '001E65':'Oppo','0024D4':'Oppo','2C43BE':'Oppo','3CF9A4':'Oppo',
        '7C3C3D':'Oppo','84D7EB':'Oppo','BC3AEA':'Oppo','E0E0FC':'Oppo',
        // Huawei phones/pads (not ONU)
        '001E10':'Huawei','002568':'Huawei','040188':'Huawei','086371':'Huawei',
        '109C70':'Huawei','200BC7':'Huawei','24DF6A':'Huawei','2C9DD7':'Huawei',
        '3485AC':'Huawei','3C9A54':'Huawei','485754':'Huawei','5441F9':'Huawei',
        '5C4CA9':'Huawei','60DE44':'Huawei','6CE875':'Huawei','784B87':'Huawei',
        '7C1CF1':'Huawei','8C34FD':'Huawei','9437E1':'Huawei','9C741A':'Huawei',
        'A4A65F':'Huawei','A89D21':'Huawei','ACA216':'Huawei','B4430D':'Huawei',
        'B8CBC1':'Huawei','C4072F':'Huawei','C8D15E':'Huawei','CC96A0':'Huawei',
        'D4F5EF':'Huawei','D8661A':'Huawei','DCA9F0':'Huawei','E8688A':'Huawei',
        // Google / Pixel / Nest
        '3C5AB4':'Google','48D6D5':'Google','54807E':'Google','58CB52':'Google',
        '7887AB':'Google','94EBB0':'Google','A41773':'Google','AC37C7':'Google',
        'F88FCA':'Google',
        // Microsoft Surface / Xbox
        '28244B':'Microsoft','404E36':'Microsoft','606BFF':'Microsoft','7C1E52':'Microsoft',
        '98F1D9':'Microsoft','9C2A83':'Microsoft','C0334B':'Microsoft',
        // Lenovo
        '000732':'Lenovo','18A905':'Lenovo','2C4401':'Lenovo','5405DB':'Lenovo',
        '606720':'Lenovo','84928E':'Lenovo','9C4EA7':'Lenovo','D05FB8':'Lenovo',
        'E8339E':'Lenovo',
        // Dell
        '002219':'Dell','006498':'Dell','14FEB5':'Dell','18FB7B':'Dell','24B6FD':'Dell',
        '2C768A':'Dell','384793':'Dell','44A842':'Dell','5C514F':'Dell','84945E':'Dell',
        'A4C361':'Dell','B083FE':'Dell','BCEE7B':'Dell','E49764':'Dell',
        // HP
        '001A4B':'HP','001708':'HP','002264':'HP','00AABB':'HP','08EA40':'HP',
        '10604B':'HP','1CC1DE':'HP','24BE05':'HP','2C768A':'HP','3499E3':'HP',
        '3C4A92':'HP','3CB87A':'HP','40B89A':'HP','4865EE':'HP','4C65A8':'HP',
        '50654B':'HP','5CD998':'HP','60EB69':'HP','70105C':'HP','7476B0':'HP',
        'C8D3FF':'HP','D49A20':'HP','DC4A3E':'HP','E0D0E9':'HP','E8B117':'HP',
        // Asus
        '001D60':'Asus','0050FC':'Asus','08606E':'Asus','1062E5':'Asus','107B44':'Asus',
        '14858C':'Asus','1C872C':'Asus','20CF30':'Asus','2C56DC':'Asus','30FD38':'Asus',
        '3C970E':'Asus','48451E':'Asus','4CE676':'Asus','50465D':'Asus','54A050':'Asus',
        '5C2E59':'Asus','6045CB':'Asus','64006A':'Asus','6C62B7':'Asus','74D02B':'Asus',
        '7CB21B':'Asus','907282':'Asus','9C5C8E':'Asus','A85840':'Asus','AC9E17':'Asus',
        'B06EBF':'Asus','BC9746':'Asus','C89435':'Asus','DC85DE':'Asus','E03F49':'Asus',
        // Realtek (many generic devices)
        '001B2F':'Realtek','002481':'Realtek',
    };

    function normalizeMac(mac) {
        return (mac || '').replace(/[:\-]/g, '').toUpperCase();
    }

    function ouiLookup(mac) {
        var norm = normalizeMac(mac);
        if (norm.length < 6) return '';
        return OUI_MAP[norm.substring(0, 6)] || '';
    }

    // Tracked blocked MACs for client table (normalised, no separators)
    var blockedMacs = [];

    function loadBlockedClients() {
        $.get('/admin/onus/{{ $onu->id }}/tr069-blocked-clients', function(resp) {
            if (!resp.success) return;
            var list = resp.blocked || [];
            blockedMacs = list.map(function(e) { return normalizeMac(e.mac); });
            $('#blocked-count').text(list.length);
            if (list.length === 0) {
                $('#blocked-clients-table thead').hide();
                $('#blocked-clients-table tbody').html('');
                $('#blocked-empty').show();
            } else {
                $('#blocked-empty').hide();
                $('#blocked-clients-table thead').show();
                var html = '';
                list.forEach(function(e) {
                    var vendor = ouiLookup(e.mac);
                    var deviceLabel = e.hostname && e.hostname !== 'Unknown'
                        ? e.hostname + (vendor ? ' <small class="text-muted">(' + vendor + ')</small>' : '')
                        : (vendor || '<em class="text-muted">Unknown</em>');
                    var blockedAt = e.blocked_at ? (new Date(e.blocked_at)).toLocaleString('id-ID') : '-';
                    html += '<tr>';
                    html += '<td>' + deviceLabel + '</td>';
                    html += '<td><code class="small">' + e.mac + '</code></td>';
                    html += '<td><code class="small">' + (e.ip || '-') + '</code></td>';
                    html += '<td class="small text-muted">' + (e.reason || '-') + '</td>';
                    html += '<td class="small text-muted">' + blockedAt + '</td>';
                    html += '<td><button type="button" class="btn btn-xs btn-outline-success btn-unblock-client" data-mac="' + e.mac + '">'
                        + '<i class="fas fa-lock-open mr-1"></i>Unblok</button></td>';
                    html += '</tr>';
                });
                $('#blocked-clients-table tbody').html(html);
            }
        });
    }

    // ── LAN DHCP range auto-calc helpers ──
    function ipToInt(ip) {
        return ip.split('.').reduce(function(acc, oct) { return ((acc << 8) + parseInt(oct, 10)) >>> 0; }, 0);
    }
    function intToIp(n) {
        return [(n >>> 24) & 255, (n >>> 16) & 255, (n >>> 8) & 255, n & 255].join('.');
    }
    function isValidIp(ip) {
        return /^(\d{1,3}\.){3}\d{1,3}$/.test(ip) && ip.split('.').every(function(o) { return +o <= 255; });
    }
    function calcDhcpRange(ip, mask) {
        var ipInt   = ipToInt(ip);
        var maskInt = ipToInt(mask);
        var network    = (ipInt & maskInt) >>> 0;
        var broadcast  = (network | (~maskInt >>> 0)) >>> 0;
        var hostCount  = broadcast - network - 1;
        if (hostCount < 3) return null;
        // Put pool in the upper 2/3 of the subnet, skip .1 (device IP)
        var poolStart  = Math.max(network + Math.max(2, Math.floor(hostCount * 0.4)), ipInt + 1);
        var poolEnd    = broadcast - 1;
        if (poolStart >= poolEnd) return null;
        return { min: intToIp(poolStart), max: intToIp(poolEnd) };
    }
    function autoFillDhcpRange(silent) {
        var ip   = $('#lan-gateway-ip').val().trim();
        var mask = $('#lan-subnet-mask').val().trim();
        if (!isValidIp(ip) || !isValidIp(mask)) {
            if (!silent) toastr.warning('Masukkan IP Address dan Subnet Mask yang valid terlebih dahulu.');
            return;
        }
        var range = calcDhcpRange(ip, mask);
        if (!range) {
            if (!silent) toastr.warning('Subnet terlalu kecil untuk menghitung range DHCP.');
            return;
        }
        $('#lan-min-address').val(range.min);
        $('#lan-max-address').val(range.max);
        var maskInt = ipToInt(mask);
        var totalHosts = (~maskInt >>> 0) - 1;
        $('#lan-range-hint').text('Subnet /' + (32 - Math.log2(totalHosts + 2) | 0) + ' → ' + range.min + ' – ' + range.max);
        if (!silent) toastr.info('Range DHCP dihitung otomatis dari IP LAN.');
    }

    // ── LAN config edit/save ──
    $('#btn-edit-lan-config').on('click', function() {
        $('#lan-config-display').hide();
        $('#lan-config-form').show();
        $(this).hide();
    });
    $('#btn-cancel-lan-config').on('click', function() {
        $('#lan-config-form').hide();
        $('#lan-config-display').show();
        $('#btn-edit-lan-config').show();
        $('#lan-range-hint').text('');
    });
    // Auto-recalc on IP/mask blur when DHCP is enabled and range fields are empty or stale
    $('#lan-gateway-ip, #lan-subnet-mask').on('blur', function() {
        if ($('#lan-dhcp-enable').val() === 'true') {
            autoFillDhcpRange(true);
        }
    });
    // When enabling DHCP, auto-calc if range looks wrong relative to IP
    $('#lan-dhcp-enable').on('change', function() {
        if ($(this).val() === 'true') {
            var ip = $('#lan-gateway-ip').val().trim();
            var minAddr = $('#lan-min-address').val().trim();
            // Only auto-fill if range is empty or subnet doesn't match
            if (!minAddr || (isValidIp(ip) && isValidIp(minAddr) && (ipToInt(ip) & ipToInt($('#lan-subnet-mask').val())) !== (ipToInt(minAddr) & ipToInt($('#lan-subnet-mask').val())))) {
                autoFillDhcpRange(true);
            }
        }
    });
    $('#btn-auto-dhcp-range').on('click', function() {
        autoFillDhcpRange(false);
    });
    $('#btn-save-lan-config').on('click', function() {
        // Validate: DHCP range must be within same subnet as LAN IP
        var ip   = $('#lan-gateway-ip').val().trim();
        var mask = $('#lan-subnet-mask').val().trim();
        var minA = $('#lan-min-address').val().trim();
        var maxA = $('#lan-max-address').val().trim();
        if ($('#lan-dhcp-enable').val() === 'true' && ip && mask && minA && isValidIp(ip) && isValidIp(mask) && isValidIp(minA)) {
            var network = (ipToInt(ip)   & ipToInt(mask)) >>> 0;
            var minNet  = (ipToInt(minA) & ipToInt(mask)) >>> 0;
            if (network !== minNet) {
                toastr.error('Range DHCP harus berada dalam subnet yang sama dengan IP Address LAN. Klik "Auto Hitung Range" untuk memperbaiki.');
                return;
            }
        }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');
        var payload = {
            _token: '{{ csrf_token() }}',
            gateway_ip: ip,
            subnet_mask: mask,
            dhcp_server_enable: $('#lan-dhcp-enable').val() === 'true' ? 1 : 0,
            min_address: minA,
            max_address: maxA,
            lease_time: $('#lan-lease-time').val(),
            dns_servers: $('#lan-dns-servers').val(),
            domain_name: $('#lan-domain-name').val(),
        };
        $.post('/admin/onus/{{ $onu->id }}/tr069-lan', payload, function(resp) {
            if (resp.success) {
                toastr.success(resp.message || 'Konfigurasi LAN berhasil disimpan.');
                $('#btn-cancel-lan-config').trigger('click');
            } else {
                toastr.error(resp.message || 'Gagal menyimpan konfigurasi LAN.');
            }
        }).fail(function(xhr) {
            toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText));
        }).always(function() {
            $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan ke Perangkat');
        });
    });

    // ── Block client ──
    $(document).on('click', '.btn-block-client', function() {
        var mac      = $(this).data('mac');
        var hostname = $(this).data('hostname') || '';
        var ip       = $(this).data('ip') || '';
        Swal.fire({
            title: 'Blokir Client?',
            html: '<b>' + (hostname || mac) + '</b><br><code class="small">' + mac + '</code>',
            input: 'text',
            inputPlaceholder: 'Alasan (opsional)',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Blokir',
            cancelButtonText: 'Batal',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.post('/admin/onus/{{ $onu->id }}/tr069-block-client', {
                _token: '{{ csrf_token() }}',
                mac: mac, hostname: hostname, ip: ip,
                reason: result.value || '',
            }, function(resp) {
                if (resp.success) {
                    toastr.warning(hostname ? hostname + ' diblokir.' : mac + ' diblokir.');
                    loadBlockedClients();
                    loadTr069Summary(); // refresh host table
                } else {
                    toastr.error(resp.message || 'Gagal memblokir client.');
                }
            }).fail(function(xhr) {
                toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText));
            });
        });
    });

    // ── Unblock client ──
    $(document).on('click', '.btn-unblock-client', function() {
        var mac = $(this).data('mac');
        Swal.fire({
            title: 'Unblok Client?',
            html: 'MAC: <code>' + mac + '</code>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Unblok',
            cancelButtonText: 'Batal',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.post('/admin/onus/{{ $onu->id }}/tr069-unblock-client', {
                _token: '{{ csrf_token() }}',
                mac: mac,
            }, function(resp) {
                if (resp.success) {
                    toastr.success('Client berhasil di-unblok.');
                    loadBlockedClients();
                    loadTr069Summary();
                } else {
                    toastr.error(resp.message || 'Gagal unblok client.');
                }
            }).fail(function(xhr) {
                toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText));
            });
        });
    });

    // ── Refresh blocked list button ──
    $('#btn-refresh-blocked').on('click', function() {
        loadBlockedClients();
    });

    // Load blocked clients on Clients tab click
    $('a[href="#acs-clients"]').on('shown.bs.tab', function() {
        loadBlockedClients();
    });

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

    // ── Auto-poll after Refresh Data / addObject ──
    var pollTimer = null;
    var pollAttempts = 0;
    var MAX_POLL = 12; // max ~36 detik (12 × 3s)

    function stopPoll() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        pollAttempts = 0;
        $('#tr069-poll-status').remove();
    }

    /**
     * startPoll(btn, mode, extra)
     *  mode 'refresh' — stop when getParameterValues tasks all gone
     *  mode 'wifi'    — stop when wifi count reaches extra.expectedCount
     */
    function startPoll(btn, mode, extra) {
        stopPoll();
        pollAttempts = 0;
        mode  = mode  || 'refresh';
        extra = extra || {};

        // Inject status bar below toolbar
        if (!$('#tr069-poll-status').length) {
            $('#tr069-acs-toolbar').after(
                '<div id="tr069-poll-status" class="alert alert-info py-2 px-3 small mb-2">' +
                '<i class="fas fa-spinner fa-spin mr-1"></i>' +
                '<span id="tr069-poll-msg">Menunggu device check-in...</span>' +
                ' <button type="button" class="close ml-2" style="font-size:14px" id="btn-stop-poll"><span>&times;</span></button>' +
                '</div>'
            );
            $('#btn-stop-poll').on('click', function() {
                stopPoll();
                if (btn) btn.find('i').removeClass('fa-spin');
            });
        }

        pollTimer = setInterval(function() {
            pollAttempts++;
            var elapsed = pollAttempts * 3;
            $('#tr069-poll-msg').text('Menunggu device check-in... (' + elapsed + 's / ' + (MAX_POLL * 3) + 's maks)');

            $.get('/admin/onus/{{ $onu->id }}/tr069-summary')
                .done(function(res) {
                    if (!res.success || !res.found) return;
                    var data  = res.data || {};
                    var tasks = data.tasks || [];

                    var done = false;
                    if (mode === 'wifi') {
                        var wifiCount = (data.wifi || []).length;
                        if (extra.mode === 'decrease') {
                            done = (wifiCount <= (extra.expectedCount));
                        } else {
                            done = (wifiCount >= (extra.expectedCount || 2));
                        }
                    } else {
                        // 'refresh' mode: done when no getParameterValues tasks pending
                        var pending = tasks.filter(function(t) { return t.name === 'getParameterValues'; });
                        done = (pending.length === 0);
                    }

                    if (done) {
                        stopPoll();
                        if (btn) btn.find('i').removeClass('fa-spin');
                        renderTr069(data);
                        toastr.success('Data berhasil diperbarui dari device.');
                    }
                });

            if (pollAttempts >= MAX_POLL) {
                stopPoll();
                if (btn) btn.find('i').removeClass('fa-spin');
                $('#tr069-poll-status').removeClass('alert-info').addClass('alert-warning')
                    .html('<i class="fas fa-exclamation-triangle mr-1"></i>Device belum check-in setelah 2 menit. Coba Refresh Data lagi saat device online.');
                setTimeout(function() { $('#tr069-poll-status').remove(); }, 5000);
            }
        }, 3000);
    }

    // Refresh TR069
    $(document).on('click', '.btn-refresh-tr069, .btn-refresh-tr069-data', function() {
        var btn = $(this);
        btn.find('i').addClass('fa-spin');
        $.post('/admin/onus/{{ $onu->id }}/tr069-refresh', { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res.success) {
                    startPoll(btn);
                } else {
                    btn.find('i').removeClass('fa-spin');
                    Swal.fire('Info', res.message || 'Tidak dapat refresh', 'info');
                }
            })
            .fail(function() {
                btn.find('i').removeClass('fa-spin');
                loadTr069Summary();
            });
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

    // Factory Reset via TR-069
    $('.btn-tr069-factory-reset').click(function() {
        var btn = $(this);
        Swal.fire({
            title: 'Factory Reset via TR-069?',
            html: '<div class="text-danger"><strong>PERINGATAN!</strong><br>Semua konfigurasi ONU akan dihapus dan dikembalikan ke pengaturan pabrik.</div>',
            icon: 'error', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Ya, Factory Reset!'
        }).then(function(result) {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Resetting...');
                $.post('/admin/onus/{{ $onu->id }}/tr069-factory-reset', { _token: '{{ csrf_token() }}' })
                    .done(function(res) { Swal.fire('Berhasil', res.message || 'Factory reset dikirim', res.success ? 'success' : 'error'); })
                    .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Gagal mengirim factory reset', 'error'); })
                    .always(function() { btn.prop('disabled', false).html('<i class="fas fa-undo mr-1"></i>Factory Reset via TR-069'); });
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
            if (res.pending) {
                $('#modal-pppoe').modal('hide');
                Swal.fire({
                    icon: 'info',
                    title: 'Menunggu ONU',
                    html: (res.message || 'Task sedang diproses.') + '<br><small class="text-muted">App akan otomatis refresh saat task selesai.</small>',
                    timer: 4000, showConfirmButton: false
                });
                startPoll(null, 'refresh');
            } else if (res.success) {
                $('#modal-pppoe').modal('hide');
                Swal.fire({ title: 'Berhasil', text: 'PPPoE WAN berhasil dikonfigurasi!', icon: 'success', timer: 2500, showConfirmButton: false });
                startPoll(null, 'refresh');
            } else {
                Swal.fire('Gagal', res.message || 'Gagal mengirim task', 'error');
            }
        })
        .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Server error', 'error'); })
        .always(function() { btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i>Terapkan'); });
    });

    // Password toggle
    $(document).on('click', '.btn-toggle-pass', function() {
        var target = $($(this).data('target'));
        var icon = $(this).find('i');
        if (target.attr('type') === 'password') {
            target.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            target.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
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
                Swal.fire({ title: 'Berhasil', text: 'Konfigurasi WiFi dikirim.', icon: 'success', timer: 2000, showConfirmButton: false });
                startPoll(null, 'refresh');
            } else {
                Swal.fire('Gagal', res.message || 'Gagal mengirim task', 'error');
            }
        })
        .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Server error', 'error'); })
        .always(function() { btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan'); });
    });

    // Open add-ssid modal
    $(document).on('click', '#btn-add-ssid', function() {
        $('#add-ssid-name').val('');
        $('#add-ssid-password').val('');
        $('#add-ssid-enabled').val('1');
        $('#modal-add-ssid').modal('show');
    });

    $('#form-add-ssid').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Mengirim...');
        var currentCount = parseInt($('#tr069-wifi-count').text(), 10) || 0;
        $.post('/admin/onus/{{ $onu->id }}/tr069-wifi-add', {
            _token: '{{ csrf_token() }}',
            ssid: $('#add-ssid-name').val(),
            password: $('#add-ssid-password').val() || undefined,
            enabled: $('#add-ssid-enabled').val(),
        })
        .done(function(res) {
            if (res.success) {
                $('#modal-add-ssid').modal('hide');
                if (res.completed) {
                    // Device responded sync — reload langsung
                    loadTr069Summary();
                    toastr.success(res.message);
                } else {
                    // Task dikirim async — poll sampai wifi count bertambah
                    var expected = res.wifi_count || (currentCount + 1);
                    Swal.fire({
                        title: 'Task Dikirim!',
                        html: res.message + '<br><small class="text-muted">App akan otomatis refresh saat SSID muncul.</small>',
                        icon: 'info', timer: 4000, showConfirmButton: false
                    });
                    startPoll(null, 'wifi', { expectedCount: expected });
                }
            } else {
                Swal.fire('Gagal', res.message || 'Gagal menambah SSID', 'error');
            }
        })
        .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Server error', 'error'); })
        .always(function() { btn.prop('disabled', false).html('<i class="fas fa-plus mr-1"></i>Tambah SSID'); });
    });

    // Delete SSID
    $(document).on('click', '.btn-delete-wifi', function() {
        var path = $(this).data('path');
        var ssid = $(this).data('ssid');
        Swal.fire({
            title: 'Hapus SSID?',
            html: 'SSID <strong>' + $('<div>').text(ssid).html() + '</strong> akan dihapus dari device.<br><small class="text-muted">Pengguna yang terhubung akan diputus.</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            var currentCount = parseInt($('#tr069-wifi-count').text(), 10) || 1;
            $.ajax({
                url: '/admin/onus/{{ $onu->id }}/tr069-wifi',
                method: 'DELETE',
                data: { _token: '{{ csrf_token() }}', wlan_path: path },
            })
            .done(function(res) {
                if (res.success) {
                    if (res.completed) {
                        loadTr069Summary();
                        toastr.success('SSID berhasil dihapus.');
                    } else {
                        Swal.fire({
                            title: 'Perintah Dikirim',
                            html: res.message + '<br><small class="text-muted">App akan otomatis refresh saat SSID hilang.</small>',
                            icon: 'info', timer: 4000, showConfirmButton: false
                        });
                        startPoll(null, 'wifi', { expectedCount: currentCount - 1, mode: 'decrease' });
                    }
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal menghapus SSID', 'error');
                }
            })
            .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Server error', 'error'); });
        });
    });

    // WAN Edit
    $(document).on('click', '.btn-edit-wan', function() {
        var existingVlan = $(this).data('vlan');
        $('#wan-edit-path').val($(this).data('path'));
        $('#wan-edit-username').val($(this).data('username'));

        var $vlanSel = $('#wan-edit-vlan');
        if (existingVlan && !$vlanSel.find('option[value="' + existingVlan + '"]').length) {
            $vlanSel.append('<option value="' + existingVlan + '">VLAN ' + existingVlan + ' (saat ini)</option>');
        }
        $vlanSel.val(existingVlan || '');

        $('#wan-edit-password').val('');
        $('#modal-wan-edit').modal('show');
    });

    $('#form-wan-edit').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');
        $.ajax({
            url: '/admin/onus/{{ $onu->id }}/tr069-wan',
            method: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                wan_path: $('#wan-edit-path').val(),
                pppoe_username: $('#wan-edit-username').val(),
                pppoe_password: $('#wan-edit-password').val(),
                vlan: $('#wan-edit-vlan').val(),
            },
        })
        .done(function(res) {
            if (res.success) {
                $('#modal-wan-edit').modal('hide');
                Swal.fire({ title: 'Berhasil', text: 'PPPoE WAN berhasil diperbarui.', icon: 'success', timer: 2000, showConfirmButton: false });
                startPoll(null, 'refresh');
            } else {
                Swal.fire('Gagal', res.message || 'Gagal memperbarui WAN', 'error');
            }
        })
        .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Server error', 'error'); })
        .always(function() { btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Perubahan'); });
    });

    // WAN Delete
    $(document).on('click', '.btn-delete-wan', function() {
        var wanPath = $(this).data('path');
        var wanName = $(this).data('name');
        Swal.fire({
            title: 'Hapus WAN?',
            html: 'Apakah Anda yakin ingin menghapus koneksi <strong>' + wanName + '</strong>?<br><small class="text-danger">Koneksi PPPoE akan dihapus dari perangkat.</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: '/admin/onus/{{ $onu->id }}/tr069-wan',
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                    wan_path: wanPath,
                },
            })
            .done(function(res) {
                if (res.success || res.pending) {
                    Swal.fire({ title: 'Berhasil', text: res.message || 'WAN berhasil dihapus.', icon: 'success', timer: 2000, showConfirmButton: false });
                    startPoll(null, 'refresh');
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal menghapus WAN', 'error');
                }
            })
            .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Server error', 'error'); });
        });
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

                    // ── ACL Toggles ──────────────────────────────────────────
                    var aclMap = {
                        acl_ftp_wan: d.acl && d.acl.ftp_wan,
                        acl_ftp_lan: d.acl && d.acl.ftp_lan,
                        acl_http_wan: d.acl && d.acl.http_wan,
                        acl_http_lan: d.acl && d.acl.http_lan,
                        acl_ssh_wan: d.acl && d.acl.ssh_wan,
                        acl_ssh_lan: d.acl && d.acl.ssh_lan,
                        acl_samba_wan: d.acl && d.acl.samba_wan,
                        acl_samba_lan: d.acl && d.acl.samba_lan,
                        acl_telnet_wan: d.acl && d.acl.telnet_wan,
                        acl_telnet_lan: d.acl && d.acl.telnet_lan,
                        acl_icmp_echo: d.acl && d.acl.icmp_echo,
                        acl_cli_ssh: d.cli && d.cli.ssh_enable,
                        acl_cli_telnet: d.cli && d.cli.telnet_enable,
                        web_admin_enable: d.web_admin && d.web_admin.enable,
                        web_user_enable: d.web_user && d.web_user.enable,
                    };
                    Object.keys(aclMap).forEach(function(id) {
                        var v = aclMap[id];
                        $('#' + id).prop('checked', v === true || v === 'true' || v === '1' || v === 1);
                    });

                    // ── Brand detection & adaptive UI ─────────────────────────
                    var brand = d.brand || 'unknown';
                    var brandLabel = d.brand_label || (brand.charAt(0).toUpperCase() + brand.slice(1));
                    var brandColor = ({huawei:'danger', zte:'info', 'tp-link':'success', fiberhome:'warning', nokia:'primary', sercomm:'secondary', calix:'dark', dzs:'info', unknown:'secondary'})[brand] || 'secondary';
                    var brandHtml = '<span class="badge badge-' + brandColor + ' px-2 py-1"><i class="fas fa-microchip mr-1"></i>' + brandLabel + '</span>';
                    if (!d.acl_supported) {
                        brandHtml += ' <span class="badge badge-warning px-2 py-1 ml-1"><i class="fas fa-info-circle mr-1"></i>ACL tidak didukung untuk brand ini</span>';
                    }
                    $('#security-brand-bar').html(brandHtml);
                    $('#security-acl-card').toggle(!!d.acl_supported);
                    $('#security-cli-card').toggle(!!d.cli_supported);

                    // ── CLI credentials ───────────────────────────────────────
                    if (d.cli) {
                        $('#cli-username').val(d.cli.username || 'root');
                    }

                    // ── Web accounts ──────────────────────────────────────────
                    if (d.web_admin) $('#web-admin-name').val(d.web_admin.username || '-');
                    if (d.web_user)  $('#web-user-name').val(d.web_user.username || '-');

                    // ── ACS Info ──────────────────────────────────────────────
                    var acsRows = '';
                    if (d.acs) {
                        acsRows += '<tr><td width="40%">ACS URL</td><td><code class="small" style="word-break:break-all">' + (d.acs.url || '-') + '</code></td></tr>';
                        acsRows += '<tr><td>Username</td><td>' + (d.acs.username || '-') + '</td></tr>';
                        acsRows += '<tr><td>Periodic Inform</td><td><span class="badge badge-' + (d.acs.periodic_inform ? 'success' : 'secondary') + '">' + (d.acs.periodic_inform ? 'Enabled' : 'Disabled') + '</span></td></tr>';
                        acsRows += '<tr><td>Inform Interval</td><td>' + (d.acs.periodic_interval ? d.acs.periodic_interval + ' s' : '-') + '</td></tr>';
                        acsRows += '<tr><td>CWM URL</td><td><code class="small" style="word-break:break-all">' + (d.acs.connection_request_url || '-') + '</code></td></tr>';
                        var iv = d.acs.periodic_interval;
                        if (iv) {
                            $('#tr069-inform-input').val(iv);
                            $('#tr069-inform-hint').text('Saat ini: ' + iv + ' detik ≈ ' + Math.round(iv / 60) + ' menit');
                        }
                        $('#tr069-inform-form-wrap').show();
                    }
                    $('#tr069-acs-table').html(acsRows);

                    // ── Firewall / Network ────────────────────────────────────
                    var secRows = '';
                    secRows += '<tr><td width="40%">Firewall Level</td><td><span class="badge badge-warning">' + (d.firewall_level || 'N/A') + '</span></td></tr>';
                    if (d.dns_servers && d.dns_servers.length > 0) {
                        secRows += '<tr><td>DNS</td><td>' + d.dns_servers.map(function(x){return '<code>'+x.trim()+'</code>';}).join(' ') + '</td></tr>';
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

    // ── Save ALL ACL toggles ─────────────────────────────────────────────────
    $(document).on('click', '#btn-save-security', function() {
        var btn = $(this);
        var settings = {};
        $('.acl-toggle').each(function() {
            settings[$(this).data('key')] = $(this).is(':checked') ? 1 : 0;
        });
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Mengirim...');
        $.post('/admin/onus/{{ $onu->id }}/tr069-security', {
            _token: '{{ csrf_token() }}',
            settings: settings,
        })
        .done(function(res) {
            if (res.success) {
                toastr.success(res.status === 200
                    ? 'Settings berhasil diterapkan ke perangkat.'
                    : 'Settings dikirim, akan diterapkan saat device check-in berikutnya.');
                // Trigger a device refresh so GenieACS fetches updated values,
                // then reload security tab after 10s
                $.post('/admin/onus/{{ $onu->id }}/tr069-refresh', { _token: '{{ csrf_token() }}' });
                setTimeout(function() {
                    $('#tr069-security-content').hide();
                    $('#tr069-security-loading').show();
                    loadSecurityInfo();
                }, 10000);
            } else {
                toastr.error(res.message || 'Gagal mengirim settings');
            }
        })
        .fail(function() { toastr.error('Koneksi gagal'); })
        .always(function() { btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Semua'); });
    });

    // ── Save CLI password ────────────────────────────────────────────────────
    $(document).on('click', '#btn-save-cli-pw', function() {
        var pw = $('#cli-password').val().trim();
        if (!pw) { toastr.warning('Masukkan password baru'); return; }
        var btn = $(this).prop('disabled', true);
        $.post('/admin/onus/{{ $onu->id }}/tr069-security', {
            _token: '{{ csrf_token() }}',
            settings: { cli_password: pw },
        })
        .done(function(res) {
            if (res.success) {
                toastr.success('CLI password berhasil diubah');
                $('#cli-password').val('');
            } else {
                toastr.error(res.message || 'Gagal');
            }
        })
        .fail(function() { toastr.error('Koneksi gagal'); })
        .always(function() { btn.prop('disabled', false); });
    });

    // ── Save web UI password (admin or user) ──────────────────────────────────
    $(document).on('click', '.btn-save-web-pw', function() {
        var key = $(this).data('target'); // 'web_admin_password' or 'web_user_password'
        var field = key === 'web_admin_password' ? '#web-admin-password' : '#web-user-password';
        var pw = $(field).val().trim();
        if (!pw) { toastr.warning('Masukkan password baru'); return; }
        var btn = $(this).prop('disabled', true);
        $.post('/admin/onus/{{ $onu->id }}/tr069-security', {
            _token: '{{ csrf_token() }}',
            settings: (function(){ var s={}; s[key]=pw; return s; })(),
        })
        .done(function(res) {
            if (res.success) {
                toastr.success(res.message || 'Password berhasil diubah');
                $(field).val('');
            } else {
                toastr.error(res.message || 'Gagal');
            }
        })
        .fail(function() { toastr.error('Koneksi gagal'); })
        .always(function() { btn.prop('disabled', false); });
    });

    // Save PeriodicInformInterval
    $(document).on('click', '#btn-save-inform-interval', function() {
        var iv = parseInt($('#tr069-inform-input').val(), 10);
        if (!iv || iv < 30 || iv > 86400) {
            toastr.warning('Interval harus antara 30 – 86400 detik');
            return;
        }
        var btn = $(this).prop('disabled', true).find('i').addClass('fa-spin').end();
        $.post('/admin/onus/{{ $onu->id }}/tr069-inform-interval', { _token: '{{ csrf_token() }}', interval: iv })
            .done(function(res) {
                if (res.success) {
                    var mins = Math.round(iv / 60);
                    $('#tr069-inform-hint').text('Tersimpan: ' + iv + ' detik ≈ ' + mins + ' menit');
                    toastr.success(res.message || 'Interval berhasil disimpan');
                } else {
                    toastr.error(res.message || 'Gagal menyimpan interval');
                }
            })
            .fail(function() { toastr.error('Koneksi gagal'); })
            .always(function() { btn.prop('disabled', false).find('i').removeClass('fa-spin'); });
    });

    // Edit ProvisioningCode (DeviceInfo.ProvisioningCode)
    $(document).on('click', '#btn-edit-provcode', function() {
        var current = $('#tr069-provcode-text').text().trim();
        $('#provcode-current').text(current || '-');
        $('#provcode-input').val(current);
        $('#modal-provcode').modal('show');
        setTimeout(function(){ $('#provcode-input').trigger('focus').trigger('select'); }, 300);
    });

    $(document).on('submit', '#form-provcode', function(e) {
        e.preventDefault();
        var code = ($('#provcode-input').val() || '').trim();
        var current = $('#tr069-provcode-text').text().trim();
        if (!code) { toastr.warning('ProvisioningCode tidak boleh kosong'); return; }
        if (code.length > 64) { toastr.warning('Maksimal 64 karakter'); return; }
        if (code === current) { $('#modal-provcode').modal('hide'); return; }

        var btn = $('#btn-save-provcode').prop('disabled', true);
        var origHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Mengirim...');

        $.post('/admin/onus/{{ $onu->id }}/tr069-provisioning-code', { _token: '{{ csrf_token() }}', code: code })
            .done(function(res) {
                if (res.success) {
                    $('#tr069-provcode-text').text(code);
                    toastr.success(res.message || 'ProvisioningCode tersimpan');
                    $('#modal-provcode').modal('hide');
                } else {
                    toastr.error(res.message || 'Gagal menyimpan');
                }
            })
            .fail(function() { toastr.error('Koneksi gagal'); })
            .always(function() { btn.prop('disabled', false).html(origHtml); });
    });

    // Clients tab: refresh
    $('.btn-refresh-clients').click(function() {
        var btn = $(this);
        btn.find('i').addClass('fa-spin');
        loadTr069Summary();
        loadBlockedClients();
        setTimeout(function() { btn.find('i').removeClass('fa-spin'); }, 2000);
    });

    // ── Save web UI username (admin or user) ─────────────────────────────────
    $(document).on('click', '.btn-save-web-username', function() {
        var key = $(this).data('target'); // 'web_admin_username' or 'web_user_username'
        var field = key === 'web_admin_username' ? '#web-admin-name' : '#web-user-name';
        var username = $(field).val().trim();
        if (!username) { toastr.warning('Masukkan username baru'); return; }
        var btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.post('/admin/onus/{{ $onu->id }}/tr069-security', {
            _token: '{{ csrf_token() }}',
            settings: (function(){ var s={}; s[key]=username; return s; })(),
        })
        .done(function(res) {
            if (res.success) {
                toastr.success(res.message || 'Username berhasil diubah');
            } else {
                toastr.error(res.message || 'Gagal mengubah username');
            }
        })
        .fail(function() { toastr.error('Koneksi gagal'); })
        .always(function() { btn.prop('disabled', false).html('<i class="fas fa-user-edit"></i>'); });
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
