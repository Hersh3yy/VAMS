<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Database\Seeders\ImageColorsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off move of the Image Colors presets from Strapi into VAMS entries.
 *
 * Reads a snapshot of the Strapi `color-presets` collection (bundled in
 * database/data/, captured with populate=*) and writes, per Strapi preset, one
 * `preset` entry plus one `processed-image` entry for each analysed image. The
 * processed images reference their preset instead of being embedded, so a big
 * preset becomes many small rows (Strapi hit 413s on the embedded blob).
 *
 * Matching is by `content.strapi_id`, so re-running updates in place instead of
 * duplicating. Images are not downloaded: the sourceImage URLs already point at
 * the Spaces bucket and are kept as-is.
 *
 * Run ImageColorsSeeder first (entry types + Itamar's permissions).
 *
 *   php artisan strapi:import-image-colors --dry-run
 *   php artisan strapi:import-image-colors
 */
class ImportImageColors extends Command
{
    protected $signature = 'strapi:import-image-colors
        {--file= : Snapshot JSON (default: database/data/image-colors-strapi-snapshot.json)}
        {--user=itamar@gilboa.net : Owner of the entries}
        {--dry-run : Report what would change, write nothing}';

    protected $description = 'Import Image Colors presets from a Strapi snapshot into VAMS entries';

    public function handle(): int
    {
        $file = $this->option('file') ?: database_path('data/image-colors-strapi-snapshot.json');
        if (! is_file($file)) {
            $this->error("Snapshot not found: {$file}");

            return self::FAILURE;
        }
        $snapshot = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        $presets = $snapshot['data'] ?? [];

        $user = User::where('email', $this->option('user'))->first();
        if (! $user) {
            $this->error("User not found: {$this->option('user')}");

            return self::FAILURE;
        }

        $types = EntryType::whereIn('slug', ['preset', 'processed-image'])->get()->keyBy('slug');
        if ($types->count() !== 2) {
            $this->error('Entry types missing. Run: php artisan db:seed --class=ImageColorsSeeder');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $presetCount = 0;
        $imageCount = 0;

        DB::transaction(function () use ($presets, $user, $types, $dryRun, &$presetCount, &$imageCount): void {
            foreach ($presets as $row) {
                $strapiId = $row['id'];
                $a = $row['attributes'] ?? [];
                $name = trim((string) ($a['Name'] ?? ''));

                $images = $this->asList($a['processed_images'] ?? []);

                $preset = $this->upsertPreset($user, $types['preset'], $strapiId, $name, $dryRun);
                $presetCount++;

                foreach ($images as $i => $image) {
                    // Without a saved preset (dry run) there is no id to link to.
                    $presetId = $preset?->id;
                    $this->upsertImage($user, $types['processed-image'], $strapiId, $i, $image, $presetId, $dryRun);
                    $imageCount++;
                }
            }
        });

        $this->newLine();
        $this->info(sprintf(
            '%s %d presets, %d processed images.',
            $dryRun ? 'Dry run:' : 'Imported:',
            $presetCount,
            $imageCount
        ));

        return self::SUCCESS;
    }

    private function upsertPreset(User $user, EntryType $type, int|string $strapiId, string $name, bool $dryRun): ?Entry
    {
        $entry = Entry::where('user_id', $user->id)
            ->where('entry_type_id', $type->id)
            ->where('content->strapi_id', (string) $strapiId)
            ->first();

        $this->line(sprintf('%s preset #%s "%s"', $entry ? 'update' : 'create', $strapiId, $name));

        if ($dryRun) {
            return $entry;
        }

        $entry ??= new Entry(['user_id' => $user->id, 'entry_type_id' => $type->id]);
        $entry->fill([
            'title' => $name !== '' ? $name : "Preset {$strapiId}",
            'content' => [
                'description' => $entry->content['description'] ?? '',
                'institution' => $entry->content['institution'] ?? '',
                'strapi_id' => (string) $strapiId,
            ],
            'status' => 'published',
            'published_at' => $entry->published_at ?? now(),
            'order' => 0,
        ]);
        $entry->save();

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $image
     */
    private function upsertImage(User $user, EntryType $type, int|string $presetStrapiId, int $index, array $image, ?string $presetId, bool $dryRun): void
    {
        // Stable per-image id: the Strapi preset id plus the image's position.
        $strapiId = "{$presetStrapiId}:{$index}";
        $name = trim((string) ($image['name'] ?? '')) ?: "Image {$strapiId}";

        $entry = Entry::where('user_id', $user->id)
            ->where('entry_type_id', $type->id)
            ->where('content->strapi_id', $strapiId)
            ->first();

        $this->line(sprintf('  %s processed-image #%s "%s"', $entry ? 'update' : 'create', $strapiId, $name));

        if ($dryRun) {
            return;
        }

        $entry ??= new Entry(['user_id' => $user->id, 'entry_type_id' => $type->id]);
        $entry->fill([
            'title' => $name,
            'content' => [
                // entry_relation value: an array of entry ids (here the owning preset).
                'preset' => $presetId ? [$presetId] : [],
                'source_image_url' => $image['sourceImage'] ?? '',
                'colors' => $image['colors'] ?? [],
                'analysis_settings' => $image['analysisSettings'] ?? [],
                'strapi_id' => $strapiId,
            ],
            'status' => 'published',
            'published_at' => $entry->published_at ?? now(),
            'order' => $index,
        ]);
        $entry->save();
    }

    /**
     * Strapi stored processed_images as a json array (sometimes a json string).
     *
     * @return array<int, array<string, mixed>>
     */
    private function asList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }
}
