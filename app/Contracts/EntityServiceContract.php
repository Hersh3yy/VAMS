<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract for entity services that handle business logic
 */
interface EntityServiceContract
{
    /**
     * Get all entities for the current user or API context
     */
    public function getAll(bool $forApi = false): Collection;

    /**
     * Get a specific entity with its relationships
     */
    public function getById(string|Model $entity, bool $forApi = false): ?Model;

    /**
     * Format entity for API response
     */
    public function formatForApi(Model $entity): array;

    /**
     * Format entity with its media for API response
     */
    public function formatWithMediaForApi(Model $entity): array;

    /**
     * Get recent entities for the current user
     */
    public function getRecent(int $limit = 3): Collection;
}
