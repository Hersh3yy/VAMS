<?php

declare(strict_types=1);

use App\Models\Album;
use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('spaces');

    $this->user = User::factory()->create([
        'is_approved' => true,
        'email_verified_at' => now(),
    ]);

    $this->otherUser = User::factory()->create([
        'is_approved' => true,
        'email_verified_at' => now(),
    ]);
});

it('allows authenticated user to view mosaics index', function () {
    $this->actingAs($this->user);

    // Create some mosaics for this user
    Mosaic::factory()->count(3)->forUser($this->user)->create();
    // Create mosaics for other users (should not appear)
    Mosaic::factory()->count(2)->forUser($this->otherUser)->create();

    $response = $this->get(route('mosaics.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Mosaics/Index')
        ->has('mosaics', 3) // Only user's mosaics
    );
});

it('prevents guest from viewing mosaics index', function () {
    $response = $this->get(route('mosaics.index'));
    $response->assertRedirect('/login');
});

it('allows authenticated user to view create mosaic page', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('mosaics.create'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Mosaics/Create')
    );
});

it('allows authenticated user to create mosaic', function () {
    $this->actingAs($this->user);

    $mosaicData = [
        'title' => 'Test Mosaic',
        'description' => 'This is a test mosaic description.',
        'columns' => 3,
        'display_settings' => [
            'show_titles' => true,
            'show_captions' => false,
        ],
    ];

    $response = $this->post(route('mosaics.store'), $mosaicData);

    $response->assertRedirect();

    $this->assertDatabaseHas('mosaics', [
        'title' => 'Test Mosaic',
        'description' => 'This is a test mosaic description.',
        'columns' => 3,
        'user_id' => $this->user->id,
    ]);
});

it('validates required fields for mosaic creation', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('mosaics.store'), []);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['title', 'columns']);
});

it('validates columns range for mosaic creation', function () {
    $this->actingAs($this->user);

    // Test minimum columns
    $response = $this->post(route('mosaics.store'), [
        'title' => 'Test Mosaic',
        'columns' => 1, // Too few
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['columns']);

    // Test maximum columns
    $response = $this->post(route('mosaics.store'), [
        'title' => 'Test Mosaic',
        'columns' => 6, // Too many
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['columns']);
});

it('allows authenticated user to view mosaic', function () {
    $this->actingAs($this->user);

    $mosaic = Mosaic::factory()->forUser($this->user)->create();

    // Add some items to the mosaic
    MosaicItem::factory()->count(3)->forMosaic($mosaic)->create();

    $response = $this->get(route('mosaics.show', $mosaic));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Mosaics/Show')
        ->has('mosaic')
        ->where('mosaic.id', $mosaic->id)
        ->where('mosaic.title', $mosaic->title)
        ->has('mosaic.items', 3)
    );
});

it('prevents user from viewing other users mosaics', function () {
    $this->actingAs($this->user);

    $otherMosaic = Mosaic::factory()->forUser($this->otherUser)->create();

    $response = $this->get(route('mosaics.show', $otherMosaic));

    $response->assertForbidden();
});

it('allows authenticated user to view mosaic show page for editing', function () {
    $this->actingAs($this->user);

    $mosaic = Mosaic::factory()->forUser($this->user)->create();

    $response = $this->get(route('mosaics.show', $mosaic));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Mosaics/Show')
        ->has('mosaic')
        ->where('mosaic.id', $mosaic->id)
    );
});

it('allows authenticated user to update mosaic', function () {
    $this->actingAs($this->user);

    $mosaic = Mosaic::factory()->forUser($this->user)->create([
        'title' => 'Original Title',
        'description' => 'Original Description',
        'columns' => 2,
    ]);

    $updateData = [
        'title' => 'Updated Title',
        'description' => 'Updated Description',
        'columns' => 4,
        'items' => [
            [
                'type' => 'album',
                'column_index' => 0,
                'order' => 0,
                'properties' => [],
            ],
        ],
    ];

    $response = $this->patch(route('mosaics.update', $mosaic), $updateData);

    $response->assertRedirect(route('mosaics.show', $mosaic));

    $this->assertDatabaseHas('mosaics', [
        'id' => $mosaic->id,
        'title' => 'Updated Title',
        'description' => 'Updated Description',
        'columns' => 4,
    ]);
});

it('prevents user from updating other users mosaics', function () {
    $this->actingAs($this->user);

    $otherMosaic = Mosaic::factory()->forUser($this->otherUser)->create();

    $updateData = [
        'title' => 'Hacked Title',
        'columns' => 3,
    ];

    $response = $this->patch(route('mosaics.update', $otherMosaic), $updateData);

    $response->assertForbidden();

    $this->assertDatabaseMissing('mosaics', [
        'id' => $otherMosaic->id,
        'title' => 'Hacked Title',
    ]);
});

it('allows authenticated user to delete mosaic', function () {
    $this->actingAs($this->user);

    $mosaic = Mosaic::factory()->forUser($this->user)->create();

    $response = $this->delete(route('mosaics.destroy', $mosaic));

    $response->assertRedirect(route('mosaics.index'));

    $this->assertDatabaseMissing('mosaics', [
        'id' => $mosaic->id,
    ]);
});

it('prevents user from deleting other users mosaics', function () {
    $this->actingAs($this->user);

    $otherMosaic = Mosaic::factory()->forUser($this->otherUser)->create();

    $response = $this->delete(route('mosaics.destroy', $otherMosaic));

    $response->assertForbidden();

    $this->assertDatabaseHas('mosaics', [
        'id' => $otherMosaic->id,
    ]);
});

it('allows user to add items to mosaic', function () {
    $this->actingAs($this->user);

    $mosaic = Mosaic::factory()->forUser($this->user)->create();
    $album = Album::factory()->forUser($this->user)->create();

    $itemData = [
        'type' => 'album',
        'properties' => ['album_id' => $album->id],
        'order' => 1,
        'column_index' => 0,
    ];

    $response = $this->post(route('mosaics.items.store', $mosaic), $itemData);

    $response->assertRedirect();

    $this->assertDatabaseHas('mosaic_items', [
        'mosaic_id' => $mosaic->id,
        'type' => 'album',
        'order' => 1,
        'column_index' => 0,
    ]);
});

it('allows user to reorder mosaic items', function () {
    $this->actingAs($this->user);

    $mosaic = Mosaic::factory()->forUser($this->user)->create();
    $album1 = Album::factory()->forUser($this->user)->create();
    $album2 = Album::factory()->forUser($this->user)->create();

    $item1 = MosaicItem::factory()->forMosaic($mosaic)->create([
        'type' => 'album',
        'properties' => ['album_id' => $album1->id],
        'order' => 1,
        'column_index' => 0,
    ]);
    $item2 = MosaicItem::factory()->forMosaic($mosaic)->create([
        'type' => 'album',
        'properties' => ['album_id' => $album2->id],
        'order' => 2,
        'column_index' => 0,
    ]);

    $reorderData = [
        'items' => [
            ['id' => $item2->id, 'order' => 1, 'column_index' => 0],
            ['id' => $item1->id, 'order' => 2, 'column_index' => 0],
        ],
    ];

    $response = $this->patch(route('mosaics.items.reorder', $mosaic), $reorderData);

    $response->assertRedirect();

    $this->assertDatabaseHas('mosaic_items', [
        'id' => $item1->id,
        'order' => 2,
    ]);

    $this->assertDatabaseHas('mosaic_items', [
        'id' => $item2->id,
        'order' => 1,
    ]);
});

it('validates mosaic item data', function () {
    $this->actingAs($this->user);

    $mosaic = Mosaic::factory()->forUser($this->user)->create();

    $response = $this->post(route('mosaics.items.store', $mosaic), []);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['type', 'properties', 'order', 'column_index']);
});

it('prevents adding items to other users mosaics', function () {
    $this->actingAs($this->user);

    $otherMosaic = Mosaic::factory()->forUser($this->otherUser)->create();
    $album = Album::factory()->forUser($this->user)->create();

    $itemData = [
        'album_id' => $album->id,
        'position' => 1,
    ];

    $response = $this->post(route('mosaics.items.store', $otherMosaic), $itemData);

    $response->assertForbidden();
});
