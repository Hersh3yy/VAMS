<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\AlbumImageResource;
use App\Http\Resources\AlbumResource;
use App\Models\Album;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * READ-ONLY API for external frontends using API keys
 * All CRUD operations are handled by the web frontend
 */
class AlbumController extends BaseApiController
{
    /**
     * Get all albums for the authenticated user (API key)
     */
    public function indexWithApiKey(Request $request): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();

        // Check if we should include images
        $withImages = $request->boolean('with_images', false);

        // Build query with count - only published albums for API
        $query = $user->albums()->published()->withCount(['images' => fn (Builder $query): Builder => $query->published()]);

        // Optionally load images if requested (only published)
        if ($withImages) {
            $query->with(['images' => fn (HasMany $query): HasMany => $query->published()->orderBy('order')]);
        }

        $albums = $query->get();

        $formattedAlbums = $albums->map(function (Album $album) use ($withImages): array {
            $albumData = AlbumResource::make($album)->resolve();

            if ($withImages && $album->relationLoaded('images')) {
                $albumData['images'] = AlbumImageResource::collection($album->images)->resolve();
            }

            return $albumData;
        });

        return $this->success([
            'albums' => $formattedAlbums,
        ], 'Albums retrieved successfully');
    }

    /**
     * Get specific album by ID (API key)
     */
    public function showWithApiKey(Request $request, string $id): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();

        $album = $user->albums()->published()->with(['images' => fn (HasMany $query): HasMany => $query->published()->orderBy('order')])->find($id);
        if (! $album) {
            return $this->notFound('Album not found');
        }

        return $this->success(
            $this->albumPayload($album),
            'Album retrieved successfully'
        );
    }

    /**
     * Get specific album by title (API key)
     *
     * Supports:
     * - Exact title match: /albums/by-title/Live%20Music (URL-encoded)
     * - Exact title match: /albums/by-title/Live Music (Laravel auto-decodes)
     * - Case-insensitive matching for better UX
     */
    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->albums()
            ->published()
            ->with(['images' => fn (HasMany $query): HasMany => $query->published()->orderBy('order')]);

        $album = $this->findByTitle($title, $query);

        if (! $album) {
            return $this->notFound('Album not found');
        }

        return $this->success(
            $this->albumPayload($album),
            'Album retrieved successfully'
        );
    }

    /**
     * @return array{album: array<string, mixed>, images: mixed}
     */
    private function albumPayload(Album $album): array
    {
        return [
            'album' => AlbumResource::make($album)->resolve(),
            'images' => AlbumImageResource::collection($album->images)->resolve(),
        ];
    }
}
