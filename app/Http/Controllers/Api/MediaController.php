<?php

namespace App\Http\Controllers\Api;

use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaController
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
        $type = $request->input('type', 'image');

        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,svg,mp4,webm,avi,webp',
            'type' => 'sometimes|string|in:image,video',
        ]);

        try {
            $file = $request->file('file');
            $type = $request->input('type', 'image');

            // Determine folder based on type
            $folder = $type === 'video' ? 'videos' : 'images';

            $result = $this->imageService->storeImage(
                $file,
                "uploads/{$folder}/".Auth::user()->id
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $result['url'],
                    'path' => $result['path'],
                    'type' => $type,
                    'size' => $file->getSize(),
                    'original_name' => $file->getClientOriginalName(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Media upload failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete media file
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $path = $request->input('path');

            // Extract the actual file path from URL if needed
            if (str_contains($path, config('filesystems.disks.spaces.endpoint'))) {
                $bucket = config('filesystems.disks.spaces.bucket');
                $endpoint = config('filesystems.disks.spaces.endpoint');
                $path = str_replace("{$endpoint}/{$bucket}/", '', $path);
            }

            // Delete from cloud storage
            $deleted = Storage::disk('spaces')->delete($path);

            if (! $deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found or could not be deleted',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Media deletion failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Deletion failed: '.$e->getMessage(),
            ], 422);
        }
    }
}
