<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Entry;

class UpdateEntryRequest extends BaseEntityRequest
{
    protected function getEntityModelClass(): string
    {
        return Entry::class;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'min:1', 'max:255'],
            'content' => ['sometimes'],
            'status' => ['nullable', 'string', 'in:draft,published'],
        ];
    }
}
