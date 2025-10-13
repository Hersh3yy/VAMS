<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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

    /**
     * Check if the authenticated user owns the given model
     */
    protected function userOwnsModel(mixed $model): bool
    {
        return isset($model->user_id) && $model->user_id == Auth::id();
    }

    /**
     * Abort if user doesn't own the model
     */
    protected function authorizeOwnership(mixed $model): void
    {
        if (! $this->userOwnsModel($model)) {
            abort(403, 'You do not have permission to access this resource.');
        }
    }

    /**
     * Get the authenticated user's ID
     */
    protected function userId(): int
    {
        return Auth::id();
    }

    /**
     * Get the authenticated user
     */
    protected function user(): \App\Models\User
    {
        return Auth::user();
    }
}
