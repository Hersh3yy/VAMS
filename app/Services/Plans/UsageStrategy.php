<?php

declare(strict_types=1);

namespace App\Services\Plans;

use App\Models\User;

/**
 * Strategy for counting a user's current usage of a plan-limited resource.
 *
 * @see https://refactoring.guru/design-patterns/strategy
 */
interface UsageStrategy
{
    /**
     * The config/plans.php limit key this strategy counts usage for.
     */
    public function limitKey(): string;

    /**
     * A human-readable, pluralized label for error messaging (e.g. "albums").
     */
    public function resourceLabel(): string;

    /**
     * The user's current usage count for this resource.
     */
    public function currentUsage(User $user): int;
}
