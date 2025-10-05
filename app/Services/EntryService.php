<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Entry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class EntryService extends BaseEntityService
{
    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Entry::class;
    }

    /**
     * Get relationships to load for web context
     */
    protected function getWebRelationships(): array
    {
        return [
            'entryType',
            'images' => function ($query) {
                $query->orderBy('field_name')->orderBy('order');
            },
        ];
    }

    /**
     * Get relationships to load for API context
     */
    protected function getApiRelationships(): array
    {
        return [
            'entryType',
            'images' => function ($query) {
                $query->orderBy('field_name')->orderBy('order');
            },
        ];
    }

    /**
     * Get all entries for the current user
     */
    public function getAllEntries(bool $forApi = false): Collection
    {
        return $this->getAll($forApi)
            ->load('entryType')
            ->sortBy('order');
    }

    /**
     * Override to use correct relationship name
     */
    public function getAll(bool $forApi = false): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return new Collection;
        }

        $relationships = $forApi ? $this->getApiRelationships() : $this->getWebRelationships();

        return $user->entries()
            ->with($relationships)
            ->orderBy('order')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get a specific entry with its relationships
     */
    public function getEntry(string|Entry $entry, bool $forApi = false): ?Entry
    {
        return $this->getById($entry, $forApi);
    }

    /**
     * Format entry data for API response
     */
    public function formatEntryForApi(Entry $entry): array
    {
        return [
            'id' => $entry->id,
            'title' => $entry->title,
            'content' => $entry->content,
            'status' => $entry->status,
            'published_at' => $entry->published_at?->toISOString(),
            'order' => $entry->order,
            'entry_type' => [
                'id' => $entry->entryType->id,
                'name' => $entry->entryType->name,
                'slug' => $entry->entryType->slug,
            ],
            'user_id' => $entry->user_id,
            'created_at' => $entry->created_at?->toISOString(),
            'updated_at' => $entry->updated_at?->toISOString(),
        ];
    }

    /**
     * Reorder entries
     */
    public function reorder(array $orderedIds): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        foreach ($orderedIds as $index => $id) {
            $user->entries()->where('id', $id)->update(['order' => $index]);
        }
    }

    /**
     * Format entity with its media for API response (required by contract)
     */
    public function formatWithMediaForApi(\Illuminate\Database\Eloquent\Model $entity): array
    {
        if (! $entity instanceof Entry) {
            return [];
        }

        return $this->formatEntryForApi($entity);
    }
}
