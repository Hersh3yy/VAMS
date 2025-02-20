<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class AlbumController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $albums = auth()->user()->albums()->with('images')->get();
        
        return Inertia::render('Albums/Index', [
            'albums' => $albums
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Albums/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info($request->all());

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:5120', // 5MB max
        ]);

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('album-covers', 'spaces');
            $validated['cover_image_path'] = Storage::disk('spaces')->url($path);
        } else {
            $validated['cover_image_path'] = null;
        }

        $album = auth()->user()->albums()->create($validated);

        return redirect()->route('albums.show', $album);
    }

    /**
     * Display the specified resource.
     */
    public function show(Album $album)
    {
        $this->authorize('view', $album);
        
        return Inertia::render('Albums/Show', [
            'album' => $album,
            'images' => $album->images()->orderBy('order')->get()
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Album $album)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Album $album)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Album $album)
    {
        //
    }
}
