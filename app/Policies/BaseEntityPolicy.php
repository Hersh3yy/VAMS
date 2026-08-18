<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy for all entities providing common authorization logic
 * Extend this class for entity-specific policies
 */
abstract class BaseEntityPolicy
{
    /**
     * Get the entity model class name
     */
    abstract protected function getEntityModelClass(): string;

    /**
     * Determine whether the user can view any models
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model
     */
    public function view(User $user, $model): Response
    {
        return $user->id === $model->user_id
            ? Response::allow()
            : Response::deny('You do not own this '.strtolower(class_basename($model)).'.');
    }

    /**
     * Determine whether the user can create models
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model
     */
    public function update(User $user, $model): Response
    {
        return $user->id === $model->user_id
            ? Response::allow()
            : Response::deny('You do not own this '.strtolower(class_basename($model)).'.');
    }

    /**
     * Determine whether the user can delete the model
     */
    public function delete(User $user, $model): Response
    {
        return $user->id === $model->user_id
            ? Response::allow()
            : Response::deny('You do not own this '.strtolower(class_basename($model)).'.');
    }

    /**
     * Determine whether the user can restore the model
     */
    public function restore(User $user, $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model
     */
    public function forceDelete(User $user, $model): bool
    {
        return false;
    }
}
