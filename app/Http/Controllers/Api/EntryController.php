<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\EntryType;
use App\Services\EntryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     * Only returns entries from entry types the user has permission for.
     *
     * Query params: per_page (int, optional for pagination)
     */
    public function indexWithApiKey(Request $request): JsonResponse
    {
        $user = $request->user();
        $allowedTypes = $user->allowedEntryTypes();

        if ($allowedTypes->isEmpty()) {
            return $this->success([
                'entries' => [],
                'entry_types' => [],
                'message' => 'No entry types assigned to this user',
            ]);
        }

        $perPage = $request->filled('per_page')
            ? min((int) $request->integer('per_page', 15), 100)
            : null;

        $result = $this->entryService->getEntriesForApi($user, null, $perPage);
        $formatType = fn ($t) => $this->entryService->formatEntryTypeForApi($t);

        if ($result instanceof LengthAwarePaginator) {
            return $this->successPaginated(
                $result,
                'entries',
                fn ($e) => $e,
                null,
                200,
                ['entry_types' => $allowedTypes->map($formatType)->values()->all()]
            );
        }

        return $this->success([
            'entries' => $result->values()->all(),
            'entry_types' => $allowedTypes->map($formatType)->values()->all(),
        ]);
    }

    /**
     * Get entries by specific type (e.g., /api/entries/by-type/i-ams)
     * Only returns entries if user has permission for that type.
     *
     * Query params: per_page (int, optional for pagination)
     */
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

        $perPage = $request->filled('per_page')
            ? min((int) $request->integer('per_page', 15), 100)
            : null;

        $result = $this->entryService->getEntriesForApi($user, $type, $perPage);

        if ($result instanceof LengthAwarePaginator) {
            return $this->successPaginated(
                $result,
                'entries',
                fn ($e) => $e,
                null,
                200,
                ['entry_type' => $this->entryService->formatEntryTypeForApi($entryType)]
            );
        }

        return $this->success([
            'entries' => $result->values()->all(),
            'entry_type' => $this->entryService->formatEntryTypeForApi($entryType),
        ]);
    }

    /**
     * Get specific entry by ID (API key)
     * Only returns entry if user has permission for its type
     */
    public function showWithApiKey(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $entry = $this->entryService->getEntryForApi($user, $id);

        if (! $entry) {
            return $this->notFound('Entry not found');
        }

        if (! $user->hasEntryTypePermission($entry['entry_type']['slug'] ?? '')) {
            return $this->forbidden('You do not have permission to access this entry');
        }

        return $this->success($entry);
    }
}
