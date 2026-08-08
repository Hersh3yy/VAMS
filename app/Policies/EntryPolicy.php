<?php

namespace App\Policies;

use App\Models\Entry;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EntryPolicy extends BaseEntityPolicy
{
    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Entry::class;
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
    public function view(User $user, $entry): Response
    {
        return $user->id == $entry->user_id
            ? Response::allow()
            : Response::deny('You do not own this entry.');
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
    public function update(User $user, $entry): Response
    {
        return $user->id == $entry->user_id
            ? Response::allow()
            : Response::deny('You do not own this entry.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $entry): Response
    {
        return $user->id == $entry->user_id
            ? Response::allow()
            : Response::deny('You do not own this entry.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, $entry): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, $entry): bool
    {
        return false;
    }
}
