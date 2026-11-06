<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'processing', 'completed', 'failed', 'cancelled', 'retrying'];

    public const HANDLERS = ['SendEmail', 'GenerateReport', 'ResizeImage', 'SyncExternalData'];

    protected $fillable = [
        'queue_id',
        'queue_name',
        'handler',
        'payload',
        'status',
        'priority',
        'attempts',
        'max_attempts',
        'delay_seconds',
        'started_at',
        'finished_at',
        'duration_ms',
        'last_error',
        'available_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'priority' => 'integer',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'delay_seconds' => 'integer',
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'available_at' => 'datetime',
    ];

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    // Named jobAttempts (not attempts) so the relation never overwrites
    // the integer attempts column when the model is serialized to JSON.
    public function jobAttempts(): HasMany
    {
        return $this->hasMany(JobAttempt::class)->orderBy('attempt_number');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForQueue($query, string $queueName)
    {
        return $query->where('queue_name', $queueName);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function isRetryable(): bool
    {
        return $this->status === 'failed' && $this->attempts < $this->max_attempts;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'retrying'], true);
    }

    /** Record completion and derive duration from timestamps when available. */
    public function markCompleted(): void
    {
        $this->finished_at = now();
        if ($this->started_at !== null) {
            $this->duration_ms = (int) $this->started_at->diffInMilliseconds($this->finished_at);
        }
        $this->status = 'completed';
        $this->save();
    }
}
