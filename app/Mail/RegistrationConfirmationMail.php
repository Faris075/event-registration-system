<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public string $attendeeName,
        public string $registrationStatus,
        public ?int $waitlistPosition = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Registration Confirmation - '.$this->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-confirmation',
        );
    }
}
