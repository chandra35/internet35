@extends('layouts.admin')

@section('title', 'Scheduler')

@section('page-title', 'Task Scheduler')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Scheduler</li>
@endsection

@push('css')
<style>
    .stat-card {
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
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
<div class="row">
    <div class="col-lg-2 col-6">
        <div class="small-box bg-info stat-card">
            <div class="inner">
                <h3>{{ $stats['total'] }}</h3>
                <p>Total Task</p>
            </div>
            <div class="icon">
                <i class="fas fa-tasks"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-success stat-card">
            <div class="inner">
                <h3>{{ $stats['enabled'] }}</h3>
                <p>Aktif</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-secondary stat-card">
            <div class="inner">
                <h3>{{ $stats['disabled'] }}</h3>
                <p>Nonaktif</p>
            </div>
            <div class="icon">
                <i class="fas fa-pause-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-primary stat-card">
            <div class="inner">
                <h3>{{ $stats['running'] }}</h3>
                <p>Berjalan</p>
            </div>
            <div class="icon">
                <i class="fas fa-spinner"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-danger stat-card">
            <div class="inner">
                <h3>{{ $stats['failed'] }}</h3>
                <p>Gagal</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-teal stat-card">
            <div class="inner">
                <h3><i class="fas fa-plus"></i></h3>
                <p>Tambah Task</p>
            </div>
            <div class="icon">
                <i class="fas fa-plus-circle"></i>
            </div>
            <a href="#" class="small-box-footer" data-toggle="modal" data-target="#createModal">
                Tambah <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tasks List -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clock mr-2"></i>Scheduled Tasks
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.scheduler.logs') }}" class="btn btn-info btn-sm">
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
                                <div class="btn-group">
                                    <form action="{{ route('admin.scheduler.run', $task) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm" 
                                                title="Jalankan Sekarang"
                                                onclick="return confirm('Jalankan task ini sekarang?')">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.scheduler.toggle', $task) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn {{ $task->is_enabled ? 'btn-warning' : 'btn-success' }} btn-sm" 
                                                title="{{ $task->is_enabled ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <i class="fas {{ $task->is_enabled ? 'fa-pause' : 'fa-play' }}"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.scheduler.show', $task) }}" class="btn btn-info btn-sm" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.scheduler.edit', $task) }}" class="btn btn-secondary btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.scheduler.destroy', $task) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Hapus task ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
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
        <div class="card">
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
        
        <!-- Quick Info -->
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title text-white">
                    <i class="fas fa-info-circle mr-2"></i>Informasi
                </h3>
            </div>
            <div class="card-body">
                <p class="small mb-2">
                    <strong>Catatan:</strong> Scheduler membutuhkan cron job di server production:
                </p>
                <pre class="bg-dark text-white p-2 rounded small">* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1</pre>
                <p class="small mb-0 mt-2">
                    <strong>Untuk Windows (development):</strong><br>
                    Jalankan <code>php artisan schedule:work</code>
                </p>
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
@endsection

@push('scripts')
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
</script>
@endpush
