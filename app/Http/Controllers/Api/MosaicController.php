<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\MosaicItemResource;
use App\Http\Resources\MosaicResource;
use App\Models\Mosaic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * READ-ONLY API for external frontends using API keys
 * All CRUD operations are handled by the web frontend
 */
class MosaicController extends BaseApiController
{
    /**
     * Get all mosaics for the authenticated user (API key)
     */
    public function indexWithApiKey(Request $request): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();

        $mosaics = $user->mosaics()->get();

        return $this->success([
            'mosaics' => MosaicResource::collection($mosaics)->resolve(),
        ], 'Mosaics retrieved successfully');
    }

    /**
     * Get specific mosaic by ID (API key)
     */
    public function showWithApiKey(Request $request, string $id): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();

        $mosaic = $user->mosaics()->find($id);
        if (! $mosaic) {
            return $this->notFound('Mosaic not found');
        }

        return $this->success(
            $this->mosaicPayload($mosaic),
            'Mosaic retrieved successfully'
        );
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
        $user = $request->user();

        $mosaic = $this->findByTitle($title, $user->mosaics());

        if (! $mosaic) {
            return $this->notFound('Mosaic not found');
        }

        return $this->success(
            $this->mosaicPayload($mosaic),
            'Mosaic retrieved successfully'
        );
    }

    /**
     * @return array{mosaic: array<string, mixed>, items: mixed}
     */
    private function mosaicPayload(Mosaic $mosaic): array
    {
        return [
            'mosaic' => MosaicResource::make($mosaic)->resolve(),
            'items' => MosaicItemResource::collection($mosaic->items)->resolve(),
        ];
    }
}
