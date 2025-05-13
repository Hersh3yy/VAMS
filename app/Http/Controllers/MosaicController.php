<?php

namespace App\Http\Controllers;

use App\Models\Mosaic;
use App\Services\AlbumService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MosaicController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * The album service instance.
     *
     * @var \App\Services\AlbumService
     */
    protected $albumService;
    
    /**
     * Create a new controller instance.
     *
     * @param \App\Services\AlbumService $albumService
     * @return void
     */
    public function __construct(AlbumService $albumService)
    {
        $this->albumService = $albumService;
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $mosaics = Mosaic::where('user_id', Auth::id())->with('items')->get();
        
        return Inertia::render('Mosaics/Index', [
            'mosaics' => $mosaics
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Mosaics/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'layout_settings' => 'nullable|json',
        ]);

        $validated['user_id'] = Auth::id();
        
        $mosaic = Mosaic::create($validated);
        
        return redirect()->route('mosaics.index')->with('success', 'Mosaic created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Mosaic $mosaic)
    {
        $this->authorize('view', $mosaic);
        
        return Inertia::render('Mosaics/Show', [
            'mosaic' => $mosaic,
            'items' => $mosaic->items()->orderBy('order')->get()
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mosaic $mosaic)
    {
        $this->authorize('update', $mosaic);
        
        // Get user's albums for the mosaic editor
        $albums = $this->albumService->getAllAlbums();
        
        return Inertia::render('Mosaics/Edit', [
            'mosaic' => $mosaic,
            'items' => $mosaic->items()->orderBy('order')->get(),
            'albums' => $albums,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mosaic $mosaic)
    {
        $this->authorize('update', $mosaic);
        
        $validated = $request->validate([
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'layout_settings' => 'nullable|json',
        ]);

        $mosaic->update($validated);
        
        return redirect()->route('mosaics.show', $mosaic);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mosaic $mosaic)
    {
        $this->authorize('delete', $mosaic);
        
        $mosaic->delete();
        
        return redirect()->route('mosaics.index');
    }
}
