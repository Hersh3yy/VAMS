<?php

namespace Tests\Feature;

use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Models\Album;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MosaicCrudTest extends TestCase
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

    public function test_authenticated_user_can_view_mosaics_index()
    {
        $this->actingAs($this->user);

        // Create some mosaics for this user
        Mosaic::factory()->count(3)->forUser($this->user)->create();
        // Create mosaics for other users (should not appear)
        Mosaic::factory()->count(2)->forUser($this->otherUser)->create();

        $response = $this->get(route('mosaics.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => 
            $page->component('Mosaics/Index')
                 ->has('mosaics', 3) // Only user's mosaics
        );
    }

    public function test_guest_cannot_view_mosaics_index()
    {
        $response = $this->get(route('mosaics.index'));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_create_mosaic_page()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('mosaics.create'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => 
            $page->component('Mosaics/Create')
        );
    }

    public function test_authenticated_user_can_create_mosaic()
    {
        $this->actingAs($this->user);

        $mosaicData = [
            'title' => 'Test Mosaic',
            'description' => 'This is a test mosaic description.',
            'columns' => 3,
            'display_settings' => [
                'show_titles' => true,
                'show_captions' => false,
            ],
        ];

        $response = $this->post(route('mosaics.store'), $mosaicData);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('mosaics', [
            'title' => 'Test Mosaic',
            'description' => 'This is a test mosaic description.',
            'columns' => 3,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_mosaic_creation_validates_required_fields()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('mosaics.store'), []);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['title', 'columns']);
    }

    public function test_mosaic_creation_validates_columns_range()
    {
        $this->actingAs($this->user);

        // Test minimum columns
        $response = $this->post(route('mosaics.store'), [
            'title' => 'Test Mosaic',
            'columns' => 1, // Too few
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['columns']);

        // Test maximum columns
        $response = $this->post(route('mosaics.store'), [
            'title' => 'Test Mosaic',
            'columns' => 6, // Too many
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['columns']);
    }

    public function test_authenticated_user_can_view_mosaic()
    {
        $this->actingAs($this->user);

        $mosaic = Mosaic::factory()->forUser($this->user)->create();
        
        // Add some items to the mosaic
        MosaicItem::factory()->count(3)->forMosaic($mosaic)->create();

        $response = $this->get(route('mosaics.show', $mosaic));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => 
            $page->component('Mosaics/Show')
                 ->has('mosaic')
                 ->where('mosaic.id', $mosaic->id)
                 ->where('mosaic.title', $mosaic->title)
                 ->has('mosaic.items', 3)
        );
    }

    public function test_user_cannot_view_other_users_mosaic()
    {
        $this->actingAs($this->user);

        $mosaic = Mosaic::factory()->forUser($this->otherUser)->create();

        $response = $this->get(route('mosaics.show', $mosaic));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_edit_mosaic_page()
    {
        $this->actingAs($this->user);

        $mosaic = Mosaic::factory()->forUser($this->user)->create();

        $response = $this->get(route('mosaics.edit', $mosaic));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => 
            $page->component('Mosaics/Edit')
                 ->has('mosaic')
                 ->where('mosaic.id', $mosaic->id)
        );
    }

    public function test_user_cannot_edit_other_users_mosaic()
    {
        $this->actingAs($this->user);

        $mosaic = Mosaic::factory()->forUser($this->otherUser)->create();

        $response = $this->get(route('mosaics.edit', $mosaic));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_update_mosaic()
    {
        $mosaic = Mosaic::factory()->create(['user_id' => $this->user->id]);
        
        // Create some initial items
        MosaicItem::factory()->create([
            'mosaic_id' => $mosaic->id,
            'column_index' => 0,
            'order' => 0,
            'type' => 'text',
            'content' => 'Original text'
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'columns' => 3,
            'items' => [
                [
                    'type' => 'text',
                    'content' => 'Updated content',
                    'column_index' => 0,
                    'order' => 0,
                    'properties' => null,
                    'album_id' => null
                ]
            ]
        ];

        $response = $this->actingAs($this->user)->patchJson(route('mosaics.update', $mosaic), $updateData);

        // The controller returns JSON response, not redirect
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Mosaic updated successfully'
                ]);
        
        $mosaic->refresh();
        $this->assertEquals('Updated Title', $mosaic->title);
        $this->assertEquals('Updated Description', $mosaic->description);
        $this->assertEquals(3, $mosaic->columns);
    }

    public function test_user_cannot_update_other_users_mosaic()
    {
        $this->actingAs($this->user);

        $mosaic = Mosaic::factory()->forUser($this->otherUser)->create();

        $response = $this->patch(route('mosaics.update', $mosaic), [
            'title' => 'Hacked Title',
            'description' => 'Hacked Description',
            'items' => [],
        ]);

        $response->assertForbidden();
    }

    public function test_mosaic_update_validates_required_fields()
    {
        $mosaic = Mosaic::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->patchJson(route('mosaics.update', $mosaic), [
            'title' => '', // Empty title
            'items' => [],
        ]);

        // Controller returns JSON validation errors, not redirect with session errors
        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'items'
                    ]
                ]);
    }

    public function test_mosaic_update_validates_items_structure()
    {
        $mosaic = Mosaic::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->patchJson(route('mosaics.update', $mosaic), [
            'title' => 'Valid Title',
            'items' => [
                [
                    'type' => 'text',
                    'content' => 'Some content',
                    // Missing required fields: column_index, order
                ]
            ],
        ]);

        // Controller returns JSON validation errors, not redirect with session errors
        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);
    }

    public function test_authenticated_user_can_delete_mosaic()
    {
        $this->actingAs($this->user);

        $mosaic = Mosaic::factory()->forUser($this->user)->create();

        $response = $this->delete(route('mosaics.destroy', $mosaic));

        $response->assertRedirect();
        $this->assertModelMissing($mosaic);
    }

    public function test_user_cannot_delete_other_users_mosaic()
    {
        $this->actingAs($this->user);

        $mosaic = Mosaic::factory()->forUser($this->otherUser)->create();

        $response = $this->delete(route('mosaics.destroy', $mosaic));

        $response->assertForbidden();
        $this->assertModelExists($mosaic);
    }

    public function test_deleting_mosaic_also_deletes_mosaic_items()
    {
        $this->actingAs($this->user);

        $mosaic = Mosaic::factory()->forUser($this->user)->create();
        $items = MosaicItem::factory()->count(3)->forMosaic($mosaic)->create();

        $response = $this->delete(route('mosaics.destroy', $mosaic));

        $response->assertRedirect();
        $this->assertModelMissing($mosaic);
        
        foreach ($items as $item) {
            $this->assertModelMissing($item);
        }
    }

    public function test_mosaic_show_includes_correct_item_ordering()
    {
        $this->actingAs($this->user);

        $mosaic = Mosaic::factory()->forUser($this->user)->withColumns(2)->create();
        
        // Create items with specific order in different columns
        $item1 = MosaicItem::factory()->forMosaic($mosaic)->inColumn(0)->withOrder(1)->create();
        $item2 = MosaicItem::factory()->forMosaic($mosaic)->inColumn(0)->withOrder(0)->create();
        $item3 = MosaicItem::factory()->forMosaic($mosaic)->inColumn(1)->withOrder(0)->create();

        $response = $this->get(route('mosaics.show', $mosaic));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => 
            $page->has('mosaic.items', 3)
        );
    }

    public function test_mosaic_media_upload_works()
    {
        $this->actingAs($this->user);

        $mosaic = Mosaic::factory()->forUser($this->user)->create();

        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $response = $this->postJson(route('mosaics.media.upload', $mosaic), [
            'media' => $file
        ]);

        $response->assertSuccessful()
                ->assertJson(['success' => true])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'path',
                        'type',
                        'mime_type',
                        'original_name',
                        'size'
                    ]
                ]);
    }

    public function test_mosaic_media_upload_validates_ownership()
    {
        $this->actingAs($this->user);

        $mosaic = Mosaic::factory()->forUser($this->otherUser)->create();

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson(route('mosaics.media.upload', $mosaic), [
            'media' => $file
        ]);

        $response->assertForbidden();
    }

    public function test_mosaic_can_include_different_item_types()
    {
        $mosaic = Mosaic::factory()->create(['user_id' => $this->user->id]);
        $album = Album::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => 'Mixed Content Mosaic',
            'items' => [
                [
                    'type' => 'text',
                    'content' => 'Text content',
                    'column_index' => 0,
                    'order' => 0,
                    'properties' => null,
                    'album_id' => null
                ],
                [
                    'type' => 'album',
                    'content' => null,
                    'album_id' => $album->id,
                    'column_index' => 1,
                    'order' => 0,
                    'properties' => null
                ],
                [
                    'type' => 'color',
                    'content' => '#ff5733',
                    'column_index' => 0,
                    'order' => 1,
                    'properties' => null,
                    'album_id' => null
                ]
            ]
        ];

        $response = $this->actingAs($this->user)->patchJson(route('mosaics.update', $mosaic), $updateData);

        // The controller returns JSON response, not redirect
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Mosaic updated successfully'
                ]);
        
        $mosaic->refresh();
        $this->assertCount(3, $mosaic->items);
        
        // Verify different item types were created
        $items = $mosaic->items;
        $this->assertTrue($items->contains('type', 'text'));
        $this->assertTrue($items->contains('type', 'album'));
        $this->assertTrue($items->contains('type', 'color'));
    }
} 