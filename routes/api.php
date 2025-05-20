<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AlbumController;
use App\Http\Controllers\Api\MosaicController;
use App\Models\Album;
use App\Models\Mosaic;
use App\Models\User;

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
Route::get('/albums/{album}', [AlbumController::class, 'show']);
Route::get('/albums/title/{title}', [AlbumController::class, 'showByTitle']);
Route::get('/albums/{title}', [AlbumController::class, 'showByTitleWithApiKey']);
Route::get('/{albumName}', [AlbumController::class, 'showStrapiFormat']);

// Mosaic API endpoints
Route::get('/mosaics/{mosaic}', [MosaicController::class, 'show']);
Route::get('/mosaics/title/{title}', [MosaicController::class, 'showByTitle']);
Route::get('/mosaics/{title}', [MosaicController::class, 'showByTitleWithApiKey']);

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
            // Parse properties to get webp_url and other data
            $properties = is_string($image->properties) ? 
                json_decode($image->properties, true) : 
                ($image->properties ?? []);
                
            return [
                'id' => $image->id,
                'title' => $image->title,
                'description' => $image->description,
                'path' => $image->path,
                'webp_path' => $image->webp_path ?? null,
                'thumbnail_url' => $properties['thumbnail_url'] ?? $image->path,
                'webp_url' => $properties['webp_url'] ?? null,
                'caption' => $image->caption,
                'order' => $image->order,
                'properties' => $image->properties,
                'created_at' => $image->created_at,
                'updated_at' => $image->updated_at
            ];
        })
    ]);
});

// New endpoint to fetch album by title
Route::get('/albums/title/{title}', function ($title) {
    $album = Album::where('title', $title)->first();
    
    if (!$album) {
        return response()->json(['error' => 'Album not found'], 404);
    }
    
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
            // Parse properties to get webp_url and other data
            $properties = is_string($image->properties) ? 
                json_decode($image->properties, true) : 
                ($image->properties ?? []);
                
            return [
                'id' => $image->id,
                'title' => $image->title,
                'description' => $image->description,
                'path' => $image->path,
                'webp_path' => $image->webp_path ?? null,
                'thumbnail_url' => $properties['thumbnail_url'] ?? $image->path,
                'webp_url' => $properties['webp_url'] ?? null,
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

// Mosaic API Endpoints
Route::get('/mosaics/{mosaic}', function (Mosaic $mosaic) {
    // Load the mosaic with its items
    $mosaic->load('items');
    
    // Parse layout settings
    $layoutSettings = [];
    if ($mosaic->layout_settings) {
        try {
            $layoutSettings = is_string($mosaic->layout_settings) ? 
                json_decode($mosaic->layout_settings, true) : 
                $mosaic->layout_settings;
        } catch (\Exception $e) {
            $layoutSettings = [];
        }
    }
    
    return response()->json([
        'mosaic' => [
            'id' => $mosaic->id,
            'title' => $mosaic->title,
            'description' => $mosaic->description,
            'user_id' => $mosaic->user_id,
            'layout_settings' => $layoutSettings,
            'created_at' => $mosaic->created_at,
            'updated_at' => $mosaic->updated_at
        ],
        'items' => $mosaic->items->map(function ($item) {
            // Parse item properties and positions for frontend rendering
            $position = null;
            if ($item->desktop_position) {
                try {
                    $position = is_string($item->desktop_position) ? 
                        json_decode($item->desktop_position, true) : 
                        $item->desktop_position;
                } catch (\Exception $e) {
                    $position = null;
                }
            }
            
            $properties = null;
            if ($item->properties) {
                try {
                    $properties = is_string($item->properties) ? 
                        json_decode($item->properties, true) : 
                        $item->properties;
                } catch (\Exception $e) {
                    $properties = null;
                }
            }
            
            // Get image data if this is an image item
            $imageData = null;
            if ($item->type === 'image' && $item->reference_id) {
                $image = \App\Models\AlbumImage::find($item->reference_id);
                if ($image) {
                    // Parse image properties
                    $imageProperties = [];
                    if ($image->properties) {
                        try {
                            $imageProperties = is_string($image->properties) ? 
                                json_decode($image->properties, true) : 
                                $image->properties;
                        } catch (\Exception $e) {
                            $imageProperties = [];
                        }
                    }
                    
                    $imageData = [
                        'id' => $image->id,
                        'path' => $image->path,
                        'webp_path' => $image->webp_path,
                        'thumbnail_url' => $imageProperties['thumbnail_url'] ?? $image->path,
                        'webp_url' => $imageProperties['webp_url'] ?? null,
                        'title' => $image->title,
                        'caption' => $image->caption,
                        'alt_text' => $image->alt_text
                    ];
                }
            }
            
            return [
                'id' => $item->id,
                'mosaic_id' => $item->mosaic_id,
                'parent_id' => $item->parent_id,
                'type' => $item->type,
                'reference_id' => $item->reference_id,
                'split_direction' => $item->split_direction,
                'position' => $position,
                'properties' => $properties,
                'order' => $item->order,
                'image' => $imageData
            ];
        })
    ]);
});

// Get mosaic by title
Route::get('/mosaics/title/{title}', function ($title) {
    $mosaic = App\Models\Mosaic::where('title', $title)->first();
    
    if (!$mosaic) {
        return response()->json(['error' => 'Mosaic not found'], 404);
    }
    
    // Load the mosaic with its items
    $mosaic->load('items');
    
    // Parse layout settings
    $layoutSettings = [];
    if ($mosaic->layout_settings) {
        try {
            $layoutSettings = is_string($mosaic->layout_settings) ? 
                json_decode($mosaic->layout_settings, true) : 
                $mosaic->layout_settings;
        } catch (\Exception $e) {
            $layoutSettings = [];
        }
    }
    
    return response()->json([
        'mosaic' => [
            'id' => $mosaic->id,
            'title' => $mosaic->title,
            'description' => $mosaic->description,
            'user_id' => $mosaic->user_id,
            'layout_settings' => $layoutSettings,
            'created_at' => $mosaic->created_at,
            'updated_at' => $mosaic->updated_at
        ],
        'items' => $mosaic->items->map(function ($item) {
            // Parse item properties and positions for frontend rendering
            $position = null;
            if ($item->desktop_position) {
                try {
                    $position = is_string($item->desktop_position) ? 
                        json_decode($item->desktop_position, true) : 
                        $item->desktop_position;
                } catch (\Exception $e) {
                    $position = null;
                }
            }
            
            $properties = null;
            if ($item->properties) {
                try {
                    $properties = is_string($item->properties) ? 
                        json_decode($item->properties, true) : 
                        $item->properties;
                } catch (\Exception $e) {
                    $properties = null;
                }
            }
            
            // Get image data if this is an image item
            $imageData = null;
            if ($item->type === 'image' && $item->reference_id) {
                $image = \App\Models\AlbumImage::find($item->reference_id);
                if ($image) {
                    // Parse image properties
                    $imageProperties = [];
                    if ($image->properties) {
                        try {
                            $imageProperties = is_string($image->properties) ? 
                                json_decode($image->properties, true) : 
                                $image->properties;
                        } catch (\Exception $e) {
                            $imageProperties = [];
                        }
                    }
                    
                    $imageData = [
                        'id' => $image->id,
                        'path' => $image->path,
                        'webp_path' => $image->webp_path,
                        'thumbnail_url' => $imageProperties['thumbnail_url'] ?? $image->path,
                        'webp_url' => $imageProperties['webp_url'] ?? null,
                        'title' => $image->title,
                        'caption' => $image->caption,
                        'alt_text' => $image->alt_text
                    ];
                }
            }
            
            return [
                'id' => $item->id,
                'mosaic_id' => $item->mosaic_id,
                'parent_id' => $item->parent_id,
                'type' => $item->type,
                'reference_id' => $item->reference_id,
                'split_direction' => $item->split_direction,
                'position' => $position,
                'properties' => $properties,
                'order' => $item->order,
                'image' => $imageData
            ];
        })
    ]);
});

// Helper function to validate API key
function validateApiKey($request) {
    $apiKey = $request->header('X-API-Key');
    if (!$apiKey) {
        return response()->json(['error' => 'API key is required'], 401);
    }

    $user = User::where('api_key', $apiKey)->first();
    if (!$user) {
        return response()->json(['error' => 'Invalid API key'], 401);
    }

    return $user;
}

// Album by title endpoint
Route::get('/albums/{title}', function ($title, Request $request) {
    $user = validateApiKey($request);
    if ($user instanceof \Illuminate\Http\JsonResponse) {
        return $user;
    }
    
    return $user->albums()->where('title', $title)->with('images')->first();
});

// Mosaic by title endpoint
Route::get('/mosaics/{title}', function ($title, Request $request) {
    $user = validateApiKey($request);
    if ($user instanceof \Illuminate\Http\JsonResponse) {
        return $user;
    }
    
    $mosaic = $user->mosaics()->where('title', $title)->first();
    if (!$mosaic) {
        return response()->json(['error' => 'Mosaic not found'], 404);
    }
    
    $mosaic->load('items');
    return response()->json([
        'mosaic' => $mosaic,
        'items' => $mosaic->items
    ]);
});