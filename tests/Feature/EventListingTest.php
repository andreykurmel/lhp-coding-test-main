<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EventListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_the_events_listing_shell_without_authentication(): void
    {
        $this->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Events/Index')
                ->has('statuses', 4)
                ->where('filters.from', '2023-01-01')
            );
    }

    public function test_returns_a_json_page_of_events_with_load_stats_for_lazy_loading(): void
    {
        $user = User::factory()->create(['name' => 'Ada Lovelace']);
        Event::factory()->for($user)->create([
            'type' => 'concert',
            'status' => 'published',
            'created_time' => 1_700_000_000,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $this->getJson(route('events.data'))
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
                'total',
                'stats' => ['ms', 'bytes'],
            ])
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.type', 'concert')
            ->assertJsonPath('data.0.created_time', 1_700_000_000)
            ->assertJsonPath('data.0.latitude', 40.7128)
            ->assertJsonPath('data.0.user.name', 'Ada Lovelace');
    }

    public function test_filters_the_data_endpoint_by_status(): void
    {
        $user = User::factory()->create();
        Event::factory()->for($user)->create(['status' => 'published']);
        Event::factory()->for($user)->create(['status' => 'cancelled']);

        $this->getJson(route('events.data', ['status' => 'cancelled']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.status', 'cancelled');
    }

    public function test_filters_the_data_endpoint_by_date_range(): void
    {
        $user = User::factory()->create();

        // October 15, 2026 timestamp = 1792022400
        $october15 = Carbon::create(2026, 10, 15, 12, 0, 0);
        Event::factory()->for($user)->create([
            'starts_at' => $october15->timestamp,
            'created_time' => $october15->timestamp,
        ]);

        // December 15, 2026 timestamp = 1797292800
        $december15 = Carbon::create(2026, 12, 15, 12, 0, 0);
        Event::factory()->for($user)->create([
            'starts_at' => $december15->timestamp,
            'created_time' => $december15->timestamp,
        ]);

        // Filter for October to November 2026
        $this->getJson(route('events.data', [
            'from' => '2026-10-01',
            'to' => '2026-11-30',
        ]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.starts_at', $october15->timestamp);

        // Filter for December 2026 to January 2027
        $this->getJson(route('events.data', [
            'from' => '2026-12-01',
            'to' => '2027-01-31',
        ]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.starts_at', $december15->timestamp);
    }

    public function test_filters_the_data_endpoint_by_location(): void
    {
        $user = User::factory()->create();

        Event::factory()->for($user)->create([
            'city' => 'Paris',
            'country' => 'France',
        ]);

        Event::factory()->for($user)->create([
            'city' => 'Chicago',
            'country' => 'United States',
        ]);

        // Search for "Paris"
        $this->getJson(route('events.data', ['location' => 'Paris']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.city', 'Paris');

        // Search for "United States"
        $this->getJson(route('events.data', ['location' => 'United States']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.city', 'Chicago');
    }

    public function test_shows_an_event_detail_page_with_its_payload(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user)->create([
            'payload' => ['name' => 'Global Tech Summit', 'location' => ['lat' => 1.5, 'lng' => 2.5]],
        ]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Events/Show')
                ->where('event.id', $event->id)
                ->where('event.payload.name', 'Global Tech Summit')
            );
    }

    public function test_renders_the_two_visualization_pages_and_the_dashboard_without_authentication(): void
    {
        $this->get(route('events.visual1'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Events/VisualOne')
                ->has('statuses', 4)
                ->where('filters.from', '2023-01-01')
            );

        $now = Carbon::now();
        $expectedFrom = $now->startOfMonth()->toDateString();
        $expectedTo = $now->endOfMonth()->toDateString();

        $this->get(route('events.visual2'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Events/VisualTwo')
                ->has('statuses', 4)
                ->where('filters.from', $expectedFrom)
                ->where('filters.to', $expectedTo)
            );

        $this->get(route('dashboard'))->assertOk();
    }
}
