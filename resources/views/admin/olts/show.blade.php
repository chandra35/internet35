@extends('layouts.admin')

@section('title', 'Detail OLT - ' . $olt->name)

@section('page-title', 'Detail OLT: ' . $olt->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.olts.index') }}">OLT</a></li>
    <li class="breadcrumb-item active">{{ $olt->name }}</li>
@endsection

@section('content')
<!-- Progress Modal -->
<div class="modal fade" id="modal-progress" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-cog fa-spin mr-2" id="progress-spinner"></i>
                    <span id="progress-title">Memproses...</span>
                </h5>
            </div>
            <div class="modal-body">
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" id="progress-bar"
                         style="width: 0%">0%</div>
                </div>
                <div id="progress-logs" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                    <!-- Progress logs will be appended here -->
                </div>
            </div>
            <div class="modal-footer" id="progress-footer" style="display: none;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-sync mr-1"></i>Refresh Halaman
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Row 1: Info Card + PON Port Stats -->
<div class="row">
    <!-- OLT Info (compact) -->
    <div class="col-lg-4">
        <!-- Status Card -->
        <div class="card card-widget widget-user-2 shadow">
            <div class="widget-user-header bg-{{ $olt->status == 'active' ? 'success' : ($olt->status == 'maintenance' ? 'warning' : 'danger') }}">
                <div class="widget-user-image">
                    <div class="img-circle elevation-2 bg-white d-flex align-items-center justify-content-center" 
                         style="width: 50px; height: 50px; font-size: 1.3rem; color: #333;">
                        <i class="fas fa-server"></i>
                    </div>
                </div>
                <h3 class="widget-user-username" style="font-size:1.1rem;">{{ $olt->name }}</h3>
                <h5 class="widget-user-desc">{{ $olt->brandLabel }} - {{ $olt->model ?? 'Unknown' }} | <code style="color:#fff">{{ $olt->ip_address }}</code></h5>
            </div>
            <div class="card-footer p-0">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <span class="nav-link py-1">
                            <i class="fas fa-hdd text-primary mr-1"></i> ONU
                            <span class="float-right">
                                <span class="badge badge-success">{{ $onuStats['online'] }} online</span>
                                <span class="badge badge-danger">{{ $onuStats['offline'] }} offline</span>
                                <span class="badge badge-secondary">{{ $onuStats['total'] }} total</span>
                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link py-1">
                            <i class="fas fa-ethernet text-info mr-1"></i> PON Ports
                            <span class="float-right badge badge-info">{{ $olt->total_pon_ports ?? '-' }}</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link py-1">
                            <i class="fas fa-exclamation-triangle text-warning mr-1"></i> Sinyal Lemah
                            <span class="float-right badge badge-warning">{{ $onuStats['weak_signal'] }}</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link py-1">
                            <i class="fas fa-network-wired text-muted mr-1"></i> SNMP
                            <span class="float-right"><small>Port {{ $olt->snmp_port }} | {{ $olt->snmp_community }}</small></span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link py-1">
                            <i class="fas fa-terminal text-muted mr-1"></i> Telnet
                            <span class="float-right">
                                @if($olt->telnet_enabled)
                                    <span class="badge badge-success">Port {{ $olt->telnet_port }}</span>
                                @else
                                    <span class="badge badge-secondary">Off</span>
                                @endif
                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link py-1">
                            <i class="fas fa-building text-muted mr-1"></i> POP / Router
                            <span class="float-right"><small>{{ $olt->pop->name ?? '-' }} / {{ $olt->router->name ?? '-' }}</small></span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link py-1">
                            <i class="fas fa-clock text-muted mr-1"></i> Last Sync
                            <span class="float-right"><small>{{ $olt->last_sync_at ? $olt->last_sync_at->diffForHumans() : 'Belum pernah' }}</small></span>
                        </span>
                    </li>
                </ul>
            </div>
            <div class="card-footer">
                @can('olts.edit')
                <a href="{{ route('admin.olts.edit', $olt) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Edit
                </a>
                @endcan
                <button type="button" class="btn btn-info btn-sm btn-sync-olt" data-id="{{ $olt->id }}">
                    <i class="fas fa-sync"></i> Sync ONU
                </button>
                <button type="button" class="btn btn-primary btn-sm btn-test-connection" data-id="{{ $olt->id }}">
                    <i class="fas fa-plug"></i> Test
                </button>
                <a href="{{ route('admin.olts.infrastructure', $olt) }}" class="btn btn-dark btn-sm">
                    <i class="fas fa-server"></i> Infrastruktur
                </a>
            </div>
        </div>

        <!-- Map Card (small) -->
        @if($olt->latitude && $olt->longitude)
        <div class="card">
            <div class="card-body p-0">
                <div id="map" style="height: 150px;"></div>
            </div>
        </div>
        @endif
    </div>

    <!-- PON Port Stats with ONU count + clickable links -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-project-diagram mr-2"></i>PON Ports</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Port</th>
                            <th class="text-center">Total ONU</th>
                            <th class="text-center">Online</th>
                            <th class="text-center">Offline</th>
                            <th class="text-center">%</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 1; $i <= ($olt->total_pon_ports ?? 8); $i++)
                        @php
                            $portOnus = $olt->onus->where('port', $i);
                            $onuCount = $portOnus->count();
                            $onlineCount = $portOnus->where('status', 'online')->count();
                            $offlineCount = $onuCount - $onlineCount;
                            $percentage = $onuCount > 0 ? round(($onlineCount / $onuCount) * 100) : 0;
                        @endphp
                        <tr>
                            <td>
                                <i class="fas fa-ethernet text-{{ $onuCount > 0 ? ($percentage >= 80 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger')) : 'muted' }} mr-1"></i>
                                <strong>PON {{ $olt->onus->first() ? $olt->onus->first()->slot : 1 }}/{{ $i }}</strong>
                            </td>
                            <td class="text-center">
                                @if($onuCount > 0)
                                    <span class="badge badge-secondary">{{ $onuCount }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($onlineCount > 0)
                                    <span class="badge badge-success">{{ $onlineCount }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($offlineCount > 0)
                                    <span class="badge badge-danger">{{ $offlineCount }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="progress progress-sm my-1" style="width:60px;display:inline-block;">
                                    <div class="progress-bar bg-{{ $percentage >= 80 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger') }}" 
                                         style="width: {{ $percentage }}%"></div>
                                </div>
                                <small>{{ $percentage }}%</small>
                            </td>
                            <td class="text-right">
                                @if($onuCount > 0)
                                <a href="{{ route('admin.olts.onus', [$olt, 'port' => $i]) }}" class="btn btn-xs btn-outline-primary" title="Lihat ONU PON {{ $i }}">
                                    <i class="fas fa-list"></i> Detail
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                    <tfoot class="thead-light">
                        <tr>
                            <td><strong>Total</strong></td>
                            <td class="text-center"><strong>{{ $olt->onus->count() }}</strong></td>
                            <td class="text-center"><strong class="text-success">{{ $olt->onus->where('status', 'online')->count() }}</strong></td>
                            <td class="text-center"><strong class="text-danger">{{ $olt->onus->where('status', '!=', 'online')->count() }}</strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Traffic Stats -->
<div class="row" id="traffic-stats-section">
    <!-- PON Ports Traffic -->
    <div class="col-lg-7">
        <div class="card card-outline card-primary">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-tachometer-alt mr-2"></i>PON Traffic</h3>
                <div class="card-tools">
                    <span class="text-muted text-sm mr-2" id="pon-timestamp"></span>
                    <button type="button" class="btn btn-tool btn-sm" id="btn-force-refresh" title="Force Refresh">
                        <i class="fas fa-redo-alt"></i>
                    </button>
                    <button type="button" class="btn btn-tool btn-sm" id="btn-refresh-traffic" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="pon-loading" class="text-center p-3">
                    <i class="fas fa-spinner fa-spin text-muted"></i> <small class="text-muted">Loading...</small>
                </div>
                <table class="table table-sm table-striped mb-0" id="table-pon-traffic" style="display: none;">
                    <thead class="thead-light">
                        <tr>
                            <th>Port</th>
                            <th>Status</th>
                            <th class="text-right">Download</th>
                            <th class="text-right">Upload</th>
                            <th class="text-right">TX Power</th>
                            <th class="text-right">Temp</th>
                        </tr>
                    </thead>
                    <tbody id="pon-traffic-body"></tbody>
                    <tfoot class="table-primary">
                        <tr>
                            <td colspan="2"><strong>Total</strong></td>
                            <td class="text-right"><strong id="pon-total-in">-</strong></td>
                            <td class="text-right"><strong id="pon-total-out">-</strong></td>
                            <td colspan="2" class="text-right text-muted" id="pon-optical-avg">-</td>
                        </tr>
                    </tfoot>
                </table>
                <div id="pon-error" class="text-center p-3 text-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i> <small>Failed to load</small>
                </div>
            </div>
        </div>
    </div>
    <!-- Uplink Ports -->
    <div class="col-lg-5">
        <div class="card card-outline card-success">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Uplink Traffic</h3>
                <div class="card-tools">
                    <span class="text-muted text-sm" id="traffic-timestamp"></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="uplink-loading" class="text-center p-3">
                    <i class="fas fa-spinner fa-spin text-muted"></i> <small class="text-muted">Loading...</small>
                </div>
                <table class="table table-sm table-striped mb-0" id="table-uplink-traffic" style="display: none;">
                    <thead class="thead-light">
                        <tr>
                            <th>Port</th>
                            <th>Status</th>
                            <th class="text-right">Download</th>
                            <th class="text-right">Upload</th>
                        </tr>
                    </thead>
                    <tbody id="uplink-traffic-body"></tbody>
                    <tfoot class="table-success">
                        <tr>
                            <td colspan="2"><strong>Total</strong></td>
                            <td class="text-right"><strong id="uplink-total-in">-</strong></td>
                            <td class="text-right"><strong id="uplink-total-out">-</strong></td>
                        </tr>
                    </tfoot>
                </table>
                <div id="uplink-error" class="text-center p-3 text-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i> <small>Failed to load</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: ONU List (full width) -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-hdd mr-2"></i>Daftar ONU ({{ $onuStats['total'] }})</h3>
                <div class="card-tools">
                    @can('onus.register')
                    <button type="button" class="btn btn-success btn-sm btn-scan-unregistered" data-id="{{ $olt->id }}">
                        <i class="fas fa-search-plus"></i> Scan ONU Baru
                    </button>
                    @endcan
                </div>
            </div>
            <div class="card-body p-0">
                @if($olt->onus->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-sm mb-0" id="table-onus">
                        <thead>
                            <tr>
                                <th>PON/ONU</th>
                                <th>Nama</th>
                                <th>Zone</th>
                                <th>ODP</th>
                                <th>SN</th>
                                <th>Status</th>
                                <th>Signal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($olt->onus as $onu)
                            <tr>
                                <td><strong>{{ $onu->slot }}/{{ $onu->port }}/{{ $onu->onu_id }}</strong></td>
                                <td>{{ $onu->name ?: '-' }}</td>
                                <td>{{ $onu->zone->name ?? '-' }}</td>
                                <td>{{ $onu->odp->name ?? '-' }}</td>
                                <td><code>{{ $onu->serial_number }}</code></td>
                                <td>
                                    @if($onu->status == 'online')
                                        <span class="badge badge-success">Online</span>
                                    @elseif($onu->status == 'offline')
                                        <span class="badge badge-danger">Offline</span>
                                    @elseif($onu->status == 'los')
                                        <span class="badge badge-warning">LOS</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($onu->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $signal = $onu->olt_rx_power ?? $onu->rx_power;
                                        $signalClass = 'success';
                                        if ($signal === null) $signalClass = 'secondary';
                                        elseif ($signal < -27) $signalClass = 'danger';
                                        elseif ($signal < -25) $signalClass = 'warning';
                                    @endphp
                                    <span class="badge badge-{{ $signalClass }}">
                                        {{ $signal !== null ? number_format($signal, 2) . ' dBm' : '-' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.onus.show', $onu) }}" class="btn btn-xs btn-info" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @can('onus.reboot')
                                    <button type="button" class="btn btn-xs btn-warning btn-reboot-onu" data-id="{{ $onu->id }}" title="Reboot">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                    @endcan
                                    @can('onus.unregister')
                                    <button type="button" class="btn btn-xs btn-danger btn-unregister-onu" data-id="{{ $onu->id }}" data-sn="{{ $onu->serial_number }}" title="Unregister">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p>Belum ada ONU terdaftar</p>
                    <button type="button" class="btn btn-success btn-sm btn-scan-unregistered" data-id="{{ $olt->id }}">
                        <i class="fas fa-search-plus"></i> Scan ONU Baru
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Unregistered ONU -->
<div class="modal fade" id="modal-unregistered" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title"><i class="fas fa-search-plus mr-2"></i>ONU Belum Terdaftar</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="unregistered-loading" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                    <p>Sedang scanning ONU...</p>
                </div>
                <div id="unregistered-result" style="display:none;">
                    <table class="table table-bordered table-sm" id="table-unregistered">
                        <thead>
                            <tr>
                                <th>PON</th>
                                <th>Serial Number</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="unregistered-empty" class="text-center py-5" style="display:none;">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p>Tidak ada ONU baru yang ditemukan</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Register ONU -->
<div class="modal fade" id="modal-register" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-register">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Register ONU</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="olt_id" value="{{ $olt->id }}">
                    <input type="hidden" name="slot" id="reg_slot">
                    <input type="hidden" name="port" id="reg_port">
                    <input type="hidden" name="pon_port" id="reg_pon_port">
                    <input type="hidden" name="serial_number" id="reg_serial_number">
                    
                    <div class="alert alert-info">
                        <strong>PON Port:</strong> <span id="reg_pon_display"></span><br>
                        <strong>Serial Number:</strong> <span id="reg_sn_display"></span><br>
                        <strong>ONU Type:</strong> <span id="reg_onu_type_display" class="text-primary">-</span>
                    </div>

                    <div class="form-group">
                        <label>Nama ONU <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Contoh: ONU-AHMAD">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Zone</label>
                                <select name="zone_id" id="reg_zone_id" class="form-control">
                                    <option value="">-- Pilih Zone --</option>
                                    @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ODP (Splitter)</label>
                                <select name="odp_id" id="reg_odp_id" class="form-control" disabled>
                                    <option value="">-- Pilih Zone dulu --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Pelanggan (Opsional)</label>
                        <select name="customer_id" class="form-control select2-customer" style="width:100%">
                            <option value="">-- Pilih Pelanggan --</option>
                        </select>
                    </div>

                    @if($olt->brand == 'zte')
                    <div class="card card-outline card-info mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">ZTE C320 - Advanced Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Line Profile</label>
                                        <select name="line_profile" class="form-control">
                                            <option value="">-- Default OLT --</option>
                                            @foreach($profiles->where('type', \App\Models\OltProfile::TYPE_LINE) as $profile)
                                            <option value="{{ $profile->name }}">{{ $profile->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Service Profile</label>
                                        <select name="service_profile" class="form-control">
                                            <option value="">-- Opsional --</option>
                                            @foreach($profiles->where('type', \App\Models\OltProfile::TYPE_SERVICE) as $profile)
                                            <option value="{{ $profile->name }}">{{ $profile->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>VLAN ID</label>
                                        <input type="number" name="vlan_id" class="form-control" min="1" max="4094" placeholder="Kosong = ambil dari profile/helper">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>GEM Port</label>
                                        <input type="number" name="gem_port" class="form-control" min="1" placeholder="Kosong = ambil dari profile/helper">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>T-CONT ID</label>
                                        <input type="number" name="tcont_id" class="form-control" min="1" placeholder="Kosong = ambil dari profile/helper">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Service Port Mode</label>
                                        <select name="service_port_mode" class="form-control">
                                            <option value="">-- Default/Profile --</option>
                                            <option value="tag">Tag</option>
                                            <option value="translate">Translate</option>
                                            <option value="transparent">Transparent</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Service ID</label>
                                        <input type="number" name="service_id" class="form-control" min="1" placeholder="Kosong = ambil dari profile/helper">
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">Pilih profile dulu jika tersedia. Field yang kosong akan diambil dari config profile OLT atau default helper ZTE.</small>
                        </div>
                    </div>
                    @elseif($profiles->count() > 0)
                    <div class="form-group">
                        <label>Profile OLT</label>
                        <select name="profile_id" class="form-control">
                            <option value="">-- Opsional --</option>
                            @foreach($profiles as $profile)
                            <option value="{{ $profile->id }}">{{ $profile->type_label }} - {{ $profile->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i>Register ONU
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
@endpush

@push('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(function() {
    @php
        $zteProfileConfigs = $profiles
            ->filter(fn($profile) => in_array($profile->type, [\App\Models\OltProfile::TYPE_LINE, \App\Models\OltProfile::TYPE_SERVICE], true))
            ->mapWithKeys(fn($profile) => ["{$profile->type}:{$profile->name}" => $profile->config ?? []]);
    @endphp
    var zteProfileConfigs = @json($zteProfileConfigs);

    // DataTable - only init if table exists
    if ($('#table-onus').length) {
        $('#table-onus').DataTable({
            pageLength: 25,
            order: [[0, 'asc']],
            language: {
                "emptyTable": "Tidak ada data",
                "info": "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                "infoEmpty": "Menampilkan 0 data",
                "infoFiltered": "(disaring dari _MAX_ total data)",
                "lengthMenu": "Tampilkan _MENU_ data",
                "loadingRecords": "Memuat...",
                "processing": "Memproses...",
                "search": "Cari:",
                "zeroRecords": "Tidak ditemukan data yang cocok",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Berikutnya",
                    "previous": "Sebelumnya"
                }
            }
        });
    }

    // Map
    @if($olt->latitude && $olt->longitude)
    var map = L.map('map').setView([{{ $olt->latitude }}, {{ $olt->longitude }}], 15);
    
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
    L.control.layers({
        "Satelit": satelliteLayer,
        "Peta": osmLayer,
        "Hybrid": hybridLayer
    }, null, { position: 'topright' }).addTo(map);
    
    L.marker([{{ $olt->latitude }}, {{ $olt->longitude }}])
        .addTo(map)
        .bindPopup('<strong>{{ $olt->name }}</strong>');
    @endif

    // Select2 for customer
    $('.select2-customer').select2({
        theme: 'bootstrap4',
        dropdownParent: $('#modal-register'),
        ajax: {
            url: '{{ route("admin.customers.search") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    pop_id: '{{ $olt->pop_id }}',
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

    // Sync OLT with Progress
    $('.btn-sync-olt').click(function() {
        var id = $(this).data('id');
        var btn = $(this);
        
        // Show progress modal
        $('#progress-title').text('Sinkronisasi ONU');
        $('#progress-bar').css('width', '0%').text('0%').removeClass('bg-success bg-danger bg-warning');
        $('#progress-logs').html('');
        $('#progress-footer').hide();
        $('#progress-spinner').show();
        $('#modal-progress').modal('show');
        
        btn.prop('disabled', true);
        
        // Use Server-Sent Events for streaming progress
        var eventSource = new EventSource('/admin/olts/' + id + '/sync-stream');
        
        eventSource.onmessage = function(event) {
            var data = JSON.parse(event.data);
            
            if (data.type === 'progress') {
                // Update progress bar
                $('#progress-bar').css('width', data.percent + '%').text(data.percent + '%');
                
                // Add log entry
                var logClass = 'text-muted';
                var icon = 'fa-info-circle';
                if (data.status === 'success') {
                    logClass = 'text-success';
                    icon = 'fa-check-circle';
                } else if (data.status === 'error') {
                    logClass = 'text-danger';
                    icon = 'fa-times-circle';
                } else if (data.status === 'warning') {
                    logClass = 'text-warning';
                    icon = 'fa-exclamation-circle';
                }
                
                $('#progress-logs').append(
                    '<div class="' + logClass + '">' +
                    '<i class="fas ' + icon + ' mr-1"></i>' +
                    '<small class="text-muted">[' + data.time + ']</small> ' + 
                    data.message + '</div>'
                );
                
                // Auto scroll to bottom
                $('#progress-logs').scrollTop($('#progress-logs')[0].scrollHeight);
            }
            
            if (data.type === 'complete') {
                eventSource.close();
                $('#progress-spinner').hide();
                $('#progress-footer').show();
                btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Sync ONU');
                
                if (data.success) {
                    $('#progress-bar').addClass('bg-success').removeClass('progress-bar-animated');
                    $('#progress-title').html('<i class="fas fa-check-circle mr-2"></i>Sinkronisasi Selesai');
                } else {
                    $('#progress-bar').addClass('bg-danger').removeClass('progress-bar-animated');
                    $('#progress-title').html('<i class="fas fa-times-circle mr-2"></i>Sinkronisasi Gagal');
                }
            }
        };
        
        eventSource.onerror = function() {
            eventSource.close();
            $('#progress-spinner').hide();
            $('#progress-footer').show();
            $('#progress-bar').addClass('bg-danger').removeClass('progress-bar-animated');
            $('#progress-title').html('<i class="fas fa-times-circle mr-2"></i>Koneksi Terputus');
            $('#progress-logs').append('<div class="text-danger"><i class="fas fa-times-circle mr-1"></i>Koneksi ke server terputus</div>');
            btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Sync ONU');
        };
    });

    // Test Connection with Progress
    $('.btn-test-connection').click(function() {
        var id = $(this).data('id');
        var btn = $(this);
        
        // Show progress modal
        $('#progress-title').text('Test Koneksi OLT');
        $('#progress-bar').css('width', '0%').text('0%').removeClass('bg-success bg-danger bg-warning');
        $('#progress-logs').html('');
        $('#progress-footer').hide();
        $('#progress-spinner').show();
        $('#modal-progress').modal('show');
        
        btn.prop('disabled', true);
        
        // Use Server-Sent Events for streaming progress
        var eventSource = new EventSource('/admin/olts/' + id + '/test-connection-stream');
        
        eventSource.onmessage = function(event) {
            var data = JSON.parse(event.data);
            
            if (data.type === 'progress') {
                // Update progress bar
                $('#progress-bar').css('width', data.percent + '%').text(data.percent + '%');
                
                // Add log entry
                var logClass = 'text-muted';
                var icon = 'fa-info-circle';
                if (data.status === 'success') {
                    logClass = 'text-success';
                    icon = 'fa-check-circle';
                } else if (data.status === 'error') {
                    logClass = 'text-danger';
                    icon = 'fa-times-circle';
                } else if (data.status === 'warning') {
                    logClass = 'text-warning';
                    icon = 'fa-exclamation-circle';
                }
                
                $('#progress-logs').append(
                    '<div class="' + logClass + '">' +
                    '<i class="fas ' + icon + ' mr-1"></i>' +
                    '<small class="text-muted">[' + data.time + ']</small> ' + 
                    data.message + '</div>'
                );
                
                // Auto scroll to bottom
                $('#progress-logs').scrollTop($('#progress-logs')[0].scrollHeight);
            }
            
            if (data.type === 'complete') {
                eventSource.close();
                $('#progress-spinner').hide();
                $('#progress-footer').show();
                btn.prop('disabled', false).html('<i class="fas fa-plug"></i> Test Koneksi');
                
                if (data.success) {
                    $('#progress-bar').addClass('bg-success').removeClass('progress-bar-animated');
                    $('#progress-title').html('<i class="fas fa-check-circle mr-2"></i>Test Koneksi Selesai');
                } else {
                    $('#progress-bar').addClass('bg-danger').removeClass('progress-bar-animated');
                    $('#progress-title').html('<i class="fas fa-times-circle mr-2"></i>Test Koneksi Gagal');
                }
            }
        };
        
        eventSource.onerror = function() {
            eventSource.close();
            $('#progress-spinner').hide();
            $('#progress-footer').show();
            $('#progress-bar').addClass('bg-danger').removeClass('progress-bar-animated');
            $('#progress-title').html('<i class="fas fa-times-circle mr-2"></i>Koneksi Terputus');
            $('#progress-logs').append('<div class="text-danger"><i class="fas fa-times-circle mr-1"></i>Koneksi ke server terputus</div>');
            btn.prop('disabled', false).html('<i class="fas fa-plug"></i> Test Koneksi');
        };
    });

    // Scan Unregistered
    $('.btn-scan-unregistered').click(function() {
        var id = $(this).data('id');
        $('#modal-unregistered').modal('show');
        $('#unregistered-loading').show();
        $('#unregistered-result').hide();
        $('#unregistered-empty').hide();
        
        $.get('/admin/olts/' + id + '/unregistered-onus')
            .done(function(res) {
                $('#unregistered-loading').hide();
                var onus = res.onus || res.data || [];
                if (onus.length > 0) {
                    var tbody = $('#table-unregistered tbody');
                    tbody.empty();
                    onus.forEach(function(onu) {
                        var ponDisplay = `${onu.slot}/${onu.port}`;
                        tbody.append(`
                            <tr>
                                <td>${ponDisplay}</td>
                                <td><code>${onu.serial_number}</code></td>
                                <td>
                                    <button type="button" class="btn btn-success btn-sm btn-register-onu"
                                            data-slot="${onu.slot}" data-port="${onu.port}"
                                            data-pon="${ponDisplay}" data-sn="${onu.serial_number}">
                                        <i class="fas fa-plus"></i> Register
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                    $('#unregistered-result').show();
                } else {
                    $('#unregistered-empty').show();
                }
            })
            .fail(function(xhr) {
                $('#unregistered-loading').hide();
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal scanning ONU', 'error');
                $('#modal-unregistered').modal('hide');
            });
    });

    // Register ONU - Open Modal
    $(document).on('click', '.btn-register-onu', function() {
        var pon = $(this).data('pon');
        var sn = $(this).data('sn');
        var slot = $(this).data('slot');
        var port = $(this).data('port');

        if ($('#form-register')[0]) {
            $('#form-register')[0].reset();
            $('.select2-customer').val(null).trigger('change');
            $('#reg_odp_id').html('<option value="">-- Pilih Zone dulu --</option>').prop('disabled', true);
        }
        
        $('#reg_slot').val(slot);
        $('#reg_port').val(port);
        $('#reg_pon_port').val(pon);
        $('#reg_serial_number').val(sn);
        $('#reg_pon_display').text(pon);
        $('#reg_sn_display').text(sn);
        $('#reg_onu_type_display').text(detectOnuType(sn));
        
        $('#modal-unregistered').modal('hide');
        $('#modal-register').modal('show');
    });

    // ONU Type auto-detect from serial number prefix
    function detectOnuType(sn) {
        if (!sn || sn.length < 4) return '-';
        var prefix = sn.substring(0, 4).toUpperCase();
        var map = {
            'HWTC': 'Huawei HG8245H', 'HWTG': 'Huawei HG8245H5', 'HWTE': 'Huawei EG8145V5',
            'ZTEG': 'ZTE F663N', 'ZICG': 'ZTE F663NV9', 'PRTS': 'Proscend',
            'ALCL': 'Nokia/Alcatel', 'FHTT': 'FiberHome', 'TPLG': 'TP-Link',
            'DSNW': 'DASAN', 'MSTC': 'ZyXEL', 'SMBS': 'SmartRG',
        };
        return map[prefix] || prefix;
    }

    // Zone/ODP cascading dropdown
    $('#reg_zone_id').change(function() {
        var zoneId = $(this).val();
        var odpSelect = $('#reg_odp_id');
        
        if (!zoneId) {
            odpSelect.html('<option value="">-- Pilih Zone dulu --</option>').prop('disabled', true);
            return;
        }
        
        odpSelect.html('<option value="">Memuat...</option>').prop('disabled', true);
        
        $.get('/admin/olts/{{ $olt->id }}/zones/' + zoneId + '/odps')
            .done(function(res) {
                var html = '<option value="">-- Pilih ODP --</option>';
                (res.odps || []).forEach(function(odp) {
                    var label = odp.name;
                    if (odp.code) label += ' (' + odp.code + ')';
                    if (odp.total_ports) label += ' [' + (odp.used_ports || 0) + '/' + odp.total_ports + ']';
                    html += '<option value="' + odp.id + '">' + label + '</option>';
                });
                odpSelect.html(html).prop('disabled', false);
            })
            .fail(function() {
                odpSelect.html('<option value="">Gagal memuat ODP</option>').prop('disabled', true);
            });
    });

    function applyZteProfileDefaults() {
        var form = $('#form-register');
        if (!form.length || '{{ $olt->brand }}' !== 'zte') {
            return;
        }

        var lineProfile = form.find('select[name="line_profile"]').val();
        var serviceProfile = form.find('select[name="service_profile"]').val();
        var merged = {};

        if (lineProfile && zteProfileConfigs['line:' + lineProfile]) {
            merged = $.extend({}, merged, zteProfileConfigs['line:' + lineProfile]);
        }

        if (serviceProfile && zteProfileConfigs['service:' + serviceProfile]) {
            merged = $.extend({}, merged, zteProfileConfigs['service:' + serviceProfile]);
        }

        var fieldMap = {
            vlan_id: ['vlan_id', 'vlan'],
            gem_port: ['gem_port'],
            tcont_id: ['tcont_id'],
            service_id: ['service_id'],
            service_port_mode: ['service_port_mode']
        };

        Object.keys(fieldMap).forEach(function(fieldName) {
            var value = null;

            fieldMap[fieldName].some(function(configKey) {
                if (merged[configKey] !== undefined && merged[configKey] !== null && merged[configKey] !== '') {
                    value = merged[configKey];
                    return true;
                }

                return false;
            });

            if (value !== null) {
                form.find('[name="' + fieldName + '"]').val(value);
            }
        });
    }

    $('#form-register').on('change', 'select[name="line_profile"], select[name="service_profile"]', applyZteProfileDefaults);

    // Register ONU - Submit
    $('#form-register').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Registering...');
        
        $.ajax({
            url: '{{ route("admin.onus.register") }}',
            method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) {
                Swal.fire('Berhasil', res.message || 'ONU berhasil didaftarkan', 'success')
                    .then(() => location.reload());
            },
            error: function(xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal mendaftarkan ONU', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-plus mr-1"></i>Register ONU');
            }
        });
    });

    // Reboot ONU
    $(document).on('click', '.btn-reboot-onu', function() {
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
    $(document).on('click', '.btn-unregister-onu', function() {
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
                            .then(() => location.reload());
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal menghapus ONU', 'error');
                    }
                });
            }
        });
    });

    // Load traffic stats - optimized for partial update
    var trafficInitialized = false;
    var oltBrand = '{{ $olt->brand }}';
    
    function loadTrafficStats(isRefresh = false, forceRefresh = false) {
        var oltId = '{{ $olt->id }}';
        var url = '/admin/olts/' + oltId + '/traffic-stats';
        if (forceRefresh) {
            url += '?refresh=1';
        }
        
        // Only show loading on first load, not on refresh
        if (!trafficInitialized) {
            $('#pon-loading, #uplink-loading').show();
            $('#table-pon-traffic, #table-uplink-traffic').hide();
        }
        $('#pon-error, #uplink-error').hide();
        
        $.get(url)
            .done(function(res) {
                if (res.success && res.data) {
                    var data = res.data;
                    
                    // Build optical data map by port index for easy lookup
                    var opticalMap = {};
                    if (data.optical_power && data.optical_power.pon_ports) {
                        data.optical_power.pon_ports.forEach(function(opt) {
                            opticalMap[opt.port] = opt;
                        });
                    }
                    
                    // Render PON ports with optical data
                    if (data.pon_ports && data.pon_ports.ports && data.pon_ports.ports.length > 0) {
                        data.pon_ports.ports.forEach(function(port, idx) {
                            var rowId = 'pon-row-' + port.index;
                            var $row = $('#' + rowId);
                            
                            var statusBadge = port.status === 'up' 
                                ? '<span class="badge badge-success">UP</span>'
                                : '<span class="badge badge-danger">DOWN</span>';
                            
                            // Get optical data for this port
                            var optical = opticalMap[port.index] || {};
                            var txPower = optical.tx_power_formatted || '-';
                            var temp = optical.temperature_formatted || '-';
                            var txClass = '';
                            if (optical.signal_quality === 'excellent') txClass = 'text-success';
                            else if (optical.signal_quality === 'good') txClass = 'text-info';
                            else if (optical.signal_quality === 'acceptable') txClass = 'text-warning';
                            else if (optical.signal_quality === 'warning') txClass = 'text-danger';
                            
                            if ($row.length) {
                                // Update existing row (partial update - no flicker)
                                $row.find('.col-status').html(statusBadge);
                                $row.find('.col-download').text(port.in_bytes_formatted);
                                $row.find('.col-upload').text(port.out_bytes_formatted);
                                $row.find('.col-txpower').html('<span class="' + txClass + '">' + txPower + '</span>');
                                $row.find('.col-temp').text(temp);
                            } else {
                                // Create new row
                                var rowHtml = '<tr id="' + rowId + '">';
                                rowHtml += '<td><strong>' + port.name + '</strong></td>';
                                rowHtml += '<td class="col-status">' + statusBadge + '</td>';
                                rowHtml += '<td class="text-right text-info col-download">' + port.in_bytes_formatted + '</td>';
                                rowHtml += '<td class="text-right text-success col-upload">' + port.out_bytes_formatted + '</td>';
                                rowHtml += '<td class="text-right col-txpower"><span class="' + txClass + '">' + txPower + '</span></td>';
                                rowHtml += '<td class="text-right col-temp">' + temp + '</td>';
                                rowHtml += '</tr>';
                                $('#pon-traffic-body').append(rowHtml);
                            }
                        });
                        
                        $('#pon-total-in').text(data.pon_ports.in_formatted || '0 B');
                        $('#pon-total-out').text(data.pon_ports.out_formatted || '0 B');
                        
                        // Optical summary
                        if (data.optical_power && data.optical_power.summary) {
                            $('#pon-optical-avg').text('Avg TX: ' + data.optical_power.summary.overall_tx_power_formatted);
                        }
                        
                        $('#pon-loading').hide();
                        $('#table-pon-traffic').show();
                    } else if (!trafficInitialized) {
                        $('#pon-loading').hide();
                        $('#pon-error').html('<div class="text-center p-3 text-muted"><i class="fas fa-info-circle mr-2"></i>Traffic data tidak tersedia</div>').show();
                    }
                    
                    // Render Uplink ports
                    if (data.uplink_ports && data.uplink_ports.ports && data.uplink_ports.ports.length > 0) {
                        data.uplink_ports.ports.forEach(function(port) {
                            var rowId = 'uplink-row-' + port.index;
                            var $row = $('#' + rowId);
                            
                            var statusBadge = port.status === 'up' 
                                ? '<span class="badge badge-success">UP</span>'
                                : '<span class="badge badge-danger">DOWN</span>';
                            
                            if ($row.length) {
                                // Update existing row
                                $row.find('.col-status').html(statusBadge);
                                $row.find('.col-download').text(port.in_bytes_formatted);
                                $row.find('.col-upload').text(port.out_bytes_formatted);
                            } else {
                                // Create new row
                                var rowHtml = '<tr id="' + rowId + '">';
                                rowHtml += '<td><strong>' + port.name + '</strong></td>';
                                rowHtml += '<td class="col-status">' + statusBadge + '</td>';
                                rowHtml += '<td class="text-right text-info col-download">' + port.in_bytes_formatted + '</td>';
                                rowHtml += '<td class="text-right text-success col-upload">' + port.out_bytes_formatted + '</td>';
                                rowHtml += '</tr>';
                                $('#uplink-traffic-body').append(rowHtml);
                            }
                        });
                        
                        $('#uplink-total-in').text(data.uplink_ports.in_formatted || '0 B');
                        $('#uplink-total-out').text(data.uplink_ports.out_formatted || '0 B');
                        $('#uplink-loading').hide();
                        $('#table-uplink-traffic').show();
                    } else if (!trafficInitialized) {
                        $('#uplink-loading').hide();
                    }
                    
                    // Update timestamp
                    if (data.collected_at) {
                        var dt = new Date(data.collected_at);
                        var timeStr = dt.toLocaleTimeString();
                        $('#pon-timestamp').text(timeStr);
                        $('#traffic-timestamp').text(timeStr);
                    }
                    
                    trafficInitialized = true;
                } else {
                    throw new Error('Invalid response');
                }
            })
            .fail(function(xhr) {
                console.error('Traffic stats error:', xhr);
                if (!trafficInitialized) {
                    $('#pon-loading, #uplink-loading').hide();
                    $('#pon-error, #uplink-error').show();
                }
            });
    }
    
    // Load traffic stats on page load
    loadTrafficStats();
    
    // Refresh traffic button
    $('#btn-refresh-traffic').click(function() {
        var btn = $(this);
        btn.find('i').addClass('fa-spin');
        loadTrafficStats(true, false);
        setTimeout(function() {
            btn.find('i').removeClass('fa-spin');
        }, 1000);
    });
    
    // Force refresh button (bypass cache)
    $('#btn-force-refresh').click(function() {
        var btn = $(this);
        btn.find('i').addClass('fa-spin');
        loadTrafficStats(true, true);
        setTimeout(function() {
            btn.find('i').removeClass('fa-spin');
        }, 2000);
    });
    
    // Auto refresh - shorter interval for SNMP (real-time), longer for telnet
    var refreshInterval = (oltBrand === 'hioso') ? 15000 : 10000; // 15s for telnet, 10s for SNMP
    setInterval(function() {
        loadTrafficStats(true);
    }, refreshInterval);
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
@endpush
