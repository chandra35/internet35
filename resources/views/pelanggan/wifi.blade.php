@extends('layouts.pelanggan')

@section('title', 'WiFi Saya')

@section('page-title', 'WiFi Saya')

@section('content')
@php
    $acs = $status['acs'] ?? [];
    $wifis = collect($status['wifi'] ?? [])->filter(fn($wifi) => empty($wifi['system']))->values();
    $mainWifi = $wifis->first(fn($wifi) => str_contains($wifi['path'] ?? '', 'WLANConfiguration.1')) ?? $wifis->first();
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-broadcast-tower mr-1"></i> Pengaturan SSID Utama</span>
            </div>
            <div class="card-body">
                @if(!($acs['found'] ?? false))
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Device ACS belum terhubung ke akun Anda. Hubungi admin untuk aktivasi fitur WiFi mandiri.
                </div>
                @elseif(!$mainWifi)
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    Data SSID belum terbaca dari ACS. Coba lagi beberapa saat setelah perangkat online.
                </div>
                @else
                <form id="wifiForm">
                    @csrf
                    <input type="hidden" name="wlan_path" value="{{ $mainWifi['path'] }}">
                    <div class="form-group">
                        <label>Nama WiFi / SSID</label>
                        <input type="text" name="ssid" class="form-control" maxlength="32" value="{{ $mainWifi['ssid'] }}" required>
                    </div>
                    <div class="form-group">
                        <label>Password WiFi Baru</label>
                        <input type="password" name="password" class="form-control" minlength="8" maxlength="63" required>
                        <small class="text-muted">Minimal 8 karakter. Perangkat yang sedang terhubung perlu memasukkan password baru.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </form>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-wifi mr-1"></i> SSID Terbaca</span>
            </div>
            <div class="card-body p-0">
                @forelse($wifis as $wifi)
                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                    <div>
                        <div style="font-weight:600;">{{ $wifi['ssid'] ?? '-' }}</div>
                        <small class="text-muted">{{ $wifi['band'] ?? '-' }} · {{ !empty($wifi['enabled']) ? 'Aktif' : 'Nonaktif' }}</small>
                    </div>
                    @if(($wifi['path'] ?? null) === ($mainWifi['path'] ?? null))
                    <span class="badge badge-primary">Utama</span>
                    @endif
                </div>
                @empty
                <div class="text-muted px-3 py-3">Belum ada SSID yang terbaca.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-satellite-dish mr-1"></i> Status ACS</span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    @if(($acs['online'] ?? false))
                    <span class="badge badge-success mr-2">Online</span>
                    @elseif(($acs['found'] ?? false))
                    <span class="badge badge-warning mr-2">Tidak realtime</span>
                    @else
                    <span class="badge badge-secondary mr-2">Belum terhubung</span>
                    @endif
                    <span style="font-size:0.85rem;">{{ $acs['model'] ?? $acs['serial_number'] ?? '-' }}</span>
                </div>
                <small class="text-muted">
                    Last inform: {{ $acs['last_inform_human'] ?? '-' }}<br>
                    Device ID: {{ $acs['device_id'] ?? '-' }}
                </small>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$('#wifiForm').on('submit', function(e) {
    e.preventDefault();
    const btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');
    $.post('{{ route("pelanggan.wifi.update") }}', $(this).serialize(), function(res) {
        if (res.success) {
            toastr.success(res.completed ? 'WiFi berhasil diubah' : 'Perintah perubahan WiFi dikirim ke ACS');
            setTimeout(() => location.reload(), 1200);
        } else {
            toastr.error(res.message || 'Gagal mengubah WiFi');
        }
    }).fail(function(xhr) {
        toastr.error(xhr.responseJSON?.message || 'Gagal mengubah WiFi');
    }).always(function() {
        btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Perubahan');
    });
});
</script>
@endpush
