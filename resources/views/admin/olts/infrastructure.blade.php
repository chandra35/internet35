@extends('layouts.admin')

@section('title', 'Infrastruktur OLT - ' . $olt->name)

@section('page-title', 'Infrastruktur OLT: ' . $olt->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.olts.index') }}">OLT</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.olts.show', $olt) }}">{{ $olt->name }}</a></li>
    <li class="breadcrumb-item active">Infrastruktur</li>
@endsection

@push('css')
<style>
    .shelf-slot {
        border: 2px solid #dee2e6;
        border-radius: 4px;
        text-align: center;
        padding: 4px 2px;
        min-height: 78px;
        transition: all 0.2s;
        cursor: default;
        min-width: 72px;
    }
    .shelf-slot.active-gpon { border-color: #28a745; background: rgba(40,167,69,0.05); }
    .shelf-slot.active-mgmt { border-color: #ffc107; background: rgba(255,193,7,0.05); }
    .shelf-slot.active-management { border-color: #ffc107; background: rgba(255,193,7,0.05); }
    .shelf-slot.active-power { border-color: #dc3545; background: rgba(220,53,69,0.05); }
    .shelf-slot.active-other { border-color: #17a2b8; background: rgba(23,162,184,0.05); }
    .shelf-slot.empty-slot { opacity: 0.25; }
    .shelf-slot.standby { border-style: dashed; opacity: 0.5; }

    .pon-port-cell {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        text-align: center;
        border-radius: 4px;
        margin: 2px;
        font-size: 11px;
        font-weight: 600;
        cursor: default;
        position: relative;
        flex-direction: column;
        line-height: 1.2;
    }
    .pon-port-cell .port-num { font-size: 13px; font-weight: 700; }
    .pon-port-cell .port-count { font-size: 8px; opacity: 0.9; }
    .pon-port-cell.has-onu { background: #28a745; color: #fff; }
    .pon-port-cell.empty-port { background: #e9ecef; color: #999; }
    .pon-port-cell.some-offline { background: #ffc107; color: #333; }
    .pon-port-cell.all-offline { background: #dc3545; color: #fff; }

    .uplink-vlan-tag {
        display: inline-block;
        padding: 1px 5px;
        margin: 1px;
        border-radius: 3px;
        font-size: 10px;
        background: #e9ecef;
        color: #495057;
    }

    .vlan-svc-badge {
        min-width: 28px;
        display: inline-block;
        text-align: center;
    }
</style>
@endpush

@section('content')
<!-- Progress Modal -->
<div class="modal fade" id="modal-progress" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h5 class="modal-title text-white">
                    <i class="fas fa-cog fa-spin mr-2" id="progress-spinner"></i>
                    <span id="progress-title">Sync Infrastruktur dari OLT...</span>
                </h5>
            </div>
            <div class="modal-body">
                <div class="progress mb-3" style="height: 28px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-dark"
                         role="progressbar" id="progress-bar" style="width: 0%">0%</div>
                </div>
                <div class="row text-center mb-3">
                    <div class="col-3">
                        <div class="p-2 rounded" id="step-cards" style="background: #f4f6f9;">
                            <i class="fas fa-microchip fa-lg mb-1 text-muted" id="step-cards-icon"></i>
                            <div class="font-weight-bold" style="font-size: 12px;">Kartu</div>
                            <small class="text-muted" id="step-cards-status">Menunggu...</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2 rounded" id="step-vlans" style="background: #f4f6f9;">
                            <i class="fas fa-tags fa-lg mb-1 text-muted" id="step-vlans-icon"></i>
                            <div class="font-weight-bold" style="font-size: 12px;">VLAN</div>
                            <small class="text-muted" id="step-vlans-status">Menunggu...</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2 rounded" id="step-uplinks" style="background: #f4f6f9;">
                            <i class="fas fa-arrow-up fa-lg mb-1 text-muted" id="step-uplinks-icon"></i>
                            <div class="font-weight-bold" style="font-size: 12px;">Uplink</div>
                            <small class="text-muted" id="step-uplinks-status">Menunggu...</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2 rounded" id="step-pon" style="background: #f4f6f9;">
                            <i class="fas fa-network-wired fa-lg mb-1 text-muted" id="step-pon-icon"></i>
                            <div class="font-weight-bold" style="font-size: 12px;">PON Port</div>
                            <small class="text-muted" id="step-pon-status">Menunggu...</small>
                        </div>
                    </div>
                </div>
                <div class="card card-outline card-secondary mb-0">
                    <div class="card-header py-1">
                        <h6 class="card-title mb-0"><i class="fas fa-terminal mr-1"></i>Log</h6>
                    </div>
                    <div class="card-body p-2" id="progress-logs"
                         style="max-height: 180px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px; background: #1e1e1e; color: #d4d4d4; border-radius: 0 0 4px 4px;">
                    </div>
                </div>
                <div id="sync-results" class="mt-3" style="display: none;">
                    <div class="alert mb-0" id="sync-results-alert">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-2x mr-3" id="sync-results-icon"></i>
                            <div>
                                <strong id="sync-results-title"></strong>
                                <div class="text-sm" id="sync-results-message"></div>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row text-center">
                            <div class="col-3">
                                <div class="h5 mb-0" id="result-cards-count">0</div>
                                <small>Kartu</small>
                            </div>
                            <div class="col-3">
                                <div class="h5 mb-0" id="result-vlans-count">0</div>
                                <small>VLAN</small>
                            </div>
                            <div class="col-3">
                                <div class="h5 mb-0" id="result-uplinks-count">0</div>
                                <small>Uplink</small>
                            </div>
                            <div class="col-3">
                                <div class="h5 mb-0" id="result-pon-count">0</div>
                                <small>PON Port</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="progress-footer" style="display: none;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-dark" onclick="location.reload()">
                    <i class="fas fa-sync mr-1"></i>Refresh Halaman
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit VLAN Type Modal -->
<div class="modal fade" id="modal-edit-vlan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white"><i class="fas fa-tag mr-2"></i>Klasifikasi VLAN</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-vlan-id">
                <div class="form-group">
                    <label>VLAN ID</label>
                    <input type="text" class="form-control" id="edit-vlan-display" readonly>
                </div>
                <div class="form-group">
                    <label>Tipe <span class="text-danger">*</span></label>
                    <select class="form-control" id="edit-vlan-type">
                        <option value="service">Service (Internet/PPPoE/IPoE)</option>
                        <option value="management">Management (TR069/CWMP)</option>
                        <option value="voip">VoIP</option>
                        <option value="iptv">IPTV</option>
                        <option value="infra">Infrastructure</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <input type="text" class="form-control" id="edit-vlan-description" placeholder="Opsional">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-info" id="btn-save-vlan-type">
                    <i class="fas fa-save mr-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Header -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-dark card-outline mb-0">
            <div class="card-body py-2 d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <h5 class="mb-0">
                        <i class="fas fa-server mr-2"></i>{{ $olt->name }}
                        <small class="text-muted ml-2">{{ $olt->ip_address }} &middot; {{ $olt->brand }} {{ $olt->model }}</small>
                    </h5>
                </div>
                <div class="mt-1 mt-md-0">
                    @php
                        $totalOnu = $olt->cards->where('role', 'gpon')->sum(fn($c) => $c->ponPorts->sum('registered_onu'));
                        $onlineOnu = $olt->cards->where('role', 'gpon')->sum(fn($c) => $c->ponPorts->sum('online_onu'));
                        $activePon = $olt->cards->where('role', 'gpon')->sum(fn($c) => $c->ponPorts->where('registered_onu', '>', 0)->count());
                        $totalPon = $olt->cards->where('role', 'gpon')->sum('port_count');
                    @endphp
                    @if($totalOnu > 0)
                        <span class="badge badge-success mr-1 py-1 px-2"><i class="fas fa-users mr-1"></i>{{ $onlineOnu }}/{{ $totalOnu }} ONU Online</span>
                        <span class="badge badge-primary mr-1 py-1 px-2"><i class="fas fa-network-wired mr-1"></i>{{ $activePon }}/{{ $totalPon }} PON Aktif</span>
                    @endif
                    <a href="{{ route('admin.olts.show', $olt) }}" class="btn btn-outline-primary btn-sm mr-1">
                        <i class="fas fa-arrow-left mr-1"></i>Detail OLT
                    </a>
                    <button type="button" class="btn btn-dark btn-sm" id="btn-sync-infra">
                        <i class="fas fa-sync mr-1"></i>Sync dari OLT
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Shelf Visual + Card Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-microchip mr-2"></i>Kartu & Slot</h3>
                <div class="card-tools">
                    @if($olt->cards->where('last_sync_at', '!=', null)->first())
                        <span class="text-muted text-sm">
                            <i class="fas fa-clock mr-1"></i>Sync: {{ $olt->cards->first()->last_sync_at?->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($olt->cards->isEmpty())
                    <div class="text-center py-4">
                        <i class="fas fa-server fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada data kartu. Klik <strong>"Sync dari OLT"</strong> untuk mengambil data.</p>
                    </div>
                @else
                    <!-- Shelf Visual -->
                    <div class="mb-3 p-3 bg-dark rounded">
                        <div class="d-flex align-items-center mb-2">
                            <span class="text-white font-weight-bold"><i class="fas fa-th mr-1"></i>RACK 1 / SHELF 1</span>
                            <span class="badge badge-light ml-auto">{{ $olt->cards->count() }} slot terisi</span>
                        </div>
                        <div class="d-flex flex-wrap justify-content-start" style="gap: 6px;">
                            @for($slot = 1; $slot <= 20; $slot++)
                                @php $card = $olt->cards->firstWhere('slot', $slot); @endphp
                                <div class="shelf-slot {{ $card ? ($card->status === 'standby' ? 'standby' : 'active-' . $card->role) : 'empty-slot' }}"
                                     @if($card) title="{{ $card->display_name }} — {{ $card->port_count }} ports — {{ $card->status }}" @endif>
                                    <small class="d-block text-muted" style="font-size: 10px;">Slot {{ $slot }}</small>
                                    @if($card)
                                        <strong class="d-block" style="font-size: 11px;">{{ $card->real_type ?: $card->configured_type }}</strong>
                                        <small class="d-block text-muted" style="font-size: 10px;">{{ $card->port_count }}P</small>
                                        @if($card->role === 'gpon')
                                            <span class="badge badge-primary" style="font-size: 9px;">GPON</span>
                                        @elseif($card->role === 'management')
                                            <span class="badge badge-warning" style="font-size: 9px;">MGMT</span>
                                        @elseif($card->role === 'power')
                                            <span class="badge badge-danger" style="font-size: 9px;">PWR</span>
                                        @else
                                            <span class="badge badge-secondary" style="font-size: 9px;">{{ strtoupper($card->role) }}</span>
                                        @endif
                                    @else
                                        <span class="d-block text-muted" style="font-size: 10px; margin-top: 14px;">—</span>
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </div>

                    <!-- Card Details Table -->
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Slot</th>
                                    <th>Tipe Konfigurasi</th>
                                    <th>Tipe Aktual</th>
                                    <th>Port</th>
                                    <th>Role</th>
                                    <th>HW Ver</th>
                                    <th>SW Ver</th>
                                    <th>Status</th>
                                    <th class="text-center">ONU (Online/Total)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($olt->cards as $card)
                                <tr>
                                    <td><strong>{{ $card->slot }}</strong></td>
                                    <td>{{ $card->configured_type ?? '-' }}</td>
                                    <td><strong>{{ $card->real_type ?? '-' }}</strong></td>
                                    <td>{{ $card->port_count }}</td>
                                    <td>{!! $card->role_badge !!}</td>
                                    <td><small>{{ $card->hardware_version ?? '-' }}</small></td>
                                    <td><small>{{ $card->software_version ?? '-' }}</small></td>
                                    <td>{!! $card->status_badge !!}</td>
                                    <td class="text-center">
                                        @if($card->role === 'gpon' && $card->ponPorts->isNotEmpty())
                                            @php
                                                $regOnu = $card->ponPorts->sum('registered_onu');
                                                $onOnu = $card->ponPorts->sum('online_onu');
                                            @endphp
                                            <span class="text-success font-weight-bold">{{ $onOnu }}</span>
                                            <span class="text-muted">/</span>
                                            <span>{{ $regOnu }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Per-Card PON Port Detail (Collapsible) -->
@foreach($olt->cards->where('role', 'gpon') as $card)
@php
    $cardReg = $card->ponPorts->sum('registered_onu');
    $cardOn = $card->ponPorts->sum('online_onu');
    $cardOff = $cardReg - $cardOn;
    $cardActivePorts = $card->ponPorts->where('registered_onu', '>', 0)->count();
@endphp
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary collapsed-card">
            <div class="card-header" data-card-widget="collapse" style="cursor: pointer;">
                <h3 class="card-title">
                    <i class="fas fa-network-wired mr-2"></i>
                    <strong>Slot {{ $card->slot }}</strong> — {{ $card->real_type ?: $card->configured_type }}
                    <small class="ml-2 text-muted">{{ $card->port_count }} PON Ports</small>
                </h3>
                <div class="card-tools">
                    <span class="badge badge-success mr-1">{{ $cardOn }} online</span>
                    @if($cardOff > 0)
                        <span class="badge badge-danger mr-1">{{ $cardOff }} offline</span>
                    @endif
                    <span class="badge badge-primary mr-1">{{ $cardActivePorts }}/{{ $card->port_count }} port aktif</span>
                    <button type="button" class="btn btn-tool"><i class="fas fa-plus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <!-- PON Port Visual Grid -->
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-square mr-1" style="color: #28a745;"></i>Semua Online
                        <i class="fas fa-square ml-2 mr-1" style="color: #ffc107;"></i>Ada Offline
                        <i class="fas fa-square ml-2 mr-1" style="color: #dc3545;"></i>Semua Offline
                        <i class="fas fa-square ml-2 mr-1" style="color: #e9ecef;"></i>Kosong
                    </small>
                    <div class="d-flex flex-wrap">
                        @for($p = 1; $p <= $card->port_count; $p++)
                            @php
                                $pp = $card->ponPorts->firstWhere('port', $p);
                                $reg = $pp->registered_onu ?? 0;
                                $on = $pp->online_onu ?? 0;
                                $off = $reg - $on;
                                if ($reg === 0) $cls = 'empty-port';
                                elseif ($off === 0) $cls = 'has-onu';
                                elseif ($on === 0) $cls = 'all-offline';
                                else $cls = 'some-offline';
                            @endphp
                            <div class="pon-port-cell {{ $cls }}" title="Port {{ $p }}: {{ $on }}/{{ $reg }} online">
                                <span class="port-num">{{ $p }}</span>
                                @if($reg > 0)
                                    <span class="port-count">{{ $on }}/{{ $reg }}</span>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>

                <!-- PON Port Detail Table -->
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 12px;">
                        <thead class="thead-light">
                            <tr>
                                <th width="80">Port</th>
                                <th width="55" class="text-center">Status</th>
                                <th width="55" class="text-center">Admin</th>
                                <th width="80" class="text-center">Terdaftar</th>
                                <th width="70" class="text-center">Online</th>
                                <th width="70" class="text-center">Offline</th>
                                <th>Kapasitas</th>
                                <th width="90" class="text-center">TX Power</th>
                                <th width="90" class="text-center">RX Min</th>
                                <th width="90" class="text-center">RX Max</th>
                                <th width="90" class="text-center">RX Avg</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($p = 1; $p <= $card->port_count; $p++)
                                @php
                                    $pp = $card->ponPorts->firstWhere('port', $p);
                                    $reg = $pp->registered_onu ?? 0;
                                    $on = $pp->online_onu ?? 0;
                                    $off = $reg - $on;
                                    $max = $pp->max_onu ?? 128;
                                    $pct = $max > 0 ? round(($reg / $max) * 100) : 0;
                                @endphp
                                <tr class="{{ $reg === 0 ? 'text-muted' : '' }}">
                                    <td><strong>1/{{ $card->slot }}/{{ $p }}</strong></td>
                                    <td class="text-center">
                                        @if($pp && $pp->status === 'up')
                                            <span class="badge badge-success" style="font-size: 10px;">UP</span>
                                        @elseif($pp)
                                            <span class="badge badge-secondary" style="font-size: 10px;">DOWN</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($pp && $pp->admin_status === 'enabled')
                                            <span class="text-success"><i class="fas fa-check"></i></span>
                                        @elseif($pp && $pp->admin_status === 'disabled')
                                            <span class="text-danger"><i class="fas fa-times"></i></span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center"><strong>{{ $reg }}</strong></td>
                                    <td class="text-center">
                                        @if($on > 0)
                                            <span class="text-success font-weight-bold">{{ $on }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($off > 0)
                                            <span class="text-danger font-weight-bold">{{ $off }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 mr-2" style="height: 14px;">
                                                <div class="progress-bar {{ $pct > 80 ? 'bg-danger' : ($pct > 50 ? 'bg-warning' : 'bg-success') }}"
                                                     style="width: {{ $pct }}%"></div>
                                            </div>
                                            <small class="text-nowrap" style="min-width: 45px;">{{ $reg }}/{{ $max }}</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($pp && $pp->tx_power)
                                            <small>{{ number_format($pp->tx_power, 2) }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($pp && $pp->rx_power_min)
                                            <small>{{ number_format($pp->rx_power_min, 2) }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($pp && $pp->rx_power_max)
                                            <small>{{ number_format($pp->rx_power_max, 2) }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($pp && $pp->rx_power_avg)
                                            <small>{{ number_format($pp->rx_power_avg, 2) }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td colspan="3" class="text-right">Total:</td>
                                <td class="text-center">{{ $card->ponPorts->sum('registered_onu') }}</td>
                                <td class="text-center text-success">{{ $card->ponPorts->sum('online_onu') }}</td>
                                <td class="text-center text-danger">{{ $card->ponPorts->sum('registered_onu') - $card->ponPorts->sum('online_onu') }}</td>
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach

<!-- VLANs + Uplinks Row -->
<div class="row">
    <!-- VLAN Database -->
    <div class="col-lg-7">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tags mr-2"></i>VLAN Database</h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ $olt->vlans->count() }} VLANs</span>
                    @if($olt->vlans->sum('service_port_count') > 0)
                        <span class="badge badge-success ml-1">{{ $olt->vlans->sum('service_port_count') }} service-port</span>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                @if($olt->vlans->isEmpty())
                    <div class="text-center py-4">
                        <i class="fas fa-tags fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Belum ada data VLAN.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="70">VLAN ID</th>
                                    <th>Nama</th>
                                    <th>Tipe</th>
                                    <th width="70" class="text-center" title="Jumlah service-port (ONU) menggunakan VLAN ini">
                                        <i class="fas fa-users"></i> Svc
                                    </th>
                                    <th>Uplink Ports</th>
                                    <th>Keterangan</th>
                                    <th width="45">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($olt->vlans as $vlan)
                                <tr>
                                    <td><strong>{{ $vlan->vlan_id }}</strong></td>
                                    <td>{{ $vlan->name }}</td>
                                    <td>{!! $vlan->type_badge !!}</td>
                                    <td class="text-center">
                                        @if($vlan->service_port_count > 0)
                                            <span class="badge badge-success vlan-svc-badge">{{ $vlan->service_port_count }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($vlan->uplink_ports)
                                            @foreach($vlan->uplink_ports as $up)
                                                <span class="uplink-vlan-tag">{{ $up }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><small>{{ $vlan->description ?? '-' }}</small></td>
                                    <td>
                                        <button class="btn btn-xs btn-outline-info btn-edit-vlan"
                                                data-id="{{ $vlan->id }}"
                                                data-vlan-id="{{ $vlan->vlan_id }}"
                                                data-name="{{ $vlan->name }}"
                                                data-type="{{ $vlan->type }}"
                                                data-description="{{ $vlan->description }}"
                                                title="Ubah Tipe">
                                            <i class="fas fa-tag"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Uplink Ports -->
    <div class="col-lg-5">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-arrow-up mr-2"></i>Uplink Ports</h3>
                <div class="card-tools">
                    <span class="badge badge-success">{{ $olt->uplinks->where('status', 'up')->count() }}/{{ $olt->uplinks->count() }} Up</span>
                </div>
            </div>
            <div class="card-body p-0">
                @if($olt->uplinks->isEmpty())
                    <div class="text-center py-4">
                        <i class="fas fa-arrow-up fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Belum ada data uplink.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Interface</th>
                                    <th width="50" class="text-center">Status</th>
                                    <th>Mode</th>
                                    <th>Tagged VLANs</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($olt->uplinks as $uplink)
                                <tr>
                                    <td>
                                        <strong>{{ $uplink->interface_name }}</strong>
                                        @if($uplink->interface_type === 'xgei')
                                            <span class="badge badge-info" style="font-size: 9px;">10G</span>
                                        @endif
                                        <br>
                                        <small class="text-muted">Slot {{ $uplink->slot }}, Port {{ $uplink->port }}</small>
                                    </td>
                                    <td class="text-center">
                                        {!! $uplink->status_badge !!}
                                        @if($uplink->admin_status === 'disabled')
                                            <br><small class="text-danger">disabled</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($uplink->switchport_mode === 'trunk')
                                            <span class="badge badge-dark" style="font-size: 10px;">TRUNK</span>
                                        @elseif($uplink->switchport_mode)
                                            <small>{{ strtoupper($uplink->switchport_mode) }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                        @if($uplink->native_vlan)
                                            <br><small class="text-muted">Native: {{ $uplink->native_vlan }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($uplink->tagged_vlans)
                                            @foreach($uplink->tagged_vlans as $vid)
                                                <span class="uplink-vlan-tag">{{ $vid }}</span>
                                            @endforeach
                                            <br><small class="text-muted">{{ count($uplink->tagged_vlans) }} VLANs</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function() {
    // =========================================================================
    // Sync Infrastructure via SSE
    // =========================================================================
    $('#btn-sync-infra').click(function() {
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Syncing...');

        $('#progress-logs').html('');
        $('#progress-bar').css('width', '0%').text('0%')
            .removeClass('bg-success bg-danger bg-warning').addClass('bg-dark progress-bar-animated');
        $('#progress-footer').hide();
        $('#sync-results').hide();
        $('#progress-spinner').addClass('fa-spin');
        $('#progress-title').text('Sync Infrastruktur dari OLT...');

        ['cards', 'vlans', 'uplinks', 'pon'].forEach(function(step) {
            $('#step-' + step).css('background', '#f4f6f9');
            $('#step-' + step + '-icon').removeClass('text-success text-primary text-danger').addClass('text-muted');
            $('#step-' + step + '-status').text('Menunggu...').removeClass('text-success text-primary text-danger').addClass('text-muted');
        });

        $('#modal-progress').modal('show');

        let url = '{{ route("admin.olts.sync-infrastructure-stream", $olt) }}';
        let eventSource = new EventSource(url);

        function appendLog(message, status) {
            let color = '#d4d4d4', icon = '&#9679;';
            if (status === 'success') { color = '#6bcf7f'; icon = '&#10003;'; }
            else if (status === 'warning') { color = '#f0ad4e'; icon = '&#9888;'; }
            else if (status === 'error') { color = '#e74c3c'; icon = '&#10007;'; }
            else if (status === 'info') { color = '#87ceeb'; icon = '&#8226;'; }

            let time = new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
            $('#progress-logs').append(
                '<div style="color:' + color + ';">' + icon + ' <span style="color:#888;">[' + time + ']</span> ' + message + '</div>'
            );
            let el = $('#progress-logs')[0];
            el.scrollTop = el.scrollHeight;
        }

        function setStepActive(step) {
            $('#step-' + step).css('background', '#e3f2fd');
            $('#step-' + step + '-icon').removeClass('text-muted').addClass('text-primary');
            $('#step-' + step + '-status').text('Memproses...').removeClass('text-muted').addClass('text-primary');
        }

        function setStepDone(step, text) {
            $('#step-' + step).css('background', '#e8f5e9');
            $('#step-' + step + '-icon').removeClass('text-primary text-muted').addClass('text-success');
            $('#step-' + step + '-status').text(text).removeClass('text-primary text-muted').addClass('text-success');
        }

        function setStepError(step) {
            $('#step-' + step).css('background', '#fbe9e7');
            $('#step-' + step + '-icon').removeClass('text-primary text-muted').addClass('text-danger');
            $('#step-' + step + '-status').text('Error').removeClass('text-primary text-muted').addClass('text-danger');
        }

        eventSource.onmessage = function(event) {
            let data = JSON.parse(event.data);

            if (data.type === 'progress') {
                $('#progress-bar').css('width', data.percent + '%').text(data.percent + '%');

                let msg = data.message.toLowerCase();

                if (msg.includes('membaca data kartu') || msg.includes('show card')) setStepActive('cards');
                if (msg.includes('slot tersinkronisasi')) {
                    let m = data.message.match(/(\d+)\s+slot/i);
                    if (m) setStepDone('cards', m[1] + ' slot');
                }
                if (msg.includes('membaca database vlan') || msg.includes('service-port')) setStepActive('vlans');
                if (msg.includes('vlan') && msg.includes('tersinkronisasi') && !msg.includes('selesai')) {
                    let m = data.message.match(/(\d+)\s+vlan/i);
                    let s = data.message.match(/(\d+)\s+service-port/i);
                    let txt = m ? m[1] + ' VLAN' : '';
                    if (s) txt += (txt ? ', ' : '') + s[1] + ' svc';
                    if (txt) setStepDone('vlans', txt);
                }
                if (msg.includes('membaca uplink') || msg.includes('interface brief')) setStepActive('uplinks');
                if (msg.includes('uplink') && msg.includes('port tersinkronisasi')) {
                    let m = data.message.match(/(\d+)\s+port/i);
                    if (m) setStepDone('uplinks', m[1] + ' port');
                }
                if (msg.includes('membaca status onu') || (msg.includes('pon') && msg.includes('port'))) {
                    if (msg.includes('membaca')) setStepActive('pon');
                }
                if (msg.includes('pon port') && msg.includes('tersinkronisasi')) {
                    let m = data.message.match(/(\d+)\s+port/i);
                    if (m) setStepDone('pon', m[1] + ' port');
                }

                appendLog(data.message, data.status);
            }

            if (data.type === 'complete') {
                eventSource.close();

                $('#progress-spinner').removeClass('fa-spin');
                $('#progress-bar').removeClass('progress-bar-animated');
                $('#sync-results').show();

                if (data.success) {
                    $('#progress-bar').removeClass('bg-dark').addClass('bg-success');
                    $('#progress-title').text('Sync Berhasil!');
                    $('#sync-results-alert').removeClass('alert-danger').addClass('alert-success');
                    $('#sync-results-icon').removeClass('fa-times-circle text-danger').addClass('fa-check-circle text-success');
                    $('#sync-results-title').text('Sync Infrastruktur Berhasil');
                    $('#sync-results-message').text(data.message);
                } else {
                    $('#progress-bar').removeClass('bg-dark').addClass('bg-danger');
                    $('#progress-title').text('Sync Gagal');
                    $('#sync-results-alert').removeClass('alert-success').addClass('alert-danger');
                    $('#sync-results-icon').removeClass('fa-check-circle text-success').addClass('fa-times-circle text-danger');
                    $('#sync-results-title').text('Sync Gagal');
                    $('#sync-results-message').text(data.message);
                    ['cards', 'vlans', 'uplinks', 'pon'].forEach(setStepError);
                }

                $('#result-cards-count').text(data.cards_synced || 0);
                $('#result-vlans-count').text(data.vlans_synced || 0);
                $('#result-uplinks-count').text(data.uplinks_synced || 0);
                $('#result-pon-count').text(data.pon_ports_synced || 0);

                $('#progress-footer').show();
                btn.prop('disabled', false).html('<i class="fas fa-sync mr-1"></i>Sync dari OLT');

                appendLog('--- Selesai ---', data.success ? 'success' : 'error');
            }
        };

        eventSource.onerror = function() {
            eventSource.close();
            $('#progress-spinner').removeClass('fa-spin');
            $('#progress-bar').removeClass('progress-bar-animated bg-dark').addClass('bg-danger').css('width', '100%').text('Error');
            $('#progress-footer').show();
            btn.prop('disabled', false).html('<i class="fas fa-sync mr-1"></i>Sync dari OLT');
            appendLog('Koneksi SSE terputus. Periksa koneksi ke OLT.', 'error');

            $('#sync-results').show();
            $('#sync-results-alert').removeClass('alert-success').addClass('alert-danger');
            $('#sync-results-icon').addClass('fa-exclamation-triangle text-danger');
            $('#sync-results-title').text('Koneksi Terputus');
            $('#sync-results-message').text('Tidak dapat terhubung ke server.');
        };
    });

    // =========================================================================
    // Edit VLAN Type
    // =========================================================================
    $(document).on('click', '.btn-edit-vlan', function() {
        let btn = $(this);
        $('#edit-vlan-id').val(btn.data('id'));
        $('#edit-vlan-display').val(btn.data('vlan-id') + ' - ' + btn.data('name'));
        $('#edit-vlan-type').val(btn.data('type'));
        $('#edit-vlan-description').val(btn.data('description') || '');
        $('#modal-edit-vlan').modal('show');
    });

    $('#btn-save-vlan-type').click(function() {
        let btn = $(this);
        let vlanId = $('#edit-vlan-id').val();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

        $.ajax({
            url: '{{ route("admin.olts.infrastructure.vlans.update-type", [$olt, "__VLAN__"]) }}'.replace('__VLAN__', vlanId),
            method: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                type: $('#edit-vlan-type').val(),
                description: $('#edit-vlan-description').val(),
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    location.reload();
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal menyimpan');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan');
            }
        });
    });
});
</script>
@endpush
