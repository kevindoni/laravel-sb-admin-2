<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'billing_plan_id',
        'code',
        'password',
        'status',
        'used_at',
        'used_by_ip',
        'used_by_mac',
        'batch_id',
        'selling_price',
        'comment',
        'expires_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'selling_price' => 'decimal:2',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function billingPlan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class);
    }

    public function hotspotUser(): BelongsTo
    {
        return $this->belongsTo(HotspotUser::class, 'code', 'username');
    }

    public static function generateCode(int $length = 8): string
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public static function generatePassword(int $length = 6): string
    {
        return Str::random($length);
    }

    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->status === 'unused' && !$this->isExpired();
    }

    public function getFormattedSellingPriceAttribute(): string
    {
        return $this->selling_price ? 'Rp ' . number_format($this->selling_price, 0, ',', '.') : '-';
    }
}