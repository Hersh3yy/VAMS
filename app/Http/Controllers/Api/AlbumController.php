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
        // Validate API key
        $user = $this->validateApiKey($request);
        if (!$user) {
            return response()->json(['error' => 'Invalid API key'], 401, [], JSON_UNESCAPED_UNICODE);
        }
        
        // Get album with images
        $album = $this->albumService->getAlbum($album, true);
        if (!$album) {
            return response()->json(['error' => 'Album not found'], 404, [], JSON_UNESCAPED_UNICODE);
        }
        
        // Format response
        return response()->json($this->albumService->formatAlbumWithImagesForApi($album), 200, [], JSON_UNESCAPED_UNICODE);
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
        $user = $this->validateApiKey($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        
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
        
        $images = $album->images()->orderBy('order')->get()->map(function ($image) {
            $properties = is_string($image->properties) ? 
                json_decode($image->properties, true) : 
                $image->properties;
                
            return [
                'id' => $image->id,
                'created_at' => $image->created_at,
                'updated_at' => $image->updated_at,
                'Name' => $image->title ?? 'Untitled',
                'Order' => $image->order ?? 0,
                'Caption' => $image->caption ?? '',
                'Year' => $properties['year'] ?? null,
                'Image' => [
                    'id' => $image->id,
                    'url' => $image->path,
                    'formats' => [
                        'thumbnail' => [
                            'url' => $image->path
                        ]
                    ]
                ]
            ];
        });
        
        return response()->json($images);
    }
} 