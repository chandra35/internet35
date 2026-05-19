@extends('layouts.pelanggan')

@section('title', 'Tagihan')

@section('page-title', 'Tagihan Saya')

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i> Semua Tagihan</span>
        @if($invoices->total() > 0)
        <small class="text-muted">{{ $invoices->total() }} tagihan</small>
        @endif
    </div>
    <div class="card-body p-0">
        @forelse($invoices as $invoice)
        <div class="d-flex align-items-center px-3 py-3 border-bottom invoice-row">
            <div class="flex-grow-1" style="min-width:0;">
                <div class="d-flex align-items-center flex-wrap" style="gap:6px;">
                    <code style="font-size:0.78rem;color:#1565c0;">{{ $invoice->invoice_number }}</code>
                    <span class="badge badge-{{ $invoice->status_color }}" style="font-size:0.65rem;">{{ $invoice->status_label }}</span>
                </div>
                <div class="text-muted mt-1" style="font-size:0.75rem;">
                    <i class="fas fa-calendar-alt mr-1"></i>{{ $invoice->period_start?->format('M Y') ?? '-' }}
                    @if($invoice->due_date)
                    · Jatuh tempo <strong class="{{ $invoice->due_date->isPast() && $invoice->status !== 'paid' ? 'text-danger' : '' }}">{{ $invoice->due_date->format('d M Y') }}</strong>
                    @if($invoice->status === 'overdue') <span class="text-danger">({{ $invoice->due_date->diffInDays(now()) }} hari)</span>@endif
                    @endif
                </div>
            </div>
            <div class="text-right ml-3 flex-shrink-0">
                <div style="font-size:0.9rem;font-weight:700;color:#1a1a1a;">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</div>
                @if(in_array($invoice->status, ['unpaid', 'overdue', 'pending']))
                <a href="{{ route('pelanggan.invoice', $invoice) }}" class="btn btn-xs btn-primary mt-1">
                    <i class="fas fa-credit-card mr-1"></i>Bayar
                </a>
                @else
                <a href="{{ route('pelanggan.invoice', $invoice) }}" class="btn btn-xs btn-outline-secondary mt-1">
                    <i class="fas fa-eye mr-1"></i>Detail
                </a>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="fas fa-file-invoice fa-3x mb-3 d-block"></i>
            <div style="font-size:0.88rem;">Belum ada tagihan</div>
            <small>Tagihan akan muncul di sini saat periode penagihan dimulai.</small>
        </div>
        @endforelse
    </div>
    @if($invoices->hasPages())
    <div class="card-footer py-2">
        <div class="d-flex justify-content-center">{{ $invoices->links() }}</div>
    </div>
    @endif
</div>
@endsection
