<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Album;
use App\Services\AlbumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * READ-ONLY API for external frontends using API keys
 * All CRUD operations are handled by the web frontend
 */
class AlbumController extends BaseApiController
{
    public function __construct(
        protected readonly AlbumService $albumService,
    ) {}

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
        $query = $user->albums()->published()->withCount(['images' => fn ($query) => $query->published()]);

        // Optionally load images if requested (only published)
        if ($withImages) {
            $query->with(['images' => fn ($query) => $query->published()->orderBy('order')]);
        }

        $albums = $query->get();

        // Format albums with or without images based on request
        $formattedAlbums = $albums->map(function (Album $album) use ($withImages) {
            $albumData = $this->albumService->formatAlbumForApi($album);

            // Include images if requested
            if ($withImages && $album->relationLoaded('images')) {
                $albumData['images'] = $album->images->map(fn ($image) => $this->albumService->formatImageForApi($image));
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

        $album = $user->albums()->published()->with(['images' => fn ($query) => $query->published()->orderBy('order')])->find($id);
        if (! $album) {
            return $this->notFound('Album not found');
        }

        return $this->success(
            $this->albumService->formatAlbumWithImagesForApi($album),
            'Album retrieved successfully'
        );
    }

    /**
     * Get specific album by title (API key)
     */
    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();

        $album = $user->albums()->published()->where('title', $title)->with(['images' => fn ($query) => $query->published()->orderBy('order')])->first();
        if (! $album) {
            return $this->notFound('Album not found');
        }

        return $this->success(
            $this->albumService->formatAlbumWithImagesForApi($album),
            'Album retrieved successfully'
        );
    }
}
