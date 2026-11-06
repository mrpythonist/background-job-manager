<?php

namespace Tests\Feature;

use App\Models\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_job_returns_201(): void
    {
        Queue::create(['name' => 'emails']);

        $response = $this->postJson('/api/jobs', [
            'queue' => 'emails',
            'handler' => 'SendEmail',
            'payload' => ['to' => 'user@example.com'],
            'priority' => 5,
            'max_attempts' => 3,
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('handler', 'SendEmail');

        $this->assertDatabaseHas('jobs', ['queue_name' => 'emails', 'handler' => 'SendEmail']);
    }

    public function test_submit_job_rejects_unknown_handler(): void
    {
        Queue::create(['name' => 'emails']);

        $response = $this->postJson('/api/jobs', [
            'queue' => 'emails',
            'handler' => 'DeleteEverything',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('handler');
    }

    public function test_submit_job_rejects_missing_queue(): void
    {
        $response = $this->postJson('/api/jobs', [
            'queue' => 'no-such-queue',
            'handler' => 'SendEmail',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('queue');
    }

    public function test_retry_only_works_for_failed_jobs(): void
    {
        $queue = Queue::create(['name' => 'emails']);
        $job = \App\Models\Job::create([
            'queue_id' => $queue->id,
            'queue_name' => 'emails',
            'handler' => 'SendEmail',
            'payload' => [],
            'status' => 'pending',
            'max_attempts' => 3,
        ]);

        $this->postJson("/api/jobs/{$job->id}/retry")->assertUnprocessable();
    }

    public function test_stats_overview_returns_totals(): void
    {
        Queue::create(['name' => 'emails']);

        $response = $this->getJson('/api/stats/overview');

        $response->assertOk()
            ->assertJsonStructure(['total', 'by_status', 'throughput_last_hour', 'average_duration_ms', 'failure_rate_percent']);
    }
}
