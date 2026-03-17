@extends('layouts.pelanggan')

@section('title', 'Demo Pembayaran')

@section('page-title', 'Demo Pembayaran')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Demo Alert -->
        <div class="alert alert-info">
            <div class="d-flex align-items-center">
                <i class="fas fa-flask fa-2x mr-3"></i>
                <div>
                    <strong>Mode Demo</strong>
                    <p class="mb-0">Ini adalah simulasi pembayaran. Tidak ada transaksi nyata yang terjadi. 
                    Klik tombol <strong>"Bayar Sekarang (Demo)"</strong> untuk mensimulasikan pembayaran sukses.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title text-white">
                    <i class="fas fa-credit-card mr-2"></i>
                    Simulasi Pembayaran — {{ $payment->paymentGateway?->display_name ?? 'Payment Gateway' }}
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
                
                <!-- Simulated Payment Instructions -->
                <div class="card card-outline card-secondary mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-info-circle mr-2"></i>Instruksi Pembayaran (Demo)</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $gatewayType = $payment->paymentGateway?->gateway_type ?? 'unknown';
                            $response = $payment->gateway_response ?? [];
                        @endphp

                        @if($gatewayType === 'tripay')
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="150">Metode</td>
                                <td><strong>{{ $response['data']['payment_name'] ?? 'BRI Virtual Account' }}</strong></td>
                            </tr>
                            <tr>
                                <td>Kode Bayar</td>
                                <td>
                                    <strong id="payCode">{{ $response['data']['pay_code'] ?? '1234567890123456' }}</strong>
                                    <button class="btn btn-xs btn-outline-primary ml-1" onclick="copyText('payCode')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Jumlah</td>
                                <td><strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong></td>
                            </tr>
                        </table>

                        @if(isset($response['data']['instructions']))
                        <hr>
                        @foreach($response['data']['instructions'] as $instruction)
                        <h6>{{ $instruction['title'] ?? 'Petunjuk' }}</h6>
                        <ol class="pl-3">
                            @foreach($instruction['steps'] ?? [] as $step)
                            <li class="mb-1">{{ $step }}</li>
                            @endforeach
                        </ol>
                        @endforeach
                        @endif

                        @elseif($gatewayType === 'midtrans')
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="150">Order ID</td>
                                <td><strong>{{ $payment->payment_number }}</strong></td>
                            </tr>
                            @if(isset($response['va_numbers'][0]))
                            <tr>
                                <td>Bank</td>
                                <td><strong>{{ strtoupper($response['va_numbers'][0]['bank'] ?? 'BCA') }}</strong></td>
                            </tr>
                            <tr>
                                <td>No. Virtual Account</td>
                                <td>
                                    <strong id="vaNumber">{{ $response['va_numbers'][0]['va_number'] ?? '1234567890123456' }}</strong>
                                    <button class="btn btn-xs btn-outline-primary ml-1" onclick="copyText('vaNumber')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td>Jumlah</td>
                                <td><strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong></td>
                            </tr>
                        </table>

                        @elseif($gatewayType === 'xendit')
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="150">Invoice</td>
                                <td><strong>{{ $payment->payment_number }}</strong></td>
                            </tr>
                            @if(isset($response['available_banks'][0]))
                            <tr>
                                <td>Bank</td>
                                <td><strong>{{ $response['available_banks'][0]['bank_code'] ?? 'BCA' }}</strong></td>
                            </tr>
                            <tr>
                                <td>No. Rekening</td>
                                <td>
                                    <strong id="bankAccount">{{ $response['available_banks'][0]['bank_account_number'] ?? '1234567890' }}</strong>
                                    <button class="btn btn-xs btn-outline-primary ml-1" onclick="copyText('bankAccount')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td>Jumlah</td>
                                <td><strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong></td>
                            </tr>
                        </table>

                        @elseif($gatewayType === 'duitku')
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="150">Reference</td>
                                <td><strong>{{ $response['reference'] ?? $payment->external_reference }}</strong></td>
                            </tr>
                            <tr>
                                <td>VA Number</td>
                                <td>
                                    <strong id="duitkuVa">{{ $response['vaNumber'] ?? '1234567890123456' }}</strong>
                                    <button class="btn btn-xs btn-outline-primary ml-1" onclick="copyText('duitkuVa')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Jumlah</td>
                                <td><strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong></td>
                            </tr>
                        </table>

                        @else
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="150">Referensi</td>
                                <td><strong>{{ $payment->external_id }}</strong></td>
                            </tr>
                            <tr>
                                <td>Jumlah</td>
                                <td><strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong></td>
                            </tr>
                        </table>
                        @endif
                    </div>
                </div>

                <!-- Demo Action -->
                <div class="card card-outline card-success">
                    <div class="card-body text-center">
                        <i class="fas fa-flask fa-3x text-info mb-3"></i>
                        <h5>Simulasi Pembayaran</h5>
                        <p class="text-muted">
                            Klik tombol di bawah untuk mensimulasikan pembayaran berhasil.<br>
                            Sistem akan memproses seolah-olah pembayaran nyata diterima dari payment gateway.
                        </p>
                        
                        @if($customer->status === 'suspended')
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Akun Anda sedang diisolir.</strong> 
                            Jika pembayaran demo berhasil dan tidak ada tagihan lain yang tertunggak, 
                            isolir akan dibuka secara otomatis.
                        </div>
                        @endif

                        <button type="button" class="btn btn-success btn-lg" id="btnDemoPay">
                            <i class="fas fa-check-circle mr-2"></i> Bayar Sekarang (Demo)
                        </button>
                    </div>
                </div>

                <!-- Result (hidden initially) -->
                <div id="demoResult" style="display: none;">
                    <div class="card card-outline card-success">
                        <div class="card-header bg-success">
                            <h5 class="card-title text-white mb-0">
                                <i class="fas fa-check-circle mr-2"></i>Pembayaran Demo Berhasil!
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                                <h4 class="text-success">Pembayaran Berhasil</h4>
                                <p class="text-muted" id="demoResultMessage"></p>
                            </div>
                            
                            <div id="unsuspendBadge" style="display:none;" class="alert alert-success text-center">
                                <i class="fas fa-unlock mr-2"></i>
                                <strong>Isolir berhasil dibuka otomatis!</strong>
                                <p class="mb-0 mt-1">Profil PPPoE Anda telah dikembalikan ke paket semula.</p>
                            </div>

                            <h6 class="mt-3"><i class="fas fa-code mr-2"></i>Data Callback (seperti dari payment gateway)</h6>
                            <pre id="callbackData" class="bg-dark text-white p-3 rounded" style="max-height: 300px; overflow-y: auto; font-size: 12px;"></pre>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('pelanggan.invoices') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Tagihan
                        </a>
                        <a href="{{ route('pelanggan.payments') }}" class="btn btn-outline-primary">
                            <i class="fas fa-history mr-1"></i> Riwayat Pembayaran
                        </a>
                    </div>
                </div>

                <hr>
                
                <div class="d-flex justify-content-between" id="demoActions">
                    <a href="{{ route('pelanggan.invoices') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                    <button type="button" class="btn btn-outline-danger" id="btnCancelDemo">
                        <i class="fas fa-times mr-1"></i> Batalkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$('#btnDemoPay').on('click', function() {
    const $btn = $(this);
    
    Swal.fire({
        title: 'Simulasi Pembayaran?',
        html: 'Sistem akan mensimulasikan pembayaran sebesar <strong>Rp {{ number_format($payment->amount, 0, ",", ".") }}</strong> dan memproses seperti pembayaran asli.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: '<i class="fas fa-check mr-1"></i> Ya, Bayar (Demo)',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Memproses pembayaran...');
            
            $.post('{{ route("pelanggan.payment.demo-execute", $payment) }}', function(response) {
                if (response.success) {
                    // Show success result
                    $('#demoResult').show();
                    $('#demoResultMessage').text(response.message);
                    $btn.closest('.card-outline').hide();
                    $('#demoActions').hide();
                    
                    // Show callback data
                    if (response.callback_data) {
                        $('#callbackData').text(JSON.stringify(response.callback_data, null, 2));
                    }
                    
                    // Show unsuspend badge if applicable
                    if (response.unsuspended) {
                        $('#unsuspendBadge').show();
                    }
                    
                    toastr.success(response.message);
                }
            }).fail(function(xhr) {
                toastr.error(xhr.responseJSON?.error || 'Terjadi kesalahan');
                $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-2"></i> Bayar Sekarang (Demo)');
            });
        }
    });
});

$('#btnCancelDemo').on('click', function() {
    Swal.fire({
        title: 'Batalkan Pembayaran?',
        text: 'Anda yakin ingin membatalkan pembayaran demo ini?',
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
