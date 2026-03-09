<?php

declare(strict_types=1);

use App\Models\Album;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('spaces');
    $this->user = User::factory()->create();
    $this->album = Album::factory()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

it('allows user to add image to their album', function () {
    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $response = $this->post(route('albums.images.store', $this->album->id), [
        'images' => [$file],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('album_images', [
        'album_id' => $this->album->id,
    ]);
});

it('requires image file when adding to album', function () {
    $response = $this->post(route('albums.images.store', $this->album->id), [
        'title' => 'Test Image',
        'description' => 'Test image description',
    ]);

    // Should not create any album images without proper image files
    $this->assertDatabaseMissing('album_images', [
        'album_id' => $this->album->id,
    ]);
});

it('prevents adding images to other users albums', function () {
    $otherUser = User::factory()->create();
    $otherAlbum = Album::factory()->create(['user_id' => $otherUser->id]);

    $file = \Illuminate\Http\UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $response = $this->post(route('albums.images.store', $otherAlbum->id), [
        'images' => [$file],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseMissing('album_images', [
        'album_id' => $otherAlbum->id,
    ]);
});
