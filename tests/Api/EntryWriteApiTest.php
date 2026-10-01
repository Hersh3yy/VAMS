<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function presetType(): EntryType
{
    return EntryType::query()->create([
        'name' => 'Preset',
        'slug' => 'preset',
        'description' => 'A named set of analysed images',
        'is_active' => true,
        'field_config' => [
            ['name' => 'description', 'type' => 'textarea', 'label' => 'Description', 'required' => false],
            ['name' => 'strapi_id', 'type' => 'text', 'label' => 'Strapi ID', 'required' => false],
        ],
    ]);
}

function processedImageType(): EntryType
{
    return EntryType::query()->create([
        'name' => 'Processed Image',
        'slug' => 'processed-image',
        'is_active' => true,
        'field_config' => [
            ['name' => 'preset', 'type' => 'entry_relation', 'label' => 'Preset', 'required' => true, 'entry_type_slug' => 'preset', 'min' => 1, 'max' => 1],
            ['name' => 'source_image_url', 'type' => 'text', 'label' => 'Source image URL', 'required' => true],
            ['name' => 'colors', 'type' => 'json', 'label' => 'Palette', 'required' => true],
            ['name' => 'analysis_settings', 'type' => 'json', 'label' => 'Analysis settings', 'required' => true],
        ],
    ]);
}

it('creates a preset via API key', function (): void {
    $user = User::factory()->create(['entry_type_permissions' => ['preset']]);
    $type = presetType();

    $response = $this->withHeaders(['X-API-Key' => $user->api_key])
        ->postJson('/api/entries', [
            'entry_type_id' => $type->id,
            'title' => 'Van Gogh Paintings',
            'content' => ['description' => 'A set', 'strapi_id' => '999'],
        ]);

    $response->assertCreated()->assertJsonPath('success', true);
    expect(Entry::where('user_id', $user->id)->where('entry_type_id', $type->id)->where('title', 'Van Gogh Paintings')->exists())->toBeTrue();
});

it('creates a processed-image referencing its preset', function (): void {
    $user = User::factory()->create(['entry_type_permissions' => ['preset', 'processed-image']]);
    $preset = Entry::query()->create([
        'user_id' => $user->id, 'entry_type_id' => presetType()->id,
        'title' => 'Set', 'content' => ['strapi_id' => '1'], 'status' => 'published', 'published_at' => now(), 'order' => 0,
    ]);
    $imgType = processedImageType();

    $response = $this->withHeaders(['X-API-Key' => $user->api_key])
        ->postJson('/api/entries', [
            'entry_type_id' => $imgType->id,
            'title' => 'painting.jpg',
            'content' => [
                'preset' => [$preset->id],
                'source_image_url' => 'https://bengijzel.ams3.digitaloceanspaces.com/x.jpg',
                'colors' => [['color' => '#fff', 'percentage' => 50]],
                'analysis_settings' => ['k' => 13, 'colorSpace' => 'lab'],
            ],
        ]);

    $response->assertCreated();
    $img = Entry::where('entry_type_id', $imgType->id)->first();
    expect($img->content['preset'])->toBe([$preset->id]);
});

it('rejects an entry type the key lacks permission for', function (): void {
    $user = User::factory()->create(['entry_type_permissions' => ['preset']]);
    $imgType = processedImageType();

    $this->withHeaders(['X-API-Key' => $user->api_key])
        ->postJson('/api/entries', ['entry_type_id' => $imgType->id, 'title' => 'x', 'content' => ['a' => 1]])
        ->assertForbidden();
});

it('rejects content that fails field_config validation', function (): void {
    $user = User::factory()->create(['entry_type_permissions' => ['processed-image']]);
    $imgType = processedImageType();

    // Missing required source_image_url / colors / analysis_settings.
    $this->withHeaders(['X-API-Key' => $user->api_key])
        ->postJson('/api/entries', [
            'entry_type_id' => $imgType->id, 'title' => 'x', 'content' => ['preset' => []],
        ])
        ->assertStatus(422);
});

it('updates and deletes only the owner\'s entry', function (): void {
    $owner = User::factory()->create(['entry_type_permissions' => ['preset']]);
    $other = User::factory()->create(['entry_type_permissions' => ['preset']]);
    $type = presetType();
    $entry = Entry::query()->create([
        'user_id' => $owner->id, 'entry_type_id' => $type->id,
        'title' => 'Old', 'content' => ['strapi_id' => '1'], 'status' => 'published', 'published_at' => now(), 'order' => 0,
    ]);

    // Another key cannot see it.
    $this->withHeaders(['X-API-Key' => $other->api_key])
        ->putJson("/api/entries/{$entry->id}", ['title' => 'Hacked'])
        ->assertNotFound();

    // Owner updates.
    $this->withHeaders(['X-API-Key' => $owner->api_key])
        ->putJson("/api/entries/{$entry->id}", ['title' => 'New'])
        ->assertSuccessful();
    expect($entry->fresh()->title)->toBe('New');

    // Owner deletes.
    $this->withHeaders(['X-API-Key' => $owner->api_key])
        ->deleteJson("/api/entries/{$entry->id}")
        ->assertSuccessful();
    expect(Entry::find($entry->id))->toBeNull();
});
