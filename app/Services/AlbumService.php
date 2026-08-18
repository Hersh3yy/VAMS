<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\AlbumImageResource;
use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Models\AlbumImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AlbumService extends BaseEntityService
{
    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Album::class;
    }

    /**
     * Get all albums for the current user or for the API
     */
    public function getAll(bool $forApi = false): Collection
    {
        if ($forApi) {
            // For API, only return published albums
            return Album::published()
                ->with(['images' => fn ($query) => $query->published()->orderBy('order')])
                ->withCount(['images' => fn ($query) => $query->published()])
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        // For web, return all user's albums (published and unpublished)
        $user = Auth::user();
        if (! $user instanceof User) {
            return new Collection;
        }

        return $user->albums()
            ->with(['images' => fn ($query) => $query->orderBy('order')])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get a specific album with its images
     */
    public function getById(string|Model $entity, bool $forApi = false): ?Model
    {
        $album = $entity instanceof Album ? $entity : (string) $entity;

        if ($forApi) {
            // For API, only return if published
            if (is_string($album)) {
                $album = Album::published()->find($album);
            } elseif ($album instanceof Album && ! $album->published) {
                return null;
            }

            if (! $album) {
                return null;
            }

            // Load relationships - only published images for API
            $album->load(['images' => fn ($query) => $query->published()->orderBy('order')]);

            return $album;
        }

        // For web, return regardless of published status
        if (is_string($album)) {
            $album = Album::findOrFail($album);
        }

        if (! $album instanceof Album) {
            return null;
        }

        $album->load(['images' => fn ($query) => $query->orderBy('order')]);

        return $album;
    }

    /**
     * Format album data for API response
     *
     * @return array<string, mixed>
     */
    public function formatAlbumForApi(Album $album): array
    {
        return AlbumResource::make($album)->resolve();
    }

    /**
     * Format image data for API response
     *
     * @return array<string, mixed>
     */
    public function formatImageForApi(AlbumImage $image): array
    {
        return AlbumImageResource::make($image)->resolve();
    }

    /**
     * Format album with images for API response
     *
     * @return array{album: array<string, mixed>, images: mixed}
     */
    public function formatAlbumWithImagesForApi(Album $album): array
    {
        return [
            'album' => $this->formatAlbumForApi($album),
            'images' => AlbumImageResource::collection($album->images)->resolve(),
        ];
    }

    /**
     * Get recent albums for the current user
     */
    public function getRecentAlbums(int $limit = 3): Collection
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return new Collection;
        }

        return $user->albums()
            ->withCount('images')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Format entity with its media for API response
     *
     * @return array{album: array<string, mixed>, images: mixed}
     */
    public function formatWithMediaForApi(Model $entity): array
    {
        if (! $entity instanceof Album) {
            return [];
        }

        return $this->formatAlbumWithImagesForApi($entity);
    }
}
