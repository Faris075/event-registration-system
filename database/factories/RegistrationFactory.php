<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Attendee;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'attendee_id' => Attendee::factory(),
            'registration_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'status' => $this->faker->randomElement(['confirmed', 'waitlisted', 'cancelled']),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'refunded']),
        ];
    }
}
