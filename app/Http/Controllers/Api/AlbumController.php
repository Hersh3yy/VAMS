<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\AlbumService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\Traits\ValidatesApiKey;

class AlbumController extends Controller
{
    use ValidatesApiKey;
    
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
            return response()->json(['error' => 'Album not found'], 404, [], JSON_UNESCAPED_UNICODE);
        }
        
        // Format response
        return response()->json($this->albumService->formatAlbumWithImagesForApi($album), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function index(Request $request): JsonResponse
    {
        // User is already authenticated via middleware
        $user = $request->user();
        
        $albums = $user->albums()->with('images')->get();
        
        return response()->json([
            'albums' => $albums->map(fn($album) => $this->albumService->formatAlbumForApi($album))
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function showByTitle(string $title): JsonResponse
    {
        $album = Album::where('title', $title)->first();
        
        if (!$album) {
            return response()->json(['error' => 'Album not found'], 404);
        }
        
        return $this->show(request(), $album);
    }

    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        // User is already authenticated via middleware
        $user = $request->user();
        
        $album = $user->albums()->where('title', $title)->with('images')->first();
        if (!$album) {
            return response()->json(['error' => 'Album not found'], 404);
        }
        
        return $this->show($request, $album);
    }

    public function showStrapiFormat(string $albumName): JsonResponse
    {
        $album = Album::where('title', $albumName)->first();
        if (!$album) {
            return response()->json(['error' => 'Album not found'], 404);
        }
        
        return response()->json($this->albumService->formatAlbumForStrapi($album));
    }
} 