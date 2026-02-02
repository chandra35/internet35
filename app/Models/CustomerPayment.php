<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPayment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'invoice_id',
        'pop_id',
        'payment_gateway_id',
        'payment_number',
        'amount',
        'payment_method',
        'payment_channel',
        'status',
        'external_id',
        'external_reference',
        'gateway_response',
        'payment_url',
        'expired_at',
        'notes',
        'paid_at',
        'verified_by',
        'verified_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'expired_at' => 'datetime',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'status_color'];

    /**
     * Status labels
     */
    public static function statusLabels(): array
    {
        return [
            'pending' => 'Menunggu',
            'verifying' => 'Menunggu Verifikasi',
            'processing' => 'Diproses',
            'success' => 'Berhasil',
            'failed' => 'Gagal',
            'expired' => 'Kadaluarsa',
            'cancelled' => 'Dibatalkan',
            'refunded' => 'Dikembalikan',
        ];
    }

    /**
     * Status colors
     */
    public static function statusColors(): array
    {
        return [
            'pending' => 'warning',
            'verifying' => 'info',
            'processing' => 'info',
            'success' => 'success',
            'failed' => 'danger',
            'expired' => 'secondary',
            'cancelled' => 'dark',
            'refunded' => 'dark',
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

    /**
     * Payment methods
     */
    public static function paymentMethods(): array
    {
        return [
            'manual' => 'Manual/Transfer',
            'midtrans' => 'Midtrans',
            'xendit' => 'Xendit',
            'tripay' => 'Tripay',
            'duitku' => 'Duitku',
            'ipaymu' => 'iPaymu',
        ];
    }

    /**
     * Generate payment number
     */
    public static function generatePaymentNumber(string $popId): string
    {
        $prefix = 'PAY';
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        
        $lastPayment = static::where('pop_id', $popId)
            ->whereDate('created_at', today())
            ->orderBy('payment_number', 'desc')
            ->first();
        
        if ($lastPayment) {
            preg_match('/(\d+)$/', $lastPayment->payment_number, $matches);
            $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . '-' . $year . $month . $day . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationships

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(CustomerInvoice::class, 'invoice_id');
    }

    public function pop()
    {
        return $this->belongsTo(User::class, 'pop_id');
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
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

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Check if payment is success
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->status === 'pending' && $this->expired_at && $this->expired_at->isPast());
    }

    /**
     * Mark as success
     */
    public function markAsSuccess(?string $externalReference = null): void
    {
        $this->update([
            'status' => 'success',
            'paid_at' => now(),
            'external_reference' => $externalReference ?? $this->external_reference,
        ]);

        // Update invoice if linked
        if ($this->invoice) {
            $this->invoice->update([
                'paid_amount' => $this->invoice->paid_amount + $this->amount,
            ]);

            if ($this->invoice->paid_amount >= $this->invoice->total_amount) {
                $this->invoice->markAsPaid($this->payment_method, $this->payment_number);
            } else {
                $this->invoice->update(['status' => 'partial']);
            }
        }
    }
}
