<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Services\MosaicService;
use App\Http\Controllers\Api\Traits\HandlesApiOperations;
use App\Http\Controllers\Api\Traits\ValidatesApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MosaicController extends Controller
{
    use HandlesApiOperations, ValidatesApiKey;

    protected $mosaicService;

    public function __construct(MosaicService $mosaicService)
    {
        $this->mosaicService = $mosaicService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->validateApiKey($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        
        $mosaics = $this->mosaicService->getAllMosaics(true);
        
        return $this->handleSuccess([
            'mosaics' => $mosaics->map(fn($mosaic) => $this->mosaicService->formatMosaicForApi($mosaic))
        ]);
    }

    public function show(Request $request, Mosaic $mosaic): JsonResponse
    {
        $mosaic = $this->mosaicService->getMosaic($mosaic, true);
        if (!$mosaic) {
            return $this->handleNotFound('Mosaic not found');
        }
        
        return $this->handleSuccess(
            $this->mosaicService->formatMosaicWithItemsForApi($mosaic)
        );
    }

    public function showByTitle(string $title): JsonResponse
    {
        $mosaic = Mosaic::where('title', $title)->first();
        
        if (!$mosaic) {
            return $this->handleNotFound('Mosaic not found');
        }
        
        return $this->show(request(), $mosaic);
    }

    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        $user = $this->validateApiKey($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        
        $mosaic = $user->mosaics()->where('title', $title)->first();
        if (!$mosaic) {
            return $this->handleNotFound('Mosaic not found');
        }
        
        return $this->show($request, $mosaic);
    }

    public function split(MosaicItem $mosaicItem, Request $request): JsonResponse
    {
        $user = $this->validateApiKey($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        
        $ownership = $this->validateOwnership($mosaicItem->mosaic, $user);
        if ($ownership instanceof JsonResponse) {
            return $ownership;
        }

        $newItem = $this->mosaicService->splitItem($mosaicItem);

        return $this->handleSuccess([
            'item' => $this->mosaicService->formatMosaicItemForApi($newItem)
        ]);
    }
} 