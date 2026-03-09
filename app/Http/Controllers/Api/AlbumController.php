<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Services\AlbumService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     *
     * Query params: with_images (bool), per_page (int, optional for pagination)
     */
    public function indexWithApiKey(Request $request): JsonResponse
    {
        $user = $request->user();
        $withImages = $request->boolean('with_images', false);
        $perPage = $request->filled('per_page')
            ? min((int) $request->integer('per_page', 15), 100)
            : null;

        $result = $this->albumService->getAlbumsForApi($user, $withImages, $perPage);

        if ($result instanceof LengthAwarePaginator) {
            return $this->successPaginated($result, 'albums', fn ($a) => $a, 'Albums retrieved successfully');
        }

        return $this->success([
            'albums' => $result->values()->all(),
        ], 'Albums retrieved successfully');
    }

    /**
     * Get specific album by ID (API key)
     */
    public function showWithApiKey(Request $request, string $id): JsonResponse
    {
        $data = $this->albumService->getAlbumForApi($request->user(), $id);
        if (! $data) {
            return $this->notFound('Album not found');
        }

        return $this->success($data, 'Album retrieved successfully');
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
        $data = $this->albumService->getAlbumByTitleForApi($request->user(), $title);
        if (! $data) {
            return $this->notFound('Album not found');
        }

        return $this->success($data, 'Album retrieved successfully');
    }
}
