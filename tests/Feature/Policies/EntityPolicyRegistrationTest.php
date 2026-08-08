<?php

declare(strict_types=1);

use App\Models\Album;
use App\Models\Entry;
use App\Models\EntryType;
use App\Models\Mosaic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('registers policies for Album, Mosaic, and Entry', function () {
    expect(Gate::getPolicyFor(Album::class))->toBeInstanceOf(App\Policies\AlbumPolicy::class)
        ->and(Gate::getPolicyFor(Mosaic::class))->toBeInstanceOf(App\Policies\MosaicPolicy::class)
        ->and(Gate::getPolicyFor(Entry::class))->toBeInstanceOf(App\Policies\EntryPolicy::class);
});

it('denies mosaic update to non-owners via the Gate', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $mosaic = Mosaic::factory()->create(['user_id' => $owner->id]);

    expect(Gate::forUser($owner)->allows('update', $mosaic))->toBeTrue()
        ->and(Gate::forUser($stranger)->allows('update', $mosaic))->toBeFalse();
});

it('denies entry update to non-owners via the Gate', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'description' => 'Affirmations',
        'field_config' => [],
        'is_active' => true,
    ]);

    $entry = Entry::create([
        'user_id' => $owner->id,
        'entry_type_id' => $entryType->id,
        'title' => 'Test Entry',
        'content' => ['statement' => 'I am testing'],
        'status' => 'published',
        'published_at' => now(),
        'order' => 0,
    ]);

    expect(Gate::forUser($owner)->allows('update', $entry))->toBeTrue()
        ->and(Gate::forUser($stranger)->allows('update', $entry))->toBeFalse();
});
