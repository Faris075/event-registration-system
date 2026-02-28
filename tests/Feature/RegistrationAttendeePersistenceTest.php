<?php

namespace Tests\Feature;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationAttendeePersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function paymentData(): array
    {
        return [
            'card_name'   => 'Test User',
            'card_number' => '4111111111111111',
            'card_expiry' => '12/28',
            'card_cvv'    => '123',
        ];
    }

    public function test_registration_adds_user_information_to_database(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create([
            'status' => 'published',
            'date_time' => now()->addDay(),
        ]);

        $this->actingAs($user)->post(route('events.register', $event), [
            'name' => 'Lina Ahmed',
            'email' => 'lina@example.com',
            'phone' => '+201001112223',
            'company' => 'Skyline Media',
        ])->assertRedirect(route('events.payment.page', $event));

        $this->post(route('events.payment.process', $event), $this->paymentData())
            ->assertRedirect(route('events.registration.confirmation', $event));

        $this->assertDatabaseHas('attendees', [
            'name' => 'Lina Ahmed',
            'email' => 'lina@example.com',
            'phone' => '+201001112223',
            'company' => 'Skyline Media',
        ]);
    }

    public function test_registration_updates_existing_user_information_in_database(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create([
            'status' => 'published',
            'date_time' => now()->addDay(),
        ]);

        Attendee::factory()->create([
            'name' => 'Lina Old',
            'email' => 'lina@example.com',
            'phone' => '+201000000000',
            'company' => 'Old Company',
        ]);

        $this->actingAs($user)->post(route('events.register', $event), [
            'name' => 'Lina Ahmed',
            'email' => 'lina@example.com',
            'phone' => '+201001112223',
            'company' => 'Skyline Media',
        ])->assertRedirect(route('events.payment.page', $event));

        $this->post(route('events.payment.process', $event), $this->paymentData())
            ->assertRedirect(route('events.registration.confirmation', $event));

        $this->assertDatabaseHas('attendees', [
            'email' => 'lina@example.com',
            'name' => 'Lina Ahmed',
            'phone' => '+201001112223',
            'company' => 'Skyline Media',
        ]);

        $this->assertSame(1, Attendee::query()->where('email', 'lina@example.com')->count());
    }

    public function test_duplicate_registration_updates_attendee_information_without_creating_new_registration(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create([
            'status' => 'published',
            'date_time' => now()->addDay(),
        ]);

        $attendee = Attendee::factory()->create([
            'name' => 'Old Name',
            'email' => 'repeat@example.com',
            'phone' => '+201000000000',
            'company' => 'Old Co',
        ]);

        Registration::factory()->create([
            'event_id' => $event->id,
            'attendee_id' => $attendee->id,
            'status' => 'confirmed',
            'waitlist_position' => null,
        ]);

        $this->actingAs($user)->post(route('events.register', $event), [
            'name' => 'New Name',
            'email' => 'repeat@example.com',
            'phone' => '+201001112223',
            'company' => 'New Co',
        ])->assertRedirect(route('events.payment.page', $event));

        $this->post(route('events.payment.process', $event), $this->paymentData())
            ->assertRedirect(route('events.registration.confirmation', $event));

        $this->assertDatabaseHas('attendees', [
            'email' => 'repeat@example.com',
            'name' => 'New Name',
            'phone' => '+201001112223',
            'company' => 'New Co',
        ]);

        $this->assertSame(1, Registration::query()->where('event_id', $event->id)->count());
    }
}
