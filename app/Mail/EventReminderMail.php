<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminderMail extends Mailable
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
            subject: 'Reminder: ' . $this->event->title . ' is tomorrow!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.event-reminder',
        );
    }
}
