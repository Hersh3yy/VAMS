<?php

namespace App\Http\Controllers;

use App\Models\MosaicItem;
use Illuminate\Http\Request;

class MosaicItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return MosaicItem::all(); // Return all mosaic items
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'landing_mosaic_id' => 'required|uuid|exists:landing_mosaics,id',
            'album_id' => 'nullable|uuid|exists:albums,id',
            'image_path' => 'nullable|string',
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'link_url' => 'nullable|string',
            'desktop_position' => 'nullable|json',
            'mobile_position' => 'nullable|json',
            'order' => 'integer|default:0',
        ]);

        return MosaicItem::create($validated); // Create and return the new mosaic item
    }

    /**
     * Display the specified resource.
     */
    public function show(MosaicItem $mosaicItem)
    {
        return $mosaicItem; // Return the specified mosaic item
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MosaicItem $mosaicItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MosaicItem $mosaicItem)
    {
        $validated = $request->validate([
            'album_id' => 'nullable|uuid|exists:albums,id',
            'image_path' => 'nullable|string',
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'link_url' => 'nullable|string',
            'desktop_position' => 'nullable|json',
            'mobile_position' => 'nullable|json',
            'order' => 'integer|default:0',
        ]);

        $mosaicItem->update($validated); // Update the mosaic item
        return $mosaicItem; // Return the updated mosaic item
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MosaicItem $mosaicItem)
    {
        $mosaicItem->delete(); // Delete the mosaic item
        return response()->noContent(); // Return no content response
    }
}
