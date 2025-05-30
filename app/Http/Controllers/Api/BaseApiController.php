<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseApiController extends Controller
{
    protected function success(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $data
        ], $status);
    }

    protected function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], $status);
    }

    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    protected function validationError(array $errors): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }
} 