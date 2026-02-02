@extends('layouts.admin')

@section('title', 'Edit Staff')

@section('page-title', 'Edit Staff')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.staff.index') }}">Kelola Tim</a></li>
    <li class="breadcrumb-item active">Edit Staff</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-edit mr-2"></i>
                    Edit Staff: {{ $staff->name }}
                </h3>
            </div>
            <form action="{{ route('admin.staff.update', $staff) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $staff->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $staff->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>No. HP</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $staff->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-control select2 @error('role') is-invalid @enderror" required>
                                    <option value="">-- Pilih Role --</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" {{ (old('role') ?? $staff->roles->first()?->name) == $role->name ? 'selected' : '' }}>
                                            {{ ucfirst($role->name) }} - {{ $role->description }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password Baru</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" onclick="generatePassword()">
                                            <i class="fas fa-random"></i>
                                        </button>
                                    </div>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password_confirmation')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $staff->is_active) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_active">Staff Aktif</label>
                                </div>
                                <small class="text-muted">Staff nonaktif tidak dapat login</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-2"></i>Info Staff</h3>
            </div>
            <div class="card-body text-center">
                <img src="{{ $staff->avatar_url }}" alt="{{ $staff->name }}" class="img-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                <h5>{{ $staff->name }}</h5>
                <p class="text-muted">{{ $staff->email }}</p>
                
                @foreach($staff->roles as $role)
                    <span class="badge badge-lg badge-{{ $role->name == 'teknisi' ? 'info' : 'warning' }}">
                        <i class="fas fa-{{ $role->name == 'teknisi' ? 'tools' : 'headset' }} mr-1"></i>
                        {{ ucfirst($role->name) }}
                    </span>
                @endforeach
            </div>
            <div class="card-footer text-muted text-sm">
                <div class="d-flex justify-content-between">
                    <span>Dibuat:</span>
                    <span>{{ $staff->created_at->format('d M Y H:i') }}</span>
                </div>
                @if($staff->updated_at->ne($staff->created_at))
                <div class="d-flex justify-content-between">
                    <span>Diupdate:</span>
                    <span>{{ $staff->updated_at->format('d M Y H:i') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    field.type = field.type === 'password' ? 'text' : 'password';
}

function generatePassword() {
    const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('password').value = password;
    document.getElementById('password_confirmation').value = password;
    document.getElementById('password').type = 'text';
    document.getElementById('password_confirmation').type = 'text';
    toastr.info('Password di-generate: ' + password);
}
</script>
@endpush
