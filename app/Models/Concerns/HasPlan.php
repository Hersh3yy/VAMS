<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Services\Plans\Plan;

/**
 * Resolves the subscription Plan a user is entitled to.
 *
 * Reads from the `plan` column today; once billing lands, the same
 * `plan()` accessor can instead resolve the slug from the user's active
 * Cashier subscription without changing any entitlement check call site.
 */
trait HasPlan
{
    public function plan(): Plan
    {
        return Plan::for($this->plan ?? null);
    }
}
