<?php

declare(strict_types=1);

use App\Models\Album;
use App\Models\User;
use App\Services\AlbumService;
use Mockery;

it('creates album with proper data', function () {
    // Create a mock user
    $user = Mockery::mock(User::class);
    $user->shouldReceive('getAttribute')
        ->with('id')
        ->andReturn('123e4567-e89b-12d3-a456-426614174000');
    
    // Create a mock album
    $album = Mockery::mock(Album::class);
    $album->shouldReceive('getAttribute')
        ->with('id')
        ->andReturn('456e7890-e89b-12d3-a456-426614174001');
    $album->shouldReceive('getAttribute')
        ->with('title')
        ->andReturn('Test Album');
    $album->shouldReceive('getAttribute')
        ->with('description')
        ->andReturn('Test Description');
    
    // Mock the Album model's create method
    Album::shouldReceive('create')
        ->once()
        ->with([
            'title' => 'Test Album',
            'description' => 'Test Description',
            'user_id' => '123e4567-e89b-12d3-a456-426614174000',
        ])
        ->andReturn($album);
    
    // Create service instance
    $service = new AlbumService();
    
    // Test the method
    $result = $service->createAlbum($user, [
        'title' => 'Test Album',
        'description' => 'Test Description',
    ]);
    
    expect($result)->toBe($album);
});

it('validates album data before creation', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('getAttribute')
        ->with('id')
        ->andReturn('123e4567-e89b-12d3-a456-426614174000');
    
    // Mock validation to throw exception
    Album::shouldReceive('create')
        ->once()
        ->andThrow(new \InvalidArgumentException('Title is required'));
    
    $service = new AlbumService();
    
    expect(fn() => $service->createAlbum($user, []))
        ->toThrow(\InvalidArgumentException::class, 'Title is required');
});

it('updates album with new data', function () {
    $album = Mockery::mock(Album::class);
    $album->shouldReceive('update')
        ->once()
        ->with([
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ])
        ->andReturn(true);
    
    $album->shouldReceive('getAttribute')
        ->with('id')
        ->andReturn('456e7890-e89b-12d3-a456-426614174001');
    
    $service = new AlbumService();
    
    $result = $service->updateAlbum($album, [
        'title' => 'Updated Title',
        'description' => 'Updated Description',
    ]);
    
    expect($result)->toBeTrue();
});

it('deletes album and returns confirmation', function () {
    $album = Mockery::mock(Album::class);
    $album->shouldReceive('delete')
        ->once()
        ->andReturn(true);
    
    $album->shouldReceive('getAttribute')
        ->with('id')
        ->andReturn('456e7890-e89b-12d3-a456-426614174001');
    
    $service = new AlbumService();
    
    $result = $service->deleteAlbum($album);
    
    expect($result)->toBeTrue();
});

it('finds album by id', function () {
    $album = Mockery::mock(Album::class);
    $album->shouldReceive('getAttribute')
        ->with('id')
        ->andReturn('456e7890-e89b-12d3-a456-426614174001');
    $album->shouldReceive('getAttribute')
        ->with('title')
        ->andReturn('Found Album');
    
    Album::shouldReceive('find')
        ->once()
        ->with('456e7890-e89b-12d3-a456-426614174001')
        ->andReturn($album);
    
    $service = new AlbumService();
    
    $result = $service->findAlbum('456e7890-e89b-12d3-a456-426614174001');
    
    expect($result)->toBe($album);
    expect($result->title)->toBe('Found Album');
});

it('returns null when album not found', function () {
    Album::shouldReceive('find')
        ->once()
        ->with('nonexistent-id')
        ->andReturn(null);
    
    $service = new AlbumService();
    
    $result = $service->findAlbum('nonexistent-id');
    
    expect($result)->toBeNull();
});

it('gets user albums with pagination', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('getAttribute')
        ->with('id')
        ->andReturn('123e4567-e89b-12d3-a456-426614174000');
    
    $albums = collect([
        Mockery::mock(Album::class),
        Mockery::mock(Album::class),
    ]);
    
    $user->shouldReceive('albums')
        ->once()
        ->andReturnSelf();
    
    $user->shouldReceive('latest')
        ->once()
        ->andReturnSelf();
    
    $user->shouldReceive('paginate')
        ->once()
        ->with(10)
        ->andReturn($albums);
    
    $service = new AlbumService();
    
    $result = $service->getUserAlbums($user, 10);
    
    expect($result)->toBe($albums);
    expect($result)->toHaveCount(2);
});
