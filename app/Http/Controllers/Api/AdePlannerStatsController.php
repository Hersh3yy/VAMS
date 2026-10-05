<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The one write in the API-key API: anonymous ADE Planner data (hiren.ninja).
 * `hits` on an ade-artist = searches that found that artist (playlist or typed), `searches`
 * = typed by name, the stronger signal; on an ade-event `opens` (details opened),
 * `ticketClicks` (ticket shop, resale or ADE page) and `favorites`
 * = people who starred it; one ade-search entry per search (what, never who).
 * ade:sync keeps these counters on resync.
 */
class AdePlannerStatsController extends BaseApiController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hits' => ['array', 'max:500'],
            'hits.*' => ['uuid'],
            'searched' => ['array', 'max:500'],
            'searched.*' => ['uuid'],
            'opens' => ['array', 'max:50'],
            'opens.*' => ['uuid'],
            'ticketClicks' => ['array', 'max:50'],
            'ticketClicks.*' => ['uuid'],
            'favorites' => ['array', 'max:50'],
            'favorites.*.id' => ['required', 'uuid'],
            'favorites.*.delta' => ['required', 'integer', 'in:-1,1'],
            'search' => ['array'],
            'search.kind' => ['required_with:search', 'in:playlist,names,daytime'],
            'search.source' => ['nullable', 'in:spotify,apple-music,youtube-music,tidal,deezer,names,daytime'],
            'search.playlistUrl' => ['nullable', 'url', 'max:500'],
            'search.playlistTitle' => ['nullable', 'string', 'max:250'],
            'search.trackCount' => ['nullable', 'integer', 'min:0'],
            'search.partial' => ['nullable', 'boolean'],
            'search.query' => ['nullable', 'string', 'max:5000'],
            'search.artistCount' => ['nullable', 'integer', 'min:0'],
            'search.matchedArtists' => ['nullable', 'array', 'max:500'],
            'search.matchedArtists.*' => ['uuid'],
            'search.unmatched' => ['nullable', 'array', 'max:200'],
            'search.unmatched.*' => ['string', 'max:200'],
            'search.resultCount' => ['nullable', 'integer', 'min:0'],
            'search.example' => ['nullable', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $hits = array_fill_keys(array_unique($data['hits'] ?? []), 1);
        $searched = array_fill_keys(array_unique($data['searched'] ?? []), 1);
        $favorites = [];
        foreach ($data['favorites'] ?? [] as $favorite) {
            $favorites[$favorite['id']] = ($favorites[$favorite['id']] ?? 0) + (int) $favorite['delta'];
        }

        return $this->success([
            'hits' => $this->increment($user, 'ade-artist', 'hits', $hits),
            'searched' => $this->increment($user, 'ade-artist', 'searches', $searched),
            'opens' => $this->increment($user, 'ade-event', 'opens', array_fill_keys(array_unique($data['opens'] ?? []), 1)),
            'ticketClicks' => $this->increment($user, 'ade-event', 'ticketClicks', array_fill_keys(array_unique($data['ticketClicks'] ?? []), 1)),
            'favorites' => $this->increment($user, 'ade-event', 'favorites', $favorites),
            'search' => isset($data['search']) ? $this->logSearch($user, $data['search']) : null,
        ]);
    }

    /**
     * One ade-search entry, as a draft so the read API never serves it.
     *
     * @param  array<string, mixed>  $search  validated
     * @return string the new entry id
     */
    private function logSearch(User $user, array $search): string
    {
        $typeId = EntryType::where('slug', 'ade-search')->value('id');
        $matched = array_values(array_unique($search['matchedArtists'] ?? []));
        $query = trim((string) ($search['query'] ?? ''));

        $title = match ($search['kind']) {
            'playlist' => Str::limit(($search['playlistTitle'] ?? '') ?: 'Playlist', 120),
            'daytime' => 'Daytime: '.Str::limit($query, 100),
            default => Str::limit(implode(', ', array_slice(preg_split('/\s*[\n,]\s*/', $query) ?: [], 0, 4)), 120) ?: 'Names',
        };

        $entry = Entry::withoutEvents(fn (): Entry => Entry::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'entry_type_id' => $typeId,
            'title' => $title,
            'status' => 'draft',
            'order' => 0,
            'content' => array_filter([
                'kind' => $search['kind'],
                'source' => $search['source'] ?? null,
                'searchedAt' => now()->toIso8601String(),
                'playlistUrl' => $search['playlistUrl'] ?? null,
                'playlistTitle' => $search['playlistTitle'] ?? null,
                'trackCount' => $search['trackCount'] ?? null,
                'partial' => $search['partial'] ?? null,
                'query' => $query !== '' ? $query : null,
                'artistCount' => $search['artistCount'] ?? null,
                'matchedCount' => count($matched),
                'matchedArtists' => $matched,
                'unmatched' => $search['unmatched'] ?? null,
                'resultCount' => $search['resultCount'] ?? null,
                'example' => $search['example'] ?? null,
            ], fn (mixed $value): bool => $value !== null),
        ]));

        return $entry->id;
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
