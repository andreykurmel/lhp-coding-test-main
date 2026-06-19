<?php

namespace App\Mail;

use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminder24Hours extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Event $event,
        public readonly Attendee $attendee,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: Your event starts in 24 hours - '.$this->eventName(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.event-reminder-24-hours',
        );
    }

    public function eventName(): string
    {
        return $this->event->payload['name'] ?? 'your event';
    }
}
