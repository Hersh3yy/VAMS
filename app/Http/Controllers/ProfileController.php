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
        $validated = $request->validated();
        
        $request->user()->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'album_display_settings' => $validated['album_display_settings'] ?? [
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

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
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
