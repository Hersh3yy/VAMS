<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ImageService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MediaController
{
    public function __construct(
        private readonly ImageService $imageService
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'media' => 'required|file|image',
        ]);

        try {
            $result = $this->imageService->storeImage(
                $request->file('media'),
                'uploads/'.(string) $request->user()->id
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: '.$e->getMessage(),
            ], 422);
        }
    }
}
