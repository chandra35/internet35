<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ config('app.name', 'Internet35') }}</title>
    
    <!-- Preload critical resources -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=swap">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <style>
        .content-wrapper { min-height: calc(100vh - 57px); }
        .card { border-radius: 0.5rem; }
        .card-header { border-radius: 0.5rem 0.5rem 0 0 !important; }
        .btn { border-radius: 0.25rem; }
        .badge { font-weight: 500; }
        .nav-sidebar .nav-link { border-radius: 0.25rem; margin: 0 0.5rem; }
        .nav-sidebar .nav-header { padding: 0.75rem 1rem 0.5rem; }
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active { background-color: rgba(255,255,255,.1); }
        .table th { font-weight: 600; background-color: #f8f9fa; }
        .dataTables_wrapper .dataTables_processing { background: rgba(255,255,255,0.9); }
        .select2-container--bootstrap-5 .select2-selection { min-height: 38px; }
        .avatar-preview { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; }
        .info-box { border-radius: 0.5rem; }
        .small-box { border-radius: 0.5rem; }
        .small-box .icon { font-size: 70px; }
        .user-panel img { width: 2.1rem; height: 2.1rem; }
        .brand-link { border-bottom: 1px solid rgba(255,255,255,.1); }
        #map { height: 300px; border-radius: 0.5rem; }
        
        /* Custom Preloader */
        .preloader-custom {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        .preloader-custom.fade-out {
            opacity: 0;
            visibility: hidden;
        }
        .loader-ring {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }
        .loader-ring div {
            box-sizing: border-box;
            display: block;
            position: absolute;
            width: 64px;
            height: 64px;
            margin: 8px;
            border: 6px solid #fff;
            border-radius: 50%;
            animation: loader-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
            border-color: #fff transparent transparent transparent;
        }
        .loader-ring div:nth-child(1) { animation-delay: -0.45s; }
        .loader-ring div:nth-child(2) { animation-delay: -0.3s; }
        .loader-ring div:nth-child(3) { animation-delay: -0.15s; }
        @keyframes loader-ring {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loader-text {
            color: #fff;
            font-size: 1.1rem;
            font-weight: 500;
            margin-top: 20px;
            letter-spacing: 2px;
        }
        .loader-dots::after {
            content: '';
            animation: dots 1.5s steps(4, end) infinite;
        }
        @keyframes dots {
            0%, 20% { content: ''; }
            40% { content: '.'; }
            60% { content: '..'; }
            80%, 100% { content: '...'; }
        }
        
        /* Page transition overlay */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: transparent;
            z-index: 9998;
        }
        .page-loader-bar {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
            background-size: 200% 100%;
            animation: gradient-shift 1s ease infinite;
            transition: width 0.3s ease;
        }
        .page-loader-bar.loading {
            width: 70%;
        }
        .page-loader-bar.complete {
            width: 100%;
        }
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
    
    @stack('css')
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<div class="wrapper">
    <!-- Custom Preloader -->
    <div class="preloader-custom" id="preloader">
        <div class="loader-ring">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
        <div class="loader-text">MEMUAT<span class="loader-dots"></span></div>
    </div>
    
    <!-- Page Loader Bar -->
    <div class="page-loader" id="pageLoader">
        <div class="page-loader-bar" id="pageLoaderBar"></div>
    </div>

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('admin.dashboard') }}" class="nav-link">Dashboard</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Fullscreen -->
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
            
            <!-- User Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <img src="{{ auth()->user()->avatar_url }}" class="img-circle" alt="User Image" style="width: 25px; height: 25px; object-fit: cover;">
                    <span class="d-none d-md-inline ml-1">{{ auth()->user()->name }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <div class="dropdown-item bg-light">
                        <div class="media">
                            <img src="{{ auth()->user()->avatar_url }}" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                            <div class="media-body">
                                <h3 class="dropdown-item-title mb-0">{{ auth()->user()->name }}</h3>
                                <p class="text-sm text-muted mb-0">{{ auth()->user()->email }}</p>
                                <p class="text-sm mb-0">
                                    @foreach(auth()->user()->roles as $role)
                                        <span class="badge badge-primary">{{ $role->name }}</span>
                                    @endforeach
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('admin.profile.index') }}" class="dropdown-item">
                        <i class="fas fa-user-circle mr-2"></i> Profile Saya
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item text-danger" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ route('admin.dashboard') }}" class="brand-link">
            <span class="brand-image d-flex align-items-center justify-content-center bg-white rounded-circle" style="width:33px;height:33px;margin-left:0.8rem;">
                <i class="fas fa-wifi text-primary" style="font-size:1rem;"></i>
            </span>
            <span class="brand-text font-weight-light"><b>Internet</b>35</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="{{ auth()->user()->avatar_url }}" class="img-circle elevation-2" alt="User Image" style="width: 2.1rem; height: 2.1rem; object-fit: cover;">
                </div>
                <div class="info">
                    <a href="{{ route('admin.profile.index') }}" class="d-block">{{ auth()->user()->name }}</a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent nav-compact" data-widget="treeview" role="menu" data-accordion="true">
                    <!-- Dashboard -->
                    @can('dashboard.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    @endcan

                    <!-- Master Data -->
                    @canany(['routers.view', 'packages.view', 'customers.view'])
                    <li class="nav-header">MASTER DATA</li>
                    @can('routers.view')
                    <li class="nav-item {{ request()->routeIs('admin.routers.*') || request()->routeIs('admin.ppp-profiles.*') || request()->routeIs('admin.ip-pools.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.routers.*') || request()->routeIs('admin.ppp-profiles.*') || request()->routeIs('admin.ip-pools.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-server"></i>
                            <p>
                                Router
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.routers.index') }}" class="nav-link {{ request()->routeIs('admin.routers.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Daftar Router</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.ppp-profiles.index') }}" class="nav-link {{ request()->routeIs('admin.ppp-profiles.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>PPP Profiles</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.ip-pools.index') }}" class="nav-link {{ request()->routeIs('admin.ip-pools.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>IP Pools</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endcan
                    @can('packages.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.packages.index') }}" class="nav-link {{ request()->routeIs('admin.packages.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cubes"></i>
                            <p>Paket Internet</p>
                        </a>
                    </li>
                    @endcan
                    @can('customers.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.customers.index') }}" class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Pelanggan</p>
                        </a>
                    </li>
                    @endcan
                    @endcanany

                    <!-- OLT Management -->
                    @canany(['olts.view', 'onus.view'])
                    <li class="nav-header">MANAJEMEN OLT</li>
                    @can('olts.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.olts.index') }}" class="nav-link {{ request()->routeIs('admin.olts.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-server"></i>
                            <p>Daftar OLT</p>
                        </a>
                    </li>
                    @endcan
                    @can('onus.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.onus.index') }}" class="nav-link {{ request()->routeIs('admin.onus.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-broadcast-tower"></i>
                            <p>Daftar ONU</p>
                        </a>
                    </li>
                    @endcan
                    @endcanany

                    <!-- Network Infrastructure (ODC, ODP, Map) -->
                    @canany(['odcs.view', 'odps.view', 'network-map.view'])
                    <li class="nav-header">INFRASTRUKTUR</li>
                    @can('odcs.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.odcs.index') }}" class="nav-link {{ request()->routeIs('admin.odcs.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-building"></i>
                            <p>ODC</p>
                        </a>
                    </li>
                    @endcan
                    @can('odps.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.odps.index') }}" class="nav-link {{ request()->routeIs('admin.odps.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-box"></i>
                            <p>ODP</p>
                        </a>
                    </li>
                    @endcan
                    @can('network-map.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.network-map.index') }}" class="nav-link {{ request()->routeIs('admin.network-map.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-map-marked-alt"></i>
                            <p>Network Map</p>
                        </a>
                    </li>
                    @endcan
                    @endcanany

                    <!-- Landing Page & Settings -->
                    @canany(['landing.sliders.view', 'landing.services.view', 'landing.packages.view', 'landing.testimonials.view', 'landing.faqs.view', 'landing.contents.view', 'settings.view'])
                    <li class="nav-header">WEBSITE</li>
                    <li class="nav-item {{ request()->routeIs('admin.landing.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.landing.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-globe"></i>
                            <p>
                                Landing Page
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('landing.sliders.view')
                            <li class="nav-item">
                                <a href="{{ route('admin.landing.sliders.index') }}" class="nav-link {{ request()->routeIs('admin.landing.sliders.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Sliders</p>
                                </a>
                            </li>
                            @endcan
                            @can('landing.services.view')
                            <li class="nav-item">
                                <a href="{{ route('admin.landing.services.index') }}" class="nav-link {{ request()->routeIs('admin.landing.services.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Layanan</p>
                                </a>
                            </li>
                            @endcan
                            @can('landing.packages.view')
                            <li class="nav-item">
                                <a href="{{ route('admin.landing.packages.index') }}" class="nav-link {{ request()->routeIs('admin.landing.packages.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Paket</p>
                                </a>
                            </li>
                            @endcan
                            @can('landing.testimonials.view')
                            <li class="nav-item">
                                <a href="{{ route('admin.landing.testimonials.index') }}" class="nav-link {{ request()->routeIs('admin.landing.testimonials.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Testimoni</p>
                                </a>
                            </li>
                            @endcan
                            @can('landing.faqs.view')
                            <li class="nav-item">
                                <a href="{{ route('admin.landing.faqs.index') }}" class="nav-link {{ request()->routeIs('admin.landing.faqs.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>FAQ</p>
                                </a>
                            </li>
                            @endcan
                            @can('landing.contents.view')
                            <li class="nav-item">
                                <a href="{{ route('admin.landing.contents.index') }}" class="nav-link {{ request()->routeIs('admin.landing.contents.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Konten</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @can('settings.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Pengaturan</p>
                        </a>
                    </li>
                    @endcan
                    @endcanany

                    <!-- Staff Management (for admin-pop) -->
                    @can('staff.view')
                    <li class="nav-header">TIM</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.staff.index') }}" class="nav-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Kelola Tim</p>
                        </a>
                    </li>
                    @endcan

                    <!-- User & System (superadmin/admin only) -->
                    @canany(['users.view', 'roles.view', 'permissions.view', 'activity-logs.view'])
                    <li class="nav-header">SISTEM</li>
                    @canany(['users.view', 'roles.view', 'permissions.view'])
                    <li class="nav-item {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users-cog"></i>
                            <p>
                                Manajemen User
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('users.view')
                            <li class="nav-item">
                                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Users</p>
                                </a>
                            </li>
                            @endcan
                            @can('roles.view')
                            <li class="nav-item">
                                <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Roles</p>
                                </a>
                            </li>
                            @endcan
                            @can('permissions.view')
                            <li class="nav-item">
                                <a href="{{ route('admin.permissions.index') }}" class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Permissions</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany
                    @can('activity-logs.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.activity-logs.index') }}" class="nav-link {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Activity Logs</p>
                        </a>
                    </li>
                    @endcan
                    @endcanany
                    
                    <!-- POP Settings -->
                    @canany(['pop-settings.view', 'payment-gateways.view', 'notification-settings.view', 'message-templates.view'])
                    @role('superadmin')
                    {{-- SuperAdmin Menu --}}
                    <li class="nav-header">SUPERADMIN</li>
                    <li class="nav-item {{ request()->routeIs('admin.pop-settings.*') || request()->routeIs('admin.payment-gateways.sandbox-requests') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.pop-settings.*') || request()->routeIs('admin.payment-gateways.sandbox-requests') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-building"></i>
                            <p>
                                Kelola POP
                                @php
                                    $pendingCount = \App\Models\PaymentGateway::where('sandbox_status', 'pending')->count();
                                @endphp
                                @if($pendingCount > 0)
                                <span class="badge badge-danger right">{{ $pendingCount }}</span>
                                @endif
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.pop-settings.monitoring') }}" class="nav-link {{ request()->routeIs('admin.pop-settings.monitoring') || request()->routeIs('admin.pop-settings.view-detail') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Monitoring</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.pop-settings.index') }}" class="nav-link {{ request()->routeIs('admin.pop-settings.index') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Daftar POP</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.payment-gateways.sandbox-requests') }}" class="nav-link {{ request()->routeIs('admin.payment-gateways.sandbox-requests') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Review Sandbox</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.pop-settings.copy-settings') }}" class="nav-link {{ request()->routeIs('admin.pop-settings.copy-settings') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Salin Pengaturan</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @else
                    {{-- Admin POP Menu --}}
                    <li class="nav-header">PENGATURAN</li>
                    <li class="nav-item {{ request()->routeIs('admin.pop-settings.*') || request()->routeIs('admin.payment-gateways.*') || request()->routeIs('admin.notification-settings.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.pop-settings.*') || request()->routeIs('admin.payment-gateways.*') || request()->routeIs('admin.notification-settings.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-sliders-h"></i>
                            <p>
                                Pengaturan POP
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('pop-settings.view')
                            <li class="nav-item">
                                <a href="{{ route('admin.pop-settings.isp-info') }}" class="nav-link {{ request()->routeIs('admin.pop-settings.isp-info') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Info ISP</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.pop-settings.invoice-settings') }}" class="nav-link {{ request()->routeIs('admin.pop-settings.invoice-settings') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Invoice & Pajak</p>
                                </a>
                            </li>
                            @endcan
                            @can('payment-gateways.view')
                            <li class="nav-item">
                                <a href="{{ route('admin.payment-gateways.index') }}" class="nav-link {{ request()->routeIs('admin.payment-gateways.index') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Payment Gateway</p>
                                </a>
                            </li>
                            @endcan
                            @can('notification-settings.view')
                            <li class="nav-item">
                                <a href="{{ route('admin.notification-settings.index') }}" class="nav-link {{ request()->routeIs('admin.notification-settings.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Notifikasi</p>
                                </a>
                            </li>
                            @endcan
                            @can('message-templates.view')
                            <li class="nav-item">
                                <a href="{{ route('admin.message-templates.index') }}" class="nav-link {{ request()->routeIs('admin.message-templates.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Template Pesan</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endrole
                    @endcanany

                    <!-- Profile -->
                    <li class="nav-header">AKUN</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.profile.index') }}" class="nav-link {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-circle"></i>
                            <p>Profile</p>
                        </a>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">@yield('page-title', 'Dashboard')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            @yield('breadcrumb')
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong>&copy; {{ date('Y') }} <a href="{{ route('landing') }}">Internet35</a>.</strong> All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
        </div>
    </footer>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Toastr -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    // DataTables Indonesian Language
    const dtLanguageID = {
        "emptyTable": "Tidak ada data yang tersedia",
        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
        "infoFiltered": "(disaring dari _MAX_ total data)",
        "infoPostFix": "",
        "thousands": ".",
        "lengthMenu": "Tampilkan _MENU_ data",
        "loadingRecords": "Memuat...",
        "processing": "<div class='d-flex align-items-center'><i class='fas fa-spinner fa-spin mr-2'></i> Memproses...</div>",
        "search": "Cari:",
        "zeroRecords": "Tidak ditemukan data yang cocok",
        "paginate": {
            "first": "<i class='fas fa-angle-double-left'></i>",
            "last": "<i class='fas fa-angle-double-right'></i>",
            "next": "<i class='fas fa-angle-right'></i>",
            "previous": "<i class='fas fa-angle-left'></i>"
        },
        "aria": {
            "sortAscending": ": aktifkan untuk mengurutkan kolom naik",
            "sortDescending": ": aktifkan untuk mengurutkan kolom turun"
        }
    };

    // CSRF Token setup for AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Toastr configuration
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "3000"
    };

    // Hide preloader with smooth animation
    $(window).on('load', function() {
        $('#preloader').addClass('fade-out');
        setTimeout(function() {
            $('#preloader').remove();
            
            // Show flash messages after preloader is gone
            @if(session('success'))
                toastr.success('{{ session('success') }}');
            @endif
            @if(session('error'))
                toastr.error('{{ session('error') }}');
            @endif
            @if(session('warning'))
                toastr.warning('{{ session('warning') }}');
            @endif
            @if(session('info'))
                toastr.info('{{ session('info') }}');
            @endif
        }, 500);
    });
    
    // Page navigation loader
    $(document).on('click', 'a[href]:not([href^="#"]):not([href^="javascript"]):not([target="_blank"]):not([data-toggle])', function(e) {
        const href = $(this).attr('href');
        if (href && !href.startsWith('#') && !href.startsWith('javascript') && !$(this).hasClass('no-loader')) {
            $('#pageLoaderBar').addClass('loading');
        }
    });
    
    $(window).on('beforeunload', function() {
        $('#pageLoaderBar').addClass('loading');
    });
    
    $(window).on('pageshow', function() {
        $('#pageLoaderBar').removeClass('loading').addClass('complete');
        setTimeout(function() {
            $('#pageLoaderBar').removeClass('complete').css('width', '0');
        }, 300);
    });

    // Initialize Select2
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
    });

    // Global delete confirmation
    function confirmDelete(url, name, table) {
        Swal.fire({
            title: 'Hapus Data?',
            html: `Anda yakin ingin menghapus <strong>${name}</strong>?<br>Data yang dihapus tidak dapat dikembalikan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            if (table) table.ajax.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan!');
                    }
                });
            }
        });
    }

    // Global form submit handler
    function submitForm(form, modal, table) {
        const formData = new FormData(form);
        const url = form.action;
        const method = form.querySelector('input[name="_method"]')?.value || 'POST';

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (modal) $(modal).modal('hide');
                    if (table) table.ajax.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessages = '';
                    for (const key in errors) {
                        errorMessages += errors[key].join('<br>') + '<br>';
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal',
                        html: errorMessages
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan!');
                }
            }
        });
    }
</script>

@stack('js')
</body>
</html>
