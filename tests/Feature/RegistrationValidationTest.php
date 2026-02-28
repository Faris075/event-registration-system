<?php

namespace Tests\Feature;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_create_event_with_past_date(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('events.store'), [
            'title' => 'Past Event',
            'description' => 'This should fail',
            'date_time' => now()->subDay()->toDateTimeString(),
            'location' => 'Test Venue',
            'capacity' => 10,
            'price' => 10,
            'status' => 'published',
        ]);

        $response->assertSessionHasErrors('date_time');
        $this->assertDatabaseMissing('events', [
            'title' => 'Past Event',
        ]);
    }

    public function test_registration_rejects_invalid_phone_format(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create([
            'status' => 'published',
            'date_time' => now()->addDay(),
        ]);

        $response = $this->actingAs($user)->post(route('events.register', $event), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => 'invalid-phone@@@',
            'company' => 'Acme',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_registration_is_blocked_after_event_date(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create([
            'status' => 'published',
            'date_time' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->from(route('events.register.page', $event))->post(route('events.register', $event), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
            'company' => 'Acme',
        ]);

        $response->assertRedirect(route('events.register.page', $event));
        $response->assertSessionHasErrors('registration');
        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_duplicate_registration_is_prevented_for_same_event_and_attendee(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create([
            'status' => 'published',
            'date_time' => now()->addDays(2),
        ]);

        $attendee = Attendee::factory()->create([
            'email' => 'repeat@example.com',
        ]);

        Registration::factory()->create([
            'event_id' => $event->id,
            'attendee_id' => $attendee->id,
            'status' => 'confirmed',
            'waitlist_position' => null,
        ]);

        $this->actingAs($user)->post(route('events.register', $event), [
            'name' => 'Repeat User',
            'email' => 'repeat@example.com',
            'phone' => '+1234567890',
            'company' => 'Acme',
        ])->assertRedirect(route('events.payment.page', $event));

        $this->post(route('events.payment.process', $event), [
            'card_name'   => 'Repeat User',
            'card_number' => '4111111111111111',
            'card_expiry' => '12/28',
            'card_cvv'    => '123',
        ])->assertRedirect(route('events.registration.confirmation', $event));

        $this->assertDatabaseCount('registrations', 1);
    }
}
