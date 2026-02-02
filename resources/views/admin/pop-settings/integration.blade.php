@extends('layouts.admin')

@section('title', 'Pengaturan Integrasi')

@section('page-title', 'Integrasi Mikrotik & Radius')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.pop-settings.isp-info') }}">Pengaturan POP</a></li>
    <li class="breadcrumb-item active">Integrasi</li>
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

        <form id="integrationForm">
            @csrf
            @if($userId)
            <input type="hidden" name="user_id" value="{{ $userId }}">
            @endif

            <!-- POP Prefix Section -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tag mr-2"></i>Prefix POP</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="pop_prefix">Prefix untuk ID Pelanggan & Username PPPoE</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="pop_prefix" name="pop_prefix" 
                                   value="{{ $popSetting->pop_prefix }}" 
                                   placeholder="contoh: POP1, JKT, BDG"
                                   maxlength="10"
                                   pattern="[A-Za-z0-9]*"
                                   oninput="this.value = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase()">
                            <div class="input-group-append">
                                <span class="input-group-text">-123456</span>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Hanya huruf dan angka. Maksimal 10 karakter.<br>
                            <strong>Contoh ID Pelanggan:</strong> <span id="customerIdPreview">{{ $popSetting->pop_prefix ?: 'POP1' }}654321</span><br>
                            <strong>Contoh Username PPPoE:</strong> <span id="usernamePreview">{{ $popSetting->pop_prefix ?: 'POP1' }}-123456</span>
                        </small>
                    </div>
                    
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Format:</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>ID Pelanggan:</strong> <code>[PREFIX][6 digit]</code> → POP1654321</li>
                            <li><strong>Username PPPoE:</strong> <code>[PREFIX]-[username]</code> → POP1-123456 atau POP1-john@lokasi</li>
                        </ul>
                        <hr class="my-2">
                        <small><i class="fas fa-lightbulb mr-1"></i>Prefix memastikan username unik di Radius server yang dipakai bersama (centralized).</small>
                    </div>
                </div>
            </div>

            <!-- Mikrotik Sync Section -->
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-server mr-2"></i>Sinkronisasi Mikrotik</h3>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="mikrotik_sync_enabled" 
                               name="mikrotik_sync_enabled" value="1" 
                               {{ $popSetting->mikrotik_sync_enabled ? 'checked' : '' }}>
                        <label class="custom-control-label" for="mikrotik_sync_enabled">
                            <strong>Aktifkan Sinkronisasi Mikrotik</strong>
                        </label>
                    </div>
                    <p class="text-muted mb-3">
                        Jika diaktifkan, Anda bisa memilih untuk membuat PPP Secret di Mikrotik saat menambah pelanggan baru.
                    </p>
                    
                    <div id="mikrotikOptions" class="{{ $popSetting->mikrotik_sync_enabled ? '' : 'd-none' }}">
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="mikrotik_auto_sync" 
                                   name="mikrotik_auto_sync" value="1"
                                   {{ $popSetting->mikrotik_auto_sync ? 'checked' : '' }}>
                            <label class="custom-control-label" for="mikrotik_auto_sync">
                                Otomatis sync ke Mikrotik saat buat pelanggan baru (checkbox tercentang default)
                            </label>
                        </div>
                        
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Catatan:</strong> Pastikan router sudah dikonfigurasi dengan benar di menu 
                            <a href="{{ route('admin.routers.index') }}">Router Management</a>.
                            <ul class="mb-0 mt-2">
                                <li>Koneksi API ke router harus aktif</li>
                                <li>Profile PPP harus sudah dibuat di router</li>
                                <li>Package di aplikasi harus terhubung dengan profile router</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FreeRadius Section -->
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-database mr-2"></i>FreeRadius</h3>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="radius_enabled" 
                               name="radius_enabled" value="1"
                               {{ $popSetting->radius_enabled ? 'checked' : '' }}>
                        <label class="custom-control-label" for="radius_enabled">
                            <strong>Aktifkan FreeRadius</strong>
                        </label>
                    </div>
                    <p class="text-muted mb-3">
                        Jika diaktifkan, aplikasi akan membuat user di database FreeRadius saat menambah pelanggan.
                    </p>
                    
                    <div id="radiusOptions" class="{{ $popSetting->radius_enabled ? '' : 'd-none' }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="radius_host">Database Host <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="radius_host" name="radius_host"
                                           value="{{ $popSetting->radius_host }}" placeholder="localhost atau IP">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="radius_port">Port</label>
                                    <input type="number" class="form-control" id="radius_port" name="radius_port"
                                           value="{{ $popSetting->radius_port ?? 3306 }}" placeholder="3306">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="radius_database">Nama Database</label>
                                    <input type="text" class="form-control" id="radius_database" name="radius_database"
                                           value="{{ $popSetting->radius_database ?? 'radius' }}" placeholder="radius">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="radius_username">Username Database <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="radius_username" name="radius_username"
                                           value="{{ $popSetting->radius_username }}" placeholder="radius">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="radius_password">Password Database</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="radius_password" name="radius_password"
                                       placeholder="{{ $popSetting->radius_password ? '••••••••' : 'Masukkan password' }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('radius_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3"><i class="fas fa-cog mr-2"></i>Pengaturan NAS (Opsional)</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="radius_nas_ip">NAS IP Address</label>
                                    <input type="text" class="form-control" id="radius_nas_ip" name="radius_nas_ip"
                                           value="{{ $popSetting->radius_nas_ip }}" placeholder="IP address NAS/Mikrotik">
                                    <small class="text-muted">IP address router/NAS untuk Radius</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="radius_coa_port">CoA Port</label>
                                    <input type="number" class="form-control" id="radius_coa_port" name="radius_coa_port"
                                           value="{{ $popSetting->radius_coa_port ?? 3799 }}" placeholder="3799">
                                    <small class="text-muted">Port untuk Change of Authorization</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="radius_nas_secret">NAS Secret</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="radius_nas_secret" name="radius_nas_secret"
                                       placeholder="{{ $popSetting->radius_nas_secret ? '••••••••' : 'Masukkan secret' }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('radius_nas_secret')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Shared secret antara NAS dan Radius server</small>
                        </div>
                        
                        <hr>
                        
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="radius_auto_sync" 
                                   name="radius_auto_sync" value="1"
                                   {{ $popSetting->radius_auto_sync ? 'checked' : '' }}>
                            <label class="custom-control-label" for="radius_auto_sync">
                                Otomatis sync ke Radius saat buat pelanggan baru (checkbox tercentang default)
                            </label>
                        </div>
                        
                        <button type="button" class="btn btn-info" id="testRadiusBtn" onclick="testRadiusConnection()">
                            <i class="fas fa-plug mr-2"></i>Test Koneksi
                        </button>
                        <span id="radiusTestResult" class="ml-3"></span>
                    </div>
                </div>
            </div>

            <!-- Hybrid Mode Info -->
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Mode Hybrid</h3>
                </div>
                <div class="card-body">
                    <p>
                        Aplikasi ini mendukung <strong>mode hybrid</strong>, di mana Anda dapat memilih:
                    </p>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light mb-0">
                                <div class="card-body text-center">
                                    <i class="fas fa-clipboard-list fa-2x text-secondary mb-2"></i>
                                    <h6>Record Only</h6>
                                    <small class="text-muted">Hanya simpan data di aplikasi, tidak sync ke mana-mana</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light mb-0">
                                <div class="card-body text-center">
                                    <i class="fas fa-server fa-2x text-info mb-2"></i>
                                    <h6>Mikrotik PPP Secret</h6>
                                    <small class="text-muted">Buat PPP Secret langsung di router (standalone)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light mb-0">
                                <div class="card-body text-center">
                                    <i class="fas fa-database fa-2x text-success mb-2"></i>
                                    <h6>FreeRadius</h6>
                                    <small class="text-muted">Buat user di Radius untuk autentikasi terpusat</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="mb-0 text-muted">
                            <i class="fas fa-lightbulb mr-1"></i>
                            Anda bisa mengaktifkan keduanya (Mikrotik + Radius) jika router Anda menggunakan Radius untuk auth
                            tetapi Anda juga ingin backup manual PPP Secret.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="card">
                <div class="card-body text-right">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save mr-2"></i>Simpan Pengaturan
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
    // Update preview when prefix changes
    function updatePrefixPreview() {
        const prefix = $('#pop_prefix').val() || 'POP1';
        $('#customerIdPreview').text(prefix + '654321');
        $('#usernamePreview').text(prefix + '-123456');
    }
    
    // Listen to prefix input
    $('#pop_prefix').on('input', function() {
        updatePrefixPreview();
    });
    
    // Initial preview
    updatePrefixPreview();
    
    // Toggle Mikrotik options
    $('#mikrotik_sync_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('#mikrotikOptions').removeClass('d-none');
        } else {
            $('#mikrotikOptions').addClass('d-none');
        }
    });
    
    // Toggle Radius options
    $('#radius_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('#radiusOptions').removeClass('d-none');
        } else {
            $('#radiusOptions').addClass('d-none');
        }
    });
    
    // Select POP user (superadmin)
    $('#selectPopUser').change(function() {
        const userId = $(this).val();
        if (userId) {
            window.location.href = '{{ route("admin.pop-settings.integration") }}?user_id=' + userId;
        }
    });
    
    // Form submission
    $('#integrationForm').submit(function(e) {
        e.preventDefault();
        
        // Client-side validation for prefix
        const prefix = $('#pop_prefix').val();
        if (prefix && !/^[A-Za-z0-9]*$/.test(prefix)) {
            toastr.error('Prefix hanya boleh berisi huruf dan angka (tanpa spasi, @, titik, atau karakter spesial)');
            $('#pop_prefix').focus().addClass('is-invalid');
            return false;
        }
        $('#pop_prefix').removeClass('is-invalid');
        
        const btn = $(this).find('button[type="submit"]');
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.pop-settings.update-integration") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message || 'Gagal menyimpan pengaturan');
                }
            },
            error: function(xhr) {
                console.log('Error response:', xhr.responseJSON); // Debug
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    // Clear previous invalid states
                    $('.is-invalid').removeClass('is-invalid');
                    
                    // Show each error with field highlighting
                    Object.keys(errors).forEach(function(field) {
                        const fieldEl = $('[name="' + field + '"]');
                        if (fieldEl.length) {
                            fieldEl.addClass('is-invalid');
                        }
                        errors[field].forEach(function(msg) {
                            toastr.error(msg);
                        });
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan');
                }
            },
            complete: function() {
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
});

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function testRadiusConnection() {
    const btn = $('#testRadiusBtn');
    const result = $('#radiusTestResult');
    const originalHtml = btn.html();
    
    btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Testing...').prop('disabled', true);
    result.html('');
    
    $.ajax({
        url: '{{ route("admin.pop-settings.test-radius") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            radius_host: $('#radius_host').val(),
            radius_port: $('#radius_port').val(),
            radius_database: $('#radius_database').val(),
            radius_username: $('#radius_username').val(),
            radius_password: $('#radius_password').val() || 'existing',
        },
        success: function(res) {
            if (res.success) {
                result.html('<span class="text-success"><i class="fas fa-check-circle mr-1"></i>' + res.message + '</span>');
            } else {
                result.html('<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>' + res.message + '</span>');
            }
        },
        error: function(xhr) {
            result.html('<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>' + (xhr.responseJSON?.message || 'Test gagal') + '</span>');
        },
        complete: function() {
            btn.html(originalHtml).prop('disabled', false);
        }
    });
}
</script>
@endpush
