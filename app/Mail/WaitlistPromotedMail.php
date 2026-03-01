<?php
// ============================================================
// Mailable: WaitlistPromotedMail
// ============================================================
// Sent to a previously waitlisted attendee when their spot is
// confirmed as a result of another registrant cancelling.
// Triggered inside the cancelAndPromote() helper.
//
// Data available in the Blade template via public properties:
//   $event        — the event the attendee is now confirmed for
//   $attendeeName — personalised salutation
//
// Best practices applied:
//  ✔ SerializesModels re-fetches Event by PK if the job is queued
//  ✔ Queueable trait makes async dispatch a one-liner (ShouldQueue)
//  ✔ Positive/celebratory subject line ('Great news!') follows
//    transactional email UX best practice for good open rates
// ============================================================

namespace App\Mail;

use App\Models\Event;              // The event the attendee has just been confirmed for
use Illuminate\Bus\Queueable;     // Enables queue dispatch support
use Illuminate\Mail\Mailable;     // Base Mailable class
use Illuminate\Mail\Mailables\Content;  // View binding for the email body
use Illuminate\Mail\Mailables\Envelope; // Envelope: subject, from, etc.
use Illuminate\Queue\SerializesModels; // Safely stores Eloquent models in the queue payload

class WaitlistPromotedMail extends Mailable
{
    use Queueable;        // Opt-in async: implement ShouldQueue to queue this email
    use SerializesModels; // Ensures $event is re-fetched from DB when the job is processed

    /**
     * Build the waitlist-promotion notification email.
     *
     * @param  Event   $event         The event the attendee is now confirmed for
     * @param  string  $attendeeName  Name used in the email greeting
     */
    public function __construct(
        public Event $event,        // Event model — title/date shown in email body
        public string $attendeeName, // Recipient's name for personalisation
    ) {
    }

    /**
     * Email envelope — sets the subject line.
     * The exclamatory tone is intentional: this is a positive notification.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            // Apostrophe must be escaped because PHP double-quoted strings would
            // interpret \' as a literal backslash+apostrophe. Using single-quoted
            // heredoc or concatenation is an alternative.
            subject: 'Great news! You\'ve been confirmed for ' . $this->event->title,
        );
    }

    /**
     * Email body — points to the Blade view template.
     * The template receives $event and $attendeeName as template variables.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.waitlist-promoted', // resources/views/emails/waitlist-promoted.blade.php
        );
    }
}
