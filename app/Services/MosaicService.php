<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\MosaicItemResource;
use App\Http\Resources\MosaicResource;
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
     *
     * @return array{mosaic: array<string, mixed>, items: mixed}
     */
    public function formatWithMediaForApi(Model $entity): array
    {
        if (! $entity instanceof Mosaic) {
            return [];
        }

        return $this->formatMosaicWithItemsForApi($entity);
    }

    /**
     * Format mosaic for API response
     *
     * @return array<string, mixed>
     */
    public function formatMosaicForApi(Mosaic $mosaic): array
    {
        return MosaicResource::make($mosaic)->resolve();
    }

    /**
     * Format mosaic with items for API response
     *
     * @return array{mosaic: array<string, mixed>, items: mixed}
     */
    public function formatMosaicWithItemsForApi(Mosaic $mosaic): array
    {
        return [
            'mosaic' => $this->formatMosaicForApi($mosaic),
            'items' => MosaicItemResource::collection($mosaic->items)->resolve(),
        ];
    }

    /**
     * Format mosaic item for API response
     *
     * @return array<string, mixed>
     */
    public function formatMosaicItemForApi(MosaicItem $item): array
    {
        return MosaicItemResource::make($item)->resolve();
    }
}
