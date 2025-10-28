<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EntryType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class EntryValidationService
{
    public function validateContent(EntryType $entryType, array $content): array
    {
        $rules = $this->buildValidationRules($entryType->field_config);

        $validator = Validator::make($content, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function buildValidationRules(array $fieldConfig): array
    {
        $rules = [];

        foreach ($fieldConfig as $field) {
            $fieldRules = [];

            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            match ($field['type']) {
                'text', 'textarea' => $fieldRules[] = 'string',
                'number' => $fieldRules[] = 'numeric',
                'checkbox' => $fieldRules[] = 'boolean',
                'repeatable' => $this->addRepeatableRules($rules, $field, $fieldRules),
                'image_collection' => $this->addImageCollectionRules($rules, $field, $fieldRules),
                'entry_relation' => $this->addEntryRelationRules($rules, $field, $fieldRules),
                'object' => $this->addObjectRules($rules, $field, $fieldRules),
                default => $fieldRules[] = 'string',
            };

            if (!empty($fieldRules)) {
                $rules[$field['name']] = $fieldRules;
            }
        }

        return $rules;
    }

    private function addRepeatableRules(array &$rules, array $field, array &$fieldRules): void
    {
        $name = $field['name'];
        $fieldRules[] = 'array';

        if (isset($field['min'])) {
            $fieldRules[] = 'min:'.$field['min'];
        }
        if (isset($field['max'])) {
            $fieldRules[] = 'max:'.$field['max'];
        }

        // Add rules for nested fields
        if (isset($field['fields'])) {
            foreach ($field['fields'] as $nestedField) {
                $nestedName = "{$name}.*.{$nestedField['name']}";
                $nestedRules = ($nestedField['required'] ?? false) ? ['required'] : ['nullable'];
                $nestedRules[] = match ($nestedField['type']) {
                    'text', 'textarea', 'image' => 'string',
                    'number' => 'numeric',
                    'checkbox' => 'boolean',
                    default => 'string',
                };
                $rules[$nestedName] = $nestedRules;
            }
        }
    }

    private function addImageCollectionRules(array &$rules, array $field, array &$fieldRules): void
    {
        $name = $field['name'];
        $fieldRules[] = 'array';

        if (isset($field['min'])) {
            $fieldRules[] = 'min:'.$field['min'];
        }
        if (isset($field['max'])) {
            $fieldRules[] = 'max:'.$field['max'];
        }

        // Add rules for image metadata fields
        if (isset($field['fields'])) {
            foreach ($field['fields'] as $nestedField) {
                $nestedName = "{$name}.*.{$nestedField['name']}";
                $nestedRules = ($nestedField['required'] ?? false) ? ['required'] : ['nullable'];
                $nestedRules[] = match ($nestedField['type']) {
                    'text', 'textarea' => 'string',
                    default => 'string',
                };
                $rules[$nestedName] = $nestedRules;
            }
        }
    }

    private function addEntryRelationRules(array &$rules, array $field, array &$fieldRules): void
    {
        $fieldRules[] = 'array';

        if (isset($field['min'])) {
            $fieldRules[] = 'min:'.$field['min'];
        }
        if (isset($field['max'])) {
            $fieldRules[] = 'max:'.$field['max'];
        }

        $name = $field['name'];
        $rules["{$name}.*"] = 'string|exists:entries,id';
    }

    private function addObjectRules(array &$rules, array $field, array &$fieldRules): void
    {
        $fieldRules[] = 'array';

        // Add rules for nested object fields
        if (isset($field['fields'])) {
            $name = $field['name'];
            foreach ($field['fields'] as $nestedField) {
                $nestedName = "{$name}.{$nestedField['name']}";
                $nestedRules = ($nestedField['required'] ?? false) ? ['required'] : ['nullable'];
                $nestedRules[] = match ($nestedField['type']) {
                    'text', 'textarea', 'image' => 'string',
                    'number' => 'numeric',
                    'checkbox' => 'boolean',
                    default => 'string',
                };
                $rules[$nestedName] = $nestedRules;
            }
        }
    }
}

