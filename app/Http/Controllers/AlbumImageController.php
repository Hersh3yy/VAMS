<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\AlbumImage;
use App\Services\ImageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AlbumImageController extends Controller
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
     */
    public function index()
    {
        //
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
        Log::info('Incoming request data:', $request->all());

        try {
            $request->validate([
                'album_id' => 'required|exists:albums,id',
                'images' => 'required|array',
                'images.*' => 'required|image|max:5120', // 5MB max
            ]);

            $album = Album::findOrFail($request->album_id);
            $this->authorize('update', $album);

            $lastOrder = $album->images()->max('order') ?? -1;

            $uploadedImages = [];
            foreach ($request->file('images') as $image) {
                Log::info('Processing image:', ['name' => $image->getClientOriginalName()]);

                // Use ImageService to store the image
                $result = $this->imageService->storeImage(
                    $image, 
                    "albums/{$album->id}"
                );
                
                Log::info('Image stored at path:', ['path' => $result['path']]);

                $uploadedImages[] = $album->images()->create([
                    'path' => $result['url'],
                    'order' => ++$lastOrder
                ]);
            }

            return response()->json($uploadedImages);
        } catch (\Exception $e) {
            Log::error('Error in AlbumImageController@store:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error uploading images: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AlbumImage $albumImage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AlbumImage $albumImage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AlbumImage $albumImage)
    {
        $this->authorize('update', $albumImage->album);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'altText' => 'nullable|string|max:255',
            'caption' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'dateCreated' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'image' => 'nullable|image|max:5120', // 5MB max
        ]);

        // The mutators in the model will handle mapping to the appropriate columns
        
        if ($request->hasFile('image')) {
            // Use ImageService to store the replacement image
            $result = $this->imageService->storeImage(
                $request->file('image'), 
                "albums/{$albumImage->album_id}"
            );
            
            $validated['path'] = $result['url'];
        }

        $albumImage->update($validated);

        return back()->with('message', 'Image updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AlbumImage $albumImage)
    {
        $this->authorize('delete', $albumImage->album);
        
        try {
            // Delete the image from storage if it's a local file
            if (!$this->isVideoLink($albumImage->path) && strpos($albumImage->path, '/storage/') !== false) {
                // Extract the path relative to the storage directory
                $path = str_replace('/storage/', '', parse_url($albumImage->path, PHP_URL_PATH));
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }
            
            // Delete the image record from the database
            $albumImage->delete();

            return response()->json(['message' => 'Image deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to delete image: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete image: ' . $e->getMessage()], 500);
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

    public function reorder(Request $request)
    {
        $request->validate([
            'image_id' => 'required|exists:album_images,id',
            'new_order' => 'required|integer|min:0',
        ]);

        $image = AlbumImage::findOrFail($request->image_id);
        $oldOrder = $image->order;
        $newOrder = $request->new_order;

        DB::transaction(function () use ($image, $oldOrder, $newOrder) {
            if ($oldOrder > $newOrder) {
                AlbumImage::where('album_id', $image->album_id)
                    ->where('order', '>=', $newOrder)
                    ->where('order', '<', $oldOrder)
                    ->increment('order');
            } else {
                AlbumImage::where('album_id', $image->album_id)
                    ->where('order', '>', $oldOrder)
                    ->where('order', '<=', $newOrder)
                    ->decrement('order');
            }

            $image->order = $newOrder;
            $image->save();
        });

        // Return an Inertia response instead of JSON
        return back()->with('message', 'Image order updated successfully');
    }

    /**
     * Store a video URL as an album image
     */
    public function storeVideo(Request $request)
    {
        Log::info('Incoming video request data:', $request->all());

        try {
            $request->validate([
                'album_id' => 'required|exists:albums,id',
                'url' => 'required|url',
                'title' => 'nullable|string|max:255',
                'caption' => 'nullable|string',
            ]);

            $album = Album::findOrFail($request->album_id);
            $this->authorize('update', $album);

            $lastOrder = $album->images()->max('order') ?? -1;
            
            // Store video thumbnail using ImageService
            $thumbnailResult = $this->imageService->storeVideoThumbnail(
                $request->url, 
                "albums/{$album->id}"
            );
            
            // Create properties JSON with video metadata
            $properties = [
                'type' => 'video',
                'video_url' => $request->url,
            ];
            
            // Add thumbnail URL if available
            if ($thumbnailResult) {
                $properties['thumbnail_url'] = $thumbnailResult['url'];
            }
            
            // Create the album image entry
            $albumImage = $album->images()->create([
                'path' => $request->url,
                'title' => $request->title,
                'caption' => $request->caption,
                'properties' => json_encode($properties),
                'order' => ++$lastOrder
            ]);

            return response()->json($albumImage);
        } catch (\Exception $e) {
            Log::error('Error in AlbumImageController@storeVideo:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error adding video: ' . $e->getMessage()
            ], 500);
        }
    }
}
