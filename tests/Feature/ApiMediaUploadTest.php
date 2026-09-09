<?php

declare(strict_types=1);

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
});

it('allows authenticated user to upload media via web route', function () {
    $this->actingAs($this->user);

    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600)->size(1000);

    $response = $this->postJson(route('media.upload'), [
        'file' => $file,
        'type' => 'image',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
        ])
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

it('validates file size 10mb limit for upload', function () {
    $this->actingAs($this->user);

    $largeFile = UploadedFile::fake()->image('large.jpg')->size(11 * 1024); // 11MB

    $response = $this->postJson(route('media.upload'), [
        'file' => $largeFile,
        'type' => 'image',
    ]);

    // Note: Backend no longer validates file size - handled client-side or by infrastructure
    $response->assertSuccessful();
})->skip('Backend file size validation removed per user request');

it('validates file types for upload', function () {
    $this->actingAs($this->user);

    $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

    $response = $this->postJson(route('media.upload'), [
        'file' => $invalidFile,
        'type' => 'image',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

it('requires authentication for upload', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    $response = $this->postJson(route('media.upload'), [
        'file' => $file,
        'type' => 'image',
    ]);

    $response->assertStatus(401);
});

it('requires file for upload', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('media.upload'), [
        'type' => 'image',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

it('makes type optional for upload', function () {
    $this->actingAs($this->user);
    $file = UploadedFile::fake()->image('test.jpg');

    $response = $this->postJson(route('media.upload'), [
        'file' => $file,
    ]);

    $response->assertSuccessful()
        ->assertJson(['success' => true])
        ->assertJsonPath('data.type', 'image');
});

it('validates type values for upload', function () {
    $this->actingAs($this->user);
    $file = UploadedFile::fake()->image('test.jpg');

    $response = $this->postJson(route('media.upload'), [
        'file' => $file,
        'type' => 'invalid_type',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

it('handles video files', function () {
    $this->actingAs($this->user);

    $video = UploadedFile::fake()->create('test-video.mp4', 5000, 'video/mp4');

    $response = $this->postJson(route('media.upload'), [
        'file' => $video,
        'type' => 'video',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.type', 'video');
});

it('can delete media via web route', function () {
    $this->actingAs($this->user);

    // Own SPA scratch space (uploaded, not yet attached to any entity).
    $response = $this->deleteJson(route('media.delete'), [
        'path' => "uploads/images/{$this->user->id}/file.jpg",
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'File deleted successfully',
        ]);
});

it('requires path for delete', function () {
    $this->actingAs($this->user);

    $response = $this->deleteJson(route('media.delete'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['path']);
});

it('allows multiple uploads', function () {
    $this->actingAs($this->user);

    for ($i = 0; $i < 3; $i++) {
        $file = UploadedFile::fake()->image("test-{$i}.jpg");

        $response = $this->postJson(route('media.upload'), [
            'file' => $file,
            'type' => 'image',
        ]);

        $response->assertSuccessful();
    }
});

it('returns webp url when available', function () {
    $this->actingAs($this->user);

    $this->mock(\App\Services\ImageService::class, function ($mock) {
        $mock->shouldReceive('storeImage')
            ->andReturn([
                'url' => 'https://example.com/image.jpg',
                'path' => 'uploads/images/test.jpg',
                'webp_url' => 'https://example.com/image.webp',
            ]);
    });

    $file = UploadedFile::fake()->image('test.jpg');

    $response = $this->postJson(route('media.upload'), [
        'file' => $file,
        'type' => 'image',
    ]);

    $response->assertSuccessful();
});
