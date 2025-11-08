<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Services\MosaicService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MosaicController extends BaseEntityController
{
    public function __construct(
        protected readonly MosaicService $mosaicService
    ) {
        parent::__construct($mosaicService);
    }

    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Mosaic::class;
    }

    /**
     * Get the form request class for this entity
     */
    protected function getFormRequestClass(): string
    {
        return \App\Http\Requests\StoreMosaicRequest::class;
    }

    /**
     * Get the view name for index page
     */
    protected function getIndexView(): string
    {
        return 'Mosaics/Index';
    }

    /**
     * Get the view name for create page
     */
    protected function getCreateView(): string
    {
        return 'Mosaics/Create';
    }

    /**
     * Get the view name for show page
     */
    protected function getShowView(): string
    {
        return 'Mosaics/Show';
    }

    /**
     * Get the view name for edit page
     */
    protected function getEditView(): string
    {
        return 'Mosaics/Edit';
    }

    /**
     * Get the route name prefix (e.g., 'albums' for albums.show, albums.index, etc.)
     */
    protected function getRouteNamePrefix(): string
    {
        return 'mosaics';
    }

    /**
     * Get the relationship name on the User model (e.g., 'albums', 'mosaics', 'entries')
     */
    protected function getRelationshipName(): string
    {
        return 'mosaics';
    }

    /**
     * Get the entity name for view data keys (e.g., 'album', 'mosaic', 'entry')
     */
    protected function getEntityName(): string
    {
        return 'Mosaic';
    }

    /**
     * Display a listing of mosaics for the authenticated user
     */
    public function index(Request $request): Response
    {
        $mosaics = $this->entityService->getAll(false);

        return Inertia::render($this->getIndexView(), [
            'entities' => $mosaics,
            'mosaics' => $mosaics,
            ...$this->getAdditionalViewData(),
        ]);
    }

    /**
     * Get additional data to pass to views
     */
    protected function getAdditionalViewData(): array
    {
        return [
            'albums' => $this->user()->albums()
                ->with(['images' => function ($query) {
                    $query->orderBy('order');
                }])
                ->get()
                ->map(function ($album) {
                    return [
                        'id' => $album->id,
                        'title' => $album->title,
                        'cover_image_path' => $album->cover_image_path,
                        'images_count' => $album->images->count(),
                        'images' => $album->images->map(function ($image) {
                            return [
                                'id' => $image->id,
                                'path' => $image->path,
                                'order' => $image->order,
                                'title' => $image->title,
                                'caption' => $image->caption,
                                'properties' => $image->properties,
                            ];
                        }),
                    ];
                }),
        ];
    }

    /**
     * Update the specified resource in storage
     */
    public function update(Request $request, $mosaic): JsonResponse|RedirectResponse
    {
        // Debug: Check what type $mosaic is
        Log::info('MosaicController@update - Parameter check:', [
            'mosaic_type' => gettype($mosaic),
            'mosaic_value' => $mosaic,
            'is_object' => is_object($mosaic),
            'is_string' => is_string($mosaic),
        ]);

        // If $mosaic is a string (ID), resolve it to a model
        if (is_string($mosaic)) {
            $mosaic = Mosaic::findOrFail($mosaic);
        }

        // Check if user owns this mosaic
        if ($mosaic->user_id != Auth::id()) {
            Log::warning('Unauthorized mosaic update attempt', [
                'user_id' => Auth::id(),
                'user_id_type' => gettype(Auth::id()),
                'mosaic_id' => $mosaic->id,
                'mosaic_owner' => $mosaic->user_id,
                'mosaic_owner_type' => gettype($mosaic->user_id),
            ]);
            abort(403);
        }

        Log::info('Mosaic update started', [
            'user_id' => Auth::id(),
            'mosaic_id' => $mosaic->id,
            'request_data' => $request->all(),
        ]);

        try {
            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'columns' => 'nullable|integer|min:2|max:5',
                'items' => 'nullable|array',
                'items.*.column_index' => 'required|integer|min:0',
                'items.*.type' => 'required|string|in:album,media,color,text',
                'items.*.content' => 'nullable',
                'items.*.album_id' => 'nullable|uuid|exists:albums,id',
                'items.*.properties' => 'nullable|array',
                'items.*.order' => 'required|integer|min:0',
            ]);
        } catch (ValidationException $e) {
            Log::error('Mosaic update validation failed', [
                'user_id' => Auth::id(),
                'mosaic_id' => $mosaic->id,
                'validation_errors' => $e->errors(),
                'request_data' => $request->all(),
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
                    'has_items' => ! empty($request->get('items')),
                ],
            ], 422);
        }

        Log::info('Mosaic update validation passed', [
            'user_id' => Auth::id(),
            'mosaic_id' => $mosaic->id,
            'items_count' => isset($validated['items']) ? count($validated['items']) : 0,
        ]);

        // Check if items array is empty and handle accordingly (only if items are provided)
        if (isset($validated['items']) && empty($validated['items'])) {
            Log::warning('Mosaic update attempted with empty items array', [
                'user_id' => Auth::id(),
                'mosaic_id' => $mosaic->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No items to save. Please add at least one item to your mosaic before saving.',
                'items_count' => 0,
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
        if (! empty($updateData)) {
            $mosaic->update($updateData);
        }

        // Update items (only if items are provided)
        if (isset($validated['items']) && ! empty($validated['items'])) {
            $syncItems = collect($validated['items'])
                ->sortBy('order')
                ->values()
                ->map(function ($itemData, $index) {
                    $itemData['order'] = $index;

                    return $itemData;
                });

            $mosaic->items()->delete(); // Remove old items
            foreach ($syncItems as $item) {
                try {
                    $mosaic->items()->create([
                        'column_index' => $item['column_index'],
                        'type' => $item['type'],
                        'content' => $item['content'] ?? null,
                        'album_id' => $item['album_id'] ?? null,
                        'properties' => $item['properties'] ?? null,
                        'order' => $item['order'],
                    ]);
                } catch (Exception $e) {
                    Log::error('Failed to create mosaic item', [
                        'user_id' => Auth::id(),
                        'mosaic_id' => $mosaic->id,
                        'item_index' => $item['order'],
                        'item_data' => $item,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }

            $validated['items'] = $syncItems->toArray();
        }

        Log::info('Mosaic update completed successfully', [
            'user_id' => Auth::id(),
            'mosaic_id' => $mosaic->id,
            'items_created' => isset($validated['items']) ? count($validated['items']) : 0,
        ]);

        // Return redirect for web requests, JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mosaic updated successfully',
                'items_count' => isset($validated['items']) ? count($validated['items']) : 0,
            ]);
        }

        return redirect()->route('mosaics.show', $mosaic->id)
            ->with('success', 'Mosaic updated successfully');
    }

    // API Routes
    public function showApi(Request $request, Mosaic $mosaic): JsonResponse
    {
        $mosaic = $this->mosaicService->getById($mosaic, true);
        if (! $mosaic) {
            return response()->json(['error' => 'Mosaic not found'], 404, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json(
            $this->mosaicService->formatWithMediaForApi($mosaic),
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    public function showByTitle(string $title): JsonResponse
    {
        $mosaic = Mosaic::where('title', $title)->first();

        if (! $mosaic) {
            return response()->json(['error' => 'Mosaic not found'], 404);
        }

        return $this->showApi(request(), $mosaic);
    }

    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        $user = $request->user();

        $mosaic = $user->mosaics()->where('title', $title)->first();
        if (! $mosaic) {
            return response()->json(['error' => 'Mosaic not found'], 404);
        }

        return $this->showApi($request, $mosaic);
    }

    public function indexApi(Request $request): JsonResponse
    {
        $user = $request->user();
        $mosaics = $user->mosaics()->with('items')->get();

        return response()->json([
            'mosaics' => $mosaics->map(fn ($mosaic) => $this->mosaicService->formatMosaicForApi($mosaic)),
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
            'item' => $this->mosaicService->formatMosaicItemForApi($newItem),
        ]);
    }

    public function storeItem(Request $request, Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id != Auth::id()) {
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

        return redirect()->route('mosaics.show', $mosaic->id)
            ->with('success', 'Item added successfully');
    }

    public function updateItem(Request $request, Mosaic $mosaic, MosaicItem $item)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id != Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|string|in:album,media,color,text',
            'properties' => 'required|array',
            'order' => 'required|integer|min:0',
            'column_index' => 'required|integer|min:0',
        ]);

        $item->update($validated);

        return redirect()->route('mosaics.show', $mosaic->id)
            ->with('success', 'Item updated successfully');
    }

    public function destroyItem(Mosaic $mosaic, MosaicItem $item)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id != Auth::id()) {
            abort(403);
        }

        $item->delete();

        return redirect()->route('mosaics.show', $mosaic->id)
            ->with('success', 'Item deleted successfully');
    }

    public function reorderItems(Request $request, Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id != Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'from_id' => 'required|string|exists:mosaic_items,id',
            'to_id' => 'required|string|exists:mosaic_items,id',
        ]);

        // Get all items for this mosaic ordered by current order
        $items = MosaicItem::where('mosaic_id', $mosaic->id)
            ->orderBy('column_index')
            ->orderBy('order')
            ->get();

        // Find the from and to positions
        $fromIndex = $items->search(fn ($item) => $item->id === $validated['from_id']);
        $toIndex = $items->search(fn ($item) => $item->id === $validated['to_id']);

        if ($fromIndex === false || $toIndex === false) {
            return back()->withErrors(['message' => 'Invalid item IDs provided']);
        }

        // Reorder the collection
        $item = $items->splice($fromIndex, 1)->first();
        $items->splice($toIndex, 0, [$item]);

        // Update the order for all affected items
        foreach ($items as $index => $item) {
            $item->order = $index;
            $item->save();
        }

        return redirect()->route('mosaics.show', $mosaic->id)
            ->with('success', 'Items reordered successfully');
    }

    public function storeMedia(Request $request, Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id != Auth::id()) {
            abort(403);
        }

        // Note: Images are converted to WebP client-side and should be under 1.99MB
        // Videos may still be larger, so we validate per file type
        $request->validate([
            'media' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi,webp',
        ]);

        try {
            $file = $request->file('media');

            // Use ImageService to store the file (same as albums)
            $result = app(\App\Services\ImageService::class)->storeImage(
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
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to upload mosaic media: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: '.$e->getMessage(),
            ], 422);
        }
    }
}
