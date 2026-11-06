<?php

namespace App\Jobs;

use App\Models\Job;
use App\Models\JobAttempt;
use App\Models\Worker;
use App\Services\JobDispatcher;
use App\Services\QueueStatsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBackgroundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $timeout = 300;

    public function __construct(public Job $job)
    {
        $this->tries = $job->max_attempts;
        $this->onQueue($job->queue_name);
    }

    public function handle(JobDispatcher $dispatcher): void
    {
        $job = $this->job->fresh();
        if ($job === null || $job->status === 'cancelled') {
            return;
        }

        $startedAt = now();
        $attemptNumber = $job->attempts + 1;
        $worker = $this->claimWorker($job);

        $job->update([
            'status' => 'processing',
            'started_at' => $startedAt,
            'attempts' => $attemptNumber,
        ]);

        $attempt = JobAttempt::create([
            'job_id' => $job->id,
            'attempt_number' => $attemptNumber,
            'worker_id' => $worker?->id,
            'status' => 'running',
            'started_at' => $startedAt,
        ]);

        try {
            $result = $dispatcher->runHandler($job->handler, $job->payload ?? []);
            Log::info('Background job completed', ['job_id' => $job->id, 'result' => $result]);

            $finishedAt = now();
            $attempt->update([
                'status' => 'completed',
                'finished_at' => $finishedAt,
                'duration_ms' => (int) $startedAt->diffInMilliseconds($finishedAt),
            ]);

            $job->markCompleted();
            $worker?->incrementProcessed();
        } catch (\Throwable $e) {
            $this->handleFailure($job, $attempt, $startedAt, $worker, $e);
        } finally {
            $this->releaseWorker($worker);
        }
    }

    protected function handleFailure(Job $job, JobAttempt $attempt, $startedAt, ?Worker $worker, \Throwable $e): void
    {
        $finishedAt = now();
        $attempt->update([
            'status' => 'failed',
            'finished_at' => $finishedAt,
            'duration_ms' => (int) $startedAt->diffInMilliseconds($finishedAt),
            'error' => $e->getMessage(),
        ]);

        $worker?->incrementFailed();
        Log::warning('Background job failed', ['job_id' => $job->id, 'error' => $e->getMessage()]);

        if ($job->attempts >= $job->max_attempts) {
            $job->update([
                'status' => 'failed',
                'finished_at' => $finishedAt,
                'duration_ms' => (int) $startedAt->diffInMilliseconds($finishedAt),
                'last_error' => $e->getMessage(),
            ]);
            DB::table('failed_jobs')->insert([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'connection' => 'redis',
                'queue' => $job->queue_name,
                'payload' => json_encode(['job_id' => $job->id, 'handler' => $job->handler, 'payload' => $job->payload]),
                'exception' => $e->getMessage(),
                'failed_at' => $finishedAt,
            ]);
            $this->fail($e);
            return;
        }

        $delay = QueueStatsService::backoffDelaySeconds($job->attempts);
        $job->update([
            'status' => 'pending',
            'last_error' => $e->getMessage(),
            'available_at' => now()->addSeconds($delay),
        ]);
        $this->release($delay);
    }

    protected function claimWorker(Job $job): ?Worker
    {
        $worker = Worker::where('status', 'idle')->orderBy('last_heartbeat_at')->first();
        if ($worker === null) {
            return null;
        }
        $worker->update(['status' => 'busy', 'current_job_id' => $job->id, 'last_heartbeat_at' => now()]);
        return $worker;
    }

    protected function releaseWorker(?Worker $worker): void
    {
        if ($worker === null) {
            return;
        }
        $worker->update(['status' => 'idle', 'current_job_id' => null, 'last_heartbeat_at' => now()]);
    }
}
