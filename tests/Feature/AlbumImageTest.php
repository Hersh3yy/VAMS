<?php

declare(strict_types=1);

use App\Models\Album;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('spaces');

    $this->user = User::factory()->create([
        'is_approved' => true,
        'email_verified_at' => now(),
    ]);
    $this->album = Album::factory()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

it('allows user to add image to their album', function (): void {
    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600)->size(1000);

    $response = $this->post(route('albums.images.store', $this->album->id), [
        'images' => [$file],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('album_images', [
        'album_id' => $this->album->id,
    ]);
});

it('requires image file when adding to album', function (): void {
    $response = $this->post(route('albums.images.store', $this->album->id), [
        'title' => 'Test Image',
        'description' => 'Test image description',
    ]);

    // Should not create any album images without proper image files
    $this->assertDatabaseMissing('album_images', [
        'album_id' => $this->album->id,
    ]);
});

it('prevents adding images to other users albums', function (): void {
    $otherUser = User::factory()->create();
    $otherAlbum = Album::factory()->create(['user_id' => $otherUser->id]);

    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $response = $this->post(route('albums.images.store', $otherAlbum->id), [
        'images' => [$file],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseMissing('album_images', [
        'album_id' => $otherAlbum->id,
    ]);
});
