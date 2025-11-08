<?php

declare(strict_types=1);

use App\Models\Mosaic;
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

    $this->mosaic = Mosaic::factory()->create([
        'user_id' => $this->user->id,
    ]);
});

it('allows authenticated user to upload media to mosaic', function () {
    $this->actingAs($this->user);

    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600)->size(1000);

    $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
        'media' => $file,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'data' => [
                'path',
                'type',
                'mime_type',
                'original_name',
                'size',
            ],
        ]);
});

it('can upload video to mosaic', function () {
    $this->actingAs($this->user);

    $video = UploadedFile::fake()->create('test-video.mp4', 5000, 'video/mp4');

    $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
        'media' => $video,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'type' => 'video',
                'mime_type' => 'video/mp4',
            ],
        ]);
});

it('prevents unauthorized user from uploading to mosaic', function () {
    $otherUser = User::factory()->create(['is_approved' => true]);
    $this->actingAs($otherUser);

    $file = UploadedFile::fake()->image('test.jpg');

    $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
        'media' => $file,
    ]);

    $response->assertForbidden();
});

it('prevents guest from uploading media', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
        'media' => $file,
    ]);

    $response->assertUnauthorized();
});

it('validates file types on upload', function () {
    $this->actingAs($this->user);

    $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

    $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
        'media' => $invalidFile,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['media']);
});

it('validates file size on upload', function () {
    $this->actingAs($this->user);

    // Create file larger than 30MB limit
    $largeFile = UploadedFile::fake()->image('large.jpg')->size(31 * 1024); // 31MB

    $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
        'media' => $largeFile,
    ]);

    // Note: Backend no longer validates file size - this is handled client-side or by infrastructure
    $response->assertSuccessful();
})->skip('Backend file size validation removed per user request');

it('requires media file for upload', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['media']);
});

it('handles upload errors gracefully', function () {
    $this->actingAs($this->user);

    // Mock ImageService to throw an exception
    $this->mock(\App\Services\ImageService::class, function ($mock) {
        $mock->shouldReceive('storeImage')
            ->andThrow(new \Exception('Storage failed'));
    });

    $file = UploadedFile::fake()->image('test.jpg');

    $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
        'media' => $file,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Upload failed: Storage failed',
        ]);
});

it('returns webp url when available', function () {
    $this->actingAs($this->user);

    // Mock ImageService to return WebP URL
    $this->mock(\App\Services\ImageService::class, function ($mock) {
        $mock->shouldReceive('storeImage')
            ->andReturn([
                'url' => 'https://example.com/image.jpg',
                'webp_url' => 'https://example.com/image.webp',
            ]);
    });

    $file = UploadedFile::fake()->image('test.jpg');

    $response = $this->postJson(route('mosaics.media.upload', $this->mosaic), [
        'media' => $file,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.webp_url', 'https://example.com/image.webp');
});
