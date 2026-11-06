<?php

namespace App\Services;

use App\Jobs\ProcessBackgroundJob;
use App\Models\Job;
use App\Models\Queue;

/**
 * Creates job records and pushes them onto the named Redis queue.
 *
 * The handler registry is an allowlist: only the handlers listed here can
 * ever run. A request may name a handler, never a class.
 */
class JobDispatcher
{
    public const HANDLERS = ['SendEmail', 'GenerateReport', 'ResizeImage', 'SyncExternalData'];

    public static function isAllowedHandler(string $handler): bool
    {
        return in_array($handler, self::HANDLERS, true);
    }

    /**
     * @param array<string, mixed> $data validated input (queue, handler, payload, priority, max_attempts, delay_seconds)
     */
    public function dispatch(array $data): Job
    {
        $queue = Queue::firstOrCreate(
            ['name' => $data['queue']],
            ['priority' => 5, 'concurrency' => 1]
        );

        $job = Job::create([
            'queue_id' => $queue->id,
            'queue_name' => $queue->name,
            'handler' => $data['handler'],
            'payload' => $data['payload'] ?? [],
            'status' => 'pending',
            'priority' => $data['priority'] ?? 5,
            'max_attempts' => $data['max_attempts'] ?? 3,
            'attempts' => 0,
            'delay_seconds' => $data['delay_seconds'] ?? 0,
            'available_at' => now()->addSeconds($data['delay_seconds'] ?? 0),
        ]);

        $this->pushToQueue($job);

        return $job;
    }

    /** Re-queue a failed job with exponential backoff. Returns null when not retryable. */
    public function retry(Job $job): ?Job
    {
        if (! $job->isRetryable()) {
            return null;
        }

        $delay = QueueStatsService::backoffDelaySeconds($job->attempts);
        $job->status = 'retrying';
        $job->delay_seconds = $delay;
        $job->available_at = now()->addSeconds($delay);
        $job->last_error = null;
        $job->save();

        $this->pushToQueue($job, $delay);

        return $job;
    }

    public function cancel(Job $job): bool
    {
        if (! $job->isCancellable()) {
            return false;
        }
        $job->status = 'cancelled';
        $job->save();
        return true;
    }

    protected function pushToQueue(Job $job, int $delaySeconds = 0): void
    {
        $queued = (new ProcessBackgroundJob($job))->onQueue($job->queue_name);
        if ($delaySeconds > 0) {
            $queued->delay(now()->addSeconds($delaySeconds));
        }
        dispatch($queued);
    }

    /**
     * Run a handler in demo/simulate mode. In v0.1 handlers are simulated:
     * they sleep for simulate.duration_ms and optionally throw
     * simulate.fail_message. No real email is sent, no real file is written.
     *
     * @param array<string, mixed> $payload
     * @throws \RuntimeException when the simulation is configured to fail
     */
    public function runHandler(string $handler, array $payload): string
    {
        $simulate = $payload['simulate'] ?? [];
        $durationMs = (int) ($simulate['duration_ms'] ?? 500);
        $durationMs = max(0, min($durationMs, 60_000));
        $shouldFail = (bool) ($simulate['should_fail'] ?? false);

        usleep($durationMs * 1000);

        if ($shouldFail) {
            throw new \RuntimeException((string) ($simulate['fail_message'] ?? 'Simulated handler failure'));
        }

        return match ($handler) {
            'SendEmail' => 'Email sent (simulated) to ' . ($payload['to'] ?? 'unknown recipient'),
            'GenerateReport' => 'Report generated (simulated): ' . ($payload['report'] ?? 'report'),
            'ResizeImage' => 'Image resized (simulated): ' . ($payload['path'] ?? 'image') . ' to ' . ($payload['width'] ?? 800) . 'px',
            'SyncExternalData' => 'External data synced (simulated) from ' . ($payload['source'] ?? 'unknown source'),
            default => throw new \InvalidArgumentException("Unknown handler: {$handler}"),
        };
    }
}
