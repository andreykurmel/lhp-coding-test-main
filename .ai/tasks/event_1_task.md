# Task List - Event Visuals 1 Page

- [x] Add `visualOne` method in `EventController` to pass filters and statuses props
- [x] Update web routes in `routes/web.php` to point `events-visual-1` to `EventController@visualOne`
- [x] Implement Event Visuals 1 card grid UI in `VisualOne.vue`
  - [x] Add props and reactive data/filters
  - [x] Implement fetch data logic and infinite scrolling
  - [x] Format location (`city, country`) and start time (`starts_at` timestamp)
  - [x] Style premium card grid layout with images, badges, and hover effects
- [x] Verify functionality
  - [x] Run PHPUnit tests
  - [x] Verify image fallback and layout rendering
