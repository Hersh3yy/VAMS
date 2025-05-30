<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Services\MosaicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MosaicController extends Controller
{
    protected $mosaicService;

    public function __construct(MosaicService $mosaicService)
    {
        $this->mosaicService = $mosaicService;
    }

    public function index(Request $request): JsonResponse
    {
        $mosaics = $this->mosaicService->getAllMosaics(true);
        
        return response()->json([
            'mosaics' => $mosaics->map(fn($mosaic) => $this->mosaicService->formatMosaicForApi($mosaic))
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function show(Request $request, Mosaic $mosaic): JsonResponse
    {
        $mosaic = $this->mosaicService->getMosaic($mosaic, true);
        if (!$mosaic) {
            return response()->json(['error' => 'Mosaic not found'], 404, [], JSON_UNESCAPED_UNICODE);
        }
        
        return response()->json(
            $this->mosaicService->formatMosaicWithItemsForApi($mosaic), 
            200, 
            [], 
            JSON_UNESCAPED_UNICODE
        );
    }

    public function showByTitle(string $title): JsonResponse
    {
        $mosaic = Mosaic::where('title', $title)->first();
        
        if (!$mosaic) {
            return response()->json(['error' => 'Mosaic not found'], 404);
        }
        
        return $this->show(request(), $mosaic);
    }

    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        $user = $request->user();
        
        $mosaic = $user->mosaics()->where('title', $title)->first();
        if (!$mosaic) {
            return response()->json(['error' => 'Mosaic not found'], 404);
        }
        
        return $this->show($request, $mosaic);
    }

    public function split(MosaicItem $mosaicItem, Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($mosaicItem->mosaic->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $newItem = $this->mosaicService->splitItem($mosaicItem);

        return response()->json([
            'success' => true,
            'item' => $this->mosaicService->formatMosaicItemForApi($newItem)
        ]);
    }
} 