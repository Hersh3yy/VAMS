<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;

class AlbumPolicy
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
    public function view(User $user, Album $album): Response
    {
        Log::info('User ID: ' . $user->id);
        Log::info('Album User ID: ' . $album->user_id);
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
    public function update(User $user, Album $album): Response
    {
        return $user->id === $album->user_id
            ? Response::allow()
            : Response::deny('You do not own this album.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Album $album): Response
    {
        return $user->id === $album->user_id
            ? Response::allow()
            : Response::deny('You do not own this album.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Album $album): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Album $album): bool
    {
        return false;
    }
}
