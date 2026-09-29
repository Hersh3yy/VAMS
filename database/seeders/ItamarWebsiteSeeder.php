<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EntryType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Entry types for Itamar Gilboa's website (koala/itamargilboa).
 *
 * The site used to read four Strapi collections. Each one becomes an entry type
 * with the same slug, so the site keeps its own vocabulary. The entry title is
 * the Strapi Title, the entry `order` is the Strapi Order, and every type carries
 * a `strapi_id` so `strapi:import-itamar-website` can re-run without duplicates.
 *
 * Images stay where Strapi put them (the bengijzel Spaces bucket, which VAMS
 * also uses), so an image_collection item is just { path, url, alt, ... }.
 *
 * Idempotent: matches entry types by slug and the user by email. Re-running never
 * resets the user's password or api_key.
 *
 *   php artisan db:seed --class=ItamarWebsiteSeeder
 */
class ItamarWebsiteSeeder extends Seeder
{
    /** Entry-type slugs this project owns. */
    public const SLUGS = ['ig-biography', 'news-articles', 'ig-projects', 'ig-landing-page-slideshow-images'];

    private const ALT = [['name' => 'alt', 'type' => 'text', 'label' => 'Alt text', 'required' => false]];

    private const STRAPI_ID = ['name' => 'strapi_id', 'type' => 'text', 'label' => 'Strapi ID (migration)', 'required' => false];

    public function run(): void
    {
        $this->seedType('ig-biography', 'Biography', 'The biography page. One entry.', [
            ['name' => 'text', 'type' => 'textarea', 'label' => 'Text', 'required' => true],
            ['name' => 'content', 'type' => 'textarea', 'label' => 'Artist statement', 'required' => false],
            ['name' => 'image', 'type' => 'image_collection', 'label' => 'Portrait', 'required' => false, 'max' => 1, 'fields' => self::ALT],
        ]);

        $this->seedType('news-articles', 'News Articles', 'News items. The entry title is the headline; order sorts them.', [
            ['name' => 'url', 'type' => 'url', 'label' => 'Link', 'required' => false],
            ['name' => 'images', 'type' => 'image_collection', 'label' => 'Images', 'required' => false, 'allow_reorder' => true, 'fields' => self::ALT],
        ]);

        $this->seedType('ig-projects', 'Projects', 'Artworks and projects. The entry title is the project name; the slug is its URL.', [
            ['name' => 'slug', 'type' => 'text', 'label' => 'Slug', 'required' => true],
            ['name' => 'text', 'type' => 'textarea', 'label' => 'Text', 'required' => false],
            ['name' => 'video_link', 'type' => 'url', 'label' => 'Video link (Vimeo/YouTube)', 'required' => false],
            ['name' => 'images', 'type' => 'image_collection', 'label' => 'Images', 'required' => false, 'allow_reorder' => true, 'fields' => self::ALT],
        ]);

        $this->seedType('ig-landing-page-slideshow-images', 'Landing Slideshow', 'One slide on the homepage slideshow; order sorts them.', [
            ['name' => 'image', 'type' => 'image_collection', 'label' => 'Image', 'required' => true, 'min' => 1, 'max' => 1, 'fields' => self::ALT],
        ]);

        $this->grantToItamar();
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function seedType(string $slug, string $name, string $description, array $fields): void
    {
        EntryType::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => $description,
                'is_active' => true,
                'field_config' => [...$fields, self::STRAPI_ID],
            ]
        );
        $this->command->info("Entry type ready: {$slug}");
    }

    /**
     * Grant the website slugs to Itamar on top of whatever he already has.
     * Creates the account only if missing, with a random password (he resets it).
     */
    private function grantToItamar(): void
    {
        $email = 'itamar@gilboa.net';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Itamar Gilboa',
                'password' => Hash::make(env('ITAMAR_PASSWORD') ?: Str::random(40)),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_approved' => true,
                'approved_at' => now(),
            ]
        );

        $user->entry_type_permissions = array_values(array_unique(array_merge(
            $user->entry_type_permissions ?? [],
            self::SLUGS
        )));
        $user->save();

        $this->command->info("Granted website entry types to {$email}");
        $this->command->info('  permissions: '.implode(', ', $user->entry_type_permissions));
    }
}
