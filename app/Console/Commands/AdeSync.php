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
 * ade-event entry types (see AdePlannerSeeder).
 *
 * ADE has no public API. The program pages load their lists from an
 * undocumented JSON endpoint (40 rows per page, empty `data` past the end).
 * The events list has no lineup, so each artist page is fetched for the ids
 * of the events that artist plays.
 *
 *   php artisan ade:sync                 full run, about 20-25 minutes
 *   php artisan ade:sync --only=events   refresh times and sold-out, about 1 minute
 *   php artisan ade:sync --limit=20      test run
 */
class AdeSync extends Command
{
    protected $signature = 'ade:sync
        {--only= : "events" or "artists"}
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
        $only = $this->option('only');

        if ($only !== 'artists') {
            $this->syncEvents();
        }
        if ($only !== 'events') {
            $this->syncArtists();
        }

        return self::SUCCESS;
    }

    private function syncEvents(): void
    {
        $rows = $this->fetchList('events');
        $this->info('Events from ADE: '.count($rows));

        $now = now()->toIso8601String();
        $saved = $this->upsert('ade-event', collect($rows)->map(fn (array $row): array => [
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
        ])->sortBy('content.startsAt')->values());

        $this->info("ade-event saved: {$saved}");
    }

    private function syncArtists(): void
    {
        $rows = collect($this->fetchList('persons'));
        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $rows = $rows->take($limit);
        }
        $this->info('Artists from ADE: '.$rows->count());

        $details = $this->fetchArtistDetails($rows);

        $now = now()->toIso8601String();
        $saved = $this->upsert('ade-artist', $rows->map(fn (array $row): array => [
            'title' => $this->cleanName($row['title']),
            'content' => [
                'externalId' => (string) $row['id'],
                'country' => $row['country']['value'] ?? null ?: null,
                'spotifyId' => $details[$row['id']]['spotifyId'] ?? null,
                'adeUrl' => $row['url'],
                'eventIds' => $details[$row['id']]['eventIds'] ?? [],
                'syncedAt' => $now,
            ],
        ])->values());

        $failed = $rows->count() - count($details);
        $this->info("ade-artist saved: {$saved}".($failed > 0 ? ", artist pages failed: {$failed}" : ''));
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
     * @return array<int, array{spotifyId: ?string, eventIds: list<string>}>
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
                    $details[(int) $id] = $this->parseArtistPage($response->body());
                }
            }
            $bar->advance($chunk->count());
            usleep(250_000);
        }

        $bar->finish();
        $this->newLine();

        return $details;
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
     */
    private function upsert(string $slug, Collection $items): int
    {
        $type = EntryType::where('slug', $slug)->firstOrFail();
        $existing = Entry::where('entry_type_id', $type->id)
            ->where('user_id', $this->owner->id)
            ->get()
            ->keyBy(fn (Entry $entry): ?string => $entry->content['externalId'] ?? null);

        // Without model events: thousands of rows would each write an activity log line.
        Entry::withoutEvents(function () use ($items, $existing, $type): void {
            foreach ($items as $order => $item) {
                $attributes = [
                    'title' => Str::limit($item['title'], 250, ''),
                    'content' => $item['content'],
                    'status' => 'published',
                    'order' => $order,
                ];

                $entry = $existing->get($item['content']['externalId']);
                if ($entry) {
                    $entry->update($attributes);
                } else {
                    Entry::create($attributes + [
                        'id' => (string) Str::uuid(),
                        'user_id' => $this->owner->id,
                        'entry_type_id' => $type->id,
                        'published_at' => now(),
                    ]);
                }
            }
        });

        return $items->count();
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
        return trim(preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $name));
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
