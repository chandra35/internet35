@extends('layouts.pelanggan')

@section('title', 'Koneksi Saya')

@section('page-title', 'Koneksi Saya')

@section('content')

{{-- Status Banner --}}
@if($customer->status === 'active' && (!$customer->active_until || $customer->active_until->isFuture()))
<div class="d-flex align-items-center px-3 py-2 mb-3 rounded" style="background:#e8f5e9;border-left:4px solid #28a745;">
    <i class="fas fa-check-circle text-success mr-2"></i>
    <span style="font-size:0.85rem;color:#1b5e20;"><strong>Koneksi Aktif</strong> â€” internet Anda berjalan normal.</span>
</div>
@elseif($customer->status === 'suspended')
<div class="d-flex align-items-center px-3 py-2 mb-3 rounded" style="background:#fff3e0;border-left:4px solid #f39c12;">
    <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
    <div style="font-size:0.85rem;"><strong>Koneksi Disuspend</strong><br><small class="text-muted">{{ $customer->suspend_reason ?? 'Hubungi admin untuk informasi lebih lanjut.' }}</small></div>
</div>
@elseif($customer->status === 'terminated')
<div class="d-flex align-items-center px-3 py-2 mb-3 rounded" style="background:#fce4ec;border-left:4px solid #dc3545;">
    <i class="fas fa-times-circle text-danger mr-2"></i>
    <div style="font-size:0.85rem;"><strong>Koneksi Diterminasi</strong><br><small class="text-muted">{{ $customer->terminate_reason ?? 'Hubungi admin untuk informasi lebih lanjut.' }}</small></div>
</div>
@elseif($customer->status === 'pending')
<div class="d-flex align-items-center px-3 py-2 mb-3 rounded" style="background:#e3f2fd;border-left:4px solid #1565c0;">
    <i class="fas fa-hourglass-half text-primary mr-2"></i>
    <div style="font-size:0.85rem;"><strong>Menunggu Aktivasi</strong><br><small class="text-muted">Koneksi sedang diproses. Hubungi admin jika ada pertanyaan.</small></div>
</div>
@elseif($customer->active_until && $customer->active_until->isPast())
<div class="d-flex align-items-center px-3 py-2 mb-3 rounded" style="background:#fff3e0;border-left:4px solid #f39c12;">
    <i class="fas fa-clock text-warning mr-2"></i>
    <div class="flex-grow-1" style="font-size:0.85rem;"><strong>Masa Aktif Berakhir</strong> â€” lakukan pembayaran untuk melanjutkan layanan.</div>
    <a href="{{ route('pelanggan.invoices') }}" class="btn btn-sm btn-warning ml-2 flex-shrink-0">Bayar</a>
</div>
@endif

<div class="row">
    {{-- LEFT: PPPoE + Package --}}
    <div class="col-lg-8">

        {{-- Live Connection Status --}}
        <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-signal mr-1"></i> Status Live</span></div>
            <div class="card-body py-3">
                <div class="row" style="row-gap:10px;">
                    <div class="col-sm-4">
                        <div class="text-muted" style="font-size:0.7rem;">PPPoE</div>
                        @if(($connectionStatus['ppp']['online'] ?? false))
                        <span class="badge badge-success">Online</span>
                        <small class="d-block text-muted mt-1">{{ $connectionStatus['ppp']['address'] ?? '-' }} · {{ $connectionStatus['ppp']['uptime'] ?? '-' }}</small>
                        @else
                        <span class="badge badge-secondary">Offline</span>
                        <small class="d-block text-muted mt-1">{{ $connectionStatus['ppp']['message'] ?? '-' }}</small>
                        @endif
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted" style="font-size:0.7rem;">ACS</div>
                        @if(($connectionStatus['acs']['online'] ?? false))
                        <span class="badge badge-success">Online</span>
                        @elseif(($connectionStatus['acs']['found'] ?? false))
                        <span class="badge badge-warning">Tidak realtime</span>
                        @else
                        <span class="badge badge-secondary">Belum terhubung</span>
                        @endif
                        <small class="d-block text-muted mt-1">{{ $connectionStatus['acs']['last_inform_human'] ?? '-' }}</small>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted" style="font-size:0.7rem;">ONU</div>
                        @if($connectionStatus['onu'] ?? null)
                        <span class="badge badge-{{ ($connectionStatus['onu']['status'] ?? '') === 'online' ? 'success' : 'secondary' }}">{{ $connectionStatus['onu']['status_label'] ?? '-' }}</span>
                        <small class="d-block text-muted mt-1">RX {{ $connectionStatus['onu']['rx_power'] ?? '-' }} dBm</small>
                        @else
                        <span class="badge badge-secondary">Belum assign</span>
                        <small class="d-block text-muted mt-1">Menunggu migrasi ONU</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- PPPoE Credentials --}}
        <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-user-circle mr-1"></i> Kredensial PPPoE</span></div>
            <div class="card-body py-3">
                <div class="row" style="row-gap:10px;">
                    <div class="col-sm-6">
                        <div class="text-muted" style="font-size:0.7rem;">Username</div>
                        <div class="d-flex align-items-center mt-1">
                            <code id="pppUsername" style="font-size:0.82rem;">{{ $customer->pppoe_username }}</code>
                            <button class="btn btn-xs btn-link text-primary p-0 ml-2" onclick="copyText('pppUsername')" title="Salin"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted" style="font-size:0.7rem;">Password</div>
                        <div class="d-flex align-items-center mt-1">
                            <code id="pppPassword" style="font-size:0.82rem;">â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢</code>
                            <button class="btn btn-xs btn-link text-primary p-0 ml-2" id="btnShowPwd" title="Tampilkan"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted" style="font-size:0.7rem;">IP Address</div>
                        <div style="font-size:0.82rem;margin-top:2px;">{{ $customer->remote_address ?? 'Dynamic' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted" style="font-size:0.7rem;">Tipe Layanan</div>
                        <div style="font-size:0.82rem;margin-top:2px;">{{ strtoupper($customer->service_type ?? 'PPPoE') }}</div>
                    </div>
                    @if($customer->mac_address)
                    <div class="col-sm-6">
                        <div class="text-muted" style="font-size:0.7rem;">MAC Address</div>
                        <code style="font-size:0.8rem;">{{ $customer->mac_address }}</code>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Package --}}
        <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-box mr-1"></i> Paket Layanan</span></div>
            <div class="card-body py-3">
                @if($customer->package)
                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                    <div>
                        <div style="font-size:1rem;font-weight:600;">{{ $customer->package->name }}</div>
                        <div class="text-muted" style="font-size:0.78rem;">{{ $customer->package->description ?? 'Paket internet berkecepatan tinggi' }}</div>
                        <div class="mt-2" style="display:flex;gap:6px;flex-wrap:wrap;">
                            <span class="badge badge-info" style="font-size:0.7rem;"><i class="fas fa-tachometer-alt mr-1"></i>{{ $customer->package->speed_name ?? $customer->package->name }}</span>
                            @if($customer->package->is_unlimited)
                            <span class="badge badge-success" style="font-size:0.7rem;"><i class="fas fa-infinity mr-1"></i>Unlimited</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-muted" style="font-size:0.7rem;">Biaya Bulanan</div>
                        <div style="font-size:1.1rem;font-weight:700;color:#1565c0;">Rp {{ number_format($customer->monthly_fee, 0, ',', '.') }}</div>
                    </div>
                </div>
                @else
                <p class="text-muted mb-0" style="font-size:0.82rem;">Informasi paket tidak tersedia</p>
                @endif
            </div>
        </div>

    </div>

    {{-- RIGHT: Actions + Subscription info --}}
    <div class="col-lg-4">

        {{-- Quick Actions --}}
        <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-bolt mr-1"></i> Aksi Cepat</span></div>
            <div class="card-body py-3">
                <a href="{{ route('pelanggan.invoices') }}" class="btn btn-primary btn-block btn-sm mb-2">
                    <i class="fas fa-file-invoice-dollar mr-1"></i> Bayar Tagihan
                </a>
                <a href="{{ route('pelanggan.payments') }}" class="btn btn-outline-secondary btn-block btn-sm mb-2">
                    <i class="fas fa-history mr-1"></i> Riwayat Pembayaran
                </a>
                <a href="https://wa.me/{{ config('app.support_whatsapp', '628123456789') }}" target="_blank" class="btn btn-success btn-block btn-sm">
                    <i class="fab fa-whatsapp mr-1"></i> Hubungi Support
                </a>
            </div>
        </div>

        {{-- Subscription Info --}}
        <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Info Langganan</span></div>
            <div class="card-body p-0">
                @php
                    $rows = [
                        ['Tanggal Pasang', $customer->installation_date?->format('d M Y') ?? '-'],
                        ['Jatuh Tempo', 'Tgl ' . ($customer->billing_day ?? '-') . ' / bulan'],
                        ['Router', $customer->router?->name ?? '-'],
                    ];
                @endphp
                @foreach($rows as [$label, $val])
                <div class="d-flex align-items-center px-3 py-2 border-bottom">
                    <span class="text-muted flex-shrink-0" style="font-size:0.75rem;width:110px;">{{ $label }}</span>
                    <span style="font-size:0.82rem;">{{ $val }}</span>
                </div>
                @endforeach
                <div class="d-flex align-items-center px-3 py-2">
                    <span class="text-muted flex-shrink-0" style="font-size:0.75rem;width:110px;">Aktif Sampai</span>
                    @if($customer->active_until)
                    <span class="{{ $customer->active_until->isPast() ? 'text-danger font-weight-bold' : 'text-success' }}" style="font-size:0.82rem;">
                        {{ $customer->active_until->format('d M Y') }}
                        @if($customer->active_until->isFuture())
                        <span class="text-muted">({{ (int) now()->diffInDays($customer->active_until) }}h)</span>
                        @elseif($customer->active_until->isPast())
                        <span class="text-danger">(lewat)</span>
                        @endif
                    </span>
                    @else
                    <span style="font-size:0.82rem;">-</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Period --}}
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:0.78rem;font-weight:600;color:#333;">{{ $billingPeriod['month'] }}</span>
                    <small class="text-muted" style="font-size:0.7rem;">Hari {{ $billingPeriod['day_of_period'] }}/{{ $billingPeriod['total_days'] }}</small>
                </div>
                <div class="progress" style="height:5px;border-radius:3px;">
                    <div class="progress-bar bg-primary" style="width:{{ round(($billingPeriod['day_of_period'] / max($billingPeriod['total_days'],1)) * 100) }}%;border-radius:3px;"></div>
                </div>
                <div class="text-muted mt-1" style="font-size:0.7rem;">{{ $billingPeriod['start'] }} â€“ {{ $billingPeriod['end'] }}</div>
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
        $('#pppPassword').text('â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢');
        $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
        passwordVisible = false;
    } else {
        $.get('{{ route("pelanggan.credentials") }}', function(data) {
            $('#pppPassword').text(data.password);
            $('#btnShowPwd i').removeClass('fa-eye').addClass('fa-eye-slash');
            passwordVisible = true;
        });
    }
});
function copyText(elementId) {
    const text = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(text).then(() => toastr.success('Tersalin!'));
}
</script>
@endpush
