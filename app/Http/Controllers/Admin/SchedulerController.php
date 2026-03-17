<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTask;
use App\Models\ScheduledTaskLog;
use App\Models\PopSetting;
use App\Models\PaymentGateway;
use App\Models\Customer;
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

    /**
     * Smart Check - step-based diagnostics with streaming support
     */
    public function smartCheck(Request $request)
    {
        $step = $request->input('step');

        if (!$step) {
            return response()->json([
                'success' => true,
                'steps' => [
                    ['key' => 'tasks', 'label' => 'Scheduled Tasks', 'icon' => 'fa-tasks'],
                    ['key' => 'pop', 'label' => 'Konfigurasi POP & Notifikasi', 'icon' => 'fa-server'],
                    ['key' => 'customers', 'label' => 'Data Pelanggan', 'icon' => 'fa-users'],
                    ['key' => 'server', 'label' => 'Server & Scheduler', 'icon' => 'fa-cog'],
                ],
            ]);
        }

        $checks = match ($step) {
            'tasks' => $this->smartCheckTasks(),
            'pop' => $this->smartCheckPop(),
            'customers' => $this->smartCheckCustomers(),
            'server' => $this->smartCheckServer(),
            default => [],
        };

        return response()->json(['success' => true, 'step' => $step, 'checks' => $checks]);
    }

    private function smartCheckTasks(): array
    {
        $checks = [];
        $requiredTasks = ScheduledTask::availableCommands();
        $criticalCommands = ['billing:generate', 'billing:reminder', 'billing:auto-suspend'];

        foreach ($criticalCommands as $cmd) {
            $info = $requiredTasks[$cmd] ?? null;
            $task = ScheduledTask::where('command', $cmd)->first();

            if (!$task) {
                $checks[] = [
                    'id' => 'task_' . str_replace(':', '_', $cmd),
                    'category' => 'Scheduled Tasks',
                    'label' => ($info['name'] ?? $cmd) . ' belum dibuat',
                    'detail' => "Command <code>{$cmd}</code> belum terdaftar. Jadwal rekomendasi: <strong>" . ($info['recommended_schedule'] ?? 'daily') . '</strong>',
                    'status' => 'danger',
                    'fixable' => true,
                    'fix_action' => 'create_task',
                    'fix_data' => ['command' => $cmd],
                ];
            } elseif (!$task->is_enabled) {
                $checks[] = [
                    'id' => 'task_' . str_replace(':', '_', $cmd),
                    'category' => 'Scheduled Tasks',
                    'label' => ($info['name'] ?? $cmd) . ' nonaktif',
                    'detail' => "Task <strong>{$task->name}</strong> ada tapi <span class='text-danger'>nonaktif</span>.",
                    'status' => 'warning',
                    'fixable' => true,
                    'fix_action' => 'enable_task',
                    'fix_data' => ['task_id' => $task->id],
                ];
            } elseif ($cmd === 'billing:generate' && $task->schedule !== 'daily' && $task->schedule !== 'dailyAt:08:00') {
                $checks[] = [
                    'id' => 'task_' . str_replace(':', '_', $cmd),
                    'category' => 'Scheduled Tasks',
                    'label' => 'billing:generate jadwal salah',
                    'detail' => "Jadwal sekarang: <strong>{$task->schedule_label}</strong>. Harus <strong>daily</strong> karena generate per billing_day pelanggan.",
                    'status' => 'warning',
                    'fixable' => true,
                    'fix_action' => 'fix_schedule',
                    'fix_data' => ['task_id' => $task->id, 'schedule' => 'daily'],
                ];
            } else {
                $lastFailed = $task->last_status === 'failed';
                if ($lastFailed) {
                    $checks[] = [
                        'id' => 'task_' . str_replace(':', '_', $cmd),
                        'category' => 'Scheduled Tasks',
                        'label' => ($info['name'] ?? $cmd) . ' gagal terakhir',
                        'detail' => 'Task terakhir gagal pada ' . ($task->last_run_at?->format('d M Y H:i') ?? '-') . '. Cek log untuk detail.',
                        'status' => 'warning',
                        'fixable' => false,
                    ];
                } else {
                    $checks[] = [
                        'id' => 'task_' . str_replace(':', '_', $cmd),
                        'category' => 'Scheduled Tasks',
                        'label' => ($info['name'] ?? $cmd),
                        'detail' => 'Aktif, jadwal: ' . $task->schedule_label . ($task->last_run_at ? '. Terakhir: ' . $task->last_run_at->diffForHumans() : ''),
                        'status' => 'success',
                        'fixable' => false,
                    ];
                }
            }
        }

        return $checks;
    }

    private function smartCheckPop(): array
    {
        $checks = [];
        $pops = User::role('admin-pop')->get();

        foreach ($pops as $pop) {
            $popSetting = PopSetting::where('user_id', $pop->id)->first();

            if (!$popSetting) {
                $checks[] = [
                    'id' => 'pop_setting_' . $pop->id,
                    'category' => 'Konfigurasi POP',
                    'label' => $pop->name . ' — belum ada PopSetting',
                    'detail' => 'POP ini belum memiliki konfigurasi. Invoice tidak bisa digenerate.',
                    'status' => 'danger',
                    'fixable' => false,
                ];
                continue;
            }

            if (!$popSetting->invoice_due_days || $popSetting->invoice_due_days < 1) {
                $checks[] = [
                    'id' => 'pop_due_' . $pop->id,
                    'category' => 'Konfigurasi POP',
                    'label' => $pop->name . ' — invoice_due_days belum diset',
                    'detail' => 'Jatuh tempo invoice belum dikonfigurasi. Default 7 hari akan digunakan.',
                    'status' => 'warning',
                    'fixable' => false,
                ];
            }

            if (!$popSetting->smtp_enabled) {
                $checks[] = [
                    'id' => 'pop_smtp_' . $pop->id,
                    'category' => 'Notifikasi',
                    'label' => $pop->name . ' — Email (SMTP) nonaktif',
                    'detail' => 'Pelanggan tidak akan menerima email invoice & reminder.',
                    'status' => 'warning',
                    'fixable' => false,
                ];
            }

            if (!$popSetting->wa_enabled) {
                $checks[] = [
                    'id' => 'pop_wa_' . $pop->id,
                    'category' => 'Notifikasi',
                    'label' => $pop->name . ' — WhatsApp nonaktif',
                    'detail' => 'Pelanggan tidak akan menerima notifikasi WhatsApp.',
                    'status' => 'info',
                    'fixable' => false,
                ];
            }

            if (!($popSetting->reminder_enabled ?? true)) {
                $checks[] = [
                    'id' => 'pop_reminder_' . $pop->id,
                    'category' => 'Notifikasi',
                    'label' => $pop->name . ' — Reminder dinonaktifkan',
                    'detail' => 'billing:reminder tidak akan mengirim notifikasi untuk POP ini.',
                    'status' => 'info',
                    'fixable' => true,
                    'fix_action' => 'enable_reminder',
                    'fix_data' => ['pop_id' => $pop->id],
                ];
            }

            $gateways = PaymentGateway::where('user_id', $pop->id)->where('is_active', true)->count();
            if ($gateways === 0) {
                $checks[] = [
                    'id' => 'pop_gateway_' . $pop->id,
                    'category' => 'Payment',
                    'label' => $pop->name . ' — tidak ada payment gateway aktif',
                    'detail' => 'Pelanggan tidak bisa membayar online. Hanya bisa bayar manual.',
                    'status' => 'warning',
                    'fixable' => false,
                ];
            }
        }

        return $checks;
    }

    private function smartCheckCustomers(): array
    {
        $checks = [];

        $noBillingDay = Customer::where('status', 'active')->where(function ($q) {
            $q->whereNull('billing_day')->orWhere('billing_day', 0);
        })->count();
        if ($noBillingDay > 0) {
            $checks[] = [
                'id' => 'customer_billing_day',
                'category' => 'Data Pelanggan',
                'label' => "{$noBillingDay} pelanggan tanpa billing_day",
                'detail' => 'Pelanggan tanpa billing_day tidak akan mendapat invoice otomatis. Set default ke tanggal 1.',
                'status' => 'danger',
                'fixable' => true,
                'fix_action' => 'fix_billing_day',
                'fix_data' => [],
            ];
        }

        $noAutoIsolir = Customer::where('status', 'active')->where('auto_isolir', false)->count();
        $totalActive = Customer::where('status', 'active')->count();
        if ($noAutoIsolir > 0) {
            $checks[] = [
                'id' => 'customer_auto_isolir',
                'category' => 'Data Pelanggan',
                'label' => "{$noAutoIsolir}/{$totalActive} pelanggan tanpa auto-isolir",
                'detail' => 'Pelanggan tanpa auto_isolir tidak akan di-suspend otomatis saat telat bayar.',
                'status' => 'info',
                'fixable' => true,
                'fix_action' => 'enable_auto_isolir',
                'fix_data' => [],
            ];
        }

        $noPackage = Customer::where('status', 'active')->whereNull('package_id')->count();
        if ($noPackage > 0) {
            $checks[] = [
                'id' => 'customer_no_package',
                'category' => 'Data Pelanggan',
                'label' => "{$noPackage} pelanggan aktif tanpa paket",
                'detail' => 'Pelanggan tanpa paket tidak bisa digenerate invoice-nya.',
                'status' => 'danger',
                'fixable' => false,
            ];
        }

        // Add success if no customer issues
        if (empty($checks)) {
            $checks[] = [
                'id' => 'customer_ok',
                'category' => 'Data Pelanggan',
                'label' => 'Data pelanggan lengkap',
                'detail' => 'Semua pelanggan aktif memiliki billing_day, paket, dan konfigurasi yang benar.',
                'status' => 'success',
                'fixable' => false,
            ];
        }

        return $checks;
    }

    private function smartCheckServer(): array
    {
        $checks = [];

        $anyTaskRan = ScheduledTask::where('is_enabled', true)->whereNotNull('last_run_at')->exists();
        $latestRun = ScheduledTask::where('is_enabled', true)->max('last_run_at');

        if (!$anyTaskRan) {
            $checks[] = [
                'id' => 'schedule_run',
                'category' => 'Server',
                'label' => 'schedule:run belum pernah berjalan',
                'detail' => 'Tidak ada task yang pernah dijalankan oleh scheduler. Pastikan <code>php artisan schedule:run</code> sudah disetup di server.',
                'status' => 'danger',
                'fixable' => false,
            ];
        } elseif ($latestRun && now()->diffInHours($latestRun) > 25) {
            $checks[] = [
                'id' => 'schedule_run',
                'category' => 'Server',
                'label' => 'schedule:run mungkin tidak aktif',
                'detail' => 'Task terakhir berjalan ' . \Carbon\Carbon::parse($latestRun)->diffForHumans() . '. Scheduler mungkin berhenti.',
                'status' => 'warning',
                'fixable' => false,
            ];
        } else {
            $checks[] = [
                'id' => 'schedule_run',
                'category' => 'Server',
                'label' => 'Scheduler aktif',
                'detail' => 'Task terakhir berjalan ' . \Carbon\Carbon::parse($latestRun)->diffForHumans() . '.',
                'status' => 'success',
                'fixable' => false,
            ];
        }

        $queueTask = ScheduledTask::where('command', 'like', '%queue%')->where('is_enabled', true)->first();
        if (!$queueTask) {
            $checks[] = [
                'id' => 'queue_worker',
                'category' => 'Server',
                'label' => 'Queue worker belum terdaftar',
                'detail' => 'Notifikasi email/WA menggunakan queue. Tanpa queue worker, notifikasi bisa tertunda.',
                'status' => 'info',
                'fixable' => true,
                'fix_action' => 'create_task',
                'fix_data' => ['command' => 'queue:work --stop-when-empty'],
            ];
        }

        return $checks;
    }

    /**
     * Auto-fix issues found by smart check
     */
    public function autoFix(Request $request)
    {
        $action = $request->input('action');
        $data = $request->input('data', []);

        switch ($action) {
            case 'create_task':
                $cmd = $data['command'] ?? null;
                $info = ScheduledTask::availableCommands()[$cmd] ?? null;
                if (!$cmd || !$info) {
                    return response()->json(['success' => false, 'message' => 'Command tidak dikenal.']);
                }
                // Don't create duplicate
                if (ScheduledTask::where('command', $cmd)->exists()) {
                    return response()->json(['success' => false, 'message' => 'Task sudah ada.']);
                }
                $task = ScheduledTask::create([
                    'name' => $info['name'],
                    'command' => $cmd,
                    'schedule' => $info['recommended_schedule'],
                    'description' => $info['description'],
                    'timeout' => 3600,
                    'without_overlapping' => true,
                    'run_in_background' => false,
                    'is_enabled' => true,
                    'next_run_at' => now(),
                ]);
                $task->update(['next_run_at' => $task->calculateNextRun()]);
                $this->activityLog->logCreate('scheduler', "Auto-created task: {$task->name}");
                return response()->json(['success' => true, 'message' => "Task '{$info['name']}' berhasil dibuat dan diaktifkan."]);

            case 'enable_task':
                $task = ScheduledTask::find($data['task_id'] ?? null);
                if (!$task) {
                    return response()->json(['success' => false, 'message' => 'Task tidak ditemukan.']);
                }
                $task->update(['is_enabled' => true]);
                $this->activityLog->logUpdate('scheduler', "Auto-enabled task: {$task->name}");
                return response()->json(['success' => true, 'message' => "Task '{$task->name}' berhasil diaktifkan."]);

            case 'fix_schedule':
                $task = ScheduledTask::find($data['task_id'] ?? null);
                $schedule = $data['schedule'] ?? 'daily';
                if (!$task) {
                    return response()->json(['success' => false, 'message' => 'Task tidak ditemukan.']);
                }
                $old = $task->schedule;
                $task->update(['schedule' => $schedule, 'next_run_at' => $task->calculateNextRun()]);
                $this->activityLog->logUpdate('scheduler', "Auto-fixed schedule for {$task->name}: {$old} → {$schedule}");
                return response()->json(['success' => true, 'message' => "Jadwal '{$task->name}' diubah ke {$schedule}."]);

            case 'fix_billing_day':
                $updated = Customer::where('status', 'active')
                    ->where(fn($q) => $q->whereNull('billing_day')->orWhere('billing_day', 0))
                    ->update(['billing_day' => 1]);
                $this->activityLog->logUpdate('scheduler', "Auto-fixed billing_day for {$updated} customers → 1");
                return response()->json(['success' => true, 'message' => "{$updated} pelanggan di-set billing_day = 1."]);

            case 'enable_auto_isolir':
                $updated = Customer::where('status', 'active')
                    ->where('auto_isolir', false)
                    ->update(['auto_isolir' => true]);
                $this->activityLog->logUpdate('scheduler', "Auto-enabled auto_isolir for {$updated} customers");
                return response()->json(['success' => true, 'message' => "{$updated} pelanggan diaktifkan auto-isolir."]);

            case 'enable_reminder':
                $popSetting = PopSetting::where('user_id', $data['pop_id'] ?? null)->first();
                if (!$popSetting) {
                    return response()->json(['success' => false, 'message' => 'PopSetting tidak ditemukan.']);
                }
                $popSetting->update(['reminder_enabled' => true]);
                return response()->json(['success' => true, 'message' => 'Reminder diaktifkan.']);

            case 'fix_all':
                // Run all fixable items
                $fixed = 0;
                $messages = [];

                // Create missing critical tasks
                foreach (['billing:generate', 'billing:reminder', 'billing:auto-suspend'] as $cmd) {
                    if (!ScheduledTask::where('command', $cmd)->exists()) {
                        $info = ScheduledTask::availableCommands()[$cmd] ?? null;
                        if ($info) {
                            $task = ScheduledTask::create([
                                'name' => $info['name'], 'command' => $cmd,
                                'schedule' => $info['recommended_schedule'],
                                'description' => $info['description'],
                                'timeout' => 3600, 'without_overlapping' => true,
                                'run_in_background' => false, 'is_enabled' => true,
                                'next_run_at' => now(),
                            ]);
                            $task->update(['next_run_at' => $task->calculateNextRun()]);
                            $fixed++;
                            $messages[] = "Task '{$info['name']}' dibuat";
                        }
                    }
                }

                // Enable disabled tasks
                $enabled = ScheduledTask::whereIn('command', ['billing:generate', 'billing:reminder', 'billing:auto-suspend'])
                    ->where('is_enabled', false)->update(['is_enabled' => true]);
                if ($enabled) { $fixed += $enabled; $messages[] = "{$enabled} task diaktifkan"; }

                // Fix billing:generate schedule
                $genTask = ScheduledTask::where('command', 'billing:generate')
                    ->whereNotIn('schedule', ['daily', 'dailyAt:08:00'])->first();
                if ($genTask) {
                    $genTask->update(['schedule' => 'daily']);
                    $fixed++;
                    $messages[] = "billing:generate jadwal diubah ke daily";
                }

                // Fix billing_day
                $bdFix = Customer::where('status', 'active')
                    ->where(fn($q) => $q->whereNull('billing_day')->orWhere('billing_day', 0))
                    ->update(['billing_day' => 1]);
                if ($bdFix) { $fixed += $bdFix; $messages[] = "{$bdFix} pelanggan billing_day diset ke 1"; }

                $this->activityLog->logUpdate('scheduler', "Auto-fix all: fixed {$fixed} issues");
                return response()->json([
                    'success' => true,
                    'message' => $fixed > 0
                        ? "Berhasil memperbaiki {$fixed} masalah: " . implode(', ', $messages)
                        : 'Tidak ada yang perlu diperbaiki.',
                ]);

            default:
                return response()->json(['success' => false, 'message' => 'Action tidak dikenal.']);
        }
    }
}
