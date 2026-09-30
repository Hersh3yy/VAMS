<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use App\Support\Ade\AdeEventClassifier;
use Carbon\CarbonImmutable;
use Database\Seeders\AdePlannerSeeder;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Copies the Amsterdam Dance Event program (festival and the ADE Pro conference)
 * into the ade-artist and ade-event entry types (see AdePlannerSeeder), linked both ways:
 * ade-event.lineup -> ade-artist entries, ade-artist.events -> ade-event entries.
 *
 * ADE has no public API. The program pages load their lists from an
 * undocumented JSON endpoint (40 rows per page, empty `data` past the end).
 * The events list has no lineup, so each artist page is fetched for the ids
 * of the events that artist plays; the lineups are that list reversed.
 *
 *   php artisan ade:sync                 full run (artist + event pages), about 35-40 minutes
 *   php artisan ade:sync --reuse-pages   relink and reclassify from pages fetched before, about 8 minutes
 *   php artisan ade:sync --only=events   refresh events, tickets and sold-out; keeps lineups, about 10 minutes
 *   php artisan ade:sync --limit=20      test run
 */
class AdeSync extends Command
{
    protected $signature = 'ade:sync
        {--only= : "events" to refresh events only}
        {--reuse-pages : Reuse artist and event page data already stored; only fetch pages not seen before}
        {--limit=0 : Stop after this many artists (testing)}
        {--concurrency=3 : Parallel artist page requests}
        {--from=2026-10-21} {--to=2026-10-25}';

    protected $description = 'Sync the Amsterdam Dance Event program into VAMS (ade-artist, ade-event)';

    private const BASE = 'https://www.amsterdam-dance-event.nl';

    private const FESTIVAL_TYPES = '8262,8263';

    /** ADE Pro, the conference program (talks, panels, masterclasses). */
    private const PRO_TYPES = '8264';

    private const USER_AGENT = 'hiren.ninja ADE Planner (info@hiren.ninja)';

    /** Area labels in ADE's categories; everything else is a genre, an event type or a loose tag. */
    private const AREAS = ['Centre', 'West', 'East', 'South', 'North', 'Nieuw-West', 'Zuidoost', 'South-East', 'Noord', 'Oost', 'Zuid'];

    private User $owner;

    public function handle(): int
    {
        $this->owner = User::where('email', AdePlannerSeeder::OWNER_EMAIL)->firstOrFail();

        $eventIds = $this->syncEvents();

        if ($this->option('only') !== 'events') {
            $lineups = $this->syncArtists($eventIds);

            // A limited test run only knows part of each lineup; don't overwrite the full ones.
            if ((int) $this->option('limit') === 0) {
                $this->saveLineups($eventIds, $lineups);
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, string> ADE event id => VAMS entry id
     */
    private function syncEvents(): array
    {
        $festival = $this->fetchList('events');
        $pro = $this->fetchList('events', types: self::PRO_TYPES);
        $proIds = array_flip(array_map(fn (array $row): string => (string) $row['id'], $pro));
        $rows = $this->uniqueById([...$festival, ...$pro]);
        $this->info('Events from ADE: '.count($festival).' festival, '.count($pro).' ADE Pro');

        $withTickets = array_flip(array_map(
            fn (array $row): string => (string) $row['id'],
            $this->fetchList('events', ['ticketsAvailable' => 'true']),
        ));
        $this->info('With tickets available: '.count($withTickets));

        $groups = $this->fetchCategoryGroups(self::FESTIVAL_TYPES) + $this->fetchCategoryGroups(self::PRO_TYPES);
        $pages = $this->pageDetails(
            collect($rows),
            $this->option('reuse-pages') ? $this->storedEventDetails() : [],
            fn (string $html): array => $this->parseEventPage($html),
        );

        $now = now()->toIso8601String();
        $items = collect($rows)->map(function (array $row) use ($withTickets, $groups, $pages, $proIds, $now): array {
            $id = (string) $row['id'];
            $isPro = isset($proIds[$id]);
            $labels = $this->splitCategories($row['categories'] ?? '');
            $types = array_values(array_filter($labels, fn (string $label): bool => ($groups[$label] ?? null) === 'type'));
            $page = $pages[$id] ?? [];

            return [
                'title' => $row['title'],
                'content' => [
                    'externalId' => $id,
                    'program' => $isPro ? 'pro' : 'festival',
                    'subtitle' => $row['subtitle'] ?? null,
                    'startsAt' => $this->toIso($row['start_date_time'] ?? null),
                    'endsAt' => $this->toIso($row['end_date_time'] ?? null),
                    'venue' => $row['venue']['title'] ?? null,
                    'address' => $page['address'] ?? null,
                    'area' => array_values(array_intersect($labels, self::AREAS))[0] ?? null,
                    // "Other" and "Live" are in ADE's genre list but help nobody choose.
                    'genres' => array_values(array_filter($labels, fn (string $label): bool => ($groups[$label] ?? null) === 'genre' && ! in_array($label, ['Other', 'Live'], true))),
                    'eventTypes' => $types,
                    'tags' => array_values(array_filter($labels, fn (string $label): bool => ! isset($groups[$label]) && ! in_array($label, self::AREAS, true))),
                    'ticketStatus' => match (true) {
                        AdeEventClassifier::isLabDiscovery(['tags' => $labels]) => 'free',
                        $isPro => 'pro pass',
                        default => $this->ticketStatus((bool) ($row['soldOut'] ?? false), isset($withTickets[$id]), $types),
                    },
                    'soldOut' => (bool) ($row['soldOut'] ?? false),
                    'ticketUrl' => $page['ticketUrl'] ?? null,
                    'ticketLabel' => $page['ticketLabel'] ?? null,
                    'categories' => $row['categories'] ?? null,
                    'adeUrl' => $row['url'],
                    'syncedAt' => $now,
                ],
            ];
        })->map(fn (array $item): array => [
            ...$item,
            'content' => [...$item['content'], ...AdeEventClassifier::classify(['title' => $item['title'], ...$item['content']])],
        ])->sortBy('content.startsAt')->values();

        $items = $this->withSeries($items);

        // The lineup comes from the artist pass; keep it when only events are refreshed.
        $ids = $this->upsert('ade-event', $items, preserve: ['lineup']);
        $this->info('ade-event saved: '.count($ids).', event pages read: '.count($pages));

        return $ids;
    }

    /**
     * Same title at the same venue on several days (exhibitions, labs, hubs) gets one
     * series key and the list of its dates, so clients can show it once.
     *
     * @param  Collection<int, array{title: string, content: array<string, mixed>}>  $items
     * @return Collection<int, array{title: string, content: array<string, mixed>}>
     */
    private function withSeries(Collection $items): Collection
    {
        $keyOf = fn (array $item): string => AdeEventClassifier::seriesKey($item['title'], $item['content']['venue'] ?? null);
        $dates = $items->groupBy($keyOf)->map(fn (Collection $group): array => $group
            ->map(fn (array $item): string => substr((string) $item['content']['startsAt'], 0, 10))
            ->unique()->sort()->values()->all());

        return $items->map(function (array $item) use ($keyOf, $dates): array {
            $seriesDates = $dates[$keyOf($item)];
            $item['content']['series'] = count($seriesDates) > 1 ? $keyOf($item) : null;
            $item['content']['seriesDates'] = count($seriesDates) > 1 ? $seriesDates : [];

            return $item;
        });
    }

    /** @param  list<string>  $types */
    private function ticketStatus(bool $soldOut, bool $available, array $types): string
    {
        return match (true) {
            $soldOut => 'sold out',
            $available => 'available',
            (bool) array_filter($types, fn (string $type): bool => str_starts_with($type, 'Free')) => 'free',
            default => 'unknown',
        };
    }

    /** @return list<string> */
    private function splitCategories(string $categories): array
    {
        return array_values(array_filter(array_map('trim', explode(' / ', $categories))));
    }

    /**
     * The program filter page lists every label with its group ("Genre", "Type"), which
     * tells a genre like "Techno" apart from an event type like "Club nights".
     *
     * @return array<string, 'genre'|'type'>
     */
    private function fetchCategoryGroups(string $types): array
    {
        $html = Http::withUserAgent(self::USER_AGENT)->timeout(30)->retry(2, 1000)
            ->get(self::BASE.'/en/program/filter/', ['section' => 'events', 'type' => $types])
            ->throw()->body();

        preg_match_all('~<[^>]*filter-popup__item[^>]*>~', $html, $items);
        $groups = [];
        foreach ($items[0] as $item) {
            preg_match('~data-filter-type="([^"]*)"~', $item, $type);
            preg_match('~data-name-readable="([^"]*)"~', $item, $name);
            $group = match ($type[1] ?? '') {
                'Genre' => 'genre',
                'Type' => 'type',
                default => null,
            };
            if ($group && isset($name[1])) {
                $groups[html_entity_decode($name[1], ENT_QUOTES | ENT_HTML5)] = $group;
            }
        }

        return $groups;
    }

    /** @return array{ticketUrl: ?string, ticketLabel: ?string, address: ?string} */
    private function parseEventPage(string $html): array
    {
        preg_match('~<a href="([^"]+)"[^>]*class="[^"]*ade-info-bar__button[^"]*"[^>]*>(.*?)</a>~s', $html, $button);
        preg_match('~google\.[a-z.]+/maps/search/\?api=1&(?:amp;)?query=[^"]*"[^>]*>\s*([^<]+?)\s*<~', $html, $address);

        return [
            'ticketUrl' => isset($button[1]) ? html_entity_decode($button[1]) : null,
            'ticketLabel' => isset($button[2]) ? (trim(html_entity_decode(strip_tags($button[2]))) ?: null) : null,
            'address' => isset($address[1]) ? html_entity_decode($address[1]) : null,
        ];
    }

    /** @return array<string, array{ticketUrl: ?string, ticketLabel: ?string, address: ?string}> keyed by ADE event id */
    private function storedEventDetails(): array
    {
        return $this->ownedEntries('ade-event')
            ->mapWithKeys(fn (Entry $entry): array => [
                (string) ($entry->content['externalId'] ?? '') => [
                    'ticketUrl' => $entry->content['ticketUrl'] ?? null,
                    'ticketLabel' => $entry->content['ticketLabel'] ?? null,
                    'address' => $entry->content['address'] ?? null,
                ],
            ])
            ->all();
    }

    /**
     * @param  array<string, string>  $eventIds  ADE event id => VAMS entry id
     * @return array<string, list<string>> VAMS event entry id => VAMS artist entry ids
     */
    private function syncArtists(array $eventIds): array
    {
        $festival = $this->fetchList('persons');
        $pro = $this->fetchList('persons', types: self::PRO_TYPES);
        $festivalIds = array_flip(array_map(fn (array $row): string => (string) $row['id'], $festival));
        $proIds = array_flip(array_map(fn (array $row): string => (string) $row['id'], $pro));
        $rows = collect($this->uniqueById([...$festival, ...$pro]));
        $this->info('People from ADE: '.count($festival).' festival artists, '.count($pro).' ADE Pro speakers');
        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $rows = $rows->take($limit);
        }
        $details = $this->pageDetails(
            $rows,
            $this->option('reuse-pages') ? $this->storedArtistDetails($eventIds) : [],
            fn (string $html): array => $this->parseArtistPage($html),
        );

        $now = now()->toIso8601String();
        $items = $rows->map(fn (array $row): array => [
            'title' => $this->cleanName($row['title']),
            'content' => [
                'externalId' => (string) $row['id'],
                'role' => match (true) {
                    isset($festivalIds[(string) $row['id']], $proIds[(string) $row['id']]) => 'artist and speaker',
                    isset($proIds[(string) $row['id']]) => 'speaker',
                    default => 'artist',
                },
                // Speakers: job and company, e.g. "Warner Chappell Music, Sync & Licensing Manager".
                'subtitle' => $row['subtitle'] ?? null,
                'country' => ($row['country']['value'] ?? '') ?: null,
                'spotifyId' => $details[(string) $row['id']]['spotifyId'] ?? null,
                'adeUrl' => $row['url'],
                // Festival events only; artist pages also link conference sessions.
                'events' => array_values(array_filter(array_map(
                    fn (string $adeEventId): ?string => $eventIds[$adeEventId] ?? null,
                    $details[(string) $row['id']]['eventIds'] ?? [],
                ))),
                'syncedAt' => $now,
            ],
        ])->values();

        $artistIds = $this->upsert('ade-artist', $items);
        $missing = $rows->reject(fn (array $row): bool => isset($details[(string) $row['id']]))->count();
        $this->info('ade-artist saved: '.count($artistIds).', artist pages missing: '.$missing);

        $lineups = [];
        foreach ($items as $item) {
            $artistId = $artistIds[$item['content']['externalId']];
            foreach ($item['content']['events'] as $eventId) {
                $lineups[$eventId][] = $artistId;
            }
        }

        return $lineups;
    }

    /**
     * @param  array<string, string>  $eventIds
     * @param  array<string, list<string>>  $lineups
     */
    private function saveLineups(array $eventIds, array $lineups): void
    {
        Entry::withoutEvents(function () use ($eventIds, $lineups): void {
            foreach (Entry::whereIn('id', array_values($eventIds))->get() as $event) {
                $content = $event->content;
                $content['lineup'] = $lineups[$event->id] ?? [];
                $event->update(['content' => $content]);
            }
        });

        $this->info('Lineups saved: '.count($lineups).' of '.count($eventIds).' events have artists');
    }

    /**
     * @param  array<string, string>  $extra  additional filter params, e.g. ticketsAvailable
     * @return array<int, array<string, mixed>>
     */
    private function fetchList(string $section, array $extra = [], string $types = self::FESTIVAL_TYPES): array
    {
        $rows = [];
        for ($page = 0; ; $page++) {
            $response = $this->http()->get(self::BASE.'/api/program/filter/', [
                'section' => $section,
                'type' => $types,
                'from' => $this->option('from'),
                'to' => $this->option('to'),
                'page' => $page,
            ] + $extra)->throw();

            // Sent as text/html, so decode the body ourselves.
            $data = json_decode($response->body(), true)['data'] ?? [];
            $data = array_values(array_filter($data));
            if ($data === []) {
                return $rows;
            }
            array_push($rows, ...$data);
        }
    }

    /**
     * Stored page data where there is some, fetched pages for the rest.
     *
     * @template T of array
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, T>  $stored  keyed by ADE id
     * @param  callable(string): T  $parse
     * @return array<string, T>
     */
    private function pageDetails(Collection $rows, array $stored, callable $parse): array
    {
        $missing = $rows->reject(fn (array $row): bool => isset($stored[(string) $row['id']]))->values();
        if ($stored !== []) {
            $this->info('Pages reused: '.count($stored).', fetching new: '.$missing->count());
        }

        return $stored + ($missing->isEmpty() ? [] : $this->fetchPageDetails($missing, $parse));
    }

    /**
     * Festival and Pro lists overlap (an artist can also speak); keep the first row per id.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function uniqueById(array $rows): array
    {
        $seen = [];
        foreach ($rows as $row) {
            $seen[(string) $row['id']] ??= $row;
        }

        return array_values($seen);
    }

    /**
     * Fetch each row's ADE page, a few at a time, and parse it.
     *
     * @template T of array
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  callable(string): T  $parse
     * @return array<string, T> keyed by ADE id
     */
    private function fetchPageDetails(Collection $rows, callable $parse): array
    {
        $details = [];
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows->chunk((int) $this->option('concurrency')) as $chunk) {
            $responses = Http::pool(fn (Pool $pool): array => $chunk->map(
                fn (array $row): PromiseInterface => $pool->as((string) $row['id'])
                    ->withUserAgent(self::USER_AGENT)
                    ->timeout(30)
                    ->retry(2, 1000, throw: false)
                    ->get($row['url'])
            )->all());

            foreach ($responses as $id => $response) {
                if ($response instanceof Response && $response->successful()) {
                    $details[(string) $id] = $parse($response->body());
                }
            }
            $bar->advance($chunk->count());
            usleep(250_000);
        }

        $bar->finish();
        $this->newLine();

        return $details;
    }

    /**
     * Artist page data from an earlier sync: `events` (entry ids) or the older `eventIds` (ADE ids).
     *
     * @param  array<string, string>  $eventIds
     * @return array<string, array{spotifyId: ?string, eventIds: list<string>}>
     */
    private function storedArtistDetails(array $eventIds): array
    {
        $adeIdByEntryId = array_flip($eventIds);

        return $this->ownedEntries('ade-artist')
            ->mapWithKeys(fn (Entry $entry): array => [
                (string) ($entry->content['externalId'] ?? '') => [
                    'spotifyId' => $entry->content['spotifyId'] ?? null,
                    'eventIds' => array_values(array_filter(array_merge(
                        array_map('strval', $entry->content['eventIds'] ?? []),
                        // ADE ids are numeric, so PHP made them int keys; cast back.
                        array_map(fn (string $id): ?string => isset($adeIdByEntryId[$id]) ? (string) $adeIdByEntryId[$id] : null, $entry->content['events'] ?? []),
                    ))),
                ],
            ])
            ->all();
    }

    /** @return array{spotifyId: ?string, eventIds: list<string>} */
    private function parseArtistPage(string $html): array
    {
        preg_match('~open\.spotify\.com/(?:intl-[a-z]{2}(?:-[A-Za-z]{2})?/)?artist/([A-Za-z0-9]{22})~', $html, $spotify);
        preg_match_all('~/en/program/\d{4}/[^/"\s]+/(\d+)/~', $html, $events);

        return [
            'spotifyId' => $spotify[1] ?? null,
            'eventIds' => array_values(array_unique($events[1])),
        ];
    }

    /**
     * Update entries matched on content.externalId, create the rest.
     *
     * @param  Collection<int, array{title: string, content: array<string, mixed>}>  $items
     * @param  list<string>  $preserve  content keys kept from the stored entry
     * @return array<string, string> externalId => VAMS entry id
     */
    private function upsert(string $slug, Collection $items, array $preserve = []): array
    {
        $type = EntryType::where('slug', $slug)->firstOrFail();
        $existing = $this->ownedEntries($slug)
            ->keyBy(fn (Entry $entry): ?string => $entry->content['externalId'] ?? null);

        $ids = [];

        // Without model events: thousands of rows would each write an activity log line.
        Entry::withoutEvents(function () use ($items, $existing, $type, $preserve, $slug, &$ids): void {
            foreach ($items as $order => $item) {
                $externalId = $item['content']['externalId'];
                $entry = $existing->get($externalId);
                $content = $item['content'];

                foreach ($preserve as $key) {
                    if ($entry && array_key_exists($key, $entry->content ?? [])) {
                        $content[$key] = $entry->content[$key];
                    }
                }

                $attributes = [
                    'title' => Str::limit($item['title'], 250, ''),
                    'content' => $content,
                    'status' => 'published',
                    'order' => $order,
                ];

                if ($entry) {
                    $entry->update($attributes);
                } else {
                    $entry = Entry::create($attributes + [
                        'id' => (string) Str::uuid(),
                        'user_id' => $this->owner->id,
                        'entry_type_id' => $type->id,
                        'published_at' => now(),
                    ]);
                }

                $ids[$externalId] = $entry->id;
            }

            // Off the ADE program now: unpublish, so the API stops serving it. A limited
            // test run doesn't see the whole program, so it never unpublishes.
            if ((int) $this->option('limit') === 0) {
                $stale = $existing->reject(fn (Entry $entry, int|string|null $externalId): bool => $externalId === null || $externalId === '' || isset($ids[$externalId]))
                    ->filter(fn (Entry $entry): bool => $entry->status === 'published');
                foreach ($stale as $entry) {
                    $entry->update(['status' => 'draft']);
                }
                if ($stale->isNotEmpty()) {
                    $this->info("{$slug}: unpublished ".$stale->count().' no longer on the ADE program');
                }
            }
        });

        return $ids;
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Entry> */
    private function ownedEntries(string $slug): \Illuminate\Database\Eloquent\Collection
    {
        $type = EntryType::where('slug', $slug)->firstOrFail();

        return Entry::where('entry_type_id', $type->id)
            ->where('user_id', $this->owner->id)
            ->get();
    }

    /** @param  array{date?: string, timezone?: string}|null  $value */
    private function toIso(?array $value): ?string
    {
        if (empty($value['date'])) {
            return null;
        }

        return CarbonImmutable::parse($value['date'], $value['timezone'] ?? 'Europe/Amsterdam')->toIso8601String();
    }

    private function cleanName(string $name): string
    {
        return trim((string) preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $name));
    }

    private function http(): PendingRequest
    {
        return Http::withUserAgent(self::USER_AGENT)
            ->accept('application/json')
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->timeout(30)
            ->retry(2, 1000);
    }
}
