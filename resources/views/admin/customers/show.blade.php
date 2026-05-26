@extends('layouts.admin')

@section('title', 'Detail Pelanggan')

@section('page-title', $customer->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Pelanggan</a></li>
    <li class="breadcrumb-item active">{{ $customer->customer_id }}</li>
@endsection

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        padding: 30px;
        color: white;
        margin-bottom: 20px;
    }
    .profile-photo {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255,255,255,0.3);
    }
    .info-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    .info-item:last-child {
        border-bottom: none;
    }
    .info-label {
        color: #6c757d;
        font-size: 0.85rem;
    }
    .info-value {
        font-weight: 500;
    }
    .photo-preview {
        width: 100%;
        border-radius: 8px;
        cursor: pointer;
        transition: transform 0.3s ease;
    }
    .photo-preview:hover {
        transform: scale(1.02);
    }
    #map {
        height: 250px;
        border-radius: 8px;
    }
    .timeline-item {
        position: relative;
        padding-left: 30px;
        padding-bottom: 20px;
        border-left: 2px solid #dee2e6;
    }
    .timeline-item:last-child {
        border-left-color: transparent;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 0;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #007bff;
    }
    .connectivity-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }
    .connectivity-tile {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px;
        min-height: 92px;
        background: #fff;
    }
    .connectivity-label {
        color: #6c757d;
        font-size: 0.72rem;
        text-transform: uppercase;
        font-weight: 700;
    }
    .connectivity-value {
        font-size: 1rem;
        font-weight: 700;
        margin-top: 4px;
    }
    .wifi-row {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 10px;
    }
    @media (max-width: 991.98px) {
        .connectivity-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>
@endpush

@section('content')
<!-- Profile Header -->
<div class="profile-header">
    <div class="row align-items-center">
        <div class="col-auto">
            @if($customer->photo_selfie_url)
            <img src="{{ $customer->photo_selfie_url }}" class="profile-photo">
            @else
            <div class="profile-photo bg-white d-flex align-items-center justify-content-center">
                <i class="fas fa-user fa-3x text-secondary"></i>
            </div>
            @endif
        </div>
        <div class="col">
            <h2 class="mb-1">{{ $customer->name }}</h2>
            @if($customer->nickname)
            <p class="mb-1"><span style="font-size:0.82rem;color:rgba(255,255,255,0.65);"><i class="fas fa-tag mr-1"></i>{{ $customer->nickname }}</span></p>
            @endif
            <p class="mb-2">
                <code class="bg-light text-dark px-2 py-1 rounded">{{ $customer->customer_id }}</code>
                <span class="badge badge-{{ $customer->status_color }} ml-2">{{ $customer->status_label }}</span>
            </p>
            <p class="mb-0">
                <i class="fas fa-phone mr-2"></i>{{ $customer->phone }}
                @if($customer->email)
                <span class="mx-2">|</span>
                <i class="fas fa-envelope mr-2"></i>{{ $customer->email }}
                @endif
            </p>
        </div>
        <div class="col-auto">
            <div class="btn-group">
                @can('customers.edit')
                {{-- Isolir / Buka Isolir Button --}}
                @if($customer->router_id && $customer->pppoe_username)
                    @if($customer->status === 'suspended')
                    <button type="button" class="btn btn-success" id="btnBukaIsolir" title="Buka Isolir — kembalikan profile PPPoE ke paket semula">
                        <i class="fas fa-unlock mr-1"></i> Buka Isolir
                    </button>
                    @else
                    <button type="button" class="btn btn-warning" id="btnIsolir" title="Isolir — ubah profile PPPoE ke isolir dan putus koneksi">
                        <i class="fas fa-ban mr-1"></i> Isolir
                    </button>
                    @endif
                @endif
                <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-light">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                @endcan
                <button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split" data-toggle="dropdown"></button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" id="btnShowPassword">
                        <i class="fas fa-key mr-2"></i> Lihat Password PPPoE
                    </a>
                    @if(!$customer->mikrotik_synced && $customer->router_id && $customer->pppoe_username)
                    <a class="dropdown-item" href="#" id="btnSyncMikrotik">
                        <i class="fas fa-sync text-info mr-2"></i> Sync ke Mikrotik
                    </a>
                    @endif
                    @if(!$customer->user_id)
                    <a class="dropdown-item" href="#" id="btnGeneratePortal">
                        <i class="fas fa-user-plus text-success mr-2"></i> Buat Akun Portal
                    </a>
                    @endif
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item btn-change-status" href="#" data-status="active">
                        <i class="fas fa-check-circle text-success mr-2"></i> Aktifkan
                    </a>
                    <a class="dropdown-item btn-change-status" href="#" data-status="suspended">
                        <i class="fas fa-ban text-warning mr-2"></i> Suspend
                    </a>
                    <a class="dropdown-item btn-change-status" href="#" data-status="terminated">
                        <i class="fas fa-times-circle text-danger mr-2"></i> Terminasi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Personal Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-circle mr-2"></i>Informasi Pribadi</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">NIK</div>
                            <div class="info-value">{{ $customer->nik ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Jenis Kelamin</div>
                            <div class="info-value">{{ $customer->gender === 'male' ? 'Laki-laki' : ($customer->gender === 'female' ? 'Perempuan' : '-') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Tanggal Lahir</div>
                            <div class="info-value">{{ $customer->birth_date?->format('d F Y') ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">No. Telepon Alternatif</div>
                            <div class="info-value">{{ $customer->phone_alt ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address & Map -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Alamat</h3>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>{{ $customer->address }}</strong></p>
                <p class="text-muted mb-3">
                    {{ $customer->village?->name }}, {{ $customer->district?->name }}<br>
                    {{ $customer->city?->name }}, {{ $customer->province?->name }} {{ $customer->postal_code }}
                </p>
                
                @if($customer->latitude && $customer->longitude)
                <div id="map"></div>
                <small class="text-muted">
                    <i class="fas fa-map-pin mr-1"></i>
                    {{ $customer->latitude }}, {{ $customer->longitude }}
                    <a href="https://www.google.com/maps?q={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank" class="ml-2">
                        <i class="fas fa-external-link-alt"></i> Buka di Google Maps
                    </a>
                </small>
                @endif
            </div>
        </div>

        <!-- Service Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-wifi mr-2"></i>Layanan Internet</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Router</div>
                            <div class="info-value">{{ $customer->router?->name ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Paket</div>
                            <div class="info-value">
                                @if($customer->package)
                                <span class="badge badge-info">{{ $customer->package->name }}</span>
                                @else
                                -
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Username PPPoE</div>
                            <div class="info-value"><code>{{ $customer->pppoe_username }}</code></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Tipe Layanan</div>
                            <div class="info-value">{{ \App\Models\Customer::serviceTypes()[$customer->service_type] ?? $customer->service_type }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">IP Address</div>
                            <div class="info-value">{{ $customer->remote_address ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">MAC Address</div>
                            <div class="info-value">{{ $customer->mac_address ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Status Mikrotik</div>
                            <div class="info-value">
                                <span class="badge badge-{{ $customer->mikrotik_status === 'enabled' ? 'success' : ($customer->mikrotik_status === 'disabled' ? 'danger' : 'secondary') }}">
                                    {{ $customer->mikrotik_status }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Terakhir Online</div>
                            <div class="info-value">{{ $customer->last_connected_at?->diffForHumans() ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connectivity Status -->
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-network-wired mr-2"></i>Status Koneksi Live</h3>
                <div class="card-tools ml-auto">
                    <button type="button" class="btn btn-sm btn-outline-info" id="btnAcsMatch">
                        <i class="fas fa-link mr-1"></i> Match ACS
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnRefreshConnectivity">
                        <i class="fas fa-sync mr-1"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="connectivityLoading" class="text-muted">
                    <i class="fas fa-spinner fa-spin mr-1"></i>Memuat status koneksi...
                </div>
                <div id="connectivityContent" style="display:none">
                    <div class="connectivity-grid">
                        <div class="connectivity-tile">
                            <div class="connectivity-label">Overall</div>
                            <div class="connectivity-value" id="connOverall">-</div>
                            <small class="text-muted" id="connOverallHint">-</small>
                        </div>
                        <div class="connectivity-tile">
                            <div class="connectivity-label">PPPoE MikroTik</div>
                            <div class="connectivity-value" id="connPpp">-</div>
                            <small class="text-muted" id="connPppDetail">-</small>
                        </div>
                        <div class="connectivity-tile">
                            <div class="connectivity-label">ACS / TR-069</div>
                            <div class="connectivity-value" id="connAcs">-</div>
                            <small class="text-muted" id="connAcsDetail">-</small>
                        </div>
                        <div class="connectivity-tile">
                            <div class="connectivity-label">ONU / Optik</div>
                            <div class="connectivity-value" id="connOnu">-</div>
                            <small class="text-muted" id="connOnuDetail">-</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- WiFi ACS -->
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-broadcast-tower mr-2"></i>WiFi via ACS</h3>
                <div class="card-tools ml-auto">
                    <span class="badge badge-light" id="wifiCount">0 SSID</span>
                </div>
            </div>
            <div class="card-body">
                <div id="wifiEmpty" class="text-muted">
                    <i class="fas fa-info-circle mr-1"></i>Belum ada data WiFi. Klik Refresh atau Match ACS jika device belum terhubung.
                </div>
                <div id="wifiList"></div>
            </div>
        </div>

        <!-- Documents -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-images mr-2"></i>Dokumentasi</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label class="text-muted">Foto KTP</label>
                        @if($customer->photo_ktp_url)
                        <a href="{{ $customer->photo_ktp_url }}" target="_blank">
                            <img src="{{ $customer->photo_ktp_url }}" class="photo-preview">
                        </a>
                        @else
                        <div class="text-muted text-center py-4 bg-light rounded">
                            <i class="fas fa-image fa-2x"></i><br>
                            <small>Tidak ada foto</small>
                        </div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted">Foto Selfie</label>
                        @if($customer->photo_selfie_url)
                        <a href="{{ $customer->photo_selfie_url }}" target="_blank">
                            <img src="{{ $customer->photo_selfie_url }}" class="photo-preview">
                        </a>
                        @else
                        <div class="text-muted text-center py-4 bg-light rounded">
                            <i class="fas fa-image fa-2x"></i><br>
                            <small>Tidak ada foto</small>
                        </div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted">Foto Rumah</label>
                        @if($customer->photo_house_url)
                        <a href="{{ $customer->photo_house_url }}" target="_blank">
                            <img src="{{ $customer->photo_house_url }}" class="photo-preview">
                        </a>
                        @else
                        <div class="text-muted text-center py-4 bg-light rounded">
                            <i class="fas fa-image fa-2x"></i><br>
                            <small>Tidak ada foto</small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Billing Info -->
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title text-white"><i class="fas fa-file-invoice-dollar mr-2"></i>Tagihan</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <small class="text-muted">Biaya Bulanan</small>
                    <h2 class="text-primary mb-0">Rp {{ number_format($customer->monthly_fee, 0, ',', '.') }}</h2>
                </div>
                <hr>
                <div class="info-item">
                    <div class="info-label">Tanggal Instalasi</div>
                    <div class="info-value">{{ $customer->installation_date?->format('d M Y') ?? '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Biaya Instalasi</div>
                    <div class="info-value">Rp {{ number_format($customer->installation_fee, 0, ',', '.') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tanggal Jatuh Tempo</div>
                    <div class="info-value">Setiap tanggal {{ $customer->billing_day }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Aktif Sampai</div>
                    <div class="info-value">
                        @if($customer->active_until)
                        <span class="{{ $customer->active_until->isPast() ? 'text-danger' : 'text-success' }}">
                            {{ $customer->active_until->format('d M Y') }}
                        </span>
                        @else
                        -
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Portal Account -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-lock mr-2"></i>Akun Portal</h3>
            </div>
            <div class="card-body">
                @if($customer->user)
                <table class="table table-sm table-borderless mb-2">
                    <tr>
                        <td class="text-muted" width="35%">Status</td>
                        <td>
                            @if($customer->user->is_active)
                            <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Aktif</span>
                            @else
                            <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Nonaktif</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">ID Login</td>
                        <td><code>{{ $customer->customer_id }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email</td>
                        <td>{{ $customer->user->email }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Password</td>
                        <td>
                            <span id="portalPasswordDisplay">••••••••</span>
                            <button class="btn btn-xs btn-outline-secondary ml-1" id="btnShowPortalPassword" title="Lihat Password">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-xs btn-outline-secondary ml-1 d-none" id="btnCopyPortalPassword" title="Salin Password">
                                <i class="fas fa-copy"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dibuat</td>
                        <td>{{ $customer->user->created_at->format('d M Y H:i') }}</td>
                    </tr>
                </table>
                <div class="d-flex flex-wrap gap-1" style="gap: 5px;">
                    <button class="btn btn-sm btn-outline-warning" id="btnResetPortalPassword">
                        <i class="fas fa-key mr-1"></i> Reset Password
                    </button>
                    <button class="btn btn-sm btn-outline-{{ $customer->user->is_active ? 'danger' : 'success' }}" id="btnTogglePortalStatus" data-active="{{ $customer->user->is_active ? 1 : 0 }}">
                        <i class="fas fa-{{ $customer->user->is_active ? 'ban' : 'check-circle' }} mr-1"></i> {{ $customer->user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                    <button class="btn btn-sm btn-outline-danger" id="btnDeletePortalAccount">
                        <i class="fas fa-trash mr-1"></i> Hapus Akun
                    </button>
                </div>
                @else
                <div class="alert alert-secondary mb-0 d-flex align-items-center justify-content-between">
                    <span><i class="fas fa-info-circle mr-2"></i>Belum memiliki akun portal</span>
                    <button type="button" class="btn btn-sm btn-success" id="btnGeneratePortal2">
                        <i class="fas fa-user-plus mr-1"></i> Buat Akun
                    </button>
                </div>
                @endif
            </div>
        </div>

        <!-- Notes -->
        @if($customer->notes || $customer->internal_notes)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Catatan</h3>
            </div>
            <div class="card-body">
                @if($customer->notes)
                <div class="mb-3">
                    <label class="text-muted">Catatan Pelanggan</label>
                    <p class="mb-0">{{ $customer->notes }}</p>
                </div>
                @endif
                @if($customer->internal_notes)
                <div>
                    <label class="text-muted">Catatan Internal</label>
                    <p class="mb-0 text-warning">{{ $customer->internal_notes }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Status History -->
        @if($customer->suspended_at || $customer->terminated_at)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Riwayat Status</h3>
            </div>
            <div class="card-body">
                @if($customer->suspended_at)
                <div class="timeline-item">
                    <strong class="text-warning">Suspend</strong><br>
                    <small class="text-muted">{{ $customer->suspended_at->format('d M Y H:i') }}</small>
                    @if($customer->suspend_reason)
                    <p class="mb-0 mt-1">{{ $customer->suspend_reason }}</p>
                    @endif
                </div>
                @endif
                @if($customer->terminated_at)
                <div class="timeline-item">
                    <strong class="text-danger">Terminasi</strong><br>
                    <small class="text-muted">{{ $customer->terminated_at->format('d M Y H:i') }}</small>
                    @if($customer->terminate_reason)
                    <p class="mb-0 mt-1">{{ $customer->terminate_reason }}</p>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Meta Info -->
        <div class="card">
            <div class="card-body">
                <small class="text-muted">
                    <i class="fas fa-clock mr-1"></i>Terdaftar: {{ $customer->created_at->format('d M Y H:i') }}<br>
                    @if($customer->registeredBy)
                    <i class="fas fa-user mr-1"></i>Oleh: {{ $customer->registeredBy->name }}
                    @endif
                </small>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(function() {
    // Initialize map if coordinates exist
    @if($customer->latitude && $customer->longitude)
    const map = L.map('map').setView([{{ $customer->latitude }}, {{ $customer->longitude }}], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);
    L.marker([{{ $customer->latitude }}, {{ $customer->longitude }}]).addTo(map)
        .bindPopup('<strong>{{ $customer->name }}</strong><br>{{ $customer->address }}');
    @endif

    // Show password
    $('#btnShowPassword').on('click', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Lihat Password PPPoE?',
            text: 'Tindakan ini akan dicatat.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Tampilkan'
        }).then((result) => {
            if (result.isConfirmed) {
                $.get('{{ route("admin.customers.password", $customer) }}', function(response) {
                    Swal.fire({
                        title: 'Password PPPoE',
                        html: `<div class="input-group">
                            <input type="text" class="form-control text-center" value="${response.password}" readonly id="pwdDisplay">
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" onclick="navigator.clipboard.writeText('${response.password}'); toastr.success('Tersalin!');">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>`,
                        showConfirmButton: false,
                        showCloseButton: true
                    });
                });
            }
        });
    });

    // Generate portal account
    function doGeneratePortal() {
        Swal.fire({
            title: 'Buat Akun Portal?',
            html: '<p>Buat akun portal untuk <strong>{{ $customer->name }}</strong>?</p>' +
                  '<p class="small text-muted">Login: <strong>{{ $customer->customer_id }}</strong>' +
                  @if($customer->email)' (atau {{ $customer->email }})' + @endif
                  '<br>Password akan menggunakan password PPPoE atau di-generate otomatis.</p>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-user-plus mr-1"></i> Ya, Buat Akun',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: '{{ route("admin.customers.generate-portal", $customer) }}',
                    method: 'POST',
                }).then(response => response).catch(xhr => {
                    Swal.showValidationMessage(xhr.responseJSON?.message || 'Gagal membuat akun portal');
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value?.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Akun Portal Dibuat!',
                    html: `<p>${result.value.message}</p>
                           <div class="input-group input-group-sm mt-2">
                               <div class="input-group-prepend"><span class="input-group-text">ID Login</span></div>
                               <input type="text" class="form-control" value="${result.value.login_id}" readonly>
                               <div class="input-group-append">
                                   <button class="btn btn-outline-primary" onclick="navigator.clipboard.writeText('${result.value.login_id}'); toastr.success('Tersalin!');">
                                       <i class="fas fa-copy"></i>
                                   </button>
                               </div>
                           </div>
                           <div class="input-group input-group-sm mt-2">
                               <div class="input-group-prepend"><span class="input-group-text">Password</span></div>
                               <input type="text" class="form-control" value="${result.value.password}" readonly>
                               <div class="input-group-append">
                                   <button class="btn btn-outline-primary" onclick="navigator.clipboard.writeText('${result.value.password}'); toastr.success('Tersalin!');">
                                       <i class="fas fa-copy"></i>
                                   </button>
                               </div>
                           </div>
                           <small class="text-muted d-block mt-2">Simpan kredensial ini, hanya ditampilkan sekali.</small>`,
                    showCloseButton: true,
                }).then(() => location.reload());
            }
        });
    }

    $('#btnGeneratePortal, #btnGeneratePortal2').on('click', function(e) {
        e.preventDefault();
        doGeneratePortal();
    });

    // === Portal Account Management ===
    @if($customer->user)
    // Show portal password
    $('#btnShowPortalPassword').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).find('i').removeClass('fa-eye').addClass('fa-spinner fa-spin');
        $.get('{{ route("admin.customers.portal-password", $customer) }}', function(res) {
            if (res.success) {
                $('#portalPasswordDisplay').text(res.password);
                btn.hide();
                $('#btnCopyPortalPassword').removeClass('d-none');
            } else {
                toastr.error(res.message || 'Gagal mengambil password');
            }
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Gagal mengambil password');
        }).always(function() {
            btn.prop('disabled', false).find('i').removeClass('fa-spinner fa-spin').addClass('fa-eye');
        });
    });

    // Copy portal password
    $('#btnCopyPortalPassword').on('click', function() {
        const pw = $('#portalPasswordDisplay').text();
        if (pw && pw !== '••••••••') {
            navigator.clipboard.writeText(pw);
            toastr.success('Password tersalin!');
        }
    });

    // Reset portal password
    $('#btnResetPortalPassword').on('click', function() {
        Swal.fire({
            title: 'Reset Password Portal?',
            html: '<p>Password baru akan di-generate otomatis.</p>' +
                  '<div class="form-group text-left mt-3">' +
                  '<label><input type="checkbox" id="chkSyncPppoe" checked> Samakan dengan password PPPoE</label>' +
                  '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-key mr-1"></i> Reset',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ffc107',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const syncPppoe = $('#chkSyncPppoe').is(':checked');
                return $.ajax({
                    url: '{{ route("admin.customers.portal-reset-password", $customer) }}',
                    method: 'POST',
                    data: { sync_pppoe: syncPppoe ? 1 : 0 },
                }).then(r => r).catch(xhr => {
                    Swal.showValidationMessage(xhr.responseJSON?.message || 'Gagal reset password');
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value?.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Password Direset!',
                    html: `<div class="input-group input-group-sm mt-2">
                               <div class="input-group-prepend"><span class="input-group-text">Password Baru</span></div>
                               <input type="text" class="form-control" value="${result.value.password}" readonly>
                               <div class="input-group-append">
                                   <button class="btn btn-outline-primary" onclick="navigator.clipboard.writeText('${result.value.password}'); toastr.success('Tersalin!');">
                                       <i class="fas fa-copy"></i>
                                   </button>
                               </div>
                           </div>
                           <small class="text-muted d-block mt-2">Simpan password ini, hanya ditampilkan sekali.</small>`,
                    showCloseButton: true,
                }).then(() => location.reload());
            }
        });
    });

    // Toggle portal status (activate/deactivate)
    $('#btnTogglePortalStatus').on('click', function() {
        const isActive = $(this).data('active');
        const action = isActive ? 'menonaktifkan' : 'mengaktifkan';
        Swal.fire({
            title: `${isActive ? 'Nonaktifkan' : 'Aktifkan'} Akun Portal?`,
            html: `<p>Anda yakin ingin ${action} akun portal <strong>{{ $customer->name }}</strong>?</p>` +
                  (isActive ? '<p class="text-danger small">Pelanggan tidak akan bisa login ke portal.</p>' : ''),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: `<i class="fas fa-${isActive ? 'ban' : 'check-circle'} mr-1"></i> Ya, ${isActive ? 'Nonaktifkan' : 'Aktifkan'}`,
            cancelButtonText: 'Batal',
            confirmButtonColor: isActive ? '#dc3545' : '#28a745',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: '{{ route("admin.customers.portal-toggle-status", $customer) }}',
                    method: 'POST',
                }).then(r => r).catch(xhr => {
                    Swal.showValidationMessage(xhr.responseJSON?.message || 'Gagal mengubah status');
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value?.success) {
                toastr.success(result.value.message);
                location.reload();
            }
        });
    });

    // Delete portal account
    $('#btnDeletePortalAccount').on('click', function() {
        Swal.fire({
            title: 'Hapus Akun Portal?',
            html: '<p>Akun portal <strong>{{ $customer->name }}</strong> akan dihapus permanen.</p>' +
                  '<p class="text-danger small">Pelanggan tidak akan bisa login ke portal lagi. Akun bisa dibuat ulang.</p>',
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-trash mr-1"></i> Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: '{{ route("admin.customers.portal-delete", $customer) }}',
                    method: 'DELETE',
                }).then(r => r).catch(xhr => {
                    Swal.showValidationMessage(xhr.responseJSON?.message || 'Gagal menghapus akun');
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value?.success) {
                toastr.success(result.value.message);
                location.reload();
            }
        });
    });
    @endif

    // Sync Mikrotik
    $('#btnSyncMikrotik').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Sync ke Mikrotik?',
            html: `<p>Buat PPP Secret <strong>{{ $customer->pppoe_username }}</strong> di router <strong>{{ $customer->router->name ?? '-' }}</strong>?</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-sync mr-1"></i> Ya, Sync',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#17a2b8',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: '{{ route("admin.customers.sync-mikrotik", $customer) }}',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                }).then(response => response).catch(xhr => {
                    Swal.showValidationMessage(xhr.responseJSON?.message || 'Gagal sync');
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                const icon = result.value.already_exists ? 'info' : 'success';
                Swal.fire({
                    title: icon === 'success' ? 'Berhasil!' : 'Info',
                    text: result.value.message,
                    icon: icon,
                }).then(() => location.reload());
            }
        });
    });

    // Change status
    $('.btn-change-status').on('click', function(e) {
        e.preventDefault();
        const status = $(this).data('status');
        const statusLabel = status === 'active' ? 'Aktifkan' : (status === 'suspended' ? 'Suspend' : 'Terminasi');
        
        let html = `<p>Ubah status menjadi <strong>${statusLabel}</strong>?</p>`;
        if (status !== 'active') {
            html += `<div class="form-group text-left">
                <label>Alasan:</label>
                <textarea id="statusReason" class="form-control" rows="2"></textarea>
            </div>`;
        }
        
        Swal.fire({
            title: 'Ubah Status',
            html: html,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Ubah',
            preConfirm: () => {
                return { reason: $('#statusReason').val() || null };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('{{ route("admin.customers.change-status", $customer) }}', {
                    _token: '{{ csrf_token() }}',
                    status: status,
                    reason: result.value.reason
                }, function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        location.reload();
                    }
                }).fail(function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Gagal');
                });
            }
        });
    });

    // Isolir pelanggan
    $('#btnIsolir').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: '<i class="fas fa-ban text-warning"></i> Isolir Pelanggan?',
            html: `<p>Anda akan melakukan <strong>isolir</strong> pelanggan <strong>{{ $customer->name }}</strong>.</p>
                   <p class="text-muted small mb-2">Profile PPPoE akan diubah ke <code>isolir</code> dan koneksi aktif akan diputus.</p>
                   <div class="form-group text-left">
                       <label>Alasan isolir:</label>
                       <textarea id="isolirReason" class="form-control" rows="2" placeholder="Contoh: Belum bayar bulan ini">Isolir manual oleh admin</textarea>
                   </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-ban mr-1"></i> Ya, Isolir',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#e6a817',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const reason = $('#isolirReason').val() || 'Isolir manual oleh admin';
                return $.ajax({
                    url: '{{ route("admin.customers.isolir", $customer) }}',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}', reason: reason },
                }).then(response => response).catch(xhr => {
                    Swal.showValidationMessage(xhr.responseJSON?.message || 'Gagal melakukan isolir');
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: result.value.message,
                    icon: 'success',
                }).then(() => location.reload());
            }
        });
    });

    // Buka isolir pelanggan
    $('#btnBukaIsolir').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: '<i class="fas fa-unlock text-success"></i> Buka Isolir?',
            html: `<p>Anda akan <strong>membuka isolir</strong> pelanggan <strong>{{ $customer->name }}</strong>.</p>
                   <p class="text-muted small">Profile PPPoE akan dikembalikan ke paket <strong>{{ $customer->package?->name ?? '-' }}</strong> dan koneksi akan di-reconnect.</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-unlock mr-1"></i> Ya, Buka Isolir',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: '{{ route("admin.customers.buka-isolir", $customer) }}',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                }).then(response => response).catch(xhr => {
                    Swal.showValidationMessage(xhr.responseJSON?.message || 'Gagal membuka isolir');
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                const icon = result.value.partial ? 'warning' : 'success';
                Swal.fire({
                    title: result.value.partial ? 'Sebagian Berhasil' : 'Berhasil!',
                    text: result.value.message,
                    icon: icon,
                }).then(() => location.reload());
            }
        });
    });

    function badge(label, color) {
        return '<span class="badge badge-' + color + '">' + label + '</span>';
    }

    function loadConnectivity() {
        $('#connectivityLoading').show();
        $.get('{{ route("admin.customers.connectivity", $customer) }}', function(res) {
            const data = res.data || {};
            const overall = data.overall || {};
            const ppp = data.ppp || {};
            const acs = data.acs || {};
            const onu = data.onu || null;

            $('#connOverall').html(badge(overall.label || '-', overall.color || 'secondary'));
            $('#connOverallHint').text(overall.status || '-');

            $('#connPpp').html(ppp.online ? badge('Online', 'success') : badge('Offline', ppp.status === 'error' ? 'danger' : 'secondary'));
            $('#connPppDetail').text(ppp.online ? ((ppp.address || '-') + ' · ' + (ppp.uptime || '-')) : (ppp.message || '-'));

            $('#connAcs').html(acs.found ? (acs.online ? badge('Online', 'success') : badge('Stale/Offline', 'warning')) : badge('Belum match', 'secondary'));
            $('#connAcsDetail').html(acs.found
                ? ((acs.model || acs.serial_number || acs.device_id || '-') + (acs.last_inform_human ? ' · ' + acs.last_inform_human : ''))
                : 'Device ACS belum terhubung');

            $('#connOnu').html(onu ? badge(onu.status_label || onu.status, onu.status === 'online' ? 'success' : 'secondary') : badge('Belum assign', 'secondary'));
            $('#connOnuDetail').text(onu ? ('RX ' + (onu.rx_power || '-') + ' dBm · WAN ' + (onu.wan_ip || '-')) : 'Belum ada ONU di pelanggan');

            renderWifi(data.wifi || []);
            $('#connectivityContent').show();
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Gagal memuat status koneksi');
        }).always(function() {
            $('#connectivityLoading').hide();
        });
    }

    function renderWifi(wifis) {
        $('#wifiCount').text(wifis.length + ' SSID');
        if (!wifis.length) {
            $('#wifiEmpty').show();
            $('#wifiList').empty();
            return;
        }

        $('#wifiEmpty').hide();
        let html = '';
        wifis.forEach(function(wifi, idx) {
            const canEdit = !wifi.system;
            html += '<div class="wifi-row" data-path="' + $('<div>').text(wifi.path || '').html() + '">'
                + '<div class="d-flex align-items-start justify-content-between">'
                + '<div>'
                + '<div class="font-weight-bold">' + $('<div>').text(wifi.ssid || ('SSID ' + (idx + 1))).html() + '</div>'
                + '<div class="text-muted small">' + (wifi.band || '-') + ' · ' + (wifi.enabled ? 'Aktif' : 'Nonaktif') + ' · ' + (wifi.path || '-') + '</div>'
                + '</div>'
                + (canEdit ? '<button type="button" class="btn btn-sm btn-outline-primary btnEditWifi" data-path="' + $('<div>').text(wifi.path || '').html() + '" data-ssid="' + $('<div>').text(wifi.ssid || '').html() + '"><i class="fas fa-edit mr-1"></i>Edit</button>' : '<span class="badge badge-dark">System</span>')
                + '</div>'
                + '</div>';
        });
        $('#wifiList').html(html);
    }

    $('#btnRefreshConnectivity').on('click', loadConnectivity);

    $('#btnAcsMatch').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).find('i').removeClass('fa-link').addClass('fa-spinner fa-spin');
        $.post('{{ route("admin.customers.acs-match", $customer) }}', {
            _token: '{{ csrf_token() }}'
        }, function(res) {
            if (res.success) {
                toastr.success(res.message);
                loadConnectivity();
            } else {
                toastr.warning(res.message || 'Device ACS belum ditemukan');
            }
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Gagal match ACS');
        }).always(function() {
            btn.prop('disabled', false).find('i').removeClass('fa-spinner fa-spin').addClass('fa-link');
        });
    });

    $(document).on('click', '.btnEditWifi', function() {
        const path = $(this).data('path');
        const currentSsid = $(this).data('ssid') || '';
        Swal.fire({
            title: 'Ubah SSID',
            html: '<div class="form-group text-left">'
                + '<label>SSID</label>'
                + '<input id="wifiSsid" class="form-control" maxlength="32" value="' + $('<div>').text(currentSsid).html() + '">'
                + '</div>'
                + '<div class="form-group text-left mb-0">'
                + '<label>Password WiFi</label>'
                + '<div class="input-group">'
                + '<input id="wifiPassword" type="password" class="form-control" minlength="8" maxlength="63" autocomplete="new-password" placeholder="Isi jika ingin mengganti password">'
                + '<div class="input-group-append">'
                + '<button class="btn btn-outline-secondary" type="button" id="btnToggleWifiPassword" title="Tampilkan password"><i class="fas fa-eye"></i></button>'
                + '</div>'
                + '</div>'
                + '<small class="text-muted">Kosongkan password jika hanya mengganti nama SSID.</small>'
                + '<div id="wifiUpdateProgress" class="mt-3 d-none">'
                + '<div class="progress" style="height:6px;"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div></div>'
                + '<small class="text-muted d-block mt-2" id="wifiUpdateProgressText">Mengirim perintah ke GenieACS...</small>'
                + '</div>'
                + '</div>',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-save mr-1"></i>Simpan',
            cancelButtonText: 'Batal',
            showLoaderOnConfirm: true,
            didOpen: () => {
                $('#btnToggleWifiPassword').on('click', function() {
                    const input = $('#wifiPassword');
                    const hidden = input.attr('type') === 'password';
                    input.attr('type', hidden ? 'text' : 'password');
                    $(this).find('i').toggleClass('fa-eye', !hidden).toggleClass('fa-eye-slash', hidden);
                    $(this).attr('title', hidden ? 'Sembunyikan password' : 'Tampilkan password');
                });
            },
            preConfirm: () => {
                const ssid = $('#wifiSsid').val().trim();
                const password = $('#wifiPassword').val();
                if (!ssid) {
                    Swal.showValidationMessage('SSID wajib diisi');
                    return false;
                }
                if (password && password.length < 8) {
                    Swal.showValidationMessage('Password minimal 8 karakter');
                    return false;
                }
                $('#wifiUpdateProgress').removeClass('d-none');
                $('#wifiUpdateProgressText').text('Menghubungi GenieACS dan menunggu response device...');
                return $.ajax({
                    url: '{{ route("admin.customers.wifi", $customer) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        wlan_path: path,
                        ssid: ssid,
                        password: password
                    },
                }).then(r => r).catch(xhr => {
                    Swal.showValidationMessage(xhr.responseJSON?.message || 'Gagal mengubah WiFi');
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                if (result.value.success) {
                    toastr.success(result.value.completed ? 'WiFi berhasil diubah' : 'Perintah WiFi dikirim ke ACS');
                    loadConnectivity();
                } else {
                    toastr.error(result.value.message || 'Gagal mengubah WiFi');
                }
            }
        });
    });

    loadConnectivity();
});
</script>
@endpush
