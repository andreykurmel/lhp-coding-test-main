<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EventSeeder extends Seeder
{
    /**
     * Approximate encoded size of a single payload, in bytes. Dial this to
     * change the on-disk footprint of the seeded dataset.
     */
    public const PAYLOAD_AVG_BYTES = 1500;

    public const NUM_USERS = 3000;

    private const CHUNK = 4000;

    /** Event categories (stored in the `type` column). */
    private const TYPES = ['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition'];

    private const STATUSES = ['draft', 'published', 'cancelled', 'sold_out'];

    private const LOCATIONS = [
        ['United States', 'New York'], ['United States', 'Los Angeles'], ['United States', 'Chicago'], ['United States', 'Houston'],
        ['Canada', 'Toronto'], ['Canada', 'Montreal'], ['Canada', 'Vancouver'], ['Mexico', 'Mexico City'],
        ['United Kingdom', 'London'], ['France', 'Paris'], ['Germany', 'Berlin'], ['Spain', 'Madrid'],
        ['Italy', 'Rome'], ['Netherlands', 'Amsterdam'], ['Japan', 'Tokyo'], ['Australia', 'Sydney'],
    ];

    private const STREET_NAMES = ['Main Street', 'Market Street', 'Broadway', 'King Street', 'Queen Street', 'High Street', 'Park Avenue', 'River Road'];

    private const NAME_ADJECTIVES = ['Annual', 'Global', 'Summer', 'Winter', 'Underground', 'Open', 'International', 'Live', 'Midnight', 'Sunset', 'Urban', 'Indie', 'Grand', 'Pop-up', 'Virtual'];

    private const NAME_THEMES = ['Synthwave', 'Founders', 'Jazz', 'Tech', 'Food & Wine', 'Yoga', 'Startup', 'Design', 'Climate', 'Gaming', 'Film', 'Book', 'Marathon', 'Comedy', 'Art'];

    private const NAME_FORMATS = ['Festival', 'Meetup', 'Conference', 'Summit', 'Workshop', 'Expo', 'Showcase', 'Gala', 'Jam', 'Retreat', 'Fair', 'Night', 'Tour', 'Symposium', 'Block Party'];

    private const IMAGES = ['/storage/images/event1.jpeg', '/storage/images/event2.jpg', '/storage/images/event3.jpg'];

    /**
     * Anchor coordinates [lat, lng] for major cities across the US, Canada,
     * Mexico and Europe, plus a few global hubs. Each row is jittered around
     * one of these anchors.
     */
    private const COMPLETE_CITY_ANCHORS = [
        // United States
        ['latitude' => 40.7128, 'longitude' => -74.0060, 'country' => 'United States', 'city' => 'New York', 'address' => 'New York City Hall, New York, NY 10007, United States'],
        ['latitude' => 34.0522, 'longitude' => -118.2437, 'country' => 'United States', 'city' => 'Los Angeles', 'address' => 'Los Angeles City Hall, Los Angeles, CA 90012, United States'],
        ['latitude' => 41.8781, 'longitude' => -87.6298, 'country' => 'United States', 'city' => 'Chicago', 'address' => 'Chicago City Hall, Chicago, IL 60602, United States'],
        ['latitude' => 29.7604, 'longitude' => -95.3698, 'country' => 'United States', 'city' => 'Houston', 'address' => 'Houston City Hall, Houston, TX 77002, United States'],
        ['latitude' => 33.4484, 'longitude' => -112.0740, 'country' => 'United States', 'city' => 'Phoenix', 'address' => 'Phoenix City Hall, Phoenix, AZ 85003, United States'],
        ['latitude' => 39.9526, 'longitude' => -75.1652, 'country' => 'United States', 'city' => 'Philadelphia', 'address' => 'Philadelphia City Hall, Philadelphia, PA 19107, United States'],
        ['latitude' => 29.4241, 'longitude' => -98.4936, 'country' => 'United States', 'city' => 'San Antonio', 'address' => 'San Antonio City Hall, San Antonio, TX 78205, United States'],
        ['latitude' => 32.7157, 'longitude' => -117.1611, 'country' => 'United States', 'city' => 'San Diego', 'address' => 'San Diego City Administration Building, San Diego, CA 92101, United States'],
        ['latitude' => 32.7767, 'longitude' => -96.7970, 'country' => 'United States', 'city' => 'Dallas', 'address' => 'Dallas City Hall, Dallas, TX 75201, United States'],
        ['latitude' => 37.3382, 'longitude' => -121.8863, 'country' => 'United States', 'city' => 'San Jose', 'address' => 'San Jose City Hall, San Jose, CA 95112, United States'],
        ['latitude' => 30.2672, 'longitude' => -97.7431, 'country' => 'United States', 'city' => 'Austin', 'address' => 'Austin City Hall, Austin, TX 78701, United States'],
        ['latitude' => 37.7749, 'longitude' => -122.4194, 'country' => 'United States', 'city' => 'San Francisco', 'address' => 'San Francisco City Hall, San Francisco, CA 94102, United States'],
        ['latitude' => 47.6062, 'longitude' => -122.3321, 'country' => 'United States', 'city' => 'Seattle', 'address' => 'Seattle City Hall, Seattle, WA 98104, United States'],
        ['latitude' => 39.7392, 'longitude' => -104.9903, 'country' => 'United States', 'city' => 'Denver', 'address' => 'Denver City and County Building, Denver, CO 80202, United States'],
        ['latitude' => 42.3601, 'longitude' => -71.0589, 'country' => 'United States', 'city' => 'Boston', 'address' => 'Boston City Hall, Boston, MA 02201, United States'],
        ['latitude' => 36.1699, 'longitude' => -115.1398, 'country' => 'United States', 'city' => 'Las Vegas', 'address' => 'Las Vegas City Hall, Las Vegas, NV 89101, United States'],
        ['latitude' => 25.7617, 'longitude' => -80.1918, 'country' => 'United States', 'city' => 'Miami', 'address' => 'Miami City Hall, Miami, FL 33133, United States'],
        ['latitude' => 33.7490, 'longitude' => -84.3880, 'country' => 'United States', 'city' => 'Atlanta', 'address' => 'Atlanta City Hall, Atlanta, GA 30303, United States'],
        ['latitude' => 38.9072, 'longitude' => -77.0369, 'country' => 'United States', 'city' => 'Washington', 'address' => 'James A. Garfield Monument, Washington, DC 20004, United States'],
        ['latitude' => 36.1627, 'longitude' => -86.7816, 'country' => 'United States', 'city' => 'Nashville', 'address' => 'Nashville City Hall, Nashville, TN 37201, United States'],
        ['latitude' => 45.5152, 'longitude' => -122.6784, 'country' => 'United States', 'city' => 'Portland', 'address' => 'Portland City Hall, Portland, OR 97204, United States'],
        ['latitude' => 29.9511, 'longitude' => -90.0715, 'country' => 'United States', 'city' => 'New Orleans', 'address' => 'New Orleans City Hall, New Orleans, LA 70112, United States'],

        // Canada
        ['latitude' => 43.6532, 'longitude' => -79.3832, 'country' => 'Canada', 'city' => 'Toronto', 'address' => 'Toronto City Hall, Toronto, ON M5H 2N2, Canada'],
        ['latitude' => 45.5019, 'longitude' => -73.5674, 'country' => 'Canada', 'city' => 'Montreal', 'address' => 'Montreal City Hall, Montreal, QC H2Y 1C6, Canada'],
        ['latitude' => 49.2827, 'longitude' => -123.1207, 'country' => 'Canada', 'city' => 'Vancouver', 'address' => 'Vancouver City Hall, Vancouver, BC V5Y 1V4, Canada'],
        ['latitude' => 51.0447, 'longitude' => -114.0719, 'country' => 'Canada', 'city' => 'Calgary', 'address' => 'Calgary City Hall, Calgary, AB T2G 2M2, Canada'],
        ['latitude' => 45.4215, 'longitude' => -75.6972, 'country' => 'Canada', 'city' => 'Ottawa', 'address' => 'Ottawa City Hall, Ottawa, ON K1P 1J1, Canada'],
        ['latitude' => 53.5461, 'longitude' => -113.4938, 'country' => 'Canada', 'city' => 'Edmonton', 'address' => 'Edmonton City Hall, Edmonton, AB T5J 2R7, Canada'],
        ['latitude' => 46.8139, 'longitude' => -71.2080, 'country' => 'Canada', 'city' => 'Quebec City', 'address' => 'Quebec City Hall, Quebec City, QC G1R 4S9, Canada'],
        ['latitude' => 49.8951, 'longitude' => -97.1384, 'country' => 'Canada', 'city' => 'Winnipeg', 'address' => 'Winnipeg City Hall, Winnipeg, MB R3B 1B9, Canada'],

        // Mexico
        ['latitude' => 19.4326, 'longitude' => -99.1332, 'country' => 'Mexico', 'city' => 'Mexico City', 'address' => 'Plaza de la Constitución, Centro Histórico, 06000 Ciudad de México, CDMX, Mexico'],
        ['latitude' => 20.6597, 'longitude' => -103.3496, 'country' => 'Mexico', 'city' => 'Guadalajara', 'address' => 'Palacio Municipal de Guadalajara, Centro, 44100 Guadalajara, Jal., Mexico'],
        ['latitude' => 25.6866, 'longitude' => -100.3161, 'country' => 'Mexico', 'city' => 'Monterrey', 'address' => 'Palacio Municipal de Monterrey, Centro, 64000 Monterrey, N.L., Mexico'],
        ['latitude' => 19.0414, 'longitude' => -98.2063, 'country' => 'Mexico', 'city' => 'Puebla', 'address' => 'Palacio Municipal de Puebla, Centro, 72000 Puebla, Pue., Mexico'],
        ['latitude' => 32.5149, 'longitude' => -117.0382, 'country' => 'Mexico', 'city' => 'Tijuana', 'address' => 'Palacio Municipal de Tijuana, Zona Urbana Rio Tijuana, 22010 Tijuana, B.C., Mexico'],
        ['latitude' => 21.1619, 'longitude' => -86.8515, 'country' => 'Mexico', 'city' => 'Cancún', 'address' => 'Palacio Municipal de Benito Juárez, Alfredo V. Bonfil, 77500 Cancún, Q.R., Mexico'],
        ['latitude' => 20.9674, 'longitude' => -89.5926, 'country' => 'Mexico', 'city' => 'Mérida', 'address' => 'Palacio Municipal de Mérida, Centro, 97000 Mérida, Yuc., Mexico'],

        // Europe
        ['latitude' => 51.5074, 'longitude' => -0.1278, 'country' => 'United Kingdom', 'city' => 'London', 'address' => 'London City Hall, London, SE1 2AA, United Kingdom'],
        ['latitude' => 48.8566, 'longitude' => 2.3522, 'country' => 'France', 'city' => 'Paris', 'address' => 'Hôtel de Ville, 75004 Paris, France'],
        ['latitude' => 52.5200, 'longitude' => 13.4050, 'country' => 'Germany', 'city' => 'Berlin', 'address' => 'Rotes Rathaus, 10178 Berlin, Germany'],
        ['latitude' => 40.4168, 'longitude' => -3.7038, 'country' => 'Spain', 'city' => 'Madrid', 'address' => 'Palacio de Cibeles, 28014 Madrid, Spain'],
        ['latitude' => 41.9028, 'longitude' => 12.4964, 'country' => 'Italy', 'city' => 'Rome', 'address' => 'Palazzo Senatorio, Piazza del Campidoglio, 00186 Roma RM, Italy'],
        ['latitude' => 52.3676, 'longitude' => 4.9041, 'country' => 'Netherlands', 'city' => 'Amsterdam', 'address' => 'Stopera, Amstel 1, 1011 PN Amsterdam, Netherlands'],
        ['latitude' => 41.3851, 'longitude' => 2.1734, 'country' => 'Spain', 'city' => 'Barcelona', 'address' => 'Ajuntament de Barcelona, Plaça de Sant Jaume, 08002 Barcelona, Spain'],
        ['latitude' => 48.1351, 'longitude' => 11.5820, 'country' => 'Germany', 'city' => 'Munich', 'address' => 'Neues Rathaus, Marienplatz 8, 80331 München, Germany'],
        ['latitude' => 45.4642, 'longitude' => 9.1900, 'country' => 'Italy', 'city' => 'Milan', 'address' => 'Palazzo Marino, Piazza della Scala 2, 20121 Milano MI, Italy'],
        ['latitude' => 48.2082, 'longitude' => 16.3738, 'country' => 'Austria', 'city' => 'Vienna', 'address' => 'Rathaus, Friedrich-Schmidt-Platz 1, 1010 Wien, Austria'],
        ['latitude' => 50.0755, 'longitude' => 14.4378, 'country' => 'Czechia', 'city' => 'Prague', 'address' => 'Staroměstské náměstí, 110 00 Praha 1, Czechia'],
        ['latitude' => 38.7223, 'longitude' => -9.1393, 'country' => 'Portugal', 'city' => 'Lisbon', 'address' => 'Paços do Concelho de Lisboa, Praça do Municipio, 1100-365 Lisboa, Portugal'],
        ['latitude' => 53.3498, 'longitude' => -6.2603, 'country' => 'Ireland', 'city' => 'Dublin', 'address' => 'Dublin City Hall, Dame St, Dublin 2, Ireland'],
        ['latitude' => 55.6761, 'longitude' => 12.5683, 'country' => 'Denmark', 'city' => 'Copenhagen', 'address' => 'Københavns Rådhus, Rådhuspladsen 1, 1550 København, Denmark'],
        ['latitude' => 59.3293, 'longitude' => 18.0686, 'country' => 'Sweden', 'city' => 'Stockholm', 'address' => 'Stockholms stadshus, Hantverkargatan 1, 111 52 Stockholm, Sweden'],
        ['latitude' => 59.9139, 'longitude' => 10.7522, 'country' => 'Norway', 'city' => 'Oslo', 'address' => 'Oslo rådhus, Rådhusplassen 1, 0037 Oslo, Norway'],
        ['latitude' => 60.1699, 'longitude' => 24.9384, 'country' => 'Finland', 'city' => 'Helsinki', 'address' => 'Helsingin kaupungintalo, Pohjoisesplanadi 11-13, 00170 Helsinki, Finland'],
        ['latitude' => 50.8503, 'longitude' => 4.3517, 'country' => 'Belgium', 'city' => 'Brussels', 'address' => 'Hôtel de Ville de Bruxelles, Grand Place, 1000 Bruxelles, Belgium'],
        ['latitude' => 47.3769, 'longitude' => 8.5417, 'country' => 'Switzerland', 'city' => 'Zurich', 'address' => 'Stadthaus Zürich, Stadthausquai 17, 8001 Zürich, Switzerland'],
        ['latitude' => 52.2297, 'longitude' => 21.0122, 'country' => 'Poland', 'city' => 'Warsaw', 'address' => 'Pałac Kultury i Nauki, plac Defilad 1, 00-901 Warszawa, Poland'],
        ['latitude' => 47.4979, 'longitude' => 19.0402, 'country' => 'Hungary', 'city' => 'Budapest', 'address' => 'Budapest Főpolgármesteri Hivatal, Városház u. 9-11, 1052 Budapest, Hungary'],
        ['latitude' => 37.9838, 'longitude' => 23.7275, 'country' => 'Greece', 'city' => 'Athens', 'address' => 'Athens City Hall, Athinas 63, Athina 105 52, Greece'],
        ['latitude' => 45.7640, 'longitude' => 4.8357, 'country' => 'France', 'city' => 'Lyon', 'address' => 'Hôtel de Ville de Lyon, Place de la Comédie, 69001 Lyon, France'],
        ['latitude' => 53.5511, 'longitude' => 9.9937, 'country' => 'Germany', 'city' => 'Hamburg', 'address' => 'Hamburger Rathaus, Rathausmarkt 1, 20095 Hamburg, Germany'],
        ['latitude' => 53.4808, 'longitude' => -2.2426, 'country' => 'United Kingdom', 'city' => 'Manchester', 'address' => 'Manchester City Hall, Albert Square, Manchester M60 2LA, United Kingdom'],
        ['latitude' => 55.9533, 'longitude' => -3.1883, 'country' => 'United Kingdom', 'city' => 'Edinburgh', 'address' => 'Edinburgh City Chambers, 249 High St, Edinburgh EH1 1YJ, United Kingdom'],
        ['latitude' => 50.1109, 'longitude' => 8.6821, 'country' => 'Germany', 'city' => 'Frankfurt', 'address' => 'Römer, Römerberg 27, 60311 Frankfurt am Main, Germany'],
        ['latitude' => 50.0647, 'longitude' => 19.9450, 'country' => 'Poland', 'city' => 'Krakow', 'address' => 'Sukiennice, Rynek Główny 1/3, 31-042 Kraków, Poland'],
        ['latitude' => 41.1579, 'longitude' => -8.6291, 'country' => 'Portugal', 'city' => 'Porto', 'address' => 'Câmara Municipal do Porto, Praça do General Humberto Delgado, 4000-407 Porto, Portugal'],
        ['latitude' => 40.8518, 'longitude' => 14.2681, 'country' => 'Italy', 'city' => 'Naples', 'address' => 'Palazzo San Giacomo, Piazza Municipio, 80133 Napoli NA, Italy'],
        // A few global hubs
        ['latitude' => 35.6762, 'longitude' => 139.6503, 'country' => 'Japan', 'city' => 'Tokyo', 'address' => 'Tokyo Metropolitan Government Building, 2 Chome-8-1 Nishishinjuku, Shinjuku City, Tokyo 163-8001, Japan'],
        ['latitude' => 37.5665, 'longitude' => 126.9780, 'country' => 'South Korea', 'city' => 'Seoul', 'address' => 'Seoul City Hall, 110 Sejong-daero, Jung-gu, Seoul, South Korea'],
        ['latitude' => 1.3521, 'longitude' => 103.8198, 'country' => 'Singapore', 'city' => 'Singapore', 'address' => 'Empress Place, Downtown Core, Singapore 179555'],
        ['latitude' => -33.8688, 'longitude' => 151.2093, 'country' => 'Australia', 'city' => 'Sydney', 'address' => 'Sydney City Hall, 483 George St, Sydney NSW 2000, Australia'],
        ['latitude' => -37.8136, 'longitude' => 144.9631, 'country' => 'Australia', 'city' => 'Melbourne', 'address' => 'Melbourne City Hall, 90-130 Swanston St, Melbourne VIC 3000, Australia'],
        ['latitude' => 25.2048, 'longitude' => 55.2708, 'country' => 'United Arab Emirates', 'city' => 'Dubai', 'address' => 'Burj Khalifa, 1 Sheikh Mohammed bin Rashid Blvd, Downtown Dubai, United Arab Emirates'],
        ['latitude' => -23.5505, 'longitude' => -46.6333, 'country' => 'Brazil', 'city' => 'São Paulo', 'address' => 'Praça da Sé, Sé, São Paulo - SP, 01001-000, Brazil'],
        ['latitude' => -34.6037, 'longitude' => -58.3816, 'country' => 'Argentina', 'city' => 'Buenos Aires', 'address' => 'Obelisco, Av. 9 de Julio, C1043 Buenos Aires, Argentina'],
    ];

    public function run(): void
    {
        $rows = (int) (env('SEED_ROWS', 1_250_000));

        $this->command?->info("Seeding {$rows} events...");

        $start = microtime(true);

        $this->withSeedingPragmas(function () use ($rows) {
            $this->ensureUsers();
            $this->insertEvents($rows);
        });

        $elapsed = round(microtime(true) - $start, 1);
        $rate = $elapsed > 0 ? round($rows / $elapsed) : $rows;
        $this->command?->info("Done. {$rows} events in {$elapsed}s ({$rate} rows/s).");
    }

    /**
     * Bulk-insert $count event rows using cheap, template-driven payloads.
     * Reused by the perf tests to top up the dataset to a target size.
     */
    public function insertEvents(int $count): void
    {
        $this->ensureUsers();

        DB::connection()->disableQueryLog();

        $template = $this->payloadTemplate();
        $now = date('Y-m-d H:i:s');
        $userMax = self::NUM_USERS;

        $year = 365 * 24 * 60 * 60;
        $now_ts = time();
        // Event start times span roughly one year in the past to one year out.
        $startTime = $now_ts - $year;
        $endTime = $now_ts + $year;

        $typeWeights = $this->cumulativeWeights([20, 14, 22, 12, 12, 8, 8, 4]);
        $statusWeights = $this->cumulativeWeights([12, 70, 8, 10]);
        $anchorCount = count(self::COMPLETE_CITY_ANCHORS);

        $remaining = $count;
        $done = 0;

        while ($remaining > 0) {
            $batchSize = min(self::CHUNK, $remaining);
            $batch = [];

            for ($i = 0; $i < $batchSize; $i++) {
                $type = self::TYPES[$this->pick($typeWeights)];
                $status = self::STATUSES[$this->pick($statusWeights)];
                $startsAt = mt_rand($startTime, $endTime);
                $endsAt = $startsAt + mt_rand(3600, 3 * 24 * 3600);

                $anchor = self::COMPLETE_CITY_ANCHORS[mt_rand(0, $anchorCount - 1)];
                $latitude = $anchor['latitude'];
                $longitude = $anchor['longitude'];

                $name = self::NAME_ADJECTIVES[array_rand(self::NAME_ADJECTIVES)]
                    .' '.self::NAME_THEMES[array_rand(self::NAME_THEMES)]
                    .' '.self::NAME_FORMATS[array_rand(self::NAME_FORMATS)];

                $payload = strtr($template, [
                    '{{NAME}}' => $this->escape($name),
                    '{{CATEGORY}}' => $type,
                    '{{ORGANIZER}}' => 'Organizer '.mt_rand(1, 9999),
                    '{{VENUE}}' => $this->escape($this->venueName()),
                    '{{LAT}}' => (string) $latitude,
                    '{{LNG}}' => (string) $longitude,
                    '{{STARTS}}' => (string) $startsAt,
                    '{{ENDS}}' => (string) $endsAt,
                    '{{CAPACITY}}' => (string) mt_rand(20, 50000),
                    '{{PRICE}}' => (string) (mt_rand(0, 25000) / 100),
                    '"{{IMAGES}}"' => $this->randomImagesJson(),
                ]);

                $batch[] = array_merge([
                    'id' => $this->uuidv4(),
                    'user_id' => mt_rand(1, $userMax),
                    'type' => $type,
                    'status' => $status,
                    'created_time' => $startsAt,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'payload' => $payload,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $anchor);
            }

            DB::transaction(function () use ($batch) {
                DB::table('events')->insert($batch);
            });

            $done += $batchSize;
            $remaining -= $batchSize;

            if ($done % (self::CHUNK * 25) === 0 || $remaining === 0) {
                $this->command?->getOutput()?->writeln("  inserted {$done}/{$count}");
            }
        }
    }

    private function ensureUsers(): void
    {
        $existing = DB::table('users')->count();
        if ($existing >= self::NUM_USERS) {
            return;
        }

        $password = Hash::make('password');
        $now = date('Y-m-d H:i:s');

        $remaining = self::NUM_USERS - $existing;
        $offset = $existing;

        while ($remaining > 0) {
            $batchSize = min(1000, $remaining);
            $batch = [];

            for ($i = 0; $i < $batchSize; $i++) {
                $n = $offset + $i + 1;
                $batch[] = [
                    'name' => "User {$n}",
                    'email' => "user{$n}@example.test",
                    'email_verified_at' => $now,
                    'password' => $password,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('users')->insert($batch);
            $offset += $batchSize;
            $remaining -= $batchSize;
        }
    }

    /**
     * Build a ~PAYLOAD_AVG_BYTES payload string once, with placeholder tokens
     * that are cheaply substituted per row.
     */
    private function payloadTemplate(): string
    {
        $payload = [
            'name' => '{{NAME}}',
            'category' => '{{CATEGORY}}',
            'description' => 'Join us for {{NAME}} — a {{CATEGORY}} you won\'t want to miss.',
            'organizer' => [
                'name' => '{{ORGANIZER}}',
                'verified' => true,
            ],
            'venue' => [
                'name' => '{{VENUE}}',
                'capacity' => '{{CAPACITY}}',
            ],
            'location' => [
                'lat' => '{{LAT}}',
                'lng' => '{{LNG}}',
            ],
            'schedule' => [
                'starts_at' => '{{STARTS}}',
                'ends_at' => '{{ENDS}}',
            ],
            'pricing' => [
                'currency' => 'USD',
                'min_price' => '{{PRICE}}',
            ],
            'tags' => ['live', 'in-person', 'featured', 'all-ages'],
            'images' => '{{IMAGES}}',
            'notes' => '',
        ];

        $encoded = json_encode($payload);
        $pad = self::PAYLOAD_AVG_BYTES - strlen($encoded);
        if ($pad > 0) {
            $payload['notes'] = str_repeat('Lorem ipsum dolor sit amet consectetur adipiscing elit. ', (int) ceil($pad / 56));
            $payload['notes'] = substr($payload['notes'], 0, $pad);
        }

        return json_encode($payload);
    }

    private function venueName(): string
    {
        $a = ['The Grand', 'Riverside', 'Downtown', 'Skyline', 'Harbor', 'Old Town', 'Central', 'Sunset'];
        $b = ['Hall', 'Arena', 'Pavilion', 'Gardens', 'Warehouse', 'Theatre', 'Rooftop', 'Stadium'];

        return $a[array_rand($a)].' '.$b[array_rand($b)];
    }

    private function randomImagesJson(): string
    {
        $count = mt_rand(1, count(self::IMAGES));
        $keys = (array) array_rand(self::IMAGES, $count);

        return json_encode(array_map(
            fn (int $key): string => self::IMAGES[$key],
            $keys,
        ));
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }

    private function uuidv4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0F) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /** @return array<int,int> */
    private function cumulativeWeights(array $weights): array
    {
        $cumulative = [];
        $sum = 0;
        foreach ($weights as $w) {
            $sum += $w;
            $cumulative[] = $sum;
        }

        return $cumulative;
    }

    /** @param array<int,int> $cumulative */
    private function pick(array $cumulative): int
    {
        $total = end($cumulative);
        $roll = mt_rand(1, $total);
        foreach ($cumulative as $index => $threshold) {
            if ($roll <= $threshold) {
                return $index;
            }
        }

        return 0;
    }

    private function withSeedingPragmas(callable $callback): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'sqlite') {
            $callback();

            return;
        }

        DB::statement('PRAGMA journal_mode = MEMORY');
        DB::statement('PRAGMA synchronous = OFF');
        DB::statement('PRAGMA temp_store = MEMORY');
        DB::statement('PRAGMA cache_size = -64000');

        try {
            $callback();
        } finally {
            DB::statement('PRAGMA journal_mode = WAL');
            DB::statement('PRAGMA synchronous = NORMAL');
        }
    }
}
