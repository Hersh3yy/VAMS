<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Services\MosaicService;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MosaicController extends BaseController
{
    public function __construct(
        protected readonly MosaicService $mosaicService,
        protected readonly ImageService $imageService
    ) {
    }

    // Web Routes
    public function index(): Response
    {
        $mosaics = $this->mosaicService->getAllMosaics(false);

        return Inertia::render('Mosaics/Index', [
            'mosaics' => $mosaics
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Mosaics/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'columns' => 'required|integer|min:2|max:5',
        ]);

        $mosaic = $this->user()->mosaics()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'columns' => $validated['columns'],
        ]);

        return $this->redirectWithSuccess('mosaics.show', $mosaic, 'Mosaic created successfully');
    }


    public function show(Mosaic $mosaic): Response
    {
        // Check if user owns this mosaic
        $this->authorizeOwnership($mosaic);

        // Get mosaic with items using service
        $mosaic = $this->mosaicService->getMosaicWithItems($mosaic, false);

        // Get user's albums with their images
        $albums = $this->user()->albums()
            ->with(['images' => function($query) {
                $query->orderBy('order');
            }])
            ->get()
            ->map(function($album) {
                return [
                    'id' => $album->id,
                    'title' => $album->title,
                    'cover_image_path' => $album->cover_image_path,
                    'images_count' => $album->images->count(),
                    'images' => $album->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'path' => $image->path,
                            'order' => $image->order,
                            'title' => $image->title,
                            'caption' => $image->caption,
                            'properties' => $image->properties
                        ];
                    })
                ];
            });

        return Inertia::render('Mosaics/Show', [
            'mosaic' => $mosaic,
            'albums' => $albums
        ]);
    }

    public function update(Request $request, Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            \Log::warning('Unauthorized mosaic update attempt', [
                'user_id' => Auth::id(),
                'mosaic_id' => $mosaic->id,
                'mosaic_owner' => $mosaic->user_id
            ]);
            abort(403);
        }

        \Log::info('Mosaic update started', [
            'user_id' => Auth::id(),
            'mosaic_id' => $mosaic->id,
            'request_data' => $request->all()
        ]);

        try {
            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'columns' => 'nullable|integer|min:2|max:5',
                'items' => 'required|array', // Temporarily remove min:1 for debugging
                'items.*.column_index' => 'required|integer|min:0',
                'items.*.type' => 'required|string|in:album,media,color,text',
                'items.*.content' => 'nullable',
                'items.*.album_id' => 'nullable|uuid|exists:albums,id',
                'items.*.properties' => 'nullable|array',
                'items.*.order' => 'required|integer|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Mosaic update validation failed', [
                'user_id' => Auth::id(),
                'mosaic_id' => $mosaic->id,
                'validation_errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            
            // Return more user-friendly error messages
            $errors = $e->errors();
            $userFriendlyMessages = [];
            
            if (isset($errors['items'])) {
                $userFriendlyMessages['items'] = ['Please add at least one item to your mosaic before saving.'];
            }
            
            if (isset($errors['items.*.type'])) {
                $userFriendlyMessages['items.*.type'] = ['Invalid item type. Please refresh the page and try again.'];
            }
            
            if (isset($errors['items.*.column_index'])) {
                $userFriendlyMessages['items.*.column_index'] = ['Invalid column position. Please refresh the page and try again.'];
            }
            
            return response()->json([
                'message' => 'Validation failed. Please check your mosaic items and try again.',
                'errors' => $userFriendlyMessages ?: $errors,
                'debug_info' => [
                    'items_count' => is_array($request->get('items')) ? count($request->get('items')) : 0,
                    'has_items' => !empty($request->get('items'))
                ]
            ], 422);
        }

        \Log::info('Mosaic update validation passed', [
            'user_id' => Auth::id(),
            'mosaic_id' => $mosaic->id,
            'items_count' => count($validated['items'])
        ]);

        // Check if items array is empty and handle accordingly
        if (empty($validated['items'])) {
            Log::warning('Mosaic update attempted with empty items array', [
                'user_id' => Auth::id(),
                'mosaic_id' => $mosaic->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'No items to save. Please add at least one item to your mosaic before saving.',
                'items_count' => 0
            ], 400);
        }

        // Update mosaic basic info if provided
        $updateData = [];
        if (isset($validated['title'])) {
            $updateData['title'] = $validated['title'];
        }
        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }
        if (isset($validated['columns'])) {
            $updateData['columns'] = $validated['columns'];
        }
        if (!empty($updateData)) {
            $mosaic->update($updateData);
        }

        // Update items
        $mosaic->items()->delete(); // Remove old items
        foreach ($validated['items'] as $index => $item) {
            try {
                $mosaic->items()->create([
                    'column_index' => $item['column_index'],
                    'type' => $item['type'],
                    'content' => $item['content'] ?? null,
                    'album_id' => $item['album_id'] ?? null,
                    'properties' => $item['properties'] ?? null,
                    'order' => $item['order'],
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create mosaic item', [
                    'user_id' => Auth::id(),
                    'mosaic_id' => $mosaic->id,
                    'item_index' => $index,
                    'item_data' => $item,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        \Log::info('Mosaic update completed successfully', [
            'user_id' => Auth::id(),
            'mosaic_id' => $mosaic->id,
            'items_created' => count($validated['items'])
        ]);

        // Return redirect for web requests, JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mosaic updated successfully',
                'items_count' => count($validated['items'])
            ]);
        }

        return redirect()->route('mosaics.show', $mosaic->id)
            ->with('success', 'Mosaic updated successfully');
    }

    public function destroy(Mosaic $mosaic): RedirectResponse
    {
        // Check if user owns this mosaic
        $this->authorizeOwnership($mosaic);

        $mosaic->items()->delete();
        $mosaic->delete();

        return $this->redirectWithSuccess('mosaics.index', [], 'Mosaic deleted successfully');
    }

    // API Routes
    public function showApi(Request $request, Mosaic $mosaic): JsonResponse
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
        
        return $this->showApi(request(), $mosaic);
    }

    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        $user = $request->user();
        
        $mosaic = $user->mosaics()->where('title', $title)->first();
        if (!$mosaic) {
            return response()->json(['error' => 'Mosaic not found'], 404);
        }
        
        return $this->showApi($request, $mosaic);
    }

    public function indexApi(Request $request): JsonResponse
    {
        $user = $request->user();
        $mosaics = $user->mosaics()->with('items')->get();
        
        return response()->json([
            'mosaics' => $mosaics->map(fn($mosaic) => $this->mosaicService->formatMosaicForApi($mosaic))
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function split(MosaicItem $mosaicItem, Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($mosaicItem->mosaic->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $newItem = $this->mosaicService->splitItem($mosaicItem);

        return response()->json([
            'item' => $this->mosaicService->formatMosaicItemForApi($newItem)
        ]);
    }

    public function storeItem(Request $request, Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|string|in:album,media,color,text',
            'properties' => 'required|array',
            'order' => 'required|integer|min:0',
            'column_index' => 'required|integer|min:0',
        ]);

        $item = $mosaic->items()->create([
            'type' => $validated['type'],
            'properties' => $validated['properties'],
            'order' => $validated['order'],
            'column_index' => $validated['column_index'],
        ]);

        // Load the updated mosaic with items for Inertia response
        $mosaic->load('items');
        
        return back()->with([
            'mosaic' => $mosaic,
            'new_item' => $item
        ]);
    }

    public function updateItem(Request $request, Mosaic $mosaic, MosaicItem $item)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|string|in:album,media,color,text',
            'properties' => 'required|array',
            'order' => 'required|integer|min:0',
            'column_index' => 'required|integer|min:0',
        ]);

        $item->update($validated);

        // Load the updated mosaic with items for Inertia response
        $mosaic->load('items');
        
        return back()->with([
            'mosaic' => $mosaic,
            'updated_item' => $item
        ]);
    }

    public function destroyItem(Mosaic $mosaic, MosaicItem $item)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $item->delete();

        // Load the updated mosaic with items for Inertia response
        $mosaic->load('items');
        
        return back()->with([
            'mosaic' => $mosaic,
            'deleted_item_id' => $item->id
        ]);
    }

    public function reorderItems(Request $request, Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|string|exists:mosaic_items,id',
            'items.*.order' => 'required|integer',
            'items.*.column_index' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            $mosaic->items()->where('id', $item['id'])->update([
                'order' => $item['order'],
                'column_index' => $item['column_index'],
            ]);
        }

        // Load the updated mosaic with items for Inertia response
        $mosaic->load('items');
        
        return back()->with([
            'mosaic' => $mosaic,
            'reordered' => true
        ]);
    }

    public function storeMedia(Request $request, Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'media' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi,webp|max:30720', // 30MB max like albums
        ]);

        try {
            $file = $request->file('media');
            
            // Use ImageService to store the file (same as albums)
            $result = $this->imageService->storeImage(
                $file,
                "mosaics/{$mosaic->id}"
            );
            
            // Determine the type
            $mime = $file->getMimeType();
            $type = str_starts_with($mime, 'video/') ? 'video' : 'image';

            return response()->json([
                'success' => true,
                'data' => [
                    'path' => $result['url'],
                    'type' => $type,
                    'mime_type' => $mime,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    // Include WebP URL if available
                    'webp_url' => $result['webp_url'] ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upload mosaic media: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 422);
        }
    }
}
