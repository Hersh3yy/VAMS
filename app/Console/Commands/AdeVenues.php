<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Database\Seeders\AdePlannerSeeder;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Places for the ADE venues: one ade-venue entry per venue name, with coordinates,
 * neighbourhood and Amsterdam district, so ADE Planner can filter by area, map a plan
 * and estimate travel time. Venues barely change, so only new ones are looked up:
 *
 *   php artisan ade:venues            geocode venues that have no ade-venue entry yet
 *   php artisan ade:venues --refresh  look every venue up again
 *
 * Addresses go to PDOK Locatieserver (Dutch government address register, no key);
 * the district comes from Amsterdam's own geosearch (gebieden/stadsdelen).
 */
class AdeVenues extends Command
{
    /** Amsterdam's districts in the English ADE uses for its area labels. */
    private const DISTRICTS = [
        'Centrum' => 'Centre', 'West' => 'West', 'Nieuw-West' => 'Nieuw-West', 'Zuid' => 'South',
        'Oost' => 'East', 'Noord' => 'North', 'Zuidoost' => 'Zuidoost', 'Weesp' => 'Weesp',
    ];

    protected $signature = 'ade:venues {--refresh : Look up every venue again}';

    protected $description = 'Geocode ADE venues into ade-venue entries (coordinates, neighbourhood, district)';

    public function handle(): int
    {
        $owner = User::where('email', AdePlannerSeeder::OWNER_EMAIL)->firstOrFail();
        $venueType = EntryType::where('slug', 'ade-venue')->firstOrFail();
        $existing = Entry::where('entry_type_id', $venueType->id)->where('user_id', $owner->id)->get()
            ->keyBy(fn (Entry $venue): string => self::key($venue->title));

        $venues = $this->venuesFromEvents($owner);
        $todo = $this->option('refresh') ? $venues : $venues->reject(fn (array $venue, string $key): bool => $existing->has($key));
        $this->info("Venues on the program: {$venues->count()}, to look up: {$todo->count()}");

        $bar = $this->output->createProgressBar($todo->count());
        $failed = 0;
        foreach ($todo as $key => $venue) {
            $place = $this->geocode($venue['address'] ?: $venue['name'].', Amsterdam');
            if (! $place) {
                $failed++;
            }
            $content = array_filter([
                'address' => $venue['address'],
                'lat' => $place['lat'] ?? null,
                'lng' => $place['lng'] ?? null,
                'neighbourhood' => $place['neighbourhood'] ?? null,
                'area' => $place['area'] ?? null,
                // North of the IJ: getting there from the centre means the ferry.
                'acrossIj' => ($place['area'] ?? null) === 'North',
                'precision' => $place['precision'] ?? 'not found',
                'geocodedAt' => now()->toIso8601String(),
            ], fn (mixed $value): bool => $value !== null);

            Entry::withoutEvents(function () use ($existing, $key, $venue, $content, $owner, $venueType): void {
                $entry = $existing->get($key);
                $entry
                    ? $entry->update(['content' => [...$entry->content, ...$content]])
                    : Entry::create([
                        'id' => (string) Str::uuid(), 'user_id' => $owner->id, 'entry_type_id' => $venueType->id,
                        'title' => $venue['name'], 'content' => $content, 'status' => 'published', 'published_at' => now(), 'order' => 0,
                    ]);
            });
            $bar->advance();
            usleep(150_000);
        }
        $bar->finish();
        $this->newLine();
        $this->info('Done'.($failed ? ", {$failed} without a place (kept, marked 'not found')" : '').'.');

        return self::SUCCESS;
    }

    public static function key(string $name): string
    {
        return Str::of($name)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString();
    }

    /** @return Collection<string, array{name: string, address: ?string}> keyed by venue key */
    private function venuesFromEvents(User $owner): Collection
    {
        $eventType = EntryType::where('slug', 'ade-event')->firstOrFail();

        return Entry::where('entry_type_id', $eventType->id)->where('user_id', $owner->id)->where('status', 'published')->get()
            ->filter(fn (Entry $event): bool => filled($event->content['venue'] ?? null))
            ->groupBy(fn (Entry $event): string => self::key($event->content['venue']))
            ->map(fn (Collection $events): array => [
                'name' => $events->first()->content['venue'],
                // The most common address listed for this venue.
                'address' => $events->pluck('content.address')->filter()->countBy()->sortDesc()->keys()->first(),
            ]);
    }

    /** @return array{lat?: float, lng?: float, neighbourhood?: ?string, area: ?string, precision: string}|null */
    private function geocode(string $query): ?array
    {
        $params = ['q' => $query, 'rows' => 1, 'fl' => 'type,centroide_ll,buurtnaam,wijknaam,gemeentenaam'];
        // "Kleine-Gartmanplantsoen, Amsterdam" otherwise finds a same-named street elsewhere.
        if (str_contains(strtolower($query), 'amsterdam')) {
            $params['fq'] = 'gemeentenaam:Amsterdam';
        }
        $doc = $this->http()->get('https://api.pdok.nl/bzk/locatieserver/search/v3_1/free', $params)->json('response.docs.0');

        if (! $doc || ! preg_match('/POINT\(([\d.]+) ([\d.]+)\)/', $doc['centroide_ll'] ?? '', $point)) {
            return null;
        }
        // Only a town or municipality: its centre would put the venue in the wrong place.
        if (in_array($doc['type'] ?? '', ['woonplaats', 'gemeente'], true)) {
            return ['precision' => $doc['type'], 'area' => ($doc['gemeentenaam'] ?? null) === 'Amsterdam' ? null : ($doc['gemeentenaam'] ?? null)];
        }

        $lat = (float) $point[2];
        $lng = (float) $point[1];

        return [
            'lat' => round($lat, 6),
            'lng' => round($lng, 6),
            'neighbourhood' => $doc['buurtnaam'] ?? $doc['wijknaam'] ?? null,
            'area' => ($doc['gemeentenaam'] ?? null) === 'Amsterdam' ? $this->district($lat, $lng) : ($doc['gemeentenaam'] ?? null),
            // An exact address, or only a street, postcode or town (less precise).
            'precision' => $doc['type'] ?? 'unknown',
        ];
    }

    private function district(float $lat, float $lng): ?string
    {
        $name = $this->http()->get('https://api.data.amsterdam.nl/geosearch/', [
            'datasets' => 'gebieden/stadsdelen', 'lat' => $lat, 'lon' => $lng, 'radius' => 1,
        ])->json('features.0.properties.display');

        return $name ? (self::DISTRICTS[$name] ?? $name) : null;
    }

    private function http(): PendingRequest
    {
        return Http::withUserAgent('hiren.ninja ADE Planner (info@hiren.ninja)')->timeout(15)->retry(2, 500, throw: false);
    }
}
