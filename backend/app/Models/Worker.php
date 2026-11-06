<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Worker extends Model
{
    use HasFactory;

    public const STALE_AFTER_SECONDS = 60;

    protected $fillable = [
        'name',
        'hostname',
        'status',
        'current_job_id',
        'queues',
        'processed_jobs',
        'failed_jobs',
        'last_heartbeat_at',
        'started_at',
    ];

    protected $casts = [
        'queues' => 'array',
        'processed_jobs' => 'integer',
        'failed_jobs' => 'integer',
        'last_heartbeat_at' => 'datetime',
        'started_at' => 'datetime',
    ];

    public function currentJob(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'current_job_id');
    }

    public function scopeStale($query)
    {
        return $query->where('last_heartbeat_at', '<', now()->subSeconds(self::STALE_AFTER_SECONDS));
    }

    /** Effective status: a worker whose heartbeat is old is stale, whatever is stored. */
    public function effectiveStatus(): string
    {
        if ($this->status === 'offline') {
            return 'offline';
        }
        if ($this->last_heartbeat_at === null) {
            return 'offline';
        }
        if ($this->last_heartbeat_at->lt(now()->subSeconds(self::STALE_AFTER_SECONDS))) {
            return 'stale';
        }
        return $this->status;
    }

    public function heartbeat(): void
    {
        $this->last_heartbeat_at = now();
        $this->save();
    }

    public function incrementProcessed(): void
    {
        $this->increment('processed_jobs');
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_jobs');
    }
}
