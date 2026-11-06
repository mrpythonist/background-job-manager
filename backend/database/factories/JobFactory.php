<?php

namespace Database\Factories;

use App\Models\Queue;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    public function definition(): array
    {
        $queue = Queue::inRandomOrder()->first() ?? Queue::factory()->create();

        return [
            'queue_id' => $queue->id,
            'queue_name' => $queue->name,
            'handler' => $this->faker->randomElement(['SendEmail', 'GenerateReport', 'ResizeImage', 'SyncExternalData']),
            'payload' => ['simulate' => ['duration_ms' => $this->faker->numberBetween(100, 3000)]],
            'status' => 'pending',
            'priority' => $this->faker->numberBetween(1, 10),
            'attempts' => 0,
            'max_attempts' => 3,
            'delay_seconds' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'attempts' => 1,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now()->subMinutes(4),
            'duration_ms' => $this->faker->numberBetween(200, 5000),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'attempts' => 3,
            'last_error' => 'Simulated handler failure',
        ]);
    }
}
