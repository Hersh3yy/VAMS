<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Adapters\EntryJsonPayloadAdapter;
use App\Http\Requests\StoreEntriesJsonRequest;
use App\Models\Entry;
use App\Models\EntryType;
use App\Services\EntryService;
use App\Services\EntryValidationService;
use App\Services\Plans\PlanLimitService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class EntryController extends BaseEntityController
{
    public function __construct(
        EntryService $entryService,
        private readonly EntryValidationService $validationService,
        private readonly EntryJsonPayloadAdapter $jsonPayloadAdapter,
        PlanLimitService $planLimitService,
    ) {
        parent::__construct($entryService, $planLimitService);
    }

    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Entry::class;
    }

    /**
     * Get the form request class for this entity
     */
    protected function getFormRequestClass(): string
    {
        return \App\Http\Requests\StoreEntryRequest::class; // Not used since we override store/update
    }

    /**
     * Get the view name for index page
     */
    protected function getIndexView(): string
    {
        return 'Entries/Index';
    }

    /**
     * Get the view name for create page
     */
    protected function getCreateView(): string
    {
        return 'Entries/Create';
    }

    /**
     * Get the view name for show page
     */
    protected function getShowView(): string
    {
        return 'Entries/Show';
    }

    /**
     * Get the view name for edit page
     */
    protected function getEditView(): string
    {
        return 'Entries/Edit';
    }

    /**
     * Get the route name prefix (e.g., 'albums' for albums.show, albums.index, etc.)
     */
    protected function getRouteNamePrefix(): string
    {
        return 'entries';
    }

    /**
     * Get the relationship name on the User model (e.g., 'albums', 'mosaics', 'entries')
     */
    protected function getRelationshipName(): string
    {
        return 'entries';
    }

    /**
     * Get the entity name for view data keys (e.g., 'album', 'mosaic', 'entry')
     */
    protected function getEntityName(): string
    {
        return 'entry';
    }

    /**
     * Get additional data to pass to views
     */
    protected function getAdditionalViewData(): array
    {
        return [];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $type = $request->query('type');

        // Type is required for entries
        if (! $type) {
            abort(400, 'Entry type is required. Use ?type=slug parameter.');
        }

        // Validate that user has permission for this entry type
        if (! $this->user()->hasEntryTypePermission($type)) {
            abort(403, 'You do not have permission to access this entry type');
        }

        // Get the entry type
        $entryType = EntryType::where('slug', $type)->where('is_active', true)->first();
        if (! $entryType) {
            abort(404, 'Entry type not found');
        }

        // Get entries of this type for the user
        $entries = $this->user()->entries()
            ->where('entry_type_id', $entryType->id)
            ->with(['entryType', 'images'])
            ->orderBy('order')
            ->get();

        return Inertia::render('Entries/Index', [
            'entries' => $entries,
            'entryType' => $entryType,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $type = $request->query('type');

        // Type is required for entries
        if (! $type) {
            abort(400, 'Entry type is required. Use ?type=slug parameter.');
        }

        // Validate that user has permission for this entry type
        if (! $this->user()->hasEntryTypePermission($type)) {
            abort(403, 'You do not have permission to access this entry type');
        }

        // Get the entry type
        $entryType = EntryType::where('slug', $type)->where('is_active', true)->first();
        if (! $entryType) {
            abort(404, 'Entry type not found');
        }

        return Inertia::render('Entries/Create', [
            'entryType' => $entryType,
            'jsonImportDraft' => session('jsonImportDraft'),
        ]);
    }

    /**
     * Store a newly created entry
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required'], // Can be string or array
            'status' => ['nullable', 'string', 'in:draft,published'],
            'entry_type_id' => ['required', 'uuid', 'exists:entry_types,id'],
        ]);

        $entryType = EntryType::find($validated['entry_type_id']);

        // Validate user has permission for this entry type
        if (! $this->user()->hasEntryTypePermission($entryType->slug)) {
            return back()->withErrors(['error' => 'You do not have permission to create entries of this type.']);
        }

        if ($this->planLimitService->hasReached($this->user(), 'entries')) {
            return $this->redirectBackWithError(
                $this->planLimitService->limitMessage($this->user(), 'entries')
            );
        }

        // Handle content: if it's a string (old simple format), convert to object
        // Otherwise it's already an array from the dynamic form
        $content = $validated['content'] ?? [];
        if (is_string($validated['content'])) {
            // Legacy format for simple text content (backward compatibility)
            $content = ['statement' => $validated['content']];
        }

        // Validate content against entry type's field_config if it's an array
        if (is_array($content) && ! empty($content)) {
            try {
                $content = $this->validationService->validateContent($entryType, $content);
            } catch (ValidationException $e) {
                return back()->withErrors($e->errors());
            }
        }

        $user = $this->user();

        /** @var EntryService $service */
        $service = $this->entityService;

        $service->createForUser(
            $user,
            $entryType,
            [
                'title' => $validated['title'],
                'content' => $content,
                'status' => $validated['status'] ?? 'published',
            ],
            $service->nextOrderFor($user, $entryType),
        );

        return redirect()->route('entries.index', ['type' => $entryType->slug])
            ->with('success', 'Entry created successfully');
    }

    /**
     * Validate JSON import and show a preview screen (no writes).
     */
    public function previewJson(StoreEntriesJsonRequest $request): Response|RedirectResponse
    {
        $prepared = $this->prepareJsonEntries($request);

        if ($prepared instanceof RedirectResponse) {
            return $prepared;
        }

        ['entryType' => $entryType, 'entries' => $entries, 'payload' => $payload] = $prepared;

        return Inertia::render('Entries/JsonPreview', [
            'entryType' => $entryType,
            'entries' => $entries,
            'payload' => $payload,
        ]);
    }

    /**
     * Return to the create JSON editor with the drafted payload.
     */
    public function editJsonImport(StoreEntriesJsonRequest $request): RedirectResponse
    {
        $entryType = EntryType::query()->findOrFail($request->validated('entry_type_id'));

        if (! $this->user()->hasEntryTypePermission($entryType->slug)) {
            return back()->withErrors(['error' => 'You do not have permission to create entries of this type.']);
        }

        return redirect()
            ->route('entries.create', ['type' => $entryType->slug])
            ->with('jsonImportDraft', [
                'mode' => 'json',
                'payload' => $request->validated('payload'),
            ]);
    }

    /**
     * Bulk-create entries from a JSON object or array of objects.
     */
    public function storeJson(StoreEntriesJsonRequest $request): RedirectResponse
    {
        $prepared = $this->prepareJsonEntries($request);

        if ($prepared instanceof RedirectResponse) {
            return $prepared;
        }

        ['entryType' => $entryType, 'entries' => $validatedEntries] = $prepared;

        /** @var EntryService $service */
        $service = $this->entityService;
        $created = $service->createManyForUser($this->user(), $entryType, $validatedEntries);

        $count = $created->count();

        return redirect()->route('entries.index', ['type' => $entryType->slug])
            ->with('success', $count === 1
                ? 'Entry created successfully'
                : "{$count} entries created successfully");
    }

    /**
     * Adapt + validate a JSON import payload without writing.
     *
     * @return array{entryType: EntryType, entries: list<array{title: string, content: array<string, mixed>, status: string}>, payload: array<mixed>}|RedirectResponse
     */
    private function prepareJsonEntries(StoreEntriesJsonRequest $request): array|RedirectResponse
    {
        $entryType = EntryType::query()->findOrFail($request->validated('entry_type_id'));

        if (! $this->user()->hasEntryTypePermission($entryType->slug)) {
            return back()->withErrors(['error' => 'You do not have permission to create entries of this type.']);
        }

        /** @var array<mixed> $payload */
        $payload = $request->validated('payload');

        try {
            $adapted = $this->jsonPayloadAdapter->adapt(
                $payload,
                $entryType->field_config ?? [],
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['payload' => $e->getMessage()]);
        }

        if (count($adapted) > 100) {
            return back()->withErrors(['payload' => 'You may insert at most 100 entries at a time.']);
        }

        $remaining = $this->planLimitService->remaining($this->user(), 'entries');

        if ($remaining !== null && count($adapted) > $remaining) {
            return $this->redirectBackWithError(
                $this->planLimitService->limitMessage($this->user(), 'entries')
                .' You tried to insert '.count($adapted).' but only '.$remaining.' remain.'
            );
        }

        $validatedEntries = [];

        foreach ($adapted as $index => $entryData) {
            try {
                $content = $this->validationService->validateContent($entryType, $entryData['content']);
            } catch (ValidationException $e) {
                $prefixed = [];

                foreach ($e->errors() as $field => $messages) {
                    $prefixed["entries.{$index}.content.{$field}"] = $messages;
                }

                return back()->withErrors($prefixed);
            }

            if (strlen($entryData['title']) > 255) {
                return back()->withErrors([
                    "entries.{$index}.title" => 'The entry title cannot be longer than 255 characters.',
                ]);
            }

            $validatedEntries[] = [
                'title' => $entryData['title'],
                'content' => $content,
                'status' => $entryData['status'],
            ];
        }

        return [
            'entryType' => $entryType,
            'entries' => $validatedEntries,
            'payload' => $payload,
        ];
    }

    /**
     * Display the specified entry
     *
     * @override
     */
    public function show(string $entityId): Response|RedirectResponse
    {
        $entry = Entry::find($entityId);

        if (! $entry) {
            return $this->redirectWithError(
                'entries.index',
                [],
                'Entry not found. You have been redirected to your entries.'
            );
        }

        // Check if user owns this entry
        $this->authorizeOwnership($entry);

        // Load the entry with its relationships
        $entry->load(['entryType', 'images']);

        return Inertia::render('Entries/Show', [
            'entry' => $entry,
        ]);
    }

    /**
     * Show the form for editing the specified entry
     *
     * @override
     */
    public function edit(string $entityId): Response|RedirectResponse
    {
        $entry = Entry::find($entityId);

        if (! $entry) {
            return $this->redirectWithError(
                'entries.index',
                [],
                'Entry not found. You have been redirected to your entries.'
            );
        }

        // Check if user owns this entry
        $this->authorizeOwnership($entry);

        // Load the entry with its relationships
        $entry->load(['entryType', 'images']);

        return Inertia::render('Entries/Edit', [
            'entry' => $entry,
        ]);
    }

    /**
     * Update an existing entry
     */
    public function updateEntry(Request $request, Entry $entry): RedirectResponse
    {
        $this->authorizeOwnership($entry);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable'], // Can be string or array
            'status' => ['nullable', 'string', 'in:draft,published'],
        ]);

        // Handle content: if it's a string (old simple format), convert to object
        // Otherwise it's already an array from the dynamic form
        $content = $validated['content'] ?? [];
        if (is_string($validated['content'])) {
            // Legacy format for simple text content (backward compatibility)
            $content = ['statement' => $validated['content']];
        }

        // Validate content against entry type's field_config if it's an array
        if (is_array($content) && ! empty($content)) {
            try {
                $content = $this->validationService->validateContent($entry->entryType, $content);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return back()->withErrors($e->errors());
            }
        }

        $newStatus = $validated['status'] ?? $entry->status;

        $entry->update([
            'title' => $validated['title'],
            'content' => $content, // Store as JSON (can be simple object or complex structure)
            'status' => $newStatus,
            // Preserve published_at if already set, set to now() if transitioning to published, null if draft
            'published_at' => $newStatus === 'published'
                ? ($entry->published_at ?? now())
                : null,
        ]);

        return redirect()->route('entries.index', ['type' => $entry->entryType->slug])
            ->with('success', 'Entry updated successfully');
    }

    /**
     * Remove the specified entry from storage
     *
     * @override
     */
    public function destroy(Model|int|string $entity): RedirectResponse
    {
        /** @var Entry $entry */
        $entry = $entity instanceof Model
            ? $entity
            : Entry::query()->findOrFail($entity);

        // Check if user owns this entry
        $this->authorizeOwnership($entry);

        $entry->loadMissing('entryType');

        // Store the entry type slug before deletion for redirect
        $entryTypeSlug = $entry->entryType->slug;

        $entry->delete();

        return redirect()->route('entries.index', ['type' => $entryTypeSlug])
            ->with('success', 'Entry deleted successfully');
    }

    /**
     * Reorder entries
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'orderedIds' => ['required', 'array'],
            'orderedIds.*' => ['required', 'uuid', 'exists:entries,id'],
            'entry_type_id' => ['required', 'uuid', 'exists:entry_types,id'],
        ]);

        // Validate that all entries belong to the specified entry type
        $entryType = EntryType::find($validated['entry_type_id']);
        $entries = Entry::whereIn('id', $validated['orderedIds'])
            ->where('entry_type_id', $entryType->id)
            ->where('user_id', $this->userId())
            ->get();

        if ($entries->count() !== count($validated['orderedIds'])) {
            return back()->withErrors(['message' => 'Some entries do not belong to the specified entry type or user']);
        }

        /** @var EntryService $service */
        $service = $this->entityService;
        $service->reorder($validated['orderedIds']);

        return back()->with('message', 'Entries reordered successfully');
    }
}
