<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Album;
use App\Services\AlbumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AlbumController extends BaseEntityController
{
    public function __construct(
        protected readonly AlbumService $albumService
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
        $rules['cover_image'] = ['nullable', 'image', 'max:10240', 'mimes:jpeg,png,jpg,gif'];
        $rules['selected_cover_image_id'] = ['nullable', 'exists:album_images,id'];

        $validated = $request->validate($rules);

        Log::info('AlbumController@update - Validated data:', $validated);

        // Update album basic info
        $album->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        Log::info('AlbumController@update - Album updated successfully');

        return $this->redirectWithSuccess(
            $this->entityRouteNamePlural.'.show',
            $album,
            'Album updated successfully'
        );
    }
}
