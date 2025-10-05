<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\EntryType;
use App\Services\EntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EntryController extends BaseEntityController
{
    public function __construct(EntryService $entryService)
    {
        parent::__construct($entryService);
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
        if (! auth()->user()->hasEntryTypePermission($type)) {
            abort(403, 'You do not have permission to access this entry type');
        }

        // Get the entry type
        $entryType = EntryType::where('slug', $type)->where('is_active', true)->first();
        if (! $entryType) {
            abort(404, 'Entry type not found');
        }

        // Get entries of this type for the user
        $entries = auth()->user()->entries()
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
        if (! auth()->user()->hasEntryTypePermission($type)) {
            abort(403, 'You do not have permission to access this entry type');
        }

        // Get the entry type
        $entryType = EntryType::where('slug', $type)->where('is_active', true)->first();
        if (! $entryType) {
            abort(404, 'Entry type not found');
        }

        return Inertia::render('Entries/Create', [
            'entryType' => $entryType,
        ]);
    }

    /**
     * Store a newly created entry
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['nullable', 'string', 'in:draft,published'],
            'entry_type_id' => ['required', 'uuid', 'exists:entry_types,id'],
        ]);

        $entryType = EntryType::find($validated['entry_type_id']);

        // Validate user has permission for this entry type
        if (! auth()->user()->hasEntryTypePermission($entryType->slug)) {
            return back()->withErrors(['error' => 'You do not have permission to create entries of this type.']);
        }

        $user = $this->user();

        // Calculate next order for this entry type
        $maxOrder = $user->entries()
            ->where('entry_type_id', $entryType->id)
            ->max('order') ?? -1;

        $entry = $user->entries()->create([
            'id' => Str::uuid(),
            'title' => $validated['title'],
            'content' => ['statement' => $validated['content']], // Store as JSON
            'entry_type_id' => $entryType->id,
            'status' => $validated['status'] ?? 'draft',
            'order' => $maxOrder + 1,
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);

        return redirect()->route('entries.index', ['type' => $entryType->slug])
            ->with('success', 'Entry created successfully');
    }

    /**
     * Update an existing entry
     */
    public function updateEntry(Request $request, Entry $entry): RedirectResponse
    {
        $this->authorizeOwnership($entry);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['nullable', 'string', 'in:draft,published'],
        ]);

        $entry->update([
            'title' => $validated['title'],
            'content' => ['statement' => $validated['content']], // Store as JSON
            'status' => $validated['status'] ?? 'draft',
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);

        return redirect()->route('entries.index', ['type' => $entry->entryType->slug])
            ->with('success', 'Entry updated successfully');
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
            ->where('user_id', auth()->id())
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
