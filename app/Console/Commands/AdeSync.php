<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
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
 * Copies the Amsterdam Dance Event festival program into the ade-artist and
 * ade-event entry types (see AdePlannerSeeder), linked both ways:
 * ade-event.lineup -> ade-artist entries, ade-artist.events -> ade-event entries.
 *
 * ADE has no public API. The program pages load their lists from an
 * undocumented JSON endpoint (40 rows per page, empty `data` past the end).
 * The events list has no lineup, so each artist page is fetched for the ids
 * of the events that artist plays; the lineups are that list reversed.
 *
 *   php artisan ade:sync                 full run, about 25-30 minutes
 *   php artisan ade:sync --reuse-pages   relink from artist pages fetched before, about 2 minutes
 *   php artisan ade:sync --only=events   refresh times and sold-out, keeps lineups, about 1 minute
 *   php artisan ade:sync --limit=20      test run
 */
class AdeSync extends Command
{
    protected $signature = 'ade:sync
        {--only= : "events" to refresh events only}
        {--reuse-pages : Use artist page data already stored instead of fetching every artist page}
        {--limit=0 : Stop after this many artists (testing)}
        {--concurrency=3 : Parallel artist page requests}
        {--from=2026-10-21} {--to=2026-10-25}';

    protected $description = 'Sync the Amsterdam Dance Event program into VAMS (ade-artist, ade-event)';

    private const BASE = 'https://www.amsterdam-dance-event.nl';

    private const FESTIVAL_TYPES = '8262,8263';

    private const USER_AGENT = 'hiren.ninja ADE Planner (info@hiren.ninja)';

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
        $rows = $this->fetchList('events');
        $this->info('Events from ADE: '.count($rows));

        $now = now()->toIso8601String();
        $items = collect($rows)->map(fn (array $row): array => [
            'title' => $row['title'],
            'content' => [
                'externalId' => (string) $row['id'],
                'subtitle' => $row['subtitle'] ?? null,
                'startsAt' => $this->toIso($row['start_date_time'] ?? null),
                'endsAt' => $this->toIso($row['end_date_time'] ?? null),
                'venue' => $row['venue']['title'] ?? null,
                'categories' => $row['categories'] ?? null,
                'soldOut' => (bool) ($row['soldOut'] ?? false),
                'adeUrl' => $row['url'],
                'syncedAt' => $now,
            ],
        ])->sortBy('content.startsAt')->values();

        // The lineup comes from the artist pass; keep it when only events are refreshed.
        $ids = $this->upsert('ade-event', $items, preserve: ['lineup']);
        $this->info('ade-event saved: '.count($ids));

        return $ids;
    }

    /**
     * @param  array<string, string>  $eventIds  ADE event id => VAMS entry id
     * @return array<string, list<string>> VAMS event entry id => VAMS artist entry ids
     */
    private function syncArtists(array $eventIds): array
    {
        $rows = collect($this->fetchList('persons'));
        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $rows = $rows->take($limit);
        }
        $this->info('Artists from ADE: '.$rows->count());

        $details = $this->option('reuse-pages')
            ? $this->storedArtistDetails($eventIds)
            : $this->fetchArtistDetails($rows);

        $now = now()->toIso8601String();
        $items = $rows->map(fn (array $row): array => [
            'title' => $this->cleanName($row['title']),
            'content' => [
                'externalId' => (string) $row['id'],
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
        $this->info('ade-artist saved: '.count($artistIds).', artist pages missing: '.($rows->count() - count($details)));

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

    /** @return array<int, array<string, mixed>> */
    private function fetchList(string $section): array
    {
        $rows = [];
        for ($page = 0; ; $page++) {
            $response = $this->http()->get(self::BASE.'/api/program/filter/', [
                'section' => $section,
                'type' => self::FESTIVAL_TYPES,
                'from' => $this->option('from'),
                'to' => $this->option('to'),
                'page' => $page,
            ])->throw();

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
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, array{spotifyId: ?string, eventIds: list<string>}> keyed by ADE artist id
     */
    private function fetchArtistDetails(Collection $rows): array
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
                    $details[(string) $id] = $this->parseArtistPage($response->body());
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
                        array_map(fn (string $id): ?string => $adeIdByEntryId[$id] ?? null, $entry->content['events'] ?? []),
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
        Entry::withoutEvents(function () use ($items, $existing, $type, $preserve, &$ids): void {
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
