<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'location',
        'description',
        'is_active',
        'last_connected_at',
        'system_info',
    ];

    protected $casts = [
        'last_connected_at' => 'datetime',
        'system_info' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'password',
    ];

    public function hotspotUsers(): HasMany
    {
        return $this->hasMany(HotspotUser::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function userSessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function getConnectionUrlAttribute(): string
    {
        return $this->host . ':' . $this->port;
    }

    public function isOnline(): bool
    {
        // Basic connectivity check
        $connection = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
        if ($connection) {
            fclose($connection);
            return true;
        }
        return false;
    }
}