<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->owner = User::factory()->create(['email' => 'info@hiren.ninja']);
    $this->type = EntryType::create(['name' => 'ADE Event', 'slug' => 'ade-event', 'field_config' => [], 'is_active' => true]);
    $this->event = fn (array $content): Entry => Entry::create([
        'id' => (string) Str::uuid(), 'user_id' => $this->owner->id, 'entry_type_id' => $this->type->id, 'title' => 'X',
        'content' => $content, 'status' => 'published', 'published_at' => now(), 'order' => 0,
    ]);
    $this->file = storage_path('framework/testing/ade-topics.json');
    File::ensureDirectoryExists(dirname($this->file));
});

it('imports topics by entry id and by series key, for daytime events only', function (): void {
    $talk = ($this->event)(['isParty' => false]);
    $exhibitionDay1 = ($this->event)(['isParty' => false, 'series' => 'abc']);
    $exhibitionDay2 = ($this->event)(['isParty' => false, 'series' => 'abc']);
    $party = ($this->event)(['isParty' => true]);

    File::put($this->file, json_encode([$talk->id => ['labels-sync', 'tech-ai'], 'abc' => ['art-immersive'], $party->id => ['business']]));
    $this->artisan('ade:topics', ['path' => 'storage/framework/testing/ade-topics.json'])->assertSuccessful();

    expect($talk->fresh()->content['topics'])->toBe(['labels-sync', 'tech-ai'])
        ->and($exhibitionDay1->fresh()->content['topics'])->toBe(['art-immersive'])
        ->and($exhibitionDay2->fresh()->content['topics'])->toBe(['art-immersive'])
        ->and($party->fresh()->content)->not->toHaveKey('topics');
});

it('refuses topics that are not on the list', function (): void {
    $talk = ($this->event)(['isParty' => false]);
    File::put($this->file, json_encode([$talk->id => ['made-up']]));

    $this->artisan('ade:topics', ['path' => 'storage/framework/testing/ade-topics.json'])->assertFailed();
    expect($talk->fresh()->content)->not->toHaveKey('topics');
});
