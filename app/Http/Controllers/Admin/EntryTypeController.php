<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\EntryType;
use Illuminate\Http\Request;
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'field_config' => 'required|array|min:1',
            'field_config.*.name' => 'required|string',
            'field_config.*.type' => 'required|string|in:text,textarea,number,select,checkbox,image,repeatable,image_collection,entry_relation,object',
            'field_config.*.label' => 'required|string',
            'field_config.*.required' => 'boolean',
            'field_config.*.placeholder' => 'nullable|string',
            'field_config.*.options' => 'nullable|array', // For select fields
            // Repeatable, object, and image_collection fields can have nested fields
            'field_config.*.fields' => 'nullable|array',
            'field_config.*.fields.*.name' => 'required_with:field_config.*.fields|string',
            'field_config.*.fields.*.type' => 'required_with:field_config.*.fields|string|in:text,textarea,number,select,checkbox,image',
            'field_config.*.fields.*.label' => 'nullable|string',
            'field_config.*.fields.*.required' => 'nullable|boolean',
            'field_config.*.fields.*.placeholder' => 'nullable|string',
            // Min/max for repeatable and image_collection
            'field_config.*.min' => 'nullable|integer',
            'field_config.*.max' => 'nullable|integer',
            // For entry_relation
            'field_config.*.entry_type_slug' => 'nullable|string',
            'field_config.*.exclude_current' => 'boolean',
            // For object fields
            'field_config.*.collapsible' => 'boolean',
        ]);

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
    public function update(Request $request, EntryType $entryType)
    {
        // Debug: Log the incoming request
        \Log::info('EntryType Update Request:', [
            'entry_type_id' => $entryType->id,
            'field_config_count' => count($request->input('field_config', [])),
            'field_config' => $request->input('field_config', []),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'field_config' => 'required|array|min:1',
            'field_config.*.name' => 'required|string',
            'field_config.*.type' => 'required|string|in:text,textarea,number,select,checkbox,image,repeatable,image_collection,entry_relation,object',
            'field_config.*.label' => 'required|string',
            'field_config.*.required' => 'boolean',
            'field_config.*.placeholder' => 'nullable|string',
            'field_config.*.options' => 'nullable|array',
            // Repeatable, object, and image_collection fields can have nested fields
            'field_config.*.fields' => 'nullable|array',
            'field_config.*.fields.*.name' => 'required_with:field_config.*.fields|string',
            'field_config.*.fields.*.type' => 'required_with:field_config.*.fields|string|in:text,textarea,number,select,checkbox,image',
            'field_config.*.fields.*.label' => 'nullable|string',
            'field_config.*.fields.*.required' => 'nullable|boolean',
            'field_config.*.fields.*.placeholder' => 'nullable|string',
            // Min/max for repeatable and image_collection
            'field_config.*.min' => 'nullable|integer',
            'field_config.*.max' => 'nullable|integer',
            // For entry_relation
            'field_config.*.entry_type_slug' => 'nullable|string',
            'field_config.*.exclude_current' => 'boolean',
            // For object fields
            'field_config.*.collapsible' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $entryType->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'field_config' => $validated['field_config'],
            'is_active' => $validated['is_active'],
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
