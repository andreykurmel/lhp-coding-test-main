# Event Attendees & Reminder Emails

Add support for attendees to register interest/attendance for events, receive confirmation emails immediately, and get automated reminder emails 3 days and 24 hours before the event starts.

## User Review Required

> [!NOTE]
> - **Mail Driver**: The application currently uses `MAIL_MAILER=log` in the local environment, meaning sent emails will be output to `storage/logs/laravel.log`.
> - **Queue / Scheduling**: We will schedule the reminders in `routes/console.php` using the Laravel Scheduler and implement a Console Command `app:send-event-reminders`.

## Open Questions

None at the moment.

## Proposed Changes

### Database

#### [NEW] [Migration file](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/database/migrations/2026_06_19_000001_create_attendees_table.php)
Create the `attendees` table with the following structure:
- `id` (bigIncrements)
- `event_id` (foreignUuid to `events.id`, cascadeOnDelete)
- `name` (string)
- `email` (string)
- `status` (string, either `'interested'` or `'attending'`)
- `reminded_3_days_at` (timestamp, nullable)
- `reminded_24_hours_at` (timestamp, nullable)
- `timestamps`
- Unique constraint on `['event_id', 'email']`

---

### Backend Models & Actions

#### [NEW] [Attendee.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/app/Models/Attendee.php)
Eloquent model representing an event attendee.
- Belongs to `Event`
- Casts `reminded_3_days_at` and `reminded_24_hours_at` to datetimes

#### [NEW] [RegisterAttendeeAction.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/app/Actions/RegisterAttendeeAction.php)
Action to register/update attendance:
- Use `updateOrCreate` on the unique `event_id` and `email` fields.
- Trigger/send the confirmation email to the attendee.

#### [NEW] [EventRegisteredConfirmation.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/app/Mail/EventRegisteredConfirmation.php)
Mailable for the registration confirmation email.

#### [NEW] [EventReminder3Days.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/app/Mail/EventReminder3Days.php)
Mailable for the 3-day reminder email.

#### [NEW] [EventReminder24Hours.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/app/Mail/EventReminder24Hours.php)
Mailable for the 24-hour reminder email.

#### [NEW] [SendEventReminders.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/app/Console/Commands/SendEventReminders.php)
Console command to send reminders:
- Look up attendees whose events start in `[now + 2 days, now + 3 days]` and have `reminded_3_days_at IS NULL`, then send 3-day reminders and update `reminded_3_days_at`.
- Look up attendees whose events start in `[now, now + 24 hours]` and have `reminded_24_hours_at IS NULL`, then send 24-hour reminders and update `reminded_24_hours_at`.

#### [MODIFY] [console.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/routes/console.php)
Schedule `app:send-event-reminders` to run hourly.

---

### Controllers & Routing

#### [NEW] [RegisterAttendeeRequest.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/app/Http/Requests/RegisterAttendeeRequest.php)
Validation rules for name, email, and status.

#### [MODIFY] [web.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/routes/web.php)
Add `POST /events/{event}/register` route mapping to a new controller action.

#### [MODIFY] [EventController.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/app/Http/Controllers/EventController.php)
- Update `show` method to load and pass the attendees list (`name`, `status`) to the Inertia page.
- Add `register` method to validate inputs using `RegisterAttendeeRequest`, execute `RegisterAttendeeAction`, flash success message, and redirect back.

---

### Frontend

#### [MODIFY] [Show.vue](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/resources/js/pages/Events/Show.vue)
Enhance the event show page:
- Build a premium event details view displaying images, title, description, date, address, and metadata (replacing the raw JSON dump).
- Include an Attendee List displaying all registered attendees in a beautiful layout using avatars.
- Include a Registration Card with a form (Name, Email, Status: Interested or Attending). If the user is logged in, pre-fill Name and Email automatically.
- Display validation errors and toast success notifications.

---

### Verification Plan

### Automated Tests
#### [NEW] [AttendeeRegistrationTest.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/tests/Feature/AttendeeRegistrationTest.php)
- Test attendee registration route.
- Test validation and registration updates.
- Test registration triggers confirmation email.
- Test `app:send-event-reminders` console command correctly identifies and emails attendees at the 3-day and 24-hour marks.

### Manual Verification
- Access `/events/{id}` page, fill out form, and verify registration is successful.
- Check database content in the `attendees` table.
- Verify email content outputted in `storage/logs/laravel.log`.
