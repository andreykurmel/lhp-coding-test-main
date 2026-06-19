# Implementation Plan - Event Visuals 1 Page

Update the "Event Visuals 1" page to display events as a modern, premium card grid layout. It will include event images, names, locations, and starting times, styled with Tailwind CSS, utilizing lazy loading, and matching the filter layout of the events list.

## User Review Required

> [!NOTE]
> We will modify the route `/events-visual-1` to go through `EventController` so we can pass the exact same status and date filters as the standard events list.

## Proposed Changes

---

### Backend Components

#### [MODIFY] [EventController.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/app/Http/Controllers/EventController.php)
- Remove the debug `dd()` call in `show()` that is currently causing tests to fail.
- Add `visualOne(Request $request)` method that renders the `Events/VisualOne` Inertia page with standard filters and statuses props.

#### [MODIFY] [web.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/routes/web.php)
- Change route `events-visual-1` from static `Route::inertia()` to call `EventController@visualOne`.

---

### Frontend Components

#### [MODIFY] [VisualOne.vue](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/resources/js/pages/Events/VisualOne.vue)
- Add props definitions for `filters` and `statuses`.
- Implement lazy loading via `/events/data` fetch calls (reusing the logic from `Index.vue`).
- Create a beautiful responsive card grid using Tailwind CSS:
  - 1 column on mobile, 2 on tablet, 3 on desktop, 4 on large screens.
  - Cards should have a hover zoom and lifting shadow transition.
  - Top of each card: Event image using the first image from `payload.images` (with a high-quality fallback gradient placeholder).
  - Overlay badges: Status (`published`, `cancelled`, `sold_out`, `draft`) and event type/category.
  - Card body:
    - Event name (`payload.name`).
    - Date & time formatting: convert `payload.schedule.starts_at` (or `starts_at` timestamp) to a user-friendly string (e.g. "Fri, Jun 19, 2026, 12:30 PM").
    - Location: display a map pin icon with city & country (`city, country`), falling back to address or coordinates.
  - Link entire card to `/events/${event.id}` using the Inertia `<Link>` component.

---

### Verification Plan

#### Automated Tests
- Run PHPUnit tests using Sail:
  ```bash
  ./vendor/bin/sail exec laravel.test php artisan test --compact
  ```

#### Manual Verification
- View the "Events Visual 1" page in the browser.
- Verify that events load in a card grid.
- Test filtering by status and date.
- Test scroll down and confirm lazy loading triggers and appends more cards.
- Verify card layouts: correct image display, clean name, location, and date formatting.
