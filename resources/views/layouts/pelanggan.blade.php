<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Pelanggan') - {{ config('app.name') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- AdminLTE & Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .main-sidebar {
            background: linear-gradient(180deg, #1e3a5f 0%, #0d1b2a 100%);
        }
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: #fff;
        }
        .brand-link {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .small-box {
            border-radius: 12px;
        }
        .small-box .icon {
            font-size: 60px;
            opacity: 0.2;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .content-wrapper {
            background-color: #f4f6f9;
        }
        .user-panel img {
            width: 40px;
            height: 40px;
        }
        .btn {
            border-radius: 8px;
        }
        .alert {
            border-radius: 8px;
        }
    </style>
    @stack('css')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        
        <ul class="navbar-nav ml-auto">
            @php $customer = auth()->user()->customerProfile; @endphp
            @if($customer)
            <li class="nav-item">
                <span class="nav-link">
                    <span class="badge badge-{{ $customer->status_color }}">
                        {{ $customer->status_label }}
                    </span>
                </span>
            </li>
            @endif
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i> {{ auth()->user()->name }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('pelanggan.profile') }}" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Profil Saya
                    </a>
                    <a href="{{ route('pelanggan.password') }}" class="dropdown-item">
                        <i class="fas fa-key mr-2"></i> Ubah Password
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('pelanggan.dashboard') }}" class="brand-link text-center">
            <span class="brand-text font-weight-bold text-white">Internet35</span>
        </a>
        
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    @if($customer && $customer->photo_selfie_url)
                    <img src="{{ $customer->photo_selfie_url }}" class="img-circle elevation-2" alt="Foto">
                    @else
                    <div class="img-circle elevation-2 bg-secondary d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    @endif
                </div>
                <div class="info">
                    <a href="{{ route('pelanggan.profile') }}" class="d-block text-white">
                        {{ auth()->user()->name }}
                    </a>
                    @if($customer)
                    <small class="text-muted">{{ $customer->customer_id }}</small>
                    @endif
                </div>
            </div>
            
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="{{ route('pelanggan.dashboard') }}" class="nav-link {{ request()->routeIs('pelanggan.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-home"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('pelanggan.connection') }}" class="nav-link {{ request()->routeIs('pelanggan.connection') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-wifi"></i>
                            <p>Koneksi Saya</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('pelanggan.invoices') }}" class="nav-link {{ request()->routeIs('pelanggan.invoices*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                            <p>Tagihan</p>
                            @if($customer)
                            @php 
                                $unpaidCount = $customer->invoices()->whereIn('status', ['pending', 'overdue'])->count(); 
                            @endphp
                            @if($unpaidCount > 0)
                            <span class="badge badge-danger right">{{ $unpaidCount }}</span>
                            @endif
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('pelanggan.payments') }}" class="nav-link {{ request()->routeIs('pelanggan.payments') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Riwayat Pembayaran</p>
                        </a>
                    </li>
                    <li class="nav-header">AKUN</li>
                    <li class="nav-item">
                        <a href="{{ route('pelanggan.profile') }}" class="nav-link {{ request()->routeIs('pelanggan.profile') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user"></i>
                            <p>Profil Saya</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('pelanggan.password') }}" class="nav-link {{ request()->routeIs('pelanggan.password') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-key"></i>
                            <p>Ubah Password</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>@yield('page-title', 'Dashboard')</h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ session('success') }}
                </div>
                @endif
                
                @if(session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ session('error') }}
                </div>
                @endif
                
                @yield('content')
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            Portal Pelanggan v1.0
        </div>
        <strong>&copy; {{ date('Y') }} {{ config('app.name') }}.</strong> All rights reserved.
    </footer>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
    };
</script>
@stack('js')
</body>
</html>
