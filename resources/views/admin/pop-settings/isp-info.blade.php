@extends('layouts.admin')

@section('title', 'Informasi ISP')

@section('page-title', 'Pengaturan Informasi ISP')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Informasi ISP</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-3">
        @include('admin.pop-settings.partials.sidebar')
    </div>
    <div class="col-lg-9">
        @if($popUsers && auth()->user()->hasRole('superadmin'))
        <div class="card card-outline card-info mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <i class="fas fa-user-shield text-info fa-lg"></i>
                        <strong class="ml-2">Mode Superadmin:</strong>
                    </div>
                    <div class="col">
                        <select class="form-control select2" id="selectPopUser">
                            <option value="">-- Pilih Admin POP --</option>
                            @foreach($popUsers as $popUser)
                                <option value="{{ $popUser->id }}" {{ $userId == $popUser->id ? 'selected' : '' }}>
                                    {{ $popUser->name }} ({{ $popUser->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <form id="ispForm" enctype="multipart/form-data">
            @csrf
            @if($userId)
            <input type="hidden" name="user_id" value="{{ $userId }}">
            @endif

            <!-- Logo Section -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-image mr-2"></i>Logo & Branding</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <label class="d-block mb-2">Logo Utama</label>
                            <div class="logo-preview mb-2" id="preview_isp_logo" style="height: 100px; display: flex; align-items: center; justify-content: center; background: #f4f6f9; border-radius: 8px;">
                                @if($popSetting->logo_url)
                                    <img src="{{ $popSetting->logo_url }}" alt="Logo" style="max-height: 80px; max-width: 100%;">
                                @else
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                @endif
                            </div>
                            <input type="file" name="isp_logo" id="isp_logo" class="d-none" accept="image/*" onchange="previewImage(this, 'preview_isp_logo')">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="$('#isp_logo').click()">
                                <i class="fas fa-upload mr-1"></i>Upload
                            </button>
                            @if($popSetting->isp_logo)
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLogo('logo')">
                                <i class="fas fa-trash"></i>
                            </button>
                            @endif
                        </div>
                        <div class="col-md-4 text-center">
                            <label class="d-block mb-2">Logo Dark Mode</label>
                            <div class="logo-preview mb-2" id="preview_isp_logo_dark" style="height: 100px; display: flex; align-items: center; justify-content: center; background: #343a40; border-radius: 8px;">
                                @if($popSetting->logo_dark_url)
                                    <img src="{{ $popSetting->logo_dark_url }}" alt="Logo Dark" style="max-height: 80px; max-width: 100%;">
                                @else
                                    <i class="fas fa-image fa-3x text-secondary"></i>
                                @endif
                            </div>
                            <input type="file" name="isp_logo_dark" id="isp_logo_dark" class="d-none" accept="image/*" onchange="previewImage(this, 'preview_isp_logo_dark')">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="$('#isp_logo_dark').click()">
                                <i class="fas fa-upload mr-1"></i>Upload
                            </button>
                            @if($popSetting->isp_logo_dark)
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLogo('logo_dark')">
                                <i class="fas fa-trash"></i>
                            </button>
                            @endif
                        </div>
                        <div class="col-md-4 text-center">
                            <label class="d-block mb-2">Favicon</label>
                            <div class="logo-preview mb-2" id="preview_isp_favicon" style="height: 100px; display: flex; align-items: center; justify-content: center; background: #f4f6f9; border-radius: 8px;">
                                @if($popSetting->favicon_url)
                                    <img src="{{ $popSetting->favicon_url }}" alt="Favicon" style="max-height: 32px;">
                                @else
                                    <i class="fas fa-globe fa-2x text-muted"></i>
                                @endif
                            </div>
                            <input type="file" name="isp_favicon" id="isp_favicon" class="d-none" accept=".ico,.png,.jpg,.jpeg" onchange="previewImage(this, 'preview_isp_favicon')">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="$('#isp_favicon').click()">
                                <i class="fas fa-upload mr-1"></i>Upload
                            </button>
                            @if($popSetting->isp_favicon)
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLogo('favicon')">
                                <i class="fas fa-trash"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- ISP Info -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-building mr-2"></i>Informasi ISP</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama ISP <span class="text-danger">*</span></label>
                                <input type="text" name="isp_name" class="form-control" value="{{ $popSetting->isp_name }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tagline</label>
                                <input type="text" name="isp_tagline" class="form-control" value="{{ $popSetting->isp_tagline }}" placeholder="Internet Cepat & Stabil">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama POP</label>
                                <input type="text" name="pop_name" class="form-control" value="{{ $popSetting->pop_name }}" placeholder="POP Pusat / POP Cabang Jakarta">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kode POP</label>
                                <input type="text" name="pop_code" class="form-control" value="{{ $popSetting->pop_code }}" placeholder="POP-001" maxlength="20">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Alamat</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Jl. Contoh No. 123, RT 001/RW 002">{{ $popSetting->address }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-map text-primary mr-1"></i>Provinsi</label>
                                <select name="province_code" id="province_code" class="form-control select2">
                                    <option value="">-- Pilih Provinsi --</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province->code }}" {{ $popSetting->province_code == $province->code ? 'selected' : '' }}>
                                            {{ $province->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-city text-info mr-1"></i>Kota/Kabupaten</label>
                                <select name="city_code" id="city_code" class="form-control select2" disabled>
                                    <option value="">Pilih Provinsi dulu...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-building text-success mr-1"></i>Kecamatan</label>
                                <select name="district_code" id="district_code" class="form-control select2" disabled>
                                    <option value="">Pilih Kota dulu...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-home text-warning mr-1"></i>Kelurahan/Desa</label>
                                <select name="village_code" id="village_code" class="form-control select2" disabled>
                                    <option value="">Pilih Kecamatan dulu...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Kode Pos</label>
                                <input type="text" name="postal_code" class="form-control" value="{{ $popSetting->postal_code }}" maxlength="10" placeholder="12345">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="number" name="latitude" class="form-control" value="{{ $popSetting->latitude }}" step="0.00000001" placeholder="-6.175392">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="number" name="longitude" class="form-control" value="{{ $popSetting->longitude }}" step="0.00000001" placeholder="106.827153">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-phone mr-2"></i>Kontak</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Telepon Utama</label>
                                <input type="text" name="phone" class="form-control" value="{{ $popSetting->phone }}" placeholder="021-12345678">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Telepon Alternatif</label>
                                <input type="text" name="phone_secondary" class="form-control" value="{{ $popSetting->phone_secondary }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>WhatsApp</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-whatsapp"></i></span>
                                    </div>
                                    <input type="text" name="whatsapp" class="form-control" value="{{ $popSetting->whatsapp }}" placeholder="081234567890">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Telegram</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-telegram"></i></span>
                                    </div>
                                    <input type="text" name="telegram" class="form-control" value="{{ $popSetting->telegram }}" placeholder="@username">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Email Umum</label>
                                <input type="email" name="email" class="form-control" value="{{ $popSetting->email }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Email Billing</label>
                                <input type="email" name="email_billing" class="form-control" value="{{ $popSetting->email_billing }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Email Support</label>
                                <input type="email" name="email_support" class="form-control" value="{{ $popSetting->email_support }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Website</label>
                                <input type="url" name="website" class="form-control" value="{{ $popSetting->website }}" placeholder="https://example.com">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Instagram</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                    </div>
                                    <input type="text" name="instagram" class="form-control" value="{{ $popSetting->instagram }}" placeholder="@username">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Facebook</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                                    </div>
                                    <input type="text" name="facebook" class="form-control" value="{{ $popSetting->facebook }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary" id="btnSave">
                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function() {
    // Select2 sudah diinisialisasi secara global di layout admin

    // POP user change handler
    $('#selectPopUser').on('change', function() {
        const userId = $(this).val();
        window.location.href = '{{ route("admin.pop-settings.isp-info") }}' + (userId ? '?user_id=' + userId : '');
    });

    // Load regions data
    const currentCity = '{{ $popSetting->city_code }}';
    const currentDistrict = '{{ $popSetting->district_code }}';
    const currentVillage = '{{ $popSetting->village_code }}';

    if ($('#province_code').val()) {
        loadCities($('#province_code').val(), currentCity);
    }

    $('#province_code').on('change', function() {
        const val = $(this).val();
        // Reset dependent dropdowns
        $('#city_code').html('<option value="">Pilih Provinsi dulu...</option>').prop('disabled', !val);
        $('#district_code').html('<option value="">Pilih Kota dulu...</option>').prop('disabled', true);
        $('#village_code').html('<option value="">Pilih Kecamatan dulu...</option>').prop('disabled', true);
        
        if (val) {
            loadCities(val);
        }
    });

    $('#city_code').on('change', function() {
        const val = $(this).val();
        // Reset dependent dropdowns
        $('#district_code').html('<option value="">Pilih Kota dulu...</option>').prop('disabled', !val);
        $('#village_code').html('<option value="">Pilih Kecamatan dulu...</option>').prop('disabled', true);
        
        if (val) {
            loadDistricts(val);
        }
    });

    $('#district_code').on('change', function() {
        const val = $(this).val();
        // Reset dependent dropdown
        $('#village_code').html('<option value="">Pilih Kecamatan dulu...</option>').prop('disabled', !val);
        
        if (val) {
            loadVillages(val);
        }
    });

    function loadCities(provinceCode, selected = null) {
        if (!provinceCode) return;
        
        // Show loading
        $('#city_code').html('<option value="">Memuat data kota...</option>').prop('disabled', true);
        
        $.get(`{{ url('admin/pop-settings/cities') }}/${provinceCode}`, function(data) {
            let html = '<option value="">-- Pilih Kota --</option>';
            data.forEach(city => {
                html += `<option value="${city.code}" ${city.code === selected ? 'selected' : ''}>${city.name}</option>`;
            });
            $('#city_code').html(html).prop('disabled', false);
            
            if (selected && currentDistrict) {
                loadDistricts(selected, currentDistrict);
            }
        }).fail(function() {
            $('#city_code').html('<option value="">Gagal memuat data</option>');
            toastr.error('Gagal memuat data kota');
        });
    }

    function loadDistricts(cityCode, selected = null) {
        if (!cityCode) return;
        
        // Show loading
        $('#district_code').html('<option value="">Memuat data kecamatan...</option>').prop('disabled', true);
        
        $.get(`{{ url('admin/pop-settings/districts') }}/${cityCode}`, function(data) {
            let html = '<option value="">-- Pilih Kecamatan --</option>';
            data.forEach(district => {
                html += `<option value="${district.code}" ${district.code === selected ? 'selected' : ''}>${district.name}</option>`;
            });
            $('#district_code').html(html).prop('disabled', false);
            
            if (selected && currentVillage) {
                loadVillages(selected, currentVillage);
            }
        }).fail(function() {
            $('#district_code').html('<option value="">Gagal memuat data</option>');
            toastr.error('Gagal memuat data kecamatan');
        });
    }

    function loadVillages(districtCode, selected = null) {
        if (!districtCode) return;
        
        // Show loading
        $('#village_code').html('<option value="">Memuat data kelurahan...</option>').prop('disabled', true);
        
        $.get(`{{ url('admin/pop-settings/villages') }}/${districtCode}`, function(data) {
            let html = '<option value="">-- Pilih Kelurahan --</option>';
            data.forEach(village => {
                html += `<option value="${village.code}" ${village.code === selected ? 'selected' : ''}>${village.name}</option>`;
            });
            $('#village_code').html(html).prop('disabled', false);
        }).fail(function() {
            $('#village_code').html('<option value="">Gagal memuat data</option>');
            toastr.error('Gagal memuat data kelurahan');
        });
    }

    // POP user selector (superadmin)
    $('#selectPopUser').on('change', function() {
        const userId = $(this).val();
        window.location.href = '{{ route("admin.pop-settings.isp-info") }}' + (userId ? '?user_id=' + userId : '');
    });

    // Form submission
    $('#ispForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const btn = $('#btnSave');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...');

        $.ajax({
            url: '{{ route("admin.pop-settings.update-isp-info") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message || 'Gagal menyimpan data');
                }
            },
            error: function(xhr) {
                console.error('Upload error:', xhr);
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        Object.keys(errors).forEach(field => {
                            toastr.error(errors[field][0]);
                        });
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'Validasi gagal');
                    }
                } else if (xhr.status === 413) {
                    toastr.error('File terlalu besar. Maksimal 2MB untuk logo dan 512KB untuk favicon.');
                } else if (xhr.status === 419) {
                    toastr.error('Session expired. Silakan refresh halaman.');
                } else {
                    toastr.error('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Simpan Perubahan');
            }
        });
    });
});

function removeLogo(type) {
    Swal.fire({
        title: 'Hapus Logo?',
        text: 'Logo akan dihapus permanen',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('{{ route("admin.pop-settings.remove-logo") }}', {
                _token: '{{ csrf_token() }}',
                type: type,
                user_id: '{{ $userId }}'
            }, function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            });
        }
    });
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const maxHeight = previewId === 'preview_isp_favicon' ? '32px' : '80px';
            preview.innerHTML = `<img src="${e.target.result}" style="max-height: ${maxHeight}; max-width: 100%;">`;
            toastr.info('File dipilih. Klik "Simpan Perubahan" untuk mengupload.');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
