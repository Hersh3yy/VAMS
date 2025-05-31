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
use Illuminate\Support\Str;

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
        Log::info('AlbumImageController@store - Raw request data:', $request->all());
        Log::info('AlbumImageController@store - Request files:', $request->allFiles());
        Log::info('AlbumImageController@store - Request headers:', $request->headers->all());

        try {
            Log::info('AlbumImageController@store - Starting validation');
            
            $request->validate([
                'album_id' => 'required|exists:albums,id',
                'images' => 'required|array',
                'images.*' => 'required|image',
            ]);
            
            Log::info('AlbumImageController@store - Validation passed');

            $album = Album::findOrFail($request->album_id);
            Log::info('AlbumImageController@store - Found album:', ['album_id' => $album->id, 'title' => $album->title]);
            
            $this->authorize('update', $album);
            Log::info('AlbumImageController@store - Authorization passed');

            $lastOrder = $album->images()->max('order') ?? -1;
            Log::info('AlbumImageController@store - Last order:', ['order' => $lastOrder]);

            $uploadedImages = [];
            foreach ($request->file('images') as $index => $image) {
                Log::info('AlbumImageController@store - Processing image:', [
                    'index' => $index,
                    'name' => $image->getClientOriginalName(),
                    'size' => $image->getSize(),
                    'mime' => $image->getMimeType()
                ]);

                // Use ImageService to store the image
                $result = $this->imageService->storeImage(
                    $image, 
                    "albums/{$album->id}"
                );
                
                Log::info('AlbumImageController@store - Image stored:', $result);

                $albumImage = $album->images()->create([
                    'id' => Str::uuid(),
                    'path' => $result['url'],
                    'order' => ++$lastOrder
                ]);
                
                Log::info('AlbumImageController@store - AlbumImage created:', ['id' => $albumImage->id]);
                
                $uploadedImages[] = $albumImage;
            }

            Log::info('AlbumImageController@store - All images processed successfully', [
                'count' => count($uploadedImages)
            ]);

            // Return Inertia response with updated album data
            return back()->with([
                'message' => 'Images uploaded successfully',
                'images' => $uploadedImages
            ]);
        } catch (\Exception $e) {
            Log::error('AlbumImageController@store - Error occurred:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return back()->with('error', 'Error uploading images: ' . $e->getMessage());
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

            return back()->with('message', 'Item deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete image: ' . $e->getMessage());
            return back()->withErrors([
                'message' => 'Failed to delete item: ' . $e->getMessage()
            ]);
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

    public function reorder(Request $request, Album $album = null)
    {
        // Support both nested and non-nested routes
        if (!$album && $request->has('album_id')) {
            $album = Album::findOrFail($request->album_id);
        }
        
        $request->validate([
            'from_id' => 'required|exists:album_images,id',
            'to_id' => 'required|exists:album_images,id',
        ]);

        $fromImage = AlbumImage::findOrFail($request->from_id);
        $toImage = AlbumImage::findOrFail($request->to_id);
        
        // Ensure both images belong to the same album
        if ($fromImage->album_id !== $toImage->album_id) {
            return back()->withErrors(['message' => 'Images must belong to the same album']);
        }
        
        // If album is provided via route, ensure it matches
        if ($album && $fromImage->album_id !== $album->id) {
            return back()->withErrors(['message' => 'Image does not belong to this album']);
        }

        $fromOrder = $fromImage->order;
        $toOrder = $toImage->order;

        DB::transaction(function () use ($fromImage, $toImage, $fromOrder, $toOrder) {
            if ($fromOrder < $toOrder) {
                // Moving down: shift images between from and to positions up
                AlbumImage::where('album_id', $fromImage->album_id)
                    ->where('order', '>', $fromOrder)
                    ->where('order', '<=', $toOrder)
                    ->decrement('order');
                
                // Place the moved image at the target position
                $fromImage->order = $toOrder;
            } else {
                // Moving up: shift images between to and from positions down
                AlbumImage::where('album_id', $fromImage->album_id)
                    ->where('order', '>=', $toOrder)
                    ->where('order', '<', $fromOrder)
                    ->increment('order');
                
                // Place the moved image at the target position
                $fromImage->order = $toOrder;
            }
            
            $fromImage->save();
        });

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

            return back()->with('message', 'Video added successfully');
        } catch (\Exception $e) {
            Log::error('Error in AlbumImageController@storeVideo:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'message' => 'Error adding video: ' . $e->getMessage()
            ]);
        }
    }
}
