@extends('layouts.admin')

@section('title', 'Detail POP - ' . $user->name)

@section('page-title', 'Detail POP')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.pop-settings.monitoring') }}">Monitoring POP</a></li>
    <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-4">
        <!-- User Info Card -->
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    @if($user->avatar)
                    <img class="profile-user-img img-fluid img-circle" src="{{ Storage::url($user->avatar) }}" alt="User profile">
                    @else
                    <div class="profile-user-img img-fluid img-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px;">
                        <i class="fas fa-user fa-3x text-white"></i>
                    </div>
                    @endif
                </div>
                <h3 class="profile-username text-center">{{ $user->name }}</h3>
                <p class="text-muted text-center">{{ $user->email }}</p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Bergabung</b> <a class="float-right">{{ $user->created_at->format('d M Y') }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>Role</b> 
                        <a class="float-right">
                            @foreach($user->roles as $role)
                            <span class="badge badge-info">{{ $role->name }}</span>
                            @endforeach
                        </a>
                    </li>
                    <li class="list-group-item">
                        <b>Status</b> 
                        <a class="float-right">
                            @if($user->status == 'active')
                            <span class="badge badge-success">Aktif</span>
                            @else
                            <span class="badge badge-danger">Nonaktif</span>
                            @endif
                        </a>
                    </li>
                </ul>

                <a href="{{ route('admin.pop-settings.isp-info', ['user_id' => $user->id]) }}" class="btn btn-primary btn-block">
                    <i class="fas fa-cog mr-1"></i> Kelola Pengaturan
                </a>
            </div>
        </div>

        <!-- Setup Progress -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>Progress Setup</h3>
            </div>
            <div class="card-body">
                @php
                    $ispComplete = $popSetting && !empty($popSetting->isp_name);
                    $invoiceComplete = $popSetting && !empty($popSetting->invoice_prefix);
                    $paymentComplete = $paymentGateways->where('is_active', true)->count() > 0;
                    $notifComplete = $notifSetting && ($notifSetting->email_enabled || $notifSetting->whatsapp_enabled || $notifSetting->telegram_enabled);
                    $completed = collect([$ispComplete, $invoiceComplete, $paymentComplete, $notifComplete])->filter()->count();
                    $progress = ($completed / 4) * 100;
                @endphp

                <div class="progress mb-3" style="height: 20px;">
                    <div class="progress-bar 
                        @if($progress == 100) bg-success
                        @elseif($progress >= 50) bg-info
                        @else bg-warning @endif" 
                        style="width: {{ $progress }}%">
                        {{ round($progress) }}%
                    </div>
                </div>

                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="fas fa-building mr-2"></i> Informasi ISP</span>
                        @if($ispComplete)
                        <span class="badge badge-success"><i class="fas fa-check"></i> Lengkap</span>
                        @else
                        <span class="badge badge-warning"><i class="fas fa-exclamation"></i> Belum</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="fas fa-file-invoice mr-2"></i> Invoice</span>
                        @if($invoiceComplete)
                        <span class="badge badge-success"><i class="fas fa-check"></i> Lengkap</span>
                        @else
                        <span class="badge badge-warning"><i class="fas fa-exclamation"></i> Belum</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="fas fa-credit-card mr-2"></i> Payment Gateway</span>
                        @if($paymentComplete)
                        <span class="badge badge-success">{{ $paymentGateways->where('is_active', true)->count() }} Aktif</span>
                        @else
                        <span class="badge badge-warning"><i class="fas fa-exclamation"></i> Belum</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="fas fa-bell mr-2"></i> Notifikasi</span>
                        @if($notifComplete)
                        <span class="badge badge-success"><i class="fas fa-check"></i> Aktif</span>
                        @else
                        <span class="badge badge-warning"><i class="fas fa-exclamation"></i> Belum</span>
                        @endif
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- ISP Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-building mr-2"></i>Informasi ISP</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.pop-settings.isp-info', ['user_id' => $user->id]) }}" class="btn btn-tool">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($popSetting && $popSetting->isp_name)
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th style="width: 140px;">Nama ISP</th>
                                <td>{{ $popSetting->isp_name }}</td>
                            </tr>
                            <tr>
                                <th>Tagline</th>
                                <td>{{ $popSetting->isp_tagline ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Kode POP</th>
                                <td>{{ $popSetting->pop_code ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Nama POP</th>
                                <td>{{ $popSetting->pop_name ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th style="width: 140px;">Telepon</th>
                                <td>{{ $popSetting->phone ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $popSetting->email ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>WhatsApp</th>
                                <td>{{ $popSetting->whatsapp ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Website</th>
                                <td>{{ $popSetting->website ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                @if($popSetting->address)
                <hr>
                <p class="mb-0">
                    <strong>Alamat:</strong><br>
                    {{ $popSetting->address }}
                </p>
                @endif
                @else
                <div class="text-center text-muted py-3">
                    <i class="fas fa-building fa-2x mb-2"></i>
                    <p>Informasi ISP belum diisi</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Invoice Settings -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Pengaturan Invoice</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.pop-settings.invoice-settings', ['user_id' => $user->id]) }}" class="btn btn-tool">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($popSetting && $popSetting->invoice_prefix)
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th style="width: 140px;">Prefix Invoice</th>
                                <td><code>{{ $popSetting->invoice_prefix }}</code></td>
                            </tr>
                            <tr>
                                <th>Jatuh Tempo</th>
                                <td>{{ $popSetting->invoice_due_days ?? 7 }} hari</td>
                            </tr>
                            <tr>
                                <th>PPN</th>
                                <td>
                                    @if($popSetting->ppn_enabled)
                                    <span class="badge badge-success">{{ $popSetting->ppn_percentage }}%</span>
                                    @else
                                    <span class="badge badge-secondary">Tidak Aktif</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th style="width: 140px;">NPWP</th>
                                <td>{{ $popSetting->npwp ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>NIB</th>
                                <td>{{ $popSetting->nib ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Izin ISP</th>
                                <td>{{ $popSetting->isp_license_number ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                @if($popSetting->bank_accounts && count($popSetting->bank_accounts) > 0)
                <hr>
                <h6><i class="fas fa-university mr-1"></i> Rekening Bank</h6>
                <div class="row">
                    @foreach($popSetting->bank_accounts as $bank)
                    <div class="col-md-6">
                        <div class="callout callout-info py-2">
                            <strong>{{ $bank['bank_name'] ?? '-' }}</strong><br>
                            <small>{{ $bank['account_number'] ?? '-' }} a.n {{ $bank['account_name'] ?? '-' }}</small>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                @else
                <div class="text-center text-muted py-3">
                    <i class="fas fa-file-invoice fa-2x mb-2"></i>
                    <p>Pengaturan invoice belum dikonfigurasi</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Payment Gateways -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-credit-card mr-2"></i>Payment Gateway</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.payment-gateways.index', ['user_id' => $user->id]) }}" class="btn btn-tool">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($paymentGateways->count() > 0)
                <div class="row">
                    @foreach($paymentGateways as $gateway)
                    <div class="col-md-4">
                        <div class="info-box {{ $gateway->is_active ? 'bg-light' : 'bg-secondary' }}">
                            <span class="info-box-icon {{ $gateway->is_active ? 'bg-success' : 'bg-secondary' }}">
                                <i class="fas fa-credit-card"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ ucfirst($gateway->gateway_type) }}</span>
                                <span class="info-box-number">
                                    @if($gateway->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                    @else
                                    <span class="badge badge-secondary">Nonaktif</span>
                                    @endif
                                    @if($gateway->is_sandbox)
                                    <span class="badge badge-warning">Sandbox</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center text-muted py-3">
                    <i class="fas fa-credit-card fa-2x mb-2"></i>
                    <p>Belum ada payment gateway yang dikonfigurasi</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell mr-2"></i>Pengaturan Notifikasi</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.notification-settings.index', ['user_id' => $user->id]) }}" class="btn btn-tool">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($notifSetting)
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="info-box {{ $notifSetting->email_enabled ? 'bg-light' : '' }}">
                            <span class="info-box-icon {{ $notifSetting->email_enabled ? 'bg-info' : 'bg-secondary' }}">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Email</span>
                                <span class="info-box-number">
                                    @if($notifSetting->email_enabled)
                                    <span class="badge badge-success">Aktif</span>
                                    @else
                                    <span class="badge badge-secondary">Nonaktif</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="info-box {{ $notifSetting->whatsapp_enabled ? 'bg-light' : '' }}">
                            <span class="info-box-icon {{ $notifSetting->whatsapp_enabled ? 'bg-success' : 'bg-secondary' }}">
                                <i class="fab fa-whatsapp"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">WhatsApp</span>
                                <span class="info-box-number">
                                    @if($notifSetting->whatsapp_enabled)
                                    <span class="badge badge-success">{{ ucfirst($notifSetting->whatsapp_provider) }}</span>
                                    @else
                                    <span class="badge badge-secondary">Nonaktif</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="info-box {{ $notifSetting->telegram_enabled ? 'bg-light' : '' }}">
                            <span class="info-box-icon {{ $notifSetting->telegram_enabled ? 'bg-primary' : 'bg-secondary' }}">
                                <i class="fab fa-telegram"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Telegram</span>
                                <span class="info-box-number">
                                    @if($notifSetting->telegram_enabled)
                                    <span class="badge badge-success">Aktif</span>
                                    @else
                                    <span class="badge badge-secondary">Nonaktif</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="text-center text-muted py-3">
                    <i class="fas fa-bell fa-2x mb-2"></i>
                    <p>Pengaturan notifikasi belum dikonfigurasi</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <a href="{{ route('admin.pop-settings.monitoring') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Monitoring
        </a>
    </div>
</div>
@endsection
