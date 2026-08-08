<?php

declare(strict_types=1);

namespace App\Services\Plans;

use App\Models\User;

final class AlbumUsageStrategy implements UsageStrategy
{
    public function limitKey(): string
    {
        return 'albums';
    }

    public function resourceLabel(): string
    {
        return 'albums';
    }

    public function currentUsage(User $user): int
    {
        return $user->albums()->count();
    }
}
