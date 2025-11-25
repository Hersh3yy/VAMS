<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Album;
use App\Services\AlbumService;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AlbumController extends BaseEntityController
{
    public function __construct(
        protected readonly AlbumService $albumService,
        protected readonly ImageService $imageService
    ) {
        parent::__construct($albumService);
    }

    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Album::class;
    }

    /**
     * Get the form request class for this entity
     */
    protected function getFormRequestClass(): string
    {
        return \App\Http\Requests\StoreAlbumRequest::class;
    }

    /**
     * Get the view name for index page
     */
    protected function getIndexView(): string
    {
        return 'Albums/Index';
    }

    /**
     * Get the view name for create page
     */
    protected function getCreateView(): string
    {
        return 'Albums/Create';
    }

    /**
     * Get the view name for show page
     */
    protected function getShowView(): string
    {
        return 'Albums/Show';
    }

    /**
     * Get the view name for edit page
     */
    protected function getEditView(): string
    {
        return 'Albums/Edit';
    }

    /**
     * Get the route name prefix (e.g., 'albums' for albums.show, albums.index, etc.)
     */
    protected function getRouteNamePrefix(): string
    {
        return 'albums';
    }

    /**
     * Get the relationship name on the User model (e.g., 'albums', 'mosaics', 'entries')
     */
    protected function getRelationshipName(): string
    {
        return 'albums';
    }

    /**
     * Get the entity name for view data keys (e.g., 'album', 'mosaic', 'entry')
     */
    protected function getEntityName(): string
    {
        return 'Album';
    }

    /**
     * Display a listing of the authenticated user's albums
     */
    public function index(Request $request): Response
    {
        $albums = $this->entityService->getAll(false);

        return Inertia::render($this->getIndexView(), [
            'entities' => $albums,
            'albums' => $albums,
            ...$this->getAdditionalViewData(),
        ]);
    }

    /**
     * Get additional data to pass to views
     */
    protected function getAdditionalViewData(): array
    {
        return [
            'album_display_settings' => $this->user()->album_display_settings ?? [
                'caption' => true,
                'altText' => true,
                'dateCreated' => true,
                'location' => true,
                'tags' => true,
                'title' => true,
                'author' => true,
                'main_color' => '#4F46E5',
                'secondary_color' => '#10B981',
            ],
        ];
    }

    /**
     * Update the specified resource in storage
     */
    public function update(Request $request, $album): RedirectResponse
    {
        Log::info('AlbumController@update - Incoming request data:', $request->all());

        // Debug: Check what type $album is
        \Log::info('AlbumController@update - Parameter check:', [
            'album_type' => gettype($album),
            'album_value' => $album,
            'is_object' => is_object($album),
            'is_string' => is_string($album),
        ]);

        // If $album is a string (ID), resolve it to a model
        if (is_string($album)) {
            $album = \App\Models\Album::findOrFail($album);
        }

        // Check if user owns this album
        $this->authorizeOwnership($album);

        $formRequestClass = $this->getFormRequestClass();
        $formRequest = new $formRequestClass;
        $rules = $formRequest->rules();

        // Add additional validation rules specific to album updates
        $rules['cover_image'] = ['nullable', 'image', 'mimes:jpeg,png,jpg,gif'];
        $rules['selected_cover_image_id'] = ['nullable', 'exists:album_images,id'];

        $validated = $request->validate($rules);

        Log::info('AlbumController@update - Validated data:', $validated);

        // Update album basic info
        $updateData = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ];

        // Handle published field if provided
        if (isset($validated['published'])) {
            $updateData['published'] = (bool) $validated['published'];
        }

        $album->update($updateData);

        if (! empty($validated['selected_cover_image_id'])) {
            $selectedImage = $album->images()->where('id', $validated['selected_cover_image_id'])->first();
            if ($selectedImage) {
                $album->update([
                    'cover_image_path' => $selectedImage->path,
                ]);
            }
        } elseif ($request->hasFile('cover_image')) {
            $result = $this->imageService->storeImage(
                $request->file('cover_image'),
                "albums/{$album->id}/cover"
            );

            $album->update([
                'cover_image_path' => $result['url'],
            ]);
        }

        Log::info('AlbumController@update - Album updated successfully');

        return $this->redirectWithSuccess(
            'albums.show',
            $album,
            'Album updated successfully'
        );
    }
}
