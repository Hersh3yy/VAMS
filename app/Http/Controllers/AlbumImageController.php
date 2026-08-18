<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReorderAlbumImagesRequest;
use App\Http\Requests\StoreAlbumImagesRequest;
use App\Http\Requests\StoreAlbumVideoRequest;
use App\Http\Requests\UpdateAlbumImageRequest;
use App\Models\Album;
use App\Models\AlbumImage;
use App\Services\AlbumService;
use App\Services\ImageService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class AlbumImageController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ImageService $imageService,
        private readonly AlbumService $albumService,
    ) {}

    public function store(StoreAlbumImagesRequest $request, Album $album): JsonResponse|RedirectResponse
    {
        try {
            $this->authorize('update', $album);

            $fileCount = count($request->file('images'));
            $album->images()->increment('order', $fileCount);

            $uploadedImages = [];
            $fileNames = [];
            foreach ($request->file('images') as $index => $image) {
                $result = $this->imageService->storeImage($image, "albums/{$album->id}");
                $albumImage = $album->images()->create([
                    'id' => Str::uuid(),
                    'path' => $result['url'],
                    'order' => $index,
                    'published' => true,
                ]);
                $uploadedImages[] = $albumImage;
                $fileNames[] = $image->getClientOriginalName().' ('.round($image->getSize() / 1024 / 1024, 2).' MB)';
            }

            Log::info('Uploaded '.count($uploadedImages).' image(s): '.implode(', ', $fileNames));

            $album = $this->albumService->getById($album, false);

            Log::info('Album after upload has '.($album->images->count() ?? 0).' images');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Image uploaded successfully',
                    'images' => $uploadedImages,
                    'album' => $album,
                ]);
            }

            // Return Inertia response with updated album data
            return back()->with([
                'Album' => $album,
                'message' => 'Images uploaded successfully',
                'images' => $uploadedImages,
            ]);
        } catch (Exception $e) {
            Log::error('Upload error: '.$e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error uploading image: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Error uploading images: '.$e->getMessage());
        }
    }

    public function update(UpdateAlbumImageRequest $request, Album $album, AlbumImage $image): RedirectResponse
    {
        if ($image->album_id !== $album->id) {
            abort(404);
        }

        $this->authorize('update', $album);

        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $result = $this->imageService->storeImage(
                $request->file('image'),
                "albums/{$image->album_id}"
            );

            $validated['path'] = $result['url'];
        }

        if (isset($validated['published'])) {
            $validated['published'] = (bool) $validated['published'];
        }

        $image->update($validated);

        return back()->with('message', 'Image updated successfully');
    }

    public function destroy(Album $album, AlbumImage $image): RedirectResponse
    {
        if ($image->album_id !== $album->id) {
            abort(404);
        }

        $this->authorize('delete', $album);

        try {
            if (! $this->isVideoLink($image->path) && strpos($image->path, '/storage/') !== false) {
                $path = str_replace('/storage/', '', parse_url($image->path, PHP_URL_PATH));
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }

            $image->delete();

            $album = $this->albumService->getById($album, false);

            return back()->with([
                'Album' => $album,
                'message' => 'Item deleted successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to delete image: '.$e->getMessage());

            return back()->withErrors([
                'message' => 'Failed to delete item: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Check if a URL is a video link (YouTube, Vimeo, etc.)
     */
    private function isVideoLink(string $url): bool
    {
        return
            strpos($url, 'youtube.com') !== false ||
            strpos($url, 'youtu.be') !== false ||
            strpos($url, 'vimeo.com') !== false;
    }

    public function reorder(ReorderAlbumImagesRequest $request, ?Album $album = null): RedirectResponse
    {
        if (! $album && $request->has('album_id')) {
            $album = Album::findOrFail($request->album_id);
        }

        $fromIndex = $request->integer('from_index');
        $toIndex = $request->integer('to_index');

        $albumId = $album ? $album->id : $request->album_id;

        $images = AlbumImage::where('album_id', $albumId)
            ->orderBy('order')
            ->get();

        if ($fromIndex >= $images->count() || $toIndex >= $images->count()) {
            return back()->withErrors(['message' => 'Invalid index provided']);
        }

        $item = $images->splice($fromIndex, 1)->first();
        $images->splice($toIndex, 0, [$item]);

        DB::transaction(function () use ($images) {
            foreach ($images as $index => $image) {
                $image->order = $index;
                $image->save();
            }
        });

        $album = $this->albumService->getById($albumId, false);

        return back()->with([
            'Album' => $album,
            'message' => 'Image order updated successfully',
        ]);
    }

    /**
     * Store a video URL as an album image
     */
    public function storeVideo(StoreAlbumVideoRequest $request, Album $album): RedirectResponse
    {
        Log::info('Incoming video request data:', $request->all());

        try {
            $this->authorize('update', $album);

            $album->images()->increment('order', 1);

            $thumbnailResult = $this->imageService->storeVideoThumbnail(
                $request->url,
                "albums/{$album->id}"
            );

            $properties = [
                'type' => 'video',
                'video_url' => $request->url,
            ];

            if ($thumbnailResult) {
                $properties['thumbnail_url'] = $thumbnailResult['url'];
            }

            $album->images()->create([
                'path' => $request->url,
                'title' => $request->title,
                'caption' => $request->caption,
                'properties' => json_encode($properties),
                'order' => 0,
                'published' => true,
            ]);

            $album = $this->albumService->getById($album, false);

            return back()->with([
                'Album' => $album,
                'message' => 'Video added successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Error in AlbumImageController@storeVideo:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'message' => 'Error adding video: '.$e->getMessage(),
            ]);
        }
    }
}
