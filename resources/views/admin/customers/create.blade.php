@extends('layouts.admin')

@section('title', 'Tambah Pelanggan')

@section('page-title', 'Tambah Pelanggan Baru')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Pelanggan</a></li>
    <li class="breadcrumb-item active">Tambah</li>
@endsection

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.1/dist/cropper.min.css">
<style>
    .photo-upload-box {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f8f9fa;
        min-height: 150px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .photo-upload-box:hover {
        border-color: #007bff;
        background: #e8f4ff;
    }
    .photo-upload-box.has-image {
        padding: 5px;
    }
    .photo-upload-box img {
        max-width: 100%;
        max-height: 200px;
        border-radius: 4px;
    }
    .photo-upload-box .upload-icon {
        font-size: 2rem;
        color: #6c757d;
    }
    .photo-upload-box .upload-text {
        margin-top: 10px;
        color: #6c757d;
    }
    .camera-btn {
        position: absolute;
        bottom: 10px;
        right: 10px;
    }
    #map {
        height: 300px;
        border-radius: 8px;
        margin-top: 10px;
    }
    .cropper-modal-body {
        max-height: 70vh;
        overflow: hidden;
    }
    .cropper-modal-body img {
        max-width: 100%;
    }
    .package-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 10px;
    }
    .nav-pills .nav-link {
        border-radius: 8px;
    }
    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
</style>
@endpush

@section('content')
<form id="customerForm" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="pop_id" value="{{ $popId }}">
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Tab Navigation -->
            <ul class="nav nav-pills mb-3" id="customerTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="pill" href="#tab-info">
                        <i class="fas fa-user mr-1"></i> Informasi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#tab-service">
                        <i class="fas fa-wifi mr-1"></i> Layanan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#tab-address">
                        <i class="fas fa-map-marker-alt mr-1"></i> Alamat
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#tab-photos">
                        <i class="fas fa-camera mr-1"></i> Foto
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Tab: Personal Info -->
                <div class="tab-pane fade show active" id="tab-info">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-circle mr-2"></i>Data Pribadi</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ID Pelanggan</label>
                                        <input type="text" class="form-control" value="{{ $nextCustomerId }}" readonly>
                                        <small class="text-muted">Auto: {{ $popSetting?->pop_prefix ?: '[PREFIX]' }} + 6 digit random</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required placeholder="Nama sesuai KTP">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>NIK (No. KTP)</label>
                                        <input type="text" name="nik" class="form-control" maxlength="16" placeholder="16 digit NIK">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jenis Kelamin</label>
                                        <select name="gender" class="form-control select2">
                                            <option value="">-- Pilih --</option>
                                            <option value="male">Laki-laki</option>
                                            <option value="female">Perempuan</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Lahir</label>
                                        <input type="date" name="birth_date" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" placeholder="email@example.com">
                                        <small class="text-muted">Diperlukan jika ingin membuat akun portal</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>No. Telepon <span class="text-danger">*</span></label>
                                        <input type="text" name="phone" class="form-control" required placeholder="08xxxxxxxxxx">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>No. Telepon Alternatif</label>
                                        <input type="text" name="phone_alt" class="form-control" placeholder="08xxxxxxxxxx">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Service -->
                <div class="tab-pane fade" id="tab-service">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Layanan Internet</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Router <span class="text-danger">*</span></label>
                                        <select name="router_id" id="router_id" class="form-control select2" required>
                                            <option value="">-- Pilih Router --</option>
                                            @foreach($routers as $router)
                                            <option value="{{ $router->id }}">{{ $router->name }} ({{ $router->host }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Paket Layanan <span class="text-danger">*</span></label>
                                        <select name="package_id" id="package_id" class="form-control select2" required disabled>
                                            <option value="">Pilih Router dulu...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Package Info -->
                            <div id="packageInfo" class="package-info d-none">
                                <div class="row">
                                    <div class="col-md-4">
                                        <small class="text-muted">Kecepatan</small>
                                        <h5 id="pkgSpeed">-</h5>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Harga/Bulan</small>
                                        <h5 id="pkgPrice" class="text-primary">-</h5>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Rate Limit</small>
                                        <h5 id="pkgRate">-</h5>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tipe Layanan</label>
                                        <select name="service_type" class="form-control select2">
                                            @foreach(\App\Models\Customer::serviceTypes() as $key => $label)
                                            <option value="{{ $key }}" {{ $key === 'pppoe' ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Username PPPoE <span class="text-danger" id="usernameRequired">*</span></label>
                                        <div class="input-group">
                                            @if($popSetting?->pop_prefix)
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">{{ $popSetting->pop_prefix }}-</span>
                                            </div>
                                            @endif
                                            <input type="text" name="pppoe_username" id="pppoe_username" class="form-control" placeholder="username atau user@lokasi" required>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="btnGenerateUsername" title="Generate random username">
                                                    <i class="fas fa-magic"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="text-muted" id="usernameStatus">
                                            <i class="fas fa-info-circle mr-1"></i>Format: {{ $popSetting?->pop_prefix ? $popSetting->pop_prefix . '-' : '' }}username (contoh: {{ $popSetting?->pop_prefix ? $popSetting->pop_prefix . '-' : '' }}123456@lokasi)
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Password PPPoE</label>
                                        <div class="input-group">
                                            <input type="text" name="pppoe_password" id="pppoe_password" class="form-control" placeholder="Default: 12345">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="btnGeneratePassword" title="Set default password">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="text-muted">Default: 12345 (mudah diingat pelanggan)</small>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Biaya Bulanan</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" name="monthly_fee" id="monthly_fee" class="form-control" min="0" placeholder="Otomatis dari paket">
                                        </div>
                                        <small class="text-muted">Akan menggunakan harga paket jika kosong</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Biaya Instalasi</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" name="installation_fee" class="form-control" min="0" value="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tanggal Jatuh Tempo</label>
                                        <select name="billing_day" class="form-control select2">
                                            @for($i = 1; $i <= 28; $i++)
                                            <option value="{{ $i }}" {{ $i === 1 ? 'selected' : '' }}>Tanggal {{ $i }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tanggal Instalasi</label>
                                        <input type="date" name="installation_date" class="form-control" value="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Address -->
                <div class="tab-pane fade" id="tab-address">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-home mr-2"></i>Alamat Pemasangan</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Alamat Lengkap</label>
                                <textarea name="address" class="form-control" rows="3" placeholder="Jl. Contoh No. 123, RT 001/RW 002"></textarea>
                                <small class="text-muted">Bisa dilengkapi nanti</small>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Provinsi</label>
                                        <select name="province_code" id="province_code" class="form-control select2">
                                            <option value="">-- Pilih Provinsi --</option>
                                            @foreach($provinces as $province)
                                            <option value="{{ $province->code }}">{{ $province->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Kota/Kabupaten</label>
                                        <select name="city_code" id="city_code" class="form-control select2" disabled>
                                            <option value="">Pilih Provinsi dulu...</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Kecamatan</label>
                                        <select name="district_code" id="district_code" class="form-control select2" disabled>
                                            <option value="">Pilih Kota dulu...</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Kelurahan/Desa</label>
                                        <select name="village_code" id="village_code" class="form-control select2" disabled>
                                            <option value="">Pilih Kecamatan dulu...</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Kode Pos</label>
                                        <input type="text" name="postal_code" class="form-control" maxlength="10" placeholder="12345">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Latitude</label>
                                        <input type="number" name="latitude" id="latitude" class="form-control" step="0.00000001" placeholder="-6.175392">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Longitude</label>
                                        <input type="number" name="longitude" id="longitude" class="form-control" step="0.00000001" placeholder="106.827153">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Map -->
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-map-marker-alt text-danger"></i> Lokasi di Peta
                                    <button type="button" class="btn btn-sm btn-outline-primary ml-2" id="btnGetLocation">
                                        <i class="fas fa-crosshairs"></i> Gunakan Lokasi Saya
                                    </button>
                                </label>
                                <div id="map"></div>
                                <small class="text-muted">Klik pada peta untuk menandai lokasi pelanggan</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Photos -->
                <div class="tab-pane fade" id="tab-photos">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-images mr-2"></i>Dokumentasi Foto</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Foto KTP</label>
                                        <div class="photo-upload-box" id="ktpUploadBox" data-target="photo_ktp">
                                            <i class="fas fa-id-card upload-icon"></i>
                                            <div class="upload-text">
                                                Klik untuk upload<br>
                                                <small>atau gunakan kamera</small>
                                            </div>
                                        </div>
                                        <input type="hidden" name="photo_ktp" id="photo_ktp">
                                        <small class="text-muted">Bisa dilengkapi nanti</small>
                                        <input type="file" class="d-none" id="file_photo_ktp" accept="image/*">
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-camera" data-target="photo_ktp">
                                                <i class="fas fa-camera mr-1"></i> Kamera
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-photo d-none" data-target="photo_ktp">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Foto Selfie</label>
                                        <div class="photo-upload-box" id="selfieUploadBox" data-target="photo_selfie">
                                            <i class="fas fa-user-circle upload-icon"></i>
                                            <div class="upload-text">
                                                Klik untuk upload<br>
                                                <small>atau gunakan kamera</small>
                                            </div>
                                        </div>
                                        <input type="hidden" name="photo_selfie" id="photo_selfie">
                                        <input type="file" class="d-none" id="file_photo_selfie" accept="image/*">
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-camera" data-target="photo_selfie">
                                                <i class="fas fa-camera mr-1"></i> Kamera
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-photo d-none" data-target="photo_selfie">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Foto Depan Rumah <small class="text-muted">(opsional)</small></label>
                                        <div class="photo-upload-box" id="houseUploadBox" data-target="photo_house">
                                            <i class="fas fa-home upload-icon"></i>
                                            <div class="upload-text">
                                                Klik untuk upload<br>
                                                <small>atau gunakan kamera</small>
                                            </div>
                                        </div>
                                        <input type="hidden" name="photo_house" id="photo_house">
                                        <input type="file" class="d-none" id="file_photo_house" accept="image/*">
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-camera" data-target="photo_house">
                                                <i class="fas fa-camera mr-1"></i> Kamera
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-photo d-none" data-target="photo_house">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- User Account -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-lock mr-2"></i>Akun Portal</h3>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="create_user_account" name="create_user_account" value="1">
                        <label class="custom-control-label" for="create_user_account">Buat akun portal pelanggan</label>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Pelanggan dapat login menggunakan <strong>ID Pelanggan</strong> atau <strong>Email</strong> untuk melihat tagihan dan melakukan pembayaran.
                    </small>
                </div>
            </div>

            <!-- Sync Options -->
            @php
                $popSetting = \App\Models\PopSetting::where('user_id', $popId)->first();
            @endphp
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sync mr-2"></i>Sinkronisasi</h3>
                </div>
                <div class="card-body">
                    @if($popSetting && $popSetting->mikrotik_sync_enabled)
                    
                    <!-- Import dari Mikrotik (untuk migrasi) -->
                    <div class="mb-3 p-3 bg-light rounded">
                        <label class="d-block mb-2">
                            <i class="fas fa-download text-primary mr-1"></i>
                            <strong>Ambil dari Mikrotik</strong>
                            <small class="text-muted">(untuk pelanggan existing)</small>
                        </label>
                        <div class="input-group">
                            <select class="form-control" id="importFromMikrotik" disabled>
                                <option value="">-- Pilih Router dulu --</option>
                            </select>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-primary" id="btnLoadSecrets" disabled title="Muat daftar PPP Secret">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Pilih PPP Secret yang sudah ada di Mikrotik untuk assign ke pelanggan ini (migrasi).
                        </small>
                    </div>
                    
                    <hr>
                    
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="sync_mikrotik" name="sync_mikrotik" value="1" {{ $popSetting->mikrotik_auto_sync ? 'checked' : '' }}>
                        <label class="custom-control-label" for="sync_mikrotik">
                            <i class="fas fa-server text-info mr-1"></i>Buat PPP Secret di Mikrotik
                        </label>
                    </div>
                    <small class="text-muted d-block mb-3">
                        Username dan password akan dibuat di router yang dipilih.
                    </small>
                    @else
                    <div class="text-muted small mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Sinkronisasi Mikrotik tidak aktif. <a href="{{ route('admin.pop-settings.integration') }}">Aktifkan di pengaturan</a>
                    </div>
                    @endif

                    @if($popSetting && $popSetting->radius_enabled)
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="sync_radius" name="sync_radius" value="1" {{ $popSetting->radius_auto_sync ? 'checked' : '' }}>
                        <label class="custom-control-label" for="sync_radius">
                            <i class="fas fa-database text-success mr-1"></i>Buat user di FreeRadius
                        </label>
                    </div>
                    <small class="text-muted d-block">
                        Username akan dibuat di database Radius untuk autentikasi.
                    </small>
                    @else
                    <div class="text-muted small">
                        <i class="fas fa-info-circle mr-1"></i>
                        FreeRadius tidak aktif. <a href="{{ route('admin.pop-settings.integration') }}">Aktifkan di pengaturan</a>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Notes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Catatan</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Catatan Pelanggan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Catatan yang bisa dilihat pelanggan..."></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Catatan Internal</label>
                        <textarea name="internal_notes" class="form-control" rows="2" placeholder="Catatan khusus admin..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Hidden Fields -->
            <input type="hidden" name="imported_from_mikrotik" id="imported_from_mikrotik" value="0">
            
            <!-- Submit -->
            <div class="card bg-gradient-primary">
                <div class="card-body">
                    <!-- Import Badge -->
                    <div id="importBadge" class="alert alert-info d-none mb-3">
                        <i class="fas fa-cloud-download-alt mr-2"></i>
                        <strong>Mode Import:</strong> PPP Secret diambil dari Mikrotik. Data tidak akan dibuat ulang.
                    </div>
                    
                    <button type="submit" class="btn btn-light btn-lg btn-block" id="btnSubmit">
                        <i class="fas fa-save mr-2"></i>Simpan Pelanggan
                    </button>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-light btn-block mt-2">
                        <i class="fas fa-times mr-2"></i>Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Camera Modal -->
<div class="modal fade" id="cameraModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-camera mr-2"></i>Ambil Foto</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <video id="cameraVideo" width="100%" autoplay style="border-radius: 8px; display: none;"></video>
                <canvas id="cameraCanvas" style="display: none;"></canvas>
                <div id="cameraError" class="alert alert-warning d-none">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Kamera tidak tersedia atau tidak diizinkan.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnCapture">
                    <i class="fas fa-camera mr-1"></i> Ambil Foto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cropper Modal -->
<div class="modal fade" id="cropperModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-crop mr-2"></i>Crop Foto</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body cropper-modal-body">
                <img id="cropperImage" src="" style="max-width: 100%;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-info" id="btnRotateLeft"><i class="fas fa-undo"></i></button>
                <button type="button" class="btn btn-info" id="btnRotateRight"><i class="fas fa-redo"></i></button>
                <button type="button" class="btn btn-primary" id="btnCropSave">
                    <i class="fas fa-check mr-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/cropperjs@1.6.1/dist/cropper.min.js"></script>
<script>
let map, marker, cropper;
let currentPhotoTarget = null;
let cameraStream = null;
let packagesData = [];
let pppSecretsData = []; // Store PPP Secrets from Mikrotik

$(function() {
    // Select2 sudah diinisialisasi secara global di layout admin

    // Initialize Map
    map = L.map('map').setView([-6.2088, 106.8456], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    map.on('click', function(e) {
        setMarker(e.latlng.lat, e.latlng.lng);
    });

    // Load PPP Secrets when router changes
    $('#router_id').on('change', function() {
        const routerId = $(this).val();
        if (routerId) {
            $('#btnLoadSecrets').prop('disabled', false);
            // Auto-load secrets when router selected
            loadPPPSecrets(routerId);
        } else {
            $('#btnLoadSecrets').prop('disabled', true);
            $('#importFromMikrotik').html('<option value="">-- Pilih Router dulu --</option>').prop('disabled', true);
        }
    });

    // Manual reload PPP Secrets
    $('#btnLoadSecrets').on('click', function() {
        const routerId = $('#router_id').val();
        if (routerId) {
            loadPPPSecrets(routerId);
        }
    });

    // When selecting PPP Secret from Mikrotik
    $('#importFromMikrotik').on('change', function() {
        const secretId = $(this).val();
        if (secretId && pppSecretsData.length > 0) {
            const secret = pppSecretsData.find(s => s['.id'] === secretId);
            if (secret) {
                // Fill form with PPP Secret data
                $('#pppoe_username').val(secret.name || '');
                $('#pppoe_password').val(secret.password || '');
                
                // Set flag that this is imported from Mikrotik
                $('#imported_from_mikrotik').val('1');
                
                // Uncheck "Create to Mikrotik" since we're importing existing
                $('#sync_mikrotik').prop('checked', false).prop('disabled', true);
                
                // Show import badge
                $('#importBadge').removeClass('d-none');
                
                // Show info
                toastr.success(`PPP Secret "${secret.name}" berhasil diambil. Data sudah ada di Mikrotik, tidak perlu sync ulang.`);
                
                // Highlight the fields
                $('#pppoe_username, #pppoe_password').addClass('border-success');
                setTimeout(() => {
                    $('#pppoe_username, #pppoe_password').removeClass('border-success');
                }, 2000);

                // Try to match profile with package
                if (secret.profile) {
                    const matchingPkg = packagesData.find(p => 
                        p.profile_name === secret.profile || p.name === secret.profile
                    );
                    if (matchingPkg) {
                        $('#package_id').val(matchingPkg.id).trigger('change');
                        toastr.info(`Profile "${secret.profile}" cocok dengan paket "${matchingPkg.name}"`);
                    }
                }
            }
        } else {
            // Reset import mode when selection is cleared
            $('#imported_from_mikrotik').val('0');
            $('#sync_mikrotik').prop('disabled', false);
            $('#importBadge').addClass('d-none');
        }
    });

    // Sync checkbox handlers - show warning when enabled
    $('#sync_mikrotik').on('change', function() {
        if ($(this).is(':checked')) {
            checkAndGenerateCredentials('Mikrotik PPP Secret');
        }
    });

    $('#sync_radius').on('change', function() {
        if ($(this).is(':checked')) {
            checkAndGenerateCredentials('FreeRadius');
        }
    });

    // Region cascade
    $('#province_code').on('change', function() {
        const val = $(this).val();
        $('#city_code').html('<option value="">Pilih Provinsi dulu...</option>').prop('disabled', !val);
        $('#district_code').html('<option value="">Pilih Kota dulu...</option>').prop('disabled', true);
        $('#village_code').html('<option value="">Pilih Kecamatan dulu...</option>').prop('disabled', true);
        
        if (val) {
            loadCities(val);
        }
    });

    $('#city_code').on('change', function() {
        const val = $(this).val();
        $('#district_code').html('<option value="">Pilih Kota dulu...</option>').prop('disabled', !val);
        $('#village_code').html('<option value="">Pilih Kecamatan dulu...</option>').prop('disabled', true);
        
        if (val) {
            loadDistricts(val);
        }
    });

    $('#district_code').on('change', function() {
        const val = $(this).val();
        $('#village_code').html('<option value="">Pilih Kecamatan dulu...</option>').prop('disabled', !val);
        
        if (val) {
            loadVillages(val);
        }
    });

    // Router change - load packages
    $('#router_id').on('change', function() {
        const routerId = $(this).val();
        $('#package_id').html('<option value="">Pilih Router dulu...</option>').prop('disabled', true);
        $('#packageInfo').addClass('d-none');
        
        if (routerId) {
            loadPackages(routerId);
        }
    });

    // Package change - show info
    $('#package_id').on('change', function() {
        const packageId = $(this).val();
        if (packageId) {
            const pkg = packagesData.find(p => p.id === packageId);
            if (pkg) {
                $('#pkgSpeed').text((pkg.speed_down / 1000) + ' Mbps / ' + (pkg.speed_up / 1000) + ' Mbps');
                $('#pkgPrice').text('Rp ' + new Intl.NumberFormat('id-ID').format(pkg.price));
                $('#pkgRate').text(pkg.rate_limit || '-');
                $('#monthly_fee').val(pkg.price);
                $('#packageInfo').removeClass('d-none');
            }
        } else {
            $('#packageInfo').addClass('d-none');
        }
    });

    // Generate username from name (digits only)
    $('#btnGenerateUsername').on('click', function() {
        // Generate 6 random digits
        const randomDigits = String(Math.floor(Math.random() * 1000000)).padStart(6, '0');
        $('#pppoe_username').val(randomDigits);
        checkUsername(randomDigits);
    });

    // Generate default password (12345)
    $('#btnGeneratePassword').on('click', function() {
        $('#pppoe_password').val('12345');
    });

    // Check username availability
    let usernameTimeout;
    $('#pppoe_username').on('input', function() {
        const username = $(this).val();
        clearTimeout(usernameTimeout);
        if (username.length >= 3) {
            usernameTimeout = setTimeout(() => checkUsername(username), 500);
        }
    });

    // Get current location
    $('#btnGetLocation').on('click', function() {
        if (navigator.geolocation) {
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            navigator.geolocation.getCurrentPosition(function(position) {
                setMarker(position.coords.latitude, position.coords.longitude);
                map.setView([position.coords.latitude, position.coords.longitude], 16);
                $('#btnGetLocation').prop('disabled', false).html('<i class="fas fa-crosshairs"></i> Gunakan Lokasi Saya');
            }, function() {
                toastr.error('Tidak dapat mengakses lokasi');
                $('#btnGetLocation').prop('disabled', false).html('<i class="fas fa-crosshairs"></i> Gunakan Lokasi Saya');
            });
        }
    });

    // Photo upload boxes
    $('.photo-upload-box').on('click', function() {
        const target = $(this).data('target');
        $(`#file_${target}`).trigger('click');
    });

    // File input change
    $('input[type="file"]').on('change', function() {
        const target = $(this).attr('id').replace('file_', '');
        const file = this.files[0];
        if (file) {
            openCropper(file, target);
        }
    });

    // Camera buttons
    $('.btn-camera').on('click', function() {
        currentPhotoTarget = $(this).data('target');
        openCamera();
    });

    // Capture photo
    $('#btnCapture').on('click', function() {
        capturePhoto();
    });

    // Remove photo
    $('.btn-remove-photo').on('click', function() {
        const target = $(this).data('target');
        $(`#${target}`).val('');
        $(`#${target}UploadBox`).removeClass('has-image').html(`
            <i class="fas fa-${target === 'photo_ktp' ? 'id-card' : (target === 'photo_selfie' ? 'user-circle' : 'home')} upload-icon"></i>
            <div class="upload-text">Klik untuk upload<br><small>atau gunakan kamera</small></div>
        `);
        $(this).addClass('d-none');
    });

    // Cropper buttons
    $('#btnRotateLeft').on('click', function() {
        if (cropper) cropper.rotate(-90);
    });

    $('#btnRotateRight').on('click', function() {
        if (cropper) cropper.rotate(90);
    });

    $('#btnCropSave').on('click', function() {
        if (cropper && currentPhotoTarget) {
            const canvas = cropper.getCroppedCanvas({
                maxWidth: 1200,
                maxHeight: 1200
            });
            const base64 = canvas.toDataURL('image/jpeg', 0.8);
            
            $(`#${currentPhotoTarget}`).val(base64);
            $(`#${currentPhotoTarget}UploadBox`)
                .addClass('has-image')
                .html(`<img src="${base64}" alt="Preview">`);
            $(`.btn-remove-photo[data-target="${currentPhotoTarget}"]`).removeClass('d-none');
            
            $('#cropperModal').modal('hide');
            cropper.destroy();
            cropper = null;
        }
    });

    // Cleanup camera on modal close
    $('#cameraModal').on('hidden.bs.modal', function() {
        stopCamera();
    });

    // Form submit
    $('#customerForm').on('submit', function(e) {
        e.preventDefault();
        
        const btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...');

        $.ajax({
            url: '{{ route("admin.customers.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '{{ route("admin.customers.index") }}';
                    });
                } else {
                    btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Simpan Pelanggan');
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Simpan Pelanggan');
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMsg = '';
                    for (const key in errors) {
                        errorMsg += errors[key].join('<br>') + '<br>';
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal',
                        html: errorMsg
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                }
            }
        });
    });
});

// Helper functions
function setMarker(lat, lng) {
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            $('#latitude').val(pos.lat.toFixed(8));
            $('#longitude').val(pos.lng.toFixed(8));
        });
    }
    $('#latitude').val(lat.toFixed(8));
    $('#longitude').val(lng.toFixed(8));
}

// Load PPP Secrets from Mikrotik for import/migration
function loadPPPSecrets(routerId) {
    const btn = $('#btnLoadSecrets');
    const select = $('#importFromMikrotik');
    
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    select.html('<option value="">Memuat PPP Secrets...</option>').prop('disabled', true);
    
    $.ajax({
        url: `{{ url('admin/routers') }}/${routerId}/ppp-secrets`,
        method: 'GET',
        success: function(response) {
            if (response.success && response.secrets) {
                pppSecretsData = response.secrets;
                
                let html = '<option value="">-- Pilih Secret untuk Import --</option>';
                
                if (response.secrets.length === 0) {
                    html = '<option value="">Tidak ada PPP Secret di router ini</option>';
                } else {
                    // Group by profile if available
                    response.secrets.forEach(secret => {
                        const status = secret.disabled === 'true' ? ' [DISABLED]' : '';
                        const profile = secret.profile ? ` (${secret.profile})` : '';
                        html += `<option value="${secret['.id']}">${secret.name}${profile}${status}</option>`;
                    });
                }
                
                select.html(html).prop('disabled', false);
                toastr.success(`${response.secrets.length} PPP Secret ditemukan`);
            } else {
                select.html('<option value="">Gagal memuat - ' + (response.message || 'Error') + '</option>');
                toastr.error(response.message || 'Gagal memuat PPP Secrets');
            }
        },
        error: function(xhr) {
            select.html('<option value="">Error koneksi ke router</option>');
            toastr.error(xhr.responseJSON?.message || 'Gagal terhubung ke router');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i>');
        }
    });
}

function loadCities(provinceCode) {
    $('#city_code').html('<option value="">Memuat...</option>');
    $.get(`{{ url('admin/pop-settings/cities') }}/${provinceCode}`, function(data) {
        let html = '<option value="">-- Pilih Kota --</option>';
        data.forEach(city => {
            html += `<option value="${city.code}">${city.name}</option>`;
        });
        $('#city_code').html(html).prop('disabled', false);
    });
}

function loadDistricts(cityCode) {
    $('#district_code').html('<option value="">Memuat...</option>');
    $.get(`{{ url('admin/pop-settings/districts') }}/${cityCode}`, function(data) {
        let html = '<option value="">-- Pilih Kecamatan --</option>';
        data.forEach(district => {
            html += `<option value="${district.code}">${district.name}</option>`;
        });
        $('#district_code').html(html).prop('disabled', false);
    });
}

function loadVillages(districtCode) {
    $('#village_code').html('<option value="">Memuat...</option>');
    $.get(`{{ url('admin/pop-settings/villages') }}/${districtCode}`, function(data) {
        let html = '<option value="">-- Pilih Kelurahan --</option>';
        data.forEach(village => {
            html += `<option value="${village.code}">${village.name}</option>`;
        });
        $('#village_code').html(html).prop('disabled', false);
    });
}

function loadPackages(routerId) {
    $('#package_id').html('<option value="">Memuat...</option>');
    $.get(`{{ url('admin/customers/packages') }}/${routerId}`, function(data) {
        packagesData = data;
        let html = '<option value="">-- Pilih Paket --</option>';
        data.forEach(pkg => {
            html += `<option value="${pkg.id}">${pkg.name} - Rp ${new Intl.NumberFormat('id-ID').format(pkg.price)}</option>`;
        });
        $('#package_id').html(html).prop('disabled', false);
    });
}

function checkUsername(username) {
    $.post('{{ route("admin.customers.check-username") }}', {
        _token: '{{ csrf_token() }}',
        username: username
    }, function(response) {
        if (response.available) {
            $('#usernameStatus').html('<i class="fas fa-check text-success"></i> Username tersedia').removeClass('text-danger').addClass('text-success');
        } else {
            $('#usernameStatus').html('<i class="fas fa-times text-danger"></i> Username sudah digunakan').removeClass('text-success').addClass('text-danger');
        }
    });
}

function openCamera() {
    $('#cameraModal').modal('show');
    $('#cameraVideo').hide();
    $('#cameraError').addClass('d-none');
    
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(function(stream) {
            cameraStream = stream;
            const video = document.getElementById('cameraVideo');
            video.srcObject = stream;
            video.style.display = 'block';
        })
        .catch(function(err) {
            $('#cameraError').removeClass('d-none');
        });
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
}

function capturePhoto() {
    const video = document.getElementById('cameraVideo');
    const canvas = document.getElementById('cameraCanvas');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    
    canvas.toBlob(function(blob) {
        $('#cameraModal').modal('hide');
        openCropper(blob, currentPhotoTarget);
    }, 'image/jpeg', 0.9);
}

function openCropper(file, target) {
    currentPhotoTarget = target;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        $('#cropperImage').attr('src', e.target.result);
        $('#cropperModal').modal('show');
        
        setTimeout(function() {
            if (cropper) {
                cropper.destroy();
            }
            cropper = new Cropper(document.getElementById('cropperImage'), {
                aspectRatio: target === 'photo_ktp' ? 1.6 : (target === 'photo_selfie' ? 1 : NaN),
                viewMode: 2,
                autoCropArea: 0.9,
            });
        }, 300);
    };
    
    if (file instanceof Blob) {
        reader.readAsDataURL(file);
    }
}

// Check and auto-generate PPPoE credentials when sync is enabled
function checkAndGenerateCredentials(syncType) {
    const name = $('input[name="name"]').val();
    const username = $('#pppoe_username').val();
    const password = $('#pppoe_password').val();
    
    let needGenerate = false;
    let message = `Sinkronisasi ke ${syncType} diaktifkan.`;
    
    if (!username && !password) {
        needGenerate = true;
        message += ' Username dan password PPPoE akan di-generate otomatis.';
    } else if (!username) {
        needGenerate = true;
        message += ' Username PPPoE akan di-generate otomatis.';
    } else if (!password) {
        needGenerate = true;
        message += ' Password PPPoE akan di-generate otomatis.';
    }
    
    if (needGenerate) {
        // Auto-generate username (6 random digits)
        if (!username) {
            const generatedUsername = String(Math.floor(Math.random() * 1000000)).padStart(6, '0');
            $('#pppoe_username').val(generatedUsername);
        }
        
        // Auto-generate password (default: 12345)
        if (!password) {
            $('#pppoe_password').val('12345');
        }
    }
    
    // Scroll to PPPoE section and highlight
    $('html, body').animate({
        scrollTop: $('#pppoe_username').closest('.card').offset().top - 100
    }, 500);
    
    // Highlight the fields briefly
    $('#pppoe_username, #pppoe_password').addClass('border-primary');
    setTimeout(function() {
        $('#pppoe_username, #pppoe_password').removeClass('border-primary');
    }, 2000);
    
    toastr.info(message);
}
</script>
@endpush
