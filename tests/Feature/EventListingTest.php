<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->get(route('events.visual2'))->assertOk();
        $this->get(route('dashboard'))->assertOk();
    }
}
