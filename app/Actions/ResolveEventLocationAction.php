<?php

namespace App\Actions;

use Psr\SimpleCache\CacheInterface;

class ResolveEventLocationAction
{
    private CacheInterface $cache;

    private int $ttl;

    /**
     * @param  CacheInterface  $cache  PSR-16 совместимый драйвер кэша (Redis, Memcached, File и т.д.)
     * @param  int  $ttl  Время жизни кэша в секундах (по умолчанию 30 дней)
     */
    public function __construct(CacheInterface $cache, int $ttl = 2592000)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    /**
     * Разрешает координаты в массив данных с использованием кэширования.
     *
     * @return array{country: ?string, city: ?string, address: ?string}
     */
    public function handle(float $latitude, float $longitude): array
    {
        $cacheKey = sprintf('geo_location_%s_%s', str_replace(['.', '-'], '_', $latitude), str_replace(['.', '-'], '_', $longitude));

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://openstreetmap.org?lat=$latitude&long=$longitude");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, env('OPENMAP_USERAGENT', ''));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $defaultResult = [
            'country' => null,
            'city' => null,
            'address' => null,
        ];

        if ($httpCode !== 200 || ! $response) {
            return $defaultResult;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || isset($data['error'])) {
            return $defaultResult;
        }

        $addressData = $data['address'] ?? [];

        $city = $addressData['city']
            ?? $addressData['town']
            ?? $addressData['village']
            ?? $addressData['municipality']
            ?? null;

        $result = [
            'country' => $addressData['country'] ?? null,
            'city' => $city,
            'address' => $data['display_name'] ?? null,
        ];

        if ($result['country'] !== null || $result['city'] !== null) {
            $this->cache->set($cacheKey, $result, $this->ttl);
        }

        return $result;
    }
}
