@extends('layouts.pelanggan')

@section('title', 'Tagihan')

@section('page-title', 'Tagihan Saya')

@section('content')
@php
    $unpaidCount  = $invoices->getCollection()->whereIn('status', ['unpaid','overdue','pending'])->count();
    $unpaidAmount = $invoices->getCollection()->whereIn('status', ['unpaid','overdue','pending'])->sum('total_amount');
    $paidCount    = $invoices->getCollection()->where('status','paid')->count();
    $overdueCount = $invoices->getCollection()->where('status','overdue')->count();
@endphp

{{-- Summary strip --}}
@if($invoices->total() > 0)
<div class="row mb-2" style="margin-left:-5px;margin-right:-5px;">
    <div class="col-6 col-sm-3" style="padding:0 5px;">
        <div class="stat-card stat-blue mb-2">
            <i class="fas fa-file-invoice stat-icon"></i>
            <div class="stat-value">{{ $invoices->total() }}</div>
            <div class="stat-label">Total Tagihan</div>
        </div>
    </div>
    <div class="col-6 col-sm-3" style="padding:0 5px;">
        <div class="stat-card {{ $unpaidAmount > 0 ? 'stat-red' : 'stat-green' }} mb-2">
            <i class="fas fa-clock stat-icon"></i>
            <div class="stat-value" style="font-size:0.82rem;">{{ $unpaidAmount > 0 ? 'Rp '.number_format($unpaidAmount,0,',','.') : 'Lunas' }}</div>
            <div class="stat-label">Belum Dibayar</div>
        </div>
    </div>
    <div class="col-6 col-sm-3" style="padding:0 5px;">
        <div class="stat-card stat-green mb-2">
            <i class="fas fa-check-circle stat-icon"></i>
            <div class="stat-value">{{ $paidCount }}</div>
            <div class="stat-label">Sudah Lunas</div>
        </div>
    </div>
    <div class="col-6 col-sm-3" style="padding:0 5px;">
        <div class="stat-card {{ $overdueCount > 0 ? 'stat-red' : 'stat-teal' }} mb-2">
            <i class="fas fa-exclamation-triangle stat-icon"></i>
            <div class="stat-value">{{ $overdueCount }}</div>
            <div class="stat-label">Jatuh Tempo</div>
        </div>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="card-title"><i class="fas fa-list mr-1"></i> Daftar Tagihan</span>
        @if($invoices->total() > 0)
        <small class="text-muted">{{ $invoices->total() }} tagihan</small>
        @endif
    </div>
    <div class="card-body p-0">
        @forelse($invoices as $invoice)
        @php
            $borderColor = match($invoice->status) {
                'paid'      => '#28a745',
                'overdue'   => '#dc3545',
                'pending', 'unpaid' => '#ffc107',
                'cancelled' => '#6c757d',
                default     => '#17a2b8',
            };
            $bgColor = match($invoice->status) {
                'overdue'   => '#fff8f8',
                'pending', 'unpaid' => '#fffdf0',
                default     => '#fff',
            };
        @endphp
        <a href="{{ route('pelanggan.invoice', $invoice) }}" class="text-decoration-none invoice-row d-block"
           style="border-left:4px solid {{ $borderColor }}; background:{{ $bgColor }}; border-bottom:1px solid #f0f0f0;">
            <div class="d-flex align-items-center px-3 py-3">
                {{-- Left icon --}}
                <div class="mr-3 flex-shrink-0" style="width:34px;height:34px;border-radius:8px;background:{{ $borderColor }}20;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-{{ $invoice->status === 'paid' ? 'check' : ($invoice->status === 'overdue' ? 'exclamation' : 'file-invoice') }} fa-sm" style="color:{{ $borderColor }};"></i>
                </div>
                {{-- Main info --}}
                <div class="flex-grow-1" style="min-width:0;">
                    {{-- Row 1: nomor invoice + nominal --}}
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center flex-wrap" style="gap:4px;">
                            <span style="font-size:0.8rem;font-weight:600;color:#1565c0;">{{ $invoice->invoice_number }}</span>
                            <span class="badge badge-{{ $invoice->status_color }}" style="font-size:0.6rem;">{{ $invoice->status_label }}</span>
                            @if($invoice->status === 'overdue')
                            <span class="badge badge-danger" style="font-size:0.58rem;">{{ $invoice->due_date->diffInDays(now()) }}h terlambat</span>
                            @endif
                        </div>
                        <div class="ml-2 text-right flex-shrink-0">
                            <span style="font-size:0.9rem;font-weight:700;color:#1a1a1a;">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    {{-- Row 2: periode + aksi --}}
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <div style="font-size:0.71rem;color:#888;">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            {{ $invoice->period_start?->format('d M') ?? '-' }}–{{ $invoice->period_end?->format('d M Y') ?? '-' }}
                            @if($invoice->due_date)
                            &nbsp;·&nbsp;<span class="{{ ($invoice->due_date->isPast() && !in_array($invoice->status,['paid','cancelled'])) ? 'text-danger' : '' }}">Tempo {{ $invoice->due_date->format('d M Y') }}</span>
                            @endif
                        </div>
                        <div class="ml-2 flex-shrink-0">
                            @if($invoice->paid_amount > 0 && $invoice->status !== 'paid')
                            <span style="font-size:0.65rem;color:#28a745;">+Rp {{ number_format($invoice->paid_amount,0,',','.') }}</span>
                            @elseif(in_array($invoice->status, ['unpaid','overdue','pending']))
                            <span class="btn btn-xs btn-primary" style="padding:1px 7px;font-size:0.67rem;"><i class="fas fa-credit-card mr-1"></i>Bayar</span>
                            @else
                            <i class="fas fa-chevron-right" style="font-size:0.68rem;color:#ccc;"></i>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </a>
        @empty
        <div class="text-center py-5 text-muted">
            <div style="width:56px;height:56px;border-radius:50%;background:#f0f4ff;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="fas fa-file-invoice fa-lg" style="color:#90aad4;"></i>
            </div>
            <div style="font-size:0.85rem;font-weight:500;color:#666;">Belum ada tagihan</div>
            <small class="text-muted">Tagihan akan muncul di sini saat periode penagihan dimulai.</small>
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
