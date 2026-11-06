<?php

namespace Tests\Unit;

use App\Services\QueueStatsService;
use PHPUnit\Framework\TestCase;

class QueueStatsServiceTest extends TestCase
{
    protected QueueStatsService $stats;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stats = new QueueStatsService();
    }

    public function test_counts_by_status_includes_all_statuses(): void
    {
        $jobs = [
            ['status' => 'completed'],
            ['status' => 'completed'],
            ['status' => 'failed'],
            ['status' => 'pending'],
        ];

        $counts = $this->stats->countsByStatus($jobs);

        $this->assertSame(2, $counts['completed']);
        $this->assertSame(1, $counts['failed']);
        $this->assertSame(1, $counts['pending']);
        $this->assertSame(0, $counts['processing']);
        $this->assertSame(0, $counts['cancelled']);
        $this->assertSame(0, $counts['retrying']);
    }

    public function test_empty_input_returns_zeroes(): void
    {
        $this->assertSame(0, array_sum($this->stats->countsByStatus([])));
        $this->assertSame(0.0, $this->stats->failureRate([]));
        $this->assertNull($this->stats->averageDurationMs([]));
        $this->assertSame([], $this->stats->countsByQueue([]));
    }

    public function test_failure_rate_is_zero_when_nothing_finished(): void
    {
        $this->assertSame(0.0, $this->stats->failureRate([['status' => 'pending']]));
    }

    public function test_failure_rate_counts_only_finished_jobs(): void
    {
        $jobs = [
            ['status' => 'completed'],
            ['status' => 'completed'],
            ['status' => 'failed'],
            ['status' => 'pending'],
        ];
        $this->assertSame(33.33, $this->stats->failureRate($jobs));
    }

    public function test_average_duration_uses_completed_jobs_only(): void
    {
        $jobs = [
            ['status' => 'completed', 'duration_ms' => 100],
            ['status' => 'completed', 'duration_ms' => 300],
            ['status' => 'failed', 'duration_ms' => 9999],
        ];
        $this->assertSame(200.0, $this->stats->averageDurationMs($jobs));
    }

    public function test_backoff_delay_doubles_each_attempt(): void
    {
        $this->assertSame(10, QueueStatsService::backoffDelaySeconds(1));
        $this->assertSame(20, QueueStatsService::backoffDelaySeconds(2));
        $this->assertSame(40, QueueStatsService::backoffDelaySeconds(3));
        $this->assertSame(80, QueueStatsService::backoffDelaySeconds(4));
        $this->assertSame(5, QueueStatsService::backoffDelaySeconds(1, 5));
    }

    public function test_timeseries_buckets_completed_and_failed(): void
    {
        $now = strtotime('2026-11-06 12:00:00 UTC');
        $jobs = [
            ['status' => 'completed', 'finished_at' => $now - 1800],
            ['status' => 'failed', 'finished_at' => $now - 1800],
            ['status' => 'completed', 'finished_at' => $now - 90000],
        ];

        $series = $this->stats->timeseries($jobs, 2, $now);

        $this->assertCount(2, $series);
        $this->assertSame(1, $series[1]['completed']);
        $this->assertSame(1, $series[1]['failed']);
        $this->assertSame(0, $series[0]['completed']);
    }
}
