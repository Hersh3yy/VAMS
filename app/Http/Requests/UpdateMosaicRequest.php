<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Mosaic;

class UpdateMosaicRequest extends BaseEntityRequest
{
    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Mosaic::class;
    }

    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by controllers
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'columns' => ['nullable', 'integer', 'min:2', 'max:5'],
            'items' => ['nullable', 'array'],
            'items.*.column_index' => ['required', 'integer', 'min:0'],
            'items.*.type' => ['required', 'string', 'in:album,media,color,text'],
            'items.*.content' => ['nullable'],
            'items.*.album_id' => ['nullable', 'uuid', 'exists:albums,id'],
            'items.*.properties' => ['nullable', 'array'],
            'items.*.order' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom error messages for validator
     */
    public function messages(): array
    {
        return [
            'title.max' => 'The mosaic title cannot be longer than 255 characters.',
            'columns.min' => 'The mosaic must have at least 2 columns.',
            'columns.max' => 'The mosaic cannot have more than 5 columns.',
            'items.array' => 'Please add at least one item to your mosaic before saving.',
            'items.*.type.required' => 'Invalid item type. Please refresh the page and try again.',
            'items.*.type.in' => 'Invalid item type. Please refresh the page and try again.',
            'items.*.column_index.required' => 'Invalid column position. Please refresh the page and try again.',
            'items.*.column_index.min' => 'Invalid column position. Please refresh the page and try again.',
            'items.*.album_id.exists' => 'The selected album does not exist.',
            'items.*.order.required' => 'Item order is required.',
        ];
    }
}

