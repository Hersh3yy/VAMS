<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Services\ImageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class AlbumController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * The image service instance.
     *
     * @var \App\Services\ImageService
     */
    protected $imageService;
    
    /**
     * Create a new controller instance.
     *
     * @param \App\Services\ImageService $imageService
     * @return void
     */
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    
    /**
     * Display a listing of the resource.
     * 
     * @return \Inertia\Response
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $albums = $user->albums()->with('images')->get();
        
        return Inertia::render('Albums/Index', [
            'albums' => $albums
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * 
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Albums/Create');
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
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
            // Use ImageService to store the cover image
            $result = $this->imageService->storeImage(
                $request->file('cover_image'), 
                'album-covers'
            );
            
            $validated['cover_image_path'] = $result['url'];
        } else {
            $validated['cover_image_path'] = null;
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $album = $user->albums()->create($validated);

        return redirect()->route('albums.show', $album);
    }

    /**
     * Display the specified resource.
     * 
     * @param  \App\Models\Album  $album
     * @return \Inertia\Response
     */
    public function show(Album $album)
    {
        $this->authorize('view', $album);
        
        return Inertia::render('Albums/Show', [
            'album' => $album,
            'images' => $album->images()->orderBy('order')->get(),
            'auth' => [
                'user' => Auth::user(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * 
     * @param  \App\Models\Album  $album
     * @return \Inertia\Response
     */
    public function edit(Album $album)
    {
        $this->authorize('update', $album);
        
        return Inertia::render('Albums/Edit', [
            'album' => $album,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Album  $album
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Album $album)
    {
        $this->authorize('update', $album);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:5120', // 5MB max
        ]);
        
        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'],
        ];
        
        if ($request->hasFile('cover_image')) {
            // Use ImageService to store the cover image
            $result = $this->imageService->storeImage(
                $request->file('cover_image'), 
                'album-covers'
            );
            
            $data['cover_image_path'] = $result['url'];
        }
        
        $album->update($data);
        
        return redirect()->route('albums.show', $album)
            ->with('success', 'Album updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param  \App\Models\Album  $album
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Album $album)
    {
        $this->authorize('delete', $album);
        
        try {
            // Begin a transaction to ensure all operations succeed or fail together
            DB::beginTransaction();
            
            // Get all images to delete their files
            $images = $album->images()->get();
            
            // Delete image files from storage
            foreach ($images as $image) {
                // Check if it's a local file (not a video URL or external link)
                if (!$this->isVideoLink($image->path) && strpos($image->path, '/storage/') !== false) {
                    // Extract the path relative to the storage directory
                    $path = str_replace('/storage/', '', parse_url($image->path, PHP_URL_PATH));
                    if ($path) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
            
            // Delete all album images from the database
            $album->images()->delete();
            
            // Delete the album
            $album->delete();
            
            DB::commit();
            
            return redirect()->route('albums.index')
                ->with('success', 'Album deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete album: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Failed to delete album. ' . $e->getMessage());
        }
    }
    
    /**
     * Check if a URL is a video link (YouTube, Vimeo, etc.)
     *
     * @param string $url
     * @return bool
     */
    private function isVideoLink(string $url): bool
    {
        return (
            strpos($url, 'youtube.com') !== false || 
            strpos($url, 'youtu.be') !== false || 
            strpos($url, 'vimeo.com') !== false
        );
    }
}
