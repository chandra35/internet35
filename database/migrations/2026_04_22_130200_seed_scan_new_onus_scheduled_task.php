<?php

use App\Models\ScheduledTask;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Seed the scheduled_tasks row that drives the unregistered-ONU scanner.
     * The cron itself self-throttles per-POP using `pop_settings.unreg_notif_settings.scan_interval`.
     */
    public function up(): void
    {
        if (!ScheduledTask::where('command', 'onus:scan-new')->exists()) {
            $task = ScheduledTask::create([
                'name'                => 'Scan ONU Baru (Unregistered)',
                'command'             => 'onus:scan-new',
                'schedule'            => 'everyMinute',
                'description'         => 'Memindai OLT aktif untuk ONU yang belum teregistrasi dan membuat notifikasi in-app. Per-POP throttle diatur di Pengaturan POP > Notifikasi ONU Baru.',
                'is_enabled'          => true,
                'timeout'             => 300,
                'without_overlapping' => true,
                'run_in_background'   => false,
                'pop_id'              => null,
                'next_run_at'         => now(),
            ]);
            $task->update(['next_run_at' => $task->calculateNextRun()]);
        }
    }

    public function down(): void
    {
        ScheduledTask::where('command', 'onus:scan-new')->delete();
    }
};
