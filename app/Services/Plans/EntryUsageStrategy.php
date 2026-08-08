<?php

declare(strict_types=1);

namespace App\Services\Plans;

use App\Models\User;

final class EntryUsageStrategy implements UsageStrategy
{
    public function limitKey(): string
    {
        return 'entries';
    }

    public function resourceLabel(): string
    {
        return 'entries';
    }

    public function currentUsage(User $user): int
    {
        return $user->entries()->count();
    }
}
