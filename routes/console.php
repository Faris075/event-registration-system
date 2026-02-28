<?php

use App\Mail\EventReminderMail;
use App\Models\Event;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('events:mark-completed', function () {
    $updatedCount = Event::query()
        ->where('status', 'published')
        ->where('date_time', '<', now())
        ->update(['status' => 'completed']);

    $this->info("Marked {$updatedCount} event(s) as completed.");
})->purpose('Mark past published events as completed');

Schedule::command('events:mark-completed')->daily();

Artisan::command('events:send-reminders', function () {
    $events = Event::query()
        ->where('status', 'published')
        ->whereBetween('date_time', [now()->addHours(23), now()->addHours(25)])
        ->with(['registrations.attendee'])
        ->get();

    $sent = 0;

    foreach ($events as $event) {
        /** @var Event $event */
        foreach ($event->registrations as $registration) {
            if ($registration->status !== 'confirmed') {
                continue;
            }

            $attendee = $registration->attendee;

            if (! $attendee) {
                continue;
            }

            try {
                Mail::to($attendee->email)->send(new EventReminderMail($event, $attendee->name));
                $sent++;
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    $this->info("Sent {$sent} reminder email(s) for " . $events->count() . ' event(s).');
})->purpose('Send 24-hour reminder emails to confirmed registrants');

Schedule::command('events:send-reminders')->hourly();
