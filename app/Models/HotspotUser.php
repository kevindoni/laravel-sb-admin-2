<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HotspotUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'billing_plan_id',
        'username',
        'password',
        'profile',
        'status',
        'expires_at',
        'time_used',
        'data_used',
        'first_login_at',
        'last_login_at',
        'last_ip',
        'last_mac',
        'comment',
        'balance',
        'is_voucher',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'first_login_at' => 'datetime',
        'last_login_at' => 'datetime',
        'balance' => 'decimal:2',
        'is_voucher' => 'boolean',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function billingPlan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function voucher(): HasOne
    {
        return $this->hasOne(Voucher::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function getRemainingTimeAttribute(): int
    {
        if (!$this->billingPlan->time_limit) return -1; // unlimited
        
        return max(0, ($this->billingPlan->time_limit * 60) - $this->time_used);
    }

    public function getRemainingDataAttribute(): int
    {
        if (!$this->billingPlan->data_limit) return -1; // unlimited
        
        return max(0, $this->billingPlan->data_limit - $this->data_used);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return 'Rp ' . number_format($this->balance, 0, ',', '.');
    }
}