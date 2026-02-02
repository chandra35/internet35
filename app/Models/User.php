<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'avatar',
        'password',
        'plain_password',
        'is_active',
        'parent_id',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'plain_password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Set plain password with encryption
     */
    public function setPlainPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['plain_password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Get decrypted plain password
     */
    public function getDecryptedPasswordAttribute(): ?string
    {
        if ($this->plain_password) {
            try {
                return Crypt::decryptString($this->plain_password);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/avatars/' . $this->avatar);
        }
        return asset('images/default-avatar.svg');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Activity logs relationship
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * POP Setting relationship
     */
    public function popSetting()
    {
        return $this->hasOne(PopSetting::class);
    }

    /**
     * Payment gateways relationship
     */
    public function paymentGateways()
    {
        return $this->hasMany(PaymentGateway::class);
    }

    /**
     * Notification setting relationship
     */
    public function notificationSetting()
    {
        return $this->hasOne(NotificationSetting::class);
    }

    /**
     * Customers (for POP admin)
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'pop_id');
    }

    /**
     * Customer profile (for customer user)
     */
    public function customerProfile()
    {
        return $this->hasOne(Customer::class, 'user_id');
    }

    /**
     * Routers (for POP admin)
     */
    public function routers()
    {
        return $this->hasMany(Router::class, 'pop_id');
    }

    /**
     * Parent user (admin-pop for staff)
     */
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Staff members (for admin-pop)
     */
    public function staff()
    {
        return $this->hasMany(User::class, 'parent_id');
    }
}
