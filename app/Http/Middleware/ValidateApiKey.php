<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (! $apiKey) {
            return response()->json(['error' => 'API key is required'], 401);
        }

        // Keys are stored hashed at rest; hash the incoming key to find its owner
        $user = User::query()->where('api_key', User::hashApiKey($apiKey))->first();

        if (! $user) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        // Optionally check if user is approved
        if (! $user->is_approved) {
            return response()->json(['error' => 'User account not approved'], 403);
        }

        // Set the authenticated user for the request
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
