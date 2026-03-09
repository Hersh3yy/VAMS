<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

final class UpdateEntryTypeRequest extends StoreEntryTypeRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'is_active' => 'boolean',
        ]);
    }
}
