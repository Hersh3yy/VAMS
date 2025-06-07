<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\AlbumImage;
use App\Services\AlbumService;
use App\Services\ImageService;
use App\Http\Controllers\Api\Traits\HandlesApiOperations;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AlbumController extends Controller
{
    use HandlesApiOperations;
    
    protected $albumService;
    protected $imageService;
    
    public function __construct(AlbumService $albumService, ImageService $imageService)
    {
        $this->albumService = $albumService;
        $this->imageService = $imageService;
    }
    
    public function show(Request $request, Album $album): JsonResponse
    {
        // Get album with images
        $album = $this->albumService->getAlbum($album, true);
        if (!$album) {
            return $this->handleNotFound('Album not found');
        }
        
        // Format response
        return $this->handleSuccess(
            $this->albumService->formatAlbumWithImagesForApi($album)
        );
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->validateApiKey($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        
        $albums = $user->albums()->with('images')->get();
        
        return $this->handleSuccess([
            'albums' => $albums->map(fn($album) => $this->albumService->formatAlbumForApi($album))
        ]);
    }

    public function showByTitle(string $title): JsonResponse
    {
        $album = Album::where('title', $title)->first();
        
        if (!$album) {
            return $this->handleNotFound('Album not found');
        }
        
        return $this->show(request(), $album);
    }

    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();
        
        $album = $user->albums()->where('title', $title)->with('images')->first();
        if (!$album) {
            return $this->handleNotFound('Album not found');
        }
        
        return $this->show($request, $album);
    }

    public function showStrapiFormat(string $albumName): JsonResponse
    {
        $album = Album::where('title', $albumName)->first();
        if (!$album) {
            return $this->handleNotFound('Album not found');
        }
        
        return $this->handleSuccess(
            $this->albumService->formatAlbumForStrapi($album)
        );
    }

    // CRUD Operations

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $album = Album::create([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => Auth::id(),
        ]);

        return $this->handleSuccess([
            'album' => $this->albumService->formatAlbumForApi($album),
            'message' => 'Album created successfully'
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $album = Album::find($id);
        if (!$album || $album->user_id !== Auth::id()) {
            return $this->handleNotFound('Album not found');
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $album->update($request->only(['title', 'description']));

        return $this->handleSuccess([
            'album' => $this->albumService->formatAlbumForApi($album),
            'message' => 'Album updated successfully'
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $album = Album::find($id);
        if (!$album || $album->user_id !== Auth::id()) {
            return $this->handleNotFound('Album not found');
        }

        $album->delete();

        return $this->handleSuccess(['message' => 'Album deleted successfully']);
    }

    // Album Image Operations

    public function storeImage(Request $request, $albumId): JsonResponse
    {
        $album = Album::find($albumId);
        if (!$album || $album->user_id !== Auth::id()) {
            return $this->handleNotFound('Album not found');
        }

        $request->validate([
            'file' => 'required|image|max:10240', // 10MB
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string',
        ]);

        try {
            $result = $this->imageService->storeImage(
                $request->file('file'),
                "albums/{$album->id}"
            );

            $image = AlbumImage::create([
                'album_id' => $album->id,
                'path' => $result['url'],
                'title' => $request->title,
                'caption' => $request->caption,
                'order' => $album->images()->count(),
                'properties' => [
                    'thumbnail_url' => $result['url'],
                    'type' => 'image'
                ]
            ]);

            return $this->handleSuccess([
                'image' => $image,
                'message' => 'Image uploaded successfully'
            ], 201);
        } catch (\Exception $e) {
            return $this->handleError('Upload failed: ' . $e->getMessage(), 422);
        }
    }

    public function reorderImages(Request $request, $albumId): JsonResponse
    {
        $album = Album::find($albumId);
        if (!$album || $album->user_id !== Auth::id()) {
            return $this->handleNotFound('Album not found');
        }

        $request->validate([
            'from_index' => 'required|integer|min:0',
            'to_index' => 'required|integer|min:0',
        ]);

        $images = $album->images()->orderBy('order')->get();
        
        if ($request->from_index >= $images->count() || $request->to_index >= $images->count()) {
            return $this->handleError('Invalid index provided', 422);
        }

        // Reorder logic
        $item = $images->splice($request->from_index, 1)->first();
        $images->splice($request->to_index, 0, [$item]);
        
        DB::transaction(function () use ($images) {
            foreach ($images as $index => $image) {
                $image->order = $index;
                $image->save();
            }
        });

        return $this->handleSuccess(['message' => 'Images reordered successfully']);
    }

    public function updateImage(Request $request, $albumId, $imageId): JsonResponse
    {
        $album = Album::find($albumId);
        if (!$album || $album->user_id !== Auth::id()) {
            return $this->handleNotFound('Album not found');
        }

        $image = $album->images()->find($imageId);
        if (!$image) {
            return $this->handleNotFound('Image not found');
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string',
        ]);

        $image->update($request->only(['title', 'caption']));

        return $this->handleSuccess([
            'image' => $image,
            'message' => 'Image updated successfully'
        ]);
    }

    public function destroyImage($albumId, $imageId): JsonResponse
    {
        $album = Album::find($albumId);
        if (!$album || $album->user_id !== Auth::id()) {
            return $this->handleNotFound('Album not found');
        }

        $image = $album->images()->find($imageId);
        if (!$image) {
            return $this->handleNotFound('Image not found');
        }

        $image->delete();

        return $this->handleSuccess(['message' => 'Image deleted successfully']);
    }

    // Public API methods with user display settings
    public function showWithApiKey(Request $request, $id): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();

        $album = $user->albums()->with('images')->find($id);
        if (!$album) {
            return $this->handleNotFound('Album not found');
        }

        return $this->handleSuccess(
            $this->albumService->formatAlbumWithUserSettings($album, $user->album_display_settings)
        );
    }

    public function userAlbums(): JsonResponse
    {
        $albums = Auth::user()->albums()->with('images')->get();
        
        return $this->handleSuccess([
            'albums' => $albums->map(fn($album) => $this->albumService->formatAlbumForApi($album))
        ]);
    }
} 