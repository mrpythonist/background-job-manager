<?php

namespace App\Services;

/**
 * Pure PHP statistics for the job dashboard.
 *
 * No framework dependency: methods take plain arrays and return plain
 * arrays, so this class can be unit-tested and smoke-tested with plain
 * PHP, without booting Laravel. Job rows are expected as associative
 * arrays with keys: status, queue_name, duration_ms, finished_at
 * (unix timestamp or null), created_at (unix timestamp or null).
 */
class QueueStatsService
{
    public const STATUSES = ['pending', 'processing', 'completed', 'failed', 'cancelled', 'retrying'];

    /**
     * @param array<int, array<string, mixed>> $jobs
     * @return array<string, int> counts keyed by status, always with all statuses present
     */
    public function countsByStatus(array $jobs): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($jobs as $job) {
            $status = $job['status'] ?? 'pending';
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }
        return $counts;
    }

    /**
     * @param array<int, array<string, mixed>> $jobs
     * @return array<string, array<string, int>> per-queue counts keyed by queue name
     */
    public function countsByQueue(array $jobs): array
    {
        $result = [];
        foreach ($jobs as $job) {
            $queue = (string) ($job['queue_name'] ?? 'default');
            if (! isset($result[$queue])) {
                $result[$queue] = array_fill_keys(self::STATUSES, 0);
            }
            $status = $job['status'] ?? 'pending';
            if (isset($result[$queue][$status])) {
                $result[$queue][$status]++;
            }
        }
        ksort($result);
        return $result;
    }

    /** Failure rate as a percentage of finished jobs (completed + failed). Returns 0.0 when nothing finished. */
    public function failureRate(array $jobs): float
    {
        $completed = 0;
        $failed = 0;
        foreach ($jobs as $job) {
            if (($job['status'] ?? null) === 'completed') {
                $completed++;
            } elseif (($job['status'] ?? null) === 'failed') {
                $failed++;
            }
        }
        $finished = $completed + $failed;
        if ($finished === 0) {
            return 0.0;
        }
        return round(($failed / $finished) * 100, 2);
    }

    /** Average execution duration in ms over completed jobs. Returns null when none completed. */
    public function averageDurationMs(array $jobs): ?float
    {
        $total = 0;
        $count = 0;
        foreach ($jobs as $job) {
            if (($job['status'] ?? null) === 'completed' && isset($job['duration_ms'])) {
                $total += (int) $job['duration_ms'];
                $count++;
            }
        }
        if ($count === 0) {
            return null;
        }
        return round($total / $count, 2);
    }

    /** Jobs finished (completed) in the last $seconds seconds, relative to $now (unix timestamp). */
    public function throughput(array $jobs, int $seconds = 3600, ?int $now = null): int
    {
        $now ??= time();
        $count = 0;
        foreach ($jobs as $job) {
            if (($job['status'] ?? null) !== 'completed') {
                continue;
            }
            $finishedAt = $job['finished_at'] ?? null;
            if ($finishedAt !== null && $finishedAt >= $now - $seconds) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Bucket completed/failed jobs per hour over the last $hours hours.
     *
     * @return array<int, array{bucket: string, completed: int, failed: int}>
     */
    public function timeseries(array $jobs, int $hours = 24, ?int $now = null): array
    {
        $now ??= time();
        $start = $now - $hours * 3600;
        $buckets = [];
        for ($i = 0; $i < $hours; $i++) {
            $bucketStart = $start + $i * 3600;
            $buckets[$i] = [
                'bucket' => gmdate('Y-m-d H:00', $bucketStart),
                'completed' => 0,
                'failed' => 0,
            ];
        }
        foreach ($jobs as $job) {
            $status = $job['status'] ?? null;
            if ($status !== 'completed' && $status !== 'failed') {
                continue;
            }
            $finishedAt = $job['finished_at'] ?? null;
            if ($finishedAt === null || $finishedAt < $start || $finishedAt > $now) {
                continue;
            }
            $index = (int) floor(($finishedAt - $start) / 3600);
            if ($index >= $hours) {
                $index = $hours - 1;
            }
            $buckets[$index][$status]++;
        }
        return array_values($buckets);
    }

    /**
     * Exponential backoff delay in seconds before the next attempt.
     * attempt is 1-based (the attempt that just failed). Base is 10 seconds
     * by default: 10, 20, 40, 80, ...
     */
    public static function backoffDelaySeconds(int $attempt, int $baseSeconds = 10): int
    {
        if ($attempt < 1) {
            $attempt = 1;
        }
        return $baseSeconds * (2 ** ($attempt - 1));
    }

    /** One-call overview combining the pieces above. */
    public function overview(array $jobs, ?int $now = null): array
    {
        $now ??= time();
        $byStatus = $this->countsByStatus($jobs);
        return [
            'total' => count($jobs),
            'by_status' => $byStatus,
            'throughput_last_hour' => $this->throughput($jobs, 3600, $now),
            'average_duration_ms' => $this->averageDurationMs($jobs),
            'failure_rate_percent' => $this->failureRate($jobs),
        ];
    }
}
