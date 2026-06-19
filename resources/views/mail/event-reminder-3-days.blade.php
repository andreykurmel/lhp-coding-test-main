<p>Hello {{ $attendee->name }},</p>

<p>This is a reminder that {{ $event->payload['name'] ?? 'your event' }} starts in about 3 days.</p>

@if ($event->starts_at)
    <p>Start time: {{ \Illuminate\Support\Carbon::createFromTimestamp($event->starts_at)->toDayDateTimeString() }}</p>
@endif

@if ($event->address)
    <p>Location: {{ $event->address }}</p>
@endif
