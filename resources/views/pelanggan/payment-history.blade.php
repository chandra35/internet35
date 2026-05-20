@extends('layouts.pelanggan')

@section('title', 'Riwayat Pembayaran')

@section('page-title', 'Riwayat Pembayaran')

@section('content')
@php
    $successCount  = $payments->getCollection()->where('status','success')->count();
    $successAmount = $payments->getCollection()->where('status','success')->sum('amount');
    $pendingCount  = $payments->getCollection()->whereIn('status',['pending','processing'])->count();
    $failedCount   = $payments->getCollection()->whereIn('status',['failed','expired','cancelled'])->count();

    $methodIcons = [
        'bank_transfer'  => ['icon'=>'fas fa-university',      'color'=>'#1565c0'],
        'virtual_account'=> ['icon'=>'fas fa-credit-card',     'color'=>'#7b1fa2'],
        'qris'           => ['icon'=>'fas fa-qrcode',          'color'=>'#00838f'],
        'ewallet'        => ['icon'=>'fas fa-wallet',          'color'=>'#e65100'],
        'cash'           => ['icon'=>'fas fa-money-bill-wave',  'color'=>'#2e7d32'],
        'default'        => ['icon'=>'fas fa-receipt',          'color'=>'#546e7a'],
    ];
@endphp

{{-- Summary strip --}}
@if($payments->total() > 0)
<div class="row mb-2" style="margin-left:-5px;margin-right:-5px;">
    <div class="col-6 col-sm-4" style="padding:0 5px;">
        <div class="stat-card stat-blue mb-2">
            <i class="fas fa-history stat-icon"></i>
            <div class="stat-value">{{ $payments->total() }}</div>
            <div class="stat-label">Total Transaksi</div>
        </div>
    </div>
    <div class="col-6 col-sm-4" style="padding:0 5px;">
        <div class="stat-card stat-green mb-2">
            <i class="fas fa-check-circle stat-icon"></i>
            <div class="stat-value" style="font-size:0.82rem;">Rp {{ number_format($successAmount,0,',','.') }}</div>
            <div class="stat-label">Total Dibayar</div>
        </div>
    </div>
    <div class="col-12 col-sm-4" style="padding:0 5px;">
        <div class="stat-card {{ $pendingCount > 0 ? 'stat-yellow' : 'stat-teal' }} mb-2">
            <i class="fas fa-hourglass-half stat-icon"></i>
            <div class="stat-value">{{ $pendingCount }}</div>
            <div class="stat-label">Menunggu Konfirmasi</div>
        </div>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="card-title"><i class="fas fa-history mr-1"></i> Riwayat Pembayaran</span>
        @if($payments->total() > 0)
        <small class="text-muted">{{ $payments->total() }} transaksi</small>
        @endif
    </div>
    <div class="card-body p-0">
        @forelse($payments as $payment)
        @php
            $method = $payment->payment_method ?? 'default';
            $mi = $methodIcons[$method] ?? $methodIcons['default'];
            $borderColor = match($payment->status) {
                'success'             => '#28a745',
                'pending','processing'=> '#ffc107',
                'failed','expired','cancelled' => '#dc3545',
                default               => '#adb5bd',
            };
        @endphp
        <div class="invoice-row" style="border-left:3px solid {{ $borderColor }};border-bottom:1px solid #f0f0f0;">
            <div class="d-flex align-items-center px-3 py-3">
                {{-- Method icon --}}
                <div class="mr-3 flex-shrink-0" style="width:36px;height:36px;border-radius:9px;background:{{ $mi['color'] }}18;display:flex;align-items:center;justify-content:center;">
                    <i class="{{ $mi['icon'] }} fa-sm" style="color:{{ $mi['color'] }};"></i>
                </div>
                {{-- Info --}}
                <div class="flex-grow-1" style="min-width:0;">
                    {{-- Row 1: nama gateway + nominal --}}
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center flex-wrap" style="gap:4px;">
                            <span style="font-size:0.78rem;font-weight:600;color:#333;">
                                {{ $payment->paymentGateway?->name ?? ucfirst(str_replace('_',' ',$payment->payment_method ?? 'Pembayaran')) }}
                            </span>
                            <span class="badge badge-{{ $payment->status_color }}" style="font-size:0.58rem;">{{ $payment->status_label }}</span>
                        </div>
                        <div class="ml-2 flex-shrink-0">
                            <span style="font-size:0.9rem;font-weight:700;color:{{ $payment->status === 'success' ? '#28a745' : '#1a1a1a' }};">
                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    {{-- Row 2: tanggal + invoice + status konfirmasi --}}
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <div style="font-size:0.7rem;color:#888;">
                            <i class="fas fa-calendar mr-1"></i>{{ $payment->created_at->format('d M Y, H:i') }}
                            @if($payment->invoice)
                            &nbsp;·&nbsp;<a href="{{ route('pelanggan.invoice', $payment->invoice) }}" class="text-muted" style="font-size:0.68rem;"><i class="fas fa-file-invoice mr-1"></i>{{ $payment->invoice->invoice_number }}</a>
                            @endif
                        </div>
                        @if($payment->status === 'success' && $payment->paid_at)
                        <div style="font-size:0.65rem;color:#28a745;margin-left:8px;flex-shrink:0;">
                            <i class="fas fa-check-circle mr-1"></i>{{ $payment->paid_at->format('d M Y') }}
                        </div>
                        @elseif(in_array($payment->status, ['pending','processing']))
                        <div style="font-size:0.65rem;color:#e67e22;margin-left:8px;flex-shrink:0;">
                            <i class="fas fa-hourglass-half mr-1"></i>Menunggu
                        </div>
                        @endif
                    </div>
                    @if($payment->external_id)
                    <div style="font-size:0.63rem;color:#ccc;margin-top:2px;">Ref: {{ $payment->external_id }}</div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <div style="width:56px;height:56px;border-radius:50%;background:#f0f4ff;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="fas fa-history fa-lg" style="color:#90aad4;"></i>
            </div>
            <div style="font-size:0.85rem;font-weight:500;color:#666;">Belum ada riwayat pembayaran</div>
            <small class="text-muted">Riwayat pembayaran Anda akan ditampilkan di sini.</small>
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
