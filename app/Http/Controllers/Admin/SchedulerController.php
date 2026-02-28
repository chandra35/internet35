<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTask;
use App\Models\ScheduledTaskLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SchedulerController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('role:superadmin'),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Get POP ID based on user role
     */
    protected function getPopId(Request $request)
    {
        $user = auth()->user();
        
        if ($user->hasRole('superadmin')) {
            return $request->input('pop_id') ?: $request->session()->get('manage_pop_id');
        }
        
        return $user->id;
    }

    /**
     * Display scheduler dashboard
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $popId = $this->getPopId($request);
        
        // For superadmin, get list of POPs
        $popUsers = null;
        if ($user->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
            
            if ($request->has('pop_id')) {
                $request->session()->put('manage_pop_id', $request->input('pop_id'));
                $popId = $request->input('pop_id');
            }
        }
        
        // Get tasks
        $tasks = ScheduledTask::when($popId, function($q) use ($popId) {
                $q->where(function($sq) use ($popId) {
                    $sq->whereNull('pop_id')
                       ->orWhere('pop_id', $popId);
                });
            })
            ->orderBy('name')
            ->get();
        
        // Statistics
        $stats = [
            'total' => $tasks->count(),
            'enabled' => $tasks->where('is_enabled', true)->count(),
            'disabled' => $tasks->where('is_enabled', false)->count(),
            'running' => $tasks->where('last_status', 'running')->count(),
            'failed' => $tasks->where('last_status', 'failed')->count(),
        ];
        
        // Recent logs
        $recentLogs = ScheduledTaskLog::with('task')
            ->whereHas('task', function($q) use ($popId) {
                $q->when($popId, function($sq) use ($popId) {
                    $sq->where(function($ssq) use ($popId) {
                        $ssq->whereNull('pop_id')
                            ->orWhere('pop_id', $popId);
                    });
                });
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        
        // Available commands for creation
        $availableCommands = ScheduledTask::availableCommands();
        $schedulePresets = ScheduledTask::schedulePresets();
        
        return view('admin.scheduler.index', compact(
            'tasks', 'stats', 'recentLogs', 'popUsers', 'popId',
            'availableCommands', 'schedulePresets'
        ));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $availableCommands = ScheduledTask::availableCommands();
        $schedulePresets = ScheduledTask::schedulePresets();
        
        return view('admin.scheduler.create', compact('availableCommands', 'schedulePresets'));
    }

    /**
     * Store new task
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'command' => 'required|string',
            'schedule' => 'required|string',
            'description' => 'nullable|string|max:500',
            'timeout' => 'nullable|integer|min:60|max:86400',
            'without_overlapping' => 'nullable|boolean',
            'run_in_background' => 'nullable|boolean',
        ]);
        
        $popId = $this->getPopId($request);
        
        $task = ScheduledTask::create([
            'name' => $validated['name'],
            'command' => $validated['command'],
            'schedule' => $validated['schedule'],
            'description' => $validated['description'] ?? null,
            'timeout' => $validated['timeout'] ?? 3600,
            'without_overlapping' => $request->boolean('without_overlapping', true),
            'run_in_background' => $request->boolean('run_in_background', false),
            'is_enabled' => true,
            'pop_id' => $popId,
            'next_run_at' => now(),
        ]);
        
        // Calculate next run
        $task->update(['next_run_at' => $task->calculateNextRun()]);
        
        $this->activityLog->logCreate('scheduler', "Created task: {$task->name}");
        
        return redirect()->route('admin.scheduler.index')
            ->with('success', 'Task berhasil ditambahkan!');
    }

    /**
     * Show task detail
     */
    public function show(ScheduledTask $task)
    {
        $task->load('logs');
        $logs = $task->logs()->orderBy('created_at', 'desc')->paginate(20);
        
        return view('admin.scheduler.show', compact('task', 'logs'));
    }

    /**
     * Show edit form
     */
    public function edit(ScheduledTask $task)
    {
        $availableCommands = ScheduledTask::availableCommands();
        $schedulePresets = ScheduledTask::schedulePresets();
        
        return view('admin.scheduler.edit', compact('task', 'availableCommands', 'schedulePresets'));
    }

    /**
     * Update task
     */
    public function update(Request $request, ScheduledTask $task)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'command' => 'required|string',
            'schedule' => 'required|string',
            'description' => 'nullable|string|max:500',
            'timeout' => 'nullable|integer|min:60|max:86400',
            'without_overlapping' => 'nullable|boolean',
            'run_in_background' => 'nullable|boolean',
        ]);
        
        $oldData = $task->toArray();
        
        $task->update([
            'name' => $validated['name'],
            'command' => $validated['command'],
            'schedule' => $validated['schedule'],
            'description' => $validated['description'] ?? null,
            'timeout' => $validated['timeout'] ?? 3600,
            'without_overlapping' => $request->boolean('without_overlapping', true),
            'run_in_background' => $request->boolean('run_in_background', false),
        ]);
        
        // Recalculate next run
        $task->update(['next_run_at' => $task->calculateNextRun()]);
        
        $this->activityLog->logUpdate('scheduler', "Updated task: {$task->name}", $oldData, $task->toArray());
        
        return redirect()->route('admin.scheduler.index')
            ->with('success', 'Task berhasil diupdate!');
    }

    /**
     * Delete task
     */
    public function destroy(ScheduledTask $task)
    {
        $taskName = $task->name;
        $task->delete();
        
        $this->activityLog->logDelete('scheduler', "Deleted task: {$taskName}");
        
        return redirect()->route('admin.scheduler.index')
            ->with('success', 'Task berhasil dihapus!');
    }

    /**
     * Toggle task enabled/disabled
     */
    public function toggle(ScheduledTask $task)
    {
        $task->update([
            'is_enabled' => !$task->is_enabled,
        ]);
        
        $status = $task->is_enabled ? 'diaktifkan' : 'dinonaktifkan';
        $this->activityLog->logUpdate('scheduler', "Task {$task->name} {$status}");
        
        return back()->with('success', "Task berhasil {$status}!");
    }

    /**
     * Run task manually
     */
    public function run(ScheduledTask $task)
    {
        try {
            $log = $task->run('manual', auth()->id());
            
            $this->activityLog->logCreate('scheduler', "Manually ran task: {$task->name}");
            
            if ($log->status === 'success') {
                return back()->with('success', 'Task berhasil dijalankan!');
            } else {
                return back()->with('warning', 'Task selesai dengan error. Lihat log untuk detail.');
            }
            
        } catch (\Exception $e) {
            Log::error("Failed to run task {$task->name}: " . $e->getMessage());
            
            return back()->with('error', 'Gagal menjalankan task: ' . $e->getMessage());
        }
    }

    /**
     * View all logs
     */
    public function logs(Request $request)
    {
        $popId = $this->getPopId($request);
        
        $logs = ScheduledTaskLog::with(['task', 'triggeredByUser'])
            ->whereHas('task', function($q) use ($popId) {
                $q->when($popId, function($sq) use ($popId) {
                    $sq->where(function($ssq) use ($popId) {
                        $ssq->whereNull('pop_id')
                            ->orWhere('pop_id', $popId);
                    });
                });
            })
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->task_id, fn($q, $t) => $q->where('scheduled_task_id', $t))
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        
        $tasks = ScheduledTask::when($popId, fn($q) => $q->forPop($popId))
            ->orderBy('name')
            ->get();
        
        return view('admin.scheduler.logs', compact('logs', 'tasks'));
    }

    /**
     * Clear old logs
     */
    public function clearLogs(Request $request)
    {
        $days = $request->input('days', 30);
        
        $deleted = ScheduledTaskLog::where('created_at', '<', now()->subDays($days))->delete();
        
        $this->activityLog->logDelete('scheduler', "Cleared {$deleted} old logs (older than {$days} days)");
        
        return back()->with('success', "Berhasil menghapus {$deleted} log lama!");
    }
}
