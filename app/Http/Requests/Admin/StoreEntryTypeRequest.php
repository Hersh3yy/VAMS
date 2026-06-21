<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntryTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'field_config' => 'required|array|min:1',
            'field_config.*.name' => 'required|string|distinct',
            'field_config.*.type' => 'required|string|in:text,textarea,number,select,checkbox,image,repeatable,image_collection,entry_relation,object',
            'field_config.*.label' => 'required|string',
            'field_config.*.required' => 'boolean',
            'field_config.*.placeholder' => 'nullable|string',
            // Select fields must declare their options
            'field_config.*.options' => 'required_if:field_config.*.type,select|nullable|array',
            'field_config.*.options.*' => 'string',
            // Repeatable, object, and image_collection fields can have nested fields
            'field_config.*.fields' => 'nullable|array',
            'field_config.*.fields.*.name' => 'required_with:field_config.*.fields|string',
            'field_config.*.fields.*.type' => 'required_with:field_config.*.fields|string|in:text,textarea,number,select,checkbox,image',
            'field_config.*.fields.*.label' => 'nullable|string',
            'field_config.*.fields.*.required' => 'nullable|boolean',
            'field_config.*.fields.*.placeholder' => 'nullable|string',
            'field_config.*.fields.*.options' => 'nullable|array',
            'field_config.*.fields.*.options.*' => 'string',
            // Min/max for repeatable, image_collection, and entry_relation
            'field_config.*.min' => 'nullable|integer|min:0',
            'field_config.*.max' => 'nullable|integer|min:1',
            // For entry_relation
            'field_config.*.entry_type_slug' => 'nullable|string',
            'field_config.*.exclude_current' => 'boolean',
            // For object fields
            'field_config.*.collapsible' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'field_config.required' => 'At least one field is required.',
            'field_config.*.name.distinct' => 'Field names must be unique within an entry type.',
            'field_config.*.type.in' => 'Unsupported field type.',
            'field_config.*.options.required_if' => 'Select fields must define their options.',
        ];
    }
}
