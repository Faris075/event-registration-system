<?php
// ============================================================
// Mailable: EventCancelledMail
// ============================================================
// Sent to all confirmed and waitlisted registrants when an event's
// status transitions to 'cancelled' (triggered in EventController::update).
//
// Data available in the Blade template via public properties:
//   $event              — cancelled Event model
//   $attendeeName       — personalised salutation
//   $registrationStatus — 'confirmed' or 'waitlisted' (affects message copy)
//
// Best practices applied:
//  ✔ SerializesModels re-fetches models by PK if queued
//  ✔ Queueable allows async sending without controller changes
//  ✔ registrationStatus passed so the template can tailor the message
//    (e.g. 'Your confirmed booking' vs 'Your waitlist spot')
// ============================================================

namespace App\Mail;

use App\Models\Event;              // The event that was cancelled
use Illuminate\Bus\Queueable;     // Queue dispatch support
use Illuminate\Mail\Mailable;     // Base Mailable class
use Illuminate\Mail\Mailables\Content;   // Email body / view binding
use Illuminate\Mail\Mailables\Envelope;  // Subject and headers
use Illuminate\Queue\SerializesModels;  // Safe model serialisation for async jobs

class EventCancelledMail extends Mailable
{
    use Queueable;        // Async dispatch: add implements ShouldQueue to enable
    use SerializesModels; // Eloquent models are stored as ID references in the queue job payload

    /**
     * Build the cancellation email.
     *
     * @param  Event   $event               The event being cancelled
     * @param  string  $attendeeName        Personalised salutation ('Dear Jane')
     * @param  string  $registrationStatus  'confirmed' or 'waitlisted' — shown in email body
     */
    public function __construct(
        public Event $event,             // The cancelled event (title, date_time, etc.)
        public string $attendeeName,     // Recipient's name for personalisation
        public string $registrationStatus, // Used in template to differentiate confirmed vs waitlisted
    ) {
    }

    /**
     * Email envelope — sets the email subject line.
     * Prefix 'Event Cancelled:' makes the email purpose immediately clear in the inbox.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Event Cancelled: ' . $this->event->title, // e.g. 'Event Cancelled: Laravel Day 2026'
        );
    }

    /**
     * Email body — points to the Blade template.
     * The template has access to $event, $attendeeName, $registrationStatus.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.event-cancelled', // resources/views/emails/event-cancelled.blade.php
        );
    }
}
