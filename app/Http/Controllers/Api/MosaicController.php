<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mosaic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MosaicController extends Controller
{
    public function show(Mosaic $mosaic): JsonResponse
    {
        $mosaic->load('items');
        
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
                
                $imageData = null;
                if ($item->type === 'image' && $item->reference_id) {
                    $image = \App\Models\AlbumImage::find($item->reference_id);
                    if ($image) {
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
    }

    public function showByTitle(string $title): JsonResponse
    {
        $mosaic = Mosaic::where('title', $title)->first();
        
        if (!$mosaic) {
            return response()->json(['error' => 'Mosaic not found'], 404);
        }
        
        return $this->show($mosaic);
    }

    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        $user = $this->validateApiKey($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        
        $mosaic = $user->mosaics()->where('title', $title)->first();
        if (!$mosaic) {
            return response()->json(['error' => 'Mosaic not found'], 404);
        }
        
        return $this->show($mosaic);
    }

    private function validateApiKey(Request $request): User|JsonResponse
    {
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
} 