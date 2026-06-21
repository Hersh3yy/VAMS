<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Entry;

final class EntryPolicy extends BaseEntityPolicy
{
    protected function getEntityModelClass(): string
    {
        return Entry::class;
    }
}
