<?php

namespace App\Http\Controllers;

use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaUploadController
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Generic media upload endpoint that can handle uploads for any entity
     */
    public function upload(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'entity_type' => 'required|string|in:album,blog,news,mosaic',
            'entity_id' => 'required|string',
            'media' => 'required|array',
            'media.*' => 'required|file|mimes:jpeg,png,jpg,gif,svg,mp4,webm,avi',
        ]);

        try {
            $entityType = $request->input('entity_type');
            $entityId = $request->input('entity_id');

            // Determine the storage folder based on entity type
            $folder = $this->getStorageFolder($entityType, $entityId);

            $uploadedMedia = [];
            foreach ($request->file('media') as $index => $file) {
                Log::info('MediaUploadController@upload - Processing file:', [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'index' => $index,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ]);

                // Use ImageService to store the file
                $result = $this->imageService->storeImage($file, $folder);

                Log::info('MediaUploadController@upload - File stored:', $result);

                // Create media record
                $media = $this->createMediaRecord($entityType, $entityId, $result, $file);

                Log::info('MediaUploadController@upload - Media record created:', ['id' => $media->id]);

                $uploadedMedia[] = $media;
            }

            Log::info('MediaUploadController@upload - All files processed successfully', [
                'count' => count($uploadedMedia),
            ]);

            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Media uploaded successfully',
                    'media' => $uploadedMedia,
                ]);
            }

            return back()->with([
                'message' => 'Media uploaded successfully',
                'media' => $uploadedMedia,
            ]);
        } catch (\Exception $e) {
            Log::error('MediaUploadController@upload - Error occurred:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error uploading media: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Error uploading media: '.$e->getMessage());
        }
    }

    /**
     * Get the storage folder path based on entity type and ID
     */
    private function getStorageFolder(string $entityType, string $entityId): string
    {
        switch ($entityType) {
            case 'album':
                return "albums/{$entityId}";
            case 'blog':
                return "blogs/{$entityId}";
            case 'news':
                return "news/{$entityId}";
            case 'mosaic':
                return "mosaics/{$entityId}";
            default:
                return "uploads/{$entityType}/{$entityId}";
        }
    }

    /**
     * Create a media record based on entity type
     */
    private function createMediaRecord(string $entityType, string $entityId, array $result, $file)
    {
        switch ($entityType) {
            case 'album':
                return $this->createAlbumImageRecord($entityId, $result, $file);
            case 'blog':
                return $this->createBlogMediaRecord($entityId, $result, $file);
            case 'news':
                return $this->createNewsMediaRecord($entityId, $result, $file);
            case 'mosaic':
                return $this->createMosaicMediaRecord($entityId, $result, $file);
            default:
                return $this->createGenericMediaRecord($entityType, $entityId, $result, $file);
        }
    }

    /**
     * Create an album image record
     */
    private function createAlbumImageRecord(string $albumId, array $result, $file)
    {
        $album = \App\Models\Album::findOrFail($albumId);

        // Check authorization using Gate facade
        if (! Gate::allows('update', $album)) {
            abort(403, 'Unauthorized action.');
        }

        // Shift all existing images down to make room at the top
        $album->images()->increment('order', 1);

        return $album->images()->create([
            'id' => Str::uuid(),
            'path' => $result['url'],
            'order' => 0,
        ]);
    }

    /**
     * Create a blog media record (placeholder for future implementation)
     */
    private function createBlogMediaRecord(string $blogId, array $result, $file)
    {
        // This would be implemented when blog functionality is added
        throw new \Exception('Blog media upload not yet implemented');
    }

    /**
     * Create a news media record (placeholder for future implementation)
     */
    private function createNewsMediaRecord(string $newsId, array $result, $file)
    {
        // This would be implemented when news functionality is added
        throw new \Exception('News media upload not yet implemented');
    }

    /**
     * Create a mosaic media record (placeholder for future implementation)
     */
    private function createMosaicMediaRecord(string $mosaicId, array $result, $file)
    {
        // This would be implemented when mosaic media functionality is added
        throw new \Exception('Mosaic media upload not yet implemented');
    }

    /**
     * Create a generic media record using the Media model
     */
    private function createGenericMediaRecord(string $entityType, string $entityId, array $result, $file)
    {
        $media = \App\Models\Media::create([
            'type' => $this->getFileType($file),
            'path' => $result['url'],
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ],
        ]);

        // Attach to the appropriate entity using pivot table
        $this->attachMediaToEntity($media, $entityType, $entityId);

        return $media;
    }

    /**
     * Get file type based on mime type
     */
    private function getFileType($file): string
    {
        $mime = $file->getMimeType();

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        return 'file';
    }

    /**
     * Attach media to the appropriate entity using pivot table
     */
    private function attachMediaToEntity($media, string $entityType, string $entityId)
    {
        switch ($entityType) {
            case 'album':
                $album = \App\Models\Album::find($entityId);
                if ($album) {
                    $lastOrder = $album->media()->max('pivot_order') ?? -1;
                    $album->media()->attach($media->id, ['order' => ++$lastOrder]);
                }
                break;
            case 'mosaic':
                $mosaic = \App\Models\Mosaic::find($entityId);
                if ($mosaic) {
                    $lastOrder = $mosaic->media()->max('pivot_order') ?? -1;
                    $mosaic->media()->attach($media->id, ['order' => ++$lastOrder]);
                }
                break;
                // Add more entity types as needed
        }
    }
}
