<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Album;

class StoreAlbumRequest extends BaseEntityRequest
{
    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Album::class;
    }
}
