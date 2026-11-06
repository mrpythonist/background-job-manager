<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Queue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'priority',
        'concurrency',
        'is_paused',
    ];

    protected $casts = [
        'priority' => 'integer',
        'concurrency' => 'integer',
        'is_paused' => 'boolean',
    ];

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_paused', false);
    }

    public function pendingJobsCount(): int
    {
        return $this->jobs()->where('status', 'pending')->count();
    }
}
