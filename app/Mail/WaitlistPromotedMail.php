<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WaitlistPromotedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public string $attendeeName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Great news! You\'ve been confirmed for ' . $this->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.waitlist-promoted',
        );
    }
}
