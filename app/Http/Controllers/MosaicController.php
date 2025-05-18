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
            ->with('items')
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
        ]);

        $mosaic = Auth::user()->mosaics()->create([
            'id' => Str::uuid(),
            'title' => $validated['title'],
            'description' => $validated['description'],
        ]);

        return redirect()->route('mosaics.edit', $mosaic);
    }

    public function edit(Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        // Load the mosaic with its items and user's albums with images
        $mosaic->load('items');
        $albums = Auth::user()->albums()->with('images')->get();

        return Inertia::render('Mosaics/Edit', [
            'mosaic' => $mosaic,
            'albums' => $albums,
        ]);
    }

    public function update(Request $request, Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|string',
            'items.*.type' => 'required|string|in:container,image',
            'items.*.split_direction' => 'nullable|string|in:horizontal,vertical,none',
            'items.*.desktop_position' => 'required|string',
            'items.*.order' => 'required|integer',
            'items.*.parent_id' => 'nullable|string',
            'items.*.image' => 'nullable|array',
            'items.*.image.src' => 'nullable|string',
            'items.*.image.alt' => 'nullable|string',
            'items.*.image.position' => 'nullable|array',
            'items.*.image.position.x' => 'nullable|numeric',
            'items.*.image.position.y' => 'nullable|numeric',
            'items.*.image.position.scale' => 'nullable|numeric',
        ]);

        // Update items
        $mosaic->items()->delete(); // Remove old items
        foreach ($validated['items'] as $item) {
            $mosaic->items()->create([
                'id' => $item['id'],
                'type' => $item['type'],
                'split_direction' => $item['split_direction'] ?? null,
                'desktop_position' => $item['desktop_position'],
                'order' => $item['order'],
                'parent_id' => $item['parent_id'] ?? null,
                'properties' => isset($item['image']) ? json_encode($item['image']) : null,
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
