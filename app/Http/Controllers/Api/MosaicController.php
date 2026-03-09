<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Services\MosaicService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * READ-ONLY API for external frontends using API keys
 * All CRUD operations are handled by the web frontend
 */
class MosaicController extends BaseApiController
{
    public function __construct(
        private readonly MosaicService $mosaicService,
    ) {}

    /**
     * Get all mosaics for the authenticated user (API key)
     *
     * Query params: per_page (int, optional for pagination)
     */
    public function indexWithApiKey(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->filled('per_page')
            ? min((int) $request->integer('per_page', 15), 100)
            : null;

        $result = $this->mosaicService->getMosaicsForApi($user, $perPage);

        if ($result instanceof LengthAwarePaginator) {
            return $this->successPaginated($result, 'mosaics', fn ($m) => $m, 'Mosaics retrieved successfully');
        }

        return $this->success([
            'mosaics' => $result->values()->all(),
        ], 'Mosaics retrieved successfully');
    }

    /**
     * Get specific mosaic by ID (API key)
     */
    public function showWithApiKey(Request $request, $id): JsonResponse
    {
        $data = $this->mosaicService->getMosaicForApi($request->user(), (string) $id);
        if (! $data) {
            return $this->notFound('Mosaic not found');
        }

        return $this->success($data, 'Mosaic retrieved successfully');
    }

    /**
     * Get specific mosaic by title (API key)
     *
     * Supports:
     * - Exact title match: /mosaics/by-title/Live%20Music (URL-encoded)
     * - Exact title match: /mosaics/by-title/Live Music (Laravel auto-decodes)
     * - Case-insensitive matching for better UX
     */
    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        $data = $this->mosaicService->getMosaicByTitleForApi($request->user(), $title);
        if (! $data) {
            return $this->notFound('Mosaic not found');
        }

        return $this->success($data, 'Mosaic retrieved successfully');
    }
}
