<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Models\AlbumImage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class MosaicService
{
    /**
     * Get all mosaics for the current user or for the API
     *
     * @param bool $forApi Whether this is for API (true) or web (false)
     * @return \Illuminate\Database\Eloquent\Collection|Collection
     */
    public function getAllMosaics(bool $forApi = false): EloquentCollection|Collection
    {
        if ($forApi) {
            // For API, we return all published mosaics
            return Mosaic::with(['items'])
                ->get();
        }
        
        // For web, we only return the user's mosaics
        /** @var User|null $user */
        $user = Auth::user();
        return $user ? $user->mosaics()->with('items')->get() : collect();
    }
    
    /**
     * Get a specific mosaic with its items
     *
     * @param Mosaic $mosaic Mosaic instance
     * @param bool $forApi Whether this is for API (true) or web (false)
     * @return Mosaic|null
     */
    public function getMosaic(Mosaic $mosaic, bool $forApi = false): ?Mosaic
    {
        if (!$mosaic) {
            return null;
        }
        
        // Load all items for this mosaic
        $mosaic->load('items');
        
        return $mosaic;
    }
    
    /**
     * Create a new mosaic
     */
    public function createMosaic(array $data): Mosaic
    {
        /** @var User $user */
        $user = Auth::user();
        
        return $user->mosaics()->create([
            'id' => Str::uuid(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'columns' => $data['columns'] ?? 3,
            'display_settings' => $data['display_settings'] ?? null,
        ]);
    }
    
    /**
     * Update an existing mosaic
     */
    public function updateMosaic(Mosaic $mosaic, array $data): Mosaic
    {
        $mosaic->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? $mosaic->description,
            'columns' => $data['columns'] ?? $mosaic->columns,
            'display_settings' => $data['display_settings'] ?? $mosaic->display_settings,
        ]);
        
        return $mosaic->fresh();
    }
    
    /**
     * Delete a mosaic and its items
     */
    public function deleteMosaic(Mosaic $mosaic): void
    {
        $mosaic->items()->delete();
        $mosaic->delete();
    }
    
    /**
     * Format mosaic data for API response
     *
     * @param Mosaic $mosaic
     * @return array
     */
    public function formatMosaicForApi(Mosaic $mosaic): array
    {
        return [
            'id' => $mosaic->id,
            'title' => $mosaic->title,
            'description' => $mosaic->description,
            'columns' => $mosaic->columns,
            'display_settings' => $mosaic->display_settings,
            'user_id' => $mosaic->user_id,
            'created_at' => $mosaic->created_at,
            'updated_at' => $mosaic->updated_at
        ];
    }
    
    /**
     * Format mosaic item data for API response
     *
     * @param MosaicItem $item
     * @return array
     */
    public function formatMosaicItemForApi(MosaicItem $item): array
    {
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
        
        $images = [];
        if ($item->type === 'image') {
            // Handle multiple images in content array
            $content = is_string($item->content) ? json_decode($item->content, true) : ($item->content ?? []);
            foreach ($content as $imagePath) {
                $image = AlbumImage::where('path', $imagePath)->first();
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
                    
                    $images[] = [
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
        } elseif ($item->type === 'album' && $item->album) {
            // Handle album images
            foreach ($item->album->images as $image) {
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
                
                $images[] = [
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
            'column_index' => $item->column_index,
            'type' => $item->type,
            'content' => $item->content,
            'album_id' => $item->album_id,
            'properties' => $properties,
            'order' => $item->order,
            'is_active' => $item->is_active,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'images' => $images
        ];
    }
    
    /**
     * Format mosaic with items for API response
     *
     * @param Mosaic $mosaic
     * @return array
     */
    public function formatMosaicWithItemsForApi(Mosaic $mosaic): array
    {
        return [
            'mosaic' => $this->formatMosaicForApi($mosaic),
            'items' => $mosaic->items->map(fn($item) => $this->formatMosaicItemForApi($item))
        ];
    }
    
    /**
     * Get recent mosaics for the current user
     *
     * @param int $limit Number of mosaics to return
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentMosaics($limit = 3)
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return collect();
        }
        
        return $user->mosaics()
            ->with(['items' => function ($query) {
                $query->orderBy('column_index')
                      ->orderBy('order');
            }])
            ->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get();
    }
    
    /**
     * Get a mosaic with its items
     *
     * @param Mosaic $mosaic
     * @param bool $forApi
     * @return Mosaic
     */
    public function getMosaicWithItems(Mosaic $mosaic, bool $forApi = false): Mosaic
    {
        return $mosaic->load(['items' => function ($query) {
            $query->orderBy('column_index')
                  ->orderBy('order');
        }]);
    }
    
    /**
     * Split a mosaic item into two items
     *
     * @param MosaicItem $mosaicItem
     * @return MosaicItem The newly created item
     */
    public function splitItem(MosaicItem $mosaicItem): MosaicItem
    {
        // Get all items in the same column with higher order
        $itemsToUpdate = MosaicItem::where('mosaic_id', $mosaicItem->mosaic_id)
            ->where('column_index', $mosaicItem->column_index)
            ->where('order', '>', $mosaicItem->order)
            ->orderBy('order')
            ->get();

        // Increment the order of all affected items
        foreach ($itemsToUpdate as $item) {
            $item->update(['order' => $item->order + 1]);
        }

        // Create a new empty text item after the current one
        return MosaicItem::create([
            'id' => Str::uuid(),
            'mosaic_id' => $mosaicItem->mosaic_id,
            'column_index' => $mosaicItem->column_index,
            'type' => 'text',
            'content' => '',
            'properties' => null,
            'order' => $mosaicItem->order + 1,
            'is_active' => true,
        ]);
    }
}