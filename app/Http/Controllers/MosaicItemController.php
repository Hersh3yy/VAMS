<?php

namespace App\Http\Controllers;

use App\Models\MosaicItem;
use App\Models\Mosaic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MosaicItemController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Require mosaic_id parameter
        $request->validate([
            'mosaic_id' => 'required|uuid|exists:mosaics,id'
        ]);
        
        // Get mosaic
        $mosaic = Mosaic::findOrFail($request->mosaic_id);
        
        // Authorize access to this mosaic
        $this->authorize('view', $mosaic);
        
        // Return all items for this mosaic with their children
        return MosaicItem::where('mosaic_id', $request->mosaic_id)
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not needed for API
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mosaic_id' => 'required|uuid|exists:mosaics,id',
            'parent_id' => 'nullable|uuid|exists:mosaic_items,id',
            'split_direction' => 'nullable|string|in:horizontal,vertical,none',
            'type' => 'required|string|in:album,image,video,text,container',
            'reference_id' => 'nullable|uuid',
            'content' => 'nullable|string',
            'properties' => 'nullable|json',
            'link_url' => 'nullable|string|url',
            'link_target' => 'nullable|string',
            'desktop_position' => 'nullable|json',
            'tablet_position' => 'nullable|json',
            'mobile_position' => 'nullable|json',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        // Get mosaic
        $mosaic = Mosaic::findOrFail($validated['mosaic_id']);
        
        // Authorize access to this mosaic
        $this->authorize('update', $mosaic);
        
        // Create and return the new mosaic item
        return MosaicItem::create($validated);
    }
    
    /**
     * Split a tile into two new tiles
     */
    public function split(Request $request, MosaicItem $mosaicItem)
    {
        $validated = $request->validate([
            'direction' => 'required|string|in:horizontal,vertical',
        ]);
        
        // Get mosaic
        $mosaic = $mosaicItem->mosaic;
        
        // Authorize access to this mosaic
        $this->authorize('update', $mosaic);
        
        // If this is already a split container, don't allow re-splitting
        if ($mosaicItem->split_direction !== 'none') {
            return response()->json(['message' => 'This tile is already split'], 400);
        }
        
        // Update the parent tile to be a container
        $mosaicItem->update([
            'split_direction' => $validated['direction'],
            'type' => 'container',
        ]);
        
        // Create two child tiles
        $childTiles = [];
        for ($i = 0; $i < 2; $i++) {
            $childTiles[] = MosaicItem::create([
                'mosaic_id' => $mosaicItem->mosaic_id,
                'parent_id' => $mosaicItem->id,
                'type' => 'container',
                'order' => $i,
            ]);
        }
        
        return response()->json([
            'parent' => $mosaicItem->fresh(),
            'children' => $childTiles
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(MosaicItem $mosaicItem)
    {
        // Get mosaic
        $mosaic = $mosaicItem->mosaic;
        
        // Authorize access to this mosaic
        $this->authorize('view', $mosaic);
        
        // Load children
        $mosaicItem->load('children');
        
        return $mosaicItem;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MosaicItem $mosaicItem)
    {
        // Not needed for API
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MosaicItem $mosaicItem)
    {
        $validated = $request->validate([
            'split_direction' => 'nullable|string|in:horizontal,vertical,none',
            'type' => 'nullable|string|in:album,image,video,text,container',
            'reference_id' => 'nullable|uuid',
            'content' => 'nullable|string',
            'properties' => 'nullable|json',
            'link_url' => 'nullable|string|url',
            'link_target' => 'nullable|string',
            'desktop_position' => 'nullable|json',
            'tablet_position' => 'nullable|json',
            'mobile_position' => 'nullable|json',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        // Get mosaic
        $mosaic = $mosaicItem->mosaic;
        
        // Authorize access to this mosaic
        $this->authorize('update', $mosaic);
        
        $mosaicItem->update($validated);
        return $mosaicItem;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MosaicItem $mosaicItem)
    {
        // Get mosaic
        $mosaic = $mosaicItem->mosaic;
        
        // Authorize access to this mosaic
        $this->authorize('update', $mosaic);
        
        // Delete all children recursively
        foreach ($mosaicItem->children as $child) {
            $child->delete();
        }
        
        $mosaicItem->delete();
        return response()->noContent();
    }
}
