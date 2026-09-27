<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\EntryLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Session-authenticated JSON helpers used by entry_relation fields in the CMS
 * (resolving related entry titles and the relation picker's search).
 */
class EntryLookupController extends BaseController
{
    public function __construct(
        private readonly EntryLookupService $lookupService,
    ) {}

    /**
     * GET /entries/lookup?ids[]=...  ->  [{id, title, entry_type_slug}]
     */
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['string', 'max:64'],
        ]);

        return response()->json(
            $this->lookupService->lookup($this->user(), $validated['ids'])
        );
    }

    /**
     * GET /entries/search?type={slug}&q={text}  ->  first matches by title
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json(
            $this->lookupService->search(
                $this->user(),
                $validated['type'],
                (string) ($validated['q'] ?? ''),
            )
        );
    }
}
