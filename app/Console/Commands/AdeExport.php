<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Database\Seeders\AdePlannerSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;

/**
 * Writes ade-artist and ade-event entries to one JSON file. hiren.ninja bundles
 * it as a fallback for when the VAMS API is unreachable.
 *
 *   php artisan ade:export storage/app/ade-planner-snapshot.json
 */
class AdeExport extends Command
{
    protected $signature = 'ade:export {path : Output file, relative to the project root}';

    protected $description = 'Export the synced ADE program as a JSON snapshot';

    public function handle(): int
    {
        $artists = $this->entries('ade-artist')->map(fn (Entry $entry): array => [
            'id' => $entry->id,
            'name' => $entry->title,
            'country' => $entry->content['country'] ?? null,
            'spotifyId' => $entry->content['spotifyId'] ?? null,
            'adeUrl' => $entry->content['adeUrl'],
            'eventIds' => $entry->content['events'] ?? [],
        ]);

        $events = $this->entries('ade-event')->map(fn (Entry $entry): array => [
            'id' => $entry->id,
            'title' => $entry->title,
            'subtitle' => $entry->content['subtitle'] ?? null,
            'startsAt' => $entry->content['startsAt'],
            'endsAt' => $entry->content['endsAt'] ?? null,
            'venue' => $entry->content['venue'] ?? null,
            'categories' => $entry->content['categories'] ?? null,
            'soldOut' => (bool) ($entry->content['soldOut'] ?? false),
            'adeUrl' => $entry->content['adeUrl'],
            'lineup' => $entry->content['lineup'] ?? [],
        ]);

        $path = base_path($this->argument('path'));
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'exportedAt' => now()->toIso8601String(),
            'artists' => $artists->values(),
            'events' => $events->values(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->info("Wrote {$artists->count()} artists and {$events->count()} events to {$path}");

        return self::SUCCESS;
    }

    /** @return Collection<int, Entry> */
    private function entries(string $slug): Collection
    {
        $type = EntryType::where('slug', $slug)->firstOrFail();

        $owner = User::where('email', AdePlannerSeeder::OWNER_EMAIL)->firstOrFail();

        return Entry::where('entry_type_id', $type->id)
            ->where('user_id', $owner->id)
            ->orderBy('order')
            ->get();
    }
}
