<?php

declare(strict_types=1);

namespace App\Services\Plans;

use App\Models\User;
use InvalidArgumentException;

/**
 * Gates resource creation against the current user's plan limits.
 *
 * Resources are identified by the same key used for both the
 * config/plans.php limit and the strategy registered for it (e.g.
 * "albums", "mosaics", "entries"), so adding a new limited resource is
 * just: add a config key + register a UsageStrategy below.
 */
final class PlanLimitService
{
    /**
     * @param  array<string, UsageStrategy>  $strategies
     */
    public function __construct(private readonly array $strategies) {}

    /**
     * Remaining quota for a resource, or null when the plan is unlimited.
     */
    public function remaining(User $user, string $resource): ?int
    {
        $strategy = $this->strategyFor($resource);
        $limit = $user->plan()->limit($strategy->limitKey());

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $strategy->currentUsage($user));
    }

    /**
     * Whether the user has already reached (or exceeded) their plan limit.
     */
    public function hasReached(User $user, string $resource): bool
    {
        return $this->remaining($user, $resource) === 0;
    }

    /**
     * A user-facing message describing the reached limit, for flashing back
     * to the UI when a create action is blocked.
     */
    public function limitMessage(User $user, string $resource): string
    {
        $strategy = $this->strategyFor($resource);
        $limit = $user->plan()->limit($strategy->limitKey());

        return "You've reached your {$user->plan()->name} plan's limit of {$limit} {$strategy->resourceLabel()}. Upgrade your plan to add more.";
    }

    /**
     * The per-file upload size cap for the user's plan, in kilobytes, for use
     * directly in a Laravel `max:` validation rule (which expects KB).
     */
    public function maxUploadSizeKb(User $user): int
    {
        $maxMb = $user->plan()->limit('max_upload_size_mb') ?? 25;

        return $maxMb * 1024;
    }

    private function strategyFor(string $resource): UsageStrategy
    {
        return $this->strategies[$resource]
            ?? throw new InvalidArgumentException("No plan usage strategy registered for resource [{$resource}].");
    }
}
