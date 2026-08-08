<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Album;
use App\Services\AlbumService;
use App\Services\ImageService;
use App\Services\Plans\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlbumController extends BaseEntityController
{
    public function __construct(
        protected readonly AlbumService $albumService,
        protected readonly ImageService $imageService,
        PlanLimitService $planLimitService,
    ) {
        parent::__construct($albumService, $planLimitService);
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
     * Get the form request class for updating this entity
     */
    protected function getUpdateFormRequestClass(): string
    {
        return \App\Http\Requests\UpdateAlbumRequest::class;
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
        // Resolve album to model instance
        $albumModel = $this->resolveEntity($album);

        // Check if user owns this album
        $this->authorizeOwnership($albumModel);

        // Use UpdateAlbumRequest for validation
        $formRequestClass = $this->getUpdateFormRequestClass();
        $validated = $request->validate((new $formRequestClass)->rules());

        // Update album basic info (only if provided)
        $updateData = [];

        if (isset($validated['title'])) {
            $updateData['title'] = $validated['title'];
        }

        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }

        // Handle published field if provided
        if (isset($validated['published'])) {
            $updateData['published'] = (bool) $validated['published'];
        }

        if (! empty($updateData)) {
            $albumModel->update($updateData);
        }

        // Handle cover image: selected image takes precedence over uploaded file
        if (! empty($validated['selected_cover_image_id'])) {
            $selectedImage = $albumModel->images()->where('id', $validated['selected_cover_image_id'])->first();
            if ($selectedImage) {
                // Get thumbnail URL for videos, regular path for images
                $coverPath = $this->getImagePathForCover($selectedImage);
                $albumModel->update(['cover_image_path' => $coverPath]);
            }
        } elseif ($request->hasFile('cover_image')) {
            $result = $this->imageService->storeImage(
                $request->file('cover_image'),
                "albums/{$albumModel->id}/cover"
            );

            $albumModel->update(['cover_image_path' => $result['url']]);
        }

        return $this->redirectWithSuccess(
            'albums.show',
            $albumModel,
            'Album updated successfully'
        );
    }

    /**
     * Get the appropriate image path for cover (handles video thumbnails)
     */
    private function getImagePathForCover($image): string
    {
        if ($image->properties) {
            $properties = is_string($image->properties)
                ? json_decode($image->properties, true)
                : $image->properties;

            // Use thumbnail URL for videos if available
            if (isset($properties['thumbnail_url'])) {
                return $properties['thumbnail_url'];
            }
        }

        return $image->path;
    }
}
