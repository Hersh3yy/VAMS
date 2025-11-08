<?php

declare(strict_types=1);

use App\Models\Album;
use App\Models\AlbumImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

it('allows authenticated user to view albums index', function () {
    $this->actingAs($this->user);

    // Create some albums for this user
    Album::factory()->count(3)->forUser($this->user)->create();
    // Create albums for other users (should not appear)
    Album::factory()->count(2)->forUser($this->otherUser)->create();

    $response = $this->get(route('albums.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Albums/Index')
        ->has('albums', 3) // Only user's albums
    );
});

it('prevents guest from viewing albums index', function () {
    $response = $this->get(route('albums.index'));
    $response->assertRedirect('/login');
});

it('allows authenticated user to view create album page', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('albums.create'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Albums/Create')
    );
});

it('allows authenticated user to create album', function () {
    $this->actingAs($this->user);

    $albumData = [
        'title' => 'Test Album',
        'description' => 'This is a test album description.',
    ];

    $response = $this->post(route('albums.store'), $albumData);

    $response->assertRedirect();

    $this->assertDatabaseHas('albums', [
        'title' => 'Test Album',
        'description' => 'This is a test album description.',
        'user_id' => $this->user->id,
    ]);
});

it('validates required fields for album creation', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('albums.store'), []);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['title']);
});

it('validates title length for album creation', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('albums.store'), [
        'title' => str_repeat('a', 256), // Too long
        'description' => 'Valid description',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['title']);
});

it('allows authenticated user to view album', function () {
    $this->actingAs($this->user);

    $album = Album::factory()->forUser($this->user)->create();

    // Add some images to the album
    AlbumImage::factory()->count(3)->forAlbum($album)->create();

    $response = $this->get(route('albums.show', $album));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Albums/Show')
        ->has('Album')
        ->where('Album.id', $album->id)
        ->where('Album.title', $album->title)
        ->has('Album.images', 3)
    );
});

it('prevents user from viewing other users album', function () {
    $this->actingAs($this->user);

    $album = Album::factory()->forUser($this->otherUser)->create();

    $response = $this->get(route('albums.show', $album));

    $response->assertForbidden();
});

it('allows authenticated user to view edit album page', function () {
    $this->actingAs($this->user);

    $album = Album::factory()->forUser($this->user)->create();

    $response = $this->get(route('albums.edit', $album));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Albums/Edit')
        ->has('Album')
        ->where('Album.id', $album->id)
    );
});

it('prevents user from editing other users album', function () {
    $this->actingAs($this->user);

    $album = Album::factory()->forUser($this->otherUser)->create();

    $response = $this->get(route('albums.edit', $album));

    $response->assertForbidden();
});

it('allows authenticated user to update album', function () {
    $this->actingAs($this->user);

    $album = Album::factory()->forUser($this->user)->create([
        'title' => 'Original Title',
        'description' => 'Original Description',
    ]);

    $updateData = [
        'title' => 'Updated Title',
        'description' => 'Updated Description',
    ];

    $response = $this->patch(route('albums.update', $album), $updateData);

    $response->assertRedirect();

    $album->refresh();
    expect($album->title)->toBe('Updated Title');
    expect($album->description)->toBe('Updated Description');
});

it('prevents user from updating other users album', function () {
    $this->actingAs($this->user);

    $album = Album::factory()->forUser($this->otherUser)->create();

    $response = $this->patch(route('albums.update', $album), [
        'title' => 'Hacked Title',
        'description' => 'Hacked Description',
    ]);

    $response->assertForbidden();
});

it('validates required fields for album update', function () {
    $this->actingAs($this->user);

    $album = Album::factory()->forUser($this->user)->create();

    $response = $this->patch(route('albums.update', $album), [
        'title' => '', // Empty title
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['title']);
});

it('allows authenticated user to upload cover image', function () {
    $this->actingAs($this->user);

    $album = Album::factory()->forUser($this->user)->create();

    $coverImage = UploadedFile::fake()->image('cover.jpg', 800, 600);

    $response = $this->patch(route('albums.update', $album), [
        'title' => $album->title,
        'description' => $album->description,
        'cover_image' => $coverImage,
    ]);

    $response->assertRedirect();

    $album->refresh();
    expect($album->cover_image_path)->not->toBeNull();
});

it('validates cover image file type', function () {
    $this->actingAs($this->user);

    $album = Album::factory()->forUser($this->user)->create();

    $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

    $response = $this->patch(route('albums.update', $album), [
        'title' => $album->title,
        'description' => $album->description,
        'cover_image' => $invalidFile,
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['cover_image']);
});

it('allows authenticated user to delete album', function () {
    $this->actingAs($this->user);

    $album = Album::factory()->forUser($this->user)->create();

    $response = $this->delete(route('albums.destroy', $album));

    $response->assertRedirect();
    $this->assertModelMissing($album);
});

it('prevents user from deleting other users album', function () {
    $this->actingAs($this->user);

    $album = Album::factory()->forUser($this->otherUser)->create();

    $response = $this->delete(route('albums.destroy', $album));

    $response->assertForbidden();
    $this->assertModelExists($album);
});

it('deletes album images when deleting album', function () {
    $this->actingAs($this->user);

    $album = Album::factory()->forUser($this->user)->create();
    $images = AlbumImage::factory()->count(3)->forAlbum($album)->create();

    $response = $this->delete(route('albums.destroy', $album));

    $response->assertRedirect();
    $this->assertModelMissing($album);

    foreach ($images as $image) {
        $this->assertModelMissing($image);
    }
});

it('shows album with correct image ordering', function () {
    $this->actingAs($this->user);

    $album = Album::factory()->forUser($this->user)->create();

    // Create images with specific order
    $image1 = AlbumImage::factory()->forAlbum($album)->withOrder(2)->create();
    $image2 = AlbumImage::factory()->forAlbum($album)->withOrder(0)->create();
    $image3 = AlbumImage::factory()->forAlbum($album)->withOrder(1)->create();

    $response = $this->get(route('albums.show', $album));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->has('Album.images', 3)
        ->where('Album.images.0.id', $image2->id) // order 0
        ->where('Album.images.1.id', $image3->id) // order 1
        ->where('Album.images.2.id', $image1->id) // order 2
    );
});
