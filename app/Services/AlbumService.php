<?php

namespace App\Services;

use App\Models\Album;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
     * @param int|Album $album Album ID or Album instance
     * @param bool $forApi Whether this is for API (true) or web (false)
     * @return Album|null
     */
    public function getAlbum($album, $forApi = false)
    {
        if (is_numeric($album)) {
            $album = Album::findOrFail($album);
        }
        
        if (!$album) {
            return null;
        }
        
        // Load all images for this album
        $album->load('images');
        
        return $album;
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
     * Format album with images for API response
     *
     * @param Album $album
     * @return array
     */
    public function formatAlbumWithImagesForApi(Album $album)
    {
        return [
            'album' => $this->formatAlbumForApi($album),
            'images' => $album->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'title' => $image->title,
                    'description' => $image->description,
                    'path' => $image->path,
                    'webp_path' => $image->webp_path ?? null,
                    'order' => $image->order,
                    'properties' => $image->properties,
                    'created_at' => $image->created_at,
                    'updated_at' => $image->updated_at
                ];
            })
        ];
    }
} 