<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Album;

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

// Album API endpoints
Route::get('/albums', function () {
    return Album::with('images')->get();
});

Route::get('/albums/{album}', function (Album $album) {
    return $album->load('images');
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