<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BaseEntity;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Base policy for all entities providing common authorization logic
 * Extend this class for entity-specific policies
 */
abstract class BaseEntityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BaseEntity $entity): Response
    {
        return $user->id === $entity->user_id
            ? Response::allow()
            : Response::deny('You do not own this '.strtolower(class_basename($entity)).'.');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, BaseEntity $entity): Response
    {
        return $user->id === $entity->user_id
            ? Response::allow()
            : Response::deny('You do not own this '.strtolower(class_basename($entity)).'.');
    }

    public function delete(User $user, BaseEntity $entity): Response
    {
        return $user->id === $entity->user_id
            ? Response::allow()
            : Response::deny('You do not own this '.strtolower(class_basename($entity)).'.');
    }

    public function restore(User $user, BaseEntity $entity): bool
    {
        return false;
    }

    public function forceDelete(User $user, BaseEntity $entity): bool
    {
        return false;
    }
}
