<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Entry;
use App\Models\EntryType;
use App\Services\EntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * READ-ONLY API for external frontends using API keys
 * All CRUD operations are handled by the web frontend
 */
class EntryController extends BaseApiController
{
    public function __construct(
        protected readonly EntryService $entryService,
    ) {}

    /**
     * Get all entries for the authenticated user (API key)
     * Only returns entries from entry types the user has permission for
     */
    public function indexWithApiKey(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get user's allowed entry types
        $allowedEntryTypes = $user->allowedEntryTypes();

        if ($allowedEntryTypes->isEmpty()) {
            return $this->success([
                'entries' => [],
                'message' => 'No entry types assigned to this user',
            ]);
        }

        // Get entries from allowed types only
        $entryTypeIds = $allowedEntryTypes->pluck('id');
        $entries = $user->entries()
            ->whereIn('entry_type_id', $entryTypeIds)
            ->with(['entryType', 'images'])
            ->orderBy('order')
            ->get();

        return $this->success([
            'entries' => $entries->map(fn (Entry $entry) => $this->entryService->formatEntryForApi($entry)),
            'entry_types' => $allowedEntryTypes->map(fn ($type) => [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'description' => $type->description,
            ]),
        ]);
    }

    /**
     * Get entries by specific type (e.g., /api/entries/by-type/i-ams)
     * Only returns entries if user has permission for that type
     */
    public function indexByTypeWithApiKey(Request $request, string $type): JsonResponse
    {
        $user = $request->user();

        // Check if user has permission for this entry type
        if (! $user->hasEntryTypePermission($type)) {
            return $this->forbidden('You do not have permission to access this entry type');
        }

        // Get the entry type
        $entryType = EntryType::where('slug', $type)->where('is_active', true)->first();
        if (! $entryType) {
            return $this->notFound('Entry type not found');
        }

        // Get entries of this type
        $entries = $user->entries()
            ->where('entry_type_id', $entryType->id)
            ->with(['entryType', 'images'])
            ->orderBy('order')
            ->get();

        return $this->success([
            'entries' => $entries->map(fn (Entry $entry) => $this->entryService->formatEntryForApi($entry)),
            'entry_type' => [
                'id' => $entryType->id,
                'name' => $entryType->name,
                'slug' => $entryType->slug,
                'description' => $entryType->description,
            ],
        ]);
    }

    /**
     * Get specific entry by ID (API key)
     * Only returns entry if user has permission for its type
     */
    public function showWithApiKey(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $entry = $user->entries()
            ->with(['entryType', 'images'])
            ->find($id);

        if (! $entry) {
            return $this->notFound('Entry not found');
        }

        // Check if user has permission for this entry's type
        if (! $user->hasEntryTypePermission($entry->entryType->slug)) {
            return $this->forbidden('You do not have permission to access this entry');
        }

        return $this->success(
            $this->entryService->formatEntryForApi($entry)
        );
    }
}
