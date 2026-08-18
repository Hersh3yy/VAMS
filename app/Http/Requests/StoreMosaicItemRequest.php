<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesMosaicOwnership;
use Illuminate\Foundation\Http\FormRequest;

final class StoreMosaicItemRequest extends FormRequest
{
    use AuthorizesMosaicOwnership;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:album,media,color,text'],
            'properties' => ['required', 'array'],
            'order' => ['required', 'integer', 'min:0'],
            'column_index' => ['required', 'integer', 'min:0'],
        ];
    }
}
