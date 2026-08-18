<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Models\Mosaic;

trait AuthorizesMosaicOwnership
{
    public function authorize(): bool
    {
        $mosaic = $this->route('mosaic');

        return $mosaic instanceof Mosaic && $this->user()?->can('update', $mosaic);
    }
}
