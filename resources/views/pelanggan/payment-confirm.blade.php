@extends('layouts.pelanggan')

@section('title', 'Konfirmasi Pembayaran')

@section('page-title', 'Konfirmasi Pembayaran')

@push('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-credit-card mr-2"></i>
                    Konfirmasi Pembayaran
                </h3>
            </div>
            <div class="card-body">
                <!-- Payment Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">Nomor Referensi</h6>
                        <h4><code>{{ $payment->external_id ?? $payment->payment_number }}</code></h4>
                    </div>
                    <div class="col-md-6 text-md-right">
                        <h6 class="text-muted">Total Pembayaran</h6>
                        <h3 class="text-primary">Rp {{ number_format($payment->amount, 0, ',', '.') }}</h3>
                    </div>
                </div>
                
                <hr>
                
                @if($payment->status === 'pending')
                <!-- Payment Instructions -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle mr-2"></i>Instruksi Pembayaran</h5>
                    @if($payment->paymentGateway)
                        @if($payment->paymentGateway->type === 'bank_transfer')
                        <p class="mb-2">Silakan transfer ke rekening berikut:</p>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="120">Bank</td>
                                <td><strong>{{ $payment->paymentGateway->config['bank_name'] ?? $payment->paymentGateway->name }}</strong></td>
                            </tr>
                            <tr>
                                <td>No. Rekening</td>
                                <td>
                                    <strong id="accountNumber">{{ $payment->paymentGateway->config['account_number'] ?? '-' }}</strong>
                                    <button class="btn btn-xs btn-outline-primary ml-1" onclick="copyText('accountNumber')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Atas Nama</td>
                                <td><strong>{{ $payment->paymentGateway->config['account_name'] ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td>Jumlah</td>
                                <td>
                                    <strong id="payAmount">{{ $payment->total_paid }}</strong>
                                    <button class="btn btn-xs btn-outline-primary ml-1" onclick="copyText('payAmount')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                            </tr>
                        </table>
                        @elseif($payment->paymentGateway->type === 'ewallet')
                        <p class="mb-2">Silakan transfer ke:</p>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="120">{{ $payment->paymentGateway->name }}</td>
                                <td>
                                    <strong id="ewalletNumber">{{ $payment->paymentGateway->config['phone_number'] ?? '-' }}</strong>
                                    <button class="btn btn-xs btn-outline-primary ml-1" onclick="copyText('ewalletNumber')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Atas Nama</td>
                                <td><strong>{{ $payment->paymentGateway->config['account_name'] ?? '-' }}</strong></td>
                            </tr>
                        </table>
                        @endif
                    @endif
                </div>

                <!-- Upload Proof -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Upload Bukti Transfer</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group text-center">
                            <div id="proofPreview" class="mb-3" style="display: none;">
                                <img id="proofImage" src="" class="img-fluid rounded" style="max-height: 300px;">
                            </div>
                            <input type="file" id="proofFile" class="d-none" accept="image/*">
                            <button type="button" class="btn btn-outline-primary btn-lg" id="btnSelectProof">
                                <i class="fas fa-image mr-2"></i> Pilih Gambar Bukti Transfer
                            </button>
                        </div>
                        <div class="form-group">
                            <label>Catatan (Opsional)</label>
                            <textarea id="paymentNotes" class="form-control" rows="2" placeholder="Nama pengirim, waktu transfer, dll."></textarea>
                        </div>
                        <button type="button" class="btn btn-primary btn-lg btn-block" id="btnConfirm" disabled>
                            <i class="fas fa-check mr-2"></i> Konfirmasi Pembayaran
                        </button>
                    </div>
                </div>
                
                @elseif($payment->status === 'verifying')
                <div class="alert alert-warning">
                    <h5><i class="fas fa-clock mr-2"></i>Menunggu Verifikasi</h5>
                    <p class="mb-0">Bukti pembayaran Anda sedang diverifikasi oleh admin. Proses ini biasanya memakan waktu 1x24 jam.</p>
                </div>
                
                @if($payment->payment_proof)
                <div class="text-center">
                    <img src="{{ asset('storage/payments/' . $payment->payment_proof) }}" class="img-fluid rounded" style="max-height: 300px;">
                    <p class="text-muted mt-2">Bukti transfer yang dikirim</p>
                </div>
                @endif
                
                @elseif($payment->status === 'success')
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle mr-2"></i>Pembayaran Berhasil</h5>
                    <p class="mb-0">Pembayaran Anda telah dikonfirmasi pada {{ $payment->verified_at?->format('d M Y H:i') }}.</p>
                </div>
                
                @elseif($payment->status === 'failed' || $payment->status === 'cancelled')
                <div class="alert alert-danger">
                    <h5><i class="fas fa-times-circle mr-2"></i>Pembayaran {{ $payment->status === 'cancelled' ? 'Dibatalkan' : 'Gagal' }}</h5>
                    <p class="mb-0">{{ $payment->notes ?? 'Silakan hubungi admin untuk informasi lebih lanjut.' }}</p>
                </div>
                @endif
                
                <hr>
                
                <div class="d-flex justify-content-between">
                    <a href="{{ route('pelanggan.invoices') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                    @if($payment->status === 'pending')
                    <button type="button" class="btn btn-outline-danger" id="btnCancel">
                        <i class="fas fa-times mr-1"></i> Batalkan
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cropper Modal -->
<div class="modal fade" id="cropperModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crop Gambar</h5>
            </div>
            <div class="modal-body">
                <img id="cropperImage" src="" style="max-width: 100%;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnCropApply">
                    <i class="fas fa-crop mr-1"></i> Terapkan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
let cropper = null;
let proofBase64 = null;

$('#btnSelectProof').on('click', function() {
    $('#proofFile').click();
});

$('#proofFile').on('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            $('#cropperImage').attr('src', e.target.result);
            $('#cropperModal').modal('show');
        };
        reader.readAsDataURL(file);
    }
});

$('#cropperModal').on('shown.bs.modal', function() {
    cropper = new Cropper(document.getElementById('cropperImage'), {
        aspectRatio: NaN,
        viewMode: 1,
    });
}).on('hidden.bs.modal', function() {
    if (cropper) { cropper.destroy(); cropper = null; }
});

$('#btnCropApply').on('click', function() {
    if (cropper) {
        const canvas = cropper.getCroppedCanvas({ maxWidth: 1200, maxHeight: 1200 });
        proofBase64 = canvas.toDataURL('image/jpeg', 0.8);
        
        $('#proofImage').attr('src', proofBase64);
        $('#proofPreview').show();
        $('#btnSelectProof').html('<i class="fas fa-image mr-2"></i> Ganti Gambar');
        $('#btnConfirm').prop('disabled', false);
        
        $('#cropperModal').modal('hide');
    }
});

$('#btnConfirm').on('click', function() {
    if (!proofBase64) {
        toastr.error('Silakan upload bukti transfer');
        return;
    }
    
    const $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Mengirim...');
    
    $.post('{{ route("pelanggan.payment.confirm-manual", $payment) }}', {
        proof: proofBase64,
        notes: $('#paymentNotes').val()
    }, function(response) {
        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: response.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        }
    }).fail(function(xhr) {
        toastr.error(xhr.responseJSON?.error || 'Terjadi kesalahan');
        $btn.prop('disabled', false).html('<i class="fas fa-check mr-2"></i> Konfirmasi Pembayaran');
    });
});

$('#btnCancel').on('click', function() {
    Swal.fire({
        title: 'Batalkan Pembayaran?',
        text: 'Anda yakin ingin membatalkan pembayaran ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Tidak'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('{{ route("pelanggan.payment.cancel", $payment) }}', function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    window.location.href = '{{ route("pelanggan.invoices") }}';
                }
            }).fail(function(xhr) {
                toastr.error(xhr.responseJSON?.error || 'Terjadi kesalahan');
            });
        }
    });
});

function copyText(elementId) {
    const text = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(text);
    toastr.success('Tersalin ke clipboard!');
}
</script>
@endpush
