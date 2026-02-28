<?php

namespace Database\Seeders;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AttendeeRegistrationSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::all();

        if ($events->isEmpty()) {
            $this->command->warn('No events found — run EventSeeder first.');
            return;
        }

        // Create 12 regular users with attendee records and registrations.
        $users = User::factory(12)->create(['is_admin' => false]);

        foreach ($users as $user) {
            // Make sure an attendee row exists with the same email as the user.
            $attendee = Attendee::firstOrCreate(
                ['email' => $user->email],
                [
                    'name'    => $user->name,
                    'phone'   => '+1' . fake()->numerify('##########'),
                    'company' => fake()->optional(0.6)->company(),
                ]
            );

            // Register each user in 2–4 random events (no duplicates).
            $selectedEvents = $events->shuffle()->take(rand(2, min(4, $events->count())));

            foreach ($selectedEvents as $event) {
                $alreadyRegistered = Registration::where('event_id', $event->id)
                    ->where('attendee_id', $attendee->id)
                    ->exists();

                if ($alreadyRegistered) {
                    continue;
                }

                $confirmedCount = Registration::where('event_id', $event->id)
                    ->where('status', 'confirmed')
                    ->count();

                [$status, $waitlistPosition] = $confirmedCount < $event->capacity
                    ? ['confirmed', null]
                    : ['waitlisted', Registration::where('event_id', $event->id)
                        ->where('status', 'waitlisted')
                        ->max('waitlist_position') + 1];

                Registration::create([
                    'event_id'          => $event->id,
                    'attendee_id'       => $attendee->id,
                    'status'            => $status,
                    'waitlist_position' => $waitlistPosition,
                    'payment_status'    => $status === 'confirmed' ? 'paid' : 'pending',
                    'is_admin_override' => false,
                    'registration_date' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        // Also make sure all existing attendees who don't have a user account get one.
        $orphanAttendees = Attendee::whereNotIn('email', User::pluck('email'))->get();
        foreach ($orphanAttendees as $attendee) {
            User::create([
                'name'              => $attendee->name,
                'email'             => $attendee->email,
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
                'security_question' => '2',
                'security_answer'   => Hash::make('fluffy'),
                'is_admin'          => false,
            ]);
        }
    }
}
