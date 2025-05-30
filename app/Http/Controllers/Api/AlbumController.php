<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\AlbumService;
use App\Http\Controllers\Api\Traits\HandlesApiOperations;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AlbumController extends Controller
{
    use HandlesApiOperations;
    
    protected $albumService;
    
    public function __construct(AlbumService $albumService)
    {
        $this->albumService = $albumService;
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
        $user = $this->validateApiKey($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        
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
} 