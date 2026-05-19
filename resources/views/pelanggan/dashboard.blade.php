@extends('layouts.pelanggan')

@section('title', 'Dashboard')

@section('page-title', 'Halo, ' . explode(' ', $customer->name)[0])

@section('content')

{{-- ── STAT CARDS ─────────────────────────────────────── --}}
<div class="row mb-0" style="row-gap:0;">
    <div class="col-6 col-lg-3 mb-3">
        <div class="stat-card stat-{{ $connectionStatus['color'] === 'success' ? 'green' : ($connectionStatus['color'] === 'danger' ? 'red' : ($connectionStatus['color'] === 'warning' ? 'yellow' : 'blue')) }}">
            <div class="stat-icon"><i class="fas fa-wifi"></i></div>
            <div class="stat-value">{{ $connectionStatus['status'] }}</div>
            <div class="stat-label">Status Koneksi</div>
            <a href="{{ route('pelanggan.connection') }}" class="stat-link">Lihat detail →</a>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-3">
        <div class="stat-card stat-teal">
            <div class="stat-icon"><i class="fas fa-bolt"></i></div>
            <div class="stat-value">{{ $customer->package?->name ?? '-' }}</div>
            <div class="stat-label">Paket Aktif</div>
            <a href="{{ route('pelanggan.connection') }}" class="stat-link">Detail paket →</a>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-3">
        @php
            $dueColor = $daysUntilDue !== null && $daysUntilDue <= 0 ? 'red' : ($daysUntilDue !== null && $daysUntilDue <= 7 ? 'yellow' : 'blue');
            $dueSub = $daysUntilDue !== null ? ($daysUntilDue > 0 ? "{$daysUntilDue} hari lagi" : ($daysUntilDue == 0 ? 'Hari ini!' : 'Lewat ' . abs($daysUntilDue) . ' hari')) : 'Aktif sampai';
        @endphp
        <div class="stat-card stat-{{ $dueColor }}">
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-value">{{ $customer->active_until ? $customer->active_until->format('d M Y') : '–' }}</div>
            <div class="stat-label">{{ $dueSub }}</div>
            <a href="{{ route('pelanggan.invoices') }}" class="stat-link">Lihat tagihan →</a>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-3">
        <div class="stat-card {{ $totalUnpaid > 0 ? 'stat-red' : 'stat-green' }}">
            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
            @if($totalUnpaid > 0)
            <div class="stat-value" style="font-size:0.98rem;">Rp {{ number_format($totalUnpaid, 0, ',', '.') }}</div>
            <div class="stat-label">Belum dibayar</div>
            <a href="{{ route('pelanggan.invoices') }}" class="stat-link">Bayar sekarang →</a>
            @else
            <div class="stat-value"><i class="fas fa-check-circle"></i> Lunas</div>
            <div class="stat-label">Tidak ada tunggakan</div>
            <a href="{{ route('pelanggan.invoices') }}" class="stat-link">Riwayat →</a>
            @endif
        </div>
    </div>
</div>

{{-- ── PENDING INVOICE BANNERS ─────────────────────────── --}}
@foreach($pendingInvoices as $invoice)
<div class="invoice-banner {{ $invoice->status === 'overdue' ? 'overdue' : 'pending' }} mb-2">
    <i class="fas fa-bell text-{{ $invoice->status === 'overdue' ? 'danger' : 'warning' }} mr-3"></i>
    <div class="flex-grow-1">
        <span class="font-weight-semibold" style="font-size:0.85rem;">{{ $invoice->invoice_number }}</span>
        <span class="badge badge-{{ $invoice->status_color }} ml-1" style="font-size:0.65rem;">{{ $invoice->status_label }}</span>
        <div class="text-muted" style="font-size:0.75rem;">
            {{ $invoice->period_start?->format('M Y') }} · Jatuh tempo {{ $invoice->due_date?->format('d M Y') }} · <strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong>
        </div>
    </div>
    <a href="{{ route('pelanggan.invoice', $invoice) }}" class="btn btn-sm btn-primary ml-3 flex-shrink-0" style="white-space:nowrap;">
        <i class="fas fa-credit-card mr-1"></i>Bayar
    </a>
</div>
@endforeach

{{-- ── PERIOD STRIP ────────────────────────────────────── --}}
<div class="period-strip d-flex align-items-center flex-wrap" style="gap:0.5rem;">
    <div class="mr-auto">
        <span class="period-val"><i class="fas fa-calendar-check text-primary mr-1"></i>{{ $billingPeriod['month'] }}</span>
        <span class="period-label ml-2">{{ $billingPeriod['start'] }} – {{ $billingPeriod['end'] }}</span>
    </div>
    <div class="text-right">
        <span class="period-label">Hari ke {{ $billingPeriod['day_of_period'] }}/{{ $billingPeriod['total_days'] }}</span>
        <span class="period-label ml-2">· Jatuh tempo tgl <strong>{{ $customer->billing_day ?? '-' }}</strong></span>
    </div>
    <div class="w-100 mt-1">
        <div class="progress" style="height:5px;border-radius:3px;">
            <div class="progress-bar bg-primary" style="width:{{ round(($billingPeriod['day_of_period'] / max($billingPeriod['total_days'],1)) * 100) }}%;border-radius:3px;"></div>
        </div>
    </div>
</div>

{{-- ── BOTTOM SECTION ──────────────────────────────────── --}}
<div class="row">
    {{-- Subscription Info --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="card-title"><i class="fas fa-id-card mr-1"></i> Informasi Langganan</span>
                <a href="{{ route('pelanggan.connection') }}" class="small text-primary">Detail →</a>
            </div>
            <div class="card-body py-3">
                <div class="row" style="row-gap:10px;">
                    <div class="col-6 col-md-4">
                        <div class="text-muted" style="font-size:0.7rem;">ID Pelanggan</div>
                        <code style="font-size:0.8rem;">{{ $customer->customer_id }}</code>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted" style="font-size:0.7rem;">Username PPPoE</div>
                        <code style="font-size:0.8rem;">{{ $customer->pppoe_username }}</code>
                        <button type="button" class="btn btn-xs btn-link p-0 ml-1 text-primary" id="btnShowCredentials" title="Lihat password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted" style="font-size:0.7rem;">Router</div>
                        <span style="font-size:0.82rem;">{{ $customer->router?->name ?? '-' }}</span>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted" style="font-size:0.7rem;">Jatuh Tempo</div>
                        <span style="font-size:0.82rem;">Tgl {{ $customer->billing_day ?? '-' }} / bulan</span>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted" style="font-size:0.7rem;">Biaya Bulanan</div>
                        <span style="font-size:0.82rem;font-weight:600;">Rp {{ number_format($customer->monthly_fee, 0, ',', '.') }}</span>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="text-muted" style="font-size:0.7rem;">Alamat</div>
                        <span style="font-size:0.8rem;">{{ collect([$customer->address, $customer->village?->name, $customer->district?->name])->filter()->implode(', ') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Payments --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="card-title"><i class="fas fa-history mr-1"></i> Pembayaran Terakhir</span>
                <a href="{{ route('pelanggan.payments') }}" class="small text-primary">Semua →</a>
            </div>
            <div class="card-body p-0">
                @forelse($recentPayments as $payment)
                <div class="payment-item d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div style="font-size:0.82rem;font-weight:500;">{{ $payment->created_at->format('d M Y') }}</div>
                        <div class="text-muted" style="font-size:0.72rem;">
                            {{ $payment->paymentGateway?->name ?? ucfirst($payment->payment_method) }}
                            @if($payment->invoice) · <a href="{{ route('pelanggan.invoice', $payment->invoice) }}" class="text-muted">{{ $payment->invoice->invoice_number }}</a>@endif
                        </div>
                    </div>
                    <div class="text-right ml-2">
                        <div style="font-size:0.82rem;font-weight:700;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
                        <span class="badge badge-{{ $payment->status_color }} badge-sm">{{ $payment->status_label }}</span>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-clock fa-2x mb-2 d-block"></i>
                    <span style="font-size:0.8rem;">Belum ada riwayat pembayaran</span>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
    
@endsection

@push('js')
<script>
$(function() {
    $('#btnShowCredentials').on('click', function() {
        $.get('{{ route("pelanggan.credentials") }}', function(data) {
            Swal.fire({
                title: 'Kredensial PPPoE',
                html: `
                    <div class="text-left">
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold">Username</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" value="${data.username}" readonly id="pppUsername">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" onclick="copyToClipboard('pppUsername')"><i class="fas fa-copy"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="small font-weight-bold">Password</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" value="${data.password}" readonly id="pppPassword">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" onclick="copyToClipboard('pppPassword')"><i class="fas fa-copy"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>`,
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#1565c0',
            });
        });
    });
});
function copyToClipboard(elementId) {
    const el = document.getElementById(elementId);
    el.select(); document.execCommand('copy');
    toastr.success('Tersalin!');
}
</script>
@endpush
