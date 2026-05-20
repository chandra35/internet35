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
        height: 400px;
        border-radius: 8px;
    }
    .custom-customer-marker { background: transparent; border: none; }
    .leaflet-control-layers { border-radius: 8px; }
    .leaflet-control-layers-toggle { width: 36px; height: 36px; }
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
                                        <label>Nama Panggilan <span class="text-muted" style="font-size:0.78rem;font-weight:400;">(opsional &mdash; untuk pencarian admin)</span></label>
                                        <input type="text" name="nickname" class="form-control" placeholder="mis. Pak Budi, Warung Pojok..." maxlength="100">
                                    </div>
                                </div>
                                        <div class="input-group">
                                            <input type="text" name="nik" id="nik" class="form-control" maxlength="16" placeholder="16 digit NIK">
                                            @if($hasResidentAccess ?? false)
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-info" id="btnSearchResident" title="Cari Data Penduduk">
                                                    <i class="fas fa-search"></i> Cari Penduduk
                                                </button>
                                            </div>
                                            @endif
                                        </div>
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
                            {{-- Router & Paket --}}
                            <h6 class="text-muted mb-3"><i class="fas fa-server mr-1"></i> Router & Paket</h6>
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

                            {{-- Detail Layanan --}}
                            <h6 class="text-muted mb-3"><i class="fas fa-cog mr-1"></i> Detail Layanan</h6>
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
                                        <label>Tanggal Instalasi</label>
                                        <input type="date" name="installation_date" class="form-control" value="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tanggal Jatuh Tempo</label>
                                        <select name="billing_day" class="form-control select2">
                                            @for($i = 1; $i <= 28; $i++)
                                            <option value="{{ $i }}" {{ $i === (int) date('j') ? 'selected' : '' }}>Tanggal {{ $i }}</option>
                                            @endfor
                                        </select>
                                        <small class="text-muted">Default: tanggal hari ini ({{ date('j') }}). Invoice akan digenerate setiap bulan pada tanggal ini.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
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
                                <div class="col-md-6">
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
                            </div>
                            
                            <hr>
                            
                            {{-- Koneksi ODP --}}
                            <h6 class="text-muted mb-3"><i class="fas fa-box mr-1"></i> Koneksi ODP <small>(Opsional)</small></h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ODP</label>
                                        <select name="odp_id" id="odp_id" class="form-control select2">
                                            <option value="">-- Tidak Ada / Belum Dipasang --</option>
                                            @foreach($odps as $odp)
                                            <option value="{{ $odp->id }}" 
                                                    data-total-ports="{{ $odp->total_ports }}"
                                                    data-used-ports="{{ $odp->used_ports }}">
                                                {{ $odp->code }} - {{ $odp->name }} ({{ $odp->total_ports - $odp->used_ports }} port tersedia)
                                            </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Bisa diisi nanti saat instalasi</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Port ODP</label>
                                        <select name="odp_port" id="odp_port" class="form-control" disabled>
                                            <option value="">-- Pilih ODP Dulu --</option>
                                        </select>
                                        <small class="text-muted" id="odp_port_info"></small>
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
                            <div class="form-group mt-3">
                                <label><i class="fas fa-map-marker-alt text-danger mr-1"></i> Lokasi di Peta</label>
                                <div id="map"></div>
                                <small class="text-muted">Klik pada peta untuk menentukan lokasi atau masukkan koordinat manual</small>
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
                    
                    {{-- Option 1: Buat PPP Secret di Mikrotik --}}
                    <div class="custom-control custom-switch mb-2">
                        <input type="checkbox" class="custom-control-input" id="sync_mikrotik" name="sync_mikrotik" value="1" {{ $popSetting->mikrotik_auto_sync ? 'checked' : '' }}>
                        <label class="custom-control-label" for="sync_mikrotik">
                            <i class="fas fa-plus-circle text-info mr-1"></i>Buat PPP Secret di Mikrotik
                        </label>
                    </div>
                    <small class="text-muted d-block mb-2" id="syncMikrotikHint">
                        Username dan password akan dibuat sebagai PPP Secret baru di router yang dipilih.
                    </small>

                    {{-- PPPoE Credentials (shown when Buat PPP Secret checked OR import mode) --}}
                    <div id="pppCredentialsSection" class="mt-2 mb-3 p-3 bg-light rounded border" style="{{ $popSetting->mikrotik_auto_sync ? '' : 'display:none' }}">
                        @if($popSetting?->pop_prefix)
                        {{-- Prefix toggle --}}
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="use_prefix" name="use_prefix" value="1" checked>
                            <label class="custom-control-label" for="use_prefix">
                                Gunakan prefix <strong>{{ $popSetting->pop_prefix }}-</strong>
                            </label>
                            <small class="text-muted d-block">Nonaktifkan jika data migrasi sudah memiliki username lengkap di Mikrotik</small>
                        </div>
                        @endif
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label class="mb-1">Username PPPoE <span class="text-danger" id="usernameRequired">*</span></label>
                                    <div class="input-group input-group-sm">
                                        @if($popSetting?->pop_prefix)
                                        <div class="input-group-prepend" id="prefixPrepend">
                                            <span class="input-group-text">{{ $popSetting->pop_prefix }}-</span>
                                        </div>
                                        @endif
                                        <input type="text" name="pppoe_username" id="pppoe_username" class="form-control" placeholder="username atau user@lokasi">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="btnGenerateUsername" title="Generate random username">
                                                <i class="fas fa-magic"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted" id="usernameStatus" data-username-state="unchecked">
                                        <i class="fas fa-info-circle mr-1"></i>Format: {{ $popSetting?->pop_prefix ? $popSetting->pop_prefix . '-' : '' }}username
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label class="mb-1">Password PPPoE</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="pppoe_password" id="pppoe_password" class="form-control" placeholder="Default: 12345">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="btnGeneratePassword" title="Set default password">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">Kosongkan untuk default: 12345</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    {{-- Option 2: Ambil dari Mikrotik (hidden when sync_mikrotik checked) --}}
                    <div id="importMikrotikSection">
                        <label class="d-block mb-2">
                            <i class="fas fa-download text-primary mr-1"></i>
                            <strong>Atau: Ambil dari Mikrotik</strong>
                            <small class="text-muted">(untuk pelanggan existing)</small>
                        </label>
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnBrowseSecrets" disabled>
                                <i class="fas fa-search mr-1"></i>Browse PPP Secret...
                            </button>
                            <span class="ml-2 text-muted small" id="importedSecretInfo">
                                <i class="fas fa-info-circle mr-1"></i>Pilih router terlebih dahulu
                            </span>
                        </div>

                        {{-- Import active badge --}}
                        <div id="importActiveTag" class="d-none mt-2">
                            <div class="alert alert-success alert-sm py-2 px-3 mb-0 d-flex align-items-center justify-content-between">
                                <span>
                                    <i class="fas fa-check-circle mr-1"></i>
                                    <strong>PPP Secret terpilih:</strong> <span id="importedSecretName">-</span>
                                    <span class="badge badge-light ml-1" id="importedSecretProfile"></span>
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-danger ml-2" id="btnClearImport" title="Batalkan import">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <small class="text-muted d-block mt-1">
                            Untuk assign PPP Secret yang sudah ada di Mikrotik ke pelanggan ini (migrasi).
                        </small>
                    </div>
                    
                    @else
                    <div class="text-muted small mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Sinkronisasi Mikrotik tidak aktif. <a href="{{ route('admin.pop-settings.integration') }}">Aktifkan di pengaturan</a>
                    </div>
                    @endif

                    @if($popSetting && $popSetting->radius_enabled)
                    <hr>
                    <div class="custom-control custom-switch mb-2">
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

                    <!-- Status Activation -->
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="activate_now" name="activate_now" value="1" checked>
                        <label class="custom-control-label text-white" for="activate_now">
                            <i class="fas fa-check-circle mr-1"></i>Langsung aktifkan pelanggan
                            <small class="d-block text-white-50">Centang agar invoice otomatis digenerate saat tagihan tiba. Jika tidak dicentang, status = <strong>Pending</strong> (tidak dibilling).</small>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-light btn-lg btn-block" id="btnSubmit" disabled>
                        <i class="fas fa-save mr-2"></i>Simpan Pelanggan
                    </button>
                    <small class="text-white-50 d-block text-center mt-1" id="btnSubmitHint">
                        <i class="fas fa-info-circle mr-1"></i>Lengkapi data wajib untuk mengaktifkan tombol simpan
                    </small>
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

{{-- Modal Browse PPP Secrets --}}
<div class="modal fade" id="pppSecretsModal" tabindex="-1" aria-labelledby="pppSecretsModalLabel">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="pppSecretsModalLabel">
                    <i class="fas fa-key mr-2"></i>Browse PPP Secret di Mikrotik
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                {{-- Search bar --}}
                <div class="p-3 border-bottom bg-light">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" class="form-control" id="searchSecrets" placeholder="Cari berdasarkan nama, profile, atau comment...">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" id="btnRefreshSecrets" title="Refresh dari router">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted" id="secretsCount">-</small>
                        <div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary active" data-filter="all">Semua</button>
                                <button type="button" class="btn btn-outline-success" data-filter="active">Aktif</button>
                                <button type="button" class="btn btn-outline-danger" data-filter="disabled">Disabled</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Loading state --}}
                <div id="secretsLoading" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                    <p class="text-muted">Memuat PPP Secrets dari router...</p>
                </div>

                {{-- Empty state --}}
                <div id="secretsEmpty" class="text-center py-5 d-none">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Tidak ada PPP Secret ditemukan</p>
                </div>

                {{-- Error state --}}
                <div id="secretsError" class="text-center py-5 d-none">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <p class="text-danger" id="secretsErrorMsg">Gagal memuat data</p>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnRetrySecrets">
                        <i class="fas fa-redo mr-1"></i>Coba Lagi
                    </button>
                </div>

                {{-- Secrets table --}}
                <div id="secretsTableWrapper" class="d-none">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th style="width: 30%">Username</th>
                                    <th style="width: 20%">Profile</th>
                                    <th style="width: 30%">Comment</th>
                                    <th style="width: 10%" class="text-center">Status</th>
                                    <th style="width: 10%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="secretsTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <small class="text-muted">
                    <i class="fas fa-info-circle mr-1"></i>
                    Pilih secret yang ingin di-assign ke pelanggan ini
                </small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- Resident Search Modal --}}
@if($hasResidentAccess ?? false)
<div class="modal fade" id="residentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white"><i class="fas fa-address-book mr-1"></i> Cari Data Penduduk</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" id="residentSearchInput" class="form-control" placeholder="Ketik NIK, Nama, atau No KK..." autofocus>
                    <div class="input-group-append">
                        <button class="btn btn-info" id="btnResidentSearch"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div id="residentSearchResults">
                    <p class="text-muted text-center py-3">Ketik minimal 2 karakter untuk mencari</p>
                </div>
            </div>
            <div class="modal-footer">
                <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Pilih penduduk untuk mengisi data pelanggan otomatis</small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endif

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

// Validate form completeness - enable/disable submit button
function validateForm() {
    const name = $('input[name="name"]').val()?.trim();
    const phone = $('input[name="phone"]').val()?.trim();
    const routerId = $('#router_id').val();
    const packageId = $('#package_id').val();
    const syncMikrotik = $('#sync_mikrotik').is(':checked');
    const importedFromMikrotik = $('#imported_from_mikrotik').val() === '1';
    const pppoeUsername = $('#pppoe_username').val()?.trim();
    const usernameState = $('#usernameStatus').data('username-state');
    
    let missing = [];
    if (!name) missing.push('Nama Lengkap');
    if (!phone) missing.push('No. Telepon');
    if (!routerId) missing.push('Router');
    if (!packageId) missing.push('Paket Layanan');
    
    // PPPoE username required when sync_mikrotik is checked or import mode
    if ((syncMikrotik || importedFromMikrotik) && !pppoeUsername) {
        missing.push('Username PPPoE');
    }
    
    // Check username availability state
    if (usernameState === 'unavailable') {
        missing.push('Username tersedia (username saat ini sudah digunakan)');
    }
    
    const btn = $('#btnSubmit');
    const hint = $('#btnSubmitHint');
    
    if (missing.length === 0) {
        btn.prop('disabled', false).removeClass('btn-secondary').addClass('btn-light');
        hint.html('<i class="fas fa-check-circle mr-1"></i>Data lengkap, siap disimpan').removeClass('text-white-50').addClass('text-white');
    } else {
        btn.prop('disabled', true).removeClass('btn-light').addClass('btn-secondary');
        hint.html('<i class="fas fa-info-circle mr-1"></i>Belum lengkap: ' + missing.join(', ')).removeClass('text-white').addClass('text-white-50');
    }
}

$(function() {
    // Select2 sudah diinisialisasi secara global di layout admin

    // Initialize Map with multiple layers (same as ODP)
    var defaultLat = -6.2088;
    var defaultLng = 106.8456;
    map = L.map('map').setView([defaultLat, defaultLng], 13);

    // Google tile layers
    const googleSat = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        maxZoom: 20, attribution: '© Google Satellite'
    });
    const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20, attribution: '© Google Hybrid'
    });
    const googleStreet = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        maxZoom: 20, attribution: '© Google Maps'
    });
    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '© OpenStreetMap contributors'
    });

    // Default to Hybrid view
    googleHybrid.addTo(map);

    // Layer control
    L.control.layers({
        "🛰️ Satelit + Label": googleHybrid,
        "🛰️ Satelit": googleSat,
        "🗺️ Street": googleStreet,
        "🗺️ OpenStreetMap": osm
    }, null, { position: 'topright' }).addTo(map);

    // Scale control
    L.control.scale({ imperial: false }).addTo(map);

    // Custom icon for customer marker
    const customerIcon = L.divIcon({
        className: 'custom-customer-marker',
        html: '<div style="background:#28a745;color:white;padding:5px 10px;border-radius:5px;font-weight:bold;box-shadow:0 2px 5px rgba(0,0,0,0.3);white-space:nowrap;"><i class="fas fa-user"></i> Pelanggan</div>',
        iconSize: [90, 30],
        iconAnchor: [45, 30]
    });

    // Click on map to set location
    map.on('click', function(e) {
        setMarker(e.latlng.lat, e.latlng.lng);
    });

    // Update marker when coordinates change manually
    $('#latitude, #longitude').on('change', function() {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());
        if (!isNaN(lat) && !isNaN(lng)) {
            setMarker(lat, lng);
            map.setView([lat, lng], 18);
        }
    });

    // Geolocation button (Leaflet control, top-left)
    if (navigator.geolocation) {
        const locateBtn = L.control({ position: 'topleft' });
        locateBtn.onAdd = function() {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            div.innerHTML = '<a href="#" title="Lokasi Saya" style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;background:white;font-size:16px;"><i class="fas fa-crosshairs"></i></a>';
            div.onclick = function(e) {
                e.preventDefault();
                const btn = div.querySelector('a');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                navigator.geolocation.getCurrentPosition(function(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    const accuracy = pos.coords.accuracy;
                    map.setView([lat, lng], 18);
                    setMarker(lat, lng);
                    btn.innerHTML = '<i class="fas fa-crosshairs"></i>';
                    toastr.success('Lokasi ditemukan (akurasi: ' + Math.round(accuracy) + 'm)');
                }, function(err) {
                    btn.innerHTML = '<i class="fas fa-crosshairs"></i>';
                    let msg = 'Tidak dapat mengakses lokasi';
                    if (err.code === 1) msg = 'Izin lokasi ditolak. Aktifkan GPS dan izinkan akses lokasi di browser.';
                    else if (err.code === 2) msg = 'Lokasi tidak tersedia. Pastikan GPS aktif.';
                    else if (err.code === 3) msg = 'Waktu permintaan lokasi habis. Coba lagi.';
                    toastr.error(msg);
                }, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                });
                return false;
            };
            return div;
        };
        locateBtn.addTo(map);
    }

    // Fix map rendering when Alamat tab is shown (Leaflet in hidden tabs)
    $('a[data-toggle="pill"]').on('shown.bs.tab', function(e) {
        if ($(e.target).attr('href') === '#tab-address') {
            setTimeout(function() { map.invalidateSize(); }, 100);
        }
    });

    // Load PPP Secrets when router changes
    $('#router_id').on('change', function() {
        const routerId = $(this).val();
        if (routerId) {
            // Enable browse button
            $('#btnBrowseSecrets').prop('disabled', false);
            $('#importedSecretInfo').html('<i class="fas fa-info-circle mr-1"></i>Klik "Browse PPP Secret" untuk melihat daftar');
        } else {
            $('#btnBrowseSecrets').prop('disabled', true);
            $('#importedSecretInfo').html('<i class="fas fa-info-circle mr-1"></i>Pilih router terlebih dahulu');
        }
    });

    // Browse PPP Secrets button → open modal
    $('#btnBrowseSecrets').on('click', function() {
        const routerId = $('#router_id').val();
        if (!routerId) {
            toastr.warning('Pilih router terlebih dahulu');
            return;
        }
        $('#pppSecretsModal').modal('show');
        loadPPPSecretsModal(routerId);
    });

    // Refresh button inside modal
    $('#btnRefreshSecrets, #btnRetrySecrets').on('click', function() {
        const routerId = $('#router_id').val();
        if (routerId) loadPPPSecretsModal(routerId);
    });

    // Search filter inside modal
    $('#searchSecrets').on('input', function() {
        filterSecretsTable();
    });

    // Status filter buttons inside modal
    $('#pppSecretsModal .btn-group .btn').on('click', function() {
        $(this).siblings().removeClass('active');
        $(this).addClass('active');
        filterSecretsTable();
    });

    // Select a PPP Secret from modal
    $(document).on('click', '.btn-select-secret', function() {
        const secretId = $(this).data('id');
        const secret = pppSecretsData.find(s => s['.id'] === secretId);
        if (!secret) return;

        // Fill form with PPP Secret data
        $('#pppoe_username').val(secret.name || '');
        $('#pppoe_password').val(secret.password || '');
        
        // Set flag that this is imported from Mikrotik
        $('#imported_from_mikrotik').val('1');
        
        // Uncheck & disable "Buat PPP Secret" since we're importing existing
        $('#sync_mikrotik').prop('checked', false).prop('disabled', true);
        $('#syncMikrotikHint').text('Dinonaktifkan karena menggunakan PPP Secret existing dari Mikrotik.');
        
        // Show credentials section with imported data (read-only)
        $('#pppCredentialsSection').slideDown(200);
        $('#pppoe_username, #pppoe_password').prop('readonly', true).addClass('bg-light');
        
        // Show import active tag
        $('#importActiveTag').removeClass('d-none');
        $('#importedSecretName').text(secret.name);
        $('#importedSecretProfile').text(secret.profile || '-');
        
        // Hide browse button, show active state
        $('#btnBrowseSecrets').addClass('d-none');
        
        // Show import badge at submit area
        $('#importBadge').removeClass('d-none');
        
        // Close modal
        $('#pppSecretsModal').modal('hide');
        
        toastr.success(`PPP Secret "${secret.name}" berhasil dipilih. Data sudah ada di Mikrotik, tidak perlu sync ulang.`);
        
        // Highlight the fields
        $('#pppoe_username, #pppoe_password').addClass('border-success');
        setTimeout(() => {
            $('#pppoe_username, #pppoe_password').removeClass('border-success');
        }, 2000);

        // Re-check username availability (DB only, since we know it exists in Mikrotik)
        if (secret.name) {
            checkUsername(secret.name);
        }

        validateForm();

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
    });

    // Clear import selection
    $('#btnClearImport').on('click', function() {
        // Reset import mode
        $('#imported_from_mikrotik').val('0');
        
        // Re-enable "Buat PPP Secret" checkbox
        $('#sync_mikrotik').prop('disabled', false);
        $('#syncMikrotikHint').text('Username dan password akan dibuat sebagai PPP Secret baru di router yang dipilih.');
        
        // Hide credentials section & remove read-only
        $('#pppCredentialsSection').slideUp(200);
        $('#pppoe_username, #pppoe_password').prop('readonly', false).removeClass('bg-light');
        
        // Hide import active tag, show browse button
        $('#importActiveTag').addClass('d-none');
        $('#btnBrowseSecrets').removeClass('d-none');
        
        // Hide import badge
        $('#importBadge').addClass('d-none');
        
        // Clear username/password
        $('#pppoe_username').val('');
        $('#pppoe_password').val('');
        $('#usernameStatus').html('<i class="fas fa-info-circle mr-1"></i>Format: {{ $popSetting?->pop_prefix ? $popSetting->pop_prefix . "-" : "" }}username')
            .data('username-state', 'unchecked');
        
        toastr.info('Import dibatalkan');
        validateForm();
    });

    // ── Sync checkbox mutual exclusivity ──
    $('#sync_mikrotik').on('change', function() {
        if ($(this).is(':checked')) {
            // Show credentials, hide "Ambil dari Mikrotik" 
            $('#pppCredentialsSection').slideDown(200);
            $('#importMikrotikSection').slideUp(200);
            $('#pppoe_username').prop('required', true);
            checkAndGenerateCredentials('Mikrotik PPP Secret');
        } else {
            // Hide credentials, show "Ambil dari Mikrotik"
            $('#pppCredentialsSection').slideUp(200);
            $('#importMikrotikSection').slideDown(200);
            $('#pppoe_username').prop('required', false);
        }
    });

    // ── Prefix toggle ──
    $('#use_prefix').on('change', function() {
        if ($(this).is(':checked')) {
            $('#prefixPrepend').show();
        } else {
            $('#prefixPrepend').hide();
        }
        // Re-check username with new prefix setting
        const username = $('#pppoe_username').val()?.trim();
        if (username && username.length >= 3) {
            checkUsername(username);
        }
    });

    // Initial state: if sync_mikrotik is checked on load, hide import section & show credentials
    if ($('#sync_mikrotik').is(':checked')) {
        $('#importMikrotikSection').hide();
        $('#pppCredentialsSection').show();
        $('#pppoe_username').prop('required', true);
    } else {
        $('#pppCredentialsSection').hide();
        $('#pppoe_username').prop('required', false);
    }

    // ── Form validation listeners ──
    $('input[name="name"], input[name="phone"], #pppoe_username').on('input', function() { validateForm(); });
    $('#router_id, #package_id').on('change', function() { validateForm(); });
    $('#sync_mikrotik').on('change', function() { validateForm(); });
    
    // Initial validation check
    validateForm();

    $('#sync_radius').on('change', function() {
        if ($(this).is(':checked')) {
            checkAndGenerateCredentials('FreeRadius');
        }
    });

    // Region cascade
    let _skipCascade = false;

    $('#province_code').on('change', function() {
        if (_skipCascade) return;
        const val = $(this).val();
        $('#city_code').html('<option value="">Pilih Provinsi dulu...</option>').prop('disabled', !val);
        $('#district_code').html('<option value="">Pilih Kota dulu...</option>').prop('disabled', true);
        $('#village_code').html('<option value="">Pilih Kecamatan dulu...</option>').prop('disabled', true);
        
        if (val) {
            loadCities(val);
        }
    });

    $('#city_code').on('change', function() {
        if (_skipCascade) return;
        const val = $(this).val();
        $('#district_code').html('<option value="">Pilih Kota dulu...</option>').prop('disabled', !val);
        $('#village_code').html('<option value="">Pilih Kecamatan dulu...</option>').prop('disabled', true);
        
        if (val) {
            loadDistricts(val);
        }
    });

    $('#district_code').on('change', function() {
        if (_skipCascade) return;
        const val = $(this).val();
        $('#village_code').html('<option value="">Pilih Kecamatan dulu...</option>').prop('disabled', !val);
        
        if (val) {
            loadVillages(val);
        }
    });

    // Router change - load packages & re-check username
    $('#router_id').on('change', function() {
        const routerId = $(this).val();
        $('#package_id').html('<option value="">Pilih Router dulu...</option>').prop('disabled', true);
        $('#packageInfo').addClass('d-none');
        
        if (routerId) {
            loadPackages(routerId);
            
            // Re-check username against new router's Mikrotik
            const username = $('#pppoe_username').val();
            if (username && username.length >= 3) {
                checkUsername(username);
            }
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

    // Used ODP ports data from controller
    var usedOdpPorts = @json($usedOdpPorts ?? []);
    
    // ODP change handler - populate port dropdown with protection
    $('#odp_id').on('change', function() {
        const $selected = $(this).find(':selected');
        const odpId = $(this).val();
        const totalPorts = parseInt($selected.data('total-ports')) || 8;
        const $portSelect = $('#odp_port');
        const $portInfo = $('#odp_port_info');
        
        if (!odpId) {
            $portSelect.html('<option value="">-- Pilih ODP Dulu --</option>').prop('disabled', true);
            $portInfo.html('');
            return;
        }
        
        // Get used ports for this ODP
        const usedForOdp = usedOdpPorts[odpId] || {};
        const usedCount = Object.keys(usedForOdp).length;
        const availableCount = totalPorts - usedCount;
        
        // Build options
        let options = '<option value="">-- Pilih Port --</option>';
        for (let i = 1; i <= totalPorts; i++) {
            const usedData = usedForOdp[i];
            if (usedData) {
                // Port is used - disable it and show customer info
                options += `<option value="${i}" disabled class="text-danger">Port ${i} - ⛔ ${usedData.customer_id}: ${usedData.customer_name}</option>`;
            } else {
                options += `<option value="${i}">Port ${i} - ✅ Tersedia</option>`;
            }
        }
        $portSelect.html(options).prop('disabled', false);
        
        // Update info text
        if (availableCount > 0) {
            $portInfo.html(`<span class="text-success"><i class="fas fa-info-circle"></i> ${availableCount} dari ${totalPorts} port tersedia</span>`);
        } else {
            $portInfo.html(`<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Semua port sudah terpakai!</span>`);
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

    // Geolocation is now handled by Leaflet control on the map

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
        
        // Block submit if username is definitively unavailable
        const usernameStatusEl = $('#usernameStatus');
        const usernameState = usernameStatusEl.data('username-state');
        
        // Only block when definitely unavailable (exists in DB as active customer)
        if (usernameState === 'unavailable') {
            Swal.fire({
                icon: 'warning',
                title: 'Username Tidak Tersedia',
                text: 'Username PPPoE yang dipilih sudah digunakan oleh pelanggan lain. Silakan ganti username terlebih dahulu.',
            });
            return;
        }
        
        // Warn if username exists in Mikrotik only (orphaned) but allow proceeding
        if (usernameState === 'mikrotik-only') {
            Swal.fire({
                icon: 'question',
                title: 'Username Ada di Mikrotik',
                text: 'Username ini sudah ada di Mikrotik sebagai PPP Secret yang belum terdaftar. Lanjutkan simpan? (Secret di Mikrotik tidak akan dibuat ulang)',
                showCancelButton: true,
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    doSubmit();
                }
            });
            return;
        }
        
        // Check that PPPoE username is filled when sync_mikrotik is checked
        if ($('#sync_mikrotik').is(':checked') && !$('#pppoe_username').val().trim()) {
            Swal.fire({
                icon: 'warning',
                title: 'Username PPPoE Kosong',
                text: 'Silakan isi username PPPoE atau generate otomatis.',
            });
            return;
        }
        
        doSubmit();
    });
});

// Perform the actual form submission via AJAX
function doSubmit() {
    const btn = $('#btnSubmit');
    const form = $('#customerForm');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...');

    $.ajax({
        url: '{{ route("admin.customers.store") }}',
        type: 'POST',
        data: form.serialize(),
        timeout: 60000, // 60 second timeout
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: true
                }).then(() => {
                    window.location.href = '{{ route("admin.customers.index") }}';
                });
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            if (xhr.statusText === 'timeout') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Timeout',
                    text: 'Request memakan waktu terlalu lama. Silakan cek apakah pelanggan sudah tersimpan.',
                });
            } else if (xhr.status === 422) {
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
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Simpan Pelanggan');
        }
    });
}

// Helper functions
function setMarker(lat, lng) {
    const customerIcon = L.divIcon({
        className: 'custom-customer-marker',
        html: '<div style="background:#28a745;color:white;padding:5px 10px;border-radius:5px;font-weight:bold;box-shadow:0 2px 5px rgba(0,0,0,0.3);white-space:nowrap;"><i class="fas fa-user"></i> Pelanggan</div>',
        iconSize: [90, 30],
        iconAnchor: [45, 30]
    });
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { icon: customerIcon, draggable: true }).addTo(map);
        marker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            $('#latitude').val(pos.lat.toFixed(8));
            $('#longitude').val(pos.lng.toFixed(8));
        });
    }
    $('#latitude').val(lat.toFixed(8));
    $('#longitude').val(lng.toFixed(8));
}

// Load PPP Secrets into modal from Mikrotik
function loadPPPSecretsModal(routerId) {
    // Show loading, hide others
    $('#secretsLoading').removeClass('d-none');
    $('#secretsTableWrapper, #secretsEmpty, #secretsError').addClass('d-none');
    $('#searchSecrets').val('');
    
    $.ajax({
        url: `{{ url('admin/routers') }}/${routerId}/ppp-secrets`,
        method: 'GET',
        timeout: 30000,
        success: function(response) {
            $('#secretsLoading').addClass('d-none');
            
            if (response.success && response.secrets) {
                pppSecretsData = response.secrets;
                
                if (response.secrets.length === 0) {
                    $('#secretsEmpty').removeClass('d-none');
                    $('#secretsCount').text('0 PPP Secret');
                } else {
                    renderSecretsTable(response.secrets);
                    $('#secretsTableWrapper').removeClass('d-none');
                    $('#secretsCount').text(`${response.secrets.length} PPP Secret ditemukan`);
                }
            } else {
                $('#secretsError').removeClass('d-none');
                $('#secretsErrorMsg').text(response.message || 'Gagal memuat PPP Secrets');
            }
        },
        error: function(xhr) {
            $('#secretsLoading').addClass('d-none');
            $('#secretsError').removeClass('d-none');
            $('#secretsErrorMsg').text(xhr.responseJSON?.message || 'Gagal terhubung ke router');
        }
    });
}

// Render PPP Secrets table rows
function renderSecretsTable(secrets) {
    const tbody = $('#secretsTableBody');
    let html = '';
    
    secrets.forEach(secret => {
        const isDisabled = secret.disabled === 'true';
        const statusBadge = isDisabled 
            ? '<span class="badge badge-danger">Disabled</span>'
            : '<span class="badge badge-success">Aktif</span>';
        const rowClass = isDisabled ? 'table-secondary' : '';
        const name = escapeHtml(secret.name || '-');
        const profile = escapeHtml(secret.profile || '-');
        const comment = escapeHtml(secret.comment || '-');
        
        html += `
            <tr class="${rowClass}" data-name="${name.toLowerCase()}" data-profile="${profile.toLowerCase()}" data-comment="${comment.toLowerCase()}" data-disabled="${isDisabled}">
                <td>
                    <strong>${name}</strong>
                </td>
                <td><span class="badge badge-info">${profile}</span></td>
                <td class="text-muted small">${comment}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-primary btn-select-secret" data-id="${secret['.id']}" title="Pilih secret ini">
                        <i class="fas fa-check"></i>
                    </button>
                </td>
            </tr>`;
    });
    
    tbody.html(html);
}

// Filter secrets table by search & status
function filterSecretsTable() {
    const search = $('#searchSecrets').val().toLowerCase();
    const statusFilter = $('#pppSecretsModal .btn-group .btn.active').data('filter') || 'all';
    let visible = 0;
    
    $('#secretsTableBody tr').each(function() {
        const name = $(this).data('name') || '';
        const profile = $(this).data('profile') || '';
        const comment = $(this).data('comment') || '';
        const isDisabled = $(this).data('disabled');
        
        // Text match
        const matchesSearch = !search || 
            name.includes(search) || 
            profile.includes(search) || 
            comment.includes(search);
        
        // Status match
        let matchesStatus = true;
        if (statusFilter === 'active') matchesStatus = !isDisabled;
        if (statusFilter === 'disabled') matchesStatus = isDisabled;
        
        if (matchesSearch && matchesStatus) {
            $(this).show();
            visible++;
        } else {
            $(this).hide();
        }
    });
    
    $('#secretsCount').text(`${visible} dari ${pppSecretsData.length} PPP Secret`);
}

// Escape HTML for safe rendering
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function loadCities(provinceCode, callback) {
    $('#city_code').html('<option value="">Memuat...</option>');
    $.get(`{{ url('admin/pop-settings/cities') }}/${provinceCode}`, function(data) {
        let html = '<option value="">-- Pilih Kota --</option>';
        data.forEach(city => {
            html += `<option value="${city.code}">${city.name}</option>`;
        });
        $('#city_code').html(html).prop('disabled', false);
        if (callback) callback();
    });
}

function loadDistricts(cityCode, callback) {
    $('#district_code').html('<option value="">Memuat...</option>');
    $.get(`{{ url('admin/pop-settings/districts') }}/${cityCode}`, function(data) {
        let html = '<option value="">-- Pilih Kecamatan --</option>';
        data.forEach(district => {
            html += `<option value="${district.code}">${district.name}</option>`;
        });
        $('#district_code').html(html).prop('disabled', false);
        if (callback) callback();
    });
}

function loadVillages(districtCode, callback) {
    $('#village_code').html('<option value="">Memuat...</option>');
    $.get(`{{ url('admin/pop-settings/villages') }}/${districtCode}`, function(data) {
        let html = '<option value="">-- Pilih Kelurahan --</option>';
        data.forEach(village => {
            html += `<option value="${village.code}">${village.name}</option>`;
        });
        $('#village_code').html(html).prop('disabled', false);
        if (callback) callback();
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
    const routerId = $('#router_id').val();
    const usePrefix = $('#use_prefix').is(':checked') ? '1' : '0';
    
    // Show checking indicator
    $('#usernameStatus').html('<i class="fas fa-spinner fa-spin"></i> Memeriksa ketersediaan...')
        .removeClass('text-danger text-success text-warning').addClass('text-info')
        .data('username-state', 'checking');
    
    $.post('{{ route("admin.customers.check-username") }}', {
        _token: '{{ csrf_token() }}',
        username: username,
        router_id: routerId,
        exclude_id: null,
        use_prefix: usePrefix
    }, function(response) {
        let html = '';
        let statusClass = '';
        let state = 'available';
        
        if (response.available) {
            html = '<i class="fas fa-check-circle text-success"></i> Username tersedia';
            statusClass = 'text-success';
            state = 'available';
            
            // Show Mikrotik check status
            if (response.mikrotik_checked === true) {
                html += ' <span class="badge badge-success"><i class="fas fa-server"></i> Tidak ada di Mikrotik</span>';
            } else if (response.mikrotik_checked === false && response.mikrotik_error) {
                html += ` <span class="badge badge-warning" title="${response.mikrotik_error}"><i class="fas fa-exclamation-triangle"></i> Mikrotik tidak dicek</span>`;
            }
            
            // Info about previously deleted username (not blocking)
            if (response.was_deleted) {
                html += `<br><small class="text-muted"><i class="fas fa-info-circle"></i> ${response.deleted_info}</small>`;
            }
        } else {
            if (response.mikrotik_only) {
                // Exists only in Mikrotik (orphaned secret) - warning but allow if importing
                html = '<i class="fas fa-exclamation-triangle text-warning"></i> <strong>' + response.message + '</strong>';
                statusClass = 'text-warning';
                // Allow submit if user is in import mode
                state = $('#imported_from_mikrotik').val() === '1' ? 'available' : 'mikrotik-only';
            } else if (response.db_exists) {
                // Definitively unavailable - active customer has this username
                statusClass = 'text-danger';
                state = 'unavailable';
                html = '<i class="fas fa-times-circle text-danger"></i> ' + (response.message || 'Username sudah digunakan di database');
                if (response.mikrotik_checked === true && !response.mikrotik_exists) {
                    html += ' <span class="badge badge-info"><i class="fas fa-server"></i> Belum ada di Mikrotik</span>';
                } else if (response.mikrotik_exists) {
                    html += ' <span class="badge badge-danger"><i class="fas fa-server"></i> Juga ada di Mikrotik</span>';
                }
            } else {
                statusClass = 'text-danger';
                state = 'unavailable';
                html = '<i class="fas fa-times-circle text-danger"></i> ' + (response.message || 'Username tidak tersedia');
            }
        }
        
        // Show full username that will be created
        if (response.full_username && response.full_username !== username) {
            html += `<br><small class="text-muted">Username lengkap: <code>${response.full_username}</code></small>`;
        }
        
        $('#usernameStatus').html(html)
            .removeClass('text-danger text-success text-warning text-info')
            .addClass(statusClass)
            .data('username-state', state);
        
        validateForm();
    }).fail(function() {
        $('#usernameStatus').html('<i class="fas fa-exclamation-triangle text-warning"></i> Gagal memeriksa username (tidak memblokir penyimpanan)')
            .removeClass('text-danger text-success text-info').addClass('text-warning')
            .data('username-state', 'error');
        validateForm();
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

// ===== Resident Search (Data Kependudukan) =====
@if($hasResidentAccess ?? false)
$('#btnSearchResident').on('click', function() {
    $('#residentSearchInput').val('');
    $('#residentSearchResults').html('<p class="text-muted text-center py-3">Ketik minimal 2 karakter untuk mencari</p>');
    $('#residentModal').modal('show');
    setTimeout(() => $('#residentSearchInput').focus(), 500);
});

function searchResidents() {
    let q = $('#residentSearchInput').val().trim();
    if (q.length < 2) {
        $('#residentSearchResults').html('<p class="text-muted text-center py-3">Ketik minimal 2 karakter untuk mencari</p>');
        return;
    }
    $('#residentSearchResults').html('<p class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Mencari...</p>');
    $.get('{{ route("admin.residents.search") }}', { q: q }, function(res) {
        if (!res.success || res.data.length === 0) {
            $('#residentSearchResults').html('<p class="text-muted text-center py-3">Tidak ditemukan</p>');
            return;
        }
        let html = '<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>NIK</th><th>Nama</th><th>JK</th><th>TTL</th><th>Alamat</th><th></th></tr></thead><tbody>';
        res.data.forEach(function(r) {
            let ttl = r.tempat_lahir || '';
            if (r.tanggal_lahir) {
                let d = new Date(r.tanggal_lahir);
                ttl += (ttl ? ', ' : '') + d.toLocaleDateString('id-ID');
            }
            let alamat = (r.alamat || '') + (r.dusun ? ' Dsn.' + r.dusun : '') + ' RT' + (r.rt||'') + '/RW' + (r.rw||'');
            html += `<tr>
                <td><code>${r.nik}</code></td>
                <td>${r.nama}</td>
                <td>${r.jenis_kelamin === 'LAKI-LAKI' ? 'L' : 'P'}</td>
                <td>${ttl}</td>
                <td>${alamat}</td>
                <td><button class="btn btn-xs btn-success btn-assign-resident" data-resident='${JSON.stringify(r)}'><i class="fas fa-check"></i> Pilih</button></td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        $('#residentSearchResults').html(html);
    }).fail(function(xhr) {
        $('#residentSearchResults').html('<p class="text-danger text-center py-3">' + (xhr.responseJSON?.message || 'Gagal mencari') + '</p>');
    });
}

$('#btnResidentSearch').on('click', searchResidents);
$('#residentSearchInput').on('keyup', function(e) {
    if (e.key === 'Enter') searchResidents();
});

let residentSearchTimer;
$('#residentSearchInput').on('input', function() {
    clearTimeout(residentSearchTimer);
    residentSearchTimer = setTimeout(searchResidents, 400);
});

$(document).on('click', '.btn-assign-resident', function() {
    let r = $(this).data('resident');

    // Close modal immediately
    $('#residentModal').modal('hide');

    $('input[name="nik"]').val(r.nik);
    $('input[name="name"]').val(r.nama);
    let genderVal = r.jenis_kelamin === 'LAKI-LAKI' ? 'male' : 'female';
    $('select[name="gender"]').val(genderVal).trigger('change');
    if (r.tanggal_lahir) {
        let d = new Date(r.tanggal_lahir);
        let dateStr = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
        $('input[name="birth_date"]').val(dateStr);
    }
    if (r.alamat || r.dusun || r.rt || r.rw || r.kelurahan) {
        let fullAddr = (r.alamat || '');
        if (r.dusun) fullAddr += (fullAddr ? ', ' : '') + 'Dusun ' + r.dusun;
        if (r.rt) fullAddr += ' RT ' + r.rt;
        if (r.rw) fullAddr += '/RW ' + r.rw;
        if (r.kelurahan) fullAddr += (fullAddr ? ', ' : '') + r.kelurahan;
        $('textarea[name="address"]').val(fullAddr);
    }

    // Helper: populate select, set value, and force Select2 to re-read
    function fillSelect(selector, data, value, placeholder) {
        let $el = $(selector);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        $el.empty().append(new Option(placeholder || '-- Pilih --', '', false, false));
        data.forEach(item => {
            $el.append(new Option(item.name, item.code, false, false));
        });
        if (value) {
            $el.val(value);
        }
        $el.select2({ theme: 'bootstrap-5' }).prop('disabled', false);
    }

    // Fill Region codes - cascade in sequence
    if (r.province_code) {
        _skipCascade = true;
        $('#province_code').val(r.province_code).trigger('change');

        $.get(`{{ url('admin/pop-settings/cities') }}/${r.province_code}`, function(cities) {
            fillSelect('#city_code', cities, r.city_code, '-- Pilih Kota --');

            if (r.city_code) {
                $.get(`{{ url('admin/pop-settings/districts') }}/${r.city_code}`, function(districts) {
                    fillSelect('#district_code', districts, r.district_code, '-- Pilih Kecamatan --');

                    if (r.district_code) {
                        $.get(`{{ url('admin/pop-settings/villages') }}/${r.district_code}`, function(villages) {
                            fillSelect('#village_code', villages, r.village_code, '-- Pilih Kelurahan --');
                            _skipCascade = false;
                        });
                    } else { _skipCascade = false; }
                });
            } else { _skipCascade = false; }
        });
    }
    toastr.success('Data penduduk berhasil di-assign ke form pelanggan');
});
@endif
</script>
@endpush
