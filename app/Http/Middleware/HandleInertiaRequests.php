<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Debug logging for Inertia requests
        if ($request->is('login')) {
            Log::info('HandleInertiaRequests@share - Login page request', [
                'url' => $request->url(),
                'full_url' => $request->fullUrl(),
                'is_secure' => $request->isSecure(),
                'scheme' => $request->getScheme(),
                'app_url' => config('app.url'),
                'asset_url' => config('app.asset_url'),
                'ziggy_location' => $request->url(),
            ]);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'logo_url' => $request->user()->logo_url,
                    'is_admin' => $request->user()->is_admin,
                ] : null,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            // Pass CSRF token refresh if available (for post-login synchronization)
            'csrf_token_refresh' => session('csrf_token_refresh'),
        ];
    }
}
