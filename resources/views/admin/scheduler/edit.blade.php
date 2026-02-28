@extends('layouts.admin')

@section('title', 'Edit Task')

@section('page-title', 'Edit Task')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.scheduler.index') }}">Scheduler</a></li>
    <li class="breadcrumb-item active">Edit {{ $task->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="card-title">
                    <i class="fas fa-edit mr-2"></i>Edit Scheduled Task
                </h3>
            </div>
            <form action="{{ route('admin.scheduler.update', $task) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama Task <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       value="{{ old('name', $task->name) }}"
                                       placeholder="Contoh: Generate Invoice Bulanan">
                                @error('name')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Command <span class="text-danger">*</span></label>
                                <select name="command" class="form-control" id="commandSelect" required>
                                    <option value="">-- Pilih Command --</option>
                                    @foreach($availableCommands as $cmd => $info)
                                        <option value="{{ $cmd }}" 
                                                {{ $task->command == $cmd ? 'selected' : '' }}
                                                data-desc="{{ $info['description'] }}"
                                                data-schedule="{{ $info['recommended_schedule'] }}">
                                            {{ $info['name'] }} ({{ $cmd }})
                                        </option>
                                    @endforeach
                                    @if(!array_key_exists($task->command, $availableCommands))
                                        <option value="{{ $task->command }}" selected>
                                            {{ $task->command }} (Kustom)
                                        </option>
                                    @endif
                                    <option value="custom">Command Kustom...</option>
                                </select>
                                @error('command')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
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
                                <option value="{{ $key }}" {{ $task->schedule == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('schedule')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2"
                                  placeholder="Deskripsi task...">{{ old('description', $task->description) }}</textarea>
                        @error('description')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Timeout (detik)</label>
                                <input type="number" name="timeout" class="form-control" 
                                       value="{{ old('timeout', $task->timeout) }}" min="60" max="86400">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="withoutOverlapping" name="without_overlapping" value="1"
                                           {{ $task->without_overlapping ? 'checked' : '' }}>
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
                                           id="runInBackground" name="run_in_background" value="1"
                                           {{ $task->run_in_background ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="runInBackground">
                                        Background
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.scheduler.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Task Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-2"></i>Info Task
                </h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Status</td>
                        <td>
                            <span class="badge badge-{{ $task->is_enabled ? 'success' : 'secondary' }}">
                                {{ $task->is_enabled ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Run</td>
                        <td>{{ number_format($task->run_count) }}x</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Gagal</td>
                        <td>{{ number_format($task->failure_count) }}x</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Terakhir Run</td>
                        <td>{{ $task->last_run_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dibuat</td>
                        <td>{{ $task->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Danger Zone -->
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title text-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Zona Berbahaya
                </h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.scheduler.destroy', $task) }}" method="POST"
                      onsubmit="return confirm('Yakin ingin menghapus task ini? Semua log juga akan dihapus.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="fas fa-trash mr-1"></i> Hapus Task
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Handle command selection
    $('#commandSelect').change(function() {
        const value = $(this).val();
        
        if (value === 'custom') {
            $('#customCommandGroup').show();
            $('#customCommand').attr('required', true);
        } else {
            $('#customCommandGroup').hide();
            $('#customCommand').attr('required', false);
        }
    });
    
    // Handle custom command
    $('#customCommand').on('input', function() {
        $('input[name="command"]').val($(this).val());
    });
});
</script>
@endpush
