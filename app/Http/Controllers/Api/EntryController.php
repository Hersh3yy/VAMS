<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\EntryResource;
use App\Http\Resources\EntryTypeResource;
use App\Models\Entry;
use App\Models\EntryType;
use App\Services\EntryService;
use App\Services\EntryValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * API for external frontends using API keys.
 *
 * Reads are open to any key with the entry type granted. Writes (store/update/
 * destroy) act as the key owner and are limited to entry types that user is
 * granted; an entry can only be changed or deleted by the key that owns it.
 * Same EntryService + validation the Inertia frontend uses - no plan/rate gate.
 */
class EntryController extends BaseApiController
{
    public function __construct(
        private readonly EntryService $entryService,
        private readonly EntryValidationService $validationService,
    ) {}

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

    /**
     * Create an entry as the key owner. content is validated against the entry
     * type's field_config, same as the web form.
     */
    public function storeWithApiKey(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'entry_type_id' => ['required', 'uuid', 'exists:entry_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'array'],
            'status' => ['nullable', 'string', 'in:draft,published'],
        ]);

        $entryType = EntryType::where('id', $validated['entry_type_id'])->where('is_active', true)->first();
        if (! $entryType) {
            return $this->notFound('Entry type not found');
        }
        if (! $user->hasEntryTypePermission($entryType->slug)) {
            return $this->forbidden('You do not have permission to create entries of this type');
        }

        try {
            $content = $this->validationService->validateContent($entryType, $validated['content']);
        } catch (ValidationException $e) {
            return $this->error('Content validation failed', 422, $e->errors());
        }

        $entry = $this->entryService->createForUser($user, $entryType, [
            'title' => $validated['title'],
            'content' => $content,
            'status' => $validated['status'] ?? 'published',
        ], $this->entryService->nextOrderFor($user, $entryType));

        return $this->success(EntryResource::make($entry)->resolve(), 'Entry created', 201);
    }

    /**
     * Update an entry the key owns.
     */
    public function updateWithApiKey(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Scoped to the owner's entries: another key's entry simply 404s.
        $entry = $user->entries()->with('entryType')->find($id);
        if (! $entry) {
            return $this->notFound('Entry not found');
        }
        if (! $user->hasEntryTypePermission($entry->entryType->slug)) {
            return $this->forbidden('You do not have permission to modify this entry');
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'array'],
            'status' => ['nullable', 'string', 'in:draft,published'],
        ]);

        $content = $entry->content;
        if (array_key_exists('content', $validated)) {
            try {
                $content = $this->validationService->validateContent($entry->entryType, $validated['content']);
            } catch (ValidationException $e) {
                return $this->error('Content validation failed', 422, $e->errors());
            }
        }

        $status = $validated['status'] ?? $entry->status;
        $entry->update([
            'title' => $validated['title'] ?? $entry->title,
            'content' => $content,
            'status' => $status,
            'published_at' => $status === 'published' ? ($entry->published_at ?? now()) : null,
        ]);

        return $this->success(EntryResource::make($entry->fresh())->resolve(), 'Entry updated');
    }

    /**
     * Delete an entry the key owns.
     */
    public function destroyWithApiKey(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $entry = $user->entries()->find($id);
        if (! $entry) {
            return $this->notFound('Entry not found');
        }

        $entry->delete();

        return $this->success(null, 'Entry deleted');
    }
}
