<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'date_time' => $this->faker->dateTimeBetween('+1 day', '+3 months'),
            'location' => $this->faker->city(),
            'capacity' => $this->faker->numberBetween(50, 500),
            'price' => $this->faker->numberBetween(10, 500),
            'status' => $this->faker->randomElement(['draft', 'published', 'cancelled', 'completed']),
        ];
    }
}
