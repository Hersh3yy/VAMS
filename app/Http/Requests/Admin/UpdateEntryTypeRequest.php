<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

final class UpdateEntryTypeRequest extends StoreEntryTypeRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'is_active' => 'boolean',
        ];
    }
}
