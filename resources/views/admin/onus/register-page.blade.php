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
    <div class="col-lg-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>Step 1: Pilih OLT</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
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
    <div class="col-lg-8">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-broadcast-tower mr-2"></i>Step 2: ONU Belum Terdaftar</h3>
                <div class="card-tools">
                    <span class="badge badge-secondary" id="scan-count">0</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="scan-placeholder" class="text-center py-4 text-muted">
                    <i class="fas fa-satellite-dish fa-3x mb-3 d-block"></i>
                    Pilih OLT lalu klik <strong>Scan ONU Baru</strong>
                </div>
                <div id="scan-loading" style="display:none;" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>
                    Scanning ONU via SNMP/Telnet...<br>
                    <small class="text-muted">Proses ini bisa memakan waktu 10-30 detik</small>
                </div>
                <div id="scan-empty" style="display:none;" class="text-center py-4 text-muted">
                    <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                    Tidak ada ONU baru yang ditemukan
                </div>
                <div class="table-responsive" id="scan-results" style="display:none;">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>PON Port</th>
                                <th>Serial Number</th>
                                <th>ONU Type</th>
                                <th width="120">Aksi</th>
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
    <div class="col-lg-12">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Step 3: Register ONU</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" id="btn-cancel-register">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <form id="form-register">
                <div class="card-body">
                    <input type="hidden" name="olt_id" id="reg_olt_id">
                    <input type="hidden" name="slot" id="reg_slot">
                    <input type="hidden" name="port" id="reg_port">
                    <input type="hidden" name="pon_port" id="reg_pon_port">
                    <input type="hidden" name="serial_number" id="reg_serial_number">

                    <!-- Info ONU -->
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-4">
                                <strong><i class="fas fa-server mr-1"></i>OLT:</strong> <span id="reg_olt_display"></span>
                            </div>
                            <div class="col-md-3">
                                <strong><i class="fas fa-plug mr-1"></i>PON Port:</strong> <span id="reg_pon_display"></span>
                            </div>
                            <div class="col-md-3">
                                <strong><i class="fas fa-barcode mr-1"></i>S/N:</strong> <span id="reg_sn_display"></span>
                            </div>
                            <div class="col-md-2">
                                <strong><i class="fas fa-microchip mr-1"></i>Type:</strong> <span id="reg_onu_type_display" class="text-primary font-weight-bold">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left column: Basic info -->
                        <div class="col-md-6">
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
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ODP</label>
                                        <select name="odp_id" id="reg_odp_id" class="form-control" disabled>
                                            <option value="">-- Pilih Zone dulu --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Pelanggan (Opsional)</label>
                                <select name="customer_id" id="reg_customer_id" class="form-control select2-customer" style="width:100%">
                                    <option value="">-- Pilih Pelanggan --</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- Right column: Profile / ZTE settings -->
                        <div class="col-md-6">
                            <!-- ZTE-specific settings (shown dynamically) -->
                            <div id="zte-settings" style="display:none;">
                                <div class="card card-outline card-info mb-0">
                                    <div class="card-header py-2">
                                        <h5 class="card-title mb-0"><i class="fas fa-cog mr-1"></i>ZTE Advanced Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">Line Profile</label>
                                                    <select name="line_profile" id="reg_line_profile" class="form-control form-control-sm">
                                                        <option value="">-- Default --</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">Service Profile</label>
                                                    <select name="service_profile" id="reg_service_profile" class="form-control form-control-sm">
                                                        <option value="">-- Opsional --</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">VLAN ID</label>
                                                    <input type="number" name="vlan_id" class="form-control form-control-sm" min="1" max="4094" placeholder="Dari profile">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">GEM Port</label>
                                                    <input type="number" name="gem_port" class="form-control form-control-sm" min="1" placeholder="Dari profile">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">T-CONT ID</label>
                                                    <input type="number" name="tcont_id" class="form-control form-control-sm" min="1" placeholder="Dari profile">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">Service ID</label>
                                                    <input type="number" name="service_id" class="form-control form-control-sm" min="1" placeholder="Dari profile">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="small mb-1">Service Port Mode</label>
                                            <select name="service_port_mode" class="form-control form-control-sm">
                                                <option value="">-- Default --</option>
                                                <option value="tag">Tag</option>
                                                <option value="translate">Translate</option>
                                                <option value="transparent">Transparent</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Generic profile (non-ZTE) -->
                            <div id="generic-profile-settings" style="display:none;">
                                <div class="form-group">
                                    <label>Profile OLT</label>
                                    <select name="profile_id" id="reg_profile_id" class="form-control">
                                        <option value="">-- Opsional --</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="button" class="btn btn-secondary mr-2" id="btn-cancel-register-2">
                        <i class="fas fa-times mr-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-success btn-lg">
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
            'ZTEG': 'F663N', 'ZICG': 'F663NV9', 'PRTS': 'Proscend',
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

        // Load OLT-specific data (zones, profiles)
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
                    var type = detectOnuType(onu.serial_number || onu.sn);
                    var sn = onu.serial_number || onu.sn;
                    var pon = onu.pon_port || (onu.slot + '/' + onu.port);
                    var slot = onu.slot || '';
                    var port = onu.port || '';

                    tbody += '<tr>' +
                        '<td><span class="badge badge-info">' + pon + '</span></td>' +
                        '<td><code>' + sn + '</code></td>' +
                        '<td><span class="badge badge-light">' + type + '</span></td>' +
                        '<td><button type="button" class="btn btn-sm btn-success btn-register-onu" ' +
                            'data-sn="' + sn + '" data-pon="' + pon + '" data-slot="' + slot + '" data-port="' + port + '">' +
                            '<i class="fas fa-plus mr-1"></i>Register</button></td>' +
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
        var oltName = $('#select-olt option:selected').data('name');

        // Fill hidden fields
        $('#reg_olt_id').val(currentOltId);
        $('#reg_slot').val(slot);
        $('#reg_port').val(port);
        $('#reg_pon_port').val(pon);
        $('#reg_serial_number').val(sn);

        // Display info
        $('#reg_olt_display').text(oltName);
        $('#reg_pon_display').text(pon);
        $('#reg_sn_display').text(sn);
        $('#reg_onu_type_display').text(detectOnuType(sn));

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

            // Populate ZTE profiles
            var lineHtml = '<option value="">-- Default --</option>';
            var svcHtml = '<option value="">-- Opsional --</option>';
            oltProfiles.forEach(function(p) {
                if (p.type === 'line') {
                    lineHtml += '<option value="' + p.name + '">' + p.name + '</option>';
                } else if (p.type === 'service') {
                    svcHtml += '<option value="' + p.name + '">' + p.name + '</option>';
                }
            });
            $('#reg_line_profile').html(lineHtml);
            $('#reg_service_profile').html(svcHtml);
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

    // ZTE Line Profile change → auto-fill config
    $('#reg_line_profile').change(function() {
        var name = $(this).val();
        if (name && zteProfileConfigs[name]) {
            var cfg = zteProfileConfigs[name];
            if (cfg.vlan_id) $('input[name="vlan_id"]').val(cfg.vlan_id);
            if (cfg.gem_port) $('input[name="gem_port"]').val(cfg.gem_port);
            if (cfg.tcont_id) $('input[name="tcont_id"]').val(cfg.tcont_id);
            if (cfg.service_id) $('input[name="service_id"]').val(cfg.service_id);
            if (cfg.service_port_mode) $('select[name="service_port_mode"]').val(cfg.service_port_mode);
        }
    });

    // Cancel register
    $('#btn-cancel-register, #btn-cancel-register-2').click(function() {
        $('#register-section').hide();
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
