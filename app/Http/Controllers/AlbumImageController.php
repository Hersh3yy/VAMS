<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\AlbumImage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AlbumImageController extends Controller
{
    use AuthorizesRequests;

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

                $path = $image->store('album-images', 'spaces');
                Log::info('Image stored at path:', ['path' => $path]);

                $uploadedImages[] = $album->images()->create([
                    'path' => Storage::disk('spaces')->url($path),
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

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('album-images', 'spaces');
            $validated['path'] = Storage::disk('spaces')->url($path);
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
        
        // Delete the image from storage
        Storage::disk('spaces')->delete($albumImage->path);
        
        // Delete the image record from the database
        $albumImage->delete();

        return back()->with('message', 'Image deleted successfully');
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
}
