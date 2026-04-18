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
    /* ── Shelf Slot Visual ── */
    .shelf-container { background: linear-gradient(135deg, #1a1e2e 0%, #2d3250 100%); border-radius: 10px; padding: 20px; }
    .shelf-slot {
        border: 2px solid rgba(255,255,255,0.08);
        border-radius: 8px;
        text-align: center;
        padding: 8px 4px;
        min-height: 94px;
        min-width: 74px;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        background: rgba(255,255,255,0.03);
    }
    .shelf-slot:hover:not(.empty-slot) { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.4); }
    .shelf-slot.active-gpon { border-color: #28a745; background: linear-gradient(180deg, rgba(40,167,69,0.15) 0%, rgba(40,167,69,0.05) 100%); }
    .shelf-slot.active-epon { border-color: #17a2b8; background: linear-gradient(180deg, rgba(23,162,184,0.15) 0%, rgba(23,162,184,0.05) 100%); }
    .shelf-slot.active-mgmt,
    .shelf-slot.active-management { border-color: #ffc107; background: linear-gradient(180deg, rgba(255,193,7,0.15) 0%, rgba(255,193,7,0.05) 100%); }
    .shelf-slot.active-power { border-color: #dc3545; background: linear-gradient(180deg, rgba(220,53,69,0.15) 0%, rgba(220,53,69,0.05) 100%); }
    .shelf-slot.active-fan { border-color: #6f42c1; background: linear-gradient(180deg, rgba(111,66,193,0.15) 0%, rgba(111,66,193,0.05) 100%); }
    .shelf-slot.active-other { border-color: #6c757d; background: linear-gradient(180deg, rgba(108,117,125,0.15) 0%, rgba(108,117,125,0.05) 100%); }
    .shelf-slot.empty-slot { opacity: 0.15; cursor: default; }
    .shelf-slot.standby { border-style: dashed; opacity: 0.35; }
    .shelf-slot .slot-label { font-size: 9px; color: rgba(255,255,255,0.35); letter-spacing: 0.5px; }
    .shelf-slot .slot-type { font-size: 12px; font-weight: 700; color: #fff; margin: 2px 0; }
    .shelf-slot .slot-ports { font-size: 9px; color: rgba(255,255,255,0.45); }
    .shelf-slot .slot-badge { font-size: 8px; padding: 1px 6px; }
    .shelf-slot.selected { box-shadow: 0 0 0 3px #007bff, 0 6px 20px rgba(0,123,255,0.4); transform: translateY(-3px); }

    /* ── PON Port Cell ── */
    .pon-port-cell {
        display: inline-flex; align-items: center; justify-content: center;
        width: 50px; height: 50px; text-align: center; border-radius: 8px;
        margin: 3px; font-weight: 600; cursor: default; position: relative;
        flex-direction: column; line-height: 1.2; transition: all 0.2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .pon-port-cell .port-num { font-size: 14px; font-weight: 700; }
    .pon-port-cell .port-count { font-size: 8px; opacity: 0.9; }
    .pon-port-cell.has-onu { background: linear-gradient(135deg, #28a745, #20c997); color: #fff; }
    .pon-port-cell.empty-port { background: #f0f2f5; color: #adb5bd; }
    .pon-port-cell.some-offline { background: linear-gradient(135deg, #ffc107, #fd7e14); color: #333; }
    .pon-port-cell.all-offline { background: linear-gradient(135deg, #dc3545, #c82333); color: #fff; }

    /* ── Card Detail Panel ── */
    .card-detail-panel {
        border-left: 4px solid #007bff;
        background: #fff;
        border-radius: 0 10px 10px 0;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        transition: box-shadow 0.3s;
    }
    .card-detail-panel:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
    .card-detail-panel.role-gpon { border-left-color: #28a745; }
    .card-detail-panel.role-epon { border-left-color: #17a2b8; }
    .card-detail-panel.role-management { border-left-color: #ffc107; }
    .card-detail-panel.role-power { border-left-color: #dc3545; }
    .card-detail-panel.role-fan { border-left-color: #6f42c1; }

    .spec-grid { display: flex; flex-wrap: wrap; gap: 4px 16px; }
    .spec-item { font-size: 12px; }
    .spec-item .spec-label { color: #6c757d; }
    .spec-item .spec-value { font-weight: 600; color: #212529; }

    /* ── VLAN / Uplink Tags ── */
    .uplink-vlan-tag {
        display: inline-block; padding: 2px 7px; margin: 1px; border-radius: 4px;
        font-size: 10px; font-weight: 600; background: #e3f2fd; color: #1565c0;
    }
    .uplink-vlan-tag.untagged { background: #e8f5e9; color: #2e7d32; }
    .vlan-svc-badge { min-width: 28px; display: inline-block; text-align: center; }

    /* ── Port Membership Radio ── */
    .port-membership-row { transition: background 0.2s; }
    .port-membership-row:hover { background: #f0f7ff !important; }
    .port-radio-group { display: flex; gap: 0; }
    .port-radio-group label {
        flex: 1; text-align: center; padding: 6px 14px; margin: 0;
        font-size: 11px; font-weight: 600; cursor: pointer; border: 1px solid #dee2e6;
        transition: all 0.15s; background: #fff; color: #6c757d;
    }
    .port-radio-group label:first-child { border-radius: 5px 0 0 5px; }
    .port-radio-group label:last-child { border-radius: 0 5px 5px 0; }
    .port-radio-group label:not(:first-child) { border-left: 0; }
    .port-radio-group input[type="radio"] { display: none; }
    .port-radio-group label.active-tagged { background: #007bff; color: #fff; border-color: #007bff; }
    .port-radio-group label.active-untagged { background: #28a745; color: #fff; border-color: #28a745; }
    .port-radio-group label.active-none { background: #6c757d; color: #fff; border-color: #6c757d; }

    /* ── Stat Card Mini ── */
    .stat-mini {
        border-radius: 10px; padding: 10px 16px; text-align: center;
        transition: transform 0.2s; min-width: 70px;
    }
    .stat-mini:hover { transform: translateY(-2px); }
    .stat-mini .stat-value { font-size: 20px; font-weight: 700; line-height: 1; }
    .stat-mini .stat-value small { font-size: 12px; }
    .stat-mini .stat-label { font-size: 10px; color: #6c757d; margin-top: 2px; }
    .stat-mini.stat-success { background: rgba(40,167,69,0.08); }
    .stat-mini.stat-success .stat-value { color: #28a745; }
    .stat-mini.stat-primary { background: rgba(0,123,255,0.08); }
    .stat-mini.stat-primary .stat-value { color: #007bff; }
    .stat-mini.stat-info { background: rgba(23,162,184,0.08); }
    .stat-mini.stat-info .stat-value { color: #17a2b8; }
    .stat-mini.stat-warning { background: rgba(255,193,7,0.08); }
    .stat-mini.stat-warning .stat-value { color: #e0a800; }
    .stat-mini.stat-danger { background: rgba(220,53,69,0.08); }
    .stat-mini.stat-danger .stat-value { color: #dc3545; }

    /* ── Section Title ── */
    .section-title {
        font-size: 12px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.8px; color: #6c757d; margin-bottom: 10px;
        padding-bottom: 6px; border-bottom: 2px solid #f0f0f0;
    }
    .section-title i { margin-right: 6px; }
</style>
@endpush

@section('content')
@php
    $totalOnu = $olt->cards->where('role', 'gpon')->sum(fn($c) => $c->ponPorts->sum('registered_onu'));
    $onlineOnu = $olt->cards->where('role', 'gpon')->sum(fn($c) => $c->ponPorts->sum('online_onu'));
    $activePon = $olt->cards->where('role', 'gpon')->sum(fn($c) => $c->ponPorts->where('registered_onu', '>', 0)->count());
    $totalPon = $olt->cards->where('role', 'gpon')->sum('port_count');
@endphp

{{-- ═══════════════════════════════════════════════════ --}}
{{-- MODALS --}}
{{-- ═══════════════════════════════════════════════════ --}}

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
                    @foreach(['cards' => ['fa-microchip', 'Kartu'], 'vlans' => ['fa-tags', 'VLAN'], 'uplinks' => ['fa-arrow-up', 'Uplink'], 'pon' => ['fa-network-wired', 'PON Port']] as $stepKey => $stepInfo)
                    <div class="col-3">
                        <div class="p-2 rounded" id="step-{{ $stepKey }}" style="background: #f4f6f9;">
                            <i class="fas {{ $stepInfo[0] }} fa-lg mb-1 text-muted" id="step-{{ $stepKey }}-icon"></i>
                            <div class="font-weight-bold" style="font-size: 12px;">{{ $stepInfo[1] }}</div>
                            <small class="text-muted" id="step-{{ $stepKey }}-status">Menunggu...</small>
                        </div>
                    </div>
                    @endforeach
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
                            <div class="col-3"><div class="h5 mb-0" id="result-cards-count">0</div><small>Kartu</small></div>
                            <div class="col-3"><div class="h5 mb-0" id="result-vlans-count">0</div><small>VLAN</small></div>
                            <div class="col-3"><div class="h5 mb-0" id="result-uplinks-count">0</div><small>Uplink</small></div>
                            <div class="col-3"><div class="h5 mb-0" id="result-pon-count">0</div><small>PON Port</small></div>
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

<!-- Edit VLAN Modal -->
<div class="modal fade" id="modal-edit-vlan" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit VLAN <span id="edit-vlan-title-id" class="font-weight-bold"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-vlan-id">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-bold">VLAN ID</label>
                            <input type="text" class="form-control font-weight-bold text-center" id="edit-vlan-display" readonly style="font-size: 18px;">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-bold">Tipe <span class="text-danger">*</span></label>
                            <select class="form-control" id="edit-vlan-type">
                                <option value="service">Service (Internet)</option>
                                <option value="management">Management (TR069)</option>
                                <option value="voip">VoIP</option>
                                <option value="iptv">IPTV</option>
                                <option value="infra">Infrastructure</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Keterangan</label>
                            <input type="text" class="form-control" id="edit-vlan-description" placeholder="Deskripsi VLAN">
                        </div>
                    </div>
                </div>

                <!-- Port Membership -->
                <div class="section-title mt-1">
                    <i class="fas fa-exchange-alt"></i>Port Membership (Uplink)
                </div>
                @if($olt->uplinks->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th style="width: 35%">Interface</th>
                                <th class="text-center" style="width: 15%">Status</th>
                                <th class="text-center" style="width: 50%">Membership</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($olt->uplinks as $uplink)
                            @php $slug = Str::slug($uplink->interface_name); @endphp
                            <tr class="port-membership-row">
                                <td>
                                    <strong>{{ $uplink->interface_name }}</strong>
                                    @if($uplink->interface_type === 'xgei')
                                        <span class="badge badge-info ml-1" style="font-size: 9px;">10G</span>
                                    @endif
                                    @if($uplink->description)
                                        <br><small class="text-muted">{{ $uplink->description }}</small>
                                    @endif
                                </td>
                                <td class="text-center">{!! $uplink->status_badge !!}</td>
                                <td>
                                    <div class="port-radio-group">
                                        <label class="mb-0">
                                            <input type="radio" name="port_{{ $slug }}" value="tagged"
                                                   data-port="{{ $uplink->interface_name }}" class="port-radio">
                                            <span class="d-block"><i class="fas fa-tag mr-1"></i>Tagged</span>
                                        </label>
                                        <label class="mb-0">
                                            <input type="radio" name="port_{{ $slug }}" value="untagged"
                                                   data-port="{{ $uplink->interface_name }}" class="port-radio">
                                            <span class="d-block"><i class="fas fa-minus-circle mr-1"></i>Untagged</span>
                                        </label>
                                        <label class="mb-0">
                                            <input type="radio" name="port_{{ $slug }}" value="none"
                                                   data-port="{{ $uplink->interface_name }}" class="port-radio" checked>
                                            <span class="d-block"><i class="fas fa-times mr-1"></i>None</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-warning py-2 mb-0">
                    <i class="fas fa-exclamation-triangle mr-1"></i>Belum ada data uplink. Sync terlebih dahulu.
                </div>
                @endif
                <small class="form-text text-muted mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Push via <strong>{{ $olt->snmp_community_rw ? 'SNMP SET (Q-BRIDGE)' : 'Telnet CLI' }}</strong>.
                    Deskripsi via CLI.
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-info" id="btn-save-vlan">
                    <i class="fas fa-save mr-1"></i>Simpan ke OLT
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Configure Uplink Modal -->
<div class="modal fade" id="modal-configure-uplink" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white"><i class="fas fa-cog mr-2"></i>Konfigurasi Uplink</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cfg-uplink-id">
                <div class="form-group">
                    <label>Interface</label>
                    <input type="text" class="form-control font-weight-bold" id="cfg-uplink-display" readonly>
                </div>
                <div class="form-group">
                    <label>Tambah VLAN (Tagged)</label>
                    <input type="text" class="form-control" id="cfg-uplink-add-vlans" placeholder="Contoh: 100, 200, 300">
                    <small class="form-text text-muted">Pisahkan dengan koma</small>
                </div>
                <div class="form-group">
                    <label>Hapus VLAN (Tagged)</label>
                    <input type="text" class="form-control" id="cfg-uplink-remove-vlans" placeholder="Contoh: 100, 200">
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Native VLAN (PVID)</label>
                            <input type="number" class="form-control" id="cfg-uplink-pvid" min="1" max="4094">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Admin Status</label>
                            <select class="form-control" id="cfg-uplink-admin">
                                <option value="enabled">Enabled</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label>Deskripsi</label>
                    <input type="text" class="form-control" id="cfg-uplink-desc" placeholder="Opsional" maxlength="64">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btn-save-uplink-config">
                    <i class="fas fa-paper-plane mr-1"></i>Terapkan ke OLT
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create VLAN Modal -->
<div class="modal fade" id="modal-create-vlan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Buat VLAN Baru</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label>VLAN ID <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="create-vlan-id" min="2" max="4094" required>
                        </div>
                    </div>
                    <div class="col-8">
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" class="form-control" id="create-vlan-name" placeholder="Opsional">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Tipe</label>
                            <select class="form-control" id="create-vlan-type">
                                <option value="service">Service</option>
                                <option value="management">Management</option>
                                <option value="voip">VoIP</option>
                                <option value="iptv">IPTV</option>
                                <option value="infra">Infrastructure</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Keterangan</label>
                            <input type="text" class="form-control" id="create-vlan-desc" placeholder="Opsional">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-create-vlan">
                    <i class="fas fa-plus mr-1"></i>Buat di OLT
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete VLAN Modal -->
<div class="modal fade" id="modal-delete-vlan" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white"><i class="fas fa-trash mr-2"></i>Hapus VLAN</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <input type="hidden" id="delete-vlan-id">
                <p>Yakin hapus <strong>VLAN <span id="delete-vlan-display"></span></strong> dari OLT?</p>
                <p class="text-danger small" id="delete-vlan-warning"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btn-confirm-delete-vlan">
                    <i class="fas fa-trash mr-1"></i>Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reboot Card Modal -->
<div class="modal fade" id="modal-reboot-card" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white"><i class="fas fa-redo mr-2"></i>Reboot Card</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <input type="hidden" id="reboot-card-id">
                <p>Yakin reboot <strong>Card Slot <span id="reboot-card-display"></span></strong>?</p>
                <p class="text-danger small">Card akan restart dan ONU di slot ini terputus sementara.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btn-confirm-reboot-card">
                    <i class="fas fa-redo mr-1"></i>Reboot
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reboot PON ONUs Modal -->
<div class="modal fade" id="modal-reboot-pon-onus" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-redo mr-2"></i>Reboot ONUs</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <input type="hidden" id="reboot-pon-slot">
                <input type="hidden" id="reboot-pon-port">
                <p>Yakin reboot semua ONU di <strong>port <span id="reboot-pon-display"></span></strong>?</p>
                <p class="text-warning small">Semua ONU di port ini akan restart.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning" id="btn-confirm-reboot-pon-onus">
                    <i class="fas fa-redo mr-1"></i>Reboot ONUs
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════ --}}
{{-- HEADER BAR --}}
{{-- ═══════════════════════════════════════════════════ --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="card bg-gradient-dark mb-0">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div class="text-white">
                        <h4 class="mb-1 font-weight-bold">
                            <i class="fas fa-server mr-2"></i>{{ $olt->name }}
                        </h4>
                        <span class="text-white-50">
                            {{ $olt->ip_address }} &middot; {{ $olt->brand }} {{ $olt->model }}
                            @if($olt->cards->first()?->software_version)
                                &middot; <span class="text-white-50">FW {{ $olt->cards->first()->software_version }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="d-flex align-items-center flex-wrap mt-2 mt-md-0" style="gap: 8px;">
                        <div class="stat-mini stat-success">
                            <div class="stat-value">{{ $onlineOnu }}<small>/{{ $totalOnu }}</small></div>
                            <div class="stat-label">ONU Online</div>
                        </div>
                        <div class="stat-mini stat-primary">
                            <div class="stat-value">{{ $activePon }}<small>/{{ $totalPon }}</small></div>
                            <div class="stat-label">PON Aktif</div>
                        </div>
                        <div class="stat-mini stat-info">
                            <div class="stat-value">{{ $olt->vlans->count() }}</div>
                            <div class="stat-label">VLANs</div>
                        </div>
                        <div class="stat-mini stat-warning">
                            <div class="stat-value">{{ $olt->uplinks->where('status', 'up')->count() }}<small>/{{ $olt->uplinks->count() }}</small></div>
                            <div class="stat-label">Uplink</div>
                        </div>
                        <a href="{{ route('admin.olts.show', $olt) }}" class="btn btn-outline-light btn-sm ml-2">
                            <i class="fas fa-arrow-left mr-1"></i>Detail
                        </a>
                        <button type="button" class="btn btn-light btn-sm font-weight-bold" id="btn-sync-infra">
                            <i class="fas fa-sync mr-1"></i>Sync dari OLT
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════ --}}
{{-- SHELF VISUAL + ALL CARD DETAILS --}}
{{-- ═══════════════════════════════════════════════════ --}}
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-microchip mr-2"></i>Kartu & Slot</h3>
                <div class="card-tools">
                    @if($olt->cards->where('last_sync_at', '!=', null)->first())
                        <span class="text-muted text-sm">
                            <i class="fas fa-clock mr-1"></i>{{ $olt->cards->first()->last_sync_at?->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($olt->cards->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-server fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Belum ada data kartu. Klik <strong>"Sync dari OLT"</strong> untuk mengambil data.</p>
                    </div>
                @else
                    <!-- Shelf Visual -->
                    <div class="shelf-container mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="text-white font-weight-bold">
                                <i class="fas fa-th mr-2"></i>RACK {{ $olt->cards->first()->rack ?? 1 }} / SHELF {{ $olt->cards->first()->shelf ?? 1 }}
                            </span>
                            <div class="ml-auto d-flex" style="gap: 12px;">
                                <small class="text-white-50"><i class="fas fa-circle mr-1" style="color: #28a745; font-size: 8px;"></i>GPON</small>
                                <small class="text-white-50"><i class="fas fa-circle mr-1" style="color: #ffc107; font-size: 8px;"></i>MGMT</small>
                                <small class="text-white-50"><i class="fas fa-circle mr-1" style="color: #dc3545; font-size: 8px;"></i>Power</small>
                                <span class="badge badge-light">{{ $olt->cards->count() }} slot terisi</span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap" style="gap: 6px;">
                            @for($slot = 1; $slot <= 20; $slot++)
                                @php $card = $olt->cards->firstWhere('slot', $slot); @endphp
                                <div class="shelf-slot {{ $card ? ($card->status === 'standby' ? 'standby' : 'active-' . $card->role) : 'empty-slot' }}"
                                     data-slot="{{ $slot }}"
                                     @if($card) data-card-id="{{ $card->id }}" title="Klik untuk detail &#10;{{ $card->real_type ?: $card->configured_type }} — {{ $card->port_count }} port — {{ ucfirst($card->status) }}" @endif>
                                    <div class="slot-label">SLOT {{ $slot }}</div>
                                    @if($card)
                                        <div class="slot-type">{{ $card->real_type ?: $card->configured_type }}</div>
                                        <div class="slot-ports">{{ $card->port_count }} Port</div>
                                        @if($card->role === 'gpon')
                                            <span class="badge badge-success slot-badge">GPON</span>
                                        @elseif($card->role === 'epon')
                                            <span class="badge badge-info slot-badge">EPON</span>
                                        @elseif($card->role === 'management')
                                            <span class="badge badge-warning slot-badge">MGMT</span>
                                        @elseif($card->role === 'power')
                                            <span class="badge badge-danger slot-badge">PWR</span>
                                        @elseif($card->role === 'fan')
                                            <span class="badge badge-purple slot-badge" style="background: #6f42c1; color: #fff;">FAN</span>
                                        @else
                                            <span class="badge badge-secondary slot-badge">{{ strtoupper(substr($card->role, 0, 4)) }}</span>
                                        @endif
                                    @else
                                        <div style="margin-top: 16px;">
                                            <span style="font-size: 18px; color: rgba(255,255,255,0.1);">—</span>
                                        </div>
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </div>

                    <!-- All Card Detail Panels -->
                    @foreach($olt->cards as $card)
                    @php
                        $isGpon = in_array($card->role, ['gpon', 'epon']);
                        $isMgmt = $card->role === 'management';
                        $cardReg = $isGpon ? $card->ponPorts->sum('registered_onu') : 0;
                        $cardOn = $isGpon ? $card->ponPorts->sum('online_onu') : 0;
                        $cardOff = $cardReg - $cardOn;
                        $cardActivePorts = $isGpon ? $card->ponPorts->where('registered_onu', '>', 0)->count() : 0;
                        $cardUplinks = $isMgmt ? $olt->uplinks->where('slot', $card->slot) : collect();
                    @endphp
                    <div class="card-detail-panel role-{{ $card->role }} mb-3" id="card-panel-{{ $card->id }}">
                        <!-- Card Header -->
                        <div class="d-flex align-items-start justify-content-between flex-wrap mb-2">
                            <div>
                                <div class="d-flex align-items-center mb-1">
                                    <h5 class="mb-0 mr-2">
                                        @if($card->role === 'gpon')
                                            <i class="fas fa-network-wired text-success mr-1"></i>
                                        @elseif($card->role === 'epon')
                                            <i class="fas fa-network-wired text-info mr-1"></i>
                                        @elseif($card->role === 'management')
                                            <i class="fas fa-cog text-warning mr-1"></i>
                                        @elseif($card->role === 'power')
                                            <i class="fas fa-bolt text-danger mr-1"></i>
                                        @elseif($card->role === 'fan')
                                            <i class="fas fa-fan text-purple mr-1"></i>
                                        @else
                                            <i class="fas fa-microchip text-secondary mr-1"></i>
                                        @endif
                                        Slot {{ $card->slot }}
                                        <span class="text-muted mx-1">—</span>
                                        {{ $card->real_type ?: $card->configured_type }}
                                    </h5>
                                    {!! $card->status_badge !!}
                                    @if($card->status !== 'standby' && $card->status !== 'offline')
                                    <button class="btn btn-xs btn-outline-danger ml-2 btn-reboot-card"
                                            data-id="{{ $card->id }}"
                                            data-slot="{{ $card->slot }}"
                                            data-type="{{ $card->real_type ?: $card->configured_type }}"
                                            title="Reboot Card">
                                        <i class="fas fa-redo mr-1"></i>Reboot
                                    </button>
                                    @endif
                                </div>

                                <!-- Specs -->
                                <div class="spec-grid">
                                    <div class="spec-item">
                                        <span class="spec-label">Posisi: </span>
                                        <span class="spec-value">{{ $card->rack }}/{{ $card->shelf }}/{{ $card->slot }}</span>
                                    </div>
                                    <div class="spec-item">
                                        <span class="spec-label">Konfig: </span>
                                        <span class="spec-value">{{ $card->configured_type ?? '-' }}</span>
                                    </div>
                                    <div class="spec-item">
                                        <span class="spec-label">Aktual: </span>
                                        <span class="spec-value">{{ $card->real_type ?? '-' }}</span>
                                    </div>
                                    <div class="spec-item">
                                        <span class="spec-label">Port: </span>
                                        <span class="spec-value">{{ $card->port_count }}</span>
                                    </div>
                                    <div class="spec-item">
                                        <span class="spec-label">HW: </span>
                                        <span class="spec-value">{{ $card->hardware_version ?? '-' }}</span>
                                    </div>
                                    <div class="spec-item">
                                        <span class="spec-label">SW: </span>
                                        <span class="spec-value">{{ $card->software_version ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats (GPON/EPON only) -->
                            @if($isGpon && $card->ponPorts->isNotEmpty())
                            <div class="d-flex mt-2 mt-md-0" style="gap: 6px;">
                                <div class="stat-mini stat-success">
                                    <div class="stat-value">{{ $cardOn }}</div>
                                    <div class="stat-label">Online</div>
                                </div>
                                @if($cardOff > 0)
                                <div class="stat-mini stat-danger">
                                    <div class="stat-value">{{ $cardOff }}</div>
                                    <div class="stat-label">Offline</div>
                                </div>
                                @endif
                                <div class="stat-mini stat-primary">
                                    <div class="stat-value">{{ $cardActivePorts }}<small>/{{ $card->port_count }}</small></div>
                                    <div class="stat-label">PON Aktif</div>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- ── GPON/EPON: PON Ports ── --}}
                        @if($isGpon && $card->ponPorts->isNotEmpty())
                        <hr class="my-2">
                        <div class="section-title"><i class="fas fa-th-large"></i>PON Ports</div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-circle mr-1" style="color: #28a745; font-size: 7px;"></i>Online
                                <i class="fas fa-circle ml-2 mr-1" style="color: #ffc107; font-size: 7px;"></i>Sebagian Offline
                                <i class="fas fa-circle ml-2 mr-1" style="color: #dc3545; font-size: 7px;"></i>Semua Offline
                                <i class="fas fa-circle ml-2 mr-1" style="color: #e9ecef; font-size: 7px;"></i>Kosong
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
                                    <div class="pon-port-cell {{ $cls }}" title="Port 1/{{ $card->slot }}/{{ $p }}: {{ $on }}/{{ $reg }} online">
                                        <span class="port-num">{{ $p }}</span>
                                        @if($reg > 0)
                                            <span class="port-count">{{ $on }}/{{ $reg }}</span>
                                        @endif
                                    </div>
                                @endfor
                            </div>
                        </div>

                        <!-- VLAN Config per Card -->
                        @if($card->vlan_config && count($card->vlan_config) > 0)
                        <div class="section-title"><i class="fas fa-tags"></i>VLAN Aktif ({{ count($card->vlan_config) }})</div>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered mb-0" style="font-size: 12px;">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="70">VLAN</th>
                                        <th>Nama</th>
                                        <th width="90" class="text-center">Svc Port</th>
                                        <th>PON Ports</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($card->vlan_config as $vc)
                                    <tr>
                                        <td><strong>{{ $vc['vlan_id'] }}</strong></td>
                                        <td>
                                            {{ $vc['name'] }}
                                            @php $vlanModel = $olt->vlans->firstWhere('vlan_id', $vc['vlan_id']); @endphp
                                            @if($vlanModel) {!! $vlanModel->type_badge !!} @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-success vlan-svc-badge">{{ $vc['service_ports'] }}</span>
                                        </td>
                                        <td>
                                            @foreach($vc['pon_ports'] as $ponPort)
                                                <span class="badge" style="font-size: 10px; border: 1px solid #007bff; color: #007bff; margin: 1px;">
                                                    1/{{ $card->slot }}/{{ $ponPort }}
                                                </span>
                                            @endforeach
                                            <small class="text-muted ml-1">({{ count($vc['pon_ports']) }})</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        <!-- PON Port Detail Table -->
                        <div class="section-title"><i class="fas fa-list-alt"></i>Detail Per Port</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 11px;">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="75">Port</th>
                                        <th width="50" class="text-center">Status</th>
                                        <th width="50" class="text-center">Admin</th>
                                        <th width="70" class="text-center">ONU</th>
                                        <th width="60" class="text-center">Online</th>
                                        <th width="60" class="text-center">Offline</th>
                                        <th>Kapasitas</th>
                                        <th width="75" class="text-center">TX Power</th>
                                        <th width="75" class="text-center">RX Min</th>
                                        <th width="75" class="text-center">RX Max</th>
                                        <th width="75" class="text-center">RX Avg</th>
                                        <th width="40" class="text-center">Aksi</th>
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
                                                    <span class="badge badge-success" style="font-size: 9px;">UP</span>
                                                @elseif($pp)
                                                    <span class="badge badge-secondary" style="font-size: 9px;">DOWN</span>
                                                @else -
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($pp && $pp->admin_status === 'enabled')
                                                    <span class="text-success"><i class="fas fa-check"></i></span>
                                                @elseif($pp && $pp->admin_status === 'disabled')
                                                    <span class="text-danger"><i class="fas fa-times"></i></span>
                                                @else -
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
                                                    <div class="progress flex-grow-1 mr-2" style="height: 12px; border-radius: 6px;">
                                                        <div class="progress-bar {{ $pct > 80 ? 'bg-danger' : ($pct > 50 ? 'bg-warning' : 'bg-success') }}"
                                                             style="width: {{ $pct }}%; border-radius: 6px;"></div>
                                                    </div>
                                                    <small class="text-nowrap" style="min-width: 40px;">{{ $reg }}/{{ $max }}</small>
                                                </div>
                                            </td>
                                            <td class="text-center"><small>{{ $pp && $pp->tx_power ? number_format($pp->tx_power, 2) : '-' }}</small></td>
                                            <td class="text-center"><small>{{ $pp && $pp->rx_power_min ? number_format($pp->rx_power_min, 2) : '-' }}</small></td>
                                            <td class="text-center"><small>{{ $pp && $pp->rx_power_max ? number_format($pp->rx_power_max, 2) : '-' }}</small></td>
                                            <td class="text-center"><small>{{ $pp && $pp->rx_power_avg ? number_format($pp->rx_power_avg, 2) : '-' }}</small></td>
                                            <td class="text-center">
                                                @if($reg > 0)
                                                <button class="btn btn-xs btn-outline-warning btn-reboot-pon-onus"
                                                        data-slot="{{ $card->slot }}" data-port="{{ $p }}" data-count="{{ $reg }}"
                                                        title="Reboot {{ $reg }} ONUs">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endfor
                                </tbody>
                                <tfoot class="font-weight-bold" style="background: #f0f0f0;">
                                    <tr>
                                        <td colspan="3" class="text-right">Total:</td>
                                        <td class="text-center">{{ $card->ponPorts->sum('registered_onu') }}</td>
                                        <td class="text-center text-success">{{ $card->ponPorts->sum('online_onu') }}</td>
                                        <td class="text-center text-danger">{{ $card->ponPorts->sum('registered_onu') - $card->ponPorts->sum('online_onu') }}</td>
                                        <td colspan="6"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @endif

                        {{-- ── Management Card: Uplink Ports ── --}}
                        @if($isMgmt && $cardUplinks->isNotEmpty())
                        <hr class="my-2">
                        <div class="section-title"><i class="fas fa-arrow-up"></i>Uplink Ports ({{ $cardUplinks->count() }})</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 12px;">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Interface</th>
                                        <th width="55" class="text-center">Status</th>
                                        <th width="55" class="text-center">Admin</th>
                                        <th>Mode</th>
                                        <th>Tagged VLANs</th>
                                        <th width="90" class="text-center">In Rate</th>
                                        <th width="90" class="text-center">Out Rate</th>
                                        <th width="45" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cardUplinks as $uplink)
                                    <tr>
                                        <td>
                                            <strong>{{ $uplink->interface_name }}</strong>
                                            @if($uplink->interface_type === 'xgei')
                                                <span class="badge badge-info" style="font-size: 8px;">10G</span>
                                            @endif
                                            @if($uplink->description)
                                                <br><small class="text-muted">{{ $uplink->description }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">{!! $uplink->status_badge !!}</td>
                                        <td class="text-center">
                                            @if($uplink->admin_status === 'disabled')
                                                <span class="badge badge-danger" style="font-size: 9px;">OFF</span>
                                            @else
                                                <span class="badge badge-success" style="font-size: 9px;">ON</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($uplink->switchport_mode === 'trunk')
                                                <span class="badge badge-dark" style="font-size: 10px;">TRUNK</span>
                                            @else
                                                <small>{{ strtoupper($uplink->switchport_mode ?? '-') }}</small>
                                            @endif
                                            @if($uplink->native_vlan)
                                                <small class="text-muted d-block">PVID: {{ $uplink->native_vlan }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($uplink->tagged_vlans)
                                                @foreach($uplink->tagged_vlans as $vid)
                                                    <span class="uplink-vlan-tag">{{ $vid }}</span>
                                                @endforeach
                                            @else -
                                            @endif
                                        </td>
                                        <td class="text-center"><small>{{ $uplink->in_rate_formatted }}</small></td>
                                        <td class="text-center"><small>{{ $uplink->out_rate_formatted }}</small></td>
                                        <td class="text-center">
                                            <button class="btn btn-xs btn-outline-success btn-configure-uplink"
                                                    data-id="{{ $uplink->id }}"
                                                    data-name="{{ $uplink->interface_name }}"
                                                    data-mode="{{ $uplink->switchport_mode ?? 'trunk' }}"
                                                    data-vlans="{{ $uplink->tagged_vlans ? implode(', ', $uplink->tagged_vlans) : '' }}"
                                                    data-admin="{{ $uplink->admin_status ?? 'enabled' }}"
                                                    data-pvid="{{ $uplink->native_vlan ?? '' }}"
                                                    title="Konfigurasi">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        {{-- ── Other Card Types ── --}}
                        @if(!$isGpon && !$isMgmt)
                            @if($card->status === 'standby')
                            <div class="text-muted text-sm mt-1">
                                <i class="fas fa-pause-circle mr-1 text-warning"></i>Mode standby — siap sebagai backup.
                            </div>
                            @endif
                        @endif
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════ --}}
{{-- VLAN DATABASE + UPLINK SUMMARY --}}
{{-- ═══════════════════════════════════════════════════ --}}
<div class="row">
    <!-- VLAN Database -->
    <div class="col-lg-7">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tags mr-2"></i>VLAN Database</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-primary mr-1" id="btn-open-create-vlan">
                        <i class="fas fa-plus mr-1"></i>Buat VLAN
                    </button>
                    <span class="badge badge-info">{{ $olt->vlans->count() }}</span>
                    @if($olt->vlans->sum('service_port_count') > 0)
                        <span class="badge badge-success ml-1">{{ $olt->vlans->sum('service_port_count') }} svc</span>
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
                        <table class="table table-sm table-striped table-hover mb-0" style="font-size: 12px;">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="60">VLAN</th>
                                    <th>Nama</th>
                                    <th>Tipe</th>
                                    <th width="45" class="text-center">Svc</th>
                                    <th>Tagged</th>
                                    <th>Untagged</th>
                                    <th>Keterangan</th>
                                    <th width="55" class="text-center">Aksi</th>
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
                                        @if($vlan->tagged_ports && count($vlan->tagged_ports) > 0)
                                            @foreach($vlan->tagged_ports as $tp)
                                                <span class="uplink-vlan-tag">{{ $tp }}</span>
                                            @endforeach
                                        @else -
                                        @endif
                                    </td>
                                    <td>
                                        @if($vlan->untagged_ports && count($vlan->untagged_ports) > 0)
                                            @foreach($vlan->untagged_ports as $up)
                                                <span class="uplink-vlan-tag untagged">{{ $up }}</span>
                                            @endforeach
                                        @else -
                                        @endif
                                    </td>
                                    <td><small>{{ $vlan->description ?? '-' }}</small></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-xs btn-outline-info btn-edit-vlan"
                                                data-id="{{ $vlan->id }}"
                                                data-vlan-id="{{ $vlan->vlan_id }}"
                                                data-name="{{ $vlan->name }}"
                                                data-type="{{ $vlan->type }}"
                                                data-description="{{ $vlan->description }}"
                                                data-tagged="{{ json_encode($vlan->tagged_ports ?? []) }}"
                                                data-untagged="{{ json_encode($vlan->untagged_ports ?? []) }}"
                                                title="Edit VLAN">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-xs btn-outline-danger btn-delete-vlan"
                                                data-id="{{ $vlan->id }}"
                                                data-vlan-id="{{ $vlan->vlan_id }}"
                                                data-svc="{{ $vlan->service_port_count }}"
                                                title="Hapus VLAN">
                                            <i class="fas fa-trash"></i>
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

    <!-- Uplink Summary -->
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
                        <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Interface</th>
                                    <th width="50" class="text-center">Status</th>
                                    <th>Mode</th>
                                    <th>Tagged VLANs</th>
                                    <th class="text-center" width="90">Traffic</th>
                                    <th width="40" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($olt->uplinks as $uplink)
                                <tr>
                                    <td>
                                        <strong>{{ $uplink->interface_name }}</strong>
                                        @if($uplink->interface_type === 'xgei')
                                            <span class="badge badge-info" style="font-size: 8px;">10G</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{!! $uplink->status_badge !!}</td>
                                    <td>
                                        @if($uplink->switchport_mode === 'trunk')
                                            <span class="badge badge-dark" style="font-size: 9px;">TRUNK</span>
                                        @else
                                            <small>{{ strtoupper($uplink->switchport_mode ?? '-') }}</small>
                                        @endif
                                        @if($uplink->native_vlan)
                                            <small class="text-muted d-block">PVID:{{ $uplink->native_vlan }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($uplink->tagged_vlans)
                                            @foreach($uplink->tagged_vlans as $vid)
                                                <span class="uplink-vlan-tag">{{ $vid }}</span>
                                            @endforeach
                                        @else -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($uplink->in_rate_bps || $uplink->out_rate_bps)
                                            <small class="text-success"><i class="fas fa-arrow-down"></i> {{ $uplink->in_rate_formatted }}</small><br>
                                            <small class="text-primary"><i class="fas fa-arrow-up"></i> {{ $uplink->out_rate_formatted }}</small>
                                        @else -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-xs btn-outline-success btn-configure-uplink"
                                                data-id="{{ $uplink->id }}"
                                                data-name="{{ $uplink->interface_name }}"
                                                data-mode="{{ $uplink->switchport_mode ?? 'trunk' }}"
                                                data-vlans="{{ $uplink->tagged_vlans ? implode(', ', $uplink->tagged_vlans) : '' }}"
                                                data-admin="{{ $uplink->admin_status ?? 'enabled' }}"
                                                data-pvid="{{ $uplink->native_vlan ?? '' }}"
                                                title="Konfigurasi">
                                            <i class="fas fa-cog"></i>
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
</div>
@endsection

@push('js')
<script>
$(function() {
    // =========================================================================
    // Shelf Slot Click → Scroll to Card
    // =========================================================================
    $(document).on('click', '.shelf-slot[data-card-id]', function() {
        let cardId = $(this).data('card-id');
        let target = $('#card-panel-' + cardId);
        if (target.length) {
            $('.shelf-slot').removeClass('selected');
            $(this).addClass('selected');
            $('html, body').animate({ scrollTop: target.offset().top - 80 }, 400);
            target.css('box-shadow', '0 0 0 3px #007bff, 0 4px 16px rgba(0,123,255,0.2)');
            setTimeout(() => target.css('box-shadow', ''), 2500);
        }
    });

    // =========================================================================
    // Port Radio Active State
    // =========================================================================
    $(document).on('change', '.port-radio', function() {
        let group = $(this).closest('.port-radio-group');
        group.find('label').removeClass('active-tagged active-untagged active-none');
        $(this).closest('label').addClass('active-' + $(this).val());
    });

    // =========================================================================
    // Sync Infrastructure via SSE
    // =========================================================================
    let steps = { cards: false, vlans: false, uplinks: false, pon: false };

    function setStepDone(step, label) {
        $('#step-' + step + '-icon').removeClass('text-muted').addClass('text-success').removeClass('fa-microchip fa-tags fa-arrow-up fa-network-wired').addClass('fa-check-circle');
        $('#step-' + step + '-status').text(label || 'Selesai').removeClass('text-muted').addClass('text-success');
        $('#step-' + step).css('background', '#d4edda');
    }
    function setStepActive(step) {
        $('#step-' + step + '-icon').removeClass('text-muted').addClass('text-primary');
        $('#step-' + step + '-status').text('Sedang sync...').removeClass('text-muted').addClass('text-primary');
        $('#step-' + step).css('background', '#cce5ff');
    }
    function setStepError(step) {
        $('#step-' + step + '-icon').removeClass('text-muted text-primary').addClass('text-danger').removeClass('fa-microchip fa-tags fa-arrow-up fa-network-wired').addClass('fa-times-circle');
        $('#step-' + step + '-status').text('Gagal').removeClass('text-muted text-primary').addClass('text-danger');
        $('#step-' + step).css('background', '#f8d7da');
    }
    function appendLog(msg, type) {
        let color = type === 'success' ? '#4ec9b0' : (type === 'error' ? '#f44747' : (type === 'info' ? '#569cd6' : '#d4d4d4'));
        let time = new Date().toLocaleTimeString('id-ID');
        $('#progress-logs').append('<div style="color:' + color + '">[' + time + '] ' + msg + '</div>');
        $('#progress-logs').scrollTop($('#progress-logs')[0].scrollHeight);
    }

    $('#btn-sync-infra').click(function() {
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Syncing...');
        $('#modal-progress').modal('show');

        let eventSource = new EventSource('{{ route("admin.olts.sync-infrastructure-stream", $olt) }}');
        eventSource.onmessage = function(event) {
            let data = JSON.parse(event.data);

            if (data.type === 'progress') {
                let pct = data.progress + '%';
                $('#progress-bar').css('width', pct).text(pct);
            }
            if (data.step) {
                let stepName = data.step.replace('_', '');
                if (!steps[stepName]) { setStepActive(stepName); steps[stepName] = true; }
                if (data.step_done) { setStepDone(stepName, data.step_label || null); }
            }
            if (data.message) { appendLog(data.message, data.status); }
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
                } else {
                    $('#progress-bar').removeClass('bg-dark').addClass('bg-danger');
                    $('#progress-title').text('Sync Gagal');
                    $('#sync-results-alert').removeClass('alert-success').addClass('alert-danger');
                    $('#sync-results-icon').removeClass('fa-check-circle text-success').addClass('fa-times-circle text-danger');
                    $('#sync-results-title').text('Sync Gagal');
                    ['cards', 'vlans', 'uplinks', 'pon'].forEach(setStepError);
                }
                $('#sync-results-message').text(data.message);
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
            appendLog('Koneksi SSE terputus', 'error');
        };
    });

    // =========================================================================
    // Edit VLAN
    // =========================================================================
    $(document).on('click', '.btn-edit-vlan', function() {
        let btn = $(this);
        $('#edit-vlan-id').val(btn.data('id'));
        $('#edit-vlan-display').val(btn.data('vlan-id'));
        $('#edit-vlan-title-id').text(btn.data('vlan-id'));
        $('#edit-vlan-type').val(btn.data('type'));
        $('#edit-vlan-description').val(btn.data('description') || '');

        let tagged = btn.data('tagged') || [];
        let untagged = btn.data('untagged') || [];
        if (typeof tagged === 'string') tagged = JSON.parse(tagged);
        if (typeof untagged === 'string') untagged = JSON.parse(untagged);

        // Reset all to none
        $('.port-radio[value="none"]').prop('checked', true);
        $('.port-radio-group label').removeClass('active-tagged active-untagged active-none');
        $('.port-radio[value="none"]').closest('label').addClass('active-none');

        tagged.forEach(function(port) {
            let slug = port.replace(/[\/\_]/g, '-');
            let radio = $('input[name="port_' + slug + '"][value="tagged"]');
            if (radio.length) {
                radio.prop('checked', true);
                radio.closest('.port-radio-group').find('label').removeClass('active-tagged active-untagged active-none');
                radio.closest('label').addClass('active-tagged');
            }
        });
        untagged.forEach(function(port) {
            let slug = port.replace(/[\/\_]/g, '-');
            let radio = $('input[name="port_' + slug + '"][value="untagged"]');
            if (radio.length) {
                radio.prop('checked', true);
                radio.closest('.port-radio-group').find('label').removeClass('active-tagged active-untagged active-none');
                radio.closest('label').addClass('active-untagged');
            }
        });

        $('#modal-edit-vlan').modal('show');
    });

    $('#btn-save-vlan').click(function() {
        let btn = $(this);
        let vlanId = $('#edit-vlan-id').val();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

        let taggedPorts = [], untaggedPorts = [];
        $('.port-radio:checked').each(function() {
            let port = $(this).data('port');
            let val = $(this).val();
            if (val === 'tagged') taggedPorts.push(port);
            else if (val === 'untagged') untaggedPorts.push(port);
        });

        $.ajax({
            url: '{{ route("admin.olts.infrastructure.vlans.update-type", [$olt, "__VLAN__"]) }}'.replace('__VLAN__', vlanId),
            method: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                type: $('#edit-vlan-type').val(),
                description: $('#edit-vlan-description').val(),
                tagged_ports: taggedPorts,
                untagged_ports: untaggedPorts,
            },
            success: function(res) {
                if (res.success) { toastr.success(res.message); location.reload(); }
                else { toastr.warning(res.message); }
            },
            error: function(xhr) { toastr.error(xhr.responseJSON?.message || 'Gagal menyimpan'); },
            complete: function() { btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan ke OLT'); }
        });
    });

    // =========================================================================
    // Configure Uplink
    // =========================================================================
    $(document).on('click', '.btn-configure-uplink', function() {
        let btn = $(this);
        $('#cfg-uplink-id').val(btn.data('id'));
        $('#cfg-uplink-display').val(btn.data('name'));
        $('#cfg-uplink-add-vlans').val('');
        $('#cfg-uplink-remove-vlans').val('');
        $('#cfg-uplink-pvid').val(btn.data('pvid') || '');
        $('#cfg-uplink-admin').val(btn.data('admin') || 'enabled');
        $('#cfg-uplink-desc').val('');
        $('#modal-configure-uplink').modal('show');
    });

    $('#btn-save-uplink-config').click(function() {
        let btn = $(this);
        let uplinkId = $('#cfg-uplink-id').val();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menerapkan...');

        $.ajax({
            url: '{{ route("admin.olts.infrastructure.uplinks.configure", [$olt, "__UPLINK__"]) }}'.replace('__UPLINK__', uplinkId),
            method: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                add_vlans: $('#cfg-uplink-add-vlans').val(),
                remove_vlans: $('#cfg-uplink-remove-vlans').val(),
                native_vlan: $('#cfg-uplink-pvid').val() || null,
                admin_status: $('#cfg-uplink-admin').val(),
                description: $('#cfg-uplink-desc').val() || null,
            },
            success: function(res) {
                if (res.success) { toastr.success(res.message); location.reload(); }
                else { toastr.warning(res.message); }
            },
            error: function(xhr) { toastr.error(xhr.responseJSON?.message || 'Gagal menerapkan'); },
            complete: function() { btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i>Terapkan ke OLT'); }
        });
    });

    // =========================================================================
    // Create / Delete VLAN
    // =========================================================================
    $('#btn-open-create-vlan').click(function() {
        $('#create-vlan-id, #create-vlan-name, #create-vlan-desc').val('');
        $('#modal-create-vlan').modal('show');
    });

    $('#btn-create-vlan').click(function() {
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Membuat...');
        $.ajax({
            url: '{{ route("admin.olts.infrastructure.vlans.create", $olt) }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', vlan_id: $('#create-vlan-id').val(), name: $('#create-vlan-name').val(), type: $('#create-vlan-type').val(), description: $('#create-vlan-desc').val() },
            success: function(res) { if (res.success) { toastr.success(res.message); location.reload(); } else { toastr.warning(res.message); } },
            error: function(xhr) { toastr.error(xhr.responseJSON?.message || 'Gagal membuat VLAN'); },
            complete: function() { btn.prop('disabled', false).html('<i class="fas fa-plus mr-1"></i>Buat di OLT'); }
        });
    });

    $(document).on('click', '.btn-delete-vlan', function() {
        let svc = $(this).data('svc') || 0;
        $('#delete-vlan-id').val($(this).data('id'));
        $('#delete-vlan-display').text($(this).data('vlan-id'));
        $('#delete-vlan-warning').text(svc > 0 ? 'PERINGATAN: ' + svc + ' service-port aktif!' : 'VLAN akan dihapus dari OLT.');
        $('#modal-delete-vlan').modal('show');
    });

    $('#btn-confirm-delete-vlan').click(function() {
        let btn = $(this), vlanId = $('#delete-vlan-id').val();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menghapus...');
        $.ajax({
            url: '{{ route("admin.olts.infrastructure.vlans.destroy", [$olt, "__VLAN__"]) }}'.replace('__VLAN__', vlanId),
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) { if (res.success) { toastr.success(res.message); location.reload(); } else { toastr.warning(res.message); } },
            error: function(xhr) { toastr.error(xhr.responseJSON?.message || 'Gagal menghapus'); },
            complete: function() { btn.prop('disabled', false).html('<i class="fas fa-trash mr-1"></i>Hapus'); }
        });
    });

    // =========================================================================
    // Reboot Card / PON ONUs
    // =========================================================================
    $(document).on('click', '.btn-reboot-card', function() {
        $('#reboot-card-id').val($(this).data('id'));
        $('#reboot-card-display').text($(this).data('slot') + ' (' + $(this).data('type') + ')');
        $('#modal-reboot-card').modal('show');
    });

    $('#btn-confirm-reboot-card').click(function() {
        let btn = $(this), cardId = $('#reboot-card-id').val();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Rebooting...');
        $.ajax({
            url: '{{ route("admin.olts.infrastructure.cards.reboot", [$olt, "__CARD__"]) }}'.replace('__CARD__', cardId),
            method: 'POST', data: { _token: '{{ csrf_token() }}' },
            success: function(res) { if (res.success) { toastr.success(res.message); $('#modal-reboot-card').modal('hide'); } else { toastr.warning(res.message); } },
            error: function(xhr) { toastr.error(xhr.responseJSON?.message || 'Gagal reboot'); },
            complete: function() { btn.prop('disabled', false).html('<i class="fas fa-redo mr-1"></i>Reboot'); }
        });
    });

    $(document).on('click', '.btn-reboot-pon-onus', function() {
        $('#reboot-pon-slot').val($(this).data('slot'));
        $('#reboot-pon-port').val($(this).data('port'));
        $('#reboot-pon-display').text('1/' + $(this).data('slot') + '/' + $(this).data('port') + ' (' + $(this).data('count') + ' ONU)');
        $('#modal-reboot-pon-onus').modal('show');
    });

    $('#btn-confirm-reboot-pon-onus').click(function() {
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Rebooting...');
        $.ajax({
            url: '{{ route("admin.olts.infrastructure.reboot-pon-onus", $olt) }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', slot: $('#reboot-pon-slot').val(), port: $('#reboot-pon-port').val() },
            success: function(res) { if (res.success) { toastr.success(res.message); $('#modal-reboot-pon-onus').modal('hide'); } else { toastr.warning(res.message); } },
            error: function(xhr) { toastr.error(xhr.responseJSON?.message || 'Gagal reboot ONUs'); },
            complete: function() { btn.prop('disabled', false).html('<i class="fas fa-redo mr-1"></i>Reboot ONUs'); }
        });
    });
});
</script>
@endpush
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

<!-- Edit VLAN Modal -->
<div class="modal fade" id="modal-edit-vlan" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit VLAN</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-vlan-id">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>VLAN ID</label>
                            <input type="text" class="form-control" id="edit-vlan-display" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
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
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Keterangan</label>
                            <input type="text" class="form-control" id="edit-vlan-description" placeholder="Opsional">
                        </div>
                    </div>
                </div>

                <!-- Port Membership -->
                <hr class="my-2">
                <h6 class="mb-3"><i class="fas fa-network-wired mr-1"></i>Port Membership (Uplink)</h6>
                @php
                    $uplinkPorts = [
                        'gei_1/3/1' => 'gei_1/3/1 (GE Uplink 1)',
                        'xgei_1/3/2' => 'xgei_1/3/2 (10G Uplink)',
                        'gei_1/3/3' => 'gei_1/3/3 (GE Uplink 3)',
                    ];
                @endphp
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Port</th>
                                <th class="text-center" width="100">Tagged</th>
                                <th class="text-center" width="100">Untagged</th>
                                <th class="text-center" width="100">None</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($uplinkPorts as $portKey => $portLabel)
                            <tr>
                                <td><code>{{ $portLabel }}</code></td>
                                <td class="text-center">
                                    <input type="radio" name="port_{{ Str::slug($portKey) }}" value="tagged"
                                           data-port="{{ $portKey }}" class="port-radio">
                                </td>
                                <td class="text-center">
                                    <input type="radio" name="port_{{ Str::slug($portKey) }}" value="untagged"
                                           data-port="{{ $portKey }}" class="port-radio">
                                </td>
                                <td class="text-center">
                                    <input type="radio" name="port_{{ Str::slug($portKey) }}" value="none"
                                           data-port="{{ $portKey }}" class="port-radio" checked>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <small class="form-text text-muted mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    Port membership diubah via {{ $olt->snmp_community_rw ? 'SNMP SET' : 'Telnet CLI' }}.
                    Deskripsi selalu via CLI (tidak ada di Q-BRIDGE-MIB).
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-info" id="btn-save-vlan">
                    <i class="fas fa-save mr-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Configure Uplink Modal -->
<div class="modal fade" id="modal-configure-uplink" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white"><i class="fas fa-cog mr-2"></i>Konfigurasi Uplink Port</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cfg-uplink-id">
                <div class="form-group">
                    <label>Interface</label>
                    <input type="text" class="form-control" id="cfg-uplink-name" readonly>
                </div>
                <div class="form-group">
                    <label>Mode</label>
                    <input type="text" class="form-control" id="cfg-uplink-mode" readonly>
                </div>
                <div class="form-group">
                    <label>Tagged VLANs saat ini</label>
                    <div id="cfg-uplink-current-vlans" class="form-control-plaintext"></div>
                </div>
                <hr>
                <div class="form-group">
                    <label>Tambah VLAN <small class="text-muted">(pisah dengan koma, misal: 100,200,300)</small></label>
                    <input type="text" class="form-control" id="cfg-uplink-add-vlans" placeholder="Contoh: 100, 200">
                </div>
                <div class="form-group">
                    <label>Hapus VLAN <small class="text-muted">(pisah dengan koma)</small></label>
                    <input type="text" class="form-control" id="cfg-uplink-remove-vlans" placeholder="Contoh: 100, 200">
                </div>
                <div class="form-group">
                    <label>Admin State</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="cfg_uplink_admin" id="cfg-uplink-admin-enabled" value="enabled" checked>
                            <label class="form-check-label" for="cfg-uplink-admin-enabled">Enabled</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="cfg_uplink_admin" id="cfg-uplink-admin-disabled" value="disabled">
                            <label class="form-check-label" for="cfg-uplink-admin-disabled">Disabled (Shutdown)</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>PVID / Native VLAN <small class="text-muted">(opsional)</small></label>
                    <input type="number" class="form-control" id="cfg-uplink-pvid" placeholder="Switchport default VLAN" min="1" max="4094">
                </div>
                <div class="form-group">
                    <label>Deskripsi Port <small class="text-muted">(opsional)</small></label>
                    <input type="text" class="form-control" id="cfg-uplink-desc" placeholder="Port description" maxlength="64">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btn-save-uplink-config">
                    <i class="fas fa-save mr-1"></i>Simpan ke OLT
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create VLAN Modal -->
<div class="modal fade" id="modal-create-vlan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Buat VLAN Baru</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>VLAN ID <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="new-vlan-id" min="2" max="4094" placeholder="2 - 4094" required>
                </div>
                <div class="form-group">
                    <label>Nama VLAN</label>
                    <input type="text" class="form-control" id="new-vlan-name" placeholder="Contoh: internet-pppoe" maxlength="32">
                </div>
                <div class="form-group">
                    <label>Tipe</label>
                    <select class="form-control" id="new-vlan-type">
                        <option value="other">Lainnya</option>
                        <option value="service">Service (Internet)</option>
                        <option value="management">Management (TR069)</option>
                        <option value="voip">VoIP</option>
                        <option value="iptv">IPTV</option>
                        <option value="infra">Infrastructure</option>
                    </select>
                </div>
                <div class="alert alert-warning py-2 mb-0">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    VLAN akan langsung dibuat di OLT. Untuk menambahkan ke uplink, gunakan tombol Configure di uplink port.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-create-vlan">
                    <i class="fas fa-plus mr-1"></i>Buat VLAN di OLT
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete VLAN Modal -->
<div class="modal fade" id="modal-delete-vlan" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white"><i class="fas fa-trash mr-2"></i>Hapus VLAN</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <input type="hidden" id="del-vlan-id">
                <p>Yakin hapus <strong>VLAN <span id="del-vlan-display"></span></strong> dari OLT?</p>
                <p class="text-danger small">VLAN akan dihapus dari perangkat OLT. Pastikan tidak ada ONU yang menggunakan VLAN ini.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btn-confirm-delete-vlan">
                    <i class="fas fa-trash mr-1"></i>Hapus dari OLT
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Reboot Card Modal -->
<div class="modal fade" id="modal-reboot-card" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white"><i class="fas fa-redo mr-2"></i>Reboot Card</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <input type="hidden" id="reboot-card-id">
                <p>Yakin reboot <strong>Card Slot <span id="reboot-card-display"></span></strong>?</p>
                <p class="text-danger small">Card akan restart dan semua ONU di slot ini akan terputus sementara.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btn-confirm-reboot-card">
                    <i class="fas fa-redo mr-1"></i>Reboot Card
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Reboot PON ONUs Modal -->
<div class="modal fade" id="modal-reboot-pon-onus" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-redo mr-2"></i>Reboot ONUs</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <input type="hidden" id="reboot-pon-slot">
                <input type="hidden" id="reboot-pon-port">
                <p>Yakin reboot semua ONU di <strong>port <span id="reboot-pon-display"></span></strong>?</p>
                <p class="text-warning small">Semua ONU di port ini akan restart dan terputus sementara.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning" id="btn-confirm-reboot-pon-onus">
                    <i class="fas fa-redo mr-1"></i>Reboot ONUs
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
                                    <th width="80" class="text-center">Aksi</th>
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
                                    <td class="text-center">
                                        @if($card->status !== 'standby')
                                        <button class="btn btn-xs btn-outline-danger btn-reboot-card"
                                                data-id="{{ $card->id }}"
                                                data-slot="{{ $card->slot }}"
                                                data-type="{{ $card->real_type ?: $card->configured_type }}"
                                                title="Reboot Card">
                                            <i class="fas fa-redo"></i>
                                        </button>
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
                    @if($card->vlan_config)
                        <span class="badge badge-info mr-1">{{ count($card->vlan_config) }} VLAN</span>
                    @endif
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

                <!-- VLAN Config per Card -->
                @if($card->vlan_config && count($card->vlan_config) > 0)
                <div class="mb-3">
                    <h6 class="font-weight-bold mb-2">
                        <i class="fas fa-tags mr-1 text-info"></i>VLAN yang Digunakan
                        <span class="badge badge-info ml-1">{{ count($card->vlan_config) }} VLAN</span>
                        <span class="badge badge-secondary ml-1">{{ collect($card->vlan_config)->sum('service_ports') }} service-port</span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="font-size: 12px;">
                            <thead class="thead-light">
                                <tr>
                                    <th width="80">VLAN ID</th>
                                    <th>Nama</th>
                                    <th width="100" class="text-center">Service Port</th>
                                    <th>PON Ports yang Menggunakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($card->vlan_config as $vc)
                                <tr>
                                    <td><strong>{{ $vc['vlan_id'] }}</strong></td>
                                    <td>
                                        {{ $vc['name'] }}
                                        @php
                                            $vlanModel = $olt->vlans->firstWhere('vlan_id', $vc['vlan_id']);
                                        @endphp
                                        @if($vlanModel)
                                            {!! $vlanModel->type_badge !!}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success vlan-svc-badge">{{ $vc['service_ports'] }}</span>
                                    </td>
                                    <td>
                                        @foreach($vc['pon_ports'] as $ponPort)
                                            <span class="badge badge-outline-primary" style="font-size: 10px; border: 1px solid #007bff; color: #007bff; margin: 1px;">
                                                1/{{ $card->slot }}/{{ $ponPort }}
                                            </span>
                                        @endforeach
                                        <small class="text-muted ml-1">({{ count($vc['pon_ports']) }} port)</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

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
                                <th width="60" class="text-center">Aksi</th>
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
                                    <td class="text-center">
                                        @if($reg > 0)
                                        <button class="btn btn-xs btn-outline-warning btn-reboot-pon-onus"
                                                data-slot="{{ $card->slot }}"
                                                data-port="{{ $p }}"
                                                data-count="{{ $reg }}"
                                                title="Reboot {{ $reg }} ONUs">
                                            <i class="fas fa-redo"></i>
                                        </button>
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
                                <td colspan="6"></td>
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
                    <button type="button" class="btn btn-sm btn-primary mr-1" id="btn-open-create-vlan">
                        <i class="fas fa-plus mr-1"></i>Buat VLAN
                    </button>
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
                                    <th class="text-center" title="Jumlah tagged port pada VLAN">Tagged</th>
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
                                    <td>
                                        <small>{{ $vlan->description ?? '-' }}</small>
                                        @if($vlan->multicast_mode && $vlan->multicast_mode !== 'flood-unknown')
                                            <br><span class="badge badge-sm badge-light">MC: {{ $vlan->multicast_mode }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($vlan->tagged_ports && count($vlan->tagged_ports) > 0)
                                            <span class="badge badge-info" title="{{ implode(', ', $vlan->tagged_ports) }}" style="cursor:help">
                                                {{ count($vlan->tagged_ports) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-xs btn-outline-info btn-edit-vlan"
                                                data-id="{{ $vlan->id }}"
                                                data-vlan-id="{{ $vlan->vlan_id }}"
                                                data-name="{{ $vlan->name }}"
                                                data-type="{{ $vlan->type }}"
                                                data-description="{{ $vlan->description }}"
                                                data-tagged="{{ json_encode($vlan->tagged_ports ?? []) }}"
                                                data-untagged="{{ json_encode($vlan->untagged_ports ?? []) }}"
                                                title="Edit VLAN">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-xs btn-outline-danger btn-delete-vlan"
                                                data-id="{{ $vlan->id }}"
                                                data-vlan-id="{{ $vlan->vlan_id }}"
                                                data-svc="{{ $vlan->service_port_count }}"
                                                title="Hapus VLAN dari OLT">
                                            <i class="fas fa-trash"></i>
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
                                    <th width="55" class="text-center">Aksi</th>
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
                                    <td class="text-center">
                                        <button class="btn btn-xs btn-outline-success btn-configure-uplink"
                                                data-id="{{ $uplink->id }}"
                                                data-name="{{ $uplink->interface_name }}"
                                                data-mode="{{ $uplink->switchport_mode ?? 'trunk' }}"
                                                data-vlans="{{ $uplink->tagged_vlans ? implode(', ', $uplink->tagged_vlans) : '' }}"
                                                data-admin="{{ $uplink->admin_status ?? 'enabled' }}"
                                                data-pvid="{{ $uplink->native_vlan ?? '' }}"
                                                title="Konfigurasi">
                                            <i class="fas fa-cog"></i>
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
    // Edit VLAN
    // =========================================================================
    $(document).on('click', '.btn-edit-vlan', function() {
        let btn = $(this);
        $('#edit-vlan-id').val(btn.data('id'));
        $('#edit-vlan-display').val(btn.data('vlan-id') + ' - ' + btn.data('name'));
        $('#edit-vlan-type').val(btn.data('type'));
        $('#edit-vlan-description').val(btn.data('description') || '');

        // Set port radios
        let tagged = btn.data('tagged') || [];
        let untagged = btn.data('untagged') || [];
        if (typeof tagged === 'string') tagged = JSON.parse(tagged);
        if (typeof untagged === 'string') untagged = JSON.parse(untagged);

        // Reset all to none
        $('.port-radio[value="none"]').prop('checked', true);

        // Set tagged ports
        tagged.forEach(function(port) {
            let slug = port.replace(/[\/\_]/g, '-');
            $('input[name="port_' + slug + '"][value="tagged"]').prop('checked', true);
        });

        // Set untagged ports
        untagged.forEach(function(port) {
            let slug = port.replace(/[\/\_]/g, '-');
            $('input[name="port_' + slug + '"][value="untagged"]').prop('checked', true);
        });

        $('#modal-edit-vlan').modal('show');
    });

    $('#btn-save-vlan').click(function() {
        let btn = $(this);
        let vlanId = $('#edit-vlan-id').val();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

        // Collect port membership
        let taggedPorts = [];
        let untaggedPorts = [];
        $('.port-radio:checked').each(function() {
            let port = $(this).data('port');
            let val = $(this).val();
            if (val === 'tagged') taggedPorts.push(port);
            else if (val === 'untagged') untaggedPorts.push(port);
        });

        $.ajax({
            url: '{{ route("admin.olts.infrastructure.vlans.update-type", [$olt, "__VLAN__"]) }}'.replace('__VLAN__', vlanId),
            method: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                type: $('#edit-vlan-type').val(),
                description: $('#edit-vlan-description').val(),
                tagged_ports: taggedPorts,
                untagged_ports: untaggedPorts,
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

    // =========================================================================
    // Configure Uplink
    // =========================================================================
    $(document).on('click', '.btn-configure-uplink', function() {
        let btn = $(this);
        $('#cfg-uplink-id').val(btn.data('id'));
        $('#cfg-uplink-name').val(btn.data('name'));
        $('#cfg-uplink-mode').val(btn.data('mode') || 'trunk');
        $('#cfg-uplink-current-vlans').text(btn.data('vlans') || 'Tidak ada');
        $('#cfg-uplink-add-vlans').val('');
        $('#cfg-uplink-remove-vlans').val('');
        $('#cfg-uplink-desc').val('');
        $('#cfg-uplink-pvid').val(btn.data('pvid') || '');
        $('input[name="cfg_uplink_admin"][value="' + (btn.data('admin') || 'enabled') + '"]').prop('checked', true);
        $('#modal-configure-uplink').modal('show');
    });

    $('#btn-save-uplink-config').click(function() {
        let btn = $(this);
        let uplinkId = $('#cfg-uplink-id').val();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan ke OLT...');

        $.ajax({
            url: '{{ route("admin.olts.infrastructure.uplinks.configure", [$olt, "__UPLINK__"]) }}'.replace('__UPLINK__', uplinkId),
            method: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                add_vlans: $('#cfg-uplink-add-vlans').val(),
                remove_vlans: $('#cfg-uplink-remove-vlans').val(),
                admin_status: $('input[name="cfg_uplink_admin"]:checked').val(),
                native_vlan: $('#cfg-uplink-pvid').val() || null,
                description: $('#cfg-uplink-desc').val(),
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#modal-configure-uplink').modal('hide');
                    location.reload();
                } else {
                    toastr.warning(res.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal menyimpan konfigurasi');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan ke OLT');
            }
        });
    });

    // =========================================================================
    // Create VLAN
    // =========================================================================
    $('#btn-open-create-vlan').click(function() {
        $('#new-vlan-id').val('');
        $('#new-vlan-name').val('');
        $('#new-vlan-type').val('other');
        $('#modal-create-vlan').modal('show');
    });

    $('#btn-create-vlan').click(function() {
        let btn = $(this);
        let vlanId = $('#new-vlan-id').val();
        if (!vlanId || vlanId < 2 || vlanId > 4094) {
            toastr.error('VLAN ID harus antara 2 - 4094');
            return;
        }
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Membuat VLAN...');

        $.ajax({
            url: '{{ route("admin.olts.infrastructure.vlans.create", $olt) }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                vlan_id: vlanId,
                name: $('#new-vlan-name').val(),
                type: $('#new-vlan-type').val(),
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#modal-create-vlan').modal('hide');
                    location.reload();
                } else {
                    toastr.warning(res.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal membuat VLAN');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-plus mr-1"></i>Buat VLAN di OLT');
            }
        });
    });

    // =========================================================================
    // Delete VLAN
    // =========================================================================
    $(document).on('click', '.btn-delete-vlan', function() {
        let btn = $(this);
        let svc = btn.data('svc') || 0;
        if (svc > 0) {
            toastr.error('VLAN masih digunakan oleh ' + svc + ' service-port. Tidak bisa dihapus.');
            return;
        }
        $('#del-vlan-id').val(btn.data('id'));
        $('#del-vlan-display').text(btn.data('vlan-id'));
        $('#modal-delete-vlan').modal('show');
    });

    $('#btn-confirm-delete-vlan').click(function() {
        let btn = $(this);
        let vlanId = $('#del-vlan-id').val();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menghapus...');

        $.ajax({
            url: '{{ route("admin.olts.infrastructure.vlans.destroy", [$olt, "__VLAN__"]) }}'.replace('__VLAN__', vlanId),
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#modal-delete-vlan').modal('hide');
                    location.reload();
                } else {
                    toastr.warning(res.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal menghapus VLAN');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-trash mr-1"></i>Hapus dari OLT');
            }
        });
    });

    // =========================================================================
    // Reboot Card
    // =========================================================================
    $(document).on('click', '.btn-reboot-card', function() {
        let btn = $(this);
        $('#reboot-card-id').val(btn.data('id'));
        $('#reboot-card-display').text(btn.data('slot') + ' (' + btn.data('type') + ')');
        $('#modal-reboot-card').modal('show');
    });

    $('#btn-confirm-reboot-card').click(function() {
        let btn = $(this);
        let cardId = $('#reboot-card-id').val();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Rebooting...');

        $.ajax({
            url: '{{ route("admin.olts.infrastructure.cards.reboot", [$olt, "__CARD__"]) }}'.replace('__CARD__', cardId),
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#modal-reboot-card').modal('hide');
                } else {
                    toastr.warning(res.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal reboot card');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-redo mr-1"></i>Reboot Card');
            }
        });
    });

    // =========================================================================
    // Reboot All ONUs on PON Port
    // =========================================================================
    $(document).on('click', '.btn-reboot-pon-onus', function() {
        let btn = $(this);
        $('#reboot-pon-slot').val(btn.data('slot'));
        $('#reboot-pon-port').val(btn.data('port'));
        $('#reboot-pon-display').text('1/' + btn.data('slot') + '/' + btn.data('port') + ' (' + btn.data('count') + ' ONU)');
        $('#modal-reboot-pon-onus').modal('show');
    });

    $('#btn-confirm-reboot-pon-onus').click(function() {
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Rebooting...');

        $.ajax({
            url: '{{ route("admin.olts.infrastructure.reboot-pon-onus", $olt) }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                slot: $('#reboot-pon-slot').val(),
                port: $('#reboot-pon-port').val(),
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#modal-reboot-pon-onus').modal('hide');
                } else {
                    toastr.warning(res.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal reboot ONUs');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-redo mr-1"></i>Reboot ONUs');
            }
        });
    });
});
</script>
@endpush
