<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use App\Services\EntryLookupService;

function lookupEntryType(string $slug): EntryType
{
    return EntryType::create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'field_config' => [['name' => 'name', 'type' => 'text', 'label' => 'Name']],
        'is_active' => true,
    ]);
}

function lookupEntry(User $user, EntryType $type, string $title): Entry
{
    return Entry::create([
        'user_id' => $user->id,
        'entry_type_id' => $type->id,
        'title' => $title,
        'content' => ['name' => $title],
        'status' => 'published',
        'published_at' => now(),
        'order' => 0,
    ]);
}

it('requires authentication for lookup and search', function (): void {
    $this->getJson(route('entries.lookup', ['ids' => ['x']]))->assertUnauthorized();
    $this->getJson(route('entries.search', ['type' => 'artist']))->assertUnauthorized();
});

it('resolves ids to titles in the requested order', function (): void {
    $user = User::factory()->create();
    $type = lookupEntryType('artist');
    $a = lookupEntry($user, $type, 'Adam Beyer');
    $b = lookupEntry($user, $type, 'Bicep');

    $this->actingAs($user)
        ->getJson(route('entries.lookup', ['ids' => [$b->id, $a->id, 'does-not-exist']]))
        ->assertOk()
        ->assertExactJson([
            ['id' => $b->id, 'title' => 'Bicep', 'entry_type_slug' => 'artist'],
            ['id' => $a->id, 'title' => 'Adam Beyer', 'entry_type_slug' => 'artist'],
        ]);
});

it('never returns another user\'s entries from lookup', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $type = lookupEntryType('artist');
    $mine = lookupEntry($user, $type, 'Mine');
    $theirs = lookupEntry($other, $type, 'Theirs');

    $this->actingAs($user)
        ->getJson(route('entries.lookup', ['ids' => [$mine->id, $theirs->id]]))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $mine->id)
        ->assertJsonMissing(['title' => 'Theirs']);
});

it('validates the ids parameter', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('entries.lookup'))->assertUnprocessable();
});

it('caps lookups at 500 ids', function (): void {
    $user = User::factory()->create();
    $type = lookupEntryType('artist');

    $ids = collect(range(1, 510))
        ->map(fn (int $i): string => lookupEntry($user, $type, "Artist {$i}")->id)
        ->all();

    $result = app(EntryLookupService::class)->lookup($user, $ids);

    expect($result)->toHaveCount(EntryLookupService::MAX_LOOKUP_IDS)
        ->and($result[0]['id'])->toBe($ids[0])
        ->and($result[499]['id'])->toBe($ids[499]);
});

it('searches titles case-insensitively within the given type', function (): void {
    $user = User::factory()->create();
    $artist = lookupEntryType('artist');
    $venue = lookupEntryType('venue');
    lookupEntry($user, $artist, 'Charlotte de Witte');
    lookupEntry($user, $artist, 'Amelie Lens');
    lookupEntry($user, $venue, 'Witte Zaal');

    $this->actingAs($user)
        ->getJson(route('entries.search', ['type' => 'artist', 'q' => 'WITTE']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.title', 'Charlotte de Witte')
        ->assertJsonPath('0.entry_type_slug', 'artist');
});

it('treats LIKE wildcards in the search text literally', function (): void {
    $user = User::factory()->create();
    $type = lookupEntryType('artist');
    lookupEntry($user, $type, '100% Techno');
    lookupEntry($user, $type, '1000 Techno');

    $this->actingAs($user)
        ->getJson(route('entries.search', ['type' => 'artist', 'q' => '100%']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.title', '100% Techno');
});

it('limits search to 20 results and returns the first entries for an empty query', function (): void {
    $user = User::factory()->create();
    $type = lookupEntryType('artist');
    foreach (range(1, 25) as $i) {
        lookupEntry($user, $type, sprintf('Artist %02d', $i));
    }

    $this->actingAs($user)
        ->getJson(route('entries.search', ['type' => 'artist']))
        ->assertOk()
        ->assertJsonCount(20)
        ->assertJsonPath('0.title', 'Artist 01');
});

it('never returns another user\'s entries from search', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $type = lookupEntryType('artist');
    lookupEntry($other, $type, 'Secret Artist');

    $this->actingAs($user)
        ->getJson(route('entries.search', ['type' => 'artist', 'q' => 'secret']))
        ->assertOk()
        ->assertExactJson([]);
});

it('returns an empty list for an unknown type', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('entries.search', ['type' => 'nope', 'q' => 'x']))
        ->assertOk()
        ->assertExactJson([]);
});
