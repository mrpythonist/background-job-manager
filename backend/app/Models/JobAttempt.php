<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'attempt_number',
        'worker_id',
        'status',
        'started_at',
        'finished_at',
        'duration_ms',
        'error',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
