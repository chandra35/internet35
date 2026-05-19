@extends('layouts.pelanggan')

@section('title', 'Riwayat Pembayaran')

@section('page-title', 'Riwayat Pembayaran')

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="card-title"><i class="fas fa-history mr-1"></i> Riwayat Pembayaran</span>
        @if($payments->total() > 0)
        <small class="text-muted">{{ $payments->total() }} transaksi</small>
        @endif
    </div>
    <div class="card-body p-0">
        @forelse($payments as $payment)
        <div class="d-flex align-items-center px-3 py-3 border-bottom invoice-row">
            <div class="flex-grow-1" style="min-width:0;">
                <div class="d-flex align-items-center flex-wrap" style="gap:5px;">
                    <code style="font-size:0.75rem;color:#555;">{{ $payment->external_id ?? $payment->payment_number }}</code>
                    <span class="badge badge-{{ $payment->status_color }}" style="font-size:0.63rem;">{{ $payment->status_label }}</span>
                </div>
                <div class="text-muted mt-1" style="font-size:0.75rem;">
                    <i class="fas fa-calendar mr-1"></i>{{ $payment->created_at->format('d M Y H:i') }}
                    · {{ $payment->paymentGateway?->name ?? ucfirst($payment->payment_method) }}
                    @if($payment->invoice)
                    · <a href="{{ route('pelanggan.invoice', $payment->invoice) }}" class="text-muted">{{ $payment->invoice->invoice_number }}</a>
                    @endif
                    @if($payment->status === 'success' && $payment->paid_at)
                    · <span class="text-success">Lunas {{ $payment->paid_at->format('d M Y') }}</span>
                    @endif
                </div>
            </div>
            <div class="text-right ml-3 flex-shrink-0">
                <div style="font-size:0.9rem;font-weight:700;color:#1a1a1a;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="fas fa-history fa-3x mb-3 d-block"></i>
            <div style="font-size:0.88rem;">Belum ada riwayat pembayaran</div>
            <small>Riwayat pembayaran Anda akan ditampilkan di sini.</small>
        </div>
        @endforelse
    </div>
    @if($payments->hasPages())
    <div class="card-footer py-2">
        <div class="d-flex justify-content-center">{{ $payments->links() }}</div>
    </div>
    @endif
</div>
@endsection
