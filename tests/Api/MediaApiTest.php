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

it('uploads media via API with sanctum token', function () {
    $token = $this->user->createToken('test')->plainTextToken;
    $file = UploadedFile::fake()->image('test.jpg', 800, 600);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ])->postJson('/api/media/upload', [
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

    $response = $this->postJson('/api/media/upload', [
        'file' => $file,
        'type' => 'image',
    ]);

    $response->assertStatus(401);
});

it('validates file for media upload via API', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ])->postJson('/api/media/upload', [
        'type' => 'image',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

it('deletes media via API', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ])->deleteJson('/api/media', [
        'path' => 'test/path/file.jpg',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'File deleted successfully',
        ]);
});

it('requires path for media deletion via API', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ])->deleteJson('/api/media', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['path']);
});
