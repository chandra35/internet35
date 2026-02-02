@extends('layouts.admin')

@section('title', 'Monitoring POP')

@section('page-title', 'Monitoring POP')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Monitoring POP</li>
@endsection

@section('content')
<div class="row">
    <!-- Statistics Cards -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['total_pop'] }}</h3>
                <p>Total POP</p>
            </div>
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['complete'] }}</h3>
                <p>Setup Lengkap</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <a href="{{ route('admin.pop-settings.monitoring', ['status' => 'complete']) }}" class="small-box-footer">
                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['incomplete'] }}</h3>
                <p>Belum Lengkap</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <a href="{{ route('admin.pop-settings.monitoring', ['status' => 'incomplete']) }}" class="small-box-footer">
                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $stats['avg_progress'] }}%</h3>
                <p>Rata-rata Progress</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list-alt mr-2"></i>Daftar Admin POP
                </h3>
                <div class="card-tools">
                    <form action="{{ route('admin.pop-settings.monitoring') }}" method="GET" class="d-inline-flex">
                        <div class="input-group input-group-sm mr-2" style="width: 200px;">
                            <input type="text" name="search" class="form-control" placeholder="Cari POP..." value="{{ $search }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-default">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <select name="status" class="form-control form-control-sm select2 mr-2" style="width: 150px;" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="complete" {{ $statusFilter == 'complete' ? 'selected' : '' }}>Lengkap</option>
                            <option value="incomplete" {{ $statusFilter == 'incomplete' ? 'selected' : '' }}>Belum Lengkap</option>
                        </select>
                        <select name="sort" class="form-control form-control-sm select2 mr-2" style="width: 130px;" onchange="this.form.submit()">
                            <option value="name" {{ $sortBy == 'name' ? 'selected' : '' }}>Nama</option>
                            <option value="created_at" {{ $sortBy == 'created_at' ? 'selected' : '' }}>Tanggal Daftar</option>
                            <option value="status" {{ $sortBy == 'status' ? 'selected' : '' }}>Progress</option>
                        </select>
                        <select name="dir" class="form-control form-control-sm select2" style="width: 100px;" onchange="this.form.submit()">
                            <option value="asc" {{ $sortDir == 'asc' ? 'selected' : '' }}>A-Z / Asc</option>
                            <option value="desc" {{ $sortDir == 'desc' ? 'selected' : '' }}>Z-A / Desc</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                @if($popData->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Belum ada Admin POP yang terdaftar.</p>
                    <a href="{{ route('admin.users.index') }}?add=pop" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> Tambah Admin POP
                    </a>
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Admin POP</th>
                                <th style="width: 200px;">Nama POP/ISP</th>
                                <th class="text-center" style="width: 100px;">Progress</th>
                                <th class="text-center" style="width: 80px;">
                                    <i class="fas fa-building" title="Info ISP"></i>
                                </th>
                                <th class="text-center" style="width: 80px;">
                                    <i class="fas fa-file-invoice" title="Invoice"></i>
                                </th>
                                <th class="text-center" style="width: 80px;">
                                    <i class="fas fa-credit-card" title="Payment"></i>
                                </th>
                                <th class="text-center" style="width: 80px;">
                                    <i class="fas fa-bell" title="Notifikasi"></i>
                                </th>
                                <th style="width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($popData as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($item['user']->avatar)
                                        <img src="{{ Storage::url($item['user']->avatar) }}" class="img-circle mr-2" style="width: 32px; height: 32px; object-fit: cover;">
                                        @else
                                        <div class="img-circle bg-secondary mr-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="fas fa-user text-white" style="font-size: 14px;"></i>
                                        </div>
                                        @endif
                                        <div>
                                            <strong>{{ $item['user']->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $item['user']->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($item['pop_setting'] && $item['pop_setting']->isp_name)
                                        <strong>{{ $item['pop_setting']->isp_name }}</strong>
                                        @if($item['pop_setting']->pop_code)
                                        <br><small class="text-muted">{{ $item['pop_setting']->pop_code }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="progress progress-sm" style="height: 8px;">
                                        <div class="progress-bar 
                                            @if($item['progress_percent'] == 100) bg-success
                                            @elseif($item['progress_percent'] >= 50) bg-info
                                            @elseif($item['progress_percent'] > 0) bg-warning
                                            @else bg-danger @endif" 
                                            style="width: {{ $item['progress_percent'] }}%">
                                        </div>
                                    </div>
                                    <small class="{{ $item['is_complete'] ? 'text-success' : 'text-muted' }}">
                                        {{ $item['completed_count'] }}/{{ $item['total_sections'] }}
                                    </small>
                                </td>
                                <td class="text-center">
                                    @if($item['status']['isp'])
                                    <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                    @else
                                    <span class="badge badge-secondary"><i class="fas fa-times"></i></span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item['status']['invoice'])
                                    <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                    @else
                                    <span class="badge badge-secondary"><i class="fas fa-times"></i></span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item['status']['payment'])
                                    <span class="badge badge-success">
                                        {{ $item['payment_gateways']->where('is_active', true)->count() }}
                                    </span>
                                    @else
                                    <span class="badge badge-secondary"><i class="fas fa-times"></i></span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item['status']['notification'])
                                    <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                    @else
                                    <span class="badge badge-secondary"><i class="fas fa-times"></i></span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.pop-settings.view-detail', $item['user']->id) }}" 
                                           class="btn btn-sm btn-info" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.pop-settings.isp-info', ['user_id' => $item['user']->id]) }}" 
                                           class="btn btn-sm btn-primary" title="Kelola Setting">
                                            <i class="fas fa-cog"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @if($popData->count() > 0)
            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">
                            Menampilkan {{ $popData->count() }} Admin POP
                        </small>
                    </div>
                    <div class="col-md-6 text-right">
                        <small class="text-muted">
                            <i class="fas fa-building"></i> Info ISP &nbsp;
                            <i class="fas fa-file-invoice"></i> Invoice &nbsp;
                            <i class="fas fa-credit-card"></i> Payment &nbsp;
                            <i class="fas fa-bell"></i> Notifikasi
                        </small>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Legend Card -->
<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Keterangan Status</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td style="width: 30px;"><span class="badge badge-success"><i class="fas fa-check"></i></span></td>
                        <td><strong>Info ISP:</strong> Nama ISP sudah diisi</td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-success"><i class="fas fa-check"></i></span></td>
                        <td><strong>Invoice:</strong> Prefix invoice sudah dikonfigurasi</td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-success"><i class="fas fa-check"></i></span></td>
                        <td><strong>Payment:</strong> Minimal 1 payment gateway aktif</td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-success"><i class="fas fa-check"></i></span></td>
                        <td><strong>Notifikasi:</strong> Minimal 1 channel aktif (Email/WA/Telegram)</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tools mr-2"></i>Tindakan Cepat</h3>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="{{ route('admin.users.index') }}?add=pop" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-plus mr-2 text-primary"></i> Tambah Admin POP Baru
                    </a>
                    <a href="{{ route('admin.pop-settings.copy-settings') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-copy mr-2 text-info"></i> Salin Pengaturan antar POP
                    </a>
                    <a href="{{ route('admin.payment-gateways.sandbox-requests') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-clipboard-check mr-2 text-warning"></i> Review Sandbox Requests
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
