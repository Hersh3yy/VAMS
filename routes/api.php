<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Album;
use App\Services\AlbumService;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Album API endpoint for fetching images - no authentication required for now
Route::get('/albums/{album}', function (Album $album) {
    // Load the album with its images
    $album->load('images');
    
    return response()->json([
        'album' => [
            'id' => $album->id,
            'title' => $album->title,
            'description' => $album->description,
            'cover_image_path' => $album->cover_image_path,
            'user_id' => $album->user_id,
            'created_at' => $album->created_at,
            'updated_at' => $album->updated_at
        ],
        'images' => $album->images->map(function ($image) {
            return [
                'id' => $image->id,
                'title' => $image->title,
                'description' => $image->description,
                'path' => $image->path,
                'webp_path' => $image->webp_path ?? null,
                'caption' => $image->caption,
                'order' => $image->order,
                'properties' => $image->properties,
                'created_at' => $image->created_at,
                'updated_at' => $image->updated_at
            ];
        })
    ]);
});

// Album by title endpoint for Strapi compatibility
Route::get('/{albumName}', function ($albumName) {
    $album = Album::where('title', $albumName)->first();
    if (!$album) {
        return response()->json(['error' => 'Album not found'], 404);
    }
    
    $images = $album->images()->orderBy('order')->get()->map(function ($image) {
        $properties = is_string($image->properties) ? 
            json_decode($image->properties, true) : 
            $image->properties;
            
        // Format response to match Strapi format
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
    
    return $images;
});