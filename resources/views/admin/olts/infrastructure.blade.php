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
    /* Shelf Slot Visual */
    .shelf-container { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; }
    .shelf-slot {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        text-align: center;
        padding: 10px 6px;
        min-height: 100px;
        min-width: 78px;
        transition: all 0.3s cubic-bezier(.4,0,.2,1);
        cursor: pointer;
        position: relative;
        background: #fff;
    }
    .shelf-slot:hover:not(.empty-slot) { transform: translateY(-4px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
    .shelf-slot.active-gpon { border-color: #22c55e; background: linear-gradient(180deg, #f0fdf4 0%, #fff 100%); }
    .shelf-slot.active-epon { border-color: #06b6d4; background: linear-gradient(180deg, #ecfeff 0%, #fff 100%); }
    .shelf-slot.active-mgmt,
    .shelf-slot.active-management { border-color: #f59e0b; background: linear-gradient(180deg, #fffbeb 0%, #fff 100%); }
    .shelf-slot.active-power { border-color: #ef4444; background: linear-gradient(180deg, #fef2f2 0%, #fff 100%); }
    .shelf-slot.active-fan { border-color: #a855f7; background: linear-gradient(180deg, #faf5ff 0%, #fff 100%); }
    .shelf-slot.active-other { border-color: #94a3b8; background: linear-gradient(180deg, #f8fafc 0%, #fff 100%); }
    .shelf-slot.empty-slot { display: none; }
    .shelf-slot.standby { border-style: dashed; opacity: 0.6; }
    .shelf-slot .slot-label { font-size: 9px; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; }
    .shelf-slot .slot-type { font-size: 13px; font-weight: 700; color: #1e293b; margin: 4px 0 2px; }
    .shelf-slot .slot-ports { font-size: 9px; color: #64748b; }
    .shelf-slot .slot-badge { font-size: 8px; padding: 2px 8px; border-radius: 4px; }
    .shelf-slot.selected { box-shadow: 0 0 0 3px #3b82f6, 0 6px 20px rgba(59,130,246,0.2); transform: translateY(-4px); border-color: #3b82f6; }

    /* PON Port Cell */
    .pon-port-cell {
        display: inline-flex; align-items: center; justify-content: center;
        width: 52px; height: 52px; text-align: center; border-radius: 10px;
        margin: 3px; font-weight: 600; cursor: default; position: relative;
        flex-direction: column; line-height: 1.2; transition: all 0.25s cubic-bezier(.4,0,.2,1);
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .pon-port-cell:hover { transform: scale(1.08); }
    .pon-port-cell .port-num { font-size: 15px; font-weight: 800; }
    .pon-port-cell .port-count { font-size: 8px; opacity: 0.9; }
    .pon-port-cell.has-onu { background: linear-gradient(135deg, #22c55e, #10b981); color: #fff; box-shadow: 0 3px 10px rgba(34,197,94,0.3); }
    .pon-port-cell.empty-port { background: #f1f5f9; color: #94a3b8; border: 1px dashed #cbd5e1; box-shadow: none; }
    .pon-port-cell.some-offline { background: linear-gradient(135deg, #f59e0b, #f97316); color: #fff; box-shadow: 0 3px 10px rgba(245,158,11,0.3); }
    .pon-port-cell.all-offline { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; box-shadow: 0 3px 10px rgba(239,68,68,0.3); }

    /* Card Detail Panel */
    .card-detail-panel {
        border-left: 4px solid #3b82f6;
        background: #fff;
        border-radius: 0 12px 12px 0;
        padding: 22px 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.06);
        transition: all 0.3s cubic-bezier(.4,0,.2,1);
    }
    .card-detail-panel:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.06), 0 8px 24px rgba(0,0,0,0.1); }
    .card-detail-panel.role-gpon { border-left-color: #22c55e; }
    .card-detail-panel.role-epon { border-left-color: #06b6d4; }
    .card-detail-panel.role-management { border-left-color: #eab308; }
    .card-detail-panel.role-power { border-left-color: #ef4444; }
    .card-detail-panel.role-fan { border-left-color: #a855f7; }

    .spec-grid { display: flex; flex-wrap: wrap; gap: 6px 20px; }
    .spec-item { font-size: 12px; }
    .spec-item .spec-label { color: #94a3b8; font-size: 11px; }
    .spec-item .spec-value { font-weight: 600; color: #1e293b; }

    /* VLAN / Uplink Tags */
    .uplink-vlan-tag {
        display: inline-block; padding: 3px 8px; margin: 2px; border-radius: 6px;
        font-size: 10px; font-weight: 600; background: #eff6ff; color: #2563eb;
        border: 1px solid rgba(37,99,235,0.12);
    }
    .uplink-vlan-tag.untagged { background: #f0fdf4; color: #16a34a; border-color: rgba(22,163,74,0.12); }
    .vlan-svc-badge { min-width: 28px; display: inline-block; text-align: center; }

    /* Port Membership Radio */
    .port-membership-row { transition: background 0.2s; }
    .port-membership-row:hover { background: #f8fafc !important; }
    .port-radio-group { display: flex; gap: 0; }
    .port-radio-group label {
        flex: 1; text-align: center; padding: 6px 14px; margin: 0;
        font-size: 11px; font-weight: 600; cursor: pointer; border: 1px solid #e2e8f0;
        transition: all 0.2s cubic-bezier(.4,0,.2,1); background: #fff; color: #94a3b8;
    }
    .port-radio-group label:first-child { border-radius: 6px 0 0 6px; }
    .port-radio-group label:last-child { border-radius: 0 6px 6px 0; }
    .port-radio-group label:not(:first-child) { border-left: 0; }
    .port-radio-group input[type="radio"] { display: none; }
    .port-radio-group label.active-tagged { background: #3b82f6; color: #fff; border-color: #3b82f6; box-shadow: 0 2px 8px rgba(59,130,246,0.3); }
    .port-radio-group label.active-untagged { background: #22c55e; color: #fff; border-color: #22c55e; box-shadow: 0 2px 8px rgba(34,197,94,0.3); }
    .port-radio-group label.active-none { background: #64748b; color: #fff; border-color: #64748b; }

    /* Stat Card Mini */
    .stat-mini {
        border-radius: 12px; padding: 12px 18px; text-align: center;
        transition: all 0.25s cubic-bezier(.4,0,.2,1); min-width: 80px;
        border: 1px solid transparent;
    }
    .stat-mini:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .stat-mini .stat-value { font-size: 22px; font-weight: 800; line-height: 1; letter-spacing: -0.5px; }
    .stat-mini .stat-value small { font-size: 12px; font-weight: 600; }
    .stat-mini .stat-label { font-size: 10px; color: #64748b; margin-top: 4px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-mini.stat-success { background: rgba(34,197,94,0.06); border-color: rgba(34,197,94,0.12); }
    .stat-mini.stat-success .stat-value { color: #16a34a; }
    .stat-mini.stat-primary { background: rgba(59,130,246,0.06); border-color: rgba(59,130,246,0.12); }
    .stat-mini.stat-primary .stat-value { color: #2563eb; }
    .stat-mini.stat-info { background: rgba(6,182,212,0.06); border-color: rgba(6,182,212,0.12); }
    .stat-mini.stat-info .stat-value { color: #0891b2; }
    .stat-mini.stat-warning { background: rgba(234,179,8,0.06); border-color: rgba(234,179,8,0.12); }
    .stat-mini.stat-warning .stat-value { color: #ca8a04; }
    .stat-mini.stat-danger { background: rgba(239,68,68,0.06); border-color: rgba(239,68,68,0.12); }
    .stat-mini.stat-danger .stat-value { color: #dc2626; }

    /* Section Title */
    .section-title {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 1.2px; color: #94a3b8; margin-bottom: 12px;
        padding-bottom: 8px; border-bottom: 2px solid #f1f5f9;
    }
    .section-title i { margin-right: 6px; color: #cbd5e1; }

    /* Infrastructure Tables */
    .infra-table { font-size: 13px; }
    .infra-table thead th {
        background: #f8fafc; border-bottom: 2px solid #e2e8f0;
        font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;
        color: #64748b; font-weight: 700; padding: 10px 12px;
    }
    .infra-table tbody td { padding: 10px 12px; vertical-align: middle; border-color: #f1f5f9; }
    .infra-table tbody tr:hover { background: #f8fafc; }

    /* VLAN Port List */
    .vlan-port-list {
        max-width: 260px; max-height: 60px; overflow: auto;
        font-size: 11px; line-height: 1.6; word-break: break-all;
    }

    /* Header Bar */
    .infra-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #334155 100%);
        border-radius: 12px; padding: 20px 24px; color: #fff;
        box-shadow: 0 4px 20px rgba(15,23,42,0.3);
    }
    .infra-header .olt-name { font-size: 20px; font-weight: 800; letter-spacing: -0.3px; }
    .infra-header .olt-ip { font-size: 13px; color: rgba(255,255,255,0.5); font-family: 'SFMono-Regular', monospace; }

    /* Card Section Headers */
    .card-section-header {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-radius: 10px; padding: 14px 18px; margin-bottom: 16px;
        border: 1px solid #e2e8f0;
    }
    .card-section-header h6 { font-weight: 700; color: #1e293b; margin: 0; font-size: 14px; }

    /* ── Stat-mini on DARK header background ── */
    .infra-header .stat-mini { border-color: rgba(255,255,255,0.14) !important; }
    .infra-header .stat-mini .stat-label { color: rgba(255,255,255,0.55) !important; }
    .infra-header .stat-mini.stat-success  { background: rgba(34,197,94,0.18)  !important; }
    .infra-header .stat-mini.stat-success  .stat-value { color: #4ade80 !important; }
    .infra-header .stat-mini.stat-primary  { background: rgba(59,130,246,0.18) !important; }
    .infra-header .stat-mini.stat-primary  .stat-value { color: #93c5fd !important; }
    .infra-header .stat-mini.stat-info     { background: rgba(6,182,212,0.18)  !important; }
    .infra-header .stat-mini.stat-info     .stat-value { color: #67e8f9 !important; }
    .infra-header .stat-mini.stat-warning  { background: rgba(234,179,8,0.18)  !important; }
    .infra-header .stat-mini.stat-warning  .stat-value { color: #fde047 !important; }
    .infra-header .stat-mini.stat-danger   { background: rgba(239,68,68,0.18)  !important; }
    .infra-header .stat-mini.stat-danger   .stat-value { color: #fca5a5 !important; }

    /* ── Mobile responsive tweaks ── */
    @media (max-width: 767.98px) {
        .infra-header { padding: 14px 16px; }
        .infra-header .olt-name { font-size: 16px; }
        .infra-header .olt-ip  { font-size: 12px; }
        .stat-mini { min-width: 60px; padding: 8px 10px; }
        .stat-mini .stat-value { font-size: 18px; }
        .shelf-container { padding: 14px 10px; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .shelf-slot { min-width: 68px; min-height: 88px; padding: 8px 4px; }
        .card-detail-panel { padding: 14px 14px; }
        .spec-grid { gap: 4px 10px; }
        .pon-port-cell { width: 44px; height: 44px; margin: 2px; }
        .pon-port-cell .port-num { font-size: 13px; }
        .vlan-port-list { max-width: 130px; max-height: 52px; }
        .infra-table { font-size: 12px; }
        .infra-table thead th,
        .infra-table tbody td { padding: 8px 8px; }
        .uplink-vlan-tag { font-size: 9px; padding: 2px 6px; }
        .port-radio-group label { padding: 5px 8px; font-size: 10px; }
    }
    @media (max-width: 991.98px) {
        .infra-header .d-flex.align-items-center.flex-wrap { gap: 8px !important; }
        .stat-mini { min-width: 70px; }
    }

    /* ── Select2 port multiselect ── */
    .port-select2 + .select2-container .select2-selection--multiple {
        border: 1px solid #e2e8f0; border-radius: 8px; min-height: 40px;
    }
    .port-select2 + .select2-container--focus .select2-selection--multiple {
        border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }
    #edit-tagged-select + .select2-container .select2-selection__choice { background: #eff6ff; border-color: rgba(37,99,235,0.3); color: #2563eb; }
    #edit-untagged-select + .select2-container .select2-selection__choice { background: #f0fdf4; border-color: rgba(22,163,74,0.3); color: #16a34a; }
    #create-tagged-select + .select2-container .select2-selection__choice { background: #eff6ff; border-color: rgba(37,99,235,0.3); color: #2563eb; }
    #create-untagged-select + .select2-container .select2-selection__choice { background: #f0fdf4; border-color: rgba(22,163,74,0.3); color: #16a34a; }

    /* ── Port Map Visualization ── */
    .port-map-wrap { background: #f8fafc; border-radius: 10px; padding: 12px 14px; border: 1px solid #e2e8f0; }
    .pm-section { margin-bottom: 8px; }
    .pm-section-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; color: #94a3b8; margin-bottom: 5px; }
    .pm-ports { display: flex; flex-wrap: wrap; gap: 3px; }
    .pm-port {
        width: 40px; height: 36px; border-radius: 6px; display: flex; flex-direction: column;
        align-items: center; justify-content: center; transition: transform 0.12s;
        border: 1.5px solid #e2e8f0; background: #fff; cursor: default;
    }
    .pm-port:hover { transform: scale(1.14); z-index: 2; box-shadow: 0 2px 8px rgba(0,0,0,0.12); }
    .pm-port-name { font-size: 9px; font-weight: 700; color: #94a3b8; line-height: 1; }
    .pm-port-mode { font-size: 9px; font-weight: 800; line-height: 1; margin-top: 2px; }
    .pm-port.pm-tagged { background: #eff6ff; border-color: #3b82f6; }
    .pm-port.pm-tagged .pm-port-name { color: #1d4ed8; }
    .pm-port.pm-tagged .pm-port-mode { color: #2563eb; }
    .pm-port.pm-untagged { background: #f0fdf4; border-color: #22c55e; }
    .pm-port.pm-untagged .pm-port-name { color: #15803d; }
    .pm-port.pm-untagged .pm-port-mode { color: #16a34a; }
    .pm-port.pm-none { opacity: 0.45; }
    .pm-uplink-port { width: 78px; }
    .pm-uplink-port .pm-port-name { font-size: 8px; word-break: break-all; text-align: center; }
    .pm-legend { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0; }
    .pm-legend-item { display: flex; align-items: center; gap: 4px; font-size: 10px; color: #64748b; }
    .pm-legend-dot { width: 9px; height: 9px; border-radius: 2px; }
    .pm-legend-dot.lg-t { background: #3b82f6; }
    .pm-legend-dot.lg-u { background: #22c55e; }
    .pm-legend-dot.lg-n { background: #e2e8f0; border: 1px solid #cbd5e1; }

    /* ═══════════════════════════════════════════════════════════
       VLAN DATABASE — network-themed redesign
    ═══════════════════════════════════════════════════════════ */
    .vlan-db-card { transition: box-shadow 0.2s; }

    /* Header stat chips */
    .vlan-hdr-stat {
        background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
        border-radius: 10px; padding: 8px 14px; text-align: center; min-width: 58px;
    }
    .vhs-val { font-size: 18px; font-weight: 800; color: #f0f9ff; line-height: 1; }
    .vhs-lbl { font-size: 9px; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 0.6px; margin-top: 2px; }
    .vhs-blue  .vhs-val { color: #7dd3fc; }
    .vhs-purple .vhs-val { color: #c4b5fd; }
    .vhs-green .vhs-val { color: #86efac; }
    .vhs-amber .vhs-val { color: #fde68a; }

    /* Search box in header */
    .vlan-search-wrap {
        display: flex; align-items: center; gap: 7px;
        background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.18);
        border-radius: 9px; padding: 7px 12px;
    }
    .vlan-search-wrap i { color: rgba(255,255,255,0.4); font-size: 11px; }
    .vlan-search-wrap input {
        background: transparent; border: none; outline: none;
        color: #fff; font-size: 12px; width: 170px;
    }
    .vlan-search-wrap input::placeholder { color: rgba(255,255,255,0.35); }

    /* VLAN table */
    .vlan-table { font-size: 13px; width: 100%; border-collapse: separate; border-spacing: 0; }
    .vlan-table thead th {
        background: #f8fafc; border-bottom: 2px solid #e2e8f0;
        font-size: 10px; text-transform: uppercase; letter-spacing: 0.7px;
        color: #64748b; font-weight: 700; padding: 10px 14px; white-space: nowrap;
        position: sticky; top: 0; z-index: 1;
    }
    .vlan-table th.vt-id { width: 68px; }
    .vlan-table th.vt-aksi { width: 70px; }
    .vlan-table tbody td { padding: 10px 14px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    .vlan-table tbody tr.vlan-row { transition: background 0.15s; }
    .vlan-table tbody tr.vlan-row:hover { background: #f8fafc; }
    .vlan-table tbody tr.vlan-row:hover .vlan-id-badge { transform: scale(1.06); }
    .vlan-table tbody tr.vlan-row.vlan-hidden { display: none; }

    /* VLAN ID badge */
    .vlan-id-badge {
        display: inline-flex; align-items: center; justify-content: center;
        width: 46px; height: 28px; border-radius: 8px; font-weight: 800;
        font-size: 13px; font-family: 'SFMono-Regular', monospace;
        transition: transform 0.15s; border-width: 1.5px; border-style: solid;
    }

    /* Type pill */
    .vlan-type-pill {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 9px; border-radius: 20px; font-size: 10px; font-weight: 700;
        border: 1px solid transparent; white-space: nowrap;
    }
    .vtp-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

    /* Service port count */
    .vlan-svc-num {
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg,#22c55e,#16a34a); color: #fff;
        width: 28px; height: 22px; border-radius: 6px; font-size: 11px; font-weight: 800;
        box-shadow: 0 2px 6px rgba(22,163,74,0.3);
    }

    /* Uplink trunk chips */
    .vlan-uplink-chip {
        display: inline-flex; align-items: center; gap: 3px;
        background: #fef3c7; color: #92400e; border: 1px solid rgba(217,119,6,0.25);
        border-radius: 5px; font-size: 10px; font-weight: 600; padding: 2px 7px;
        white-space: nowrap;
    }

    /* Port membership inline */
    .vlan-port-membership { line-height: 1; }
    .vpm-group { display: flex; align-items: flex-start; gap: 5px; }
    .vpm-label {
        display: inline-flex; align-items: center; justify-content: center;
        width: 18px; height: 18px; border-radius: 4px; font-size: 9px; font-weight: 800;
        flex-shrink: 0; margin-top: 1px;
    }
    .vpm-t { background: #eff6ff; color: #2563eb; border: 1px solid rgba(37,99,235,0.2); }
    .vpm-u { background: #f0fdf4; color: #16a34a; border: 1px solid rgba(22,163,74,0.2); }
    .vpm-ports { display: flex; flex-wrap: wrap; gap: 2px; }
    .vpm-chip {
        display: inline-block; padding: 1px 6px; border-radius: 4px;
        font-size: 10px; font-weight: 600; font-family: monospace; white-space: nowrap;
    }
    .vpm-chip-t { background: #eff6ff; color: #1d4ed8; border: 1px solid rgba(37,99,235,0.15); }
    .vpm-chip-u { background: #f0fdf4; color: #15803d; border: 1px solid rgba(22,163,74,0.15); }

    /* Action buttons */
    .vlan-actions { display: flex; gap: 4px; justify-content: center; }
    .vlan-act-btn {
        width: 28px; height: 28px; border-radius: 7px; border: 1.5px solid;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 11px; cursor: pointer; transition: all 0.15s; background: #fff;
    }
    .vlan-act-edit { border-color: rgba(14,165,233,0.35); color: #0ea5e9; }
    .vlan-act-edit:hover { background: #0ea5e9; color: #fff; border-color: #0ea5e9; }
    .vlan-act-del  { border-color: rgba(239,68,68,0.3); color: #ef4444; }
    .vlan-act-del:hover  { background: #ef4444; color: #fff; border-color: #ef4444; }

    /* Footer bar */
    .vlan-table-footer {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 16px; background: #f8fafc; border-top: 1px solid #e2e8f0;
        font-size: 11px; color: #64748b; border-radius: 0 0 16px 16px;
    }

    /* ═══════════════════════════════════════════════════════════
       UPLINK GRID — port-card style
    ═══════════════════════════════════════════════════════════ */
    .uplink-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px; padding: 16px;
    }
    .uplink-card {
        border-radius: 12px; border: 1.5px solid; padding: 14px;
        position: relative; transition: transform 0.15s, box-shadow 0.15s;
        background: #fff;
    }
    .uplink-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
    .uplink-up   { border-color: rgba(34,197,94,0.35); background: linear-gradient(145deg,#fff,#f0fdf4); }
    .uplink-down { border-color: rgba(239,68,68,0.25); background: linear-gradient(145deg,#fff,#fef2f2); }

    .uplink-icon-wrap { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
    .uplink-port-icon {
        width: 36px; height: 36px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        border: 1.5px solid; flex-shrink: 0;
    }
    .upicon-up   { background: rgba(34,197,94,0.12); border-color: rgba(34,197,94,0.35); color: #16a34a; }
    .upicon-down { background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.25); color: #dc2626; }
    .uplink-blink {
        width: 8px; height: 8px; border-radius: 50%;
        background: #22c55e; box-shadow: 0 0 6px rgba(34,197,94,0.6);
        animation: blink-anim 1.4s ease-in-out infinite;
    }
    .blink-off { background: #ef4444; box-shadow: 0 0 4px rgba(239,68,68,0.4); animation: none; }
    @@keyframes blink-anim { 0%,100%{opacity:1}50%{opacity:0.3} }

    .uplink-name { font-size: 13px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
    .uplink-speed-badge {
        font-size: 9px; font-weight: 700; padding: 1px 6px; border-radius: 4px;
        background: #e0e7ff; color: #4338ca;
    }

    .uplink-status-row { display: flex; flex-wrap: wrap; gap: 4px; margin: 6px 0; }
    .uplink-status-pill {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 9px; font-weight: 800; padding: 2px 8px; border-radius: 20px;
        letter-spacing: 0.5px;
    }
    .pill-up   { background: rgba(34,197,94,0.12); color: #16a34a; border: 1px solid rgba(34,197,94,0.3); }
    .pill-down { background: rgba(239,68,68,0.10); color: #dc2626; border: 1px solid rgba(239,68,68,0.25); }
    .blink-dot { width: 5px; height: 5px; border-radius: 50%; background: #22c55e; animation: blink-anim 1.4s infinite; flex-shrink: 0; }
    .uplink-mode-pill { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 4px; background: #1e293b; color: #94a3b8; }

    .uplink-vlans { display: flex; flex-wrap: wrap; gap: 3px; margin-top: 6px; }
    .uplink-vlan-num {
        display: inline-block; background: #eff6ff; color: #2563eb;
        border: 1px solid rgba(37,99,235,0.18); border-radius: 4px;
        font-size: 9px; font-weight: 700; padding: 1px 5px; font-family: monospace;
    }

    .uplink-traffic { font-size: 10px; margin-top: 6px; }
    .uplink-cfg-btn {
        position: absolute; top: 10px; right: 10px;
        width: 26px; height: 26px; border-radius: 7px; border: 1.5px solid rgba(22,163,74,0.3);
        background: rgba(22,163,74,0.08); color: #16a34a; font-size: 11px;
        display: flex; align-items: center; justify-content: center; cursor: pointer;
        transition: all 0.15s;
    }
    .uplink-cfg-btn:hover { background: #16a34a; color: #fff; border-color: #16a34a; }
</style>
@endpush

@section('content')
@php
    $totalOnu = $olt->cards->where('role', 'gpon')->sum(fn($c) => $c->ponPorts->sum('registered_onu'));
    $onlineOnu = $olt->cards->where('role', 'gpon')->sum(fn($c) => $c->ponPorts->sum('online_onu'));
    $activePon = $olt->cards->where('role', 'gpon')->sum(fn($c) => $c->ponPorts->where('registered_onu', '>', 0)->count());
    $totalPon = $olt->cards->where('role', 'gpon')->sum('port_count');

    // Build port option list for Select2 dropdowns
    $uplinkSet = $olt->uplinks->pluck('interface_name')->toArray();
    $portOptions = [];

    // Build port layout for visualization (grouped by GPON card)
    $portLayout = [];
    foreach ($olt->cards->where('role', 'gpon')->sortBy('slot') as $card) {
        $cardPorts = [];
        foreach ($card->ponPorts->sortBy('port') as $pp) {
            $cardPorts[] = ['name' => "gpon_{$pp->slot}/{$pp->port}", 'label' => "P{$pp->port}"];
        }
        if (!empty($cardPorts)) {
            $portLayout[] = [
                'slot'  => $card->slot,
                'type'  => $card->board_type ?? 'GPON',
                'ports' => $cardPorts,
            ];
        }
    }
    foreach ($orphanPonPorts->groupBy('slot') as $slot => $pps) {
        $orphanGroup = $pps->map(fn($pp) => ['name' => "gpon_{$pp->slot}/{$pp->port}", 'label' => "P{$pp->port}"])->values()->toArray();
        $portLayout[] = ['slot' => $slot, 'type' => 'Orphan', 'ports' => $orphanGroup];
    }
    $uplinkLayout = $olt->uplinks->map(fn($u) => [
        'name'  => $u->interface_name,
        'label' => $u->interface_name,
        'is10g' => (($u->interface_type ?? '') === 'xgei'),
    ])->values()->toArray();
    // PON ports  (gpon-onu_slot/port style derived from ONU records)
    $olt->onus->each(function($onu) use (&$portOptions) {
        $p = "gpon-onu_{$onu->slot}/{$onu->port}/{$onu->onu_id}";
        if (!in_array($p, $portOptions)) $portOptions[] = $p;
    });
    // PON port-level names (gpon_slot/port)
    foreach ($olt->cards as $card) {
        foreach ($card->ponPorts as $pp) {
            $p = "gpon_{$pp->slot}/{$pp->port}";
            if (!in_array($p, $portOptions)) $portOptions[] = $p;
        }
    }
    // Uplinks as options too (all)
    foreach ($uplinkSet as $u) {
        if (!in_array($u, $portOptions)) $portOptions[] = $u;
    }
    // Orphan PON ports
    foreach ($orphanPonPorts as $pp) {
        $p = "gpon_{$pp->slot}/{$pp->port}";
        if (!in_array($p, $portOptions)) $portOptions[] = $p;
    }
    sort($portOptions);
@endphp

{{-- ================================================== --}}
{{-- MODALS --}}
{{-- ================================================== --}}

<!-- Progress Modal -->
<div class="modal fade" id="modal-progress" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #0f172a, #1e293b); border: none;">
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
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #0891b2, #06b6d4); border: none;">
                <h5 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit VLAN <span id="edit-vlan-title-id" class="font-weight-bold"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-vlan-id">

                {{-- Row 1: Basic Info --}}
                <div class="row mb-1">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="font-weight-bold small">VLAN ID</label>
                            <input type="text" class="form-control font-weight-bold text-center" id="edit-vlan-display" readonly style="font-size:20px; background:#f8fafc;">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold small">Nama</label>
                            <input type="text" class="form-control" id="edit-vlan-name" placeholder="Nama VLAN">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-bold small">Tipe <span class="text-danger">*</span></label>
                            <select class="form-control" id="edit-vlan-type">
                                <option value="service">Service (Internet)</option>
                                <option value="management">Management</option>
                                <option value="voip">VoIP</option>
                                <option value="iptv">IPTV</option>
                                <option value="infra">Infrastructure</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-bold small">Keterangan</label>
                            <input type="text" class="form-control" id="edit-vlan-description" placeholder="Deskripsi">
                        </div>
                    </div>
                </div>

                {{-- Port Membership --}}
                <div class="section-title mt-1">
                    <i class="fas fa-exchange-alt"></i>Port Membership
                </div>

                @if($olt->uplinks->isNotEmpty())
                {{-- Uplink radio buttons --}}
                <div class="mb-3">
                    <label class="small font-weight-bold text-muted mb-2 d-block">
                        <i class="fas fa-arrow-up mr-1"></i>Uplink Ports
                    </label>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                            <thead>
                                <tr>
                                    <th style="width:38%">Interface</th>
                                    <th class="text-center" style="width:15%">Status</th>
                                    <th>Mode</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($olt->uplinks as $uplink)
                                @php $slug = Str::slug($uplink->interface_name); @endphp
                                <tr class="port-membership-row">
                                    <td>
                                        <strong>{{ $uplink->interface_name }}</strong>
                                        @if($uplink->interface_type === 'xgei')
                                            <span class="badge badge-info ml-1" style="font-size:9px;">10G</span>
                                        @endif
                                        @if($uplink->description)
                                            <br><small class="text-muted">{{ $uplink->description }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{!! $uplink->status_badge !!}</td>
                                    <td>
                                        <div class="port-radio-group">
                                            <label class="mb-0">
                                                <input type="radio" name="port_{{ $slug }}" value="tagged" data-port="{{ $uplink->interface_name }}" class="port-radio">
                                                <span class="d-block"><i class="fas fa-tag mr-1"></i>Tagged</span>
                                            </label>
                                            <label class="mb-0">
                                                <input type="radio" name="port_{{ $slug }}" value="untagged" data-port="{{ $uplink->interface_name }}" class="port-radio">
                                                <span class="d-block"><i class="fas fa-minus-circle mr-1"></i>Untagged</span>
                                            </label>
                                            <label class="mb-0">
                                                <input type="radio" name="port_{{ $slug }}" value="none" data-port="{{ $uplink->interface_name }}" class="port-radio" checked>
                                                <span class="d-block"><i class="fas fa-times mr-1"></i>None</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- Port select2 dropdowns --}}
                <div class="row">
                    <div class="col-md-6">
                        <label class="small font-weight-bold mb-1">
                            <span style="color:#2563eb;"><i class="fas fa-tag mr-1"></i>Tagged Ports</span>
                            @if($olt->uplinks->isNotEmpty())
                                <small class="text-muted font-weight-normal">(non-uplink)</small>
                            @endif
                        </label>
                        <select id="edit-tagged-select" class="form-control port-select2" multiple style="width:100%">
                            @foreach($portOptions as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small font-weight-bold mb-1">
                            <span style="color:#16a34a;"><i class="fas fa-minus-circle mr-1"></i>Untagged Ports</span>
                            @if($olt->uplinks->isNotEmpty())
                                <small class="text-muted font-weight-normal">(non-uplink)</small>
                            @endif
                        </label>
                        <select id="edit-untagged-select" class="form-control port-select2" multiple style="width:100%">
                            @foreach($portOptions as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <small class="form-text text-muted mt-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Push via <strong>{{ $olt->snmp_community_rw ? 'SNMP SET (Q-BRIDGE)' : 'Telnet CLI' }}</strong>.
                </small>

                {{-- Port Map Visualization --}}
                <div class="port-map-wrap mt-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;">
                            <i class="fas fa-sitemap mr-1"></i>Peta Port
                        </span>
                        <small class="text-muted" style="font-size:10px;">Update otomatis saat pilih port</small>
                    </div>
                    <div id="edit-port-map-body">
                        <p class="text-muted small text-center mb-0" style="font-size:11px;">Buka Edit VLAN untuk melihat peta port</p>
                    </div>
                    <div class="pm-legend">
                        <div class="pm-legend-item"><div class="pm-legend-dot lg-t"></div>Tagged</div>
                        <div class="pm-legend-item"><div class="pm-legend-dot lg-u"></div>Untagged</div>
                        <div class="pm-legend-item"><div class="pm-legend-dot lg-n"></div>None</div>
                    </div>
                </div>
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
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #16a34a, #22c55e); border: none;">
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
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #2563eb, #3b82f6); border: none;">
                <h5 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Buat VLAN Baru</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                {{-- Row 1: Basic Info --}}
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="font-weight-bold small">VLAN ID <span class="text-danger">*</span></label>
                            <input type="number" class="form-control font-weight-bold text-center" id="create-vlan-id" min="2" max="4094" required style="font-size:18px;">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold small">Nama</label>
                            <input type="text" class="form-control" id="create-vlan-name" placeholder="Opsional">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-bold small">Tipe</label>
                            <select class="form-control" id="create-vlan-type">
                                <option value="service">Service (Internet)</option>
                                <option value="management">Management</option>
                                <option value="voip">VoIP</option>
                                <option value="iptv">IPTV</option>
                                <option value="infra">Infrastructure</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-bold small">Keterangan</label>
                            <input type="text" class="form-control" id="create-vlan-desc" placeholder="Opsional">
                        </div>
                    </div>
                </div>

                {{-- Port Membership --}}
                <div class="section-title mt-1">
                    <i class="fas fa-exchange-alt"></i>Port Membership <small class="text-muted font-weight-normal">(opsional)</small>
                </div>

                @if($olt->uplinks->isNotEmpty())
                <div class="mb-3">
                    <label class="small font-weight-bold text-muted mb-2 d-block">
                        <i class="fas fa-arrow-up mr-1"></i>Uplink Ports
                    </label>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                            <thead>
                                <tr>
                                    <th style="width:38%">Interface</th>
                                    <th class="text-center" style="width:15%">Status</th>
                                    <th>Mode</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($olt->uplinks as $uplink)
                                @php $slugC = 'c_' . Str::slug($uplink->interface_name); @endphp
                                <tr class="port-membership-row">
                                    <td>
                                        <strong>{{ $uplink->interface_name }}</strong>
                                        @if($uplink->interface_type === 'xgei')
                                            <span class="badge badge-info ml-1" style="font-size:9px;">10G</span>
                                        @endif
                                        @if($uplink->description)
                                            <br><small class="text-muted">{{ $uplink->description }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{!! $uplink->status_badge !!}</td>
                                    <td>
                                        <div class="port-radio-group">
                                            <label class="mb-0">
                                                <input type="radio" name="create_port_{{ $slugC }}" value="tagged" data-port="{{ $uplink->interface_name }}" class="create-port-radio">
                                                <span class="d-block"><i class="fas fa-tag mr-1"></i>Tagged</span>
                                            </label>
                                            <label class="mb-0">
                                                <input type="radio" name="create_port_{{ $slugC }}" value="untagged" data-port="{{ $uplink->interface_name }}" class="create-port-radio">
                                                <span class="d-block"><i class="fas fa-minus-circle mr-1"></i>Untagged</span>
                                            </label>
                                            <label class="mb-0">
                                                <input type="radio" name="create_port_{{ $slugC }}" value="none" data-port="{{ $uplink->interface_name }}" class="create-port-radio" checked>
                                                <span class="d-block"><i class="fas fa-times mr-1"></i>None</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <label class="small font-weight-bold mb-1">
                            <span style="color:#2563eb;"><i class="fas fa-tag mr-1"></i>Tagged Ports</span>
                            @if($olt->uplinks->isNotEmpty())
                                <small class="text-muted font-weight-normal">(non-uplink)</small>
                            @endif
                        </label>
                        <select id="create-tagged-select" class="form-control port-select2" multiple style="width:100%">
                            @foreach($portOptions as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small font-weight-bold mb-1">
                            <span style="color:#16a34a;"><i class="fas fa-minus-circle mr-1"></i>Untagged Ports</span>
                            @if($olt->uplinks->isNotEmpty())
                                <small class="text-muted font-weight-normal">(non-uplink)</small>
                            @endif
                        </label>
                        <select id="create-untagged-select" class="form-control port-select2" multiple style="width:100%">
                            @foreach($portOptions as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Port Map Visualization --}}
                <div class="port-map-wrap mt-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;">
                            <i class="fas fa-sitemap mr-1"></i>Peta Port
                        </span>
                        <small class="text-muted" style="font-size:10px;">Update otomatis saat pilih port</small>
                    </div>
                    <div id="create-port-map-body">
                        <p class="text-muted small text-center mb-0" style="font-size:11px;">Pilih port di atas untuk melihat peta</p>
                    </div>
                    <div class="pm-legend">
                        <div class="pm-legend-item"><div class="pm-legend-dot lg-t"></div>Tagged</div>
                        <div class="pm-legend-item"><div class="pm-legend-dot lg-u"></div>Untagged</div>
                        <div class="pm-legend-item"><div class="pm-legend-dot lg-n"></div>None</div>
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
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #ef4444); border: none;">
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
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #ef4444); border: none;">
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
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #d97706, #f59e0b); border: none;">
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

{{-- ================================================== --}}
{{-- HEADER BAR --}}
{{-- ================================================== --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="infra-header mb-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <div class="d-flex align-items-center mb-1">
                        <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 14px;">
                            <i class="fas fa-server" style="font-size: 18px; color: #60a5fa;"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 olt-name">{{ $olt->name }}</h4>
                            <span class="olt-ip">
                                {{ $olt->ip_address }} &middot; {{ $olt->brand }} {{ $olt->model }}
                                @if($olt->cards->first()?->software_version)
                                    &middot; FW {{ $olt->cards->first()->software_version }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center flex-wrap mt-2 mt-md-0" style="gap: 10px;">
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
                    <div class="d-flex ml-2" style="gap: 8px;">
                        <a href="{{ route('admin.olts.show', $olt) }}" class="btn btn-sm" style="background: rgba(255,255,255,0.08); color: #94a3b8; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;">
                            <i class="fas fa-arrow-left mr-1"></i>Detail
                        </a>
                        <button type="button" class="btn btn-sm font-weight-bold" id="btn-sync-infra" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; border: none; border-radius: 8px; box-shadow: 0 2px 10px rgba(59,130,246,0.4);">
                            <i class="fas fa-sync mr-1"></i>Sync dari OLT
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ================================================== --}}
{{-- SHELF VISUAL + ALL CARD DETAILS --}}
{{-- ================================================== --}}
<div class="row">
    <div class="col-12">
        <div class="card" style="border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.06);">
            <div class="card-header" style="background: #fff; border-bottom: 1px solid #f1f5f9; border-radius: 14px 14px 0 0; padding: 16px 20px;">
                <h3 class="card-title" style="font-size: 15px; font-weight: 700; color: #1e293b;">
                    <i class="fas fa-microchip mr-2" style="color: #64748b;"></i>Kartu & Slot
                </h3>
                <div class="card-tools">
                    @if($olt->cards->where('last_sync_at', '!=', null)->first())
                        <span class="text-muted text-sm">
                            <i class="far fa-clock mr-1"></i>{{ $olt->cards->first()->last_sync_at?->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body" style="padding: 20px;">
                @if($olt->cards->isEmpty())
                    <div class="text-center py-5">
                        <div style="width: 64px; height: 64px; background: #f1f5f9; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                            <i class="fas fa-server fa-2x" style="color: #94a3b8;"></i>
                        </div>
                        <p class="text-muted mb-0">Belum ada data kartu. Klik <strong>"Sync dari OLT"</strong> untuk mengambil data.</p>
                    </div>
                @else
                    <!-- Shelf Visual -->
                    <div class="shelf-container mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="font-weight-bold" style="font-size: 12px; letter-spacing: 1px; text-transform: uppercase; color: #475569;">
                                <i class="fas fa-th mr-2" style="color: #94a3b8;"></i>RACK {{ $olt->cards->first()->rack ?? 1 }} / SHELF {{ $olt->cards->first()->shelf ?? 1 }}
                            </span>
                            <div class="ml-auto d-flex align-items-center" style="gap: 16px;">
                                <small style="color: #64748b; font-size: 11px;"><i class="fas fa-circle mr-1" style="color: #22c55e; font-size: 7px;"></i>GPON</small>
                                <small style="color: #64748b; font-size: 11px;"><i class="fas fa-circle mr-1" style="color: #f59e0b; font-size: 7px;"></i>MGMT</small>
                                <small style="color: #64748b; font-size: 11px;"><i class="fas fa-circle mr-1" style="color: #ef4444; font-size: 7px;"></i>Power</small>
                                <span class="badge" style="background: #e2e8f0; color: #64748b; font-size: 11px; padding: 4px 10px; border-radius: 6px;">{{ $olt->cards->count() }}/20 slot terisi</span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap" style="gap: 8px;">
                            @for($slot = 1; $slot <= 20; $slot++)
                                @php $card = $olt->cards->firstWhere('slot', $slot); @endphp
                                <div class="shelf-slot {{ $card ? ($card->status === 'standby' ? 'standby' : 'active-' . $card->role) : 'empty-slot' }}"
                                     data-slot="{{ $slot }}"
                                     @if($card) data-card-id="{{ $card->id }}" title="Klik untuk detail &#10;{{ $card->real_type ?: $card->configured_type }} - {{ $card->port_count }} port - {{ ucfirst($card->status) }}" @endif>
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
                                            <span style="font-size: 18px; color: rgba(255,255,255,0.1);">-</span>
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
                                        <span class="text-muted mx-1">-</span>
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

                        {{-- - GPON/EPON: PON Ports - --}}
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

                        {{-- - Management Card: Uplink Ports - --}}
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

                        {{-- Other Card Types --}}
                        @if(!$isGpon && !$isMgmt)
                            @if($card->status === 'standby')
                            <div class="text-muted text-sm mt-1">
                                <i class="fas fa-pause-circle mr-1 text-warning"></i>Mode standby - siap sebagai backup.
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

{{-- ================================================== --}}
{{-- VLAN DATABASE + UPLINK SUMMARY --}}
{{-- ================================================== --}}
@php
    $vlanStats = [
        'total'    => $olt->vlans->count(),
        'service'  => $olt->vlans->where('type', 'service')->count(),
        'mgmt'     => $olt->vlans->whereIn('type', ['management','infra'])->count(),
        'svcPorts' => $olt->vlans->sum('service_port_count'),
        'withUplink'=> $olt->vlans->filter(fn($v) => !empty($v->uplink_ports))->count(),
    ];
@endphp
<div class="row">
    <!-- VLAN Database -->
    <div class="col-12">
    <div class="card vlan-db-card" style="border:none; border-radius:16px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06),0 8px 32px rgba(0,0,0,0.08);">

        {{-- ── Card Header ── --}}
        <div class="vlan-db-header" style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 60%,#0c4a6e 100%); padding:20px 24px; position:relative; overflow:hidden;">
            {{-- Network grid decoration --}}
            <div style="position:absolute;inset:0;opacity:0.04;background-image:repeating-linear-gradient(0deg,transparent,transparent 24px,#fff 24px,#fff 25px),repeating-linear-gradient(90deg,transparent,transparent 24px,#fff 24px,#fff 25px);pointer-events:none;"></div>

            <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px; position:relative;">
                {{-- Title --}}
                <div class="d-flex align-items-center" style="gap:14px;">
                    <div style="width:44px;height:44px;background:rgba(14,165,233,0.18);border:1.5px solid rgba(14,165,233,0.4);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-tags" style="color:#38bdf8;font-size:18px;"></i>
                    </div>
                    <div>
                        <div style="font-size:17px;font-weight:800;color:#f0f9ff;letter-spacing:-0.3px;">VLAN Database</div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.45);font-family:monospace;">{{ $olt->name }} · {{ $olt->ip_address }}</div>
                    </div>
                </div>

                {{-- Stats row --}}
                <div class="d-flex flex-wrap" style="gap:8px;">
                    <div class="vlan-hdr-stat">
                        <div class="vhs-val">{{ $vlanStats['total'] }}</div>
                        <div class="vhs-lbl">Total VLAN</div>
                    </div>
                    <div class="vlan-hdr-stat vhs-blue">
                        <div class="vhs-val">{{ $vlanStats['service'] }}</div>
                        <div class="vhs-lbl">Service</div>
                    </div>
                    <div class="vlan-hdr-stat vhs-purple">
                        <div class="vhs-val">{{ $vlanStats['mgmt'] }}</div>
                        <div class="vhs-lbl">Mgmt/Infra</div>
                    </div>
                    <div class="vlan-hdr-stat vhs-green">
                        <div class="vhs-val">{{ $vlanStats['svcPorts'] }}</div>
                        <div class="vhs-lbl">Svc Ports</div>
                    </div>
                    <div class="vlan-hdr-stat vhs-amber">
                        <div class="vhs-val">{{ $vlanStats['withUplink'] }}</div>
                        <div class="vhs-lbl">Trunk</div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="d-flex align-items-center" style="gap:8px;">
                    <div class="vlan-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="vlan-search-input" placeholder="Cari VLAN, nama, port..." autocomplete="off">
                    </div>
                    <button type="button" id="btn-open-create-vlan"
                        style="background:linear-gradient(135deg,#0ea5e9,#2563eb);border:none;color:#fff;border-radius:9px;padding:8px 16px;font-size:12px;font-weight:700;letter-spacing:0.3px;white-space:nowrap;cursor:pointer;box-shadow:0 2px 8px rgba(14,165,233,0.35);">
                        <i class="fas fa-plus mr-1"></i>Buat VLAN
                    </button>
                </div>
            </div>
        </div>

        {{-- ── VLAN Table ── --}}
        <div class="card-body p-0" style="background:#fff;">
            @if($olt->vlans->isEmpty())
                <div class="text-center py-5">
                    <div style="width:64px;height:64px;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;border:2px dashed #cbd5e1;">
                        <i class="fas fa-network-wired fa-xl" style="color:#94a3b8;"></i>
                    </div>
                    <p class="font-weight-700 mb-1" style="color:#1e293b;">Belum ada data VLAN</p>
                    <p class="text-muted small mb-0">Sync infrastruktur dari OLT atau buat VLAN baru</p>
                </div>
            @else
            <div class="table-responsive">
                <table class="table vlan-table mb-0" id="vlan-table-main">
                    <thead>
                        <tr>
                            <th class="vt-id">ID</th>
                            <th>Nama / Keterangan</th>
                            <th class="text-center">Tipe</th>
                            <th class="text-center">Svc</th>
                            <th>Uplink Trunk</th>
                            <th>Port Membership</th>
                            <th class="text-center vt-aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($olt->vlans as $vlan)
                        @php
                            $typeColor = match($vlan->type ?? 'other') {
                                'service'    => ['bg'=>'#eff6ff','fg'=>'#2563eb','dot'=>'#3b82f6'],
                                'management' => ['bg'=>'#faf5ff','fg'=>'#7c3aed','dot'=>'#8b5cf6'],
                                'voip'       => ['bg'=>'#fff7ed','fg'=>'#c2410c','dot'=>'#f97316'],
                                'iptv'       => ['bg'=>'#f0fdf4','fg'=>'#16a34a','dot'=>'#22c55e'],
                                'infra'      => ['bg'=>'#f8fafc','fg'=>'#475569','dot'=>'#64748b'],
                                default      => ['bg'=>'#fafafa','fg'=>'#6b7280','dot'=>'#9ca3af'],
                            };
                            $hasTrunk  = !empty($vlan->uplink_ports);
                            $taggedCnt = count($vlan->tagged_ports ?? []);
                            $untgdCnt  = count($vlan->untagged_ports ?? []);
                        @endphp
                        <tr class="vlan-row" data-search="{{ strtolower($vlan->vlan_id.' '.$vlan->name.' '.implode(' ',$vlan->tagged_ports??[]).' '.implode(' ',$vlan->untagged_ports??[]).' '.implode(' ',$vlan->uplink_ports??[])) }}">
                            {{-- VLAN ID --}}
                            <td class="vt-id">
                                <div class="vlan-id-badge" style="background:{{ $typeColor['bg'] }};border:1.5px solid {{ $typeColor['dot'] }}30;color:{{ $typeColor['fg'] }};">
                                    {{ $vlan->vlan_id }}
                                </div>
                            </td>

                            {{-- Name + desc --}}
                            <td>
                                <div class="font-weight-700" style="font-size:13px;color:#1e293b;line-height:1.2;">
                                    {{ $vlan->name ?: 'VLAN'.$vlan->vlan_id }}
                                </div>
                                @if($vlan->description)
                                    <div style="font-size:11px;color:#94a3b8;margin-top:2px;">{{ $vlan->description }}</div>
                                @endif
                            </td>

                            {{-- Type badge --}}
                            <td class="text-center">
                                <span class="vlan-type-pill" style="background:{{ $typeColor['bg'] }};color:{{ $typeColor['fg'] }};border-color:{{ $typeColor['dot'] }}30;">
                                    <span class="vtp-dot" style="background:{{ $typeColor['dot'] }};"></span>
                                    {{ ucfirst($vlan->type ?? 'other') }}
                                </span>
                            </td>

                            {{-- Service ports --}}
                            <td class="text-center">
                                @if($vlan->service_port_count > 0)
                                    <div class="vlan-svc-num">{{ $vlan->service_port_count }}</div>
                                @else
                                    <span style="color:#cbd5e1;font-size:12px;">—</span>
                                @endif
                            </td>

                            {{-- Uplink (trunk) ports --}}
                            <td>
                                @if($hasTrunk)
                                    <div class="d-flex flex-wrap" style="gap:3px;">
                                        @foreach($vlan->uplink_ports as $up)
                                            <span class="vlan-uplink-chip">
                                                <i class="fas fa-arrow-up" style="font-size:7px;opacity:.6;"></i>
                                                {{ Str::limit($up, 16) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color:#cbd5e1;font-size:12px;">—</span>
                                @endif
                            </td>

                            {{-- Port Membership (tagged + untagged mini) --}}
                            <td style="max-width:220px;">
                                @if($taggedCnt > 0 || $untgdCnt > 0)
                                    <div class="vlan-port-membership">
                                        @if($taggedCnt > 0)
                                            <div class="vpm-group">
                                                <span class="vpm-label vpm-t">T</span>
                                                <span class="vpm-ports">
                                                    @foreach(array_slice($vlan->tagged_ports, 0, 3) as $tp)
                                                        <span class="vpm-chip vpm-chip-t" title="{{ $tp }}">{{ Str::limit($tp, 14) }}</span>
                                                    @endforeach
                                                    @if($taggedCnt > 3)
                                                        <span class="vpm-chip vpm-chip-t" style="opacity:.6;">+{{ $taggedCnt-3 }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        @endif
                                        @if($untgdCnt > 0)
                                            <div class="vpm-group mt-1">
                                                <span class="vpm-label vpm-u">U</span>
                                                <span class="vpm-ports">
                                                    @foreach(array_slice($vlan->untagged_ports, 0, 3) as $up)
                                                        <span class="vpm-chip vpm-chip-u" title="{{ $up }}">{{ Str::limit($up, 14) }}</span>
                                                    @endforeach
                                                    @if($untgdCnt > 3)
                                                        <span class="vpm-chip vpm-chip-u" style="opacity:.6;">+{{ $untgdCnt-3 }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span style="color:#cbd5e1;font-size:12px;">—</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="text-center vt-aksi">
                                <div class="vlan-actions">
                                    <button class="vlan-act-btn vlan-act-edit btn-edit-vlan"
                                            data-id="{{ $vlan->id }}"
                                            data-vlan-id="{{ $vlan->vlan_id }}"
                                            data-name="{{ $vlan->name }}"
                                            data-type="{{ $vlan->type }}"
                                            data-description="{{ $vlan->description }}"
                                            data-tagged="{{ json_encode($vlan->tagged_ports ?? []) }}"
                                            data-untagged="{{ json_encode($vlan->untagged_ports ?? []) }}"
                                            data-uplink="{{ json_encode($vlan->uplink_ports ?? []) }}"
                                            title="Edit VLAN">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="vlan-act-btn vlan-act-del btn-delete-vlan"
                                            data-id="{{ $vlan->id }}"
                                            data-vlan-id="{{ $vlan->vlan_id }}"
                                            data-svc="{{ $vlan->service_port_count }}"
                                            title="Hapus VLAN">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Footer bar --}}
            <div class="vlan-table-footer">
                <span id="vlan-count-label">{{ $olt->vlans->count() }} VLAN</span>
                <span>Total service-port: <strong>{{ $olt->vlans->sum('service_port_count') }}</strong></span>
            </div>
            @endif
        </div>
    </div>
    </div>

    <!-- Uplink Summary -->
    <div class="col-12 mt-3">
        <div class="card" style="border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.06);">
            <div class="card-header" style="background: linear-gradient(135deg,#052e16,#14532d,#166534); border-radius:14px 14px 0 0; padding: 14px 20px; border:none;">
                <h3 class="card-title mb-0" style="font-size: 14px; font-weight: 700; color:#dcfce7;">
                    <i class="fas fa-arrow-up mr-2" style="color:#4ade80;"></i>Uplink Ports
                    <span class="ml-2" style="font-size:11px;font-weight:500;color:rgba(255,255,255,0.5);">— Interface trunk ke upstream</span>
                </h3>
                <div class="card-tools">
                    <span class="badge" style="background:rgba(74,222,128,0.15);color:#4ade80;border:1px solid rgba(74,222,128,0.3);padding:4px 10px;border-radius:6px;font-size:11px;">{{ $olt->uplinks->where('status', 'up')->count() }}<span style="opacity:.5;">/{{ $olt->uplinks->count() }}</span> Up</span>
                </div>
            </div>
            <div class="card-body p-0">
                @if($olt->uplinks->isEmpty())
                    <div class="text-center py-5">
                        <div style="width: 56px; height: 56px; background: #f1f5f9; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                            <i class="fas fa-arrow-up fa-lg" style="color: #94a3b8;"></i>
                        </div>
                        <p class="text-muted mb-0">Belum ada data uplink.</p>
                    </div>
                @else
                <div class="card-body p-0">
                @if($olt->uplinks->isEmpty())
                    <div class="text-center py-4">
                        <i class="fas fa-plug" style="color:#94a3b8;font-size:28px;"></i>
                        <p class="text-muted small mt-2 mb-0">Belum ada data uplink. Sync dari OLT terlebih dahulu.</p>
                    </div>
                @else
                <div class="uplink-grid">
                    @foreach($olt->uplinks as $uplink)
                    @php
                        $isUp  = $uplink->status === 'up';
                        $is10g = ($uplink->interface_type ?? '') === 'xgei';
                        $vlanCount = count($uplink->tagged_vlans ?? []);
                    @endphp
                    <div class="uplink-card {{ $isUp ? 'uplink-up' : 'uplink-down' }}">
                        {{-- Port visual icon --}}
                        <div class="uplink-icon-wrap">
                            <div class="uplink-port-icon {{ $isUp ? 'upicon-up' : 'upicon-down' }}">
                                <i class="fas {{ $is10g ? 'fa-bolt' : 'fa-ethernet' }}" style="font-size:16px;"></i>
                            </div>
                            <div class="uplink-blink {{ $isUp ? '' : 'blink-off' }}"></div>
                        </div>

                        {{-- Interface name + speed --}}
                        <div class="uplink-name">
                            <strong>{{ $uplink->interface_name }}</strong>
                            @if($is10g)<span class="uplink-speed-badge">10G</span>@else<span class="uplink-speed-badge" style="background:#e0f2fe;color:#0369a1;">1G</span>@endif
                        </div>

                        {{-- Status pill --}}
                        <div class="uplink-status-row">
                            @if($isUp)
                                <span class="uplink-status-pill pill-up"><span class="blink-dot"></span>UP</span>
                            @else
                                <span class="uplink-status-pill pill-down">DOWN</span>
                            @endif
                            @if($uplink->switchport_mode === 'trunk')
                                <span class="uplink-mode-pill">TRUNK</span>
                            @endif
                            @if($uplink->native_vlan)
                                <span class="uplink-mode-pill" style="background:#fef3c7;color:#92400e;">PVID {{ $uplink->native_vlan }}</span>
                            @endif
                        </div>

                        {{-- Tagged VLANs --}}
                        @if($vlanCount > 0)
                        <div class="uplink-vlans">
                            @foreach(array_slice($uplink->tagged_vlans, 0, 8) as $vid)
                                <span class="uplink-vlan-num">{{ $vid }}</span>
                            @endforeach
                            @if($vlanCount > 8)
                                <span class="uplink-vlan-num" style="opacity:.5;">+{{ $vlanCount-8 }}</span>
                            @endif
                        </div>
                        @endif

                        {{-- Traffic --}}
                        @if($uplink->in_rate_bps || $uplink->out_rate_bps)
                        <div class="uplink-traffic">
                            <span style="color:#22c55e;"><i class="fas fa-arrow-down" style="font-size:9px;"></i> {{ $uplink->in_rate_formatted ?? '-' }}</span>
                            <span style="color:#60a5fa;margin-left:8px;"><i class="fas fa-arrow-up" style="font-size:9px;"></i> {{ $uplink->out_rate_formatted ?? '-' }}</span>
                        </div>
                        @endif

                        {{-- Config button --}}
                        <button class="uplink-cfg-btn btn-configure-uplink"
                                data-id="{{ $uplink->id }}"
                                data-name="{{ $uplink->interface_name }}"
                                data-mode="{{ $uplink->switchport_mode ?? 'trunk' }}"
                                data-vlans="{{ $uplink->tagged_vlans ? implode(', ', $uplink->tagged_vlans) : '' }}"
                                data-admin="{{ $uplink->admin_status ?? 'enabled' }}"
                                data-pvid="{{ $uplink->native_vlan ?? '' }}"
                                title="Konfigurasi Uplink">
                            <i class="fas fa-sliders-h"></i>
                        </button>
                    </div>
                    @endforeach
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
    // VLAN Live Search
    // =========================================================================
    $('#vlan-search-input').on('input', function() {
        let q = $(this).val().toLowerCase().trim();
        let visible = 0;
        $('.vlan-row').each(function() {
            let hay = $(this).data('search') || '';
            let show = !q || hay.includes(q);
            $(this).toggleClass('vlan-hidden', !show);
            if (show) visible++;
        });
        let total = {{ $olt->vlans->count() }};
        $('#vlan-count-label').text(q ? visible + ' / ' + total + ' VLAN' : total + ' VLAN');
    });
            $(this).addClass('selected');
            $('html, body').animate({ scrollTop: target.offset().top - 80 }, 400);
            target.css('box-shadow', '0 0 0 3px #007bff, 0 4px 16px rgba(0,123,255,0.2)');
            setTimeout(() => target.css('box-shadow', ''), 2500);
        }
    });

    // =========================================================================
    // Port Radio Active State
    // =========================================================================
    // Port Radio Change (also updates port map)
    // =========================================================================
    $(document).on('change', '.port-radio', function() {
        let group = $(this).closest('.port-radio-group');
        group.find('label').removeClass('active-tagged active-untagged active-none');
        $(this).closest('label').addClass('active-' + $(this).val());
        buildPortMap('edit');
    });
    $(document).on('change', '.create-port-radio', function() {
        let group = $(this).closest('.port-radio-group');
        group.find('label').removeClass('active-tagged active-untagged active-none');
        $(this).closest('label').addClass('active-' + $(this).val());
        buildPortMap('create');
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
                let pct = data.percent + '%';
                $('#progress-bar').css('width', pct).text(pct);

                // Auto-detect step from message
                let msg = (data.message || '').toLowerCase();
                if (msg.includes('kartu') || msg.includes('show card')) {
                    if (!steps.cards) { setStepActive('cards'); steps.cards = true; }
                    if (msg.includes('tersinkronisasi')) setStepDone('cards', data.message.match(/(\d+)/)?.[1] + ' slot');
                }
                if (msg.includes('vlan') || msg.includes('service-port')) {
                    if (!steps.vlans) { setStepActive('vlans'); steps.vlans = true; }
                    if (msg.includes('tersinkronisasi') && !msg.includes('selesai')) setStepDone('vlans', 'Selesai');
                }
                if (msg.includes('uplink') || msg.includes('interface brief')) {
                    if (!steps.uplinks) { setStepActive('uplinks'); steps.uplinks = true; }
                    if (msg.includes('tersinkronisasi')) setStepDone('uplinks', data.message.match(/(\d+)/)?.[1] + ' port');
                }
                if (msg.includes('pon') || msg.includes('status onu')) {
                    if (!steps.pon) { setStepActive('pon'); steps.pon = true; }
                    if (msg.includes('tersinkronisasi')) setStepDone('pon', data.message.match(/(\d+)/)?.[1] + ' port');
                }
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
    // Select2 Port Dropdowns
    // =========================================================================
    let uplinkNames = [
        @foreach($olt->uplinks as $u)
            '{{ $u->interface_name }}',
        @endforeach
    ];

    // Port layout data for visualization
    let portLayout = @json($portLayout);
    let uplinkData  = @json($uplinkLayout);

    // Select2 with tags:true so user can type custom port names not in list
    let s2cfg = { theme: 'bootstrap-5', width: '100%', tags: true, tokenSeparators: [',', ' '],
        placeholder: 'Pilih atau ketik nama port...', allowClear: true };
    $('#edit-tagged-select').select2($.extend({}, s2cfg, { dropdownParent: $('#modal-edit-vlan') }));
    $('#edit-untagged-select').select2($.extend({}, s2cfg, { dropdownParent: $('#modal-edit-vlan') }));
    $('#create-tagged-select').select2($.extend({}, s2cfg, { dropdownParent: $('#modal-create-vlan') }));
    $('#create-untagged-select').select2($.extend({}, s2cfg, { dropdownParent: $('#modal-create-vlan') }));

    function s2Set(selectId, values) {
        let $s = $('#' + selectId);
        // Add any value not already in options
        (values || []).forEach(function(v) {
            if (!$s.find('option[value="' + $.escapeSelector(v) + '"]').length) {
                $s.append(new Option(v, v));
            }
        });
        $s.val(values || []).trigger('change');
    }
    function s2Get(selectId) { return $('#' + selectId).val() || []; }

    // =========================================================================
    // Port Map Visualization
    // =========================================================================
    function buildPortMap(modalPrefix) {
        let isEdit = (modalPrefix === 'edit');
        let taggedSet   = new Set(s2Get(modalPrefix + '-tagged-select'));
        let untaggedSet = new Set(s2Get(modalPrefix + '-untagged-select'));

        let uplinkTagged = new Set(), uplinkUntagged = new Set();
        let radioClass = isEdit ? '.port-radio' : '.create-port-radio';
        $(radioClass + ':checked').each(function() {
            let port = $(this).data('port'), val = $(this).val();
            if (val === 'tagged') uplinkTagged.add(port);
            else if (val === 'untagged') uplinkUntagged.add(port);
        });

        let html = '';

        // Uplinks section
        if (uplinkData.length) {
            html += '<div class="pm-section"><div class="pm-section-label"><i class="fas fa-arrow-up mr-1"></i>Uplinks</div><div class="pm-ports">';
            uplinkData.forEach(function(u) {
                let isT = uplinkTagged.has(u.name), isU = uplinkUntagged.has(u.name);
                let cls  = isT ? 'pm-tagged' : (isU ? 'pm-untagged' : 'pm-none');
                let mode = isT ? '<span class="pm-port-mode" style="color:#2563eb">T</span>' :
                           (isU ? '<span class="pm-port-mode" style="color:#16a34a">U</span>' : '');
                let shortName = u.label.replace(/^x?gei_/, '');
                let badge = u.is10g ? '<span style="font-size:7px;color:#0891b2;line-height:1;">10G</span>' : '';
                html += '<div class="pm-port pm-uplink-port ' + cls + '" title="' + u.name + '">';
                html += '<span class="pm-port-name">' + shortName + '</span>';
                html += badge + mode;
                html += '</div>';
            });
            html += '</div></div>';
        }

        // PON ports per GPON card
        portLayout.forEach(function(card) {
            html += '<div class="pm-section"><div class="pm-section-label"><i class="fas fa-microchip mr-1"></i>Slot ' + card.slot + ' <span style="font-weight:400;text-transform:none;letter-spacing:0;">' + card.type + '</span></div><div class="pm-ports">';
            card.ports.forEach(function(p) {
                let isT = taggedSet.has(p.name), isU = untaggedSet.has(p.name);
                let cls  = isT ? 'pm-tagged' : (isU ? 'pm-untagged' : 'pm-none');
                let mode = isT ? '<span class="pm-port-mode" style="color:#2563eb">T</span>' :
                           (isU ? '<span class="pm-port-mode" style="color:#16a34a">U</span>' : '');
                html += '<div class="pm-port ' + cls + '" title="' + p.name + '"><span class="pm-port-name">' + p.label + '</span>' + mode + '</div>';
            });
            html += '</div></div>';
        });

        $('#' + modalPrefix + '-port-map-body').html(html || '<p class="text-muted small mb-0" style="font-size:11px;">Tidak ada data port untuk divisualisasikan</p>');
    }

    // Auto-update port map when Select2 or radio changes
    $('#edit-tagged-select, #edit-untagged-select').on('change', function() { buildPortMap('edit'); });
    $('#create-tagged-select, #create-untagged-select').on('change', function() { buildPortMap('create'); });

    // =========================================================================
    // Edit VLAN
    // =========================================================================
    $(document).on('click', '.btn-edit-vlan', function() {
        let btn = $(this);
        $('#edit-vlan-id').val(btn.data('id'));
        $('#edit-vlan-display').val(btn.data('vlan-id'));
        $('#edit-vlan-title-id').text(btn.data('vlan-id'));
        $('#edit-vlan-name').val(btn.data('name') || '');
        $('#edit-vlan-type').val(btn.data('type'));
        $('#edit-vlan-description').val(btn.data('description') || '');

        let tagged = btn.data('tagged') || [];
        let untagged = btn.data('untagged') || [];
        let uplinkPorts = btn.data('uplink') || [];
        if (typeof tagged === 'string') tagged = JSON.parse(tagged);
        if (typeof untagged === 'string') untagged = JSON.parse(untagged);
        if (typeof uplinkPorts === 'string') uplinkPorts = JSON.parse(uplinkPorts);

        // Reset uplink radios
        $('.port-radio[value="none"]').prop('checked', true);
        $('.port-radio-group label').removeClass('active-tagged active-untagged active-none');
        $('.port-radio[value="none"]').closest('label').addClass('active-none');

        // Set radios from uplink_ports column (stored from sync; these are trunk=tagged)
        uplinkPorts.forEach(function(port) {
            if (uplinkNames.includes(port)) {
                let radio = $('input.port-radio[data-port="' + port + '"][value="tagged"]');
                if (radio.length) {
                    radio.prop('checked', true);
                    radio.closest('.port-radio-group').find('label').removeClass('active-tagged active-untagged active-none');
                    radio.closest('label').addClass('active-tagged');
                }
            }
        });

        // Separate tagged_ports / untagged_ports into uplink (radio) vs extra (select2)
        let extraTagged = [], extraUntagged = [];
        tagged.forEach(function(port) {
            if (uplinkNames.includes(port)) {
                let slug = port.replace(/[\/\:\._]/g, '-');
                let radio = $('input.port-radio[data-port="' + port + '"][value="tagged"]');
                if (radio.length) {
                    radio.prop('checked', true);
                    radio.closest('.port-radio-group').find('label').removeClass('active-tagged active-untagged active-none');
                    radio.closest('label').addClass('active-tagged');
                }
            } else {
                extraTagged.push(port);
            }
        });
        untagged.forEach(function(port) {
            if (uplinkNames.includes(port)) {
                let radio = $('input.port-radio[data-port="' + port + '"][value="untagged"]');
                if (radio.length) {
                    radio.prop('checked', true);
                    radio.closest('.port-radio-group').find('label').removeClass('active-tagged active-untagged active-none');
                    radio.closest('label').addClass('active-untagged');
                }
            } else {
                extraUntagged.push(port);
            }
        });

        // If no uplinks, put ALL ports in extra
        if (uplinkNames.length === 0) {
            extraTagged = tagged;
            extraUntagged = untagged;
        }

        s2Set('edit-tagged-select', extraTagged);
        s2Set('edit-untagged-select', extraUntagged);
        buildPortMap('edit');
        $('#modal-edit-vlan').modal('show');
    });

    $('#btn-save-vlan').click(function() {
        let btn = $(this);
        let vlanId = $('#edit-vlan-id').val();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

        // Collect from uplink radios
        let taggedPorts = [], untaggedPorts = [];
        $('.port-radio:checked').each(function() {
            let port = $(this).data('port');
            let val = $(this).val();
            if (val === 'tagged') taggedPorts.push(port);
            else if (val === 'untagged') untaggedPorts.push(port);
        });
        // Merge select2 ports
        s2Get('edit-tagged-select').forEach(p => { if (!taggedPorts.includes(p)) taggedPorts.push(p); });
        s2Get('edit-untagged-select').forEach(p => { if (!untaggedPorts.includes(p)) untaggedPorts.push(p); });

        $.ajax({
            url: '{{ route("admin.olts.infrastructure.vlans.update-type", [$olt, "__VLAN__"]) }}'.replace('__VLAN__', vlanId),
            method: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                name: $('#edit-vlan-name').val(),
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
        $('#create-vlan-type').val('service');
        // Reset create uplink radios
        $('.create-port-radio[value="none"]').prop('checked', true);
        $('.port-radio-group label').each(function() {
            let radio = $(this).find('.create-port-radio');
            if (radio.length) {
                $(this).closest('.port-radio-group').find('label').removeClass('active-tagged active-untagged active-none');
                $(this).closest('.port-radio-group').find('label:last-child').addClass('active-none');
            }
        });
        s2Set('create-tagged-select', []);
        s2Set('create-untagged-select', []);
        buildPortMap('create');
        $('#modal-create-vlan').modal('show');
    });

    $('#btn-create-vlan').click(function() {
        let btn = $(this);
        let vid = $('#create-vlan-id').val();
        if (!vid || vid < 2 || vid > 4094) { toastr.warning('VLAN ID harus antara 2 - 4094'); return; }
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Membuat...');

        // Collect port membership
        let taggedPorts = [], untaggedPorts = [];
        $('.create-port-radio:checked').each(function() {
            let port = $(this).data('port'), val = $(this).val();
            if (val === 'tagged') taggedPorts.push(port);
            else if (val === 'untagged') untaggedPorts.push(port);
        });
        s2Get('create-tagged-select').forEach(p => { if (!taggedPorts.includes(p)) taggedPorts.push(p); });
        s2Get('create-untagged-select').forEach(p => { if (!untaggedPorts.includes(p)) untaggedPorts.push(p); });

        $.ajax({
            url: '{{ route("admin.olts.infrastructure.vlans.create", $olt) }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                vlan_id: vid,
                name: $('#create-vlan-name').val(),
                type: $('#create-vlan-type').val(),
                description: $('#create-vlan-desc').val(),
                tagged_ports: taggedPorts,
                untagged_ports: untaggedPorts,
            },
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
