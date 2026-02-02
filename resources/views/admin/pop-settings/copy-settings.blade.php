@extends('layouts.admin')

@section('title', 'Salin Pengaturan')

@section('page-title', 'Salin Pengaturan Antar POP')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Salin Pengaturan</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-3">
        @include('admin.pop-settings.partials.sidebar')
    </div>
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-copy mr-2"></i>Salin Pengaturan</h3>
            </div>
            <div class="card-body">
                <form id="copyForm">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Sumber (Copy From)</label>
                                <select name="source_user_id" class="form-control select2" id="sourceUser" required style="width: 100%;">
                                    <option value="">-- Pilih Sumber --</option>
                                    @foreach($popUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 text-center d-flex align-items-center justify-content-center">
                            <i class="fas fa-arrow-right fa-2x text-muted"></i>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Target (Copy To)</label>
                                <select name="target_user_id" class="form-control select2" id="targetUser" required style="width: 100%;">
                                    <option value="">-- Pilih Target --</option>
                                    @foreach($popUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-check-square mr-2"></i>Pilih yang akan disalin:</h6>
                            
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="copy_isp" name="copy_sections[]" value="isp" checked>
                                    <label class="custom-control-label" for="copy_isp">
                                        <strong>Informasi ISP</strong>
                                        <br><small class="text-muted">Nama ISP, tagline, nama POP (tanpa logo)</small>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="copy_address" name="copy_sections[]" value="address">
                                    <label class="custom-control-label" for="copy_address">
                                        <strong>Alamat</strong>
                                        <br><small class="text-muted">Alamat, provinsi, kota, kecamatan, kelurahan</small>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="copy_contact" name="copy_sections[]" value="contact">
                                    <label class="custom-control-label" for="copy_contact">
                                        <strong>Kontak</strong>
                                        <br><small class="text-muted">Telepon, WhatsApp, email, social media</small>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="copy_invoice" name="copy_sections[]" value="invoice" checked>
                                    <label class="custom-control-label" for="copy_invoice">
                                        <strong>Pengaturan Invoice</strong>
                                        <br><small class="text-muted">Prefix, jatuh tempo, catatan, footer</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="copy_tax" name="copy_sections[]" value="tax" checked>
                                    <label class="custom-control-label" for="copy_tax">
                                        <strong>Pengaturan Pajak (PPN)</strong>
                                        <br><small class="text-muted">PPN enabled, persentase, metode</small>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="copy_business" name="copy_sections[]" value="business">
                                    <label class="custom-control-label" for="copy_business">
                                        <strong>Informasi Bisnis</strong>
                                        <br><small class="text-muted">NPWP, NIB, nomor izin ISP</small>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="copy_bank" name="copy_sections[]" value="bank">
                                    <label class="custom-control-label" for="copy_bank">
                                        <strong>Rekening Bank</strong>
                                        <br><small class="text-muted">Daftar rekening bank untuk transfer manual</small>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="copy_notifications" name="copy_sections[]" value="notifications">
                                    <label class="custom-control-label" for="copy_notifications">
                                        <strong>Template Notifikasi</strong>
                                        <br><small class="text-muted">Template pesan dan event (tanpa API key)</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Yang TIDAK akan disalin:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Logo dan Favicon (unique per ISP)</li>
                            <li>Payment Gateway credentials (unique per merchant)</li>
                            <li>SMTP, WhatsApp API Key, Telegram Bot Token (sensitive)</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" id="btnCopy">
                        <i class="fas fa-copy mr-2"></i>Salin Pengaturan
                    </button>
                </form>
            </div>
        </div>

        <!-- Preview -->
        <div class="row" id="previewSection" style="display: none;">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title text-white"><i class="fas fa-file-import mr-2"></i>Sumber</h3>
                    </div>
                    <div class="card-body" id="sourcePreview">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success">
                        <h3 class="card-title text-white"><i class="fas fa-file-export mr-2"></i>Target (Setelah)</h3>
                    </div>
                    <div class="card-body" id="targetPreview">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function() {
    // Select2 sudah diinisialisasi secara global di layout admin

    // Prevent selecting same user
    $('#sourceUser, #targetUser').on('change', function() {
        const sourceId = $('#sourceUser').val();
        const targetId = $('#targetUser').val();
        
        if (sourceId && targetId && sourceId === targetId) {
            toastr.warning('Sumber dan target tidak boleh sama');
            $(this).val('').trigger('change');
        }
        
        // Load preview if both selected
        if (sourceId && targetId && sourceId !== targetId) {
            loadPreview(sourceId, targetId);
        } else {
            $('#previewSection').hide();
        }
    });

    function loadPreview(sourceId, targetId) {
        // Load source data
        $.get(`{{ url('admin/pop-settings/preview') }}/${sourceId}`, function(data) {
            let html = '<h6>Informasi ISP</h6>';
            html += `<p><strong>Nama:</strong> ${data.isp_name || '-'}<br>`;
            html += `<strong>POP:</strong> ${data.pop_name || '-'}</p>`;
            html += '<h6>Invoice</h6>';
            html += `<p><strong>Prefix:</strong> ${data.invoice_prefix || 'INV'}<br>`;
            html += `<strong>PPN:</strong> ${data.ppn_enabled ? data.ppn_percentage + '%' : 'Tidak aktif'}</p>`;
            $('#sourcePreview').html(html);
        });

        $.get(`{{ url('admin/pop-settings/preview') }}/${targetId}`, function(data) {
            let html = '<h6>Informasi ISP</h6>';
            html += `<p><strong>Nama:</strong> ${data.isp_name || '-'}<br>`;
            html += `<strong>POP:</strong> ${data.pop_name || '-'}</p>`;
            html += '<h6>Invoice</h6>';
            html += `<p><strong>Prefix:</strong> ${data.invoice_prefix || 'INV'}<br>`;
            html += `<strong>PPN:</strong> ${data.ppn_enabled ? data.ppn_percentage + '%' : 'Tidak aktif'}</p>`;
            html += '<div class="alert alert-info mt-3 mb-0"><small>Data ini akan ditimpa dengan data dari sumber</small></div>';
            $('#targetPreview').html(html);
        });

        $('#previewSection').show();
    }

    // Submit copy
    $('#copyForm').on('submit', function(e) {
        e.preventDefault();
        
        const sections = $('input[name="copy_sections[]"]:checked');
        if (sections.length === 0) {
            toastr.warning('Pilih minimal satu bagian untuk disalin');
            return;
        }

        Swal.fire({
            title: 'Konfirmasi Salin?',
            text: 'Data target akan ditimpa dengan data dari sumber',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Salin!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#btnCopy');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Menyalin...');

                $.ajax({
                    url: '{{ route("admin.pop-settings.copy") }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Gagal menyalin pengaturan');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-copy mr-2"></i>Salin Pengaturan');
                    }
                });
            }
        });
    });
});
</script>
@endpush
