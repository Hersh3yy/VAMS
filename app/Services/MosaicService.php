<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Mosaic;
use App\Models\MosaicItem;
use Illuminate\Database\Eloquent\Model;

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
     * Get a specific mosaic with its items
     */
    public function getMosaicWithItems(string|Mosaic $mosaic, bool $forApi = false): ?Mosaic
    {
        if (is_string($mosaic)) {
            $mosaic = Mosaic::findOrFail($mosaic);
        }

        if (! $mosaic instanceof Mosaic) {
            return null;
        }

        // Load relationships
        $mosaic->load('items');

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

    /**
     * Split a mosaic item into multiple items
     */
    public function splitItem(MosaicItem $mosaicItem): MosaicItem
    {
        // Implementation for splitting items would go here
        // This is a placeholder for the existing functionality
        return $mosaicItem;
    }
}
