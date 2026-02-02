<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerInvoice extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'pop_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'period_start',
        'period_end',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'status',
        'notes',
        'items',
        'paid_at',
        'payment_method',
        'payment_reference',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'items' => 'array',
        'paid_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'status_color', 'remaining_amount'];

    /**
     * Status labels
     */
    public static function statusLabels(): array
    {
        return [
            'draft' => 'Draft',
            'pending' => 'Belum Dibayar',
            'paid' => 'Lunas',
            'partial' => 'Dibayar Sebagian',
            'overdue' => 'Jatuh Tempo',
            'cancelled' => 'Dibatalkan',
        ];
    }

    /**
     * Status colors
     */
    public static function statusColors(): array
    {
        return [
            'draft' => 'secondary',
            'pending' => 'warning',
            'paid' => 'success',
            'partial' => 'info',
            'overdue' => 'danger',
            'cancelled' => 'dark',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::statusColors()[$this->status] ?? 'secondary';
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    /**
     * Generate invoice number
     */
    public static function generateInvoiceNumber(string $popId): string
    {
        $popSetting = PopSetting::where('user_id', $popId)->first();
        $prefix = $popSetting?->invoice_prefix ?? 'INV';
        
        $year = date('Y');
        $month = date('m');
        
        $lastInvoice = static::where('pop_id', $popId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastInvoice) {
            // Extract last number
            preg_match('/(\d+)$/', $lastInvoice->invoice_number, $matches);
            $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . '-' . $year . $month . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationships

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function pop()
    {
        return $this->belongsTo(User::class, 'pop_id');
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class, 'invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes

    public function scopeForPop($query, $popId)
    {
        return $query->where('pop_id', $popId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || 
               ($this->status === 'pending' && $this->due_date && $this->due_date->isPast());
    }

    /**
     * Mark as paid
     */
    public function markAsPaid(string $method = 'manual', ?string $reference = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_amount' => $this->total_amount,
            'paid_at' => now(),
            'payment_method' => $method,
            'payment_reference' => $reference,
        ]);
    }
}
