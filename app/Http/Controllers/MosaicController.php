<?php

namespace App\Http\Controllers;

use App\Models\Mosaic;
use App\Models\MosaicItem;
use App\Models\User;
use App\Services\MosaicService;
use App\Http\Controllers\Api\Traits\HandlesMosaicOperations;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class MosaicController extends Controller
{
    use HandlesMosaicOperations;

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
        $mosaic = $this->getMosaicWithItems($mosaic, false);

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
                            'order' => $image->order
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'columns' => 'required|integer|min:2|max:5',
            'items' => 'required|array',
            'items.*.id' => 'required|string',
            'items.*.column_index' => 'required|integer|min:0',
            'items.*.type' => 'required|string|in:image,text,album',
            'items.*.content' => 'nullable|array',
            'items.*.album_id' => 'nullable|uuid|exists:albums,id',
            'items.*.properties' => 'nullable|array',
            'items.*.order' => 'required|integer',
        ]);

        // Update mosaic basic info
        $mosaic->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'columns' => $validated['columns'],
        ]);

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
        return $this->handleMosaicSplit($mosaicItem, $request);
    }
}
