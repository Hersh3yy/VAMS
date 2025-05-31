<?php

namespace App\Http\Controllers;

use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Models\User;
use App\Services\MosaicService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class MosaicController extends Controller
{
    protected $mosaicService;

    public function __construct(MosaicService $mosaicService)
    {
        $this->mosaicService = $mosaicService;
    }

    // Web Routes
    public function index()
    {
        $mosaics = $this->mosaicService->getAllMosaics(false);

        return Inertia::render('Mosaics/Index', [
            'mosaics' => $mosaics
        ]);
    }

    public function create()
    {
        return Inertia::render('Mosaics/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'columns' => 'required|integer|min:2|max:5',
        ]);

        $user = Auth::user();
        $mosaic = $user->mosaics()->create([
            'id' => Str::uuid(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'columns' => $validated['columns'],
        ]);

        return redirect()->route('mosaics.edit', $mosaic);
    }

    public function edit(Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        // Get mosaic with items using service
        $mosaic = $this->mosaicService->getMosaicWithItems($mosaic, false);

        // Get user's albums with their images
        $albums = Auth::user()->albums()
            ->with(['images' => function($query) {
                $query->orderBy('order');
            }])
            ->get()
            ->map(function($album) {
                return [
                    'id' => $album->id,
                    'title' => $album->title,
                    'cover_image_path' => $album->cover_image_path,
                    'images_count' => $album->images->count(),
                    'images' => $album->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'path' => $image->path,
                            'order' => $image->order,
                            'title' => $image->title,
                            'caption' => $image->caption,
                            'properties' => $image->properties
                        ];
                    })
                ];
            });

        return Inertia::render('Mosaics/Edit', [
            'mosaic' => $mosaic,
            'albums' => $albums
        ]);
    }

    public function show(Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        // Get mosaic with items using service
        $mosaic = $this->mosaicService->getMosaicWithItems($mosaic, false);

        // Get user's albums with their images
        $albums = Auth::user()->albums()
            ->with(['images' => function($query) {
                $query->orderBy('order');
            }])
            ->get()
            ->map(function($album) {
                return [
                    'id' => $album->id,
                    'title' => $album->title,
                    'cover_image_path' => $album->cover_image_path,
                    'images_count' => $album->images->count(),
                    'images' => $album->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'path' => $image->path,
                            'order' => $image->order,
                            'title' => $image->title,
                            'caption' => $image->caption,
                            'properties' => $image->properties
                        ];
                    })
                ];
            });

        return Inertia::render('Mosaics/Edit', [
            'mosaic' => $mosaic,
            'albums' => $albums
        ]);
    }

    public function update(Request $request, Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'columns' => 'nullable|integer|min:2|max:5',
            'items' => 'required|array',
            'items.*.id' => 'required|string',
            'items.*.column_index' => 'required|integer|min:0',
            'items.*.type' => 'required|string|in:album,media,color',
            'items.*.content' => 'nullable',
            'items.*.album_id' => 'nullable|uuid|exists:albums,id',
            'items.*.properties' => 'nullable|array',
            'items.*.order' => 'required|integer',
        ]);

        // Update mosaic basic info if provided
        $updateData = [];
        if (isset($validated['title'])) {
            $updateData['title'] = $validated['title'];
        }
        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }
        if (isset($validated['columns'])) {
            $updateData['columns'] = $validated['columns'];
        }
        if (!empty($updateData)) {
            $mosaic->update($updateData);
        }

        // Update items
        $mosaic->items()->delete(); // Remove old items
        foreach ($validated['items'] as $item) {
            $mosaic->items()->create([
                'id' => $item['id'],
                'column_index' => $item['column_index'],
                'type' => $item['type'],
                'content' => $item['content'] ?? null,
                'album_id' => $item['album_id'] ?? null,
                'properties' => $item['properties'] ?? null,
                'order' => $item['order'],
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $mosaic->items()->delete();
        $mosaic->delete();

        return redirect()->route('mosaics.index');
    }

    // API Routes
    public function showApi(Request $request, Mosaic $mosaic): JsonResponse
    {
        $mosaic = $this->mosaicService->getMosaic($mosaic, true);
        if (!$mosaic) {
            return response()->json(['error' => 'Mosaic not found'], 404, [], JSON_UNESCAPED_UNICODE);
        }
        
        return response()->json(
            $this->mosaicService->formatMosaicWithItemsForApi($mosaic), 
            200, 
            [], 
            JSON_UNESCAPED_UNICODE
        );
    }

    public function showByTitle(string $title): JsonResponse
    {
        $mosaic = Mosaic::where('title', $title)->first();
        
        if (!$mosaic) {
            return response()->json(['error' => 'Mosaic not found'], 404);
        }
        
        return $this->showApi(request(), $mosaic);
    }

    public function showByTitleWithApiKey(string $title, Request $request): JsonResponse
    {
        $user = $request->user();
        
        $mosaic = $user->mosaics()->where('title', $title)->first();
        if (!$mosaic) {
            return response()->json(['error' => 'Mosaic not found'], 404);
        }
        
        return $this->showApi($request, $mosaic);
    }

    public function indexApi(Request $request): JsonResponse
    {
        $user = $request->user();
        $mosaics = $user->mosaics()->with('items')->get();
        
        return response()->json([
            'mosaics' => $mosaics->map(fn($mosaic) => $this->mosaicService->formatMosaicForApi($mosaic))
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function split(MosaicItem $mosaicItem, Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($mosaicItem->mosaic->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $newItem = $this->mosaicService->splitItem($mosaicItem);

        return response()->json([
            'item' => $this->mosaicService->formatMosaicItemForApi($newItem)
        ]);
    }

    public function storeItem(Request $request, Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|string|in:album,media,color',
            'properties' => 'required|array',
            'order' => 'required|integer',
            'column_index' => 'required|integer|min:0',
        ]);

        $item = $mosaic->items()->create([
            'id' => Str::uuid(),
            'type' => $validated['type'],
            'properties' => $validated['properties'],
            'order' => $validated['order'],
            'column_index' => $validated['column_index'],
        ]);

        return response()->json($item);
    }

    public function updateItem(Request $request, Mosaic $mosaic, MosaicItem $item)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|string|in:album,media,color',
            'properties' => 'required|array',
            'order' => 'required|integer',
            'column_index' => 'required|integer|min:0',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    public function destroyItem(Mosaic $mosaic, MosaicItem $item)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $item->delete();

        return response()->json(['success' => true]);
    }

    public function reorderItems(Request $request, Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|string|exists:mosaic_items,id',
            'items.*.order' => 'required|integer',
            'items.*.column_index' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            $mosaic->items()->where('id', $item['id'])->update([
                'order' => $item['order'],
                'column_index' => $item['column_index'],
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function storeMedia(Request $request, Mosaic $mosaic)
    {
        // Check if user owns this mosaic
        if ($mosaic->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'media' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:10240', // 10MB max
        ]);

        $file = $request->file('media');
        $path = $file->store('mosaics/' . $mosaic->id, 'public');
        $type = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';

        return response()->json([
            'path' => Storage::url($path),
            'type' => $type,
        ]);
    }
}
