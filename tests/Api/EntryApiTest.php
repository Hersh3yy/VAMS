<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns only published entries for the user via API', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $type = EntryType::query()->create([
        'name' => 'I AMS',
        'slug' => 'i-ams',
        'description' => 'Affirmations',
        'is_active' => true,
    ]);

    // Create one draft and one published
    Entry::query()->create([
        'user_id' => $user->id,
        'entry_type_id' => $type->id,
        'title' => 'Draft I AM',
        'content' => ['statement' => 'I am draft'],
        'status' => 'draft',
        'published_at' => null,
        'order' => 0,
    ]);

    $published = Entry::query()->create([
        'user_id' => $user->id,
        'entry_type_id' => $type->id,
        'title' => 'Published I AM',
        'content' => ['statement' => 'I am published'],
        'status' => 'published',
        'published_at' => now(),
        'order' => 1,
    ]);

    $response = $this->withHeaders([
        'X-API-Key' => $user->api_key,
    ])->getJson('/api/entries');

    $response->assertSuccessful()
        ->assertJson(fn ($json) => $json
            ->has('success')
            ->has('data.entries', 1)
            ->where('data.entries.0.id', $published->id)
        );
});

it('returns only published entries by type via API', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $type = EntryType::query()->create([
        'name' => 'I AMS',
        'slug' => 'i-ams',
        'description' => 'Affirmations',
        'is_active' => true,
    ]);

    Entry::query()->create([
        'user_id' => $user->id,
        'entry_type_id' => $type->id,
        'title' => 'Draft I AM',
        'content' => ['statement' => 'I am draft'],
        'status' => 'draft',
        'published_at' => null,
        'order' => 0,
    ]);

    $published = Entry::query()->create([
        'user_id' => $user->id,
        'entry_type_id' => $type->id,
        'title' => 'Published I AM',
        'content' => ['statement' => 'I am published'],
        'status' => 'published',
        'published_at' => now(),
        'order' => 1,
    ]);

    $response = $this->withHeaders([
        'X-API-Key' => $user->api_key,
    ])->getJson('/api/entries/by-type/i-ams');

    $response->assertSuccessful()
        ->assertJson(fn ($json) => $json
            ->has('success')
            ->has('data.entries', 1)
            ->where('data.entries.0.id', $published->id)
        );
});
