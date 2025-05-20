<?php

namespace App\Http\Controllers\Api\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

trait ValidatesApiKey
{
    protected function validateApiKey(Request $request): User|JsonResponse
    {
        $apiKey = $request->header('X-API-Key');
        if (!$apiKey) {
            return response()->json(['error' => 'API key is required'], 401);
        }

        $user = User::where('api_key', $apiKey)->first();
        if (!$user) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        return $user;
    }
} 