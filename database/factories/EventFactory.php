<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition']);
        $lat = fake()->latitude();
        $lng = fake()->longitude();
        $startsAt = fake()->numberBetween(strtotime('-1 year'), strtotime('+1 year'));
        $endsAt = $startsAt + 7200;
        $country = fake()->country();
        $city = fake()->city();
        $address = fake()->streetAddress().', '.$city.', '.$country;

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'status' => fake()->randomElement(['draft', 'published', 'cancelled', 'sold_out']),
            'created_time' => $startsAt,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'latitude' => $lat,
            'longitude' => $lng,
            'country' => $country,
            'city' => $city,
            'address' => $address,
            'payload' => [
                'name' => ucwords(fake()->words(3, true)),
                'category' => $type,
                'images' => [
                    fake()->randomElement(['/storage/images/event1.jpeg', '/storage/images/event2.jpg', '/storage/images/event3.jpg']),
                ],
                'venue' => ['name' => fake()->company(), 'capacity' => fake()->numberBetween(20, 50000)],
                'location' => ['lat' => $lat, 'lng' => $lng],
                'schedule' => ['starts_at' => $startsAt, 'ends_at' => $endsAt],
                'pricing' => ['currency' => 'USD', 'min_price' => fake()->randomFloat(2, 0, 250)],
            ],
        ];
    }
}
