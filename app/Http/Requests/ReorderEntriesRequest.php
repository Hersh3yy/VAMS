<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderEntriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'orderedIds' => ['required', 'array'],
            'orderedIds.*' => ['required', 'uuid', 'exists:entries,id'],
            'entry_type_id' => ['required', 'uuid', 'exists:entry_types,id'],
        ];
    }
}
