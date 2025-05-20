<?php

namespace App\Http\Controllers;

use App\Models\Mosaic;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class MosaicController extends Controller
{
    public function index()
    {
        $mosaics = Auth::user()->mosaics()
            ->with(['items' => function ($query) {
                $query->orderBy('column_index')
                      ->orderBy('order');
            }])
            ->latest()
            ->get();

        return Inertia::render('Mosaics/Index', [
            'mosaics' => $mosaics
        ]);
    }

    public function create()
    {
        return Inertia::render('Mosaics/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'columns' => 'required|integer|min:2|max:5',
        ]);

        $mosaic = Auth::user()->mosaics()->create([
            'id' => Str::uuid(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'columns' => $validated['columns'],
        ]);

        return redirect()->route('mosaics.edit', $mosaic);
    }

    public function edit(Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        // Load the mosaic with its items ordered by column and position
        $mosaic->load(['items' => function ($query) {
            $query->orderBy('column_index')
                  ->orderBy('order');
        }]);

        return Inertia::render('Mosaics/Edit', [
            'mosaic' => $mosaic
        ]);
    }

    public function update(Request $request, Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'columns' => 'required|integer|min:2|max:5',
            'items' => 'required|array',
            'items.*.id' => 'required|string',
            'items.*.column_index' => 'required|integer|min:0',
            'items.*.type' => 'required|string|in:image,text',
            'items.*.content' => 'nullable|string',
            'items.*.properties' => 'nullable|array',
            'items.*.order' => 'required|integer',
        ]);

        // Update mosaic basic info
        $mosaic->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'columns' => $validated['columns'],
        ]);

        // Update items
        $mosaic->items()->delete(); // Remove old items
        foreach ($validated['items'] as $item) {
            $mosaic->items()->create([
                'id' => $item['id'],
                'column_index' => $item['column_index'],
                'type' => $item['type'],
                'content' => $item['content'] ?? null,
                'properties' => $item['properties'] ?? null,
                'order' => $item['order'],
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $mosaic->items()->delete();
        $mosaic->delete();

        return redirect()->route('mosaics.index');
    }
}
