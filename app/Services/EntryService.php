<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Entry;
use App\Models\User;
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
     * Get all entries for the current user
     */
    public function getAllEntries(bool $forApi = false): Collection
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return new Collection;
        }

        $query = $user->entries();

        if ($forApi) {
            // For API, only return published entries
            $query = $query->published();
        }

        return $query
            ->with([
                'entryType',
                'images' => fn ($query) => $query->orderBy('field_name')->orderBy('order'),
            ])
            ->orderBy('order')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Override to use correct relationship name
     */
    public function getAll(bool $forApi = false): Collection
    {
        return $this->getAllEntries($forApi);
    }

    /**
     * Get a specific entry with its relationships
     */
    public function getEntry(string|Entry $entry, bool $forApi = false): ?Entry
    {
        if (is_string($entry)) {
            $entry = Entry::findOrFail($entry);
        }

        if (! $entry instanceof Entry) {
            return null;
        }

        // For API, only return if published (has published_at and status is published)
        if ($forApi && ($entry->status !== 'published' || ! $entry->published_at)) {
            return null;
        }

        // Load relationships
        $entry->load([
            'entryType',
            'images' => fn ($query) => $query->orderBy('field_name')->orderBy('order'),
        ]);

        return $entry;
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

        if (! $user instanceof User) {
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
