@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@push('css')
<style>
    /* Portal-style stat cards */
    .stat-card {
        border-radius: 10px; padding: 14px 16px 12px; color: #fff;
        position: relative; overflow: hidden; cursor: default;
        transition: transform 0.18s, box-shadow 0.18s;
        text-decoration: none; display: block; height: 100%;
    }
    a.stat-card { cursor: pointer; }
    a.stat-card:hover { transform: translateY(-3px); box-shadow: 0 7px 22px rgba(0,0,0,0.22); color: #fff; text-decoration: none; }
    .stat-card .sc-icon  { position: absolute; right: 12px; top: 10px; font-size: 32px; opacity: 0.14; pointer-events: none; }
    .stat-card .sc-value { font-size: 1.7rem; font-weight: 700; line-height: 1.2; }
    .stat-card .sc-label { font-size: 0.7rem; opacity: 0.88; margin-top: 2px; }
    .stat-card .sc-divider { border-top: 1px solid rgba(255,255,255,0.2); margin-top: 8px; padding-top: 7px; }
    .stat-card .sc-row { display: flex; justify-content: space-between; font-size: 0.63rem; opacity: 0.9; line-height: 1.75; }
    .stat-card .sc-row strong { font-weight: 600; opacity: 1; }
    .stat-card .sc-link { display: block; color: rgba(255,255,255,0.72); font-size: 0.65rem; margin-top: 6px; border-top: 1px solid rgba(255,255,255,0.18); padding-top: 5px; }
    .stat-card .sc-link:hover { color: #fff; }
    .stat-blue   { background: linear-gradient(135deg, #1565c0, #1976d2); }
    .stat-green  { background: linear-gradient(135deg, #1aaa55, #17c671); }
    .stat-yellow { background: linear-gradient(135deg, #e0871a, #f4a721); }
    .stat-red    { background: linear-gradient(135deg, #dc3545, #c82333); }
    .stat-teal   { background: linear-gradient(135deg, #00838f, #0097a7); }
</style>
@endpush

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
    <div class="row mb-3">
        <!-- Total Pelanggan -->
        <div class="col-lg-4 col-md-6 mb-3">
            <a href="{{ route('admin.customers.index') }}" class="stat-card stat-blue">
                <i class="fas fa-users sc-icon"></i>
                <div class="sc-value">{{ number_format($totalCustomers) }}</div>
                <div class="sc-label">Total Pelanggan</div>
                <div class="sc-divider">
                    <div class="sc-row">
                        <span>Pendapatan Bulan Ini</span>
                        <strong>Rp {{ number_format($paidThisMonthAmount, 0, ',', '.') }}</strong>
                    </div>
                    <div class="sc-row">
                        <span>
                            Seharusnya Diterima
                            <i id="toggleRevIcon" class="fas fa-eye-slash ml-1" style="opacity:0.7; cursor:pointer; font-size:0.7rem;" onclick="event.preventDefault(); toggleExpectedRevenue();"></i>
                        </span>
                        <strong id="expectedRevVal">Rp {{ number_format($expectedRevenue, 0, ',', '.') }}</strong>
                    </div>
                    <div class="sc-row">
                        <span>Sudah Terbayar</span>
                        <strong>{{ number_format($paidThisMonthCount) }} invoice</strong>
                    </div>
                    <div class="sc-row">
                        <span>Belum Bayar</span>
                        <strong>{{ number_format($pendingInvoicesCount) }} invoice</strong>
                    </div>
                </div>
            </a>
        </div>

        <!-- Belum Bayar -->
        <div class="col-lg-4 col-md-6 mb-3">
            <a href="{{ route('admin.invoices.index', ['status' => 'pending']) }}" class="stat-card stat-yellow">
                <i class="fas fa-file-invoice-dollar sc-icon"></i>
                <div class="sc-value">{{ number_format($pendingInvoicesCount) }}</div>
                <div class="sc-label">Invoice Belum Dibayar</div>
                <div class="sc-divider">
                    <div class="sc-row">
                        <span>Total Tagihan</span>
                        <strong>Rp {{ number_format($pendingInvoicesAmount, 0, ',', '.') }}</strong>
                    </div>
                    <div class="sc-row">
                        <span>Pelanggan Aktif</span>
                        <strong>{{ number_format($activeCustomers) }} pelanggan</strong>
                    </div>
                </div>
                <span class="sc-link">Lihat semua invoice →</span>
            </a>
        </div>

        <!-- Isolir -->
        <div class="col-lg-4 col-md-6 mb-3">
            <a href="{{ route('admin.customers.index', ['status' => 'suspended']) }}" class="stat-card stat-red">
                <i class="fas fa-ban sc-icon"></i>
                <div class="sc-value">{{ number_format($suspendedCustomers) }}</div>
                <div class="sc-label">Pelanggan Isolir</div>
                <div class="sc-divider">
                    <div class="sc-row">
                        <span>Dari total pelanggan</span>
                        <strong>{{ $totalCustomers > 0 ? number_format($suspendedCustomers / $totalCustomers * 100, 1) : 0 }}%</strong>
                    </div>
                    <div class="sc-row">
                        <span>Aktif</span>
                        <strong>{{ number_format($activeCustomers) }} pelanggan</strong>
                    </div>
                </div>
                <span class="sc-link">Lihat pelanggan isolir →</span>
            </a>
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
var revealedRev = false;
var revActual = 'Rp {{ number_format($expectedRevenue, 0, ',', '.') }}';
function toggleExpectedRevenue() {
    revealedRev = !revealedRev;
    document.getElementById('expectedRevVal').textContent = revealedRev ? revActual : '••••••••';
    var icon = document.getElementById('toggleRevIcon');
    icon.classList.toggle('fa-eye-slash', !revealedRev);
    icon.classList.toggle('fa-eye', revealedRev);
}
// Hidden by default on load
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('expectedRevVal').textContent = '••••••••';
});
</script>
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
