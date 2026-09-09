<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('spaces');
    $this->user = User::factory()->create();
});

it('uploads media via web route with session auth', function () {
    $this->actingAs($this->user);
    $file = UploadedFile::fake()->image('test.jpg', 800, 600);

    $response = $this->postJson(route('media.upload'), [
        'file' => $file,
        'type' => 'image',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'url',
                'path',
                'type',
                'size',
                'original_name',
            ],
        ]);
});

it('requires authentication for media upload', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $response = $this->postJson(route('media.upload'), [
        'file' => $file,
        'type' => 'image',
    ]);

    $response->assertStatus(401);
});

it('validates file for media upload', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('media.upload'), [
        'type' => 'image',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

it('deletes media via web route', function () {
    $this->actingAs($this->user);

    // Own SPA scratch space (uploaded, not yet attached to any entity).
    $response = $this->deleteJson(route('media.delete'), [
        'path' => "uploads/images/{$this->user->id}/file.jpg",
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'File deleted successfully',
        ]);
});

it('requires path for media deletion', function () {
    $this->actingAs($this->user);

    $response = $this->deleteJson(route('media.delete'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['path']);
});

it('denies deleting another user\'s attached album image', function () {
    $owner = User::factory()->create();
    $album = \App\Models\Album::factory()->create(['user_id' => $owner->id]);
    $image = \App\Models\AlbumImage::factory()->create([
        'album_id' => $album->id,
        'path' => 'https://spaces.example/bucket/albums/1/photo.jpg',
    ]);

    $this->actingAs($this->user); // not the owner

    $response = $this->deleteJson(route('media.delete'), [
        'path' => $image->path,
    ]);

    $response->assertStatus(403);
});
