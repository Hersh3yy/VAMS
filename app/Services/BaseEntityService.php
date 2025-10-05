<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntityServiceContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Base service for all entities providing common functionality
 * Extend this class for entity-specific services
 */
abstract class BaseEntityService implements EntityServiceContract
{
    /**
     * Get the entity model class name
     */
    abstract protected function getEntityModelClass(): string;

    /**
     * Get relationships to load for web context
     */
    protected function getWebRelationships(): array
    {
        return [];
    }

    /**
     * Get relationships to load for API context
     */
    protected function getApiRelationships(): array
    {
        return [];
    }

    /**
     * Get all entities for the current user or API context
     */
    public function getAll(bool $forApi = false): Collection
    {
        $entityModelClass = $this->getEntityModelClass();
        $relationships = $forApi ? $this->getApiRelationships() : $this->getWebRelationships();

        if ($forApi) {
            // For API, we return all published entities
            return $entityModelClass::with($relationships)
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        // For web, we only return the user's entities
        $user = Auth::user();

        if (! $user) {
            return new Collection;
        }

        return $user->{$this->getEntityNamePlural()}()
            ->with($relationships)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get a specific entity with its relationships
     */
    public function getById(string|Model $entity, bool $forApi = false): ?Model
    {
        try {
            $entityModelClass = $this->getEntityModelClass();

            if (is_string($entity)) {
                $entity = $entityModelClass::findOrFail($entity);
            }

            if (! $entity instanceof Model) {
                return null;
            }

            $relationships = $forApi ? $this->getApiRelationships() : $this->getWebRelationships();

            // Load relationships
            if (! empty($relationships)) {
                $entity->load($relationships);
            }

            return $entity;
        } catch (\Exception $e) {
            Log::error('Error in '.class_basename($this).'@getById:', [
                'entity_id' => is_string($entity) ? $entity : $entity->id ?? 'unknown',
                'for_api' => $forApi,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Format entity for API response
     */
    public function formatForApi(Model $entity): array
    {
        return [
            'id' => $entity->id,
            'title' => $entity->getTitle(),
            'description' => $entity->getDescription(),
            'user_id' => $entity->user_id,
            'created_at' => $entity->created_at?->toISOString(),
            'updated_at' => $entity->updated_at?->toISOString(),
        ];
    }

    /**
     * Format entity with its media for API response
     */
    abstract public function formatWithMediaForApi(Model $entity): array;

    /**
     * Get recent entities for the current user
     */
    public function getRecent(int $limit = 3): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return new Collection;
        }

        $entityModelClass = $this->getEntityModelClass();

        return $user->{$this->getEntityNamePlural()}()
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the entity name for this service
     */
    protected function getEntityName(): string
    {
        $className = class_basename($this->getEntityModelClass());

        return strtolower($className);
    }

    /**
     * Get the entity name plural for this service
     */
    protected function getEntityNamePlural(): string
    {
        return $this->getEntityName().'s';
    }
}
