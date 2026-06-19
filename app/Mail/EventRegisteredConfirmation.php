<?php

namespace App\Mail;

use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventRegisteredConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Event $event,
        public readonly Attendee $attendee,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You are registered for '.$this->eventName(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.event-registered-confirmation',
        );
    }

    public function eventName(): string
    {
        return $this->event->payload['name'] ?? 'an event';
    }
}
