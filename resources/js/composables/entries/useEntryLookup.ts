import type { EntryLookupItem } from '@/types/entryField';
import axios from 'axios';
import { reactive } from 'vue';

// Resolves entry ids to titles for entry_relation fields, via GET /entries/lookup.
// One module-level cache is shared by every component on the page, so the read
// view, the relation picker and the index cards never fetch the same id twice.

/** Ids per request: keeps the query string well under common URL length limits. */
const CHUNK_SIZE = 100;

/** id -> entry, or null once the server has said it is unknown / not ours. */
const cache = reactive(new Map<string, EntryLookupItem | null>());
const inFlight = new Set<string>();

export async function resolveEntries(ids: unknown[]): Promise<void> {
    const missing = [...new Set(ids.map(String))].filter(
        id => id !== '' && !cache.has(id) && !inFlight.has(id)
    );
    if (missing.length === 0) {
        return;
    }

    missing.forEach(id => inFlight.add(id));

    const chunks: string[][] = [];
    for (let i = 0; i < missing.length; i += CHUNK_SIZE) {
        chunks.push(missing.slice(i, i + CHUNK_SIZE));
    }

    await Promise.all(
        chunks.map(async chunk => {
            try {
                const { data } = await axios.get<EntryLookupItem[]>(route('entries.lookup'), {
                    params: { ids: chunk }
                });
                const found = new Map(data.map(item => [item.id, item]));
                chunk.forEach(id => cache.set(id, found.get(id) ?? null));
            } catch (error) {
                // Leave uncached so a later render can retry.
                console.error('Entry lookup failed:', error);
            } finally {
                chunk.forEach(id => inFlight.delete(id));
            }
        })
    );
}

/** Remember entries we already know (e.g. picked from search results). */
export function rememberEntries(items: EntryLookupItem[]): void {
    items.forEach(item => cache.set(item.id, item));
}

export async function searchEntries(typeSlug: string, query: string): Promise<EntryLookupItem[]> {
    const { data } = await axios.get<EntryLookupItem[]>(route('entries.search'), {
        params: { type: typeSlug, q: query }
    });
    rememberEntries(data);

    return data;
}

export function useEntryLookup() {
    const entryFor = (id: string): EntryLookupItem | null | undefined => cache.get(id);

    /** Title if known; null while loading or when the entry is missing. */
    const titleFor = (id: string): string | null => cache.get(id)?.title ?? null;

    /** True once the server confirmed the id does not resolve (deleted or not owned). */
    const isMissing = (id: string): boolean => cache.has(id) && cache.get(id) === null;

    return {
        entryFor,
        titleFor,
        isMissing,
        resolve: resolveEntries,
        search: searchEntries,
        remember: rememberEntries
    };
}
