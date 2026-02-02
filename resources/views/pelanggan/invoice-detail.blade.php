@extends('layouts.pelanggan')

@section('title', 'Invoice #' . $invoice->invoice_number)

@section('page-title', 'Detail Tagihan')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <!-- Invoice Detail -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-file-invoice mr-2"></i>
                        Invoice #{{ $invoice->invoice_number }}
                    </h3>
                    <span class="badge badge-{{ $invoice->status_color }} badge-lg">
                        {{ $invoice->status_label }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-6">
                        <strong>Ditagihkan Kepada:</strong><br>
                        {{ $customer->name }}<br>
                        <small class="text-muted">
                            {{ $customer->address }}<br>
                            {{ $customer->village?->name }}, {{ $customer->district?->name }}<br>
                            {{ $customer->city?->name }}, {{ $customer->province?->name }}
                        </small>
                    </div>
                    <div class="col-6 text-right">
                        <strong>ID Pelanggan:</strong><br>
                        <code>{{ $customer->customer_id }}</code><br>
                        <small class="text-muted">
                            Periode: {{ $invoice->period_start?->format('d M') }} - {{ $invoice->period_end?->format('d M Y') }}<br>
                            Jatuh Tempo: {{ $invoice->due_date?->format('d M Y') }}
                        </small>
                    </div>
                </div>
                
                <hr>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Deskripsi</th>
                            <th class="text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>{{ $invoice->description ?? 'Layanan Internet Bulanan' }}</strong><br>
                                <small class="text-muted">Paket: {{ $customer->package?->name }}</small>
                            </td>
                            <td class="text-right">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($invoice->discount_amount > 0)
                        <tr class="text-success">
                            <td>Diskon</td>
                            <td class="text-right">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if($invoice->tax_amount > 0)
                        <tr>
                            <td>Pajak</td>
                            <td class="text-right">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <td>Total</td>
                            <td class="text-right h4 mb-0">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
                
                @if($invoice->notes)
                <div class="alert alert-info mt-3">
                    <small><i class="fas fa-info-circle mr-1"></i> {{ $invoice->notes }}</small>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Payment History for this Invoice -->
        @if($invoice->payments->count() > 0)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Riwayat Pembayaran Invoice Ini</h3>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Metode</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->created_at->format('d M Y H:i') }}</td>
                            <td>{{ ucfirst($payment->payment_method) }}</td>
                            <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge badge-{{ $payment->status_color }}">
                                    {{ $payment->status_label }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
    
    <div class="col-lg-4">
        <!-- Payment Action -->
        @if(in_array($invoice->status, ['pending', 'overdue']))
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title text-white">
                    <i class="fas fa-credit-card mr-2"></i>Bayar Sekarang
                </h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <small class="text-muted">Total yang harus dibayar</small>
                    <h2 class="text-primary mb-0">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</h2>
                </div>
                
                @if($gateways->count() > 0)
                <form id="paymentForm">
                    <div class="form-group">
                        <label>Pilih Metode Pembayaran</label>
                        @foreach($gateways as $gateway)
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="gateway_{{ $gateway->id }}" name="gateway_id" 
                                   value="{{ $gateway->id }}" class="custom-control-input" 
                                   {{ $loop->first ? 'checked' : '' }}>
                            <label class="custom-control-label d-flex align-items-center" for="gateway_{{ $gateway->id }}">
                                @if($gateway->logo)
                                <img src="{{ asset('storage/' . $gateway->logo) }}" height="24" class="mr-2">
                                @endif
                                {{ $gateway->name }}
                                @if($gateway->fee > 0)
                                <small class="text-muted ml-auto">+ Rp {{ number_format($gateway->fee, 0, ',', '.') }}</small>
                                @endif
                            </label>
                        </div>
                        @endforeach
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg btn-block" id="btnPay">
                        <i class="fas fa-lock mr-2"></i> Bayar Sekarang
                    </button>
                </form>
                @else
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Metode pembayaran belum tersedia. Silakan hubungi admin.
                </div>
                @endif
            </div>
        </div>
        @elseif($invoice->status === 'paid')
        <div class="card">
            <div class="card-header bg-success">
                <h3 class="card-title text-white">
                    <i class="fas fa-check-circle mr-2"></i>Lunas
                </h3>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                <p class="mb-0">Invoice ini sudah dibayar pada<br>
                <strong>{{ $invoice->paid_at?->format('d M Y H:i') }}</strong></p>
            </div>
        </div>
        @endif
        
        <!-- Help -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-question-circle mr-2"></i>Bantuan</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Jika Anda mengalami masalah dengan pembayaran, silakan hubungi tim support kami.
                </p>
                <a href="https://wa.me/{{ config('app.support_whatsapp', '628123456789') }}" target="_blank" class="btn btn-success btn-block">
                    <i class="fab fa-whatsapp mr-2"></i> Chat WhatsApp
                </a>
            </div>
        </div>
        
        <a href="{{ route('pelanggan.invoices') }}" class="btn btn-outline-secondary btn-block">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Tagihan
        </a>
    </div>
</div>
@endsection

@push('js')
<script>
$('#paymentForm').on('submit', function(e) {
    e.preventDefault();
    
    const $btn = $('#btnPay');
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...');
    
    $.post('{{ route("pelanggan.pay", $invoice) }}', {
        gateway_id: $('input[name="gateway_id"]:checked').val()
    }, function(response) {
        if (response.success) {
            if (response.payment_url) {
                window.location.href = response.payment_url;
            } else {
                toastr.success(response.message);
                location.reload();
            }
        }
    }).fail(function(xhr) {
        toastr.error(xhr.responseJSON?.error || 'Terjadi kesalahan');
        $btn.prop('disabled', false).html('<i class="fas fa-lock mr-2"></i> Bayar Sekarang');
    });
});
</script>
@endpush
