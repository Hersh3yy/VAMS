<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Mosaic;

class StoreMosaicRequest extends BaseEntityRequest
{
    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Mosaic::class;
    }

    public function authorize(): bool
    {
        return true; // Authorization is handled by policies
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'columns' => ['required', 'integer', 'min:2', 'max:5'],
            'display_settings' => ['nullable', 'array'],
            'display_settings.grid_columns' => ['nullable', 'integer', 'min:1', 'max:5'],
            'display_settings.gap' => ['nullable', 'integer', 'min:0', 'max:100'],
            'display_settings.padding' => ['nullable', 'integer', 'min:0', 'max:100'],
            'display_settings.show_titles' => ['nullable', 'boolean'],
            'display_settings.show_captions' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The mosaic title is required.',
            'title.max' => 'The mosaic title cannot be longer than 255 characters.',
            'columns.required' => 'The number of columns is required.',
            'columns.min' => 'The mosaic must have at least 2 columns.',
            'columns.max' => 'The mosaic cannot have more than 5 columns.',
        ];
    }
}
