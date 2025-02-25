<?php

namespace App\Policies;

use App\Models\Mosaic;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MosaicPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Mosaic $mosaic): Response
    {
        return $user->id === $mosaic->user_id
            ? Response::allow()
            : Response::deny('You do not own this mosaic.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Mosaic $mosaic): Response
    {
        return $user->id === $mosaic->user_id
            ? Response::allow()
            : Response::deny('You do not own this mosaic.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Mosaic $mosaic): Response
    {
        return $user->id === $mosaic->user_id
            ? Response::allow()
            : Response::deny('You do not own this mosaic.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Mosaic $mosaic): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Mosaic $mosaic): bool
    {
        return false;
    }
}
