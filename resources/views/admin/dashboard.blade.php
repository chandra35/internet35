@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    <!-- Welcome & Info Cards -->
    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="card bg-gradient-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="img-circle elevation-2" style="width: 60px; height: 60px; object-fit: cover;">
                        </div>
                        <div>
                            <h5 class="mb-1"><i class="fas fa-hand-wave mr-1"></i> Selamat Datang!</h5>
                            <p class="mb-0"><strong>{{ auth()->user()->name }}</strong></p>
                            <p class="mb-0">
                                @foreach(auth()->user()->roles as $role)
                                    <span class="badge badge-light">{{ $role->name }}</span>
                                @endforeach
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card bg-gradient-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <i class="fas fa-clock fa-3x"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Waktu Server</h5>
                            <p class="mb-0" id="serverTime" style="font-size: 1.1rem;">{{ now()->format('d M Y H:i:s') }}</p>
                            <small>Timezone: {{ config('app.timezone') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card bg-gradient-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <i class="fas fa-server fa-3x"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">System Info</h5>
                            <p class="mb-0">PHP: {{ PHP_VERSION }} | Laravel: {{ app()->version() }}</p>
                            <small>Server: {{ php_uname('s') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($totalUsers) }}</h3>
                    <p>Total Users</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                @can('users.view')
                <a href="{{ route('admin.users.index') }}" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
                @else
                <span class="small-box-footer">&nbsp;</span>
                @endcan
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($activeUsers) }}</h3>
                    <p>Users Aktif</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-check"></i>
                </div>
                @can('users.view')
                <a href="{{ route('admin.users.index') }}?status=active" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
                @else
                <span class="small-box-footer">&nbsp;</span>
                @endcan
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($totalRoles) }}</h3>
                    <p>Total Roles</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-tag"></i>
                </div>
                @can('roles.view')
                <a href="{{ route('admin.roles.index') }}" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
                @else
                <span class="small-box-footer">&nbsp;</span>
                @endcan
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($todayLogins) }}</h3>
                    <p>Login Hari Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                @can('activity-logs.view')
                <a href="{{ route('admin.activity-logs.index') }}" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
                @else
                <span class="small-box-footer">&nbsp;</span>
                @endcan
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Activity Chart -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-1"></i>
                        Aktivitas 7 Hari Terakhir
                    </h3>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 250px;">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users by Role -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users mr-1"></i>
                        Users per Role
                    </h3>
                </div>
                <div class="card-body">
                    @forelse($usersByRole as $role)
                        @php
                            $colors = ['superadmin' => 'danger', 'admin' => 'primary', 'admin-pop' => 'info', 'client' => 'success'];
                            $color = $colors[$role->name] ?? 'secondary';
                            $percentage = $totalUsers > 0 ? ($role->users_count / $totalUsers) * 100 : 0;
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge badge-{{ $color }}">{{ ucfirst($role->name) }}</span>
                                <span class="text-muted">{{ $role->users_count }} users</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-{{ $color }}" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center">Belum ada data role</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Activity -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history mr-1"></i>
                        Aktivitas Terbaru
                    </h3>
                    <div class="card-tools">
                        @can('activity-logs.view')
                        <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-sm btn-primary">
                            Lihat Semua
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Module</th>
                                    <th>Description</th>
                                    <th>IP</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentActivities as $activity)
                                    <tr>
                                        <td>{{ $activity->user?->name ?? 'System' }}</td>
                                        <td>
                                            @php
                                                $actionColors = ['login' => 'success', 'logout' => 'secondary', 'login_failed' => 'danger', 'create' => 'primary', 'update' => 'info', 'delete' => 'danger'];
                                                $color = $actionColors[$activity->action] ?? 'secondary';
                                            @endphp
                                            <span class="badge badge-{{ $color }}">{{ $activity->action }}</span>
                                        </td>
                                        <td>{{ $activity->module ?? '-' }}</td>
                                        <td>{{ Str::limit($activity->description, 40) }}</td>
                                        <td><small>{{ $activity->ip_address }}</small></td>
                                        <td><small>{{ $activity->created_at->diffForHumans() }}</small></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Belum ada aktivitas
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
$(document).ready(function() {
    // Activity Chart
    const ctx = document.getElementById('activityChart');
    if (ctx) {
        const activityData = @json($activityChart);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: activityData.map(item => item.date),
                datasets: [{
                    label: 'Aktivitas',
                    data: activityData.map(item => item.count),
                    borderColor: 'rgb(60, 141, 188)',
                    backgroundColor: 'rgba(60, 141, 188, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(60, 141, 188)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14 },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.parsed.y + ' aktivitas';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Update server time
    function updateServerTime() {
        const now = new Date();
        const day = String(now.getDate()).padStart(2, '0');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const month = months[now.getMonth()];
        const year = now.getFullYear();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        document.getElementById('serverTime').textContent = `${day} ${month} ${year} ${hours}:${minutes}:${seconds}`;
    }
    
    setInterval(updateServerTime, 1000);
});
</script>
@endpush
