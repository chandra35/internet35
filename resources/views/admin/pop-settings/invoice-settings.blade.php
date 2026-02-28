@extends('layouts.admin')

@section('title', 'Pengaturan Invoice')

@section('page-title', 'Pengaturan Invoice & Pajak')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Pengaturan Invoice</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-3">
        @include('admin.pop-settings.partials.sidebar')
    </div>
    <div class="col-lg-9">
        @if($popUsers && auth()->user()->hasRole('superadmin'))
        <div class="card card-outline card-info mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <i class="fas fa-user-shield text-info fa-lg"></i>
                        <strong class="ml-2">Mode Superadmin:</strong>
                    </div>
                    <div class="col">
                        <select class="form-control select2" id="selectPopUser">
                            <option value="">-- Pilih Admin POP --</option>
                            @foreach($popUsers as $popUser)
                                <option value="{{ $popUser->id }}" {{ $userId == $popUser->id ? 'selected' : '' }}>
                                    {{ $popUser->name }} ({{ $popUser->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <form id="invoiceForm">
            @csrf
            @if($userId)
            <input type="hidden" name="user_id" value="{{ $userId }}">
            @endif

            <div class="row">
                <div class="col-lg-8">
                    <!-- Invoice Settings -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0"><i class="fas fa-file-invoice mr-2"></i>Pengaturan Invoice</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnPreview" onclick="openFullPreview()">
                                <i class="fas fa-eye mr-1"></i>Preview Invoice
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Prefix Invoice</label>
                                        <input type="text" name="invoice_prefix" class="form-control preview-trigger" 
                                               value="{{ $popSetting->invoice_prefix ?? 'INV' }}" 
                                               placeholder="INV" maxlength="10">
                                        <small class="text-muted">Contoh: INV-2024-0001</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jatuh Tempo (Hari)</label>
                                        <input type="number" name="invoice_due_days" class="form-control preview-trigger" 
                                               value="{{ $popSetting->invoice_due_days ?? 7 }}" 
                                               min="1" max="30">
                                        <small class="text-muted">Hari setelah invoice dibuat</small>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Footer Invoice</label>
                                        <input type="text" name="invoice_footer" class="form-control preview-trigger" 
                                               value="{{ $popSetting->invoice_footer }}" 
                                               placeholder="Terima kasih atas pembayaran Anda">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label>Catatan Invoice Default</label>
                                        <textarea name="invoice_notes" class="form-control preview-trigger" rows="2" 
                                                  placeholder="Catatan yang akan ditampilkan di setiap invoice">{{ $popSetting->invoice_notes }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tax Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-percentage mr-2"></i>Pengaturan Pajak (PPN)</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input preview-trigger" id="ppn_enabled" name="ppn_enabled" 
                                               value="1" {{ $popSetting->ppn_enabled ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="ppn_enabled">
                                            <strong>Aktifkan PPN</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">Jika diaktifkan, PPN akan ditambahkan ke setiap invoice</small>
                                </div>
                            </div>
                            
                            <div id="ppnSettings" style="{{ $popSetting->ppn_enabled ? '' : 'display:none;' }}">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Persentase PPN (%)</label>
                                            <div class="input-group">
                                                <input type="number" name="ppn_percentage" class="form-control preview-trigger" 
                                                       value="{{ $popSetting->ppn_percentage ?? 11 }}" 
                                                       min="0" max="100" step="0.5">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                            <small class="text-muted">PPN standar Indonesia: 11%</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Metode Perhitungan</label>
                                            <select name="ppn_method" class="form-control">
                                                <option value="exclusive" {{ ($popSetting->ppn_method ?? 'exclusive') == 'exclusive' ? 'selected' : '' }}>
                                                    Exclusive (PPN ditambahkan)
                                                </option>
                                                <option value="inclusive" {{ ($popSetting->ppn_method ?? '') == 'inclusive' ? 'selected' : '' }}>
                                                    Inclusive (Sudah termasuk)
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Tampilkan di Invoice</label>
                                            <select name="ppn_display" class="form-control">
                                                <option value="separate" {{ ($popSetting->ppn_display ?? 'separate') == 'separate' ? 'selected' : '' }}>
                                                    Terpisah (Subtotal + PPN)
                                                </option>
                                                <option value="included" {{ ($popSetting->ppn_display ?? '') == 'included' ? 'selected' : '' }}>
                                                    Termasuk dalam total
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Preview Panel -->
                <div class="col-lg-4">
                    <div class="card sticky-top" style="top: 70px;">
                        <div class="card-header bg-gradient-primary text-white">
                            <h3 class="card-title mb-0"><i class="fas fa-eye mr-2"></i>Live Preview</h3>
                        </div>
                        <div class="card-body p-2" style="max-height: 500px; overflow-y: auto;">
                            <div id="livePreviewContent">
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                    <p class="mb-0">Memuat preview...</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <button type="button" class="btn btn-primary btn-sm" onclick="openFullPreview()">
                                <i class="fas fa-external-link-alt mr-1"></i>Buka Preview Penuh
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Syarat & Ketentuan -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-contract mr-2"></i>Syarat & Ketentuan Invoice</h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <label>Syarat & Ketentuan</label>
                        <textarea name="invoice_terms" class="form-control preview-trigger" rows="4" 
                                  placeholder="Syarat dan ketentuan yang berlaku pada setiap invoice">{{ $popSetting->invoice_terms }}</textarea>
                        <small class="text-muted">Akan ditampilkan di bagian bawah invoice</small>
                    </div>
                </div>
            </div>

            <!-- Business Info -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-briefcase mr-2"></i>Informasi Bisnis (untuk Pajak)</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama Perusahaan (Legal)</label>
                                <input type="text" name="business_name" class="form-control" 
                                       value="{{ $popSetting->business_name }}" 
                                       placeholder="PT. Nama Perusahaan">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>NPWP</label>
                                <input type="text" name="npwp" class="form-control npwp-mask" 
                                       value="{{ $popSetting->npwp }}" 
                                       placeholder="00.000.000.0-000.000">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>NIB (Nomor Induk Berusaha)</label>
                                <input type="text" name="nib" class="form-control" 
                                       value="{{ $popSetting->nib }}" maxlength="20">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nomor Izin ISP</label>
                                <input type="text" name="isp_license_number" class="form-control" 
                                       value="{{ $popSetting->isp_license_number }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bank Account -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-university mr-2"></i>Rekening Bank (Transfer Manual)</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-success" id="addBankAccount">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="bankAccounts">
                        @php
                            $bankAccounts = $popSetting->bank_accounts ?? [];
                        @endphp
                        @forelse($bankAccounts as $index => $bank)
                        <div class="bank-account-item border rounded p-3 mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group mb-md-0">
                                        <label>Nama Bank</label>
                                        <select name="bank_accounts[{{ $index }}][bank_name]" class="form-control select2">
                                            <option value="">-- Pilih --</option>
                                            @foreach(['BCA', 'BNI', 'BRI', 'Mandiri', 'BSI', 'CIMB Niaga', 'Permata', 'Danamon', 'OCBC NISP', 'BTN', 'Maybank', 'Sinarmas', 'BTPN', 'Jenius'] as $bankName)
                                            <option value="{{ $bankName }}" {{ ($bank['bank_name'] ?? '') == $bankName ? 'selected' : '' }}>{{ $bankName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-md-0">
                                        <label>No. Rekening</label>
                                        <input type="text" name="bank_accounts[{{ $index }}][account_number]" class="form-control" 
                                               value="{{ $bank['account_number'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-md-0">
                                        <label>Atas Nama</label>
                                        <input type="text" name="bank_accounts[{{ $index }}][account_name]" class="form-control" 
                                               value="{{ $bank['account_name'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-md-0">
                                        <label>Cabang</label>
                                        <input type="text" name="bank_accounts[{{ $index }}][branch]" class="form-control" 
                                               value="{{ $bank['branch'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger btn-sm remove-bank" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-4" id="noBankMessage">
                            <i class="fas fa-university fa-2x mb-2"></i>
                            <p>Belum ada rekening bank. Klik tombol + untuk menambah.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary" id="btnSave">
                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function() {
    // Select2 sudah diinisialisasi secara global di layout admin

    // POP user selector
    $('#selectPopUser').on('change', function() {
        const userId = $(this).val();
        window.location.href = '{{ route("admin.pop-settings.invoice-settings") }}' + (userId ? '?user_id=' + userId : '');
    });

    // NPWP mask
    $('.npwp-mask').on('input', function() {
        let val = $(this).val().replace(/\D/g, '');
        if (val.length > 15) val = val.substring(0, 15);
        let formatted = '';
        if (val.length > 0) formatted += val.substring(0, 2);
        if (val.length > 2) formatted += '.' + val.substring(2, 5);
        if (val.length > 5) formatted += '.' + val.substring(5, 8);
        if (val.length > 8) formatted += '.' + val.substring(8, 9);
        if (val.length > 9) formatted += '-' + val.substring(9, 12);
        if (val.length > 12) formatted += '.' + val.substring(12, 15);
        $(this).val(formatted);
    });

    // Toggle PPN settings
    $('#ppn_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#ppnSettings').slideDown();
        } else {
            $('#ppnSettings').slideUp();
        }
    });

    // Update calculation example
    function updateCalculationExample() {
        const percentage = parseFloat($('input[name="ppn_percentage"]').val()) || 11;
        const method = $('select[name="ppn_method"]').val();
        const basePrice = 100000;
        
        let result, text;
        if (method === 'exclusive') {
            result = basePrice + (basePrice * percentage / 100);
            text = `Harga Paket: Rp 100.000 + PPN ${percentage}% = <strong>Rp ${result.toLocaleString('id-ID')}</strong>`;
        } else {
            const beforeTax = basePrice / (1 + percentage / 100);
            const taxAmount = basePrice - beforeTax;
            text = `Harga Rp 100.000 sudah termasuk PPN ${percentage}% (Rp ${Math.round(taxAmount).toLocaleString('id-ID')})`;
        }
        
        $('#calculationExample').html(text);
    }

    $('input[name="ppn_percentage"], select[name="ppn_method"]').on('change input', updateCalculationExample);
    updateCalculationExample();

    // Bank accounts management
    let bankIndex = {{ count($bankAccounts) }};

    $('#addBankAccount').on('click', function() {
        $('#noBankMessage').hide();
        const html = `
            <div class="bank-account-item border rounded p-3 mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group mb-md-0">
                            <label>Nama Bank</label>
                            <select name="bank_accounts[${bankIndex}][bank_name]" class="form-control select2">
                                <option value="">-- Pilih --</option>
                                @foreach(['BCA', 'BNI', 'BRI', 'Mandiri', 'BSI', 'CIMB Niaga', 'Permata', 'Danamon', 'OCBC NISP', 'BTN', 'Maybank', 'Sinarmas', 'BTPN', 'Jenius'] as $bankName)
                                <option value="{{ $bankName }}">{{ $bankName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-md-0">
                            <label>No. Rekening</label>
                            <input type="text" name="bank_accounts[${bankIndex}][account_number]" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-md-0">
                            <label>Atas Nama</label>
                            <input type="text" name="bank_accounts[${bankIndex}][account_name]" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-md-0">
                            <label>Cabang</label>
                            <input type="text" name="bank_accounts[${bankIndex}][branch]" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm remove-bank" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#bankAccounts').append(html);
        // Initialize select2 for newly added bank dropdown
        $('#bankAccounts').find('.bank-account-item:last .select2').select2({
            theme: 'bootstrap-5'
        });
        bankIndex++;
    });

    $(document).on('click', '.remove-bank', function() {
        $(this).closest('.bank-account-item').remove();
        if ($('.bank-account-item').length === 0) {
            $('#noBankMessage').show();
        }
    });

    // POP user selector (superadmin)
    $('#selectPopUser').on('change', function() {
        const userId = $(this).val();
        window.location.href = '{{ route("admin.pop-settings.invoice-settings") }}' + (userId ? '?user_id=' + userId : '');
    });

    // Form submission
    $('#invoiceForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const btn = $('#btnSave');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...');

        $.ajax({
            url: '{{ route("admin.pop-settings.update-invoice-settings") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    updateLivePreview(); // Refresh preview after save
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(field => {
                        toastr.error(errors[field][0]);
                    });
                } else {
                    toastr.error('Terjadi kesalahan!');
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Simpan Perubahan');
            }
        });
    });

    // Live Preview Functions
    let previewTimeout = null;
    
    function updateLivePreview() {
        const formData = $('#invoiceForm').serialize();
        
        $.ajax({
            url: '{{ route("admin.pop-settings.invoice-live-preview") }}',
            type: 'POST',
            data: formData,
            success: function(html) {
                $('#livePreviewContent').html(html);
            },
            error: function() {
                $('#livePreviewContent').html('<div class="text-center py-3 text-muted"><i class="fas fa-exclamation-triangle"></i> Gagal memuat preview</div>');
            }
        });
    }

    function debouncedPreview() {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(updateLivePreview, 500);
    }

    // Trigger live preview on input change
    $(document).on('input change', '.preview-trigger', function() {
        debouncedPreview();
    });

    // Also update on bank account changes
    $(document).on('input change', '[name^="bank_accounts"]', function() {
        debouncedPreview();
    });

    // Initial preview load
    updateLivePreview();
});

// Open full preview in new window
function openFullPreview() {
    const userId = '{{ $userId ?? "" }}';
    const url = '{{ route("admin.pop-settings.invoice-preview") }}' + (userId ? '?user_id=' + userId : '');
    window.open(url, '_blank', 'width=900,height=800,scrollbars=yes');
}
</script>
@endpush
