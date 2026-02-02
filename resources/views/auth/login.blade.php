<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login | {{ config('app.name', 'Internet35') }}</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body.login-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-box {
            width: 400px;
            margin-top: 5%;
        }
        .login-card-body {
            border-radius: 1rem;
            padding: 2rem;
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 10px 50px rgba(0,0,0,0.2);
        }
        .login-logo {
            margin-bottom: 1.5rem;
        }
        .login-logo a {
            color: white;
            font-size: 2rem;
        }
        .login-logo img {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .input-group-text {
            background: transparent;
            border-right: none;
        }
        .form-control {
            border-left: none;
        }
        .form-control:focus {
            border-color: #ced4da;
            box-shadow: none;
        }
        .input-group:focus-within .input-group-text {
            border-color: #80bdff;
        }
        .input-group:focus-within .form-control {
            border-color: #80bdff;
        }
        .custom-checkbox .custom-control-label::before {
            border-radius: 0.25rem;
        }
        .login-footer {
            text-align: center;
            color: rgba(255,255,255,0.8);
            margin-top: 2rem;
        }
        .login-footer a {
            color: white;
        }
        .floating-shapes {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        .floating-shapes span {
            position: absolute;
            display: block;
            width: 20px;
            height: 20px;
            background: rgba(255,255,255,0.1);
            animation: float 25s linear infinite;
            bottom: -150px;
            border-radius: 50%;
        }
        .floating-shapes span:nth-child(1) { left: 25%; width: 80px; height: 80px; animation-delay: 0s; }
        .floating-shapes span:nth-child(2) { left: 10%; width: 20px; height: 20px; animation-delay: 2s; animation-duration: 12s; }
        .floating-shapes span:nth-child(3) { left: 70%; width: 20px; height: 20px; animation-delay: 4s; }
        .floating-shapes span:nth-child(4) { left: 40%; width: 60px; height: 60px; animation-delay: 0s; animation-duration: 18s; }
        .floating-shapes span:nth-child(5) { left: 65%; width: 20px; height: 20px; animation-delay: 0s; }
        .floating-shapes span:nth-child(6) { left: 75%; width: 110px; height: 110px; animation-delay: 3s; }
        .floating-shapes span:nth-child(7) { left: 35%; width: 150px; height: 150px; animation-delay: 7s; }
        .floating-shapes span:nth-child(8) { left: 50%; width: 25px; height: 25px; animation-delay: 15s; animation-duration: 45s; }
        .floating-shapes span:nth-child(9) { left: 20%; width: 15px; height: 15px; animation-delay: 2s; animation-duration: 35s; }
        .floating-shapes span:nth-child(10) { left: 85%; width: 150px; height: 150px; animation-delay: 0s; animation-duration: 11s; }
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; }
        }
    </style>
</head>
<body class="hold-transition login-page">
    <div class="floating-shapes">
        <span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span><span></span>
    </div>

    <div class="login-box">
        <div class="login-logo">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" class="img-fluid" onerror="this.style.display='none'">
            <a href="{{ route('landing') }}"><b>Internet</b>35</a>
        </div>
        
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Masuk ke akun Anda</p>

                <form id="loginForm" action="{{ route('login') }}" method="POST">
                    @csrf
                    
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-user text-muted"></i>
                            </span>
                        </div>
                        <input type="text" name="login" class="form-control @error('login') is-invalid @enderror" 
                               placeholder="Email atau ID Pelanggan" value="{{ old('login') }}" required autofocus>
                        @error('login')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-lock text-muted"></i>
                            </span>
                        </div>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" 
                               placeholder="Password" required>
                        <div class="input-group-append">
                            <span class="input-group-text" style="cursor:pointer;background:transparent;border-left:none;" onclick="togglePassword(this)">
                                <i class="fas fa-eye text-muted"></i>
                            </span>
                        </div>
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-8">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                                <label class="custom-control-label" for="remember">Ingat Saya</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="btnLogin">
                        <i class="fas fa-sign-in-alt mr-2"></i> Login
                    </button>
                </form>
            </div>
        </div>

        <div class="login-footer">
            <p>&copy; {{ date('Y') }} Internet35. All rights reserved.</p>
            <p><a href="{{ route('landing') }}"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Website</a></p>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function togglePassword(el) {
            const input = el.closest('.input-group').querySelector('input[name="password"]');
            const icon = el.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            
            const btn = $('#btnLogin');
            const originalText = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...').prop('disabled', true);

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = response.redirect;
                        });
                    }
                },
                error: function(xhr) {
                    btn.html(originalText).prop('disabled', false);
                    
                    let message = 'Terjadi kesalahan!';
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        if (errors) {
                            message = Object.values(errors).flat().join('<br>');
                        } else if (xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Gagal',
                        html: message
                    });
                }
            });
        });
    </script>
</body>
</html>
