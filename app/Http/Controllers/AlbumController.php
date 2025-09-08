<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlbumRequest;
use App\Models\Album;
use App\Models\AlbumImage;
use App\Services\AlbumService;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AlbumController extends BaseController
{
    public function __construct(
        protected readonly AlbumService $albumService,
        protected readonly MediaService $mediaService
    ) {
    }

    public function index(): Response
    {
        $albums = $this->albumService->getAllAlbums(false);

        return Inertia::render('Albums/Index', [
            'albums' => $albums
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Albums/Create');
    }

    public function store(StoreAlbumRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $album = $this->user()->albums()->create([
            'id' => Str::uuid(),
            'title' => $validated['title'],
            'description' => $validated['description'],
        ]);

        return $this->redirectWithSuccess('albums.show', $album, 'Album created successfully');
    }

    public function edit(string $albumId): Response|RedirectResponse
    {
        // Find album or handle gracefully
        $album = Album::find($albumId);
        
        if (!$album) {
            return $this->redirectWithError('albums.index', [], 'Album not found. You have been redirected to your albums.');
        }

        // Check if user owns this album
        $this->authorizeOwnership($album);

        // Get album with images using service
        $album = $this->albumService->getAlbum($album, false);

        return Inertia::render('Albums/Edit', [
            'album' => $album
        ]);
    }

    public function show(string $albumId): Response|RedirectResponse
    {
        // Find album or handle gracefully
        $album = Album::find($albumId);
        
        if (!$album) {
            return $this->redirectWithError('albums.index', [], 'Album not found. You have been redirected to your albums.');
        }

        // Check if user owns this album
        $this->authorizeOwnership($album);

        // Get album with images using service
        $album = $this->albumService->getAlbum($album, false);

        $defaultSettings = [
            'caption' => true,
            'altText' => true,
            'dateCreated' => true,
            'location' => true,
            'tags' => true,
            'title' => true,
            'author' => true,
            'main_color' => '#4F46E5', // Default indigo color
            'secondary_color' => '#10B981', // Default emerald color
        ];

        return Inertia::render('Albums/Show', [
            'album' => $album,
            'album_display_settings' => $this->user()->album_display_settings ?? $defaultSettings,
        ]);
    }

    public function update(Request $request, Album $album): RedirectResponse
    {
        Log::info('AlbumController@update - Incoming request data:', $request->all());
        
        // Check if user owns this album
        $this->authorizeOwnership($album);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:10240', // 10MB max
            'selected_cover_image_id' => 'nullable|exists:album_images,id',
        ]);
        
        Log::info('AlbumController@update - Validated data:', $validated);

        // Update album basic info
        $album->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
        ]);
        
        // Handle cover image upload if provided
        if ($request->hasFile('cover_image')) {
            Log::info('AlbumController@update - Processing cover image');
            
            // Delete old cover image if exists
            if ($album->cover_image_path) {
                $this->mediaService->deleteFile($album->cover_image_path);
            }
            
            // Store new cover image
            $result = $this->mediaService->storeFile(
                $request->file('cover_image'),
                'albums/' . $album->id
            );
            
            $album->update([
                'cover_image_path' => $result['path']
            ]);
            
            Log::info('AlbumController@update - Cover image stored:', $result);
        }
        
        // Handle selected cover image from album images
        if ($request->filled('selected_cover_image_id')) {
            Log::info('AlbumController@update - Processing selected cover image');
            
            $selectedImage = AlbumImage::find($request->selected_cover_image_id);
            if ($selectedImage && $selectedImage->album_id === $album->id) {
                $album->update([
                    'cover_image_path' => $selectedImage->path
                ]);
                
                Log::info('AlbumController@update - Selected cover image set:', [
                    'image_id' => $selectedImage->id,
                    'image_path' => $selectedImage->path
                ]);
            }
        }

        Log::info('AlbumController@update - Album updated successfully');
        
        return $this->redirectWithSuccess('albums.show', $album, 'Album updated successfully');
    }

    public function destroy(Album $album): RedirectResponse
    {
        // Check if user owns this album
        $this->authorizeOwnership($album);

        $album->images()->delete();
        $album->delete();

        return $this->redirectWithSuccess('albums.index', [], 'Album deleted successfully');
    }
}
