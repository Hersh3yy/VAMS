<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BaseEntity;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

abstract class BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Redirect with success message
     */
    protected function redirectWithSuccess(string $route, mixed $parameters = [], string $message = 'Operation completed successfully'): RedirectResponse
    {
        return Redirect::route($route, $parameters)->with('message', $message);
    }

    /**
     * Redirect with error message
     */
    protected function redirectWithError(string $route, mixed $parameters = [], string $message = 'An error occurred'): RedirectResponse
    {
        return Redirect::route($route, $parameters)->with('error', $message);
    }

    /**
     * Redirect back with error message
     */
    protected function redirectBackWithError(string $message = 'An error occurred'): RedirectResponse
    {
        return Redirect::back()->with('error', $message);
    }

    protected function userOwns(BaseEntity $entity): bool
    {
        return $entity->user_id === Auth::id();
    }

    protected function authorizeOwnership(BaseEntity $entity): void
    {
        if (! $this->userOwns($entity)) {
            abort(403, 'You do not have permission to access this resource.');
        }
    }

    /**
     * Get the authenticated user's ID
     */
    protected function userId(): string
    {
        return Auth::id();
    }

    /**
     * Get the authenticated user
     */
    protected function user(): User
    {
        return Auth::user();
    }
}
