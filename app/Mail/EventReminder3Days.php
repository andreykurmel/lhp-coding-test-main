<?php

namespace App\Mail;

use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminder3Days extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Event $event,
        public readonly Attendee $attendee,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: 3 days until '.$this->eventName(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.event-reminder-3-days',
        );
    }

    public function eventName(): string
    {
        return $this->event->payload['name'] ?? 'your event';
    }
}
