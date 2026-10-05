<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Database\Seeders\AdePlannerSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

/**
 * Topics for ADE daytime events ("Labels, publishing & sync", "Wellbeing & movement"),
 * classified by judgment outside VAMS (Claude reads each event) and stored on the
 * ade-event as content.topics. ade:sync keeps them, so only new events need classifying:
 *
 *   php artisan ade:topics --missing=storage/app/ade-topics-todo.jsonl   events still without topics
 *   php artisan ade:topics storage/app/ade-topics.json                   import {"<entry id or series key>": ["slug", ...]}
 */
class AdeTopics extends Command
{
    /** Slug => label; hiren.ninja shows the labels. */
    public const TOPICS = [
        'business' => 'Music business',
        'artist-careers' => 'Artist careers',
        'labels-sync' => 'Labels, publishing & sync',
        'tech-ai' => 'Tech, data & AI',
        'marketing-fans' => 'Marketing, media & fans',
        'live-events' => 'Live events & promoters',
        'legal-rights' => 'Legal & rights',
        'startups' => 'Startups & investment',
        'sustainability-impact' => 'Sustainability & social impact',
        'scenes-regions' => 'Scenes & regions',
        'culture-history' => 'Culture & history',
        'production-djing' => 'Production, DJing & gear',
        'art-immersive' => 'Art & immersive',
        'listening-showcases' => 'Listening & showcases',
        'wellbeing-movement' => 'Wellbeing & movement',
        'networking' => 'Networking & meetups',
    ];

    protected $signature = 'ade:topics
        {path? : JSON file mapping an entry id or series key to topic slugs, to import}
        {--missing= : Write daytime events that have no topics yet as JSON lines to this path}';

    protected $description = 'Import or list topics for ADE daytime events';

    public function handle(): int
    {
        $events = $this->daytimeEvents();

        if ($missing = $this->option('missing')) {
            return $this->writeMissing($events, base_path($missing));
        }

        $path = $this->argument('path');
        if (! $path) {
            $this->error('Give a JSON file to import, or --missing=<path> to list what needs topics.');

            return self::FAILURE;
        }

        /** @var array<string, list<string>> $map */
        $map = json_decode(File::get(base_path($path)), true, flags: JSON_THROW_ON_ERROR);
        $invalid = collect($map)->flatten()->unique()->reject(fn (string $slug): bool => isset(self::TOPICS[$slug]));
        if ($invalid->isNotEmpty()) {
            $this->error('Unknown topics: '.$invalid->implode(', '));

            return self::FAILURE;
        }

        $updated = 0;
        Entry::withoutEvents(function () use ($events, $map, &$updated): void {
            foreach ($events as $event) {
                $topics = $map[$event->id] ?? (isset($event->content['series']) ? ($map[$event->content['series']] ?? null) : null);
                if ($topics === null || ($event->content['topics'] ?? null) === array_values($topics)) {
                    continue;
                }
                $event->update(['content' => [...$event->content, 'topics' => array_values($topics)]]);
                $updated++;
            }
        });

        $left = $events->filter(fn (Entry $event): bool => empty($event->fresh()?->content['topics']))->count();
        $this->info("Topics saved on {$updated} events; {$left} daytime events still without topics.");

        return self::SUCCESS;
    }

    /** @param  Collection<int, Entry>  $events */
    private function writeMissing(Collection $events, string $path): int
    {
        $lines = $events
            ->filter(fn (Entry $event): bool => empty($event->content['topics']))
            ->unique(fn (Entry $event): string => $event->content['series'] ?? $event->id)
            ->map(fn (Entry $event): string => json_encode([
                'key' => $event->content['series'] ?? $event->id,
                'title' => $event->title,
                'subtitle' => mb_substr((string) ($event->content['subtitle'] ?? ''), 0, 160),
                'venue' => $event->content['venue'] ?? null,
                'program' => $event->content['program'] ?? null,
                'labels' => array_values(array_unique([...($event->content['eventTypes'] ?? []), ...($event->content['tags'] ?? []), ...($event->content['genres'] ?? [])])),
                'kinds' => $event->content['kinds'] ?? [],
            ], JSON_UNESCAPED_UNICODE));

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $lines->implode("\n").($lines->isEmpty() ? '' : "\n"));
        $this->info("{$lines->count()} events need topics: {$path}");

        return self::SUCCESS;
    }

    /** @return Collection<int, Entry> */
    private function daytimeEvents(): Collection
    {
        $type = EntryType::where('slug', 'ade-event')->firstOrFail();
        $owner = User::where('email', AdePlannerSeeder::OWNER_EMAIL)->firstOrFail();

        return Entry::where('entry_type_id', $type->id)
            ->where('user_id', $owner->id)
            ->where('status', 'published')
            ->get()
            ->filter(fn (Entry $event): bool => ($event->content['isParty'] ?? true) === false)
            ->values();
    }
}
