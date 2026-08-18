<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\EntryResource;
use App\Http\Resources\EntryTypeResource;
use App\Models\EntryType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * READ-ONLY API for external frontends using API keys
 * All CRUD operations are handled by the web frontend
 */
class EntryController extends BaseApiController
{
    public function indexWithApiKey(Request $request): JsonResponse
    {
        $user = $request->user();

        $allowedEntryTypes = $user->allowedEntryTypes();

        if ($allowedEntryTypes->isEmpty()) {
            return $this->success([
                'entries' => [],
                'message' => 'No entry types assigned to this user',
            ]);
        }

        $entryTypeIds = $allowedEntryTypes->pluck('id');
        $entries = $user->entries()
            ->whereIn('entry_type_id', $entryTypeIds)
            ->published()
            ->with(['entryType', 'images'])
            ->orderBy('order')
            ->get();

        return $this->success([
            'entries' => EntryResource::collection($entries)->resolve(),
            'entry_types' => EntryTypeResource::collection($allowedEntryTypes)->resolve(),
        ]);
    }

    public function indexByTypeWithApiKey(Request $request, string $type): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasEntryTypePermission($type)) {
            return $this->forbidden('You do not have permission to access this entry type');
        }

        $entryType = EntryType::where('slug', $type)->where('is_active', true)->first();
        if (! $entryType) {
            return $this->notFound('Entry type not found');
        }

        $entries = $user->entries()
            ->where('entry_type_id', $entryType->id)
            ->published()
            ->with(['entryType', 'images'])
            ->orderBy('order')
            ->get();

        return $this->success([
            'entries' => EntryResource::collection($entries)->resolve(),
            'entry_type' => EntryTypeResource::make($entryType)->resolve(),
        ]);
    }

    public function showWithApiKey(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $entry = $user->entries()
            ->published()
            ->with(['entryType', 'images'])
            ->find($id);

        if (! $entry) {
            return $this->notFound('Entry not found');
        }

        if (! $user->hasEntryTypePermission($entry->entryType->slug)) {
            return $this->forbidden('You do not have permission to access this entry');
        }

        return $this->success(
            EntryResource::make($entry)->resolve()
        );
    }
}
