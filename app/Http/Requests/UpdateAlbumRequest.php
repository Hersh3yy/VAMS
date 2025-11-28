<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Album;

class UpdateAlbumRequest extends BaseEntityRequest
{
    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Album::class;
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        $baseRules = parent::rules();

        // Add update-specific rules
        $baseRules['cover_image'] = ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'];
        $baseRules['selected_cover_image_id'] = ['nullable', 'exists:album_images,id'];
        $baseRules['published'] = ['nullable', 'boolean'];

        return $baseRules;
    }
}
