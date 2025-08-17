<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'time_limit',
        'data_limit',
        'rate_limit',
        'price',
        'validity_period',
        'is_active',
        'description',
        'additional_settings',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'additional_settings' => 'array',
    ];

    public function hotspotUsers(): HasMany
    {
        return $this->hasMany(HotspotUser::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function getFormattedTimeLimitAttribute(): string
    {
        if (!$this->time_limit) return 'Unlimited';
        
        $hours = floor($this->time_limit / 60);
        $minutes = $this->time_limit % 60;
        
        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }

    public function getFormattedDataLimitAttribute(): string
    {
        if (!$this->data_limit) return 'Unlimited';
        
        $gb = $this->data_limit / (1024 * 1024 * 1024);
        $mb = $this->data_limit / (1024 * 1024);
        
        return $gb >= 1 ? number_format($gb, 1) . ' GB' : number_format($mb, 0) . ' MB';
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }
}