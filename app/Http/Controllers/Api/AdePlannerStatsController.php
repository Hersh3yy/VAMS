<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The one write in the API-key API: anonymous counters for ADE Planner (hiren.ninja).
 * `hits` on an ade-artist = searches that found that artist; `favorites` on an ade-event
 * = people who starred it. Only counts, never who. ade:sync keeps both on resync.
 */
class AdePlannerStatsController extends BaseApiController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hits' => ['array', 'max:500'],
            'hits.*' => ['uuid'],
            'favorites' => ['array', 'max:50'],
            'favorites.*.id' => ['required', 'uuid'],
            'favorites.*.delta' => ['required', 'integer', 'in:-1,1'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $hits = array_fill_keys(array_unique($data['hits'] ?? []), 1);
        $favorites = [];
        foreach ($data['favorites'] ?? [] as $favorite) {
            $favorites[$favorite['id']] = ($favorites[$favorite['id']] ?? 0) + (int) $favorite['delta'];
        }

        return $this->success([
            'hits' => $this->increment($user, 'ade-artist', 'hits', $hits),
            'favorites' => $this->increment($user, 'ade-event', 'favorites', $favorites),
        ]);
    }

    /**
     * Add each delta to one counter in the entries' content, never below zero. Locked
     * rows, so two requests at once don't lose a count.
     *
     * @param  array<string, int>  $deltas  entry id => change
     * @return int entries updated
     */
    private function increment(User $user, string $typeSlug, string $field, array $deltas): int
    {
        if ($deltas === []) {
            return 0;
        }

        $typeId = EntryType::where('slug', $typeSlug)->value('id');

        return DB::transaction(function () use ($user, $typeId, $field, $deltas): int {
            $entries = Entry::where('user_id', $user->id)
                ->where('entry_type_id', $typeId)
                ->whereIn('id', array_keys($deltas))
                ->lockForUpdate()
                ->get();

            Entry::withoutEvents(function () use ($entries, $field, $deltas): void {
                foreach ($entries as $entry) {
                    $content = $entry->content ?? [];
                    $content[$field] = max(0, (int) ($content[$field] ?? 0) + $deltas[$entry->id]);
                    $entry->update(['content' => $content]);
                }
            });

            return $entries->count();
        });
    }
}
