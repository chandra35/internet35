@extends('layouts.admin')

@section('title', 'Pengaturan Situs')
@section('page-title', 'Pengaturan Situs')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Pengaturan</li>
@endsection

@section('content')
    <form id="settingsForm" action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="row">
            @foreach($settings as $group => $items)
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            @switch($group)
                                @case('general')
                                    <i class="fas fa-cog mr-1"></i> Umum
                                    @break
                                @case('contact')
                                    <i class="fas fa-address-book mr-1"></i> Kontak
                                    @break
                                @case('social')
                                    <i class="fas fa-share-alt mr-1"></i> Media Sosial
                                    @break
                                @case('seo')
                                    <i class="fas fa-search mr-1"></i> SEO
                                    @break
                                @case('appearance')
                                    <i class="fas fa-palette mr-1"></i> Tampilan
                                    @break
                                @default
                                    <i class="fas fa-sliders-h mr-1"></i> {{ ucfirst($group) }}
                            @endswitch
                        </h3>
                    </div>
                    <div class="card-body">
                        @foreach($items as $setting)
                            @php $fieldName = str_replace('.', '_', $setting->key); @endphp
                            <div class="form-group">
                                <label for="{{ $fieldName }}">{{ $setting->label }}</label>
                                
                                @switch($setting->type)
                                    @case('text')
                                        <input type="text" class="form-control" id="{{ $fieldName }}" name="{{ $fieldName }}" value="{{ $setting->value }}">
                                        @break
                                    
                                    @case('email')
                                        <input type="email" class="form-control" id="{{ $fieldName }}" name="{{ $fieldName }}" value="{{ $setting->value }}">
                                        @break
                                    
                                    @case('url')
                                        <input type="url" class="form-control" id="{{ $fieldName }}" name="{{ $fieldName }}" value="{{ $setting->value }}" placeholder="https://...">
                                        @break
                                    
                                    @case('number')
                                        <input type="number" class="form-control" id="{{ $fieldName }}" name="{{ $fieldName }}" value="{{ $setting->value }}">
                                        @break
                                    
                                    @case('textarea')
                                        <textarea class="form-control" id="{{ $fieldName }}" name="{{ $fieldName }}" rows="3">{{ $setting->value }}</textarea>
                                        @break
                                    
                                    @case('select')
                                        <select class="form-control select2" id="{{ $fieldName }}" name="{{ $fieldName }}">
                                            @foreach($setting->options ?? [] as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}" {{ $setting->value == $optionValue ? 'selected' : '' }}>{{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                        @break
                                    
                                    @case('image')
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="{{ $fieldName }}" name="{{ $fieldName }}" accept="image/*">
                                            <label class="custom-file-label" for="{{ $fieldName }}">Pilih file...</label>
                                        </div>
                                        @if($setting->value)
                                            <div class="mt-2">
                                                <img src="{{ asset('storage/settings/' . $setting->value) }}" class="img-thumbnail" style="max-height:80px;">
                                            </div>
                                        @endif
                                        @break
                                    
                                    @case('color')
                                        <input type="color" class="form-control" id="{{ $fieldName }}" name="{{ $fieldName }}" value="{{ $setting->value }}" style="height:38px;">
                                        @break
                                    
                                    @case('boolean')
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="{{ $fieldName }}" name="{{ $fieldName }}" value="1" {{ $setting->value ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="{{ $fieldName }}">Aktif</label>
                                        </div>
                                        @break
                                    
                                    @default
                                        <input type="text" class="form-control" id="{{ $fieldName }}" name="{{ $fieldName }}" value="{{ $setting->value }}">
                                @endswitch
                                
                                @if($setting->description)
                                    <small class="text-muted">{{ $setting->description }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save mr-1"></i> Simpan Pengaturan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('js')
<script>
    $(document).ready(function() {
        bsCustomFileInput.init();

        $('#settingsForm').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = form.find('#submitBtn');
            const originalBtnText = submitBtn.html();
            const formData = new FormData(this);
            
            // Disable button and show loading
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
            
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    submitBtn.prop('disabled', false).html(originalBtnText);
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(originalBtnText);
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let errorMsg = '<ul class="text-left mb-0">';
                        Object.keys(errors).forEach(key => {
                            errors[key].forEach(msg => {
                                errorMsg += '<li>' + msg + '</li>';
                            });
                        });
                        errorMsg += '</ul>';
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            html: errorMsg
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Terjadi kesalahan saat menyimpan data'
                        });
                    }
                }
            });
        });
    });
</script>
@endpush
