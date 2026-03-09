<?php

declare(strict_types=1);

use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->mosaic = Mosaic::factory()->create(['user_id' => $this->user->id]);
    $this->mosaicItems = MosaicItem::factory()->count(3)->create([
        'mosaic_id' => $this->mosaic->id,
    ]);
});

it('retrieves mosaic via API with valid API key', function () {
    $response = $this->withHeaders([
        'X-API-Key' => $this->user->api_key,
    ])->getJson("/api/v1/mosaics/{$this->mosaic->id}");

    $response->assertStatus(200);

    // Let's just check basic structure for now
    $response->assertJsonStructure([
        'success',
        'message',
        'data',
    ]);
});

it('returns 401 for mosaic API request without API key', function () {
    $response = $this->getJson("/api/v1/mosaics/{$this->mosaic->id}");

    $response->assertStatus(401)
        ->assertJson([
            'error' => 'API key is required',
        ]);
});

it('returns 401 for mosaic API request with invalid API key', function () {
    $response = $this->withHeaders([
        'X-API-Key' => 'invalid-key',
    ])->getJson("/api/v1/mosaics/{$this->mosaic->id}");

    $response->assertStatus(401)
        ->assertJson([
            'error' => 'Invalid API key',
        ]);
});

it('returns 404 for non-existent mosaic via API', function () {
    $response = $this->withHeaders([
        'X-API-Key' => $this->user->api_key,
    ])->getJson('/api/v1/mosaics/non-existent-id');

    $response->assertStatus(404);
});

it('returns 404 when accessing other users mosaic via API', function () {
    $otherUser = User::factory()->create();
    $otherMosaic = Mosaic::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->withHeaders([
        'X-API-Key' => $this->user->api_key,
    ])->getJson("/api/v1/mosaics/{$otherMosaic->id}");

    $response->assertStatus(404);
});
