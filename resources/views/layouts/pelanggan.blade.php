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
        /* Base — 13px felt right for compact portal */
        *, body { font-family: 'Inter', sans-serif; }
        body { font-size: 13px; line-height: 1.5; color: #2d3748; }

        /* Sidebar */
        .main-sidebar { background: linear-gradient(180deg, #1a2e4a 0%, #0d1b2a 100%); }
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active {
            background-color: rgba(255,255,255,0.12); color: #fff; border-radius: 8px;
        }
        .nav-sidebar .nav-link {
            border-radius: 8px; margin: 1px 8px;
            font-size: 0.8rem; padding: 7px 10px;
        }
        .nav-sidebar .nav-icon { font-size: 0.85rem; width: 1.4rem; }
        .nav-sidebar .nav-link p { font-size: 0.8rem; line-height: 1.4; }
        .brand-link { border-bottom: 1px solid rgba(255,255,255,0.1); padding: 9px 15px !important; }
        .brand-text { font-size: 0.92rem !important; letter-spacing: 0.3px; }
        .user-panel .info a { font-size: 0.8rem; font-weight: 500; }
        .user-panel .info small { font-size: 0.68rem; }

        /* Navbar */
        .main-header.navbar { min-height: 44px; padding: 0 12px; }
        .navbar .nav-link { padding: 6px 10px; font-size: 0.78rem; }
        .badge-sm { font-size: 0.6rem; padding: 2px 5px; }
        .dropdown-item { font-size: 0.78rem; padding: 5px 14px; }

        /* Content area */
        .content-wrapper { background-color: #f0f2f5; }
        .content-header { padding: 8px 15px 0 !important; }
        .content-header h1 { font-size: 0.92rem; font-weight: 600; color: #1a2533; margin: 0; }
        .content-header .breadcrumb { font-size: 0.7rem; }
        .content { padding: 8px 0 !important; }

        /* Cards */
        .card {
            border-radius: 10px; box-shadow: 0 1px 5px rgba(0,0,0,0.07);
            border: 1px solid rgba(0,0,0,0.06); margin-bottom: 14px; font-size: 0.8rem;
        }
        .card-header {
            background: #fff; border-bottom: 1px solid rgba(0,0,0,0.07);
            padding: 9px 14px; border-radius: 10px 10px 0 0 !important;
        }
        .card-title { font-size: 0.78rem; font-weight: 600; color: #444; margin-bottom: 0; }
        .card-body { padding: 12px 14px; }
        .card-footer { padding: 7px 14px; background: #fafafa; border-top: 1px solid #f0f0f0; border-radius: 0 0 10px 10px !important; font-size: 0.75rem; }

        /* Compact Stat Cards */
        .stat-card {
            border-radius: 10px; padding: 12px 13px 9px;
            color: #fff; position: relative; overflow: hidden;
            height: 100%; min-height: 82px;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .stat-icon { position: absolute; right: 10px; top: 8px; font-size: 28px; opacity: 0.16; }
        .stat-value { font-size: 1rem; font-weight: 700; line-height: 1.25; margin-top: 2px; word-break: break-word; }
        .stat-label { font-size: 0.65rem; opacity: 0.88; margin-top: 1px; line-height: 1.3; }
        .stat-link {
            display: block; color: rgba(255,255,255,0.72); font-size: 0.65rem;
            margin-top: 5px; text-decoration: none; border-top: 1px solid rgba(255,255,255,0.15);
            padding-top: 4px;
        }
        .stat-link:hover { color: #fff; }
        .stat-green  { background: linear-gradient(135deg, #1aaa55, #17c671); }
        .stat-red    { background: linear-gradient(135deg, #dc3545, #c82333); }
        .stat-yellow { background: linear-gradient(135deg, #e0871a, #f4a721); }
        .stat-blue   { background: linear-gradient(135deg, #1565c0, #1976d2); }
        .stat-teal   { background: linear-gradient(135deg, #00838f, #0097a7); }

        /* Period strip */
        .period-strip { background: #fff; border-radius: 10px; border: 1px solid rgba(0,0,0,0.06); padding: 9px 13px; margin-bottom: 14px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .period-strip .period-label { font-size: 0.68rem; color: #888; }
        .period-strip .period-val { font-size: 0.78rem; font-weight: 600; color: #333; }

        /* Invoice alert banner */
        .invoice-banner {
            display: flex; align-items: center; background: #fff;
            border-left: 4px solid #dc3545; border-radius: 8px;
            padding: 9px 13px; margin-bottom: 10px;
            box-shadow: 0 1px 4px rgba(220,53,69,0.12); font-size: 0.78rem;
        }
        .invoice-banner.pending { border-left-color: #f39c12; }

        /* Payment list items */
        .payment-item { padding: 8px 14px; border-bottom: 1px solid #f0f0f0; }
        .payment-item:last-child { border-bottom: none; }
        .invoice-row:hover { background: #fafbfc; }

        /* Buttons */
        .btn { border-radius: 7px; font-size: 0.78rem; }
        .btn-sm { font-size: 0.73rem; padding: 3px 9px; }
        .btn-xs { font-size: 0.68rem; padding: 2px 6px; border-radius: 5px; }
        .alert { border-radius: 8px; font-size: 0.8rem; }
        code { font-size: 0.78rem; }
        .badge { font-size: 0.65rem; }

        /* Footer */
        .main-footer { font-size: 0.72rem; padding: 8px 16px; }

        /* Mobile */
        @media (max-width: 576px) {
            body { font-size: 12.5px; }
            .stat-value { font-size: 0.88rem; }
            .stat-icon { font-size: 22px; }
            .content-header h1 { font-size: 0.85rem; }
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
            <li class="nav-item d-none d-md-flex align-items-center">
                <span class="nav-link text-muted" id="navbarClock" aria-label="Waktu Indonesia Barat">
                    <i class="far fa-clock mr-1"></i><span>--:--:-- WIB</span>
                </span>
            </li>
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
                        <a href="{{ route('pelanggan.wifi') }}" class="nav-link {{ request()->routeIs('pelanggan.wifi') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-broadcast-tower"></i>
                            <p>WiFi Saya</p>
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
<script>
(() => {
    const clock = document.querySelector('#navbarClock span');
    if (!clock) return;

    const formatter = new Intl.DateTimeFormat('id-ID', {
        timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
    });
    const updateClock = () => { clock.textContent = `${formatter.format(new Date())} WIB`; };
    updateClock();
    setInterval(updateClock, 1000);
})();
</script>
@stack('js')
</body>
</html>
