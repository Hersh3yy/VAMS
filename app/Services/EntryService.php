<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\EntryResource;
use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
     * Create a single entry for a user at the given order.
     *
     * @param  array{title: string, content: array<string, mixed>, status?: string}  $data
     */
    public function createForUser(User $user, EntryType $entryType, array $data, int $order): Entry
    {
        $status = $data['status'] ?? 'published';

        return $user->entries()->create([
            'id' => (string) Str::uuid(),
            'title' => $data['title'],
            'content' => $data['content'],
            'entry_type_id' => $entryType->id,
            'status' => $status,
            'order' => $order,
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }

    /**
     * Create many entries atomically with sequential order.
     *
     * @param  list<array{title: string, content: array<string, mixed>, status: string}>  $entries
     * @return Collection<int, Entry>
     */
    public function createManyForUser(User $user, EntryType $entryType, array $entries): Collection
    {
        return DB::transaction(function () use ($user, $entryType, $entries): Collection {
            $maxOrder = $user->entries()
                ->where('entry_type_id', $entryType->id)
                ->max('order') ?? -1;

            $created = new Collection;

            foreach ($entries as $index => $entryData) {
                $created->push(
                    $this->createForUser($user, $entryType, $entryData, $maxOrder + 1 + $index)
                );
            }

            return $created;
        });
    }

    /**
     * Next order value for a user's entries of this type.
     */
    public function nextOrderFor(User $user, EntryType $entryType): int
    {
        $maxOrder = $user->entries()
            ->where('entry_type_id', $entryType->id)
            ->max('order') ?? -1;

        return $maxOrder + 1;
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
     *
     * @return array<string, mixed>
     */
    public function formatEntryForApi(Entry $entry): array
    {
        return EntryResource::make($entry)->resolve();
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
    public function formatWithMediaForApi(Model $entity): array
    {
        if (! $entity instanceof Entry) {
            return [];
        }

        return $this->formatEntryForApi($entity);
    }
}
