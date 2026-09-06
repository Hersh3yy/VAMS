<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EntryType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Entry types for the Image Colors project (koala/img-clrs).
 *
 * The app analyses paintings, extracts a palette, matches each colour to a
 * parent colour and to a Pantone reference, and saves sets of analysed images
 * as presets. This seeder provisions the NON-learning entity types it needs to
 * persist that work on VAMS, and grants them to Itamar Gilboa.
 *
 * The learning-related types (feedback, knowledge-base) are deliberately left
 * out - they are the next session's work, once the learning stack in the app
 * has been reviewed.
 *
 * Idempotent: safe to run repeatedly (matches entry types by slug, the user by
 * email). Re-running never resets the user's password or api_key.
 *
 *   docker compose exec api php artisan db:seed --class=ImageColorsSeeder
 */
class ImageColorsSeeder extends Seeder
{
    /** Entry-type slugs this project owns. */
    public const SLUGS = ['parent-colors', 'preset', 'processed-image'];

    public function run(): void
    {
        $this->seedParentColors();
        $this->seedPreset();
        $this->seedProcessedImage();
        $this->grantToItamar();
    }

    /**
     * A versioned list of anchor colours every extracted colour is matched to.
     * Was the hardcoded 32-entry array in the app (data/colors.js); versioning
     * it here keeps old results meaningful when the list changes.
     */
    private function seedParentColors(): void
    {
        EntryType::updateOrCreate(
            ['slug' => 'parent-colors'],
            [
                'name' => 'Parent Colors',
                'description' => 'A versioned list of named anchor colours. Each processed image records which version it was matched against.',
                'is_active' => true,
                'field_config' => [
                    ['name' => 'version', 'type' => 'number', 'label' => 'Version', 'required' => true],
                    // [{ name, hex, lab: [L, a, b] }, ...]
                    ['name' => 'colors', 'type' => 'json', 'label' => 'Colours', 'required' => true],
                    ['name' => 'notes', 'type' => 'textarea', 'label' => 'Notes', 'required' => false],
                ],
            ]
        );
        $this->command->info('Entry type ready: parent-colors');
    }

    /**
     * A named set of analysed paintings (a room, an exhibition, an artist).
     * Was the Strapi "color-presets" collection. Processed images point back to
     * their preset rather than being embedded, so a large preset is many small
     * rows instead of one blob (Strapi hit 413s on the embedded shape).
     */
    private function seedPreset(): void
    {
        EntryType::updateOrCreate(
            ['slug' => 'preset'],
            [
                'name' => 'Preset',
                'description' => 'A named set of analysed images. The entry title is the preset name.',
                'is_active' => true,
                'field_config' => [
                    ['name' => 'description', 'type' => 'textarea', 'label' => 'Description', 'required' => false],
                    ['name' => 'institution', 'type' => 'text', 'label' => 'Institution', 'required' => false],
                    // Set during the one-off Strapi migration; lets that run be idempotent.
                    ['name' => 'strapi_id', 'type' => 'text', 'label' => 'Strapi ID (migration)', 'required' => false],
                ],
            ]
        );
        $this->command->info('Entry type ready: preset');
    }

    /**
     * One analysed painting: its source image plus the result of analysing it.
     * `colors` and `analysis_settings` are json passthrough (their own nested
     * shape); relations tie it to its preset and to the parent-colour list used.
     */
    private function seedProcessedImage(): void
    {
        EntryType::updateOrCreate(
            ['slug' => 'processed-image'],
            [
                'name' => 'Processed Image',
                'description' => 'One analysed painting: source image, extracted colours with matches, and the settings used. The entry title is the image name.',
                'is_active' => true,
                'field_config' => [
                    ['name' => 'preset', 'type' => 'entry_relation', 'label' => 'Preset', 'required' => true, 'entry_type_slug' => 'preset', 'min' => 1, 'max' => 1],
                    ['name' => 'parent_colors', 'type' => 'entry_relation', 'label' => 'Parent colour list used', 'required' => false, 'entry_type_slug' => 'parent-colors', 'min' => 0, 'max' => 1],
                    ['name' => 'source_image_url', 'type' => 'text', 'label' => 'Source image URL', 'required' => true],
                    // [{ color, percentage, parent:{...}, pantone:{...} }, ...]
                    ['name' => 'colors', 'type' => 'json', 'label' => 'Palette + matches', 'required' => true],
                    ['name' => 'analysis_settings', 'type' => 'json', 'label' => 'Analysis settings', 'required' => true],
                    ['name' => 'average_confidence', 'type' => 'number', 'label' => 'Average confidence', 'required' => false],
                    ['name' => 'problematic_count', 'type' => 'number', 'label' => 'Problematic matches', 'required' => false],
                ],
            ]
        );
        $this->command->info('Entry type ready: processed-image');
    }

    /**
     * Grant the three slugs to Itamar Gilboa (the project's client), on top of
     * whatever permissions he already has. Creates the account only if missing.
     */
    private function grantToItamar(): void
    {
        $email = 'itamar@gilboa.net';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Itamar Gilboa',
                'password' => Hash::make(env('ITAMAR_PASSWORD', 'password')),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_approved' => true,
                'approved_at' => now(),
            ]
        );

        $perms = array_values(array_unique(array_merge(
            $user->entry_type_permissions ?? [],
            self::SLUGS
        )));
        $user->entry_type_permissions = $perms;
        $user->save();

        $this->command->info("Granted image-colors entry types to {$email}");
        $this->command->info('  permissions: '.implode(', ', $perms));
        $this->command->info('  api_key: '.$user->api_key);
    }
}
