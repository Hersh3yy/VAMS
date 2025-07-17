<?php

namespace Tests\Feature;

use App\Models\Mosaic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MosaicMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Mosaic $mosaic;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('spaces');
        
        $this->user = User::factory()->create([
            'is_approved' => true,
            'email_verified_at' => now()
        ]);
        
        $this->mosaic = Mosaic::factory()->create([
            'user_id' => $this->user->id
        ]);
    }

    public function test_authenticated_user_can_upload_media_to_mosaic()
    {
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600)->size(1000);

        $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
            'media' => $file
        ]);

        $response->assertSuccessful()
                ->assertJson([
                    'success' => true
                ])
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

    public function test_can_upload_video_to_mosaic()
    {
        $this->actingAs($this->user);

        $video = UploadedFile::fake()->create('test-video.mp4', 5000, 'video/mp4');

        $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
            'media' => $video
        ]);

        $response->assertSuccessful()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'type' => 'video',
                        'mime_type' => 'video/mp4'
                    ]
                ]);
    }

    public function test_unauthorized_user_cannot_upload_to_mosaic()
    {
        $otherUser = User::factory()->create(['is_approved' => true]);
        $this->actingAs($otherUser);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
            'media' => $file
        ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_upload_media()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
            'media' => $file
        ]);

        $response->assertUnauthorized();
    }

    public function test_upload_validates_file_types()
    {
        $this->actingAs($this->user);

        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
            'media' => $invalidFile
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['media']);
    }

    public function test_upload_validates_file_size()
    {
        $this->actingAs($this->user);

        // Create file larger than 30MB limit
        $largeFile = UploadedFile::fake()->image('large.jpg')->size(31 * 1024); // 31MB

        $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
            'media' => $largeFile
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['media']);
    }

    public function test_upload_requires_media_file()
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['media']);
    }

    public function test_upload_handles_errors_gracefully()
    {
        $this->actingAs($this->user);

        // Mock ImageService to throw an exception
        $this->mock(\App\Services\ImageService::class, function ($mock) {
            $mock->shouldReceive('storeImage')
                 ->andThrow(new \Exception('Storage failed'));
        });

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
            'media' => $file
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Upload failed: Storage failed'
                ]);
    }

    public function test_upload_returns_webp_url_when_available()
    {
        $this->actingAs($this->user);

        // Mock ImageService to return WebP URL
        $this->mock(\App\Services\ImageService::class, function ($mock) {
            $mock->shouldReceive('storeImage')
                 ->andReturn([
                     'url' => 'https://example.com/image.jpg',
                     'webp_url' => 'https://example.com/image.webp'
                 ]);
        });

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
            'media' => $file
        ]);

        $response->assertSuccessful()
                ->assertJsonPath('data.webp_url', 'https://example.com/image.webp');
    }
} 