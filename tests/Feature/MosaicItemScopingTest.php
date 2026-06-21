<?php

declare(strict_types=1);

use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Models\User;

it('returns 404 when updating an item that belongs to another mosaic', function () {
    $user = User::factory()->create();
    $mosaic = Mosaic::factory()->create(['user_id' => $user->id]);
    $otherMosaic = Mosaic::factory()->create(['user_id' => $user->id]);
    $foreignItem = MosaicItem::factory()->create(['mosaic_id' => $otherMosaic->id]);

    $this->actingAs($user)
        ->put(route('mosaics.items.update', [$mosaic, $foreignItem]), [
            'type' => 'text',
            'properties' => ['text' => 'hijacked'],
            'order' => 0,
            'column_index' => 0,
        ])
        ->assertNotFound();

    expect($foreignItem->refresh()->properties)->not->toBe(['text' => 'hijacked']);
});

it('returns 404 when deleting an item that belongs to another mosaic', function () {
    $user = User::factory()->create();
    $mosaic = Mosaic::factory()->create(['user_id' => $user->id]);
    $otherMosaic = Mosaic::factory()->create(['user_id' => $user->id]);
    $foreignItem = MosaicItem::factory()->create(['mosaic_id' => $otherMosaic->id]);

    $this->actingAs($user)
        ->delete(route('mosaics.items.destroy', [$mosaic, $foreignItem]))
        ->assertNotFound();

    $this->assertDatabaseHas('mosaic_items', ['id' => $foreignItem->id]);
});

it('still updates items that belong to the mosaic', function () {
    $user = User::factory()->create();
    $mosaic = Mosaic::factory()->create(['user_id' => $user->id]);
    $item = MosaicItem::factory()->create(['mosaic_id' => $mosaic->id]);

    $this->actingAs($user)
        ->put(route('mosaics.items.update', [$mosaic, $item]), [
            'type' => 'text',
            'properties' => ['text' => 'updated'],
            'order' => 0,
            'column_index' => 0,
        ])
        ->assertRedirect(route('mosaics.show', $mosaic->id));

    expect($item->refresh()->properties)->toMatchArray(['text' => 'updated']);
});
