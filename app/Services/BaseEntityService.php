<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntityServiceContract;
use App\Models\BaseEntity;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Base service for all entities providing common functionality
 * Extend this class for entity-specific services
 */
abstract class BaseEntityService implements EntityServiceContract
{
    /**
     * @return class-string<BaseEntity>
     */
    abstract protected function getEntityModelClass(): string;

    public function getAll(bool $forApi = false): Collection
    {
        $entityClass = $this->getEntityModelClass();

        if ($forApi) {
            $query = $entityClass::query();
            if (method_exists($entityClass, 'scopePublished')) {
                $query = $query->published();
            }

            return $query
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        $user = Auth::user();

        if (! $user) {
            return new Collection;
        }

        return $user->{$this->getEntityNamePlural()}()
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function getById(string|BaseEntity $entity, bool $forApi = false): ?BaseEntity
    {
        try {
            $entityClass = $this->getEntityModelClass();

            if (is_string($entity)) {
                $entity = $entityClass::query()->findOrFail($entity);
            }

            if (! $entity instanceof BaseEntity) {
                return null;
            }

            if ($forApi && property_exists($entity, 'published') && ! $entity->published) {
                return null;
            }

            return $entity;
        } catch (Exception $e) {
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
     * @return array<string, mixed>
     */
    public function formatForApi(BaseEntity $entity): array
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
     * @return array<string, mixed>
     */
    abstract public function formatWithMediaForApi(BaseEntity $entity): array;

    public function getRecent(int $limit = 3): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return new Collection;
        }

        return $user->{$this->getEntityNamePlural()}()
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    protected function getEntityName(): string
    {
        return strtolower(class_basename($this->getEntityModelClass()));
    }

    protected function getEntityNamePlural(): string
    {
        return $this->getEntityName().'s';
    }
}
