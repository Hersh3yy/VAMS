<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesMosaicOwnership;
use Illuminate\Foundation\Http\FormRequest;

final class ReorderMosaicItemsRequest extends FormRequest
{
    use AuthorizesMosaicOwnership;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'from_id' => ['required', 'string', 'exists:mosaic_items,id'],
            'to_id' => ['required', 'string', 'exists:mosaic_items,id'],
        ];
    }
}
