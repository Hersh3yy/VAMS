<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Services\MosaicService;
use App\Http\Controllers\Api\Traits\HandlesApiOperations;
use App\Http\Controllers\Api\Traits\ValidatesApiKey;
use App\Http\Requests\StoreMosaicRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MosaicController extends BaseApiController
{
    use HandlesApiOperations, ValidatesApiKey;

    private readonly MosaicService $mosaicService;

    public function __construct(MosaicService $mosaicService)
    {
        $this->mosaicService = $mosaicService;
    }

    public function index(Request $request): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();
        
        $mosaics = $this->mosaicService->getAllMosaics(true);
        
        return $this->success([
            'mosaics' => $mosaics->map(fn($mosaic) => $this->mosaicService->formatMosaicForApi($mosaic))
        ]);
    }

    public function show(Request $request, Mosaic $mosaic): JsonResponse
    {
        $mosaic = $this->mosaicService->getMosaic($mosaic, true);
        if (!$mosaic) {
            return $this->notFound('Mosaic not found');
        }
        
        return $this->success(
            $this->mosaicService->formatMosaicWithItemsForApi($mosaic)
        );
    }

    public function showByTitle(string $title): JsonResponse
    {
        $mosaic = Mosaic::where('title', $title)->first();
        
        if (!$mosaic) {
            return $this->notFound('Mosaic not found');
        }
        
        return $this->show(request(), $mosaic);
    }

    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();
        
        $mosaic = $user->mosaics()->where('title', $title)->first();
        if (!$mosaic) {
            return $this->notFound('Mosaic not found');
        }
        
        return $this->show($request, $mosaic);
    }

    public function showWithApiKey(Request $request, $id): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();

        $mosaic = $user->mosaics()->find($id);
        if (!$mosaic) {
            return $this->notFound('Mosaic not found');
        }

        return $this->success(
            $this->mosaicService->formatMosaicWithItemsForApi($mosaic)
        );
    }

    public function store(StoreMosaicRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $mosaic = $this->mosaicService->createMosaic($validated);
        
        return $this->success(
            $this->mosaicService->formatMosaicForApi($mosaic),
            201
        );
    }

    public function update(StoreMosaicRequest $request, Mosaic $mosaic): JsonResponse
    {
        $validated = $request->validated();
        $mosaic = $this->mosaicService->updateMosaic($mosaic, $validated);
        
        return $this->success(
            $this->mosaicService->formatMosaicForApi($mosaic)
        );
    }

    public function split(MosaicItem $mosaicItem, Request $request): JsonResponse
    {
        // User is automatically set by the api.key middleware
        $user = $request->user();
        
        $ownership = $this->validateOwnership($mosaicItem->mosaic, $user);
        if ($ownership instanceof JsonResponse) {
            return $ownership;
        }

        $newItem = $this->mosaicService->splitItem($mosaicItem);

        return $this->success([
            'item' => $this->mosaicService->formatMosaicItemForApi($newItem)
        ]);
    }

    public function destroy(Mosaic $mosaic): JsonResponse
    {
        $this->mosaicService->deleteMosaic($mosaic);
        return $this->success(null, 204);
    }

    public function userMosaics(Request $request): JsonResponse
    {
        $user = $request->user();
        $mosaics = $user->mosaics()->get();
        
        return $this->success([
            'mosaics' => $mosaics->map(fn($mosaic) => $this->mosaicService->formatMosaicForApi($mosaic))
        ]);
    }
} 