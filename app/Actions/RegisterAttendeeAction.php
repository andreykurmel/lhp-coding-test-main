<?php

namespace App\Actions;

use App\Mail\EventRegisteredConfirmation;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RegisterAttendeeAction
{
    /**
     * @param  array{name: string, email: string, status: string}  $data
     */
    public function execute(Event $event, array $data): Attendee
    {
        $attendee = DB::transaction(function () use ($event, $data): Attendee {
            return Attendee::query()->updateOrCreate(
                [
                    'event_id' => $event->id,
                    'email' => $data['email'],
                ],
                [
                    'name' => $data['name'],
                    'status' => $data['status'],
                ],
            );
        });

        Mail::to($attendee->email)->send(new EventRegisteredConfirmation($event, $attendee));

        return $attendee;
    }
}
