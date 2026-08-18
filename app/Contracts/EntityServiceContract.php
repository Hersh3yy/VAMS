<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\BaseEntity;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for entity services that handle business logic
 */
interface EntityServiceContract
{
    public function getAll(bool $forApi = false): Collection;

    public function getById(string|BaseEntity $entity, bool $forApi = false): ?BaseEntity;

    /**
     * @return array<string, mixed>
     */
    public function formatForApi(BaseEntity $entity): array;

    /**
     * @return array<string, mixed>
     */
    public function formatWithMediaForApi(BaseEntity $entity): array;

    public function getRecent(int $limit = 3): Collection;
}
