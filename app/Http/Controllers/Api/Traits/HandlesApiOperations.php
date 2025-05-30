<?php

namespace App\Http\Controllers\Api\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HandlesApiOperations
{
    protected function handleNotFound(string $message = 'Resource not found'): JsonResponse
    {
        return response()->json(['error' => $message], 404, [], JSON_UNESCAPED_UNICODE);
    }

    protected function handleUnauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return response()->json(['error' => $message], 403);
    }

    protected function handleSuccess($data = null, string $message = 'Success'): JsonResponse
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, 200, [], JSON_UNESCAPED_UNICODE);
    }

    protected function handleError(string $message, int $code = 500): JsonResponse
    {
        return response()->json(['error' => $message], $code, [], JSON_UNESCAPED_UNICODE);
    }

    protected function validateApiKey(Request $request)
    {
        $apiKey = $request->header('X-API-Key');
        if (!$apiKey) {
            return $this->handleUnauthorized('API key is required');
        }

        $user = \App\Models\User::where('api_key', $apiKey)->first();
        if (!$user) {
            return $this->handleUnauthorized('Invalid API key');
        }

        return $user;
    }

    protected function validateOwnership($model, $user): JsonResponse|bool
    {
        if ($model->user_id !== $user->id) {
            return $this->handleUnauthorized();
        }
        return true;
    }
} 