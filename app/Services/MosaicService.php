<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
     * Get a specific entity with its relationships
     */
    public function getById(string|Model $entity, bool $forApi = false): ?Model
    {
        $mosaic = parent::getById($entity, $forApi);

        if ($mosaic instanceof Mosaic && ! $forApi) {
            $mosaic->load('items');
        }

        return $mosaic;
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
     * Format entity with its media for API response (contract).
     */
    public function formatWithMediaForApi(Model $entity): array
    {
        return $entity instanceof Mosaic
            ? $this->formatMosaicWithItemsForApi($entity->loadMissing('items'))
            : [];
    }

    /**
     * Get mosaics for API (user-scoped), formatted for response. Supports pagination.
     * Fetches and formats in one call — no separate format method needed.
     *
     * @return Collection<int, array<string, mixed>>|LengthAwarePaginator
     */
    public function getMosaicsForApi(User $user, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = $user->mosaics()->orderBy('updated_at', 'desc');

        $result = $perPage !== null
            ? $query->paginate($perPage)->through(fn (Mosaic $m) => $this->formatMosaicForApi($m))
            : $query->get()->map(fn (Mosaic $m) => $this->formatMosaicForApi($m));

        return $result;
    }

    /**
     * Get a single mosaic by ID for API (user-scoped), formatted with items.
     */
    public function getMosaicForApi(User $user, string $id): ?array
    {
        $mosaic = $user->mosaics()->find($id);

        return $mosaic ? $this->formatMosaicWithItemsForApi($mosaic->load('items')) : null;
    }

    /**
     * Get a single mosaic by title for API (user-scoped). Case-insensitive fallback.
     */
    public function getMosaicByTitleForApi(User $user, string $title): ?array
    {
        $decoded = urldecode($title);
        $mosaic = $user->mosaics()->where('title', $decoded)->first();

        if (! $mosaic) {
            $mosaic = $user->mosaics()->whereRaw('LOWER(title) = LOWER(?)', [$decoded])->first();
        }

        return $mosaic ? $this->formatMosaicWithItemsForApi($mosaic->load('items')) : null;
    }

    /**
     * Format mosaic for API response (used by getMosaicsForApi and formatWithMediaForApi).
     */
    protected function formatMosaicForApi(Mosaic $mosaic): array
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
     * Format mosaic with items for API response (used by getMosaicForApi and formatWithMediaForApi).
     */
    protected function formatMosaicWithItemsForApi(Mosaic $mosaic): array
    {
        return [
            'mosaic' => $this->formatMosaicForApi($mosaic),
            'items' => $mosaic->items->map(fn (MosaicItem $item) => $this->formatMosaicItemForApi($item))->values()->all(),
        ];
    }

    /**
     * Format mosaic item for API response (used by formatWithMediaForApi).
     */
    protected function formatMosaicItemForApi(MosaicItem $item): array
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
