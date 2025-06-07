<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Upload media file
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,svg,mp4,webm,avi|max:10240', // 10MB
            'type' => 'sometimes|string|in:image,video',
        ]);

        try {
            $file = $request->file('file');
            $type = $request->input('type', 'image');
            
            // Determine folder based on type
            $folder = $type === 'video' ? 'videos' : 'images';
            
            $result = $this->imageService->storeImage(
                $file,
                "uploads/{$folder}/" . auth()->user()->id
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $result['url'],
                    'path' => $result['path'],
                    'type' => $type,
                    'size' => $file->getSize(),
                    'original_name' => $file->getClientOriginalName(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Media upload failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 422);
        }
    }
} 