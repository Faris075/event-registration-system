<?php

namespace Tests\Feature;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_registrations_as_csv_with_filters(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $event = Event::factory()->create([
            'status' => 'published',
            'date_time' => now()->addDay(),
        ]);

        $waitlistedAttendee = Attendee::factory()->create([
            'name' => 'Waitlisted Person',
            'email' => 'waitlisted@example.com',
        ]);

        $confirmedAttendee = Attendee::factory()->create([
            'name' => 'Confirmed Person',
            'email' => 'confirmed@example.com',
        ]);

        Registration::factory()->create([
            'event_id' => $event->id,
            'attendee_id' => $waitlistedAttendee->id,
            'status' => 'waitlisted',
            'payment_status' => 'pending',
            'waitlist_position' => 1,
        ]);

        Registration::factory()->create([
            'event_id' => $event->id,
            'attendee_id' => $confirmedAttendee->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'waitlist_position' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.events.registrations.export', [
            'event' => $event,
            'status' => 'waitlisted',
            'payment_status' => 'pending',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        if (!str_contains($csv, 'Waitlisted Person')) {
            throw new \RuntimeException('Expected waitlisted attendee row in CSV export.');
        }

        if (str_contains($csv, 'Confirmed Person')) {
            throw new \RuntimeException('Filtered CSV should not include confirmed attendee row.');
        }
    }
}
