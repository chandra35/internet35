<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledTaskLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'scheduled_task_id',
        'started_at',
        'finished_at',
        'status',
        'output',
        'duration',
        'triggered_by',
        'triggered_by_user_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected $appends = ['status_color', 'status_label'];

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'success' => 'success',
            'running' => 'info',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'success' => 'Berhasil',
            'running' => 'Berjalan',
            'failed' => 'Gagal',
            default => 'Pending',
        };
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) return '-';
        
        if ($this->duration < 60) {
            return $this->duration . ' detik';
        }
        
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        return "{$minutes}m {$seconds}s";
    }

    // Relationships

    public function task()
    {
        return $this->belongsTo(ScheduledTask::class, 'scheduled_task_id');
    }

    public function triggeredByUser()
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
