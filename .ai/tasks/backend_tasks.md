# Coding Test — Event Visuals
Use [CODING_TEST.md](../../CODING_TEST.md) and [index.md](../index.md) for the general context.

## 1. Backend Development

- `[x]` User model
    - `[x]` Add "timezone" column, update seeders and migrations
- `[x]` Event model
    - `[x]` Add columns "starts_at", "ends_at", "country", "city", "address"
    - `[x]` Update seeders and migrations
    - `[x]` Create "ResolveEventLocationAction" with caching to get addresses and store them into Events
    - `[x]` Update CITY_ANCHORS in EventSeeder with "ResolveEventLocationAction" to have lat/long/country/city/address values

