<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\AlbumImage;
use App\Services\AlbumService;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AlbumController extends Controller
{
    protected $albumService;
    protected $mediaService;

    public function __construct(AlbumService $albumService, MediaService $mediaService)
    {
        $this->albumService = $albumService;
        $this->mediaService = $mediaService;
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

        return redirect()->route('albums.show', $album)->with('message', 'Album created successfully');
    }

    public function edit(Album $album)
    {
        // Check if user owns this album
        if ($album->user_id !== Auth::id()) {
            abort(403);
        }

        // Get album with images using service
        $album = $this->albumService->getAlbum($album, false);

        return Inertia::render('Albums/Edit', [
            'album' => $album
        ]);
    }

    public function show(Album $album)
    {
        // Check if user owns this album
        if ($album->user_id !== Auth::id()) {
            abort(403);
        }

        // Get album with images using service
        $album = $this->albumService->getAlbum($album, false);

        return Inertia::render('Albums/Show', [
            'album' => $album
        ]);
    }

    public function update(Request $request, Album $album)
    {
        Log::info('AlbumController@update - Incoming request data:', $request->all());
        
        // Check if user owns this album
        if ($album->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:10240', // 10MB max
        ]);
        
        Log::info('AlbumController@update - Validated data:', $validated);

        // Update album basic info
        $album->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
        ]);
        
        // Handle cover image upload if provided
        if ($request->hasFile('cover_image')) {
            Log::info('AlbumController@update - Processing cover image');
            
            // Delete old cover image if exists
            if ($album->cover_image_path) {
                $this->mediaService->deleteFile($album->cover_image_path);
            }
            
            // Store new cover image
            $result = $this->mediaService->storeFile(
                $request->file('cover_image'),
                'albums/' . $album->id
            );
            
            $album->update([
                'cover_image_path' => $result['path']
            ]);
            
            Log::info('AlbumController@update - Cover image stored:', $result);
        }

        Log::info('AlbumController@update - Album updated successfully');
        
        return redirect()->route('albums.show', $album)->with('message', 'Album updated successfully');
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
