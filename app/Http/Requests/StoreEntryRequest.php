<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Entry;

class StoreEntryRequest extends BaseEntityRequest
{
    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Entry::class;
    }
}
