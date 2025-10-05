<?php

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

        // Find user by API key
        $user = User::where('api_key', $apiKey)->first();

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
