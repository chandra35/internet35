@extends('layouts.pelanggan')

@section('title', 'Profil Saya')

@section('page-title', 'Profil Saya')

@section('content')
<div class="row">
    <div class="col-lg-4">
        <!-- Profile Photo Card -->
        <div class="card">
            <div class="card-body text-center">
                <div class="position-relative d-inline-block mb-3">
                    @if($customer && $customer->photo_selfie_url)
                    <img src="{{ $customer->photo_selfie_url }}" class="rounded-circle" 
                         style="width: 150px; height: 150px; object-fit: cover;" id="profilePhoto">
                    @else
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                         style="width: 150px; height: 150px;">
                        <i class="fas fa-user fa-4x text-white"></i>
                    </div>
                    @endif
                </div>
                <h4 class="mb-1">{{ $user->name }}</h4>
                <p class="text-muted mb-2">{{ $customer->customer_id ?? '-' }}</p>
                <span class="badge badge-{{ $customer->status_color ?? 'secondary' }}">
                    {{ $customer->status_label ?? 'Tidak Aktif' }}
                </span>
            </div>
        </div>

        <!-- Quick Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informasi Langganan</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    @if($customer)
                    <tr>
                        <td>Paket</td>
                        <td><strong>{{ $customer->package?->name ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Biaya</td>
                        <td>Rp {{ number_format($customer->monthly_fee, 0, ',', '.') }}/bulan</td>
                    </tr>
                    <tr>
                        <td>Aktif Sampai</td>
                        <td>
                            @if($customer->active_until)
                            <span class="{{ $customer->active_until->isPast() ? 'text-danger' : '' }}">
                                {{ $customer->active_until->format('d M Y') }}
                            </span>
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    @else
                    <tr>
                        <td colspan="2" class="text-center text-muted">Data tidak tersedia</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Profile Details -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-2"></i>Data Pribadi</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('pelanggan.profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" class="form-control" value="{{ $user->name }}" readonly disabled>
                                <small class="text-muted">Hubungi admin untuk mengubah nama</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" value="{{ $user->email }}" readonly disabled>
                                <small class="text-muted">Hubungi admin untuk mengubah email</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>No. Telepon <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone', $customer->phone ?? $user->phone) }}" required>
                                @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>No. Telepon Alternatif</label>
                                <input type="text" name="phone_alt" class="form-control" 
                                       value="{{ old('phone_alt', $customer->phone_alt ?? '') }}">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

        <!-- Address -->
        @if($customer)
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-map-marker-alt mr-2"></i>Alamat</h3>
                <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="collapse" data-target="#addressForm">
                    <i class="fas fa-edit mr-1"></i>Edit Alamat
                </button>
            </div>
            <div class="card-body">
                <!-- Current Address Display -->
                <div id="currentAddress">
                    <p class="mb-2"><strong>{{ $customer->address ?? 'Alamat belum diisi' }}</strong></p>
                    <p class="text-muted mb-0">
                        {{ $customer->village?->name }}, {{ $customer->district?->name }}<br>
                        {{ $customer->city?->name }}, {{ $customer->province?->name }} {{ $customer->postal_code }}
                    </p>
                </div>
                
                <!-- Edit Address Form -->
                <div class="collapse mt-3" id="addressForm">
                    <hr>
                    <form action="{{ route('pelanggan.profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Keep phone fields hidden but with values -->
                        <input type="hidden" name="phone" value="{{ $customer->phone ?? $user->phone }}">
                        <input type="hidden" name="phone_alt" value="{{ $customer->phone_alt ?? '' }}">
                        
                        <div class="form-group">
                            <label>Alamat Lengkap</label>
                            <textarea name="address" class="form-control" rows="2" 
                                      placeholder="Nama jalan, RT/RW, No. rumah">{{ old('address', $customer->address) }}</textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Provinsi</label>
                                    <select name="province_code" id="province_code" class="form-control select2">
                                        <option value="">Pilih Provinsi</option>
                                        @foreach($provinces as $province)
                                            <option value="{{ $province->code }}" 
                                                {{ old('province_code', $customer->province_code) == $province->code ? 'selected' : '' }}>
                                                {{ $province->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Kota/Kabupaten</label>
                                    <select name="city_code" id="city_code" class="form-control select2">
                                        <option value="">Pilih Kota</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Kecamatan</label>
                                    <select name="district_code" id="district_code" class="form-control select2">
                                        <option value="">Pilih Kecamatan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Kelurahan</label>
                                    <select name="village_code" id="village_code" class="form-control select2">
                                        <option value="">Pilih Kelurahan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Kode Pos</label>
                                    <input type="text" name="postal_code" class="form-control" 
                                           value="{{ old('postal_code', $customer->postal_code) }}" maxlength="10">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Simpan Alamat
                        </button>
                        <button type="button" class="btn btn-secondary" data-toggle="collapse" data-target="#addressForm">
                            Batal
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <!-- Account Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi Akun</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl>
                            <dt>Terdaftar Sejak</dt>
                            <dd>{{ $user->created_at->format('d M Y') }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl>
                            <dt>Login Terakhir</dt>
                            <dd>{{ $user->last_login_at?->format('d M Y H:i') ?? 'Belum pernah' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Initialize select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
    
    // Load initial data if customer has province
    @if($customer->province_code)
    loadCities('{{ $customer->province_code }}', '{{ $customer->city_code }}');
    @endif
    
    @if($customer->city_code)
    loadDistricts('{{ $customer->city_code }}', '{{ $customer->district_code }}');
    @endif
    
    @if($customer->district_code)
    loadVillages('{{ $customer->district_code }}', '{{ $customer->village_code }}');
    @endif
    
    // Province change
    $('#province_code').change(function() {
        const code = $(this).val();
        resetCityDropdown();
        resetDistrictDropdown();
        resetVillageDropdown();
        
        if (code) {
            loadCities(code);
        }
    });
    
    // City change
    $('#city_code').change(function() {
        const code = $(this).val();
        resetDistrictDropdown();
        resetVillageDropdown();
        
        if (code) {
            loadDistricts(code);
        }
    });
    
    // District change
    $('#district_code').change(function() {
        const code = $(this).val();
        resetVillageDropdown();
        
        if (code) {
            loadVillages(code);
        }
    });
});

function loadCities(provinceCode, selected = null) {
    $.get('/admin/pop-settings/cities/' + provinceCode, function(data) {
        let options = '<option value="">Pilih Kota</option>';
        data.forEach(function(item) {
            options += '<option value="' + item.code + '"' + (selected == item.code ? ' selected' : '') + '>' + item.name + '</option>';
        });
        $('#city_code').html(options).trigger('change.select2');
    });
}

function loadDistricts(cityCode, selected = null) {
    $.get('/admin/pop-settings/districts/' + cityCode, function(data) {
        let options = '<option value="">Pilih Kecamatan</option>';
        data.forEach(function(item) {
            options += '<option value="' + item.code + '"' + (selected == item.code ? ' selected' : '') + '>' + item.name + '</option>';
        });
        $('#district_code').html(options).trigger('change.select2');
    });
}

function loadVillages(districtCode, selected = null) {
    $.get('/admin/pop-settings/villages/' + districtCode, function(data) {
        let options = '<option value="">Pilih Kelurahan</option>';
        data.forEach(function(item) {
            options += '<option value="' + item.code + '"' + (selected == item.code ? ' selected' : '') + '>' + item.name + '</option>';
        });
        $('#village_code').html(options).trigger('change.select2');
    });
}

function resetCityDropdown() {
    $('#city_code').html('<option value="">Pilih Kota</option>').trigger('change.select2');
}

function resetDistrictDropdown() {
    $('#district_code').html('<option value="">Pilih Kecamatan</option>').trigger('change.select2');
}

function resetVillageDropdown() {
    $('#village_code').html('<option value="">Pilih Kelurahan</option>').trigger('change.select2');
}
</script>
@endpush
