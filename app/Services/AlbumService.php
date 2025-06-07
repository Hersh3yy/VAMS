<?php

namespace App\Services;

use App\Models\Album;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AlbumService
{
    /**
     * Get all albums for the current user or for the API
     *
     * @param bool $forApi Whether this is for API (true) or web (false)
     * @return \Illuminate\Database\Eloquent\Collection|Collection
     */
    public function getAllAlbums($forApi = false)
    {
        if ($forApi) {
            // For API, we return all published albums
            return Album::with('coverImage')
                ->withCount('images')
                ->get();
        }
        
        // For web, we only return the user's albums
        /** @var User|null $user */
        $user = Auth::user();
        return $user ? $user->albums()->with('images')->get() : collect();
    }
    
    /**
     * Get a specific album with its images
     *
     * @param string|Album $album Album ID (UUID) or Album instance
     * @param bool $forApi Whether this is for API (true) or web (false)
     * @return Album|null
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getAlbum($album, $forApi = false)
    {
        try {
            if (is_string($album)) {
                $album = Album::findOrFail($album);
            }
            
            if (!$album) {
                return null;
            }
            
            // Load all images for this album
            $album->load('images');
            
            return $album;
        } catch (\Exception $e) {
            Log::error('Error in AlbumService@getAlbum:', [
                'album' => $album,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Format album data for API response
     *
     * @param Album $album
     * @return array
     */
    public function formatAlbumForApi(Album $album)
    {
        return [
            'id' => $album->id,
            'title' => $album->title,
            'description' => $album->description,
            'cover_image_path' => $album->cover_image_path,
            'images_count' => $album->images_count ?? $album->images->count(),
            'user_id' => $album->user_id,
            'created_at' => $album->created_at,
            'updated_at' => $album->updated_at
        ];
    }
    
    /**
     * Format image data for API response
     *
     * @param \App\Models\AlbumImage $image
     * @return array
     */
    public function formatImageForApi($image)
    {
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
    }
    
    /**
     * Format album with images for API response
     *
     * @param Album $album
     * @return array
     */
    public function formatAlbumWithImagesForApi(Album $album)
    {
        return [
            'album' => $this->formatAlbumForApi($album),
            'images' => $album->images->map(fn($image) => $this->formatImageForApi($image))
        ];
    }

    /**
     * Format album for Strapi compatibility
     *
     * @param Album $album
     * @return array
     */
    public function formatAlbumForStrapi(Album $album)
    {
        return $album->images()->orderBy('order')->get()->map(function ($image) {
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
        })->toArray();
    }

    /**
     * Get recent albums for the current user
     *
     * @param int $limit Number of albums to return
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentAlbums($limit = 3)
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return collect();
        }
        
        return $user->albums()
            ->withCount('images')
            ->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Format album data with user display settings
     *
     * @param Album $album
     * @param array|null $userSettings
     * @return array
     */
    public function formatAlbumWithUserSettings($album, $userSettings = null)
    {
        $settings = $userSettings ?? [
            'caption' => true,
            'altText' => true,
            'dateCreated' => true,
            'location' => true,
            'tags' => true,
            'title' => true,
            'author' => true,
            'main_color' => '#4F46E5',
            'secondary_color' => '#10B981',
        ];

        $images = $album->images()->orderBy('order')->get()->map(function ($image) use ($settings) {
            $properties = is_string($image->properties) ? 
                json_decode($image->properties, true) : 
                $image->properties;

            $formattedImage = [
                'id' => $image->id,
                'url' => $image->path,
                'order' => $image->order ?? 0,
            ];

            // Add fields based on user settings
            if ($settings['title'] ?? false) {
                $formattedImage['title'] = $image->title ?? '';
            }
            if ($settings['caption'] ?? false) {
                $formattedImage['caption'] = $image->caption ?? '';
            }
            if ($settings['altText'] ?? false) {
                $formattedImage['alt_text'] = $image->title ?? $image->caption ?? '';
            }
            if ($settings['dateCreated'] ?? false) {
                $formattedImage['date_created'] = $image->created_at->toISOString();
            }
            if ($settings['location'] ?? false) {
                $formattedImage['location'] = $properties['location'] ?? null;
            }
            if ($settings['tags'] ?? false) {
                $formattedImage['tags'] = $properties['tags'] ?? [];
            }
            if ($settings['author'] ?? false) {
                $formattedImage['author'] = $properties['author'] ?? $album->user->name ?? '';
            }

            return $formattedImage;
        });

        return [
            'id' => $album->id,
            'title' => $album->title,
            'description' => $album->description,
            'created_at' => $album->created_at->toISOString(),
            'updated_at' => $album->updated_at->toISOString(),
            'images' => $images,
            'images_count' => $images->count(),
            'display_settings' => [
                'main_color' => $settings['main_color'] ?? '#4F46E5',
                'secondary_color' => $settings['secondary_color'] ?? '#10B981',
            ],
        ];
    }
}