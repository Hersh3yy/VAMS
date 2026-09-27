<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EntryType;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Entry types for ADE Planner (koala/hiren-ninja, /ade-planner).
 *
 * A copy of the Amsterdam Dance Event program, filled by `php artisan ade:sync`,
 * so hiren.ninja never calls the ADE site at request time. Only facts are
 * stored (names, ids, times, venues, links), no ADE photos or bios.
 *
 * Idempotent: matches entry types by slug and the user by email.
 *
 *   docker compose exec api php artisan db:seed --class=AdePlannerSeeder
 */
class AdePlannerSeeder extends Seeder
{
    public const SLUGS = ['ade-artist', 'ade-event'];

    public const OWNER_EMAIL = 'info@hiren.ninja';

    public function run(): void
    {
        EntryType::updateOrCreate(
            ['slug' => 'ade-artist'],
            [
                'name' => 'ADE Artist',
                'description' => 'An artist on the Amsterdam Dance Event lineup. The entry title is the artist name. Filled by ade:sync.',
                'is_active' => true,
                'field_config' => [
                    ['name' => 'externalId', 'type' => 'text', 'label' => 'ADE id', 'required' => true],
                    ['name' => 'country', 'type' => 'text', 'label' => 'Country code', 'required' => false],
                    ['name' => 'spotifyId', 'type' => 'text', 'label' => 'Spotify artist id', 'required' => false],
                    ['name' => 'adeUrl', 'type' => 'text', 'label' => 'ADE page', 'required' => true],
                    // ADE event ids (strings), joined to ade-event.externalId
                    ['name' => 'eventIds', 'type' => 'json', 'label' => 'Event ids', 'required' => false],
                    ['name' => 'syncedAt', 'type' => 'text', 'label' => 'Synced at', 'required' => false],
                ],
            ]
        );
        $this->command->info('Entry type ready: ade-artist');

        EntryType::updateOrCreate(
            ['slug' => 'ade-event'],
            [
                'name' => 'ADE Event',
                'description' => 'An Amsterdam Dance Event festival event. The entry title is the event title. Filled by ade:sync.',
                'is_active' => true,
                'field_config' => [
                    ['name' => 'externalId', 'type' => 'text', 'label' => 'ADE id', 'required' => true],
                    ['name' => 'subtitle', 'type' => 'text', 'label' => 'Subtitle', 'required' => false],
                    ['name' => 'startsAt', 'type' => 'text', 'label' => 'Starts at (ISO 8601)', 'required' => true],
                    ['name' => 'endsAt', 'type' => 'text', 'label' => 'Ends at (ISO 8601)', 'required' => false],
                    ['name' => 'venue', 'type' => 'text', 'label' => 'Venue', 'required' => false],
                    ['name' => 'categories', 'type' => 'text', 'label' => 'Categories', 'required' => false],
                    ['name' => 'soldOut', 'type' => 'json', 'label' => 'Sold out', 'required' => false],
                    ['name' => 'adeUrl', 'type' => 'text', 'label' => 'ADE page', 'required' => true],
                    ['name' => 'syncedAt', 'type' => 'text', 'label' => 'Synced at', 'required' => false],
                ],
            ]
        );
        $this->command->info('Entry type ready: ade-event');

        $user = User::where('email', self::OWNER_EMAIL)->firstOrFail();
        $user->entry_type_permissions = array_values(array_unique(array_merge(
            $user->entry_type_permissions ?? [],
            self::SLUGS
        )));
        $user->save();

        $this->command->info('Granted '.implode(', ', self::SLUGS).' to '.self::OWNER_EMAIL);
    }
}
