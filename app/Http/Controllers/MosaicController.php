<?php

namespace App\Http\Controllers;

use App\Models\Mosaic;
use Illuminate\Http\Request;

class MosaicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Mosaic::all(); // Return all mosaics
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'theme_settings' => 'nullable|json',
        ]);

        return Mosaic::create($validated); // Create and return the new mosaic
    }

    /**
     * Display the specified resource.
     */
    public function show(Mosaic $mosaic)
    {
        return $mosaic; // Return the specified mosaic
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mosaic $mosaic)
    {
        $validated = $request->validate([
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'theme_settings' => 'nullable|json',
        ]);

        $mosaic->update($validated); // Update the mosaic
        return $mosaic; // Return the updated mosaic
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mosaic $mosaic)
    {
        $mosaic->delete(); // Delete the mosaic
        return response()->noContent(); // Return no content response
    }
}
