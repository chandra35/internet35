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
                <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-light">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                @endcan
                <button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split" data-toggle="dropdown"></button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" id="btnShowPassword">
                        <i class="fas fa-key mr-2"></i> Lihat Password PPPoE
                    </a>
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
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle mr-2"></i>
                    <strong>Akun Aktif</strong><br>
                    <small>{{ $customer->user->email }}</small>
                </div>
                @else
                <div class="alert alert-secondary mb-0">
                    <i class="fas fa-info-circle mr-2"></i>
                    Belum memiliki akun portal
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
});
</script>
@endpush
