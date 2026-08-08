<?php

declare(strict_types=1);

namespace App\Services\Plans;

/**
 * Immutable read model over a single entry from config/plans.php.
 *
 * Deliberately has no notion of billing/Stripe: a subscription provider
 * only needs to resolve which plan slug a user is on (e.g. by mapping a
 * Stripe price ID to a slug); every entitlement check reads from here.
 */
final readonly class Plan
{
    /**
     * @param  array<string, int|null>  $limits
     */
    public function __construct(
        public string $slug,
        public string $name,
        private array $limits,
    ) {}

    public static function for(?string $slug): self
    {
        $plans = config('plans.plans', []);
        $resolvedSlug = $slug ?? config('plans.default');

        if (! isset($plans[$resolvedSlug])) {
            $resolvedSlug = config('plans.default');
        }

        $config = $plans[$resolvedSlug] ?? ['name' => 'Free', 'limits' => []];

        return new self($resolvedSlug, $config['name'], $config['limits'] ?? []);
    }

    /**
     * The configured limit for a given key, or null when unlimited/undefined.
     */
    public function limit(string $key): ?int
    {
        return $this->limits[$key] ?? null;
    }
}
