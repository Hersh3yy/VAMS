<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Database\Seeders\ItamarWebsiteSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off move of Itamar Gilboa's website content from Strapi into VAMS entries.
 *
 * Reads a snapshot of the four Strapi collections (bundled in
 * database/data/, captured while Strapi was still up) and writes one entry per
 * Strapi item. Matching is by `content.strapi_id`, so re-running updates in
 * place instead of duplicating. Images are not downloaded: they already live in
 * the bengijzel Spaces bucket, so each one keeps its URL.
 *
 * Run ItamarWebsiteSeeder first (entry types + permissions).
 *
 *   php artisan strapi:import-itamar-website --dry-run
 *   php artisan strapi:import-itamar-website
 */
class ImportItamarWebsite extends Command
{
    protected $signature = 'strapi:import-itamar-website
        {--file= : Snapshot JSON (default: database/data/itamargilboa-strapi-snapshot.json)}
        {--user=itamar@gilboa.net : Owner of the entries}
        {--dry-run : Report what would change, write nothing}';

    protected $description = "Import Itamar Gilboa's website content from a Strapi snapshot into VAMS entries";

    public function handle(): int
    {
        $file = $this->option('file') ?: database_path('data/itamargilboa-strapi-snapshot.json');
        if (! is_file($file)) {
            $this->error("Snapshot not found: {$file}");

            return self::FAILURE;
        }
        $snapshot = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);

        $user = User::where('email', $this->option('user'))->first();
        if (! $user) {
            $this->error("User not found: {$this->option('user')}");

            return self::FAILURE;
        }

        $types = EntryType::whereIn('slug', ItamarWebsiteSeeder::SLUGS)->get()->keyBy('slug');
        if ($types->count() !== count(ItamarWebsiteSeeder::SLUGS)) {
            $this->error('Entry types missing. Run: php artisan db:seed --class=ItamarWebsiteSeeder');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        DB::transaction(function () use ($snapshot, $user, $types, $dryRun): void {
            // Strapi returns the biography as a single object, the rest as lists.
            $bio = $snapshot['ig-biography'];
            $this->upsert($user, $types['ig-biography'], $bio['id'], 'Biography', 0, [
                'text' => $bio['attributes']['Text'] ?? '',
                'content' => $bio['attributes']['Content'] ?? '',
                'image' => $this->images($bio['attributes']['Image'] ?? null),
            ], $dryRun);

            foreach ($snapshot['news-articles'] as $item) {
                $a = $item['attributes'];
                $this->upsert($user, $types['news-articles'], $item['id'], trim($a['Title'] ?? ''), $a['Order'] ?? null, [
                    'url' => $a['URL'] ?? '',
                    'images' => $this->images($a['Images'] ?? null),
                ], $dryRun);
            }

            foreach ($snapshot['ig-projects'] as $item) {
                $a = $item['attributes'];
                $this->upsert($user, $types['ig-projects'], $item['id'], trim($a['Title'] ?? ''), $a['Order'] ?? null, [
                    'slug' => $a['slug'] ?? '',
                    'text' => $a['Text'] ?? '',
                    'video_link' => $a['VideoLink'] ?? '',
                    'images' => $this->images($a['Images'] ?? null),
                ], $dryRun);
            }

            foreach ($snapshot['ig-landing-page-slideshow-images'] as $item) {
                $a = $item['attributes'];
                $this->upsert($user, $types['ig-landing-page-slideshow-images'], $item['id'], 'Slide '.($a['Order'] ?? $item['id']), $a['Order'] ?? null, [
                    'image' => $this->images($a['Image'] ?? null),
                ], $dryRun);
            }
        });

        $this->info($dryRun ? 'Dry run: nothing written.' : 'Import complete.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function upsert(User $user, EntryType $type, int|string $strapiId, string $title, mixed $order, array $content, bool $dryRun): void
    {
        $content['strapi_id'] = (string) $strapiId;

        $entry = Entry::where('user_id', $user->id)
            ->where('entry_type_id', $type->id)
            ->where('content->strapi_id', (string) $strapiId)
            ->first();

        $images = count($content['images'] ?? $content['image'] ?? []);
        $this->line(sprintf('%s %s #%s "%s" (%d images)', $entry ? 'update' : 'create', $type->slug, $strapiId, $title, $images));

        if ($dryRun) {
            return;
        }

        $entry ??= new Entry(['user_id' => $user->id, 'entry_type_id' => $type->id]);
        $entry->fill([
            'title' => $title !== '' ? $title : "{$type->name} {$strapiId}",
            'content' => $content,
            'status' => 'published',
            'published_at' => $entry->published_at ?? now(),
            // Strapi's empty Order becomes 0; the site treats 0 like empty (sorts last).
            'order' => is_numeric($order) ? (int) $order : 0,
        ]);
        $entry->save();
    }

    /**
     * Strapi media relation ({ data: {...} } or { data: [...] }) to image_collection items.
     *
     * @return array<int, array<string, mixed>>
     */
    private function images(?array $relation): array
    {
        $data = $relation['data'] ?? null;
        if (! $data) {
            return [];
        }
        $items = array_is_list($data) ? $data : [$data];

        return array_map(function (array $media): array {
            $a = $media['attributes'];

            return [
                // Path inside the bucket, e.g. hiren-devs-strapi/abc.jpg
                'path' => ltrim((string) parse_url($a['url'], PHP_URL_PATH), '/'),
                'url' => $a['url'],
                'alt' => $a['alternativeText'] ?? '',
                'name' => $a['name'] ?? '',
                'width' => $a['width'] ?? null,
                'height' => $a['height'] ?? null,
            ];
        }, $items);
    }
}
