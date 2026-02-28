<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkCompletedEventsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_marks_only_past_published_events_as_completed(): void
    {
        $pastPublished = Event::factory()->create([
            'status' => 'published',
            'date_time' => now()->subDay(),
        ]);

        $futurePublished = Event::factory()->create([
            'status' => 'published',
            'date_time' => now()->addDay(),
        ]);

        $pastDraft = Event::factory()->create([
            'status' => 'draft',
            'date_time' => now()->subDay(),
        ]);

        $this->artisan('events:mark-completed')
            ->expectsOutput('Marked 1 event(s) as completed.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('events', [
            'id' => $pastPublished->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('events', [
            'id' => $futurePublished->id,
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('events', [
            'id' => $pastDraft->id,
            'status' => 'draft',
        ]);
    }
}
