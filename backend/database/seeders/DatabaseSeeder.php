<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\Queue;
use App\Models\Worker;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $queues = collect([
            ['name' => 'default', 'priority' => 5, 'concurrency' => 2],
            ['name' => 'emails', 'priority' => 3, 'concurrency' => 2],
            ['name' => 'reports', 'priority' => 5, 'concurrency' => 1],
            ['name' => 'images', 'priority' => 7, 'concurrency' => 3],
            ['name' => 'sync', 'priority' => 8, 'concurrency' => 1],
        ])->map(fn (array $q) => Queue::firstOrCreate(['name' => $q['name']], $q));

        foreach ($queues as $queue) {
            Job::factory()->count(3)->create(['queue_id' => $queue->id, 'queue_name' => $queue->name]);
            Job::factory()->count(2)->completed()->create(['queue_id' => $queue->id, 'queue_name' => $queue->name]);
            Job::factory()->failed()->create(['queue_id' => $queue->id, 'queue_name' => $queue->name]);
        }

        Job::factory()->count(2)->create([
            'status' => 'processing',
            'attempts' => 1,
            'started_at' => now()->subSeconds(30),
        ]);

        Worker::create([
            'name' => 'worker-1',
            'hostname' => 'bjm-worker',
            'status' => 'busy',
            'queues' => ['default', 'emails', 'reports', 'images', 'sync'],
            'processed_jobs' => 42,
            'failed_jobs' => 3,
            'last_heartbeat_at' => now(),
            'started_at' => now()->subHours(2),
        ]);
        Worker::create([
            'name' => 'worker-2',
            'hostname' => 'bjm-worker',
            'status' => 'idle',
            'queues' => ['emails', 'images'],
            'processed_jobs' => 27,
            'failed_jobs' => 1,
            'last_heartbeat_at' => now(),
            'started_at' => now()->subHours(2),
        ]);
        Worker::create([
            'name' => 'worker-3',
            'hostname' => 'bjm-worker',
            'status' => 'offline',
            'queues' => ['reports'],
            'processed_jobs' => 15,
            'failed_jobs' => 5,
            'last_heartbeat_at' => now()->subMinutes(30),
            'started_at' => now()->subDays(1),
        ]);
    }
}
