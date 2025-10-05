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

        $albums = $user->albums()->with('images')->get();

        return $this->success([
            'albums' => $albums->map(fn (Album $album) => $this->albumService->formatAlbumForApi($album)),
        ]);
    }

    /**
     * Get specific album by ID (API key)
     */
    public function showWithApiKey(Request $request, string $id): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();

        $album = $user->albums()->with('images')->find($id);
        if (! $album) {
            return $this->notFound('Album not found');
        }

        return $this->success(
            $this->albumService->formatAlbumWithImagesForApi($album)
        );
    }

    /**
     * Get specific album by title (API key)
     */
    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();

        $album = $user->albums()->where('title', $title)->with('images')->first();
        if (! $album) {
            return $this->notFound('Album not found');
        }

        return $this->success(
            $this->albumService->formatAlbumWithImagesForApi($album)
        );
    }
}
