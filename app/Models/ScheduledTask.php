<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScheduledTask extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'command',
        'schedule',
        'description',
        'is_enabled',
        'last_run_at',
        'next_run_at',
        'last_status',
        'last_output',
        'run_count',
        'failure_count',
        'timeout',
        'without_overlapping',
        'run_in_background',
        'pop_id',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'without_overlapping' => 'boolean',
        'run_in_background' => 'boolean',
    ];

    protected $appends = ['status_color', 'schedule_label'];

    /**
     * Schedule presets
     */
    public static function schedulePresets(): array
    {
        return [
            'everyMinute' => 'Setiap Menit',
            'everyFiveMinutes' => 'Setiap 5 Menit',
            'everyTenMinutes' => 'Setiap 10 Menit',
            'everyFifteenMinutes' => 'Setiap 15 Menit',
            'everyThirtyMinutes' => 'Setiap 30 Menit',
            'hourly' => 'Setiap Jam',
            'daily' => 'Setiap Hari (00:00)',
            'dailyAt:08:00' => 'Setiap Hari (08:00)',
            'dailyAt:12:00' => 'Setiap Hari (12:00)',
            'weekly' => 'Setiap Minggu',
            'monthly' => 'Setiap Bulan',
            'monthlyOn:1' => 'Tanggal 1 Setiap Bulan',
        ];
    }

    /**
     * Available commands
     */
    public static function availableCommands(): array
    {
        return [
            'billing:generate' => [
                'name' => 'Generate Invoice',
                'description' => 'Generate invoice harian berdasarkan billing_day masing-masing pelanggan',
                'recommended_schedule' => 'daily',
            ],
            'billing:reminder' => [
                'name' => 'Kirim Reminder',
                'description' => 'Kirim reminder pembayaran ke pelanggan',
                'recommended_schedule' => 'daily',
            ],
            'billing:auto-suspend' => [
                'name' => 'Auto Suspend',
                'description' => 'Suspend pelanggan yang belum bayar setelah jatuh tempo',
                'recommended_schedule' => 'daily',
            ],
            'onu:sync-power' => [
                'name' => 'Sync Optical Power',
                'description' => 'Sinkronisasi data optical power ONU',
                'recommended_schedule' => 'everyTenMinutes',
            ],
            'queue:work --stop-when-empty' => [
                'name' => 'Process Queue',
                'description' => 'Proses antrian job',
                'recommended_schedule' => 'everyMinute',
            ],
        ];
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->last_status) {
            'success' => 'success',
            'running' => 'info',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get schedule label
     */
    public function getScheduleLabelAttribute(): string
    {
        return self::schedulePresets()[$this->schedule] ?? $this->schedule;
    }

    /**
     * Calculate next run time
     */
    public function calculateNextRun(): ?Carbon
    {
        // For simple presets, calculate next run
        $now = Carbon::now();
        
        return match($this->schedule) {
            'everyMinute' => $now->addMinute(),
            'everyFiveMinutes' => $now->addMinutes(5),
            'everyTenMinutes' => $now->addMinutes(10),
            'everyFifteenMinutes' => $now->addMinutes(15),
            'everyThirtyMinutes' => $now->addMinutes(30),
            'hourly' => $now->addHour()->startOfHour(),
            'daily' => $now->addDay()->startOfDay(),
            'weekly' => $now->addWeek()->startOfWeek(),
            'monthly' => $now->addMonth()->startOfMonth(),
            default => $this->parseSchedulePreset(),
        };
    }

    /**
     * Parse schedule preset with parameters
     */
    protected function parseSchedulePreset(): ?Carbon
    {
        $now = Carbon::now();
        
        if (str_starts_with($this->schedule, 'dailyAt:')) {
            $time = substr($this->schedule, 8);
            [$hour, $minute] = explode(':', $time);
            $next = $now->copy()->setTime((int)$hour, (int)$minute, 0);
            if ($next->lte($now)) {
                $next->addDay();
            }
            return $next;
        }
        
        if (str_starts_with($this->schedule, 'monthlyOn:')) {
            $day = (int)substr($this->schedule, 10);
            $next = $now->copy()->day($day)->startOfDay();
            if ($next->lte($now)) {
                $next->addMonth();
            }
            return $next;
        }
        
        return $now->addDay();
    }

    /**
     * Run the task
     */
    public function run(string $triggeredBy = 'scheduler', ?string $userId = null): ScheduledTaskLog
    {
        $startedAt = now();
        
        // Create log entry
        $log = $this->logs()->create([
            'started_at' => $startedAt,
            'status' => 'running',
            'triggered_by' => $triggeredBy,
            'triggered_by_user_id' => $userId,
        ]);
        
        // Update task status
        $this->update([
            'last_run_at' => $startedAt,
            'last_status' => 'running',
        ]);
        
        try {
            // Run the artisan command
            $exitCode = Artisan::call($this->command);
            $output = Artisan::output();
            
            $finishedAt = now();
            $duration = $startedAt->diffInSeconds($finishedAt);
            $status = $exitCode === 0 ? 'success' : 'failed';
            
            // Update log
            $log->update([
                'finished_at' => $finishedAt,
                'status' => $status,
                'output' => $output,
                'duration' => $duration,
            ]);
            
            // Update task
            $this->update([
                'last_status' => $status,
                'last_output' => $output,
                'next_run_at' => $this->calculateNextRun(),
                'run_count' => $this->run_count + 1,
                'failure_count' => $status === 'failed' ? $this->failure_count + 1 : $this->failure_count,
            ]);
            
        } catch (\Exception $e) {
            Log::error("Scheduled task {$this->name} failed: " . $e->getMessage());
            
            $finishedAt = now();
            $log->update([
                'finished_at' => $finishedAt,
                'status' => 'failed',
                'output' => $e->getMessage(),
                'duration' => $startedAt->diffInSeconds($finishedAt),
            ]);
            
            $this->update([
                'last_status' => 'failed',
                'last_output' => $e->getMessage(),
                'next_run_at' => $this->calculateNextRun(),
                'failure_count' => $this->failure_count + 1,
            ]);
        }
        
        return $log->fresh();
    }

    // Relationships

    public function pop()
    {
        return $this->belongsTo(User::class, 'pop_id');
    }

    public function logs()
    {
        return $this->hasMany(ScheduledTaskLog::class);
    }

    // Scopes

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForPop($query, $popId)
    {
        return $query->where(function($q) use ($popId) {
            $q->whereNull('pop_id')
              ->orWhere('pop_id', $popId);
        });
    }
}
