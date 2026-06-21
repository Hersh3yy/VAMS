<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class UserController
{
    /**
     * Display a listing of users.
     */
    public function index(): Response
    {
        $users = User::query()
            ->latest()
            ->withCount('albums')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'is_approved' => $user->is_approved,
                'created_at' => $user->created_at,
                'approved_at' => $user->approved_at,
                'albums_count' => $user->albums_count,
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create');
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $isApproved = (bool) ($validated['is_approved'] ?? false);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'album_display_settings' => [
                'caption' => true,
                'altText' => true,
                'dateCreated' => true,
                'location' => true,
                'tags' => true,
                'title' => true,
                'author' => true,
                'main_color' => '#4F46E5',
                'secondary_color' => '#10B981',
            ],
        ]);

        $user->forceFill([
            'is_admin' => (bool) ($validated['is_admin'] ?? false),
            'is_approved' => $isApproved,
            'approved_at' => $isApproved ? now() : null,
            'email_verified_at' => now(),
        ])->save();

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} created successfully.");
    }

    /**
     * Show the form for editing the user.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'is_approved' => $user->is_approved,
                'created_at' => $user->created_at,
                'approved_at' => $user->approved_at,
                'has_api_key' => filled($user->api_key),
                'entry_type_permissions' => $user->entry_type_permissions,
            ],
            'entryTypes' => EntryType::active()->get(['id', 'name', 'slug', 'description']),
        ]);
    }

    /**
     * Update the user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = Hash::make($validated['password']);
        }

        $isApprovedNow = (bool) ($validated['is_approved'] ?? false);

        $user->forceFill([
            'is_admin' => (bool) ($validated['is_admin'] ?? false),
            'is_approved' => $isApprovedNow,
            'entry_type_permissions' => $validated['entry_type_permissions'] ?? null,
        ]);

        if (! $user->getOriginal('is_approved') && $isApprovedNow) {
            $user->forceFill(['approved_at' => now()]);
        }

        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} updated successfully.");
    }

    /**
     * Remove the user.
     */
    public function destroy(User $user): RedirectResponse
    {
        if (Auth::id() === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Approve a user account.
     */
    public function approve(User $user): RedirectResponse
    {
        $user->forceFill([
            'is_approved' => true,
            'approved_at' => now(),
        ])->save();

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} has been approved.");
    }

    /**
     * Impersonate a user.
     */
    public function impersonate(User $user): RedirectResponse
    {
        $admin = Auth::user();

        session()->put('admin_id', $admin->id);

        Log::info('Admin started impersonating user', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'user_id' => $user->id,
            'user_email' => $user->email,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', "You are now impersonating {$user->name}.");
    }

    /**
     * Stop impersonating a user.
     */
    public function stopImpersonating(): RedirectResponse
    {
        $adminId = session()->pull('admin_id');

        if ($adminId) {
            $admin = User::query()->findOrFail($adminId);

            Log::info('Admin stopped impersonating user', [
                'admin_id' => $admin->id,
                'impersonated_user_id' => Auth::id(),
            ]);

            Auth::login($admin);

            return redirect()->route('admin.users.index')
                ->with('success', 'Returned to your admin account.');
        }

        return redirect()->route('dashboard');
    }

    /**
     * Regenerate API key for a user. The plain-text key is flashed once and
     * cannot be retrieved afterwards (only its hash is stored).
     */
    public function regenerateApiKey(User $user): RedirectResponse
    {
        $plainTextKey = $user->regenerateApiKey();

        return redirect()->back()
            ->with('success', "API key regenerated successfully for {$user->name}. Copy it now - it will not be shown again.")
            ->with('new_api_key', $plainTextKey);
    }
}
