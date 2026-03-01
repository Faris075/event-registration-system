<?php
// ============================================================
// Mailable: RegistrationConfirmationMail
// ------------------------------------------------------------
// Sent to an attendee immediately after a successful registration
// (both confirmed and waitlisted outcomes).
//
// Data passed to the Blade view via public properties:
//   $event              — full Event model (title, date_time, location)
//   $attendeeName       — personalized salutation name
//   $registrationStatus — 'confirmed' or 'waitlisted'
//   $waitlistPosition   — queue position; null for confirmed attendees
//
// Best practices applied:
//  ✔ Public constructor properties (PHP 8.0 promoted properties)
//    are automatically available in the Blade view template
//  ✔ SerializesModels: Eloquent models are serialized by primary key
//    and re-fetched on de-queue (important if queuing is enabled)
//  ✔ Queueable trait: implement ShouldQueue on the class to turn
//    this into an async job (no code change required in the controller)
//  ✔ Envelope/Content split follows modern Laravel Mailable API
//  ✔ Dynamic subject includes event title for inbox scannability
// ============================================================

namespace App\Mail;

use App\Models\Event;                        // The event being registered for
use Illuminate\Bus\Queueable;               // Enables job queue dispatch if ShouldQueue is added
use Illuminate\Mail\Mailable;               // Base class for all Laravel mailables
use Illuminate\Mail\Mailables\Content;      // Defines the view / markdown template
use Illuminate\Mail\Mailables\Envelope;     // Defines subject, from, reply-to, etc.
use Illuminate\Queue\SerializesModels;      // Safe model serialisation for queued jobs

class RegistrationConfirmationMail extends Mailable
{
    use Queueable;         // Provides onQueue(), delay(), etc. for async dispatch
    use SerializesModels;  // Re-hydrates Eloquent models from DB when dequeued

    /**
     * Create a new registration confirmation email.
     *
     * All constructor parameters become public properties and are automatically
     * injected into the Blade view without needing to pass them in content().
     *
     * @param  Event       $event               The event the attendee registered for
     * @param  string      $attendeeName        Used in the greeting line of the email
     * @param  string      $registrationStatus  'confirmed' or 'waitlisted'
     * @param  int|null    $waitlistPosition    1-based queue number; null when confirmed
     */
    public function __construct(
        public Event $event,                  // Serialised by SerializesModels trait
        public string $attendeeName,          // e.g. 'Jane Doe'
        public string $registrationStatus,    // 'confirmed' | 'waitlisted'
        public ?int $waitlistPosition = null, // Only set when $registrationStatus === 'waitlisted'
    ) {
    }

    /**
     * Define the email envelope (headers visible to the recipient's mail client).
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            // Dynamic subject includes the event title so recipients can identify the email
            // at a glance in their inbox without opening it.
            subject: 'Registration Confirmation - '.$this->event->title,
        );
    }

    /**
     * Define the email body template.
     *
     * The view at resources/views/emails/registration-confirmation.blade.php
     * has access to all public properties: $event, $attendeeName,
     * $registrationStatus, $waitlistPosition.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-confirmation', // Blade view path (no .blade.php suffix)
        );
    }
}
