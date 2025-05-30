<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\AlbumImage;
use App\Services\AlbumService;
use App\Http\Controllers\Api\Traits\HandlesAlbumOperations;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AlbumController extends Controller
{
    use HandlesAlbumOperations;

    protected $albumService;

    public function __construct(AlbumService $albumService)
    {
        $this->albumService = $albumService;
    }

    public function index()
    {
        $albums = $this->albumService->getAllAlbums(false);

        return Inertia::render('Albums/Index', [
            'albums' => $albums
        ]);
    }

    public function create()
    {
        return Inertia::render('Albums/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $user = Auth::user();
        $album = $user->albums()->create([
            'id' => Str::uuid(),
            'title' => $validated['title'],
            'description' => $validated['description'],
        ]);

        return redirect()->route('albums.edit', $album);
    }

    public function edit(Album $album)
    {
        // Check if user owns this album
        if ($album->user_id !== Auth::id()) {
            abort(403);
        }

        // Get album with images using service
        $album = $this->getAlbumWithImages($album, false);

        return Inertia::render('Albums/Edit', [
            'album' => $album
        ]);
    }

    public function update(Request $request, Album $album)
    {
        // Check if user owns this album
        if ($album->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'images' => 'required|array',
            'images.*.id' => 'required|string',
            'images.*.path' => 'required|string',
            'images.*.caption' => 'nullable|string',
            'images.*.title' => 'nullable|string',
            'images.*.order' => 'required|integer',
            'images.*.properties' => 'nullable|array',
        ]);

        // Update album basic info
        $album->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
        ]);

        // Update images
        $album->images()->delete(); // Remove old images
        foreach ($validated['images'] as $image) {
            $album->images()->create([
                'id' => $image['id'],
                'path' => $image['path'],
                'caption' => $image['caption'] ?? null,
                'title' => $image['title'] ?? null,
                'order' => $image['order'],
                'properties' => $image['properties'] ?? null,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Album $album)
    {
        // Check if user owns this album
        if ($album->user_id !== Auth::id()) {
            abort(403);
        }

        $album->images()->delete();
        $album->delete();

        return redirect()->route('albums.index');
    }
}
