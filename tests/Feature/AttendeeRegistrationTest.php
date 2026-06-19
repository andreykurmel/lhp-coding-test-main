<?php

namespace Tests\Feature;

use App\Mail\EventRegisteredConfirmation;
use App\Mail\EventReminder24Hours;
use App\Mail\EventReminder3Days;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AttendeeRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_includes_attendee_data_without_email_addresses(): void
    {
        $event = Event::factory()->for(User::factory())->create([
            'payload' => ['name' => 'Global Tech Summit'],
        ]);

        Attendee::factory()->for($event)->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'status' => 'attending',
        ]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Events/Show')
                ->where('event.id', $event->id)
                ->where('event.attendees.0.name', 'Ada Lovelace')
                ->where('event.attendees.0.status', 'attending')
                ->missing('event.attendees.0.email')
            );
    }

    public function test_attendee_can_register_and_receives_confirmation_email(): void
    {
        Mail::fake();

        $event = Event::factory()->for(User::factory())->create([
            'payload' => ['name' => 'Global Tech Summit'],
        ]);

        $this->post(route('events.register', $event), [
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'status' => 'attending',
        ])
            ->assertRedirect()
            ->assertSessionHas('toast.message', 'Registration saved. Confirmation email sent.');

        $this->assertDatabaseHas('attendees', [
            'event_id' => $event->id,
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'status' => 'attending',
        ]);

        Mail::assertSent(EventRegisteredConfirmation::class, function (EventRegisteredConfirmation $mail) use ($event): bool {
            return $mail->event->is($event)
                && $mail->attendee->email === 'grace@example.com';
        });
    }

    public function test_registration_updates_existing_attendee_for_event_and_email(): void
    {
        Mail::fake();

        $event = Event::factory()->for(User::factory())->create();
        Attendee::factory()->for($event)->create([
            'name' => 'Old Name',
            'email' => 'person@example.com',
            'status' => 'interested',
        ]);

        $this->post(route('events.register', $event), [
            'name' => 'New Name',
            'email' => 'person@example.com',
            'status' => 'attending',
        ])->assertRedirect();

        $this->assertSame(1, Attendee::where('event_id', $event->id)->where('email', 'person@example.com')->count());
        $this->assertDatabaseHas('attendees', [
            'event_id' => $event->id,
            'name' => 'New Name',
            'email' => 'person@example.com',
            'status' => 'attending',
        ]);
        Mail::assertSent(EventRegisteredConfirmation::class, 1);
    }

    public function test_registration_validates_input(): void
    {
        Mail::fake();

        $event = Event::factory()->for(User::factory())->create();

        $this->post(route('events.register', $event), [
            'name' => '',
            'email' => 'not-an-email',
            'status' => 'maybe',
        ])
            ->assertSessionHasErrors(['name', 'email', 'status']);

        $this->assertDatabaseCount('attendees', 0);
        Mail::assertNothingSent();
    }

    public function test_send_event_reminders_sends_three_day_and_twenty_four_hour_reminders_once(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::create(2026, 6, 19, 10));

        $threeDayEvent = Event::factory()->for(User::factory())->create([
            'starts_at' => Carbon::now()->addDays(2)->addHour()->timestamp,
            'payload' => ['name' => 'Three Day Event'],
        ]);
        $twentyFourHourEvent = Event::factory()->for(User::factory())->create([
            'starts_at' => Carbon::now()->addHours(6)->timestamp,
            'payload' => ['name' => 'Tomorrow Event'],
        ]);
        $outsideWindowEvent = Event::factory()->for(User::factory())->create([
            'starts_at' => Carbon::now()->addDays(5)->timestamp,
        ]);

        $threeDayAttendee = Attendee::factory()->for($threeDayEvent)->create(['email' => 'three@example.com']);
        $twentyFourHourAttendee = Attendee::factory()->for($twentyFourHourEvent)->create(['email' => 'day@example.com']);
        $outsideWindowAttendee = Attendee::factory()->for($outsideWindowEvent)->create(['email' => 'outside@example.com']);
        $alreadyRemindedAttendee = Attendee::factory()->for($threeDayEvent)->create([
            'email' => 'reminded@example.com',
            'reminded_3_days_at' => Carbon::now()->subHour(),
        ]);

        $this->artisan('app:send-event-reminders')
            ->assertSuccessful();

        Mail::assertSent(EventReminder3Days::class, 1);
        Mail::assertSent(EventReminder3Days::class, function (EventReminder3Days $mail): bool {
            return $mail->attendee->email === 'three@example.com';
        });
        Mail::assertSent(EventReminder24Hours::class, 1);
        Mail::assertSent(EventReminder24Hours::class, function (EventReminder24Hours $mail): bool {
            return $mail->attendee->email === 'day@example.com';
        });

        $this->assertNotNull($threeDayAttendee->refresh()->reminded_3_days_at);
        $this->assertNotNull($twentyFourHourAttendee->refresh()->reminded_24_hours_at);
        $this->assertNull($outsideWindowAttendee->refresh()->reminded_3_days_at);
        $this->assertNull($outsideWindowAttendee->refresh()->reminded_24_hours_at);
        $this->assertNotNull($alreadyRemindedAttendee->refresh()->reminded_3_days_at);

        Mail::fake();

        $this->artisan('app:send-event-reminders')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
