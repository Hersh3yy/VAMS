<?php

declare(strict_types=1);

use App\Models\Album;
use App\Models\AlbumImage;
use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('user can create a mosaic with columns and items', function () {
    // Create an album with images for testing
    $album = Album::factory()->create(['user_id' => $this->user->id]);
    $image1 = AlbumImage::factory()->create([
        'album_id' => $album->id,
        'path' => 'test-image-1.jpg',
        'title' => 'Test Image 1'
    ]);
    $image2 = AlbumImage::factory()->create([
        'album_id' => $album->id,
        'path' => 'test-image-2.jpg',
        'title' => 'Test Image 2'
    ]);

    // Visit the mosaics index page
    $response = $this->get(route('mosaics.index'));
    $response->assertStatus(200);

    // Create a new mosaic
    $mosaicData = [
        'title' => 'Test Mosaic',
        'description' => 'A test mosaic with multiple items',
        'columns' => 3
    ];

    $response = $this->post(route('mosaics.store'), $mosaicData);
    $response->assertRedirect();
    
    // Verify mosaic was created
    $this->assertDatabaseHas('mosaics', [
        'title' => 'Test Mosaic',
        'description' => 'A test mosaic with multiple items',
        'columns' => 3,
        'user_id' => $this->user->id
    ]);

    $mosaic = Mosaic::where('title', 'Test Mosaic')->first();

    // Add items to the mosaic
    $item1Data = [
        'type' => 'media',
        'properties' => [
            'media_url' => 'test-image-1.jpg',
            'title' => 'Test Image 1',
            'caption' => 'First test image'
        ],
        'order' => 0,
        'column_index' => 0
    ];

    $item2Data = [
        'type' => 'media',
        'properties' => [
            'media_url' => 'test-image-2.jpg',
            'title' => 'Test Image 2',
            'caption' => 'Second test image'
        ],
        'order' => 1,
        'column_index' => 1
    ];

    // Add first item
    $response = $this->post(route('mosaics.items.store', $mosaic->id), $item1Data);
    $response->assertRedirect();

    // Add second item
    $response = $this->post(route('mosaics.items.store', $mosaic->id), $item2Data);
    $response->assertRedirect();

    // Verify items were created
    $this->assertDatabaseHas('mosaic_items', [
        'mosaic_id' => $mosaic->id,
        'type' => 'media',
        'order' => 0,
        'column_index' => 0
    ]);

    $this->assertDatabaseHas('mosaic_items', [
        'mosaic_id' => $mosaic->id,
        'type' => 'media',
        'order' => 1,
        'column_index' => 1
    ]);

    // Verify we can visit the mosaic edit page
    $response = $this->get(route('mosaics.show', $mosaic->id));
    $response->assertStatus(200);
    $response->assertSee('Test Mosaic');
});

test('user can edit a mosaic and its items', function () {
    // Create a mosaic with items
    $mosaic = Mosaic::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Original Title',
        'description' => 'Original Description',
        'columns' => 2
    ]);

    $item1 = MosaicItem::factory()->create([
        'mosaic_id' => $mosaic->id,
        'type' => 'media',
        'properties' => [
            'media_url' => 'original-image-1.jpg',
            'title' => 'Original Title 1'
        ],
        'order' => 0,
        'column_index' => 0
    ]);

    $item2 = MosaicItem::factory()->create([
        'mosaic_id' => $mosaic->id,
        'type' => 'media',
        'properties' => [
            'media_url' => 'original-image-2.jpg',
            'title' => 'Original Title 2'
        ],
        'order' => 1,
        'column_index' => 1
    ]);

    // Visit the mosaic edit page
    $response = $this->get(route('mosaics.show', $mosaic->id));
    $response->assertStatus(200);
    $response->assertSee('Original Title');

    // Update the mosaic basic info
    $updateData = [
        'title' => 'Updated Title',
        'description' => 'Updated Description',
        'columns' => 3,
        'items' => [
            [
                'id' => $item1->id,
                'type' => 'media',
                'properties' => [
                    'media_url' => 'updated-image-1.jpg',
                    'title' => 'Updated Title 1',
                    'caption' => 'Updated caption 1'
                ],
                'order' => 0,
                'column_index' => 0
            ],
            [
                'id' => $item2->id,
                'type' => 'media',
                'properties' => [
                    'media_url' => 'updated-image-2.jpg',
                    'title' => 'Updated Title 2',
                    'caption' => 'Updated caption 2'
                ],
                'order' => 1,
                'column_index' => 1
            ]
        ]
    ];

    $response = $this->patch(route('mosaics.update', $mosaic->id), $updateData);
    $response->assertRedirect();

    // Verify mosaic was updated
    $mosaic->refresh();
    $this->assertEquals('Updated Title', $mosaic->title);
    $this->assertEquals('Updated Description', $mosaic->description);
    $this->assertEquals(3, $mosaic->columns);

    // Verify items were updated (items are recreated, so check by querying)
    $updatedItems = $mosaic->items()->get();
    $this->assertCount(2, $updatedItems);
    
    // Check that items have the correct type and basic structure
    $this->assertEquals('media', $updatedItems->first()->type);
    $this->assertEquals('media', $updatedItems->last()->type);
    
    // Verify that properties contain the expected data
    $firstItemProperties = $updatedItems->first()->properties;
    $this->assertArrayHasKey('title', $firstItemProperties);
    $this->assertArrayHasKey('caption', $firstItemProperties);
});

test('user can add new items to existing mosaic', function () {
    // Create a mosaic
    $mosaic = Mosaic::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Test Mosaic'
    ]);

    // Create an existing item
    $existingItem = MosaicItem::factory()->create([
        'mosaic_id' => $mosaic->id,
        'type' => 'media',
        'properties' => ['title' => 'Existing Item'],
        'order' => 0,
        'column_index' => 0
    ]);

    // Add a new item
    $newItemData = [
        'type' => 'media',
        'properties' => [
            'media_url' => 'new-image.jpg',
            'title' => 'New Item',
            'caption' => 'A newly added item'
        ],
        'order' => 1,
        'column_index' => 1
    ];

    $response = $this->post(route('mosaics.items.store', $mosaic->id), $newItemData);
    $response->assertRedirect();

    // Verify new item was created
    $this->assertDatabaseHas('mosaic_items', [
        'mosaic_id' => $mosaic->id,
        'type' => 'media',
        'order' => 1,
        'column_index' => 1
    ]);

    // Verify existing item is still there
    $this->assertDatabaseHas('mosaic_items', [
        'id' => $existingItem->id,
        'mosaic_id' => $mosaic->id
    ]);
});

test('user can delete items from mosaic', function () {
    // Create a mosaic with items
    $mosaic = Mosaic::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Test Mosaic'
    ]);

    $item1 = MosaicItem::factory()->create([
        'mosaic_id' => $mosaic->id,
        'type' => 'media',
        'order' => 0,
        'column_index' => 0
    ]);

    $item2 = MosaicItem::factory()->create([
        'mosaic_id' => $mosaic->id,
        'type' => 'media',
        'order' => 1,
        'column_index' => 1
    ]);

    // Delete the first item
    $response = $this->delete(route('mosaics.items.destroy', [$mosaic->id, $item1->id]));
    $response->assertRedirect();

    // Verify item was deleted
    $this->assertDatabaseMissing('mosaic_items', [
        'id' => $item1->id
    ]);

    // Verify second item still exists
    $this->assertDatabaseHas('mosaic_items', [
        'id' => $item2->id
    ]);
});

test('user can reorder mosaic items', function () {
    // Create a mosaic with items
    $mosaic = Mosaic::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Test Mosaic'
    ]);

    $item1 = MosaicItem::factory()->create([
        'mosaic_id' => $mosaic->id,
        'type' => 'media',
        'order' => 0,
        'column_index' => 0
    ]);

    $item2 = MosaicItem::factory()->create([
        'mosaic_id' => $mosaic->id,
        'type' => 'media',
        'order' => 1,
        'column_index' => 1
    ]);

    // Reorder items
    $reorderData = [
        'items' => [
            [
                'id' => $item2->id,
                'order' => 0,
                'column_index' => 0
            ],
            [
                'id' => $item1->id,
                'order' => 1,
                'column_index' => 1
            ]
        ]
    ];

    $response = $this->patch(route('mosaics.items.reorder', $mosaic->id), $reorderData);
    $response->assertRedirect();

    // Verify items were reordered
    $item1->refresh();
    $item2->refresh();

    $this->assertEquals(1, $item1->order);
    $this->assertEquals(1, $item1->column_index);
    $this->assertEquals(0, $item2->order);
    $this->assertEquals(0, $item2->column_index);
});

test('user cannot access other users mosaics', function () {
    $otherUser = User::factory()->create();
    $otherMosaic = Mosaic::factory()->create([
        'user_id' => $otherUser->id,
        'title' => 'Other User Mosaic'
    ]);

    // Try to access other user's mosaic
    $response = $this->get(route('mosaics.show', $otherMosaic->id));
    $response->assertStatus(403);

    // Try to update other user's mosaic
    $response = $this->patch(route('mosaics.update', $otherMosaic->id), [
        'title' => 'Hacked Title'
    ]);
    $response->assertStatus(403);

    // Try to add item to other user's mosaic
    $response = $this->post(route('mosaics.items.store', $otherMosaic->id), [
        'type' => 'media',
        'properties' => ['title' => 'Hacked Item'],
        'order' => 0,
        'column_index' => 0
    ]);
    $response->assertStatus(403);
});

test('mosaic creation requires valid data', function () {
    // Test with missing title
    $response = $this->post(route('mosaics.store'), [
        'description' => 'Missing title'
    ]);
    $response->assertSessionHasErrors(['title']);

    // Test with invalid columns
    $response = $this->post(route('mosaics.store'), [
        'title' => 'Valid Title',
        'columns' => 'invalid'
    ]);
    $response->assertSessionHasErrors(['columns']);
});

test('mosaic item creation requires valid data', function () {
    $mosaic = Mosaic::factory()->create([
        'user_id' => $this->user->id
    ]);

    // Test with missing type
    $response = $this->post(route('mosaics.items.store', $mosaic->id), [
        'properties' => ['title' => 'Test'],
        'order' => 0,
        'column_index' => 0
    ]);
    $response->assertSessionHasErrors(['type']);

    // Test with invalid type
    $response = $this->post(route('mosaics.items.store', $mosaic->id), [
        'type' => 'invalid_type',
        'properties' => ['title' => 'Test'],
        'order' => 0,
        'column_index' => 0
    ]);
    $response->assertSessionHasErrors(['type']);

    // Test with missing properties
    $response = $this->post(route('mosaics.items.store', $mosaic->id), [
        'type' => 'media',
        'order' => 0,
        'column_index' => 0
    ]);
    $response->assertSessionHasErrors(['properties']);
});
