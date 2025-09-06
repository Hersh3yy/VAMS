<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        Log::info('AuthenticatedSessionController@store - Login attempt started', [
            'url' => $request->url(),
            'full_url' => $request->fullUrl(),
            'is_secure' => $request->isSecure(),
            'scheme' => $request->getScheme(),
            'headers' => [
                'host' => $request->header('host'),
                'x-forwarded-proto' => $request->header('x-forwarded-proto'),
                'x-forwarded-for' => $request->header('x-forwarded-for'),
                'user-agent' => $request->header('user-agent'),
            ],
            'app_url' => config('app.url'),
            'asset_url' => config('app.asset_url'),
        ]);

        $request->authenticate();

        $request->session()->regenerate();

        // Flash a fresh CSRF token to ensure frontend synchronization
        $newCsrfToken = csrf_token();
        session()->flash('csrf_token_refresh', $newCsrfToken);
        
        Log::info('AuthenticatedSessionController@store - Session regenerated and CSRF token flashed', [
            'user_id' => $request->user()->id,
            'new_csrf_token' => substr($newCsrfToken, 0, 8) . '...',
            'session_id' => session()->getId(),
            'intended_url' => route('dashboard', absolute: false),
            'intended_url_absolute' => route('dashboard', absolute: true),
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
