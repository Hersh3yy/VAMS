<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Support\Str;

function adeStatsType(string $slug): EntryType
{
    return EntryType::create(['name' => $slug, 'slug' => $slug, 'field_config' => [], 'is_active' => true]);
}

function adeStatsEntry(User $user, EntryType $type, array $content = []): Entry
{
    return Entry::create([
        'id' => (string) Str::uuid(), 'user_id' => $user->id, 'entry_type_id' => $type->id,
        'title' => 'X', 'content' => $content, 'status' => 'published', 'published_at' => now(), 'order' => 0,
    ]);
}

beforeEach(function (): void {
    $this->user = User::factory()->create(['api_key' => 'key-owner', 'is_approved' => true]);
    $this->artistType = adeStatsType('ade-artist');
    $this->eventType = adeStatsType('ade-event');
    $this->searchType = adeStatsType('ade-search');
});

it('counts artist hits and event favorites, never below zero', function (): void {
    $artist = adeStatsEntry($this->user, $this->artistType, ['hits' => 2]);
    $event = adeStatsEntry($this->user, $this->eventType);

    $this->postJson('/api/ade-planner/stats', [
        'hits' => [$artist->id, $artist->id],
        'favorites' => [['id' => $event->id, 'delta' => 1], ['id' => $event->id, 'delta' => -1], ['id' => $event->id, 'delta' => -1]],
    ], ['X-API-Key' => 'key-owner'])->assertOk()->assertJsonPath('data.hits', 1);

    expect($artist->fresh()->content['hits'])->toBe(3)
        ->and($event->fresh()->content['favorites'])->toBe(0);

    $this->postJson('/api/ade-planner/stats', ['favorites' => [['id' => $event->id, 'delta' => 1]]], ['X-API-Key' => 'key-owner'])->assertOk();
    expect($event->fresh()->content['favorites'])->toBe(1);
});

it('counts artists typed by name separately from all finds', function (): void {
    $artist = adeStatsEntry($this->user, $this->artistType, ['hits' => 5]);

    $this->postJson('/api/ade-planner/stats', ['hits' => [$artist->id], 'searched' => [$artist->id]], ['X-API-Key' => 'key-owner'])
        ->assertOk()->assertJsonPath('data.searched', 1);

    expect($artist->fresh()->content)->toMatchArray(['hits' => 6, 'searches' => 1]);
});

it('only touches the key owner\'s ADE entries', function (): void {
    $other = User::factory()->create();
    $foreign = adeStatsEntry($other, $this->eventType);
    $wrongType = adeStatsEntry($this->user, $this->artistType);

    $this->postJson('/api/ade-planner/stats', [
        'favorites' => [['id' => $foreign->id, 'delta' => 1], ['id' => $wrongType->id, 'delta' => 1]],
    ], ['X-API-Key' => 'key-owner'])->assertOk()->assertJsonPath('data.favorites', 0);

    expect($foreign->fresh()->content)->not->toHaveKey('favorites');
});

it('needs an API key and a valid body', function (): void {
    $this->postJson('/api/ade-planner/stats', ['hits' => []])->assertUnauthorized();
    $this->postJson('/api/ade-planner/stats', ['favorites' => [['id' => 'nope', 'delta' => 5]]], ['X-API-Key' => 'key-owner'])->assertUnprocessable();
});

it('logs a search as a draft entry, without who searched', function (): void {
    $artist = adeStatsEntry($this->user, $this->artistType);

    $response = $this->postJson('/api/ade-planner/stats', [
        'hits' => [$artist->id],
        'search' => [
            'kind' => 'playlist', 'source' => 'spotify', 'playlistUrl' => 'https://open.spotify.com/playlist/abc',
            'playlistTitle' => 'Techno bangers', 'trackCount' => 120, 'artistCount' => 80,
            'matchedArtists' => [$artist->id], 'unmatched' => ['Nobody'],
        ],
    ], ['X-API-Key' => 'key-owner'])->assertOk();

    $search = Entry::find($response->json('data.search'));
    expect($search->title)->toBe('Techno bangers')
        ->and($search->status)->toBe('draft')
        ->and($search->entry_type_id)->toBe($this->searchType->id)
        ->and($search->content)->toMatchArray(['kind' => 'playlist', 'matchedCount' => 1, 'unmatched' => ['Nobody'], 'trackCount' => 120])
        ->and($search->content)->toHaveKey('searchedAt')
        ->and($search->content)->not->toHaveKeys(['ip', 'userAgent']);
});

it('titles typed-name searches by their first names', function (): void {
    $response = $this->postJson('/api/ade-planner/stats', [
        'search' => ['kind' => 'names', 'source' => 'names', 'query' => "Amelie Lens\nNTO, Adam Beyer\nKerri Chandler\nPaul Kalkbrenner", 'example' => false],
    ], ['X-API-Key' => 'key-owner'])->assertOk();

    expect(Entry::find($response->json('data.search'))->title)->toBe('Amelie Lens, NTO, Adam Beyer, Kerri Chandler');
});
