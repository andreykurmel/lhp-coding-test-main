<p>Hello {{ $attendee->name }},</p>

<p>Your {{ $attendee->status }} registration for {{ $event->payload['name'] ?? 'this event' }} has been saved.</p>

@if ($event->starts_at)
    <p>Start time: {{ \Illuminate\Support\Carbon::createFromTimestamp($event->starts_at)->toDayDateTimeString() }}</p>
@endif

@if ($event->address)
    <p>Location: {{ $event->address }}</p>
@endif

<p>Thank you for registering.</p>
