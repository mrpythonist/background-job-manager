<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class QueueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'priority' => $this->faker->numberBetween(1, 10),
            'concurrency' => $this->faker->numberBetween(1, 5),
            'is_paused' => false,
        ];
    }
}
