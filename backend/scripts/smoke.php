<?php

/**
 * Plain-PHP smoke test for QueueStatsService. No framework, no vendor/.
 * Run: php backend/scripts/smoke.php
 */

declare(strict_types=1);

require __DIR__ . '/../app/Services/QueueStatsService.php';

use App\Services\QueueStatsService;

$failures = 0;

function check(string $label, bool $ok, mixed $actual = null): void
{
    global $failures;
    if ($ok) {
        echo "PASS: {$label}\n";
    } else {
        $failures++;
        echo "FAIL: {$label} (got " . var_export($actual, true) . ")\n";
    }
}

$stats = new QueueStatsService();
$now = strtotime('2026-11-06 12:00:00 UTC');

$jobs = [
    ['status' => 'completed', 'queue_name' => 'emails', 'duration_ms' => 100, 'finished_at' => $now - 600, 'created_at' => $now - 3600],
    ['status' => 'completed', 'queue_name' => 'emails', 'duration_ms' => 300, 'finished_at' => $now - 1800, 'created_at' => $now - 3600],
    ['status' => 'failed', 'queue_name' => 'reports', 'duration_ms' => 50, 'finished_at' => $now - 1200, 'created_at' => $now - 3600],
    ['status' => 'pending', 'queue_name' => 'reports', 'duration_ms' => null, 'finished_at' => null, 'created_at' => $now - 60],
    ['status' => 'processing', 'queue_name' => 'images', 'duration_ms' => null, 'finished_at' => null, 'created_at' => $now - 30],
];

$byStatus = $stats->countsByStatus($jobs);
check('per-status counts', $byStatus['completed'] === 2 && $byStatus['failed'] === 1 && $byStatus['pending'] === 1 && $byStatus['processing'] === 1 && $byStatus['cancelled'] === 0, $byStatus);

$byQueue = $stats->countsByQueue($jobs);
check('per-queue counts', $byQueue['emails']['completed'] === 2 && $byQueue['reports']['failed'] === 1 && $byQueue['images']['processing'] === 1, $byQueue);

check('failure rate is 33.33%', $stats->failureRate($jobs) === 33.33, $stats->failureRate($jobs));
check('average duration is 200ms', $stats->averageDurationMs($jobs) === 200.0, $stats->averageDurationMs($jobs));
check('throughput last hour is 2', $stats->throughput($jobs, 3600, $now) === 2, $stats->throughput($jobs, 3600, $now));

$empty = $stats->overview([]);
check('empty input: total 0', $empty['total'] === 0, $empty['total']);
check('empty input: failure rate 0.0', $empty['failure_rate_percent'] === 0.0, $empty['failure_rate_percent']);
check('empty input: avg duration null', $empty['average_duration_ms'] === null, $empty['average_duration_ms']);

check('backoff 1st attempt is 10s', QueueStatsService::backoffDelaySeconds(1) === 10);
check('backoff 2nd attempt is 20s', QueueStatsService::backoffDelaySeconds(2) === 20);
check('backoff 3rd attempt is 40s', QueueStatsService::backoffDelaySeconds(3) === 40);
check('backoff 4th attempt is 80s', QueueStatsService::backoffDelaySeconds(4) === 80);

$series = $stats->timeseries($jobs, 2, $now);
check('timeseries returns 2 buckets', count($series) === 2, count($series));
check('timeseries last bucket has 2 completed 1 failed', $series[1]['completed'] === 2 && $series[1]['failed'] === 1, $series[1]);

if ($failures > 0) {
    echo "\n{$failures} check(s) FAILED.\n";
    exit(1);
}

echo "\nAll smoke checks passed.\n";
exit(0);
