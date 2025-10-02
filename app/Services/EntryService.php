<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Entry;
use App\Services\BaseEntityService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EntryService extends BaseEntityService
{
    public function __construct()
    {
        // No specific dependencies for EntryService
    }

    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Entry::class;
    }

    /**
     * Get all entries for the current user or API context
     */
    public function getAll(bool $forApi = false): Collection
    {
        $query = auth()->user()->entries()->orderBy('order')->latest();

        if ($forApi) {
            // For API, only return published entries
            $query->published();
        }

        return $query->get();
    }

    /**
     * Get a specific entry with its relationships
     */
    public function getById(string|Model $entry, bool $forApi = false): ?Model
    {
        if (is_string($entry)) {
            $entry = Entry::find($entry);
        }

        if (!$entry) {
            return null;
        }

        // For API, only return published entries
        if ($forApi && $entry->status !== 'published') {
            return null;
        }

        return $entry;
    }

    /**
     * Format entry for API response
     */
    public function formatForApi(Model $entry): array
    {
        return [
            'id' => $entry->id,
            'title' => $entry->title,
            'content' => $entry->content,
            'status' => $entry->status,
            'order' => $entry->order,
            'published_at' => $entry->published_at?->toISOString(),
            'created_at' => $entry->created_at->toISOString(),
            'updated_at' => $entry->updated_at->toISOString(),
        ];
    }

    /**
     * Format entry with its media for API response
     */
    public function formatWithMediaForApi(Model $entry): array
    {
        // Entries don't have media relationships for now
        return $this->formatForApi($entry);
    }

    /**
     * Get recent entries for the current user
     */
    public function getRecent(int $limit = 3): Collection
    {
        return auth()->user()->entries()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Publish an entry
     */
    public function publish(Entry $entry): Entry
    {
        $entry->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return $entry;
    }

    /**
     * Unpublish an entry (set to draft)
     */
    public function unpublish(Entry $entry): Entry
    {
        $entry->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        return $entry;
    }
}
