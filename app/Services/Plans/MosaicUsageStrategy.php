<?php

declare(strict_types=1);

namespace App\Services\Plans;

use App\Models\User;

final class MosaicUsageStrategy implements UsageStrategy
{
    public function limitKey(): string
    {
        return 'mosaics';
    }

    public function resourceLabel(): string
    {
        return 'mosaics';
    }

    public function currentUsage(User $user): int
    {
        return $user->mosaics()->count();
    }
}
