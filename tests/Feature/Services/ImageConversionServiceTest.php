<?php

declare(strict_types=1);

use App\Services\ImageConversionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// These tests exercise ImageConversionService with a real Storage fake
// and the Laravel container, so they live in Feature-test style but
// are scoped to the service layer only.

beforeEach(function (): void {
    Storage::fake('spaces');
    $this->service = $this->app->make(ImageConversionService::class);
});

it('stores the original and generates a webp derivative for a jpeg', function (): void {
    $file = UploadedFile::fake()->image('photo.jpg', 200, 200);

    $result = $this->service->storeWithDerivatives($file, 'test-folder');

    expect($result)->toHaveKeys(['path', 'url', 'variants'])
        ->and($result['path'])->toContain('test-folder')
        ->and($result['variants'])->toHaveKey('webp')
        ->and($result['variants']['webp']['path'])->toContain('test-folder')
        ->and($result['variants']['webp']['url'])->toContain('.webp');
});

it('stores the original and generates a webp derivative for a png', function (): void {
    $file = UploadedFile::fake()->image('photo.png', 100, 100);

    $result = $this->service->storeWithDerivatives($file, 'test-folder');

    expect($result['variants'])->toHaveKey('webp');
});

it('treats an uploaded webp as both original and webp variant', function (): void {
    $file = UploadedFile::fake()->image('photo.webp', 100, 100);

    $result = $this->service->storeWithDerivatives($file, 'test-folder');

    expect($result['variants'])->toHaveKey('webp')
        ->and($result['variants']['webp']['path'])->toBe($result['path']);
});

it('returns an empty variants array when conversion is not applicable', function (): void {
    $file = UploadedFile::fake()->create('icon.svg', 10, 'image/svg+xml');

    $result = $this->service->storeWithDerivatives($file, 'test-folder');

    expect($result)->toHaveKey('path')
        ->and($result['variants'])->toBe([]);
});

it('stores the original file on disk', function (): void {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $result = $this->service->storeWithDerivatives($file, 'uploads');

    Storage::disk('spaces')->assertExists($result['path']);
});

it('stores the webp derivative on disk', function (): void {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $result = $this->service->storeWithDerivatives($file, 'uploads');

    Storage::disk('spaces')->assertExists($result['variants']['webp']['path']);
});
