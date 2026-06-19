# Implementation Plan - Event Visuals 2 (Calendar) & Enhanced Filtering

Implement a premium calendar layout for **Event Visuals 2** (`VisualTwo.vue`) and add robust date/location filtering capabilities to the backend and both visual views.

## User Review Required

> [!NOTE]
> - We will modify `EventController@loadListing` query logic to support date-range filtering (via `from` and `to` request inputs) and location filtering (via matching `city`, `country`, or `address` columns).
> - We will modify `events-visual-2` route to go through `EventController@visualTwo` to default its date range filters to the current month.

## Proposed Changes

---

### Backend Components

#### [MODIFY] [EventController.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/app/Http/Controllers/EventController.php)
- Update `loadListing` to handle:
  - `from` date: Filter `starts_at` >= parsed `from` timestamp.
  - `to` date: Filter `starts_at` <= parsed `to` timestamp.
  - `location` text search: Filter by `city`, `country`, or `address` columns matching the keyword.
- Update `visualOne()` to include `to` and `location` in the returned `filters` array.
- Add `visualTwo()` that defaults the `from` and `to` filters to the current month's start and end dates respectively.

#### [MODIFY] [web.php](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/routes/web.php)
- Update route `events-visual-2` to map to `EventController@visualTwo`.

---

### Frontend Components

#### [MODIFY] [Index.vue](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/resources/js/pages/Events/Index.vue)
- Update `filters` prop to include `to` and `location`.
- Add `location` search field to the filtering form.
- Include `to` and `location` search parameters in the `loadMore()` AJAX requests.

#### [MODIFY] [VisualOne.vue](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/resources/js/pages/Events/VisualOne.vue)
- Update `filters` prop to include `to` and `location`.
- Add `location` search field and `to` (end date) filter to the filtering form.
- Update `loadMore()` to pass `to` and `location` parameters.

#### [MODIFY] [VisualTwo.vue](file:///home/andrey/Documents/Workspace/lhp-coding-test-main/resources/js/pages/Events/VisualTwo.vue)
- Implement interactive Calendar + Agenda split layout:
  - **Left panel (desktop) / top (mobile)**: A beautiful monthly calendar card.
    - Prev/Next month buttons to automatically slide the viewed month, updating date filters on-the-fly and loading relevant events.
    - Calendar grid displaying days. Cells with events contain a status indicator dot.
    - Clicking a day highlights it and filters the agenda list to events scheduled on that day.
  - **Right panel (desktop) / bottom (mobile)**: A scrollable, lazy-loaded list of events for the selected month/day.
    - Cards show first image, name, status, type, location, and starts_at time.
    - Clicking a card navigates to `/events/${event.id}`.
- Include filters form (Status, Date Range, Location) for targeted searching.

---

### Verification Plan

#### Automated Tests
- Run PHPUnit tests using Sail:
  ```bash
  ./vendor/bin/sail exec laravel.test php artisan test --compact
  ```
- Add unit/feature tests in `EventListingTest.php` verifying:
  - Date filtering works correctly on backend.
  - Location filtering works correctly on backend.
  - `visualTwo` route returns correct Inertia component and default month range props.

#### Manual Verification
- View the "Events Visual 2" page.
- Verify the monthly calendar layout correctly displays cells for the current month.
- Verify navigating months updates the calendar days and correctly queries events for that month.
- Verify selecting a day filters the agenda list to show events on that day.
- Verify location search and date range filters work seamlessly.
