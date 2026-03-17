<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'pop_id',
        'customer_id',
        'channel',
        'template_code',
        'recipient',
        'subject',
        'body',
        'status',
        'error_message',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    // ─── Relationships ─────────────────────────────────────────

    public function pop(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pop_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeForPop($query, string $popId)
    {
        return $query->where('pop_id', $popId);
    }

    public function scopeEmail($query)
    {
        return $query->where('channel', 'email');
    }

    public function scopeWhatsapp($query)
    {
        return $query->where('channel', 'whatsapp');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // ─── Accessors ─────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'sent' => 'Terkirim',
            'failed' => 'Gagal',
            'bounced' => 'Bounced',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'sent' => 'success',
            'failed' => 'danger',
            'bounced' => 'dark',
            default => 'secondary',
        };
    }

    public function getChannelLabelAttribute(): string
    {
        return match ($this->channel) {
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            default => ucfirst($this->channel),
        };
    }

    public function getChannelIconAttribute(): string
    {
        return match ($this->channel) {
            'email' => 'fas fa-envelope',
            'whatsapp' => 'fab fa-whatsapp',
            'telegram' => 'fab fa-telegram-plane',
            default => 'fas fa-comment',
        };
    }

    public function getChannelColorAttribute(): string
    {
        return match ($this->channel) {
            'email' => 'primary',
            'whatsapp' => 'success',
            'telegram' => 'info',
            default => 'secondary',
        };
    }

    public function getTemplateLabelAttribute(): string
    {
        $codes = MessageTemplate::templateCodes();
        return $codes[$this->template_code]['name'] ?? $this->template_code ?? '-';
    }

    // ─── Static Helpers ────────────────────────────────────────

    /**
     * Get stats for a POP
     */
    public static function statsForPop(string $popId): array
    {
        $base = static::where('pop_id', $popId);
        $today = now()->startOfDay();

        return [
            'total' => (clone $base)->count(),
            'today' => (clone $base)->where('created_at', '>=', $today)->count(),
            'sent' => (clone $base)->where('status', 'sent')->count(),
            'failed' => (clone $base)->where('status', 'failed')->count(),
            'email_count' => (clone $base)->where('channel', 'email')->count(),
            'wa_count' => (clone $base)->where('channel', 'whatsapp')->count(),
        ];
    }
}
