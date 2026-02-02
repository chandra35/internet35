<!-- Settings Sidebar -->
<div class="card">
    <div class="card-header bg-primary">
        <h3 class="card-title text-white"><i class="fas fa-cog mr-2"></i>Pengaturan</h3>
    </div>
    <div class="card-body p-0">
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a href="{{ route('admin.pop-settings.isp-info', request()->only('user_id')) }}" 
                   class="nav-link {{ request()->routeIs('admin.pop-settings.isp-info') ? 'active' : '' }}">
                    <i class="fas fa-building mr-2"></i> Informasi ISP
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.pop-settings.invoice-settings', request()->only('user_id')) }}" 
                   class="nav-link {{ request()->routeIs('admin.pop-settings.invoice-settings') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice mr-2"></i> Pengaturan Invoice
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.pop-settings.integration', request()->only('user_id')) }}" 
                   class="nav-link {{ request()->routeIs('admin.pop-settings.integration') ? 'active' : '' }}">
                    <i class="fas fa-plug mr-2"></i> Integrasi
                    <small class="badge badge-success float-right">Baru</small>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.payment-gateways.index', request()->only('user_id')) }}" 
                   class="nav-link {{ request()->routeIs('admin.payment-gateways.*') ? 'active' : '' }}">
                    <i class="fas fa-credit-card mr-2"></i> Payment Gateway
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.notification-settings.index', request()->only('user_id')) }}" 
                   class="nav-link {{ request()->routeIs('admin.notification-settings.*') ? 'active' : '' }}">
                    <i class="fas fa-bell mr-2"></i> Notifikasi
                </a>
            </li>
        </ul>
    </div>
</div>

@if(auth()->user()->hasRole('admin'))
<div class="card card-outline card-danger">
    <div class="card-header bg-danger">
        <h3 class="card-title text-white"><i class="fas fa-shield-alt mr-2"></i>SuperAdmin</h3>
    </div>
    <div class="card-body p-0">
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a href="{{ route('admin.pop-settings.monitoring') }}" 
                   class="nav-link {{ request()->routeIs('admin.pop-settings.monitoring') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt mr-2"></i> Monitoring POP
                    @php
                        $totalPop = \App\Models\User::role('admin-pop')->count();
                    @endphp
                    <span class="badge badge-info float-right">{{ $totalPop }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.pop-settings.index') }}" 
                   class="nav-link {{ request()->routeIs('admin.pop-settings.index') ? 'active' : '' }}">
                    <i class="fas fa-list mr-2"></i> Semua Pengaturan POP
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.payment-gateways.sandbox-requests') }}" 
                   class="nav-link {{ request()->routeIs('admin.payment-gateways.sandbox-requests') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-check mr-2"></i> Review Sandbox
                    @php
                        $pendingCount = \App\Models\PaymentGateway::where('sandbox_status', 'pending')->count();
                    @endphp
                    @if($pendingCount > 0)
                    <span class="badge badge-danger float-right">{{ $pendingCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.pop-settings.copy-settings') }}" 
                   class="nav-link {{ request()->routeIs('admin.pop-settings.copy-settings') ? 'active' : '' }}">
                    <i class="fas fa-copy mr-2"></i> Salin Pengaturan
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="alert alert-warning">
    <i class="fas fa-info-circle mr-1"></i>
    <strong>Mode SuperAdmin</strong><br>
    <small>Anda sedang melihat/mengedit pengaturan {{ request('user_id') ? 'Admin POP lain' : 'sebagai SuperAdmin' }}. 
    Gunakan menu <strong>Monitoring POP</strong> untuk melihat semua POP.</small>
</div>
@endif

<!-- Quick Stats -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Status</h3>
    </div>
    <div class="card-body p-0">
        @php
            $userId = request('user_id', auth()->id());
            $popSetting = \App\Models\PopSetting::where('user_id', $userId)->first();
            $paymentGateways = \App\Models\PaymentGateway::where('user_id', $userId)->where('is_active', true)->count();
            $notifSetting = \App\Models\NotificationSetting::where('user_id', $userId)->first();
        @endphp
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Info ISP
                @if($popSetting && $popSetting->isp_name)
                <span class="badge badge-success"><i class="fas fa-check"></i></span>
                @else
                <span class="badge badge-warning"><i class="fas fa-exclamation"></i></span>
                @endif
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Payment Gateway
                @if($paymentGateways > 0)
                <span class="badge badge-success">{{ $paymentGateways }} aktif</span>
                @else
                <span class="badge badge-warning">0 aktif</span>
                @endif
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Email
                @if($notifSetting && $notifSetting->smtp_host)
                <span class="badge badge-success"><i class="fas fa-check"></i></span>
                @else
                <span class="badge badge-secondary">-</span>
                @endif
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                WhatsApp
                @if($notifSetting && $notifSetting->whatsapp_enabled)
                <span class="badge badge-success"><i class="fas fa-check"></i></span>
                @else
                <span class="badge badge-secondary">-</span>
                @endif
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Telegram
                @if($notifSetting && $notifSetting->telegram_enabled)
                <span class="badge badge-success"><i class="fas fa-check"></i></span>
                @else
                <span class="badge badge-secondary">-</span>
                @endif
            </li>
        </ul>
    </div>
</div>
