<?php

declare(strict_types=1);

use App\Models\Album;
use App\Models\AlbumImage;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->album = Album::factory()->create(['user_id' => $this->user->id]);
    $this->albumImages = AlbumImage::factory()->count(3)->create([
        'album_id' => $this->album->id,
    ]);
});

it('retrieves album via API with valid API key', function () {
    $response = $this->withHeaders([
        'X-API-Key' => $this->user->api_key,
    ])->getJson("/api/albums/{$this->album->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'album' => [
                    'id',
                    'title',
                    'description',
                    'cover_image_path',
                    'images_count',
                    'user_id',
                    'created_at',
                    'updated_at',
                ],
                'images' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'path',
                        'webp_path',
                        'thumbnail_url',
                        'webp_url',
                        'caption',
                        'order',
                        'properties',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);
});

it('returns 401 for API requests without API key', function () {
    $response = $this->getJson("/api/albums/{$this->album->id}");

    $response->assertStatus(401);
});

it('returns 404 when accessing other users album with API key', function () {
    $otherUser = User::factory()->create();

    $response = $this->withHeaders([
        'X-API-Key' => $otherUser->api_key,
    ])->getJson("/api/albums/{$this->album->id}");

    $response->assertStatus(404);
});

it('returns 404 for non-existent album', function () {
    $response = $this->withHeaders([
        'X-API-Key' => $this->user->api_key,
    ])->getJson('/api/albums/99999');

    $response->assertStatus(404);
});

it('retrieves all albums via API index with valid API key', function () {
    // Create another album for this user
    $album2 = Album::factory()->create(['user_id' => $this->user->id]);
    AlbumImage::factory()->count(2)->create(['album_id' => $album2->id]);

    $response = $this->withHeaders([
        'X-API-Key' => $this->user->api_key,
    ])->getJson('/api/albums');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'albums' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'cover_image_path',
                        'images_count',
                        'user_id',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);

    // Verify images_count is correct
    $albums = $response->json('data.albums');
    expect($albums)->toHaveCount(2);

    // Find the albums in the response
    $album1Data = collect($albums)->firstWhere('id', $this->album->id);
    $album2Data = collect($albums)->firstWhere('id', $album2->id);

    expect($album1Data['images_count'])->toBe(3);
    expect($album2Data['images_count'])->toBe(2);

    // Verify images are NOT included by default
    expect($album1Data)->not->toHaveKey('images');
    expect($album2Data)->not->toHaveKey('images');
});

it('includes images when with_images parameter is true', function () {
    $response = $this->withHeaders([
        'X-API-Key' => $this->user->api_key,
    ])->getJson('/api/albums?with_images=true');

    $response->assertStatus(200);

    $albums = $response->json('data.albums');
    expect($albums)->toHaveCount(1);

    $albumData = $albums[0];

    // Verify images are included
    expect($albumData)->toHaveKey('images');
    expect($albumData['images'])->toHaveCount(3);

    // Verify images have correct structure
    expect($albumData['images'][0])->toHaveKeys([
        'id',
        'title',
        'description',
        'path',
        'webp_path',
        'thumbnail_url',
        'webp_url',
        'caption',
        'order',
        'properties',
        'created_at',
        'updated_at',
    ]);
});
