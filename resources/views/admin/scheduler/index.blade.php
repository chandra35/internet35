@extends('layouts.admin')

@section('title', 'Scheduler')

@section('page-title', 'Task Scheduler')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Scheduler</li>
@endsection

@push('css')
<style>
    /* Portal-style stat cards (matches customers page) */
    .stat-card {
        border-radius: 10px; padding: 14px 16px 12px; color: #fff;
        position: relative; overflow: hidden;
        transition: transform 0.18s, box-shadow 0.18s;
        display: block;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 7px 22px rgba(0,0,0,0.22); }
    .stat-card .sc-icon { position: absolute; right: 12px; top: 10px; font-size: 32px; opacity: 0.14; pointer-events: none; }
    .stat-card .sc-value { font-size: 1.6rem; font-weight: 700; line-height: 1.2; }
    .stat-card .sc-label { font-size: 0.7rem; opacity: 0.88; margin-top: 2px; }
    .stat-blue   { background: linear-gradient(135deg, #1565c0, #1976d2); }
    .stat-green  { background: linear-gradient(135deg, #1aaa55, #17c671); }
    .stat-grey   { background: linear-gradient(135deg, #495057, #6c757d); }
    .stat-indigo { background: linear-gradient(135deg, #3949ab, #5c6bc0); }
    .stat-red    { background: linear-gradient(135deg, #dc3545, #c82333); }
    .stat-teal   { background: linear-gradient(135deg, #00838f, #0097a7); }
    /* Scheduler card headers — dark blue gradient (matches customers page .card-customers) */
    .card-scheduler { border: none !important; border-radius: 10px !important; overflow: hidden; }
    .card-scheduler > .card-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        border-bottom: none; padding: 14px 20px;
    }
    .card-scheduler > .card-header .card-title { color: white; font-size: 1rem; font-weight: 600; }
    .card-scheduler > .card-header .btn { background: transparent; border-color: rgba(255,255,255,0.5); color: white; }
    .card-scheduler > .card-header .btn:hover { background: rgba(255,255,255,0.15); }
    /* Cron Monitor Card */
    .cron-card {
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .cron-card:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.13);
    }
    .cron-icon {
        width: 52px; height: 52px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; color: #fff; flex-shrink: 0;
        margin-bottom: 14px;
    }
    .ci-success { background: linear-gradient(135deg, #28a745, #20c35d); }
    .ci-warning { background: linear-gradient(135deg, #e0871a, #f4a721); }
    .ci-danger  { background: linear-gradient(135deg, #dc3545, #c82333); }
    .ci-unknown { background: linear-gradient(135deg, #6c757d, #868e96); }
    .cron-status-label {
        font-size: 1rem; font-weight: 700; margin-bottom: 4px;
    }
    .cron-status-detail {
        font-size: 0.8rem; color: #6c757d; margin-bottom: 12px;
    }
    .cron-heartbeat-row {
        display: flex; align-items: center; gap: 8px;
        padding: 8px 10px; background: #f8f9fa; border-radius: 8px;
        margin-bottom: 10px; font-size: 0.8rem;
    }
    .cron-heartbeat-dot {
        width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
    }
    .dot-success { background: #28a745; box-shadow: 0 0 0 3px rgba(40,167,69,0.25); }
    .dot-warning { background: #e0871a; box-shadow: 0 0 0 3px rgba(224,135,26,0.25); }
    .dot-danger  { background: #dc3545; box-shadow: 0 0 0 3px rgba(220,53,69,0.25); }
    .dot-unknown { background: #6c757d; }
    @keyframes dotPulse {
        0%, 100% { box-shadow: 0 0 0 3px rgba(40,167,69,0.25); }
        50% { box-shadow: 0 0 0 6px rgba(40,167,69,0.1); }
    }
    .dot-success { animation: dotPulse 2s infinite; }
    /* Circular action buttons (matches customers page .btn-action) */
    .btn-action {
        width: 30px; height: 30px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 50% !important; font-size: 0.72rem;
    }
    .btn-action.dropdown-toggle::after { display: none; }
    .task-card {
        border-left: 4px solid #6c757d;
        transition: all 0.2s ease;
    }
    .task-card.enabled {
        border-left-color: #28a745;
    }
    .task-card.disabled {
        border-left-color: #dc3545;
        opacity: 0.7;
    }
    .task-card:hover {
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    .status-running {
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    /* Smart Check Modal */
    .sc-item {
        display: flex;
        align-items: flex-start;
        padding: 10px 15px;
        border-bottom: 1px solid #f0f0f0;
        animation: scSlideIn 0.3s ease-out;
    }
    .sc-item:last-child { border-bottom: none; }
    .sc-item:hover { background: #f8f9fa; }
    .sc-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-right: 12px;
        font-size: 13px;
    }
    .sc-icon.success { background: #d4edda; color: #155724; }
    .sc-icon.danger { background: #f8d7da; color: #721c24; }
    .sc-icon.warning { background: #fff3cd; color: #856404; }
    .sc-icon.info { background: #d1ecf1; color: #0c5460; }
    .sc-content { flex: 1; min-width: 0; }
    .sc-label { font-weight: 600; font-size: 0.9rem; margin-bottom: 2px; }
    .sc-detail { font-size: 0.8rem; color: #6c757d; }
    .sc-action { flex-shrink: 0; margin-left: 10px; }
    .sc-category-header {
        background: #f4f6f9;
        padding: 8px 15px;
        font-weight: 700;
        font-size: 0.85rem;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
        animation: scSlideIn 0.2s ease-out;
    }
    .sc-summary-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    @keyframes scSlideIn {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .sc-steps-bar {
        display: flex;
        gap: 0;
        margin-bottom: 20px;
    }
    .sc-step {
        flex: 1;
        text-align: center;
        padding: 10px 5px;
        position: relative;
        font-size: 0.78rem;
        color: #adb5bd;
        transition: all 0.3s;
    }
    .sc-step .sc-step-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e9ecef;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 5px;
        font-size: 14px;
        transition: all 0.3s;
    }
    .sc-step.active .sc-step-icon {
        background: #007bff;
        color: #fff;
        box-shadow: 0 0 0 4px rgba(0,123,255,0.2);
        animation: scPulse 1.5s infinite;
    }
    .sc-step.active { color: #007bff; font-weight: 600; }
    .sc-step.done .sc-step-icon {
        background: #28a745;
        color: #fff;
    }
    .sc-step.done { color: #28a745; }
    .sc-step.error .sc-step-icon {
        background: #dc3545;
        color: #fff;
    }
    @keyframes scPulse {
        0%, 100% { box-shadow: 0 0 0 4px rgba(0,123,255,0.2); }
        50% { box-shadow: 0 0 0 8px rgba(0,123,255,0.1); }
    }
    .sc-results-area {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #e9ecef;
        border-radius: 6px;
    }
    .sc-progress-text {
        font-size: 0.85rem;
        color: #495057;
        margin-bottom: 8px;
        min-height: 22px;
    }
    #smartCheckModal .modal-body {
        padding: 20px 25px;
    }
    #smartCheckModal .modal-footer {
        flex-wrap: wrap;
        gap: 8px;
    }
</style>
@endpush

@section('content')
<!-- POP Selector for Superadmin -->
@if($popUsers && auth()->user()->hasRole('superadmin'))
<div class="card card-outline card-info mb-3">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-auto">
                <i class="fas fa-user-shield text-info fa-lg"></i>
                <strong class="ml-2">Mode Superadmin:</strong>
            </div>
            <div class="col-md-4">
                <select class="form-control select2" id="selectPop" onchange="changePop(this.value)">
                    <option value="">-- Semua POP (Global) --</option>
                    @foreach($popUsers as $pop)
                        <option value="{{ $pop->id }}" {{ $popId == $pop->id ? 'selected' : '' }}>
                            {{ $pop->name }} ({{ $pop->email }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Statistics -->
<div class="row mb-3">
    <div class="col-6 col-lg-2 mb-2 mb-lg-0">
        <div class="stat-card stat-blue">
            <i class="fas fa-tasks sc-icon"></i>
            <div class="sc-value">{{ $stats['total'] }}</div>
            <div class="sc-label">Total Task</div>
        </div>
    </div>
    <div class="col-6 col-lg-2 mb-2 mb-lg-0">
        <div class="stat-card stat-green">
            <i class="fas fa-check-circle sc-icon"></i>
            <div class="sc-value">{{ $stats['enabled'] }}</div>
            <div class="sc-label">Aktif</div>
        </div>
    </div>
    <div class="col-6 col-lg-2 mb-2 mb-lg-0">
        <div class="stat-card stat-grey">
            <i class="fas fa-pause-circle sc-icon"></i>
            <div class="sc-value">{{ $stats['disabled'] }}</div>
            <div class="sc-label">Nonaktif</div>
        </div>
    </div>
    <div class="col-6 col-lg-2 mb-2 mb-lg-0">
        <div class="stat-card stat-indigo">
            <i class="fas fa-spinner sc-icon"></i>
            <div class="sc-value">{{ $stats['running'] }}</div>
            <div class="sc-label">Berjalan</div>
        </div>
    </div>
    <div class="col-6 col-lg-2 mb-2 mb-lg-0">
        <div class="stat-card stat-red">
            <i class="fas fa-exclamation-triangle sc-icon"></i>
            <div class="sc-value">{{ $stats['failed'] }}</div>
            <div class="sc-label">Gagal</div>
        </div>
    </div>
    <div class="col-6 col-lg-2 mb-2 mb-lg-0">
        <div class="stat-card stat-teal" style="cursor:pointer;" data-toggle="modal" data-target="#createModal">
            <i class="fas fa-plus-circle sc-icon"></i>
            <div class="sc-value"><i class="fas fa-plus"></i></div>
            <div class="sc-label">Tambah Task</div>
        </div>
    </div>
</div>

<!-- Auto Isolir Configuration -->
<div class="card card-outline {{ $autoSuspendTask?->is_enabled ? 'card-success' : 'card-warning' }} mb-3">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-shield-alt mr-2"></i>Auto Isolir Pelanggan ke MikroTik
        </h3>
        <div class="card-tools">
            @if($autoSuspendTask)
                <span class="badge badge-{{ $autoSuspendTask->is_enabled ? 'success' : 'secondary' }}">
                    {{ $autoSuspendTask->is_enabled ? 'Aktif' : 'Nonaktif' }}
                </span>
            @else
                <span class="badge badge-warning">Belum dikonfigurasi</span>
            @endif
        </div>
    </div>
    <form action="{{ route('admin.scheduler.auto-suspend.configure') }}" method="POST">
        @csrf
        <div class="card-body py-3">
            <div class="row align-items-end">
                <div class="col-md-5">
                    <label class="mb-1">Jadwal pemeriksaan</label>
                    <select name="schedule" class="form-control form-control-sm">
                        @foreach($schedulePresets as $key => $label)
                            <option value="{{ $key }}" {{ ($autoSuspendTask?->schedule ?? 'daily') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 mt-2 mt-md-0">
                    <div class="custom-control custom-switch pt-2">
                        <input type="hidden" name="is_enabled" value="0">
                        <input type="checkbox" class="custom-control-input" id="autoSuspendEnabled" name="is_enabled" value="1"
                               {{ !$autoSuspendTask || $autoSuspendTask->is_enabled ? 'checked' : '' }}>
                        <label class="custom-control-label" for="autoSuspendEnabled">Aktifkan auto isolir</label>
                    </div>
                    <small class="text-muted d-block mt-2">Hanya pelanggan aktif dengan flag <code>auto_isolir</code>, invoice terlambat, dan masa tenggang terlewati yang dikirim ke MikroTik.</small>
                </div>
                <div class="col-md-2 mt-3 mt-md-0 text-md-right">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save mr-1"></i>Simpan
                    </button>
                </div>
            </div>
            <div class="alert alert-light border mb-0 mt-3 py-2 small">
                <i class="fas fa-info-circle text-info mr-1"></i>
                Saat memenuhi syarat, aplikasi mengubah profile PPP secret menjadi profile isolir lalu memutus sesi aktif agar perangkat login ulang. Secret tidak di-disable. Cron server Laravel tetap harus menjalankan <code>php artisan schedule:run</code> setiap menit.
            </div>
        </div>
    </form>
</div>

<!-- Smart Check Trigger -->
<div class="callout callout-primary mb-3" style="cursor:pointer;" onclick="openSmartCheck()">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-stethoscope mr-2"></i>Smart Check — Diagnostik Sistem</h5>
            <small class="text-muted">Cek semua komponen billing, notifikasi, dan server dalam sekali klik</small>
        </div>
        <button class="btn btn-primary">
            <i class="fas fa-play mr-1"></i> Mulai Cek
        </button>
    </div>
</div>

<div class="row">
    <!-- Tasks List -->
    <div class="col-md-8">
        <div class="card card-scheduler shadow-sm">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clock mr-2"></i>Scheduled Tasks
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.scheduler.logs') }}" class="btn btn-sm">
                        <i class="fas fa-history mr-1"></i> Semua Log
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                @forelse($tasks as $task)
                <div class="card task-card m-3 {{ $task->is_enabled ? 'enabled' : 'disabled' }}">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-1">
                                    @if($task->last_status === 'running')
                                        <i class="fas fa-spinner fa-spin text-info mr-2 status-running"></i>
                                    @elseif($task->last_status === 'success')
                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                    @elseif($task->last_status === 'failed')
                                        <i class="fas fa-times-circle text-danger mr-2"></i>
                                    @else
                                        <i class="fas fa-clock text-secondary mr-2"></i>
                                    @endif
                                    {{ $task->name }}
                                </h5>
                                <p class="mb-1 text-muted small">
                                    <code>{{ $task->command }}</code>
                                </p>
                                <p class="mb-0 text-muted small">
                                    {{ $task->description }}
                                </p>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    {{ $task->schedule_label }}
                                </div>
                                @if($task->last_run_at)
                                <div class="small text-muted mt-1">
                                    <i class="fas fa-history mr-1"></i>
                                    Terakhir: {{ $task->last_run_at->diffForHumans() }}
                                </div>
                                @endif
                                @if($task->next_run_at && $task->is_enabled)
                                <div class="small text-muted mt-1">
                                    <i class="fas fa-forward mr-1"></i>
                                    Berikutnya: {{ $task->next_run_at->diffForHumans() }}
                                </div>
                                @endif
                            </div>
                            <div class="col-md-3 text-right">
                                <div class="d-flex justify-content-end flex-wrap" style="gap:4px;">
                                    <form action="{{ route('admin.scheduler.run', $task) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-action" 
                                                title="Jalankan Sekarang"
                                                onclick="return confirm('Jalankan task ini sekarang?')">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.scheduler.toggle', $task) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn {{ $task->is_enabled ? 'btn-warning' : 'btn-success' }} btn-action" 
                                                title="{{ $task->is_enabled ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <i class="fas {{ $task->is_enabled ? 'fa-pause' : 'fa-play' }}"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.scheduler.show', $task) }}" class="btn btn-info btn-action" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.scheduler.edit', $task) }}" class="btn btn-secondary btn-action" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.scheduler.destroy', $task) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Hapus task ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-action" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="fas fa-clock fa-4x text-muted mb-3"></i>
                    <p class="text-muted">Belum ada scheduled task.</p>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createModal">
                        <i class="fas fa-plus mr-1"></i> Tambah Task Pertama
                    </button>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    
    <!-- Recent Logs -->
    <div class="col-md-4">
        <div class="card card-scheduler shadow-sm">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-2"></i>Log Terbaru
                </h3>
            </div>
            <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                <ul class="list-group list-group-flush">
                    @forelse($recentLogs as $log)
                    <li class="list-group-item py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge badge-{{ $log->status_color }}">{{ $log->status_label }}</span>
                                <strong class="ml-1">{{ $log->task?->name ?? 'Task Dihapus' }}</strong>
                            </div>
                            <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                        </div>
                        <div class="small text-muted mt-1">
                            @if($log->duration)
                                <i class="fas fa-stopwatch mr-1"></i>{{ $log->formatted_duration }}
                            @endif
                            <span class="ml-2">
                                <i class="fas fa-user mr-1"></i>
                                {{ $log->triggered_by === 'manual' ? $log->triggeredByUser?->name ?? 'User' : 'Scheduler' }}
                            </span>
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-4">
                        Belum ada log
                    </li>
                    @endforelse
                </ul>
            </div>
            @if($recentLogs->count() > 0)
            <div class="card-footer">
                <a href="{{ route('admin.scheduler.logs') }}" class="btn btn-block btn-sm btn-outline-info">
                    Lihat Semua Log
                </a>
            </div>
            @endif
        </div>
        
        <!-- Cron Job Monitor -->
        <div class="card card-scheduler cron-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-heartbeat mr-2"></i>Cron Job Monitor
                </h3>
            </div>
            <div class="card-body" style="padding: 18px 20px;">

                {{-- Icon + Status --}}
                <div class="d-flex align-items-start">
                    <div class="cron-icon ci-{{ $cronStatus['color'] === 'success' ? 'success' : ($cronStatus['color'] === 'warning' ? 'warning' : ($cronStatus['color'] === 'danger' ? 'danger' : 'unknown')) }}"
                         id="cronIconWrapper">
                        <i class="fas {{ $cronStatus['icon'] }}" id="cronIcon"></i>
                    </div>
                    <div class="ml-3 flex-grow-1">
                        <div class="cron-status-label" id="cronStatusLabel">{{ $cronStatus['label'] }}</div>
                        <div class="cron-status-detail" id="cronStatusDetail">{{ $cronStatus['detail'] }}</div>
                    </div>
                </div>

                {{-- Heartbeat row --}}
                <div class="cron-heartbeat-row" id="cronHeartbeatRow">
                    <div class="cron-heartbeat-dot dot-{{ $cronStatus['color'] === 'success' ? 'success' : ($cronStatus['color'] === 'warning' ? 'warning' : ($cronStatus['color'] === 'danger' ? 'danger' : 'unknown')) }}"
                         id="cronDot"></div>
                    <div>
                        @if($cronStatus['last_heartbeat'])
                            <span id="cronLastText">Heartbeat: <strong>{{ $cronStatus['last_heartbeat_full'] }}</strong></span>
                            <span class="text-muted ml-2" id="cronAgoText">{{ $cronStatus['last_heartbeat_human'] }}</span>
                        @else
                            <span id="cronLastText" class="text-muted">Belum ada data heartbeat</span>
                        @endif
                    </div>
                </div>

                {{-- Cron command --}}
                <div class="mb-2">
                    <div class="small text-muted mb-1"><i class="fas fa-terminal mr-1"></i> Crontab server:</div>
                    <pre class="bg-dark text-white p-2 rounded" style="font-size: 0.72rem; white-space: pre-wrap; word-break: break-all; margin-bottom:0;">{{ $cronStatus['cron_command'] }}</pre>
                </div>

                {{-- Footer: refresh info + button --}}
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted" id="cronRefreshText">
                        <i class="fas fa-sync-alt mr-1"></i> Auto-refresh 60 detik
                    </small>
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshCronStatus()" id="cronRefreshBtn">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.scheduler.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-plus mr-2"></i>Tambah Scheduled Task
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama Task <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       placeholder="Contoh: Generate Invoice Bulanan">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Command <span class="text-danger">*</span></label>
                                <select name="command" class="form-control" id="commandSelect" required>
                                    <option value="">-- Pilih Command --</option>
                                    @foreach($availableCommands as $cmd => $info)
                                        <option value="{{ $cmd }}" 
                                                data-desc="{{ $info['description'] }}"
                                                data-schedule="{{ $info['recommended_schedule'] }}">
                                            {{ $info['name'] }} ({{ $cmd }})
                                        </option>
                                    @endforeach
                                    <option value="custom">Command Kustom...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" id="customCommandGroup" style="display:none;">
                        <label>Command Kustom</label>
                        <input type="text" id="customCommand" class="form-control" 
                               placeholder="Contoh: backup:run --daily">
                    </div>
                    
                    <div class="form-group">
                        <label>Jadwal <span class="text-danger">*</span></label>
                        <select name="schedule" class="form-control" id="scheduleSelect" required>
                            @foreach($schedulePresets as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2" id="descriptionField"
                                  placeholder="Deskripsi task..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Timeout (detik)</label>
                                <input type="number" name="timeout" class="form-control" 
                                       value="3600" min="60" max="86400">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="withoutOverlapping" name="without_overlapping" value="1" checked>
                                    <label class="custom-control-label" for="withoutOverlapping">
                                        Cegah Overlap
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="runInBackground" name="run_in_background" value="1">
                                    <label class="custom-control-label" for="runInBackground">
                                        Background
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Smart Check Modal -->
<div class="modal fade" id="smartCheckModal" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-stethoscope mr-2"></i>Smart Check — Diagnostik Sistem
                </h5>
                <button type="button" class="close text-white" id="scCloseBtn" data-dismiss="modal" disabled>
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Step Progress Indicators -->
                <div class="sc-steps-bar" id="scStepsBar"></div>

                <!-- Progress Bar -->
                <div class="sc-progress-text" id="scProgressText">Mempersiapkan diagnostik...</div>
                <div class="progress mb-3" style="height: 6px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="scProgressBar" style="width: 0%; transition: width 0.4s ease;"></div>
                </div>

                <!-- Results Area -->
                <div class="sc-results-area" id="scResultsBody"></div>
            </div>
            <div class="modal-footer" id="scModalFooter" style="display:none;">
                <div class="w-100 d-flex justify-content-between align-items-center flex-wrap">
                    <div id="scSummaryBadges"></div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mr-1" onclick="openSmartCheck()">
                            <i class="fas fa-sync-alt mr-1"></i> Ulangi
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="scFixAllBtn" onclick="fixAll()" style="display:none;">
                            <i class="fas fa-magic mr-1"></i> Perbaiki Semua
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
function changePop(popId) {
    window.location.href = '{{ route('admin.scheduler.index') }}?pop_id=' + popId;
}

$(document).ready(function() {
    // Handle command selection
    $('#commandSelect').change(function() {
        const selected = $(this).find(':selected');
        const value = $(this).val();
        
        if (value === 'custom') {
            $('#customCommandGroup').show();
            $('#customCommand').attr('required', true);
        } else {
            $('#customCommandGroup').hide();
            $('#customCommand').attr('required', false);
            
            // Auto-fill description and schedule
            if (selected.data('desc')) {
                $('#descriptionField').val(selected.data('desc'));
            }
            if (selected.data('schedule')) {
                $('#scheduleSelect').val(selected.data('schedule'));
            }
        }
    });
    
    // Handle custom command
    $('#customCommand').on('input', function() {
        $('input[name="command"]').val($(this).val());
    });
});

/**
 * Smart Check — Streaming Modal
 */
let scAllChecks = [];
let scSteps = [];

function openSmartCheck() {
    scAllChecks = [];
    scSteps = [];
    $('#scStepsBar').html('');
    $('#scResultsBody').html('');
    $('#scProgressBar').css('width', '0%').removeClass('bg-success bg-danger');
    $('#scProgressText').text('Mempersiapkan diagnostik...');
    $('#scModalFooter').hide();
    $('#scSummaryBadges').html('');
    $('#scFixAllBtn').hide();
    $('#scCloseBtn').attr('disabled', true);
    $('#smartCheckModal').modal('show');

    // Step 1: fetch available steps
    $.get('{{ route("admin.scheduler.smart-check") }}', function(resp) {
        if (!resp.success || !resp.steps) {
            showSmartCheckError('Gagal memuat langkah diagnostik.');
            return;
        }
        scSteps = resp.steps;
        renderStepIndicators();
        runNextStep(0);
    }).fail(function() {
        showSmartCheckError('Tidak dapat terhubung ke server.');
    });
}

function renderStepIndicators() {
    let html = '';
    scSteps.forEach(function(step, i) {
        html += `<div class="sc-step" id="scStep_${step.key}">
            <div class="sc-step-icon"><i class="fas ${step.icon}"></i></div>
            <div>${step.label}</div>
        </div>`;
    });
    $('#scStepsBar').html(html);
}

function runNextStep(index) {
    if (index >= scSteps.length) {
        finishSmartCheck();
        return;
    }

    const step = scSteps[index];
    const pct = Math.round(((index) / scSteps.length) * 100);
    const pctNext = Math.round(((index + 1) / scSteps.length) * 100);

    // Mark current step as active
    $('#scStep_' + step.key).addClass('active');
    $('#scProgressBar').css('width', pct + '%');
    $('#scProgressText').html(`<i class="fas fa-spinner fa-spin mr-1"></i> Memeriksa <strong>${step.label}</strong>...`);

    $.get('{{ route("admin.scheduler.smart-check") }}', { step: step.key }, function(resp) {
        // Mark step as done
        $('#scStep_' + step.key).removeClass('active').addClass('done');
        $('#scProgressBar').css('width', pctNext + '%');

        if (resp.success && resp.checks) {
            scAllChecks = scAllChecks.concat(resp.checks);
            appendCheckResults(step, resp.checks);
        }

        // Small delay for visual streaming effect, then next step
        setTimeout(function() {
            runNextStep(index + 1);
        }, 200);
    }).fail(function() {
        $('#scStep_' + step.key).removeClass('active').addClass('error');
        appendStepError(step);
        setTimeout(function() {
            runNextStep(index + 1);
        }, 200);
    });
}

function appendCheckResults(step, checks) {
    const $body = $('#scResultsBody');
    const iconMap = {success: 'fa-check', danger: 'fa-times', warning: 'fa-exclamation-triangle', info: 'fa-info'};

    // Category header
    $body.append(`<div class="sc-category-header"><i class="fas ${step.icon} mr-1"></i> ${step.label}</div>`);

    if (checks.length === 0) {
        $body.append(`<div class="sc-item">
            <div class="sc-icon success"><i class="fas fa-check"></i></div>
            <div class="sc-content">
                <div class="sc-label">Semua OK</div>
                <div class="sc-detail">Tidak ditemukan masalah pada ${step.label}.</div>
            </div>
        </div>`);
        return;
    }

    checks.forEach(function(item, idx) {
        const delay = idx * 80;
        const $item = $(`<div class="sc-item" style="opacity:0;">
            <div class="sc-icon ${item.status}">
                <i class="fas ${iconMap[item.status] || 'fa-circle'}"></i>
            </div>
            <div class="sc-content">
                <div class="sc-label">${item.label}</div>
                <div class="sc-detail">${item.detail}</div>
            </div>
            ${item.fixable ? `<div class="sc-action">
                <button class="btn btn-sm btn-outline-primary" onclick="fixItem('${item.fix_action}', ${JSON.stringify(JSON.stringify(item.fix_data))})">
                    <i class="fas fa-wrench mr-1"></i>Perbaiki
                </button>
            </div>` : ''}
        </div>`);
        $body.append($item);
        setTimeout(function() {
            $item.css({opacity: 1, transition: 'opacity 0.3s ease, transform 0.3s ease', transform: 'translateY(0)'});
        }, delay);
    });

    // Auto-scroll to bottom
    $body.scrollTop($body[0].scrollHeight);
}

function appendStepError(step) {
    const $body = $('#scResultsBody');
    $body.append(`<div class="sc-category-header bg-danger text-white"><i class="fas ${step.icon} mr-1"></i> ${step.label}</div>`);
    $body.append(`<div class="sc-item">
        <div class="sc-icon danger"><i class="fas fa-times"></i></div>
        <div class="sc-content">
            <div class="sc-label">Gagal memeriksa</div>
            <div class="sc-detail">Terjadi error saat memeriksa ${step.label}. Coba ulangi.</div>
        </div>
    </div>`);
}

function finishSmartCheck() {
    // Progress complete
    const hasDanger = scAllChecks.some(c => c.status === 'danger');
    $('#scProgressBar')
        .css('width', '100%')
        .removeClass('progress-bar-animated progress-bar-striped')
        .addClass(hasDanger ? 'bg-danger' : 'bg-success');
    $('#scProgressText').html(hasDanger
        ? '<i class="fas fa-exclamation-triangle text-danger mr-1"></i> <strong>Ditemukan masalah yang perlu diperbaiki</strong>'
        : '<i class="fas fa-check-circle text-success mr-1"></i> <strong>Diagnostik selesai</strong>');

    // Enable close
    $('#scCloseBtn').attr('disabled', false);

    // Summary badges
    const counts = {success: 0, warning: 0, danger: 0, info: 0, fixable: 0};
    scAllChecks.forEach(function(c) {
        counts[c.status] = (counts[c.status] || 0) + 1;
        if (c.fixable) counts.fixable++;
    });

    let badgeHtml = '';
    if (counts.success) badgeHtml += `<span class="sc-summary-badge bg-success text-white"><i class="fas fa-check"></i> ${counts.success} OK</span> `;
    if (counts.warning) badgeHtml += `<span class="sc-summary-badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> ${counts.warning} Peringatan</span> `;
    if (counts.danger)  badgeHtml += `<span class="sc-summary-badge bg-danger text-white"><i class="fas fa-times"></i> ${counts.danger} Masalah</span> `;
    if (counts.info)    badgeHtml += `<span class="sc-summary-badge bg-info text-white"><i class="fas fa-info"></i> ${counts.info} Info</span> `;
    $('#scSummaryBadges').html(badgeHtml);

    if (counts.fixable > 0) {
        $('#scFixAllBtn').show().html(`<i class="fas fa-magic mr-1"></i> Perbaiki Semua <span class="badge badge-light">${counts.fixable}</span>`);
    }

    $('#scModalFooter').show();
}

function showSmartCheckError(msg) {
    $('#scProgressBar').css('width', '100%').removeClass('progress-bar-animated').addClass('bg-danger');
    $('#scProgressText').html(`<i class="fas fa-times-circle text-danger mr-1"></i> ${msg}`);
    $('#scCloseBtn').attr('disabled', false);
    $('#scModalFooter').show();
}

function fixItem(action, dataJson) {
    const data = JSON.parse(dataJson);
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Jalankan auto-fix ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Perbaiki',
        cancelButtonText: 'Batal'
    }).then(function(result) {
        if (result.isConfirmed) {
            doFix(action, data);
        }
    });
}

function fixAll() {
    Swal.fire({
        title: 'Perbaiki Semua?',
        text: 'Semua masalah yang bisa diperbaiki otomatis akan dijalankan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Perbaiki Semua',
        cancelButtonText: 'Batal'
    }).then(function(result) {
        if (result.isConfirmed) {
            doFix('fix_all', {});
        }
    });
}

function doFix(action, data) {
    $.ajax({
        url: '{{ route("admin.scheduler.smart-check.fix") }}',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}', action: action, data: data },
        beforeSend: function() {
            Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); }});
        },
        success: function(resp) {
            Swal.close();
            if (resp.success) {
                toastr.success(resp.message);
                // Re-run smart check in the modal
                openSmartCheck();
            } else {
                toastr.error(resp.message || 'Gagal memperbaiki.');
            }
        },
        error: function() {
            Swal.close();
            toastr.error('Terjadi kesalahan server.');
        }
    });
}

/**
 * Cron Status Monitor
 */
var cronRefreshTimer = null;
var cronCountdown = 60;

const CRON_ICON_MAP = {
    success: { iconClass: 'ci-success', dotClass: 'dot-success', icon: 'fa-check-circle' },
    warning: { iconClass: 'ci-warning', dotClass: 'dot-warning', icon: 'fa-exclamation-triangle' },
    error:   { iconClass: 'ci-danger',  dotClass: 'dot-danger',  icon: 'fa-times-circle' },
    danger:  { iconClass: 'ci-danger',  dotClass: 'dot-danger',  icon: 'fa-times-circle' },
    unknown: { iconClass: 'ci-unknown', dotClass: 'dot-unknown', icon: 'fa-question-circle' },
};

function applyCronStatus(data) {
    const map = CRON_ICON_MAP[data.status] || CRON_ICON_MAP['unknown'];
    $('#cronIconWrapper').removeClass('ci-success ci-warning ci-danger ci-unknown').addClass(map.iconClass);
    $('#cronIcon').attr('class', 'fas ' + map.icon);
    $('#cronDot').removeClass('dot-success dot-warning dot-danger dot-unknown').addClass(map.dotClass);
    $('#cronStatusLabel').text(data.label);
    $('#cronStatusDetail').text(data.detail);
    if (data.last_heartbeat) {
        $('#cronLastText').html('Heartbeat: <strong>' + (data.last_heartbeat_full || data.last_heartbeat) + '</strong>');
        $('#cronAgoText').text(data.last_heartbeat_human || '');
    } else {
        $('#cronLastText').text('Belum ada data heartbeat');
        $('#cronAgoText').text('');
    }
    $('#cronRefreshBtn').html('<i class="fas fa-sync-alt"></i>').prop('disabled', false);
}

function refreshCronStatus() {
    clearInterval(cronRefreshTimer);
    $('#cronRefreshBtn').html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
    $('#cronRefreshText').html('<i class="fas fa-sync-alt fa-spin mr-1"></i> Memperbarui...');

    $.get('{{ route("admin.scheduler.cron-status") }}', function(data) {
        applyCronStatus(data);
        startCronCountdown();
    }).fail(function() {
        $('#cronRefreshText').html('<i class="fas fa-exclamation-triangle text-danger mr-1"></i> Gagal memuat');
        $('#cronRefreshBtn').html('<i class="fas fa-sync-alt"></i>').prop('disabled', false);
        startCronCountdown();
    });
}

function startCronCountdown() {
    cronCountdown = 60;
    clearInterval(cronRefreshTimer);
    cronRefreshTimer = setInterval(function() {
        cronCountdown--;
        if (cronCountdown <= 0) {
            refreshCronStatus();
        } else {
            $('#cronRefreshText').html('<i class="fas fa-clock mr-1"></i> Refresh dalam <strong>' + cronCountdown + 's</strong>');
        }
    }, 1000);
}

// Start auto-refresh countdown on page load
$(function() {
    startCronCountdown();
});
</script>
@endpush
