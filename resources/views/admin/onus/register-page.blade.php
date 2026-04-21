@extends('layouts.admin')

@section('title', 'Register ONU Baru')

@section('page-title', 'Register ONU Baru')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.onus.index') }}">ONU</a></li>
    <li class="breadcrumb-item active">Register</li>
@endsection

@section('content')
<div class="row">
    <!-- Step 1: Pilih OLT -->
    <div class="col-lg-4 col-md-5">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>Step 1: Pilih OLT</h3>
            </div>
            <div class="card-body">
                <div class="form-group mb-2">
                    <label>OLT <span class="text-danger">*</span></label>
                    <select id="select-olt" class="form-control">
                        <option value="">-- Pilih OLT --</option>
                        @foreach($olts as $olt)
                        <option value="{{ $olt->id }}" data-brand="{{ $olt->brand }}" data-name="{{ $olt->name }}" data-ip="{{ $olt->ip_address }}">
                            {{ $olt->name }} ({{ $olt->ip_address }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div id="olt-info" style="display:none;">
                    <table class="table table-sm table-bordered mb-0">
                        <tr><td width="35%"><strong>OLT</strong></td><td id="info-olt-name"></td></tr>
                        <tr><td><strong>IP</strong></td><td id="info-olt-ip"></td></tr>
                        <tr><td><strong>Brand</strong></td><td id="info-olt-brand"></td></tr>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-info btn-block" id="btn-scan" disabled>
                    <i class="fas fa-search mr-1"></i>Scan ONU Baru
                </button>
            </div>
        </div>
    </div>

    <!-- Step 2: Hasil Scan -->
    <div class="col-lg-8 col-md-7">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-broadcast-tower mr-2"></i>Step 2: ONU Belum Terdaftar</h3>
                <div class="card-tools">
                    <span class="badge badge-secondary" id="scan-count">0</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="scan-placeholder" class="text-center py-5 text-muted">
                    <i class="fas fa-satellite-dish fa-3x mb-3 d-block text-secondary"></i>
                    <p class="mb-1">Pilih OLT lalu klik <strong>Scan ONU Baru</strong></p>
                    <small>Sistem akan mencari ONU yang belum terdaftar pada OLT</small>
                </div>
                <div id="scan-loading" style="display:none;" class="text-center py-5">
                    <div class="spinner-border text-warning mb-3" role="status" style="width:3rem;height:3rem;">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mb-1"><strong>Scanning ONU...</strong></p>
                    <small class="text-muted">Menghubungi OLT via CLI/Telnet, tunggu 10-30 detik</small>
                    <div class="progress mt-3 mx-auto" style="max-width:300px;height:4px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width:100%"></div>
                    </div>
                </div>
                <div id="scan-empty" style="display:none;" class="text-center py-5 text-muted">
                    <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                    <p class="mb-1"><strong>Semua ONU sudah terdaftar</strong></p>
                    <small>Tidak ada ONU baru yang menunggu registrasi</small>
                </div>
                <div class="table-responsive" id="scan-results" style="display:none;">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>PON Port</th>
                                <th>Serial Number</th>
                                <th>Tipe ONU</th>
                                <th class="text-center" width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="scan-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Step 3: Register Form -->
<div class="row" id="register-section" style="display:none;">
    <div class="col-12">
        <div class="card card-success card-outline elevation-2">
            <div class="card-header bg-gradient-success">
                <h3 class="card-title text-white"><i class="fas fa-plus-circle mr-2"></i>Step 3: Register ONU</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool text-white" id="btn-cancel-register" title="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <form id="form-register">
                <div class="card-body pb-2">
                    <input type="hidden" name="olt_id" id="reg_olt_id">
                    <input type="hidden" name="slot" id="reg_slot">
                    <input type="hidden" name="port" id="reg_port">
                    <input type="hidden" name="pon_port" id="reg_pon_port">
                    <input type="hidden" name="serial_number" id="reg_serial_number">
                    <input type="hidden" id="reg_onu_type">

                    <!-- ONU Info Banner -->
                    <div class="row mb-3">
                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                            <div class="info-box bg-light mb-0 py-1 px-2 shadow-sm">
                                <span class="info-box-icon bg-info elevation-1" style="width:40px;height:40px;line-height:40px;font-size:16px;border-radius:6px;"><i class="fas fa-server"></i></span>
                                <div class="info-box-content py-1 pl-2">
                                    <span class="info-box-text text-muted" style="font-size:10px;">OLT</span>
                                    <span class="info-box-number" style="font-size:13px;" id="reg_olt_display">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                            <div class="info-box bg-light mb-0 py-1 px-2 shadow-sm">
                                <span class="info-box-icon bg-purple elevation-1" style="width:40px;height:40px;line-height:40px;font-size:16px;border-radius:6px;"><i class="fas fa-plug"></i></span>
                                <div class="info-box-content py-1 pl-2">
                                    <span class="info-box-text text-muted" style="font-size:10px;">PON Port</span>
                                    <span class="info-box-number" style="font-size:13px;" id="reg_pon_display">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 mb-2 mb-md-0">
                            <div class="info-box bg-light mb-0 py-1 px-2 shadow-sm">
                                <span class="info-box-icon bg-warning elevation-1" style="width:40px;height:40px;line-height:40px;font-size:16px;border-radius:6px;"><i class="fas fa-barcode"></i></span>
                                <div class="info-box-content py-1 pl-2">
                                    <span class="info-box-text text-muted" style="font-size:10px;">Serial Number</span>
                                    <span class="info-box-number" style="font-size:13px;font-family:monospace;" id="reg_sn_display">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                            <div class="info-box bg-light mb-0 py-1 px-2 shadow-sm">
                                <span class="info-box-icon bg-success elevation-1" style="width:40px;height:40px;line-height:40px;font-size:16px;border-radius:6px;"><i class="fas fa-microchip"></i></span>
                                <div class="info-box-content py-1 pl-2">
                                    <span class="info-box-text text-muted" style="font-size:10px;">Tipe ONU</span>
                                    <span class="info-box-number text-primary" style="font-size:13px;" id="reg_onu_type_display">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left column: Basic info -->
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label>Nama ONU <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                    </div>
                                    <input type="text" name="name" class="form-control" required placeholder="Contoh: ONU-AHMAD">
                                </div>
                                <small class="form-text text-muted">Wajib diisi. Nama untuk identifikasi ONU.</small>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Zone</label>
                                        <select name="zone_id" id="reg_zone_id" class="form-control">
                                            <option value="">-- Opsional --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>ODP</label>
                                        <select name="odp_id" id="reg_odp_id" class="form-control" disabled>
                                            <option value="">-- Pilih Zone dulu --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Pelanggan <span class="badge badge-secondary badge-sm">Opsional</span></label>
                                <select name="customer_id" id="reg_customer_id" class="form-control select2-customer" style="width:100%">
                                    <option value="">-- Cari nama / ID pelanggan --</option>
                                </select>
                                <small class="form-text text-muted">Bisa diisi nanti dari halaman ONU.</small>
                            </div>

                            <div class="form-group">
                                <label>Deskripsi <span class="badge badge-secondary badge-sm">Opsional</span></label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Catatan lokasi, keterangan, dll."></textarea>
                            </div>
                        </div>

                        <!-- Right column: Profile settings -->
                        <div class="col-lg-6">
                            <!-- ZTE Settings (shown for ZTE OLTs) -->
                            <div id="zte-settings" style="display:none;">
                                <div class="callout callout-info py-2 mb-3">
                                    <h6 class="mb-1"><i class="fas fa-info-circle mr-1"></i>ZTE C320 — Full Provisioning</h6>
                                    <p class="mb-0" style="font-size:13px;">
                                        Seperti SmartOLT: isi <strong>Service VLAN</strong> + <strong>Management VLAN</strong>.
                                        ONU langsung aktif, dapat IP, dan terhubung ke ACS (TR069).
                                    </p>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>Service VLAN <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-network-wired"></i></span>
                                                </div>
                                                <select name="vlan_id" id="reg_vlan_id" class="form-control" required>
                                                    <option value="">-- Pilih VLAN --</option>
                                                </select>
                                            </div>
                                            <small class="form-text text-muted">
                                                VLAN internet pelanggan.
                                                <span id="hint-service-vlan" class="text-warning font-weight-bold" style="display:none;"></span>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>Mgmt VLAN (TR069) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-broadcast-tower"></i></span>
                                                </div>
                                                <select name="mgmt_vlan" id="reg_mgmt_vlan" class="form-control" required>
                                                    <option value="">-- Pilih VLAN --</option>
                                                </select>
                                            </div>
                                            <small class="form-text text-muted">
                                                VLAN manajemen DHCP/ACS.
                                                <span id="hint-mgmt-vlan" class="text-warning font-weight-bold" style="display:none;"></span>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Advanced: collapsed by default -->
                                <div class="card card-outline card-secondary mb-0 collapsed-card" id="zte-advanced-card">
                                    <div class="card-header py-2" data-card-widget="collapse" style="cursor:pointer;">
                                        <h5 class="card-title mb-0" style="font-size:13px;">
                                            <i class="fas fa-cog mr-1"></i>Advanced
                                            <small class="text-muted ml-1">— biasanya tidak perlu diubah</small>
                                        </h5>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool"><i class="fas fa-plus"></i></button>
                                        </div>
                                    </div>
                                    <div class="card-body py-2" style="display:none;">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">TCONT Profile (Upstream)</label>
                                                    <select name="line_profile" id="reg_line_profile" class="form-control form-control-sm">
                                                        <option value="default">default</option>
                                                    </select>
                                                    <small class="form-text text-muted">Profile DBA upstream bandwidth.</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">Traffic Profile (Downstream)</label>
                                                    <select name="traffic_profile" id="reg_traffic_profile" class="form-control form-control-sm">
                                                        <option value="">-- Pilih profile --</option>
                                                    </select>
                                                    <small class="form-text text-muted">Shaping kecepatan downstream.</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-3">
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">GEM Port</label>
                                                    <input type="number" name="gem_port" class="form-control form-control-sm" min="1" value="1">
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">T-CONT ID</label>
                                                    <input type="number" name="tcont_id" class="form-control form-control-sm" min="1" value="1">
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">Service ID</label>
                                                    <input type="number" name="service_id" class="form-control form-control-sm" min="1" value="1">
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">Service Port Mode</label>
                                                    <select name="service_port_mode" class="form-control form-control-sm">
                                                        <option value="tag" selected>Tag</option>
                                                        <option value="translate">Translate</option>
                                                        <option value="transparent">Transparent</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <div class="form-group mb-1">
                                                    <label class="small mb-1">OLT Profile Type <span class="text-muted">(onu type di OLT)</span></label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" name="onu_type" id="reg_onu_type_input" class="form-control form-control-sm" placeholder="Auto-detect dari scan">
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown" title="Pilih profile umum">
                                                                <i class="fas fa-list"></i>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                <h6 class="dropdown-header">Fiberhome</h6>
                                                                <a class="dropdown-item onu-type-option small" href="#" data-type="OPEN_FIBERHOME">OPEN_FIBERHOME</a>
                                                                <div class="dropdown-divider"></div>
                                                                <h6 class="dropdown-header">Huawei</h6>
                                                                <a class="dropdown-item onu-type-option small" href="#" data-type="HG8245H">HG8245H</a>
                                                                <a class="dropdown-item onu-type-option small" href="#" data-type="HG8546M">HG8546M</a>
                                                                <a class="dropdown-item onu-type-option small" href="#" data-type="EG8145V5">EG8145V5</a>
                                                                <div class="dropdown-divider"></div>
                                                                <h6 class="dropdown-header">ZTE</h6>
                                                                <a class="dropdown-item onu-type-option small" href="#" data-type="F660">F660</a>
                                                                <a class="dropdown-item onu-type-option small" href="#" data-type="F670L">F670L</a>
                                                                <a class="dropdown-item onu-type-option small" href="#" data-type="OPEN_ZTE">OPEN_ZTE</a>
                                                                <div class="dropdown-divider"></div>
                                                                <h6 class="dropdown-header">Generic</h6>
                                                                <a class="dropdown-item onu-type-option small" href="#" data-type="ALL">ALL (universal)</a>
                                                                <a class="dropdown-item onu-type-option small" href="#" data-type="OPEN_NOKIA">OPEN_NOKIA</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">Ubah jika registrasi gagal <em>"Not support this ONU"</em>. Cek profil yang tersedia di OLT dengan <code>show gpon onu-profile</code>.</small>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Default sudah terisi, ubah hanya jika perlu.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Generic profile (non-ZTE) -->
                            <div id="generic-profile-settings" style="display:none;">
                                <div class="form-group">
                                    <label>Profile OLT <span class="badge badge-secondary badge-sm">Opsional</span></label>
                                    <select name="profile_id" id="reg_profile_id" class="form-control">
                                        <option value="">-- Opsional --</option>
                                    </select>
                                </div>
                            </div>

                            <!-- WAN Setup Mode — shown for all OLT brands -->
                            <div id="wan-setup-section" style="display:none;" class="mt-2">
                                <hr class="my-2">
                                <label class="font-weight-bold d-block mb-1">
                                    <i class="fas fa-wifi mr-1 text-primary"></i>Setup WAN
                                </label>
                                <div class="btn-group btn-group-toggle w-100 mb-2" data-toggle="buttons" id="wan-mode-group">
                                    <label class="btn btn-outline-secondary active" style="flex:1; font-size:12px;">
                                        <input type="radio" name="wan_mode" value="skip" checked>
                                        <i class="fas fa-ban mr-1"></i>Skip
                                    </label>
                                    <label class="btn btn-outline-warning omci-only-option" style="flex:1; font-size:12px; display:none;">
                                        <input type="radio" name="wan_mode" value="omci">
                                        <i class="fas fa-microchip mr-1"></i>OMCI <small>(ZTE)</small>
                                    </label>
                                    <label class="btn btn-outline-info" style="flex:1; font-size:12px;">
                                        <input type="radio" name="wan_mode" value="tr069">
                                        <i class="fas fa-cloud mr-1"></i>TR-069/ACS
                                    </label>
                                </div>
                                <p id="wan-mode-hint" class="text-muted mb-2" style="font-size:12px;">
                                    Tidak ada konfigurasi WAN sekarang. Pelanggan set sendiri via halaman ONU.
                                </p>
                                <div id="pppoe-fields" style="display:none;">
                                    <div class="callout py-2 mb-2" id="pppoe-callout">
                                        <p id="pppoe-callout-text" class="mb-0" style="font-size:12px;"></p>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="small mb-1">PPPoE Username <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                    </div>
                                                    <input type="text" name="pppoe_username" id="reg_pppoe_username" class="form-control" placeholder="user@isp" autocomplete="off">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="small mb-1">PPPoE Password <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                    </div>
                                                    <input type="password" name="pppoe_password" id="reg_pppoe_password" class="form-control" placeholder="password" autocomplete="new-password">
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-toggle-pwd" tabindex="-1">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-default" id="btn-cancel-register-2">
                        <i class="fas fa-arrow-left mr-1"></i>Kembali
                    </button>
                    <button type="submit" class="btn btn-success btn-lg px-4">
                        <i class="fas fa-plus-circle mr-1"></i>Register ONU
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function() {
    var currentOltId = null;
    var currentOltBrand = null;
    var oltZones = [];
    var oltProfiles = [];
    var zteProfileConfigs = {};

    // ========== ONU Type Detection ==========
    function detectOnuType(sn) {
        if (!sn || sn.length < 4) return '-';
        var prefix = sn.substring(0, 4).toUpperCase();
        var map = {
            'HWTC': 'HG8245H', 'HWTG': 'HG8245H5', 'HWTE': 'EG8145V5',
            'ZTEG': 'ZTE ONT', 'ZICG': 'F663NV9', 'PRTS': 'Proscend',
            'ALCL': 'Nokia', 'FHTT': 'FiberHome', 'TPLG': 'TP-Link',
            'DSNW': 'DASAN', 'MSTC': 'ZyXEL', 'SMBS': 'SmartRG'
        };
        return map[prefix] || prefix;
    }

    // ========== Step 1: Select OLT ==========
    $('#select-olt').change(function() {
        var oltId = $(this).val();
        var opt = $(this).find(':selected');

        if (!oltId) {
            currentOltId = null;
            $('#olt-info').hide();
            $('#btn-scan').prop('disabled', true);
            resetScan();
            return;
        }

        currentOltId = oltId;
        currentOltBrand = opt.data('brand');
        $('#info-olt-name').text(opt.data('name'));
        $('#info-olt-ip').text(opt.data('ip'));
        $('#info-olt-brand').text(opt.data('brand'));
        $('#olt-info').show();
        $('#btn-scan').prop('disabled', false);

        // Load OLT-specific data (zones, profiles, vlans)
        $.get('/admin/onus/register/olt-data/' + oltId, function(res) {
            if (res.success) {
                oltZones = res.zones || [];
                oltProfiles = res.profiles || [];
                zteProfileConfigs = {};

                // Build profile configs map
                oltProfiles.forEach(function(p) {
                    if (p.config) {
                        zteProfileConfigs[p.name] = typeof p.config === 'string' ? JSON.parse(p.config) : p.config;
                    }
                });

                // Populate VLAN datalists and auto-fill
                var oltVlans = res.vlans || [];
                var serviceVlans = oltVlans.filter(function(v) { return v.type === 'service'; });
                var mgmtVlans = oltVlans.filter(function(v) { return v.type === 'management'; });

                // Fallback: if no typed VLANs, show all VLANs in both lists
                if (!serviceVlans.length) serviceVlans = oltVlans;
                if (!mgmtVlans.length) mgmtVlans = oltVlans;

                var svcOptions = '<option value="">-- Pilih VLAN --</option>' + serviceVlans.map(function(v) {
                    return '<option value="' + v.vlan_id + '">' + v.vlan_id + (v.name ? ' — ' + v.name : '') + '</option>';
                }).join('');
                var mgmtOptions = '<option value="">-- Pilih VLAN --</option>' + mgmtVlans.map(function(v) {
                    return '<option value="' + v.vlan_id + '">' + v.vlan_id + (v.name ? ' — ' + v.name : '') + '</option>';
                }).join('');

                $('#reg_vlan_id').html(svcOptions);
                $('#reg_mgmt_vlan').html(mgmtOptions);

                // Auto-fill VLAN fields from first available configured VLAN
                if (serviceVlans.length) {
                    if (serviceVlans.length === 1) {
                        $('#reg_vlan_id').val(serviceVlans[0].vlan_id);
                        $('#hint-service-vlan').text('Auto: VLAN ' + serviceVlans[0].vlan_id).show();
                    } else {
                        $('#hint-service-vlan').text(serviceVlans.length + ' VLAN tersedia — cek dropdown').show();
                    }
                } else {
                    $('#reg_vlan_id').val('');
                    $('#hint-service-vlan').hide();
                }

                if (mgmtVlans.length) {
                    if (mgmtVlans.length === 1) {
                        $('#reg_mgmt_vlan').val(mgmtVlans[0].vlan_id);
                        $('#hint-mgmt-vlan').text('Auto: VLAN ' + mgmtVlans[0].vlan_id).show();
                    } else {
                        $('#hint-mgmt-vlan').text(mgmtVlans.length + ' VLAN tersedia — cek dropdown').show();
                    }
                } else {
                    $('#reg_mgmt_vlan').val('');
                    $('#hint-mgmt-vlan').hide();
                }
            }
        });

        resetScan();
    });

    // ========== Step 2: Scan ==========
    function resetScan() {
        $('#scan-placeholder').show();
        $('#scan-loading, #scan-empty, #scan-results').hide();
        $('#scan-count').text('0');
        $('#register-section').hide();
    }

    $('#btn-scan').click(function() {
        if (!currentOltId) return;

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Scanning...');
        $('#scan-placeholder').hide();
        $('#scan-loading').show();
        $('#scan-empty, #scan-results').hide();

        $.get('/admin/onus/register/scan/' + currentOltId)
            .done(function(res) {
                $('#scan-loading').hide();

                if (!res.success || !res.data || res.data.length === 0) {
                    $('#scan-empty').show();
                    $('#scan-count').text('0');
                    return;
                }

                var tbody = '';
                res.data.forEach(function(onu) {
                    // Prefer onu_type from server (actual CLI output), fall back to prefix guess
                    var type = onu.onu_type || detectOnuType(onu.serial_number || onu.sn);
                    var sn = onu.serial_number || onu.sn;
                    var pon = onu.pon_port || (onu.slot + '/' + onu.port);
                    var slot = onu.slot || '';
                    var port = onu.port || '';
                    var typeBadge = 'badge-secondary';
                    if (type.match(/HG8|EG8/)) typeBadge = 'badge-primary';
                    else if (type.match(/F663|F660|F680|F6600|ZTE/i)) typeBadge = 'badge-info';
                    else if (type.match(/Nokia/)) typeBadge = 'badge-warning';

                    tbody += '<tr>' +
                        '<td><span class="badge badge-dark"><i class="fas fa-plug mr-1"></i>' + pon + '</span></td>' +
                        '<td><code class="text-dark font-weight-bold">' + sn + '</code></td>' +
                        '<td><span class="badge ' + typeBadge + '">' + type + '</span></td>' +
                        '<td class="text-center"><button type="button" class="btn btn-sm btn-success btn-register-onu" ' +
                            'data-sn="' + sn + '" data-pon="' + pon + '" data-slot="' + slot + '" data-port="' + port + '" data-type="' + type + '">' +
                            '<i class="fas fa-plus mr-1"></i>Daftarkan</button></td>' +
                        '</tr>';
                });

                $('#scan-table-body').html(tbody);
                $('#scan-results').show();
                $('#scan-count').text(res.data.length).removeClass('badge-secondary').addClass('badge-warning');
            })
            .fail(function(xhr) {
                $('#scan-loading').hide();
                $('#scan-placeholder').show();
                Swal.fire('Error', xhr.responseJSON?.message || 'Gagal scan ONU', 'error');
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-search mr-1"></i>Scan ONU Baru');
            });
    });

    // ========== Step 3: Register Form ==========
    $(document).on('click', '.btn-register-onu', function() {
        var sn = $(this).data('sn');
        var pon = $(this).data('pon');
        var slot = $(this).data('slot');
        var port = $(this).data('port');
        var type = $(this).data('type') || detectOnuType(sn);
        var oltName = $('#select-olt option:selected').data('name');

        // Fill hidden fields
        $('#reg_olt_id').val(currentOltId);
        $('#reg_slot').val(slot);
        $('#reg_port').val(port);
        $('#reg_pon_port').val(pon);
        $('#reg_serial_number').val(sn);
        $('#reg_onu_type').val(type);
        $('#reg_onu_type_input').val(type);

        // Display info
        $('#reg_olt_display').text(oltName);
        $('#reg_pon_display').text(pon);
        $('#reg_sn_display').text(sn);
        $('#reg_onu_type_display').text(type);

        // Populate zones dropdown
        var zoneHtml = '<option value="">-- Pilih Zone --</option>';
        oltZones.forEach(function(z) {
            zoneHtml += '<option value="' + z.id + '">' + z.name + '</option>';
        });
        $('#reg_zone_id').html(zoneHtml);
        $('#reg_odp_id').html('<option value="">-- Pilih Zone dulu --</option>').prop('disabled', true);

        // Show/hide OLT-specific settings
        if (currentOltBrand === 'zte') {
            $('#zte-settings').show();
            $('#generic-profile-settings').hide();

            // Populate TCONT profiles dropdown
            var tcontHtml = '<option value="default">default</option>';
            var trafficHtml = '<option value="">-- Pilih profile --</option>';
            oltProfiles.forEach(function(p) {
                if (p.type === 'tcont') {
                    tcontHtml += '<option value="' + p.name + '">' + p.name + '</option>';
                }
                if (p.type === 'traffic') {
                    trafficHtml += '<option value="' + p.name + '">' + p.name + '</option>';
                }
            });
            $('#reg_line_profile').html(tcontHtml);
            $('#reg_traffic_profile').html(trafficHtml);

            // Auto-select first TCONT & first Traffic profile if available
            if ($('#reg_line_profile option').length > 1) {
                $('#reg_line_profile').val($('#reg_line_profile option:eq(1)').val());
            }
            if ($('#reg_traffic_profile option').length > 1) {
                $('#reg_traffic_profile').val($('#reg_traffic_profile option:eq(1)').val());
            }
        } else if (oltProfiles.length > 0) {
            $('#zte-settings').hide();
            $('#generic-profile-settings').show();

            var profHtml = '<option value="">-- Opsional --</option>';
            oltProfiles.forEach(function(p) {
                profHtml += '<option value="' + p.id + '">' + (p.type || '') + ' - ' + p.name + '</option>';
            });
            $('#reg_profile_id').html(profHtml);
        } else {
            $('#zte-settings, #generic-profile-settings').hide();
        }

        // ── WAN Setup section ──
        $('#wan-setup-section').show();

        // OMCI option: hanya untuk ZTE
        if (currentOltBrand === 'zte') {
            $('.omci-only-option').css('display', '').removeClass('d-none');
        } else {
            // Non-ZTE: sembunyikan OMCI, reset ke skip
            $('.omci-only-option').hide();
            $('input[name="wan_mode"][value="skip"]').prop('checked', true).closest('label')
                .addClass('active').siblings().removeClass('active');
        }
        // Reset WAN mode to skip & hide pppoe
        $('input[name="wan_mode"][value="skip"]').prop('checked', true)
            .closest('label').addClass('active').siblings().removeClass('active');
        $('#pppoe-fields').hide();
        $('#wan-mode-hint').text('Tidak ada konfigurasi WAN sekarang. Pelanggan set sendiri via halaman ONU.');
        $('#reg_pppoe_username').val('');
        $('#reg_pppoe_password').val('');

        // Init Select2 for customer
        if (!$('#reg_customer_id').hasClass('select2-hidden-accessible')) {
            $('#reg_customer_id').select2({
                theme: 'bootstrap4',
                placeholder: '-- Pilih Pelanggan --',
                allowClear: true,
                ajax: {
                    url: '{{ route("admin.customers.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { q: params.term, without_onu: true };
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
        }

        // Reset form fields
        $('input[name="name"]').val('');
        $('textarea[name="description"]').val('');
        $('#reg_customer_id').val(null).trigger('change');

        // Show register section
        $('#register-section').show();
        $('html, body').animate({ scrollTop: $('#register-section').offset().top - 70 }, 300);
    });

    // Zone change → load ODPs
    $('#reg_zone_id').change(function() {
        var zoneId = $(this).val();
        var odp = $('#reg_odp_id');

        if (!zoneId || !currentOltId) {
            odp.html('<option value="">-- Pilih Zone dulu --</option>').prop('disabled', true);
            return;
        }

        odp.html('<option value="">Memuat...</option>').prop('disabled', true);
        $.get('/admin/olts/' + currentOltId + '/zones/' + zoneId + '/odps', function(res) {
            var html = '<option value="">-- Pilih ODP --</option>';
            (res.data || res).forEach(function(o) {
                html += '<option value="' + o.id + '">' + o.name + '</option>';
            });
            odp.html(html).prop('disabled', false);
        }).fail(function() {
            odp.html('<option value="">Gagal memuat ODP</option>');
        });
    });

    // Cancel register
    $('#btn-cancel-register, #btn-cancel-register-2').click(function() {
        $('#register-section').hide();
    });

    // ========== WAN Mode Toggle ==========
    $(document).on('change', 'input[name="wan_mode"]', function() {
        var mode = $(this).val();
        if (mode === 'skip') {
            $('#pppoe-fields').hide();
            $('#reg_pppoe_username').prop('required', false);
            $('#reg_pppoe_password').prop('required', false);
            $('#wan-mode-hint').text('Tidak ada konfigurasi WAN sekarang. Pelanggan set sendiri via halaman ONU.');
        } else if (mode === 'omci') {
            $('#pppoe-callout').removeClass('callout-info').addClass('callout-warning');
            $('#pppoe-callout-text').html('<i class="fas fa-microchip mr-1"></i><strong>OMCI:</strong> PPPoE dikonfigurasi langsung ke hardware ONU via GPON OMCI saat register. Hanya untuk ONU ZTE.');
            $('#pppoe-fields').show();
            $('#reg_pppoe_username').prop('required', true);
            $('#reg_pppoe_password').prop('required', true);
            $('#wan-mode-hint').text('PPPoE akan di-inject via pon-onu-mng saat ONU diregister.');
        } else if (mode === 'tr069') {
            $('#pppoe-callout').removeClass('callout-warning').addClass('callout-info');
            $('#pppoe-callout-text').html('<i class="fas fa-cloud mr-1"></i><strong>TR-069/ACS:</strong> Credentials disimpan & dikirim ke GenieACS. ONU akan dikonfigurasi otomatis setelah terhubung ke ACS (1–3 menit).');
            $('#pppoe-fields').show();
            $('#reg_pppoe_username').prop('required', true);
            $('#reg_pppoe_password').prop('required', true);
            $('#wan-mode-hint').text('PPPoE akan dikonfigurasi ke ONU via GenieACS (TR-069) setelah ONU online.');
        }
    });

    // ONU Profile Type quick-select dropdown
    $(document).on('click', '.onu-type-option', function(e) {
        e.preventDefault();
        var type = $(this).data('type');
        $('#reg_onu_type_input').val(type);
    });

    // Toggle password visibility
    $(document).on('click', '#btn-toggle-pwd', function() {
        var input = $('#reg_pppoe_password');
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Submit register
    $('#form-register').submit(function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Mendaftarkan ONU...');

        $.ajax({
            url: '{{ route("admin.onus.register") }}',
            method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: res.message || 'ONU berhasil didaftarkan',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Lihat ONU',
                        cancelButtonText: 'Register Lagi'
                    }).then(function(result) {
                        if (result.isConfirmed && res.redirect_url) {
                            window.location.href = res.redirect_url;
                        } else {
                            // Re-scan to update the list
                            $('#register-section').hide();
                            $('#btn-scan').click();
                        }
                    });
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal mendaftarkan ONU', 'error');
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || 'Gagal mendaftarkan ONU';
                if (xhr.responseJSON?.errors) {
                    msg += '<br><br>' + Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire('Error', msg, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-plus-circle mr-1"></i>Register ONU');
            }
        });
    });
});
</script>
@endpush
