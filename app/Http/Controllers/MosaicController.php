<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReorderMosaicItemsRequest;
use App\Http\Requests\StoreMosaicItemRequest;
use App\Http\Requests\StoreMosaicMediaRequest;
use App\Http\Requests\StoreMosaicRequest;
use App\Http\Requests\UpdateMosaicRequest;
use App\Models\Album;
use App\Models\AlbumImage;
use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Services\ImageService;
use App\Services\MosaicService;
use App\Services\Plans\PlanLimitService;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class MosaicController extends BaseEntityController
{
    public function __construct(
        MosaicService $mosaicService,
        private readonly ImageService $imageService,
        PlanLimitService $planLimitService,
    ) {
        parent::__construct($mosaicService, $planLimitService);
    }

    protected function getEntityModelClass(): string
    {
        return Mosaic::class;
    }

    protected function getFormRequestClass(): string
    {
        return StoreMosaicRequest::class;
    }

    protected function getIndexView(): string
    {
        return 'Mosaics/Index';
    }

    protected function getCreateView(): string
    {
        return 'Mosaics/Create';
    }

    protected function getShowView(): string
    {
        return 'Mosaics/Show';
    }

    protected function getEditView(): string
    {
        return 'Mosaics/Edit';
    }

    protected function getRouteNamePrefix(): string
    {
        return 'mosaics';
    }

    protected function getRelationshipName(): string
    {
        return 'mosaics';
    }

    protected function getEntityName(): string
    {
        return 'Mosaic';
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function extraCreationAttributes(array $validated): array
    {
        return [
            'columns' => $validated['columns'],
            'display_settings' => $validated['display_settings'] ?? null,
        ];
    }

    protected function getAdditionalViewData(): array
    {
        return [
            'albums' => $this->user()->albums()
                ->with(['images' => function (HasMany $query): void {
                    $query->orderBy('order');
                }])
                ->get()
                ->map(function (Album $album): array {
                    return [
                        'id' => $album->id,
                        'title' => $album->title,
                        'cover_image_path' => $album->cover_image_path,
                        'images_count' => $album->images->count(),
                        'images' => $album->images->map(function (AlbumImage $image): array {
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

    public function update(UpdateMosaicRequest $request, Mosaic $mosaic): JsonResponse|RedirectResponse
    {
        $this->authorizeOwnership($mosaic);

        $validated = $request->validated();

        if (isset($validated['items']) && $validated['items'] === []) {
            return response()->json([
                'success' => false,
                'message' => 'No items to save. Please add at least one item to your mosaic before saving.',
                'items_count' => 0,
            ], 400);
        }

        $this->fillValidatedAttributes($mosaic, $validated, ['title', 'description', 'columns']);

        if (isset($validated['items']) && $validated['items'] !== []) {
            $syncItems = collect($validated['items'])
                ->sortBy('order')
                ->values()
                ->map(function (array $itemData, int $index): array {
                    $itemData['order'] = $index;

                    return $itemData;
                });

            $mosaic->items()->delete();
            foreach ($syncItems as $item) {
                $mosaic->items()->create([
                    'column_index' => $item['column_index'],
                    'type' => $item['type'],
                    'content' => $item['content'] ?? null,
                    'album_id' => $item['album_id'] ?? null,
                    'properties' => $item['properties'] ?? null,
                    'order' => $item['order'],
                ]);
            }

            $validated['items'] = $syncItems->toArray();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mosaic updated successfully',
                'items_count' => isset($validated['items']) ? count($validated['items']) : 0,
            ]);
        }

        return $this->redirectWithSuccess(
            'mosaics.show',
            $mosaic,
            'Mosaic updated successfully'
        );
    }

    public function destroy(Mosaic $mosaic): RedirectResponse
    {
        return $this->deleteOwned($mosaic);
    }

    public function storeItem(StoreMosaicItemRequest $request, Mosaic $mosaic): RedirectResponse
    {
        $this->authorizeOwnership($mosaic);

        $mosaic->items()->create($request->validated());

        return $this->redirectWithSuccess(
            'mosaics.show',
            $mosaic,
            'Item added successfully'
        );
    }

    public function updateItem(StoreMosaicItemRequest $request, Mosaic $mosaic, MosaicItem $item): RedirectResponse
    {
        $this->authorizeOwnership($mosaic);

        $item->update($request->validated());

        return $this->redirectWithSuccess(
            'mosaics.show',
            $mosaic,
            'Item updated successfully'
        );
    }

    public function destroyItem(Mosaic $mosaic, MosaicItem $item): RedirectResponse
    {
        $this->authorizeOwnership($mosaic);

        $item->delete();

        return $this->redirectWithSuccess(
            'mosaics.show',
            $mosaic,
            'Item deleted successfully'
        );
    }

    public function reorderItems(ReorderMosaicItemsRequest $request, Mosaic $mosaic): RedirectResponse
    {
        $this->authorizeOwnership($mosaic);

        $validated = $request->validated();

        $items = MosaicItem::query()
            ->where('mosaic_id', $mosaic->id)
            ->orderBy('column_index')
            ->orderBy('order')
            ->get();

        $fromIndex = $items->search(fn (MosaicItem $item): bool => $item->id === $validated['from_id']);
        $toIndex = $items->search(fn (MosaicItem $item): bool => $item->id === $validated['to_id']);

        if ($fromIndex === false || $toIndex === false) {
            return back()->withErrors(['message' => 'Invalid item IDs provided']);
        }

        $moved = $items->splice($fromIndex, 1)->first();
        $items->splice($toIndex, 0, [$moved]);

        foreach ($items as $index => $item) {
            $item->order = $index;
            $item->save();
        }

        return $this->redirectWithSuccess(
            'mosaics.show',
            $mosaic,
            'Items reordered successfully'
        );
    }

    public function storeMedia(StoreMosaicMediaRequest $request, Mosaic $mosaic): JsonResponse
    {
        $this->authorizeOwnership($mosaic);

        try {
            $file = $request->uploadedFile();
            $result = $this->imageService->storeImage($file, "mosaics/{$mosaic->id}");
            $mime = $file->getMimeType();
            $type = str_starts_with((string) $mime, 'video/') ? 'video' : 'image';

            return response()->json([
                'success' => true,
                'data' => [
                    'path' => $result['url'],
                    'type' => $type,
                    'mime_type' => $mime,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
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
