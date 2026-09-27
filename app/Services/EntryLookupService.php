<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lightweight title lookups for entry_relation fields: resolve ids to titles
 * and search by title. Always scoped to the given user's own entries.
 */
final class EntryLookupService
{
    public const MAX_LOOKUP_IDS = 500;

    public const SEARCH_LIMIT = 20;

    /**
     * Resolve entry ids to {id, title, entry_type_slug}, in the order requested.
     * Unknown ids and ids owned by other users are silently left out.
     *
     * @param  array<int, mixed>  $ids
     * @return list<array{id: string, title: string, entry_type_slug: string|null}>
     */
    public function lookup(User $user, array $ids): array
    {
        $ids = array_slice(
            array_values(array_unique(array_filter($ids, fn (mixed $id): bool => is_string($id) && $id !== ''))),
            0,
            self::MAX_LOOKUP_IDS,
        );

        if ($ids === []) {
            return [];
        }

        $entries = $this->baseQuery($user)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $results = [];
        foreach ($ids as $id) {
            $entry = $entries->get($id);
            if ($entry instanceof Entry) {
                $results[] = $this->format($entry);
            }
        }

        return $results;
    }

    /**
     * First matches (case-insensitive, substring) by title within one entry type.
     * An empty query returns the first entries of the type alphabetically.
     *
     * @return list<array{id: string, title: string, entry_type_slug: string|null}>
     */
    public function search(User $user, string $typeSlug, string $query, int $limit = self::SEARCH_LIMIT): array
    {
        $entryType = EntryType::query()->where('slug', $typeSlug)->first();

        if (! $entryType instanceof EntryType) {
            return [];
        }

        $query = trim($query);

        return $this->baseQuery($user)
            ->where('entry_type_id', $entryType->id)
            ->when($query !== '', function (Builder $builder) use ($query): Builder {
                // '!' as the LIKE escape character is portable across pgsql, mysql and sqlite.
                $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($query));

                return $builder->whereRaw("LOWER(title) LIKE ? ESCAPE '!'", ['%'.$escaped.'%']);
            })
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(fn (Entry $entry): array => $this->format($entry))
            ->values()
            ->all();
    }

    /**
     * @return Builder<Entry>
     */
    private function baseQuery(User $user): Builder
    {
        return Entry::query()
            ->where('user_id', $user->id)
            ->select(['id', 'title', 'entry_type_id'])
            ->with('entryType:id,slug');
    }

    /**
     * @return array{id: string, title: string, entry_type_slug: string|null}
     */
    private function format(Entry $entry): array
    {
        return [
            'id' => (string) $entry->id,
            'title' => (string) $entry->title,
            'entry_type_slug' => $entry->entryType?->slug,
        ];
    }
}
