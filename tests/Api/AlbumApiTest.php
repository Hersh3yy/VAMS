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
