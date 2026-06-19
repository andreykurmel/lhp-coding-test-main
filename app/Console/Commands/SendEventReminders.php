<?php

namespace App\Console\Commands;

use App\Mail\EventReminder24Hours;
use App\Mail\EventReminder3Days;
use App\Models\Attendee;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

#[Signature('app:send-event-reminders')]
#[Description('Send reminder emails to registered event attendees.')]
class SendEventReminders extends Command
{
    public function handle(): int
    {
        $now = Carbon::now();

        $threeDayCount = $this->sendReminders(
            'reminded_3_days_at',
            $now->copy()->addDays(2)->timestamp,
            $now->copy()->addDays(3)->timestamp,
            EventReminder3Days::class,
            $now,
        );

        $twentyFourHourCount = $this->sendReminders(
            'reminded_24_hours_at',
            $now->timestamp,
            $now->copy()->addDay()->timestamp,
            EventReminder24Hours::class,
            $now,
        );

        $this->components->info("Sent {$threeDayCount} three-day reminders and {$twentyFourHourCount} 24-hour reminders.");

        return self::SUCCESS;
    }

    /**
     * @param  class-string<Mailable>  $mailable
     */
    private function sendReminders(
        string $remindedColumn,
        int $startsFrom,
        int $startsUntil,
        string $mailable,
        Carbon $sentAt,
    ): int {
        $sent = 0;

        Attendee::query()
            ->with('event')
            ->whereNull($remindedColumn)
            ->whereHas('event', function ($query) use ($startsFrom, $startsUntil): void {
                $query->whereBetween('starts_at', [$startsFrom, $startsUntil]);
            })
            ->orderBy('id')
            ->chunkById(100, function ($attendees) use ($remindedColumn, $mailable, $sentAt, &$sent): void {
                foreach ($attendees as $attendee) {
                    Mail::to($attendee->email)->send(new $mailable($attendee->event, $attendee));

                    $attendee->forceFill([$remindedColumn => $sentAt])->save();
                    $sent++;
                }
            });

        return $sent;
    }
}
