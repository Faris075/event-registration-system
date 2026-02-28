<?php

namespace Database\Seeders;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * AttendeeRegistrationSeeder
 *
 * Ensures every user in the system has a matching attendee record, then
 * registers each user for 2–4 randomly chosen upcoming events.
 *
 * Steps:
 *  1. Promote any orphan attendees (attendees with no matching user) to full users.
 *  2. Create 10 fresh regular users to add volume.
 *  3. For EVERY non-admin user, guarantee an attendee row exists.
 *  4. Register each attendee in 2–4 random published future events.
 */
class AttendeeRegistrationSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::where('status', 'published')
            ->where('date_time', '>', now())
            ->get();

        if ($events->isEmpty()) {
            $this->command->warn('No published future events found — run EventSeeder first.');
            return;
        }

        // ── Step 1: promote orphan attendees to users ─────────────────────
        $existingUserEmails = User::pluck('email');
        $orphanAttendees    = Attendee::whereNotIn('email', $existingUserEmails)->get();

        foreach ($orphanAttendees as $attendee) {
            User::create([
                'name'              => $attendee->name,
                'email'             => $attendee->email,
                'password'          => Hash::make('Password1!'),
                'email_verified_at' => now(),
                'security_question' => '2',
                'security_answer'   => Hash::make('fluffy'),
                'is_admin'          => false,
            ]);
            $this->command->line("  Promoted orphan attendee → user: {$attendee->email}");
        }

        // ── Step 2: create 10 fresh regular users ─────────────────────────
        User::factory(10)->create(['is_admin' => false]);

        // ── Step 3 & 4: ensure attendee row + registrations for every non-admin user ──
        $allUsers = User::where('is_admin', false)->get();

        foreach ($allUsers as $user) {
            // Guarantee an attendee record keyed by email.
            $attendee = Attendee::firstOrCreate(
                ['email' => $user->email],
                [
                    'name'    => $user->name,
                    'phone'   => '+1' . fake()->numerify('##########'),
                    'company' => fake()->optional(0.5)->company(),
                ]
            );

            // Skip if this user already has registrations.
            $alreadyHasRegistrations = Registration::where('attendee_id', $attendee->id)->exists();
            if ($alreadyHasRegistrations) {
                continue;
            }

            // Pick 2–4 random events, avoiding date collisions within this user's set.
            $count          = rand(2, min(4, $events->count()));
            $shuffled       = $events->shuffle();
            $chosen         = collect();
            $chosenDates    = collect(); // track calendar dates already picked for this user

            foreach ($shuffled as $event) {
                if ($chosen->count() >= $count) {
                    break;
                }

                $eventDate = $event->date_time->toDateString();

                // Don't register the same user for two events on the same calendar day.
                if ($chosenDates->contains($eventDate)) {
                    continue;
                }

                $chosen->push($event);
                $chosenDates->push($eventDate);
            }

            foreach ($chosen as $event) {
                $confirmedCount = Registration::where('event_id', $event->id)
                    ->where('status', 'confirmed')
                    ->count();

                if ($confirmedCount < $event->capacity) {
                    $status          = 'confirmed';
                    $waitlistPosition = null;
                } else {
                    $waitlistCap      = (int) max(1, ceil($event->capacity * 0.25));
                    $waitlistedCount  = Registration::where('event_id', $event->id)
                        ->where('status', 'waitlisted')
                        ->count();

                    if ($waitlistedCount >= $waitlistCap) {
                        continue; // event + waitlist both full — skip
                    }

                    $status           = 'waitlisted';
                    $waitlistPosition = Registration::where('event_id', $event->id)
                        ->where('status', 'waitlisted')
                        ->max('waitlist_position') + 1;
                }

                Registration::create([
                    'event_id'          => $event->id,
                    'attendee_id'       => $attendee->id,
                    'status'            => $status,
                    'waitlist_position' => $waitlistPosition,
                    'payment_status'    => $status === 'confirmed' ? 'paid' : 'pending',
                    'is_admin_override' => false,
                    'registration_date' => now()->subDays(rand(1, 14)),
                ]);
            }
        }

        $this->command->info('AttendeeRegistrationSeeder complete.');
    }
}
