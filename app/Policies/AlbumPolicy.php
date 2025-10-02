<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\User;
use App\Policies\BaseEntityPolicy;
use Illuminate\Auth\Access\Response;

class AlbumPolicy extends BaseEntityPolicy
{
    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Album::class;
    }
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
    public function view(User $user, $album): Response
    {
        \Illuminate\Support\Facades\Log::info('User ID: ' . $user->id);
        \Illuminate\Support\Facades\Log::info('Album User ID: ' . $album->user_id);
        return $user->id === $album->user_id
            ? Response::allow()
            : Response::deny('You do not own this album.');
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
    public function update(User $user, $album): Response
    {
        return $user->id === $album->user_id
            ? Response::allow()
            : Response::deny('You do not own this album.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $album): Response
    {
        return $user->id === $album->user_id
            ? Response::allow()
            : Response::deny('You do not own this album.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, $album): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, $album): bool
    {
        return false;
    }
}
