@extends('layouts.admin')

@section('title', 'Edit Pelanggan')

@section('page-title', 'Edit Pelanggan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Pelanggan</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.customers.show', $customer) }}">{{ $customer->customer_id }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
<style>
    .nav-tabs-custom { border-bottom: 2px solid #007bff; }
    .nav-tabs-custom .nav-link { border: none; color: #6c757d; padding: 10px 20px; }
    .nav-tabs-custom .nav-link.active { color: #007bff; border-bottom: 2px solid #007bff; margin-bottom: -2px; background: transparent; }
    #map { height: 300px; border-radius: 8px; }
    .photo-upload-box { 
        width: 100%; 
        padding-top: 75%; 
        position: relative;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        background: #f8f9fa;
        cursor: pointer;
        overflow: hidden;
    }
    .photo-upload-box.has-image { border-style: solid; border-color: #28a745; }
    .photo-upload-box-inner {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .photo-upload-box img { width: 100%; height: 100%; object-fit: cover; }
    .photo-upload-box .btn-remove {
        position: absolute;
        top: 5px;
        right: 5px;
        z-index: 10;
    }
    #cropperImage { max-width: 100%; }
    #cameraPreview { width: 100%; max-height: 400px; object-fit: cover; border-radius: 8px; }
</style>
@endpush

@section('content')
<form id="customerForm" action="{{ route('admin.customers.update', $customer) }}" method="POST">
    @csrf
    @method('PUT')
    <input type="hidden" name="photo_ktp" id="photo_ktp">
    <input type="hidden" name="photo_selfie" id="photo_selfie">
    <input type="hidden" name="photo_house" id="photo_house">

    <div class="card">
        <div class="card-header p-0 border-bottom-0">
            <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tab-info">
                        <i class="fas fa-user mr-1"></i> Informasi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-address">
                        <i class="fas fa-map-marker-alt mr-1"></i> Alamat
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-service">
                        <i class="fas fa-wifi mr-1"></i> Layanan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-photos">
                        <i class="fas fa-images mr-1"></i> Foto
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Tab Info -->
                <div class="tab-pane fade show active" id="tab-info">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ID Pelanggan</label>
                                <input type="text" class="form-control" value="{{ $customer->customer_id }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <input type="text" class="form-control" value="{{ $customer->status_label }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name', $customer->name) }}" required>
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>NIK</label>
                                <input type="text" name="nik" class="form-control @error('nik') is-invalid @enderror" 
                                       value="{{ old('nik', $customer->nik) }}" maxlength="16">
                                @error('nik')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>No. Telepon <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone', $customer->phone) }}" required>
                                @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>No. Telepon Alternatif</label>
                                <input type="text" name="phone_alt" class="form-control" 
                                       value="{{ old('phone_alt', $customer->phone_alt) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                       value="{{ old('email', $customer->email) }}">
                                @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tanggal Lahir</label>
                                <input type="date" name="birth_date" class="form-control" 
                                       value="{{ old('birth_date', $customer->birth_date?->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <select name="gender" class="form-control select2">
                                    <option value="">-- Pilih --</option>
                                    <option value="male" {{ old('gender', $customer->gender) === 'male' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="female" {{ old('gender', $customer->gender) === 'female' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Catatan</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $customer->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Address -->
                <div class="tab-pane fade" id="tab-address">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Provinsi</label>
                                <select name="province_code" id="province_code" class="form-control select2">
                                    <option value="">-- Pilih Provinsi --</option>
                                    @foreach($provinces as $province)
                                    <option value="{{ $province->code }}" {{ old('province_code', $customer->province_code) == $province->code ? 'selected' : '' }}>
                                        {{ $province->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kabupaten/Kota</label>
                                <select name="city_code" id="city_code" class="form-control select2">
                                    <option value="">-- Pilih Kota --</option>
                                    @foreach($cities as $city)
                                    <option value="{{ $city->code }}" {{ old('city_code', $customer->city_code) == $city->code ? 'selected' : '' }}>
                                        {{ $city->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kecamatan</label>
                                <select name="district_code" id="district_code" class="form-control select2">
                                    <option value="">-- Pilih Kecamatan --</option>
                                    @foreach($districts as $district)
                                    <option value="{{ $district->code }}" {{ old('district_code', $customer->district_code) == $district->code ? 'selected' : '' }}>
                                        {{ $district->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kelurahan/Desa</label>
                                <select name="village_code" id="village_code" class="form-control select2">
                                    <option value="">-- Pilih Kelurahan --</option>
                                    @foreach($villages as $village)
                                    <option value="{{ $village->code }}" {{ old('village_code', $customer->village_code) == $village->code ? 'selected' : '' }}>
                                        {{ $village->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="form-group">
                                <label>Alamat Lengkap</label>
                                <textarea name="address" class="form-control @error('address') is-invalid @enderror" 
                                          rows="2">{{ old('address', $customer->address) }}</textarea>
                                @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Kode Pos</label>
                                <input type="text" name="postal_code" class="form-control" 
                                       value="{{ old('postal_code', $customer->postal_code) }}" maxlength="5">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Lokasi di Peta</label>
                                <div id="map"></div>
                                <small class="text-muted">Klik pada peta untuk menandai lokasi</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="text" name="latitude" id="latitude" class="form-control" 
                                       value="{{ old('latitude', $customer->latitude) }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="text" name="longitude" id="longitude" class="form-control" 
                                       value="{{ old('longitude', $customer->longitude) }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Service -->
                <div class="tab-pane fade" id="tab-service">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Router <span class="text-danger">*</span></label>
                                <select name="router_id" id="router_id" class="form-control select2" required>
                                    <option value="">-- Pilih Router --</option>
                                    @foreach($routers as $router)
                                    <option value="{{ $router->id }}" {{ old('router_id', $customer->router_id) == $router->id ? 'selected' : '' }}>
                                        {{ $router->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Paket Layanan <span class="text-danger">*</span></label>
                                <select name="package_id" id="package_id" class="form-control select2" required>
                                    <option value="">-- Pilih Paket --</option>
                                    @foreach($packages as $package)
                                    <option value="{{ $package->id }}" data-price="{{ $package->price }}"
                                            {{ old('package_id', $customer->package_id) == $package->id ? 'selected' : '' }}>
                                        {{ $package->name }} - Rp {{ number_format($package->price, 0, ',', '.') }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Username PPPoE</label>
                                <input type="text" name="pppoe_username" class="form-control @error('pppoe_username') is-invalid @enderror" 
                                       value="{{ old('pppoe_username', $customer->pppoe_username) }}">
                                @error('pppoe_username')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password PPPoE <small class="text-muted">(Kosongkan jika tidak diubah)</small></label>
                                <div class="input-group">
                                    <input type="password" name="pppoe_password" id="pppoe_password" class="form-control">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="btnTogglePwd">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="btnGeneratePwd">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipe Layanan</label>
                                <select name="service_type" class="form-control select2">
                                    @foreach(\App\Models\Customer::serviceTypes() as $key => $val)
                                    <option value="{{ $key }}" {{ old('service_type', $customer->service_type) === $key ? 'selected' : '' }}>
                                        {{ $val }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>IP Address (Remote)</label>
                                <input type="text" name="remote_address" class="form-control" 
                                       value="{{ old('remote_address', $customer->remote_address) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>MAC Address</label>
                                <input type="text" name="mac_address" class="form-control" 
                                       value="{{ old('mac_address', $customer->mac_address) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Biaya Bulanan</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                    <input type="number" name="monthly_fee" id="monthly_fee" class="form-control" 
                                           value="{{ old('monthly_fee', $customer->monthly_fee) }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tanggal Jatuh Tempo</label>
                                <select name="billing_day" class="form-control select2">
                                    @for($i = 1; $i <= 28; $i++)
                                    <option value="{{ $i }}" {{ old('billing_day', $customer->billing_day) == $i ? 'selected' : '' }}>
                                        Tanggal {{ $i }}
                                    </option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Aktif Sampai</label>
                                <input type="date" name="active_until" class="form-control" 
                                       value="{{ old('active_until', $customer->active_until?->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Catatan Internal</label>
                                <textarea name="internal_notes" class="form-control" rows="2">{{ old('internal_notes', $customer->internal_notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Photos -->
                <div class="tab-pane fade" id="tab-photos">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Foto KTP</label>
                                <div class="photo-upload-box {{ $customer->photo_ktp_url ? 'has-image' : '' }}" data-target="ktp">
                                    <div class="photo-upload-box-inner">
                                        @if($customer->photo_ktp_url)
                                        <img src="{{ $customer->photo_ktp_url }}" id="preview_ktp">
                                        <button type="button" class="btn btn-sm btn-danger btn-remove" onclick="removePhoto('ktp', event)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        @else
                                        <i class="fas fa-id-card fa-3x text-muted"></i>
                                        <small class="text-muted mt-2">Klik untuk upload</small>
                                        @endif
                                    </div>
                                </div>
                                <input type="file" id="file_ktp" class="d-none" accept="image/*">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Foto Selfie</label>
                                <div class="photo-upload-box {{ $customer->photo_selfie_url ? 'has-image' : '' }}" data-target="selfie">
                                    <div class="photo-upload-box-inner">
                                        @if($customer->photo_selfie_url)
                                        <img src="{{ $customer->photo_selfie_url }}" id="preview_selfie">
                                        <button type="button" class="btn btn-sm btn-danger btn-remove" onclick="removePhoto('selfie', event)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        @else
                                        <i class="fas fa-user fa-3x text-muted"></i>
                                        <small class="text-muted mt-2">Klik untuk upload</small>
                                        @endif
                                    </div>
                                </div>
                                <input type="file" id="file_selfie" class="d-none" accept="image/*">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Foto Rumah <small class="text-muted">(Opsional)</small></label>
                                <div class="photo-upload-box {{ $customer->photo_house_url ? 'has-image' : '' }}" data-target="house">
                                    <div class="photo-upload-box-inner">
                                        @if($customer->photo_house_url)
                                        <img src="{{ $customer->photo_house_url }}" id="preview_house">
                                        <button type="button" class="btn btn-sm btn-danger btn-remove" onclick="removePhoto('house', event)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        @else
                                        <i class="fas fa-home fa-3x text-muted"></i>
                                        <small class="text-muted mt-2">Klik untuk upload</small>
                                        @endif
                                    </div>
                                </div>
                                <input type="file" id="file_house" class="d-none" accept="image/*">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary" id="btnOpenCamera">
                                    <i class="fas fa-camera mr-1"></i> Ambil Foto dari Kamera
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <div class="row">
                <div class="col">
                    <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
                <div class="col text-right">
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
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
                <h5 class="modal-title">Ambil Foto</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <video id="cameraPreview" autoplay playsinline></video>
                <canvas id="cameraCanvas" class="d-none"></canvas>
            </div>
            <div class="modal-footer">
                <select id="cameraTarget" class="form-control mr-auto" style="width: auto;">
                    <option value="ktp">Untuk: Foto KTP</option>
                    <option value="selfie">Untuk: Foto Selfie</option>
                    <option value="house">Untuk: Foto Rumah</option>
                </select>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnCapture">
                    <i class="fas fa-camera mr-1"></i> Ambil
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
                <h5 class="modal-title">Crop Gambar</h5>
            </div>
            <div class="modal-body">
                <img id="cropperImage" src="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnCropCancel">Batal</button>
                <button type="button" class="btn btn-primary" id="btnCropApply">
                    <i class="fas fa-crop mr-1"></i> Terapkan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
$(function() {
    // Map
    const defaultLat = {{ $customer->latitude ?? -6.2088 }};
    const defaultLng = {{ $customer->longitude ?? 106.8456 }};
    const map = L.map('map').setView([defaultLat, defaultLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);
    
    let marker = null;
    @if($customer->latitude && $customer->longitude)
    marker = L.marker([defaultLat, defaultLng]).addTo(map);
    @endif

    map.on('click', function(e) {
        const lat = e.latlng.lat.toFixed(8);
        const lng = e.latlng.lng.toFixed(8);
        
        if (marker) map.removeLayer(marker);
        marker = L.marker([lat, lng]).addTo(map);
        
        $('#latitude').val(lat);
        $('#longitude').val(lng);
    });

    // Fix map display issue when tab is shown
    $('a[href="#tab-address"]').on('shown.bs.tab', function() {
        map.invalidateSize();
    });

    // Region cascade
    const loadRegion = (url, targetId, callback) => {
        $.get(url, function(data) {
            const $target = $(targetId);
            $target.html('<option value="">-- Pilih --</option>');
            data.forEach(item => {
                $target.append(`<option value="${item.code}">${item.name}</option>`);
            });
            if (callback) callback();
        });
    };

    $('#province_code').on('change', function() {
        const code = $(this).val();
        if (code) {
            loadRegion(`/api/wilayah/cities/${code}`, '#city_code');
            $('#district_code, #village_code').html('<option value="">-- Pilih --</option>');
        }
    });

    $('#city_code').on('change', function() {
        const code = $(this).val();
        if (code) {
            loadRegion(`/api/wilayah/districts/${code}`, '#district_code');
            $('#village_code').html('<option value="">-- Pilih --</option>');
        }
    });

    $('#district_code').on('change', function() {
        const code = $(this).val();
        if (code) {
            loadRegion(`/api/wilayah/villages/${code}`, '#village_code');
        }
    });

    // Load packages by router
    $('#router_id').on('change', function() {
        const id = $(this).val();
        if (id) {
            $.get(`/api/routers/${id}/packages`, function(data) {
                const $pkg = $('#package_id');
                $pkg.html('<option value="">-- Pilih Paket --</option>');
                data.forEach(pkg => {
                    $pkg.append(`<option value="${pkg.id}" data-price="${pkg.price}">${pkg.name} - Rp ${Number(pkg.price).toLocaleString('id')}</option>`);
                });
            });
        }
    });

    $('#package_id').on('change', function() {
        const price = $(this).find(':selected').data('price');
        if (price) $('#monthly_fee').val(price);
    });

    // Password toggle & generate
    $('#btnTogglePwd').on('click', function() {
        const $pwd = $('#pppoe_password');
        const type = $pwd.attr('type') === 'password' ? 'text' : 'password';
        $pwd.attr('type', type);
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    $('#btnGeneratePwd').on('click', function() {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let pwd = '';
        for (let i = 0; i < 8; i++) pwd += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#pppoe_password').val(pwd).attr('type', 'text');
    });

    // Photo handling
    let cropper = null;
    let currentTarget = null;
    
    // Track which photos are changed
    const photoChanged = { ktp: false, selfie: false, house: false };

    $('.photo-upload-box').on('click', function() {
        const target = $(this).data('target');
        $(`#file_${target}`).click();
    });

    $('input[type="file"]').on('change', function() {
        const target = $(this).attr('id').replace('file_', '');
        const file = this.files[0];
        if (file) {
            currentTarget = target;
            const reader = new FileReader();
            reader.onload = (e) => {
                $('#cropperImage').attr('src', e.target.result);
                $('#cropperModal').modal('show');
            };
            reader.readAsDataURL(file);
        }
    });

    $('#cropperModal').on('shown.bs.modal', function() {
        cropper = new Cropper(document.getElementById('cropperImage'), {
            aspectRatio: currentTarget === 'ktp' ? 85.6/53.98 : (currentTarget === 'selfie' ? 1 : 4/3),
            viewMode: 1,
        });
    }).on('hidden.bs.modal', function() {
        if (cropper) { cropper.destroy(); cropper = null; }
    });

    $('#btnCropApply').on('click', function() {
        if (cropper) {
            const canvas = cropper.getCroppedCanvas({ maxWidth: 1024, maxHeight: 1024 });
            const base64 = canvas.toDataURL('image/jpeg', 0.8);
            $(`#photo_${currentTarget}`).val(base64);
            
            const $box = $(`.photo-upload-box[data-target="${currentTarget}"]`);
            $box.addClass('has-image');
            $box.find('.photo-upload-box-inner').html(`
                <img src="${base64}" id="preview_${currentTarget}">
                <button type="button" class="btn btn-sm btn-danger btn-remove" onclick="removePhoto('${currentTarget}', event)">
                    <i class="fas fa-times"></i>
                </button>
            `);
            
            photoChanged[currentTarget] = true;
            $('#cropperModal').modal('hide');
        }
    });

    $('#btnCropCancel').on('click', function() {
        $('#cropperModal').modal('hide');
    });

    // Camera
    let stream = null;

    $('#btnOpenCamera').on('click', function() {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(s => {
                stream = s;
                document.getElementById('cameraPreview').srcObject = s;
                $('#cameraModal').modal('show');
            })
            .catch(err => {
                toastr.error('Tidak dapat mengakses kamera: ' + err.message);
            });
    });

    $('#cameraModal').on('hidden.bs.modal', function() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
    });

    $('#btnCapture').on('click', function() {
        const video = document.getElementById('cameraPreview');
        const canvas = document.getElementById('cameraCanvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        const target = $('#cameraTarget').val();
        currentTarget = target;
        
        $('#cameraModal').modal('hide');
        $('#cropperImage').attr('src', canvas.toDataURL('image/jpeg'));
        $('#cropperModal').modal('show');
    });

    // Form submit
    $('#customerForm').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#btnSubmit');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    window.location.href = response.redirect || '{{ route("admin.customers.show", $customer) }}';
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(err => toastr.error(err[0]));
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                }
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Perubahan');
            }
        });
    });
});

function removePhoto(target, event) {
    event.stopPropagation();
    $(`#photo_${target}`).val('removed');
    const $box = $(`.photo-upload-box[data-target="${target}"]`);
    $box.removeClass('has-image');
    const icons = { ktp: 'fa-id-card', selfie: 'fa-user', house: 'fa-home' };
    $box.find('.photo-upload-box-inner').html(`
        <i class="fas ${icons[target]} fa-3x text-muted"></i>
        <small class="text-muted mt-2">Klik untuk upload</small>
    `);
}
</script>
@endpush
