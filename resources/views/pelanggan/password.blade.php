@extends('layouts.pelanggan')

@section('title', 'Ubah Password')

@section('page-title', 'Ubah Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-key mr-2"></i>Ubah Password</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('pelanggan.password.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label>Password Saat Ini <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label>Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimal 8 karakter</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    
                    <hr>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Ubah Password
                    </button>
                    <a href="{{ route('pelanggan.profile') }}" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
        
        <div class="alert alert-info">
            <h6><i class="fas fa-lightbulb mr-2"></i>Tips Keamanan</h6>
            <ul class="mb-0 pl-3">
                <li>Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol</li>
                <li>Jangan gunakan password yang mudah ditebak seperti tanggal lahir</li>
                <li>Jangan bagikan password Anda kepada siapapun</li>
            </ul>
        </div>
    </div>
</div>
@endsection
