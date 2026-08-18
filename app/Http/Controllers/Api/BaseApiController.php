<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\BaseEntity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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

    protected function userOwns(BaseEntity $entity, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        return $entity->user_id === $user->id;
    }

    protected function validateOwnership(BaseEntity $entity, ?User $user = null): ?JsonResponse
    {
        if (! $this->userOwns($entity, $user)) {
            return $this->forbidden();
        }

        return null;
    }

    protected function user(): User
    {
        return Auth::user();
    }

    /**
     * @param  Builder<BaseEntity>  $query
     */
    protected function findByTitle(string $title, Builder $query, bool $caseSensitive = false): ?BaseEntity
    {
        $decodedTitle = urldecode($title);

        $entity = $query->where('title', $decodedTitle)->first();

        if (! $entity instanceof BaseEntity && ! $caseSensitive) {
            $entity = $query->whereRaw('LOWER(title) = LOWER(?)', [$decodedTitle])->first();
        }

        return $entity instanceof BaseEntity ? $entity : null;
    }
}
