<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

abstract class BaseApiController
{
    /**
     * Return a successful response
     */
    protected function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Return an error response
     */
    protected function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Return a not found response
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Return an unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Return a forbidden response
     */
    protected function forbidden(string $message = 'You do not have permission to access this resource'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Return a validation error response
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Check if the authenticated user owns the given model
     */
    protected function userOwnsModel(mixed $model, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        return isset($model->user_id) && $model->user_id === $user->id;
    }

    /**
     * Validate ownership and return error response if unauthorized
     */
    protected function validateOwnership(mixed $model, ?User $user = null): ?JsonResponse
    {
        if (! $this->userOwnsModel($model, $user)) {
            return $this->forbidden();
        }

        return null;
    }

    /**
     * Get the authenticated user
     */
    protected function user(): User
    {
        return Auth::user();
    }

    /**
     * Find an entity by title with case-insensitive fallback
     *
     * @param string $title The title to search for (will be URL-decoded)
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder to search on
     * @param bool $caseSensitive Whether to only match exact case (default: false)
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function findByTitle(string $title, $query, bool $caseSensitive = false): ?\Illuminate\Database\Eloquent\Model
    {
        $decodedTitle = urldecode($title);

        // Try exact match first
        $entity = $query->where('title', $decodedTitle)->first();

        // If no exact match and case-insensitive is enabled, try case-insensitive match
        if (! $entity && ! $caseSensitive) {
            $entity = $query->whereRaw('LOWER(title) = LOWER(?)', [$decodedTitle])->first();
        }

        return $entity;
    }
}
