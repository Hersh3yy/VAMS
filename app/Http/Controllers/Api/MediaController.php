<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\DeleteApiMediaRequest;
use App\Http\Requests\StoreApiMediaRequest;
use App\Services\ImageService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class MediaController extends BaseApiController
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function upload(StoreApiMediaRequest $request): JsonResponse
    {
        try {
            $file = $request->uploadedFile();
            $type = $request->mediaType();
            $folder = $type === 'video' ? 'videos' : 'images';

            $result = $this->imageService->storeImage(
                $file,
                "uploads/{$folder}/".$request->user()->id
            );

            return $this->success([
                'url' => $result['url'],
                'path' => $result['path'],
                'type' => $type,
                'size' => $file->getSize(),
                'original_name' => $file->getClientOriginalName(),
            ]);
        } catch (Exception $e) {
            Log::error('Media upload failed: '.$e->getMessage());

            return $this->error('Upload failed: '.$e->getMessage(), 422);
        }
    }

    public function delete(DeleteApiMediaRequest $request): JsonResponse
    {
        try {
            $path = $request->string('path')->toString();

            if (str_contains($path, (string) config('filesystems.disks.spaces.endpoint'))) {
                $bucket = config('filesystems.disks.spaces.bucket');
                $endpoint = config('filesystems.disks.spaces.endpoint');
                $path = str_replace("{$endpoint}/{$bucket}/", '', $path);
            }

            $deleted = Storage::disk('spaces')->delete($path);

            if (! $deleted) {
                return $this->notFound('File not found or could not be deleted');
            }

            return $this->success(message: 'File deleted successfully');
        } catch (Exception $e) {
            Log::error('Media deletion failed: '.$e->getMessage());

            return $this->error('Deletion failed: '.$e->getMessage(), 422);
        }
    }
}
