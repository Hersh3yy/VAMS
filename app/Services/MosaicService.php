<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MosaicService extends BaseEntityService
{
    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Mosaic::class;
    }

    /**
     * Get all mosaics for the current user or for the API.
     * Eager-loads items so the index UI can render cover previews without crashing.
     */
    public function getAll(bool $forApi = false): Collection
    {
        $itemsRelation = ['items' => fn ($query) => $query->orderBy('column_index')->orderBy('order')];

        if ($forApi) {
            return Mosaic::query()
                ->with($itemsRelation)
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        $user = Auth::user();
        if (! $user instanceof User) {
            return new Collection;
        }

        return $user->mosaics()
            ->with($itemsRelation)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get a specific mosaic with its items.
     */
    public function getById(string|Model $entity, bool $forApi = false): ?Model
    {
        $mosaic = parent::getById($entity, $forApi);

        if (! $mosaic instanceof Mosaic) {
            return null;
        }

        $mosaic->load(['items' => fn ($query) => $query->orderBy('column_index')->orderBy('order')]);

        return $mosaic;
    }

    /**
     * Format entity with its media for API response
     */
    public function formatWithMediaForApi(Model $entity): array
    {
        return [
            'mosaic' => $this->formatForApi($entity),
            'items' => $entity->items->map(fn (MosaicItem $item) => $this->formatMosaicItemForApi($item)),
        ];
    }

    /**
     * Format mosaic for API response
     */
    public function formatMosaicForApi(Mosaic $mosaic): array
    {
        return [
            'id' => $mosaic->id,
            'title' => $mosaic->title,
            'description' => $mosaic->description,
            'columns' => $mosaic->columns,
            'user_id' => $mosaic->user_id,
            'created_at' => $mosaic->created_at?->toISOString(),
            'updated_at' => $mosaic->updated_at?->toISOString(),
        ];
    }

    /**
     * Format mosaic with items for API response
     */
    public function formatMosaicWithItemsForApi(Mosaic $mosaic): array
    {
        return [
            'mosaic' => $this->formatMosaicForApi($mosaic),
            'items' => $mosaic->items->map(fn (MosaicItem $item) => $this->formatMosaicItemForApi($item)),
        ];
    }

    /**
     * Format mosaic item for API response
     */
    public function formatMosaicItemForApi(MosaicItem $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'content' => $item->content,
            'properties' => $item->properties,
            'column_index' => $item->column_index,
            'order' => $item->order,
            'created_at' => $item->created_at?->toISOString(),
            'updated_at' => $item->updated_at?->toISOString(),
        ];
    }
}
