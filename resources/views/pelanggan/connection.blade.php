@extends('layouts.pelanggan')

@section('title', 'Koneksi Saya')

@section('page-title', 'Koneksi Saya')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <!-- Connection Status -->
        <div class="card">
            <div class="card-header bg-{{ $customer->status === 'active' ? 'success' : ($customer->status === 'suspended' ? 'warning' : ($customer->status === 'pending' ? 'info' : 'danger')) }}">
                <h3 class="card-title text-white">
                    <i class="fas fa-{{ $customer->status === 'active' ? 'check-circle' : 'exclamation-circle' }} mr-2"></i>
                    Status Koneksi: {{ $customer->status_label }}
                </h3>
            </div>
            <div class="card-body">
                @if($customer->status === 'suspended')
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Koneksi Disuspend</strong><br>
                    {{ $customer->suspend_reason ?? 'Silakan hubungi admin untuk informasi lebih lanjut.' }}
                </div>
                @elseif($customer->status === 'terminated')
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle mr-2"></i>
                    <strong>Koneksi Diterminasi</strong><br>
                    {{ $customer->terminate_reason ?? 'Silakan hubungi admin untuk informasi lebih lanjut.' }}
                </div>
                @elseif($customer->status === 'pending')
                <div class="alert alert-info">
                    <i class="fas fa-hourglass-half mr-2"></i>
                    <strong>Menunggu Aktivasi</strong><br>
                    Koneksi Anda sedang dalam proses aktivasi. Silakan hubungi admin jika memerlukan informasi lebih lanjut.
                </div>
                @elseif($customer->active_until && $customer->active_until->isPast())
                <div class="alert alert-warning">
                    <i class="fas fa-clock mr-2"></i>
                    <strong>Masa Aktif Telah Berakhir</strong><br>
                    Silakan lakukan pembayaran untuk melanjutkan layanan.
                    <a href="{{ route('pelanggan.invoices') }}" class="btn btn-sm btn-warning ml-2">Bayar Sekarang</a>
                </div>
                @else
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i>
                    Koneksi internet Anda aktif dan berjalan normal.
                </div>
                @endif
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h5><i class="fas fa-user mr-2"></i>Kredensial PPPoE</h5>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="40%">Username</td>
                                <td><code id="pppUsername">{{ $customer->pppoe_username }}</code>
                                    <button class="btn btn-xs btn-outline-primary ml-1" onclick="copyText('pppUsername')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Password</td>
                                <td>
                                    <code id="pppPassword">••••••••</code>
                                    <button class="btn btn-xs btn-outline-primary ml-1" id="btnShowPwd">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-network-wired mr-2"></i>Informasi Jaringan</h5>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="40%">IP Address</td>
                                <td>{{ $customer->remote_address ?? 'Dynamic' }}</td>
                            </tr>
                            <tr>
                                <td>Tipe Layanan</td>
                                <td>{{ strtoupper($customer->service_type) }}</td>
                            </tr>
                            @if($customer->mac_address)
                            <tr>
                                <td>MAC Address</td>
                                <td><code>{{ $customer->mac_address }}</code></td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Package Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-box mr-2"></i>Paket Layanan</h3>
            </div>
            <div class="card-body">
                @if($customer->package)
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-1">{{ $customer->package->name }}</h4>
                        <p class="text-muted mb-2">{{ $customer->package->description ?? 'Paket internet berkecepatan tinggi' }}</p>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-info mr-3">
                                <i class="fas fa-tachometer-alt mr-1"></i>
                                {{ $customer->package->speed_name ?? $customer->package->name }}
                            </span>
                            @if($customer->package->is_unlimited)
                            <span class="badge badge-success">
                                <i class="fas fa-infinity mr-1"></i> Unlimited
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <small class="text-muted">Biaya Bulanan</small>
                        <h3 class="text-primary mb-0">
                            Rp {{ number_format($customer->monthly_fee, 0, ',', '.') }}
                        </h3>
                    </div>
                </div>
                @else
                <p class="text-muted mb-0">Informasi paket tidak tersedia</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Aksi Cepat</h3>
            </div>
            <div class="card-body">
                <a href="{{ route('pelanggan.invoices') }}" class="btn btn-primary btn-block mb-2">
                    <i class="fas fa-file-invoice-dollar mr-2"></i> Bayar Tagihan
                </a>
                <a href="{{ route('pelanggan.payments') }}" class="btn btn-outline-secondary btn-block mb-2">
                    <i class="fas fa-history mr-2"></i> Riwayat Pembayaran
                </a>
                <hr>
                <a href="https://wa.me/{{ config('app.support_whatsapp', '628123456789') }}" target="_blank" class="btn btn-success btn-block">
                    <i class="fab fa-whatsapp mr-2"></i> Hubungi Support
                </a>
            </div>
        </div>

        <!-- Router Info -->
        @if($customer->router)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>Server / Router</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td>Nama</td>
                        <td><strong>{{ $customer->router->name }}</strong></td>
                    </tr>
                    <tr>
                        <td>Lokasi</td>
                        <td>{{ $customer->router->location ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        @endif

        <!-- Billing Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>Info Langganan</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td>Tanggal Pasang</td>
                        <td>{{ $customer->installation_date?->format('d M Y') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Biaya Bulanan</td>
                        <td><strong>Rp {{ number_format($customer->monthly_fee, 0, ',', '.') }}</strong></td>
                    </tr>
                    <tr>
                        <td>Jatuh Tempo</td>
                        <td>Tanggal {{ $customer->billing_day ?? '-' }} setiap bulan</td>
                    </tr>
                    <tr>
                        <td>Aktif Sampai</td>
                        <td>
                            @if($customer->active_until)
                            <span class="{{ $customer->active_until->isPast() ? 'text-danger font-weight-bold' : 'text-success' }}">
                                {{ $customer->active_until->format('d M Y') }}
                            </span>
                            @if($customer->active_until->isFuture())
                            <br><small class="text-muted">{{ (int) now()->diffInDays($customer->active_until, false) }} hari lagi</small>
                            @elseif($customer->active_until->isPast())
                            <br><small class="text-danger">Lewat {{ (int) $customer->active_until->diffInDays(now()) }} hari</small>
                            @endif
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Billing Period -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Periode Berjalan</h3>
            </div>
            <div class="card-body">
                <h5 class="text-center mb-2">{{ $billingPeriod['month'] }}</h5>
                <p class="text-center text-muted small mb-2">{{ $billingPeriod['start'] }} - {{ $billingPeriod['end'] }}</p>
                <div class="d-flex justify-content-between small mb-1">
                    <span>Hari ke-{{ $billingPeriod['day_of_period'] }}</span>
                    <span>{{ $billingPeriod['total_days'] }} hari</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-primary" style="width: {{ round(($billingPeriod['day_of_period'] / $billingPeriod['total_days']) * 100) }}%"></div>
                </div>
            </div>
        </div>

        <!-- Payment Status -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-receipt mr-2"></i>Status Pembayaran</h3>
            </div>
            <div class="card-body">
                @if($pendingInvoiceCount > 0)
                <div class="alert alert-{{ $latestInvoice && $latestInvoice->status === 'overdue' ? 'danger' : 'warning' }} py-2 mb-3">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <strong>{{ $pendingInvoiceCount }} tagihan</strong> belum lunas
                </div>
                @else
                <div class="alert alert-success py-2 mb-3">
                    <i class="fas fa-check-circle mr-1"></i>
                    Semua tagihan lunas
                </div>
                @endif

                @if($latestInvoice)
                <table class="table table-sm table-borderless mb-2">
                    <tr>
                        <td class="text-muted">Invoice Terakhir</td>
                        <td>
                            <code>{{ $latestInvoice->invoice_number }}</code>
                            <span class="badge badge-{{ $latestInvoice->status_color }} ml-1">{{ $latestInvoice->status_label }}</span>
                        </td>
                    </tr>
                    @if($latestPayment)
                    <tr>
                        <td class="text-muted">Bayar Terakhir</td>
                        <td>
                            {{ $latestPayment->paid_at?->format('d M Y') ?? $latestPayment->created_at->format('d M Y') }}
                            <br><small class="text-muted">Rp {{ number_format($latestPayment->amount, 0, ',', '.') }}</small>
                        </td>
                    </tr>
                    @endif
                </table>
                @endif

                @if($pendingInvoiceCount > 0)
                <a href="{{ route('pelanggan.invoices') }}" class="btn btn-primary btn-sm btn-block">
                    <i class="fas fa-credit-card mr-1"></i> Bayar Tagihan
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
let passwordVisible = false;

$('#btnShowPwd').on('click', function() {
    if (passwordVisible) {
        $('#pppPassword').text('••••••••');
        passwordVisible = false;
    } else {
        $.get('{{ route("pelanggan.credentials") }}', function(data) {
            $('#pppPassword').text(data.password);
            passwordVisible = true;
        });
    }
});

function copyText(elementId) {
    const text = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(text);
    toastr.success('Tersalin ke clipboard!');
}
</script>
@endpush
