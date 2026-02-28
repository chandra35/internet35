@extends('layouts.admin')

@section('title', 'Log Scheduler')

@section('page-title', 'Log Scheduler')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.scheduler.index') }}">Scheduler</a></li>
    <li class="breadcrumb-item active">Log</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-history mr-2"></i>Semua Log Eksekusi
        </h3>
        <div class="card-tools">
            <form action="{{ route('admin.scheduler.clear-logs') }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Hapus log yang lebih dari 30 hari?')">
                @csrf
                <input type="hidden" name="days" value="30">
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash mr-1"></i> Hapus Log Lama
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <!-- Filter -->
        <form action="{{ route('admin.scheduler.logs') }}" method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Task</label>
                        <select name="task_id" class="form-control">
                            <option value="">Semua Task</option>
                            @foreach($tasks as $task)
                                <option value="{{ $task->id }}" {{ request('task_id') == $task->id ? 'selected' : '' }}>
                                    {{ $task->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Berhasil</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Gagal</option>
                            <option value="running" {{ request('status') == 'running' ? 'selected' : '' }}>Berjalan</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="{{ route('admin.scheduler.logs') }}" class="btn btn-secondary">
                                <i class="fas fa-sync"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="thead-light">
                    <tr>
                        <th>Task</th>
                        <th>Waktu Mulai</th>
                        <th>Waktu Selesai</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th>Dipicu Oleh</th>
                        <th width="80">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>
                            @if($log->task)
                                <a href="{{ route('admin.scheduler.show', $log->task) }}">
                                    {{ $log->task->name }}
                                </a>
                            @else
                                <span class="text-muted">Task Dihapus</span>
                            @endif
                        </td>
                        <td>
                            {{ $log->started_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td>
                            {{ $log->finished_at?->format('d/m/Y H:i:s') ?? '-' }}
                        </td>
                        <td>{{ $log->formatted_duration }}</td>
                        <td>
                            <span class="badge badge-{{ $log->status_color }}">
                                {{ $log->status_label }}
                            </span>
                        </td>
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
                                    onclick="showOutput(`{{ addslashes($log->output ?? 'Tidak ada output') }}`)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            Belum ada log eksekusi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="mt-3">
            {{ $logs->appends(request()->query())->links() }}
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
