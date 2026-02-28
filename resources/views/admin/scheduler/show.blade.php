@extends('layouts.admin')

@section('title', 'Detail Task')

@section('page-title', $task->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.scheduler.index') }}">Scheduler</a></li>
    <li class="breadcrumb-item active">{{ $task->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <!-- Task Info -->
        <div class="card">
            <div class="card-header bg-{{ $task->is_enabled ? 'success' : 'secondary' }}">
                <h3 class="card-title text-white">
                    <i class="fas fa-info-circle mr-2"></i>Informasi Task
                </h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted" width="120">Status</td>
                        <td>
                            <span class="badge badge-{{ $task->is_enabled ? 'success' : 'secondary' }}">
                                {{ $task->is_enabled ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Command</td>
                        <td><code>{{ $task->command }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Jadwal</td>
                        <td>{{ $task->schedule_label }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Timeout</td>
                        <td>{{ number_format($task->timeout) }} detik</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Run</td>
                        <td>{{ number_format($task->run_count) }}x</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Gagal</td>
                        <td>{{ number_format($task->failure_count) }}x</td>
                    </tr>
                </table>
                
                @if($task->description)
                <hr>
                <p class="mb-0 text-muted small">{{ $task->description }}</p>
                @endif
            </div>
            <div class="card-footer">
                <form action="{{ route('admin.scheduler.run', $task) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm" 
                            onclick="return confirm('Jalankan task ini sekarang?')">
                        <i class="fas fa-play mr-1"></i> Jalankan
                    </button>
                </form>
                <form action="{{ route('admin.scheduler.toggle', $task) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-{{ $task->is_enabled ? 'warning' : 'success' }} btn-sm">
                        <i class="fas {{ $task->is_enabled ? 'fa-pause' : 'fa-play' }} mr-1"></i>
                        {{ $task->is_enabled ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
                <a href="{{ route('admin.scheduler.edit', $task) }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
            </div>
        </div>
        
        <!-- Last Run Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clock mr-2"></i>Status Terakhir
                </h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    @if($task->last_status === 'running')
                        <i class="fas fa-spinner fa-spin fa-3x text-info"></i>
                        <h5 class="mt-2 text-info">Sedang Berjalan</h5>
                    @elseif($task->last_status === 'success')
                        <i class="fas fa-check-circle fa-3x text-success"></i>
                        <h5 class="mt-2 text-success">Berhasil</h5>
                    @elseif($task->last_status === 'failed')
                        <i class="fas fa-times-circle fa-3x text-danger"></i>
                        <h5 class="mt-2 text-danger">Gagal</h5>
                    @else
                        <i class="fas fa-clock fa-3x text-secondary"></i>
                        <h5 class="mt-2 text-secondary">Belum Pernah Dijalankan</h5>
                    @endif
                </div>
                
                @if($task->last_run_at)
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">Terakhir Run</td>
                        <td>{{ $task->last_run_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    @if($task->next_run_at && $task->is_enabled)
                    <tr>
                        <td class="text-muted">Berikutnya</td>
                        <td>{{ $task->next_run_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    @endif
                </table>
                @endif
                
                @if($task->last_output)
                <hr>
                <label class="text-muted small">Output Terakhir:</label>
                <pre class="bg-dark text-white p-2 rounded small" style="max-height: 200px; overflow-y: auto;">{{ $task->last_output }}</pre>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Run History -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-2"></i>Riwayat Eksekusi
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Waktu</th>
                                <th>Status</th>
                                <th>Durasi</th>
                                <th>Dipicu Oleh</th>
                                <th width="100">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>
                                    <div>{{ $log->started_at->format('d/m/Y H:i:s') }}</div>
                                    <small class="text-muted">{{ $log->started_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $log->status_color }}">
                                        {{ $log->status_label }}
                                    </span>
                                </td>
                                <td>{{ $log->formatted_duration }}</td>
                                <td>
                                    @if($log->triggered_by === 'manual')
                                        <i class="fas fa-user mr-1"></i>
                                        {{ $log->triggeredByUser?->name ?? 'User' }}
                                    @else
                                        <i class="fas fa-clock mr-1"></i>
                                        Scheduler
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="showOutput('{{ addslashes($log->output ?? 'Tidak ada output') }}')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Belum ada riwayat eksekusi
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Output Modal -->
<div class="modal fade" id="outputModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-terminal mr-2"></i>Output
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <pre id="outputContent" class="bg-dark text-white p-3 rounded" style="max-height: 400px; overflow-y: auto;"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showOutput(output) {
    document.getElementById('outputContent').textContent = output;
    $('#outputModal').modal('show');
}
</script>
@endpush
