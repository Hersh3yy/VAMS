<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlbumRequest;
use App\Http\Requests\UpdateAlbumRequest;
use App\Models\Album;
use App\Models\AlbumImage;
use App\Services\AlbumService;
use App\Services\ImageService;
use App\Services\Plans\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

class AlbumController extends BaseEntityController
{
    public function __construct(
        AlbumService $albumService,
        private readonly ImageService $imageService,
        PlanLimitService $planLimitService,
    ) {
        parent::__construct($albumService, $planLimitService);
    }

    protected function getEntityModelClass(): string
    {
        return Album::class;
    }

    protected function getFormRequestClass(): string
    {
        return StoreAlbumRequest::class;
    }

    protected function getIndexView(): string
    {
        return 'Albums/Index';
    }

    protected function getCreateView(): string
    {
        return 'Albums/Create';
    }

    protected function getShowView(): string
    {
        return 'Albums/Show';
    }

    protected function getEditView(): string
    {
        return 'Albums/Edit';
    }

    protected function getRouteNamePrefix(): string
    {
        return 'albums';
    }

    protected function getRelationshipName(): string
    {
        return 'albums';
    }

    protected function getEntityName(): string
    {
        return 'Album';
    }

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

    public function update(UpdateAlbumRequest $request, Album $album): RedirectResponse
    {
        $this->authorizeOwnership($album);

        $validated = $request->validated();

        if (isset($validated['published'])) {
            $validated['published'] = (bool) $validated['published'];
        }

        $this->fillValidatedAttributes($album, $validated, ['title', 'description', 'published']);

        if (! empty($validated['selected_cover_image_id'])) {
            $selectedImage = $album->images()->where('id', $validated['selected_cover_image_id'])->first();
            if ($selectedImage instanceof AlbumImage) {
                $album->update(['cover_image_path' => $this->coverPathFor($selectedImage)]);
            }
        } elseif ($request->hasFile('cover_image')) {
            $coverImage = $request->file('cover_image');
            assert($coverImage instanceof UploadedFile);

            $result = $this->imageService->storeImage(
                $coverImage,
                "albums/{$album->id}/cover"
            );

            $album->update(['cover_image_path' => $result['url']]);
        }

        return $this->redirectWithSuccess(
            'albums.show',
            $album,
            'Album updated successfully'
        );
    }

    public function destroy(Album $album): RedirectResponse
    {
        return $this->deleteOwned($album);
    }

    private function coverPathFor(AlbumImage $image): string
    {
        $properties = is_array($image->properties) ? $image->properties : [];

        return $properties['thumbnail_url'] ?? $image->path;
    }
}
