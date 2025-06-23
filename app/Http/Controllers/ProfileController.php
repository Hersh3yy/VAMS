<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ImageService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * The image service instance.
     *
     * @var \App\Services\ImageService
     */
    protected $imageService;

    /**
     * Create a new controller instance.
     *
     * @param \App\Services\ImageService $imageService
     * @return void
     */
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'album_display_settings' => $request->user()->album_display_settings ?? [
                'caption' => true,
                'altText' => true,
                'dateCreated' => true,
                'location' => true,
                'tags' => true,
                'title' => true,
                'author' => true,
                'main_color' => '#4F46E5', // Default indigo color
                'secondary_color' => '#10B981', // Default emerald color
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Update basic profile info
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Handle API key regeneration
        if ($request->boolean('regenerate_api_key')) {
            $user->regenerateApiKey();
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's theme settings only.
     */
    public function updateTheme(Request $request): RedirectResponse
    {
        $request->validate([
            'album_display_settings' => ['required', 'array'],
            'album_display_settings.caption' => ['nullable', 'boolean'],
            'album_display_settings.altText' => ['nullable', 'boolean'],
            'album_display_settings.dateCreated' => ['nullable', 'boolean'],
            'album_display_settings.location' => ['nullable', 'boolean'],
            'album_display_settings.tags' => ['nullable', 'boolean'],
            'album_display_settings.title' => ['nullable', 'boolean'],
            'album_display_settings.author' => ['nullable', 'boolean'],
            'album_display_settings.main_color' => ['nullable', 'string'],
            'album_display_settings.secondary_color' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $settings = $request->input('album_display_settings');

        // Get existing settings to preserve colors when they're not being updated
        $existingSettings = $user->album_display_settings ?? [];

        $user->album_display_settings = [
            'caption' => isset($settings['caption']) ? (bool)$settings['caption'] : true,
            'altText' => isset($settings['altText']) ? (bool)$settings['altText'] : false,
            'dateCreated' => isset($settings['dateCreated']) ? (bool)$settings['dateCreated'] : false,
            'location' => isset($settings['location']) ? (bool)$settings['location'] : false,
            'tags' => isset($settings['tags']) ? (bool)$settings['tags'] : false,
            'title' => isset($settings['title']) ? (bool)$settings['title'] : false,
            'author' => isset($settings['author']) ? (bool)$settings['author'] : false,
            'main_color' => $settings['main_color'] ?? $existingSettings['main_color'] ?? '#4F46E5',
            'secondary_color' => $settings['secondary_color'] ?? $existingSettings['secondary_color'] ?? '#10B981',
        ];

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'theme-updated');
    }

    /**
     * Upload and update the user's logo.
     */
    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => 'required|image|max:2048', // 2MB max
        ]);

        try {
            // Use ImageService to store the logo
            $result = $this->imageService->storeImage(
                $request->file('logo'), 
                "users/{$request->user()->id}/logos"
            );
            
            // Update user with logo URL
            $request->user()->update([
                'logo_url' => $result['url'],
            ]);

            return Redirect::route('profile.edit')->with('status', 'logo-updated');
        } catch (\Exception $e) {
            Log::error('Error uploading logo: ' . $e->getMessage());
            return Redirect::route('profile.edit')->with('error', 'Failed to upload logo.');
        }
    }

    /**
     * Remove the user's logo.
     */
    public function destroyLogo(Request $request): RedirectResponse
    {
        // Update user to remove logo URL
        $request->user()->update([
            'logo_url' => null,
        ]);

        return Redirect::route('profile.edit')->with('status', 'logo-removed');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
