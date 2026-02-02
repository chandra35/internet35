@extends('layouts.admin')

@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Profil</li>
@endsection

@push('css')
<style>
    .profile-avatar-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto;
    }
    .profile-avatar {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #007bff;
    }
    .avatar-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
        cursor: pointer;
    }
    .profile-avatar-container:hover .avatar-overlay {
        opacity: 1;
    }
    .avatar-overlay i {
        color: white;
        font-size: 2rem;
    }
    .cropper-container {
        max-height: 400px;
    }
</style>
@endpush

@section('content')
<div class="row">
    <!-- Profile Info Card -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center mb-3">
                    <div class="profile-avatar-container">
                        <img id="avatarPreview" class="profile-avatar" 
                             src="{{ $user->avatar_url }}" 
                             alt="Avatar">
                        <div class="avatar-overlay" onclick="$('#avatarInput').click()">
                            <i class="fas fa-camera"></i>
                        </div>
                        <input type="file" id="avatarInput" accept="image/*" style="display:none;">
                    </div>
                </div>

                <h3 class="profile-username text-center">{{ $user->name }}</h3>
                <p class="text-muted text-center">{{ $user->email }}</p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Role</b> 
                        <span class="float-right">
                            @foreach($user->roles as $role)
                                <span class="badge badge-primary">{{ $role->name }}</span>
                            @endforeach
                        </span>
                    </li>
                    <li class="list-group-item">
                        <b>Bergabung</b> 
                        <span class="float-right">{{ $user->created_at->format('d M Y') }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Login Terakhir</b> 
                        <span class="float-right">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : '-' }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Update Profile Form -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-user mr-2"></i>Update Profil</h5>
            </div>
            <form id="profileForm">
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ $user->name }}" required>
                        <span class="invalid-feedback"></span>
                    </div>
                    <div class="form-group">
                        <label for="email">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" required>
                        <span class="invalid-feedback"></span>
                    </div>
                    <div class="form-group">
                        <label for="phone">No. Telepon</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="{{ $user->phone }}">
                        <span class="invalid-feedback"></span>
                    </div>
                    <div class="form-group">
                        <label for="address">Alamat</label>
                        <textarea class="form-control" id="address" name="address" rows="3">{{ $user->address }}</textarea>
                        <span class="invalid-feedback"></span>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password Form -->
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-key mr-2"></i>Ganti Password</h5>
            </div>
            <form id="passwordForm">
                <div class="card-body">
                    <div class="form-group">
                        <label for="current_password">Password Saat Ini <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <span class="invalid-feedback"></span>
                    </div>
                    <div class="form-group">
                        <label for="password">Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <span class="invalid-feedback"></span>
                        <small class="text-muted">Minimal 8 karakter</small>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password_confirmation">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <span class="invalid-feedback"></span>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key mr-1"></i> Ganti Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Avatar Crop Modal -->
<div class="modal fade" id="avatarModal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crop Avatar</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="img-container">
                    <img id="cropperImage" src="" style="max-width:100%;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnCropSave">
                    <i class="fas fa-crop mr-1"></i> Crop & Simpan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    let cropper = null;

    $(document).ready(function() {
        // Avatar upload
        $('#avatarInput').on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Validate file type
            if (!file.type.match('image.*')) {
                toastr.error('File harus berupa gambar!');
                return;
            }

            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                toastr.error('Ukuran file maksimal 5MB!');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                $('#cropperImage').attr('src', e.target.result);
                $('#avatarModal').modal('show');
            };
            reader.readAsDataURL(file);
        });

        // Initialize cropper when modal opens
        $('#avatarModal').on('shown.bs.modal', function() {
            if (cropper) cropper.destroy();
            
            cropper = new Cropper(document.getElementById('cropperImage'), {
                aspectRatio: 1,
                viewMode: 2,
                preview: '.profile-avatar',
                autoCropArea: 1,
                responsive: true
            });
        });

        // Destroy cropper when modal closes
        $('#avatarModal').on('hidden.bs.modal', function() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            $('#avatarInput').val('');
        });

        // Crop and save avatar
        $('#btnCropSave').on('click', function() {
            if (!cropper) return;

            const btn = $(this);
            btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...').prop('disabled', true);

            const canvas = cropper.getCroppedCanvas({
                width: 300,
                height: 300,
                fillColor: '#fff'
            });

            const base64 = canvas.toDataURL('image/jpeg', 0.9);

            $.post('{{ route('admin.profile.avatar') }}', {
                avatar: base64
            }, function(response) {
                btn.html('<i class="fas fa-crop mr-1"></i> Crop & Simpan').prop('disabled', false);
                $('#avatarModal').modal('hide');
                
                if (response.success) {
                    $('#avatarPreview').attr('src', response.avatar_url);
                    toastr.success(response.message);
                }
            }).fail(function(xhr) {
                btn.html('<i class="fas fa-crop mr-1"></i> Crop & Simpan').prop('disabled', false);
                toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan!');
            });
        });

        // Profile form submission
        $('#profileForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const btn = form.find('button[type="submit"]');
            const originalText = btn.html();

            btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...').prop('disabled', true);
            form.find('.is-invalid').removeClass('is-invalid');

            $.post('{{ route('admin.profile.update') }}', form.serialize(), function(response) {
                btn.html(originalText).prop('disabled', false);
                
                if (response.success) {
                    toastr.success(response.message);
                }
            }).fail(function(xhr) {
                btn.html(originalText).prop('disabled', false);
                
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, messages) {
                        const input = form.find(`[name="${key}"]`);
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan!');
                }
            });
        });

        // Password form submission
        $('#passwordForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const btn = form.find('button[type="submit"]');
            const originalText = btn.html();

            btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...').prop('disabled', true);
            form.find('.is-invalid').removeClass('is-invalid');

            $.post('{{ route('admin.profile.password') }}', form.serialize(), function(response) {
                btn.html(originalText).prop('disabled', false);
                
                if (response.success) {
                    toastr.success(response.message);
                    form[0].reset();
                }
            }).fail(function(xhr) {
                btn.html(originalText).prop('disabled', false);
                
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, messages) {
                        const input = form.find(`[name="${key}"]`);
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan!');
                }
            });
        });

        // Toggle password visibility
        $('.toggle-password').on('click', function() {
            const target = $(this).data('target');
            const input = $(`#${target}`);
            const icon = $(this).find('i');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    });
</script>
@endpush
