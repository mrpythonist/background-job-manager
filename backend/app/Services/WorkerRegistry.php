<?php

namespace App\Services;

use App\Models\Worker;

class WorkerRegistry
{
    public function register(string $name, ?string $hostname, array $queues = []): Worker
    {
        return Worker::updateOrCreate(
            ['name' => $name],
            [
                'hostname' => $hostname ?? gethostname() ?: 'unknown',
                'status' => 'idle',
                'queues' => $queues,
                'last_heartbeat_at' => now(),
                'started_at' => now(),
            ]
        );
    }

    public function heartbeat(Worker $worker): void
    {
        $worker->heartbeat();
    }

    /** Mark workers with no heartbeat for $staleAfterSeconds as offline. Returns the count. */
    public function pruneStale(int $staleAfterSeconds = 60): int
    {
        return Worker::where('status', '!=', 'offline')
            ->where('last_heartbeat_at', '<', now()->subSeconds($staleAfterSeconds))
            ->update(['status' => 'offline', 'current_job_id' => null]);
    }

    /** @return array<int, array<string, mixed>> */
    public function statusList(): array
    {
        return Worker::orderBy('name')->get()->map(fn (Worker $w) => [
            'id' => $w->id,
            'name' => $w->name,
            'hostname' => $w->hostname,
            'status' => $w->effectiveStatus(),
            'current_job_id' => $w->current_job_id,
            'queues' => $w->queues ?? [],
            'processed_jobs' => $w->processed_jobs,
            'failed_jobs' => $w->failed_jobs,
            'last_heartbeat_at' => $w->last_heartbeat_at?->toIso8601String(),
            'started_at' => $w->started_at?->toIso8601String(),
        ])->all();
    }
}
