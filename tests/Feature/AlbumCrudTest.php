<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\AlbumImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlbumCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('spaces');
        
        $this->user = User::factory()->create([
            'is_approved' => true,
            'email_verified_at' => now()
        ]);
        
        $this->otherUser = User::factory()->create([
            'is_approved' => true,
            'email_verified_at' => now()
        ]);
    }

    public function test_authenticated_user_can_view_albums_index()
    {
        $this->actingAs($this->user);

        // Create some albums for this user
        Album::factory()->count(3)->forUser($this->user)->create();
        // Create albums for other users (should not appear)
        Album::factory()->count(2)->forUser($this->otherUser)->create();

        $response = $this->get(route('albums.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => 
            $page->component('Albums/Index')
                 ->has('albums', 3) // Only user's albums
        );
    }

    public function test_guest_cannot_view_albums_index()
    {
        $response = $this->get(route('albums.index'));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_create_album_page()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('albums.create'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => 
            $page->component('Albums/Create')
        );
    }

    public function test_authenticated_user_can_create_album()
    {
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
    }

    public function test_album_creation_validates_required_fields()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('albums.store'), []);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['title']);
    }

    public function test_album_creation_validates_title_length()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('albums.store'), [
            'title' => str_repeat('a', 256), // Too long
            'description' => 'Valid description',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['title']);
    }

    public function test_authenticated_user_can_view_album()
    {
        $this->actingAs($this->user);

        $album = Album::factory()->forUser($this->user)->create();
        
        // Add some images to the album
        AlbumImage::factory()->count(3)->forAlbum($album)->create();

        $response = $this->get(route('albums.show', $album));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => 
            $page->component('Albums/Show')
                 ->has('album')
                 ->where('album.id', $album->id)
                 ->where('album.title', $album->title)
                 ->has('album.images', 3)
        );
    }

    public function test_user_cannot_view_other_users_album()
    {
        $this->actingAs($this->user);

        $album = Album::factory()->forUser($this->otherUser)->create();

        $response = $this->get(route('albums.show', $album));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_edit_album_page()
    {
        $this->actingAs($this->user);

        $album = Album::factory()->forUser($this->user)->create();

        $response = $this->get(route('albums.edit', $album));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => 
            $page->component('Albums/Edit')
                 ->has('album')
                 ->where('album.id', $album->id)
        );
    }

    public function test_user_cannot_edit_other_users_album()
    {
        $this->actingAs($this->user);

        $album = Album::factory()->forUser($this->otherUser)->create();

        $response = $this->get(route('albums.edit', $album));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_update_album()
    {
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
        $this->assertEquals('Updated Title', $album->title);
        $this->assertEquals('Updated Description', $album->description);
    }

    public function test_user_cannot_update_other_users_album()
    {
        $this->actingAs($this->user);

        $album = Album::factory()->forUser($this->otherUser)->create();

        $response = $this->patch(route('albums.update', $album), [
            'title' => 'Hacked Title',
            'description' => 'Hacked Description',
        ]);

        $response->assertForbidden();
    }

    public function test_album_update_validates_required_fields()
    {
        $this->actingAs($this->user);

        $album = Album::factory()->forUser($this->user)->create();

        $response = $this->patch(route('albums.update', $album), [
            'title' => '', // Empty title
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['title']);
    }

    public function test_authenticated_user_can_upload_cover_image()
    {
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
        $this->assertNotNull($album->cover_image_path);
    }

    public function test_cover_image_upload_validates_file_type()
    {
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
    }

    public function test_authenticated_user_can_delete_album()
    {
        $this->actingAs($this->user);

        $album = Album::factory()->forUser($this->user)->create();

        $response = $this->delete(route('albums.destroy', $album));

        $response->assertRedirect();
        $this->assertModelMissing($album);
    }

    public function test_user_cannot_delete_other_users_album()
    {
        $this->actingAs($this->user);

        $album = Album::factory()->forUser($this->otherUser)->create();

        $response = $this->delete(route('albums.destroy', $album));

        $response->assertForbidden();
        $this->assertModelExists($album);
    }

    public function test_deleting_album_also_deletes_album_images()
    {
        $this->actingAs($this->user);

        $album = Album::factory()->forUser($this->user)->create();
        $images = AlbumImage::factory()->count(3)->forAlbum($album)->create();

        $response = $this->delete(route('albums.destroy', $album));

        $response->assertRedirect();
        $this->assertModelMissing($album);
        
        foreach ($images as $image) {
            $this->assertModelMissing($image);
        }
    }

    public function test_album_show_includes_correct_image_ordering()
    {
        $this->actingAs($this->user);

        $album = Album::factory()->forUser($this->user)->create();
        
        // Create images with specific order
        $image1 = AlbumImage::factory()->forAlbum($album)->withOrder(2)->create();
        $image2 = AlbumImage::factory()->forAlbum($album)->withOrder(0)->create();
        $image3 = AlbumImage::factory()->forAlbum($album)->withOrder(1)->create();

        $response = $this->get(route('albums.show', $album));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => 
            $page->has('album.images', 3)
                 ->where('album.images.0.id', $image2->id) // order 0
                 ->where('album.images.1.id', $image3->id) // order 1  
                 ->where('album.images.2.id', $image1->id) // order 2
        );
    }
} 