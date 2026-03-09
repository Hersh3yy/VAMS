<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreEntryTypeRequest;
use App\Http\Requests\Admin\UpdateEntryTypeRequest;
use App\Models\EntryType;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EntryTypeController
{
    /**
     * Display a listing of entry types
     */
    public function index()
    {
        $entryTypes = EntryType::withCount('entries')->get();

        return Inertia::render('Admin/EntryTypes/Index', [
            'entryTypes' => $entryTypes,
        ]);
    }

    /**
     * Show the form for creating a new entry type
     */
    public function create()
    {
        return Inertia::render('Admin/EntryTypes/Create');
    }

    /**
     * Store a newly created entry type
     */
    public function store(StoreEntryTypeRequest $request)
    {
        $validated = $request->validated();

        EntryType::create([
            'id' => Str::uuid(),
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'field_config' => $validated['field_config'],
            'is_active' => true,
        ]);

        return redirect()->route('admin.entry-types.index')
            ->with('success', 'Entry type created successfully.');
    }

    /**
     * Show the form for editing the entry type
     */
    public function edit(EntryType $entryType)
    {
        return Inertia::render('Admin/EntryTypes/Edit', [
            'entryType' => $entryType,
        ]);
    }

    /**
     * Update the entry type
     */
    public function update(UpdateEntryTypeRequest $request, EntryType $entryType)
    {
        $validated = $request->validated();

        $entryType->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'field_config' => $validated['field_config'],
            'is_active' => $validated['is_active'] ?? $entryType->is_active,
        ]);

        return redirect()->route('admin.entry-types.index')
            ->with('success', 'Entry type updated successfully.');
    }

    /**
     * Remove the entry type
     */
    public function destroy(EntryType $entryType)
    {
        // Check if there are existing entries of this type
        if ($entryType->entries()->count() > 0) {
            return redirect()->route('admin.entry-types.index')
                ->with('error', 'Cannot delete entry type that has existing entries. Please delete all entries first or deactivate the type.');
        }

        $entryType->delete();

        return redirect()->route('admin.entry-types.index')
            ->with('success', 'Entry type deleted successfully.');
    }
}
