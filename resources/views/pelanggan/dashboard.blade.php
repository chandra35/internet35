@extends('layouts.pelanggan')

@section('title', 'Dashboard')

@section('page-title', 'Selamat Datang, ' . $customer->name)

@section('content')
<!-- Status Cards -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-{{ $connectionStatus['color'] }}">
            <div class="inner">
                <h3>{{ $connectionStatus['status'] }}</h3>
                <p>Status Koneksi</p>
            </div>
            <div class="icon">
                <i class="fas {{ $connectionStatus['online'] ? 'fa-wifi' : 'fa-wifi-slash' }}"></i>
            </div>
            <a href="{{ route('pelanggan.connection') }}" class="small-box-footer">
                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $customer->package?->name ?? '-' }}</h3>
                <p>Paket Saya</p>
            </div>
            <div class="icon">
                <i class="fas fa-tachometer-alt"></i>
            </div>
            <a href="{{ route('pelanggan.connection') }}" class="small-box-footer">
                Detail Paket <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box {{ $daysUntilDue !== null && $daysUntilDue <= 7 ? ($daysUntilDue <= 0 ? 'bg-danger' : 'bg-warning') : 'bg-primary' }}">
            <div class="inner">
                @if($customer->active_until)
                <h3>{{ $customer->active_until->format('d M') }}</h3>
                <p>Aktif Sampai {{ $daysUntilDue !== null ? ($daysUntilDue > 0 ? "($daysUntilDue hari lagi)" : '(Jatuh tempo)') : '' }}</p>
                @else
                <h3>-</h3>
                <p>Aktif Sampai</p>
                @endif
            </div>
            <div class="icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <a href="{{ route('pelanggan.invoices') }}" class="small-box-footer">
                Lihat Tagihan <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>Rp {{ number_format($customer->monthly_fee, 0, ',', '.') }}</h3>
                <p>Biaya Bulanan</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill"></i>
            </div>
            <a href="{{ route('pelanggan.invoices') }}" class="small-box-footer">
                Bayar Sekarang <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Pending Invoices -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>
                    Tagihan Menunggu Pembayaran
                </h3>
            </div>
            <div class="card-body p-0">
                @if($pendingInvoices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Periode</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingInvoices as $invoice)
                            <tr>
                                <td><code>{{ $invoice->invoice_number }}</code></td>
                                <td>{{ $invoice->period_start?->format('M Y') ?? '-' }}</td>
                                <td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge badge-{{ $invoice->status_color }}">
                                        {{ $invoice->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('pelanggan.invoice', $invoice) }}" class="btn btn-sm btn-primary">
                                        Bayar
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <p class="mb-0">Tidak ada tagihan yang menunggu pembayaran</p>
                </div>
                @endif
            </div>
            @if($pendingInvoices->count() > 0)
            <div class="card-footer">
                <a href="{{ route('pelanggan.invoices') }}">Lihat Semua Tagihan &rarr;</a>
            </div>
            @endif
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-2"></i>
                    Pembayaran Terakhir
                </h3>
            </div>
            <div class="card-body p-0">
                @if($recentPayments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jumlah</th>
                                <th>Metode</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentPayments as $payment)
                            <tr>
                                <td>{{ $payment->created_at->format('d M Y') }}</td>
                                <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                <td>{{ ucfirst($payment->payment_method) }}</td>
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
                @else
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-clock fa-3x mb-3"></i>
                    <p class="mb-0">Belum ada riwayat pembayaran</p>
                </div>
                @endif
            </div>
            @if($recentPayments->count() > 0)
            <div class="card-footer">
                <a href="{{ route('pelanggan.payments') }}">Lihat Semua Riwayat &rarr;</a>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Connection Info Card -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-2"></i>
                    Informasi Langganan
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <dl>
                            <dt>ID Pelanggan</dt>
                            <dd><code>{{ $customer->customer_id }}</code></dd>
                            
                            <dt>Router</dt>
                            <dd>{{ $customer->router?->name ?? '-' }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-4">
                        <dl>
                            <dt>Username PPPoE</dt>
                            <dd>
                                <code>{{ $customer->pppoe_username }}</code>
                                <button type="button" class="btn btn-xs btn-outline-primary ml-1" id="btnShowCredentials">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </dd>
                            
                            <dt>Tanggal Jatuh Tempo</dt>
                            <dd>Setiap tanggal {{ $customer->billing_day }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-4">
                        <dl>
                            <dt>Alamat</dt>
                            <dd>
                                {{ $customer->address }}<br>
                                <small class="text-muted">
                                    {{ $customer->village?->name }}, {{ $customer->district?->name }}<br>
                                    {{ $customer->city?->name }}, {{ $customer->province?->name }}
                                </small>
                            </dd>
                        </dl>
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
    $('#btnShowCredentials').on('click', function() {
        $.get('{{ route("pelanggan.credentials") }}', function(data) {
            Swal.fire({
                title: 'Kredensial PPPoE',
                html: `
                    <div class="text-left">
                        <div class="form-group">
                            <label>Username</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="${data.username}" readonly id="pppUsername">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-primary" onclick="copyToClipboard('pppUsername')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="${data.password}" readonly id="pppPassword">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-primary" onclick="copyToClipboard('pppPassword')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCloseButton: true,
            });
        });
    });
});

function copyToClipboard(elementId) {
    const el = document.getElementById(elementId);
    el.select();
    document.execCommand('copy');
    toastr.success('Tersalin ke clipboard!');
}
</script>
@endpush
