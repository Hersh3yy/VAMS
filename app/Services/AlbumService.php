<?php

declare(strict_types=1);

namespace App\Services;

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
     */
    public function formatAlbumForApi(Album $album): array
    {
        return [
            'id' => $album->id,
            'title' => $album->title,
            'description' => $album->description,
            'cover_image_path' => $album->cover_image_path,
            'images_count' => $album->images_count ?? $album->images->count(),
            'user_id' => $album->user_id,
            'published' => $album->published,
            'created_at' => $album->created_at?->toISOString(),
            'updated_at' => $album->updated_at?->toISOString(),
        ];
    }

    /**
     * Format image data for API response
     */
    public function formatImageForApi(AlbumImage $image): array
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
            'published' => $image->published,
            'properties' => $properties,
            'created_at' => $image->created_at?->toISOString(),
            'updated_at' => $image->updated_at?->toISOString(),
        ];
    }

    /**
     * Format album with images for API response
     */
    public function formatAlbumWithImagesForApi(Album $album): array
    {
        return [
            'album' => $this->formatAlbumForApi($album),
            'images' => $album->images->map(fn (AlbumImage $image) => $this->formatImageForApi($image)),
        ];
    }

    /**
     * Format album for Strapi compatibility
     */
    public function formatAlbumForStrapi(Album $album): array
    {
        return $album->images()
            ->orderBy('order')
            ->get()
            ->map(fn (AlbumImage $image) => [
                'id' => $image->id,
                'created_at' => $image->created_at?->toISOString(),
                'updated_at' => $image->updated_at?->toISOString(),
                'Name' => $image->title ?? 'Untitled',
                'Order' => $image->order ?? 0,
                'Caption' => $image->caption ?? '',
                'Year' => (is_string($image->properties) ? json_decode($image->properties, true) : ($image->properties ?? []))['year'] ?? null,
                'Image' => [
                    'id' => $image->id,
                    'url' => $image->path,
                    'formats' => [
                        'thumbnail' => [
                            'url' => $image->path,
                        ],
                    ],
                ],
            ])
            ->toArray();
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
     * Format album data with user display settings
     */
    public function formatAlbumWithUserSettings(Album $album, ?array $userSettings = null): array
    {
        $defaultSettings = [
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

        $settings = $userSettings ?? $defaultSettings;

        $images = $album->images()
            ->orderBy('order')
            ->get()
            ->map(function (AlbumImage $image) use ($settings, $album) {
                $properties = is_string($image->properties) ?
                    json_decode($image->properties, true) :
                    ($image->properties ?? []);

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
                    $formattedImage['date_created'] = $image->created_at?->toISOString();
                }
                if ($settings['location'] ?? false) {
                    $formattedImage['location'] = $properties['location'] ?? null;
                }
                if ($settings['tags'] ?? false) {
                    $formattedImage['tags'] = $properties['tags'] ?? [];
                }
                if ($settings['author'] ?? false) {
                    $formattedImage['author'] = $properties['author'] ?? $album->user?->name ?? '';
                }

                return $formattedImage;
            });

        return [
            'id' => $album->id,
            'title' => $album->title,
            'description' => $album->description,
            'created_at' => $album->created_at?->toISOString(),
            'updated_at' => $album->updated_at?->toISOString(),
            'images' => $images->toArray(),
            'images_count' => $images->count(),
            'display_settings' => [
                'main_color' => $settings['main_color'] ?? '#4F46E5',
                'secondary_color' => $settings['secondary_color'] ?? '#10B981',
            ],
        ];
    }

    /**
     * Format entity with its media for API response
     */
    public function formatWithMediaForApi(Model $entity): array
    {
        return [
            'album' => $this->formatForApi($entity),
            'images' => $entity->images->map(fn (AlbumImage $image) => $this->formatImageForApi($image)),
        ];
    }
}
