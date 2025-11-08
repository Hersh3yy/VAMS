<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Mosaic;
use App\Services\MosaicService;
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
     */
    public function indexWithApiKey(Request $request): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();

        $mosaics = $user->mosaics()->get();

        return $this->success([
            'mosaics' => $mosaics->map(fn (Mosaic $mosaic) => $this->mosaicService->formatMosaicForApi($mosaic)),
        ], 'Mosaics retrieved successfully');
    }

    /**
     * Get specific mosaic by ID (API key)
     */
    public function showWithApiKey(Request $request, $id): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();

        $mosaic = $user->mosaics()->find($id);
        if (! $mosaic) {
            return $this->notFound('Mosaic not found');
        }

        return $this->success(
            $this->mosaicService->formatMosaicWithItemsForApi($mosaic),
            'Mosaic retrieved successfully'
        );
    }

    /**
     * Get specific mosaic by title (API key)
     */
    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();

        $mosaic = $user->mosaics()->where('title', $title)->first();
        if (! $mosaic) {
            return $this->notFound('Mosaic not found');
        }

        return $this->success(
            $this->mosaicService->formatMosaicWithItemsForApi($mosaic),
            'Mosaic retrieved successfully'
        );
    }
}
