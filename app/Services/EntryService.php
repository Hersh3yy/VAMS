<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
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
    public function getAllEntries(bool $forApi = false): EloquentCollection
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return new EloquentCollection;
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
    public function getAll(bool $forApi = false): EloquentCollection
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
     * Get published entries for API (user-scoped, filtered by allowed entry types), formatted for response.
     * When typeSlug is provided, returns only entries of that type.
     * Fetches and formats in one call — no separate format method needed.
     *
     * @return Collection<int, array<string, mixed>>|LengthAwarePaginator
     */
    public function getEntriesForApi(User $user, ?string $typeSlug = null, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $allowedTypes = $user->allowedEntryTypes();
        if ($allowedTypes->isEmpty()) {
            return new Collection;
        }

        $query = $user->entries()
            ->whereIn('entry_type_id', $allowedTypes->pluck('id'))
            ->published()
            ->with(['entryType', 'images'])
            ->orderBy('order')
            ->orderBy('updated_at', 'desc');

        if ($typeSlug !== null) {
            $entryType = EntryType::where('slug', $typeSlug)->where('is_active', true)->first();
            if (! $entryType) {
                return new Collection;
            }
            $query->where('entry_type_id', $entryType->id);
        }

        $format = fn (Entry $e) => $this->formatEntryForApi($e);

        return $perPage !== null
            ? $query->paginate($perPage)->through($format)
            : $query->get()->map($format);
    }

    /**
     * Get a single published entry by ID for API (user-scoped), formatted.
     */
    public function getEntryForApi(User $user, string $id): ?array
    {
        $entry = $user->entries()
            ->published()
            ->with(['entryType', 'images'])
            ->find($id);

        return $entry ? $this->formatEntryForApi($entry) : null;
    }

    /**
     * Format entry type for API response (minimal fields).
     *
     * @return array{id: string, name: string, slug: string, description: string|null}
     */
    public function formatEntryTypeForApi(EntryType $type): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'slug' => $type->slug,
            'description' => $type->description,
        ];
    }

    /**
     * Format entry data for API response (used by getEntriesForApi and formatWithMediaForApi).
     */
    protected function formatEntryForApi(Entry $entry): array
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
