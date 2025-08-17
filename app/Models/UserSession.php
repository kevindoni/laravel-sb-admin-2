<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'hotspot_user_id',
        'session_id',
        'username',
        'nas_ip',
        'framed_ip',
        'calling_station_id',
        'started_at',
        'last_update',
        'session_time',
        'upload_bytes',
        'download_bytes',
        'terminate_cause',
        'is_active',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_update' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function hotspotUser(): BelongsTo
    {
        return $this->belongsTo(HotspotUser::class);
    }

    public function getFormattedSessionTimeAttribute(): string
    {
        $hours = floor($this->session_time / 3600);
        $minutes = floor(($this->session_time % 3600) / 60);
        $seconds = $this->session_time % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function getFormattedUploadAttribute(): string
    {
        return $this->formatBytes($this->upload_bytes);
    }

    public function getFormattedDownloadAttribute(): string
    {
        return $this->formatBytes($this->download_bytes);
    }

    public function getFormattedTotalDataAttribute(): string
    {
        return $this->formatBytes($this->upload_bytes + $this->download_bytes);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }
}