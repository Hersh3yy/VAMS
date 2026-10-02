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
    public const SLUGS = ['ade-artist', 'ade-event', 'ade-search'];

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
                    ['name' => 'role', 'type' => 'select', 'label' => 'Role', 'required' => false, 'summary' => true, 'options' => ['artist', 'speaker', 'artist and speaker']],
                    // Speakers: job and company. Festival artists: ADE's own tagline, if any.
                    ['name' => 'subtitle', 'type' => 'text', 'label' => 'Job and company', 'required' => false, 'summary' => true],
                    ['name' => 'country', 'type' => 'text', 'label' => 'Country', 'required' => false, 'summary' => true],
                    // ADE event entries this artist plays; the reverse of ade-event.lineup.
                    ['name' => 'events', 'type' => 'entry_relation', 'label' => 'Events', 'required' => false, 'entry_type_slug' => 'ade-event', 'min' => 0],
                    ['name' => 'spotifyId', 'type' => 'text', 'label' => 'Spotify artist id', 'required' => false],
                    ['name' => 'adeUrl', 'type' => 'url', 'label' => 'ADE page', 'required' => true],
                    ['name' => 'externalId', 'type' => 'text', 'label' => 'ADE id', 'required' => true],
                    ['name' => 'hits', 'type' => 'number', 'label' => 'Found in ADE Planner searches', 'required' => false, 'summary' => true],
                    ['name' => 'searches', 'type' => 'number', 'label' => 'Searched by name in ADE Planner', 'required' => false, 'summary' => true],
                    ['name' => 'syncedAt', 'type' => 'datetime', 'label' => 'Synced at', 'required' => false],
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
                    ['name' => 'program', 'type' => 'select', 'label' => 'Program', 'required' => false, 'options' => ['festival', 'pro']],
                    // Derived by AdeEventClassifier on every sync.
                    ['name' => 'intent', 'type' => 'select', 'label' => 'Intent', 'required' => false, 'summary' => true, 'options' => ['party', 'learn', 'meet', 'listen', 'recharge', 'other']],
                    ['name' => 'format', 'type' => 'select', 'label' => 'Format', 'required' => false, 'options' => ['session', 'drop-in', 'tba']],
                    ['name' => 'durationMinutes', 'type' => 'number', 'label' => 'Duration (minutes)', 'required' => false],
                    ['name' => 'timeOfDay', 'type' => 'select', 'label' => 'Time of day', 'required' => false, 'options' => ['morning', 'afternoon', 'evening', 'night', 'all-day', 'tba']],
                    ['name' => 'isParty', 'type' => 'checkbox', 'label' => 'Party or concert', 'required' => false],
                    ['name' => 'access', 'type' => 'select', 'label' => 'Access', 'required' => false, 'options' => ['free', 'ticket', 'pro']],
                    ['name' => 'kinds', 'type' => 'json', 'label' => 'Kinds', 'required' => false],
                    ['name' => 'series', 'type' => 'text', 'label' => 'Series key (same event on several days)', 'required' => false],
                    ['name' => 'seriesDates', 'type' => 'json', 'label' => 'Series dates', 'required' => false],
                    ['name' => 'venue', 'type' => 'text', 'label' => 'Venue', 'required' => false, 'summary' => true],
                    ['name' => 'startsAt', 'type' => 'datetime', 'label' => 'Starts', 'required' => true, 'summary' => true],
                    ['name' => 'endsAt', 'type' => 'datetime', 'label' => 'Ends', 'required' => false],
                    // available: ADE lists it under "tickets available"; free: a free event; pro pass: ADE Pro conference; unknown: none of these.
                    ['name' => 'ticketStatus', 'type' => 'select', 'label' => 'Tickets', 'required' => false, 'summary' => true, 'options' => ['available', 'sold out', 'free', 'pro pass', 'unknown']],
                    ['name' => 'ticketUrl', 'type' => 'url', 'label' => 'Ticket shop', 'required' => false],
                    ['name' => 'ticketLabel', 'type' => 'text', 'label' => 'Ticket button text', 'required' => false],
                    // ADE artist entries on this event, from their artist pages (ADE lists every act it links).
                    ['name' => 'lineup', 'type' => 'entry_relation', 'label' => 'Lineup', 'required' => false, 'entry_type_slug' => 'ade-artist', 'min' => 0],
                    ['name' => 'genres', 'type' => 'json', 'label' => 'Genres', 'required' => false],
                    ['name' => 'eventTypes', 'type' => 'json', 'label' => 'Event types', 'required' => false],
                    ['name' => 'area', 'type' => 'text', 'label' => 'Area', 'required' => false],
                    ['name' => 'address', 'type' => 'text', 'label' => 'Address', 'required' => false],
                    ['name' => 'tags', 'type' => 'json', 'label' => 'Other tags', 'required' => false],
                    ['name' => 'soldOut', 'type' => 'checkbox', 'label' => 'Sold out', 'required' => false],
                    ['name' => 'subtitle', 'type' => 'text', 'label' => 'Subtitle', 'required' => false],
                    ['name' => 'categories', 'type' => 'text', 'label' => 'ADE categories (raw)', 'required' => false],
                    ['name' => 'adeUrl', 'type' => 'url', 'label' => 'ADE page', 'required' => true],
                    ['name' => 'externalId', 'type' => 'text', 'label' => 'ADE id', 'required' => true],
                    ['name' => 'favorites', 'type' => 'number', 'label' => 'Starred in ADE Planner', 'required' => false, 'summary' => true],
                    ['name' => 'syncedAt', 'type' => 'datetime', 'label' => 'Synced at', 'required' => false],
                ],
            ]
        );
        $this->command->info('Entry type ready: ade-event');

        // Written by ADE Planner (AdePlannerStatsController), one per search. Anonymous:
        // what was searched and what matched, never who (no IP, no browser details).
        // Stored as draft, so the read API never serves them.
        EntryType::updateOrCreate(
            ['slug' => 'ade-search'],
            [
                'name' => 'ADE Search',
                'description' => 'One ADE Planner search: a playlist link, typed artist names or a daytime query, and what it found. Anonymous. Filled by hiren.ninja.',
                'is_active' => true,
                'field_config' => [
                    ['name' => 'kind', 'type' => 'select', 'label' => 'Kind', 'required' => true, 'summary' => true, 'options' => ['playlist', 'names', 'daytime']],
                    ['name' => 'source', 'type' => 'select', 'label' => 'Source', 'required' => false, 'summary' => true, 'options' => ['spotify', 'apple-music', 'youtube-music', 'names', 'daytime']],
                    ['name' => 'searchedAt', 'type' => 'datetime', 'label' => 'Searched at', 'required' => true, 'summary' => true],
                    ['name' => 'playlistUrl', 'type' => 'url', 'label' => 'Playlist link', 'required' => false],
                    ['name' => 'playlistTitle', 'type' => 'text', 'label' => 'Playlist title', 'required' => false],
                    ['name' => 'trackCount', 'type' => 'number', 'label' => 'Tracks read', 'required' => false],
                    ['name' => 'partial', 'type' => 'checkbox', 'label' => 'Playlist only partly read', 'required' => false],
                    ['name' => 'query', 'type' => 'textarea', 'label' => 'Typed names or daytime query', 'required' => false],
                    ['name' => 'artistCount', 'type' => 'number', 'label' => 'Artists searched', 'required' => false, 'summary' => true],
                    ['name' => 'matchedCount', 'type' => 'number', 'label' => 'Artists on ADE', 'required' => false, 'summary' => true],
                    ['name' => 'matchedArtists', 'type' => 'entry_relation', 'label' => 'Matched artists', 'required' => false, 'entry_type_slug' => 'ade-artist', 'min' => 0],
                    // Wanted but not playing ADE: who the crowd would come for.
                    ['name' => 'unmatched', 'type' => 'json', 'label' => 'Not on the lineup', 'required' => false],
                    ['name' => 'resultCount', 'type' => 'number', 'label' => 'Daytime results', 'required' => false],
                    ['name' => 'example', 'type' => 'checkbox', 'label' => '"Try an example" search', 'required' => false],
                ],
            ]
        );
        $this->command->info('Entry type ready: ade-search');

        $user = User::where('email', self::OWNER_EMAIL)->firstOrFail();
        $user->entry_type_permissions = array_values(array_unique(array_merge(
            $user->entry_type_permissions ?? [],
            self::SLUGS
        )));
        $user->save();

        $this->command->info('Granted '.implode(', ', self::SLUGS).' to '.self::OWNER_EMAIL);
    }
}
