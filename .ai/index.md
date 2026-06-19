## General
It is an event management system.


## Architecture
Tech Stack:
- Database: PostgreSQL
- Backend: PHP 8.5, Laravel 13, Inertia 3
- Frontend: Vue 3, Tailwindcss


## Backend Rules
General:
- Use dependency injection
- No business logic in controllers
- Repositories only access database
- Actions contain business logic
- Add tests for new logic

Controllers:
- Thin
- Validation only
- Call Actions
- Use Form Requests
- Response as JSON (JsonResource)

Actions:
- Business logic
- Do one business operation
- Be transaction-safe
- Placed in the folder: /app/Actions

Repositories:
- Database access

Jobs:
- Async operations

Events:
- Cross-module communication

Example of a code flow:
Controller -> Action -> Repository -> Model.


## Frontend Rules
Components:
- Keep components modular and limited to a single responsibility.
- Use Pinia for global state management.
- Emit events using defineEmits for child-to-parent communication.


## Testing
Try to find out and add tests of possible edge cases.
Add integration tests to check all actions in an endpoint.


## Agent Workflow
You are a senior full-stack software engineer.
Write minimal clean code.

Never:
- Change migrations without a request
- Break public API contracts
- Introduce new packages without approval

This project works inside a laravel sail container, so use "./vendor/bin/sail exec laravel.test" prefix for your commands.
Examples:
- instead of "vendor/bin/pint --format agent" run: "./vendor/bin/sail exec laravel.test vendor/bin/pint --format agent"
- instead of "vendor/bin/phpunit" run: "./vendor/bin/sail exec laravel.test vendor/bin/phpunit"

Also use the laravel boost guidelines: ./laravel-boost.md
Cache these guidelines, ./laravel-boost.md and `laravel-best-practices` skill (if used) when it is possible to reduce tokens consumption.
