<?php

use App\Models\ScheduledTask;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks from Database
|--------------------------------------------------------------------------
| Automatically load enabled tasks from the scheduled_tasks table
| and register them with Laravel's scheduler.
|
| Setup:
|   Linux  : * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
|   Windows: schtasks /create /sc minute /mo 1 /tn "LaravelScheduler" /tr "php D:\path-to-project\artisan schedule:run"
|   Dev    : php artisan schedule:work
|
*/

try {
    if (Schema::hasTable('scheduled_tasks')) {
        $tasks = ScheduledTask::where('is_enabled', true)->get();

        foreach ($tasks as $task) {
            $schedule = Schedule::command($task->command);

            // Apply schedule frequency
            match (true) {
                $task->schedule === 'everyMinute'          => $schedule->everyMinute(),
                $task->schedule === 'everyFiveMinutes'     => $schedule->everyFiveMinutes(),
                $task->schedule === 'everyTenMinutes'      => $schedule->everyTenMinutes(),
                $task->schedule === 'everyFifteenMinutes'  => $schedule->everyFifteenMinutes(),
                $task->schedule === 'everyThirtyMinutes'   => $schedule->everyThirtyMinutes(),
                $task->schedule === 'hourly'               => $schedule->hourly(),
                $task->schedule === 'daily'                => $schedule->daily(),
                $task->schedule === 'weekly'               => $schedule->weekly(),
                $task->schedule === 'monthly'              => $schedule->monthly(),
                str_starts_with($task->schedule, 'dailyAt:') => $schedule->dailyAt(substr($task->schedule, 8)),
                str_starts_with($task->schedule, 'monthlyOn:') => $schedule->monthlyOn((int) substr($task->schedule, 10)),
                default => $schedule->daily(),
            };

            // Apply options
            if ($task->without_overlapping) {
                $schedule->withoutOverlapping($task->timeout ?? 3600);
            }

            if ($task->run_in_background) {
                $schedule->runInBackground();
            }

            // After task runs, update task record and create log
            $schedule->after(function () use ($task) {
                try {
                    $task->update([
                        'last_run_at' => now(),
                        'last_status' => 'success',
                        'next_run_at' => $task->calculateNextRun(),
                        'run_count' => $task->run_count + 1,
                    ]);

                    $task->logs()->create([
                        'started_at' => now(),
                        'finished_at' => now(),
                        'status' => 'success',
                        'triggered_by' => 'scheduler',
                        'output' => 'Executed by Laravel scheduler',
                    ]);
                } catch (\Exception $e) {
                    Log::error("Scheduler post-run hook failed for {$task->name}: " . $e->getMessage());
                }
            });

            // On failure
            $schedule->onFailure(function () use ($task) {
                try {
                    $task->update([
                        'last_run_at' => now(),
                        'last_status' => 'failed',
                        'next_run_at' => $task->calculateNextRun(),
                        'failure_count' => $task->failure_count + 1,
                    ]);

                    $task->logs()->create([
                        'started_at' => now(),
                        'finished_at' => now(),
                        'status' => 'failed',
                        'triggered_by' => 'scheduler',
                        'output' => 'Task failed when executed by Laravel scheduler',
                    ]);
                } catch (\Exception $e) {
                    Log::error("Scheduler failure hook failed for {$task->name}: " . $e->getMessage());
                }
            });
        }
    }
} catch (\Exception $e) {
    Log::warning('Could not load scheduled tasks from database: ' . $e->getMessage());
}
