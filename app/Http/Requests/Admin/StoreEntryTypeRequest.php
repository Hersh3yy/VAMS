<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntryTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->fieldConfigRules();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    private function fieldConfigRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'field_config' => 'required|array|min:1',
            'field_config.*.name' => 'required|string',
            'field_config.*.type' => 'required|string|in:text,textarea,number,select,checkbox,image,json,repeatable,image_collection,entry_relation,object',
            'field_config.*.label' => 'required|string',
            'field_config.*.required' => 'boolean',
            'field_config.*.placeholder' => 'nullable|string',
            'field_config.*.options' => 'nullable|array',
            'field_config.*.fields' => 'nullable|array',
            'field_config.*.fields.*.name' => 'required_with:field_config.*.fields|string',
            'field_config.*.fields.*.type' => 'required_with:field_config.*.fields|string|in:text,textarea,number,select,checkbox,image',
            'field_config.*.fields.*.label' => 'nullable|string',
            'field_config.*.fields.*.required' => 'nullable|boolean',
            'field_config.*.fields.*.placeholder' => 'nullable|string',
            'field_config.*.min' => 'nullable|integer',
            'field_config.*.max' => 'nullable|integer',
            'field_config.*.entry_type_slug' => 'nullable|string',
            'field_config.*.exclude_current' => 'boolean',
            'field_config.*.collapsible' => 'boolean',
        ];
    }
}
