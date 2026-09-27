<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\EntryType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController
{
    /**
     * Display a listing of users.
     */
    public function index(): Response
    {
        $users = User::latest()
            ->with('albums')
            ->withCount('albums')
            ->get()
            ->map(function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'is_approved' => $user->is_approved,
                    'created_at' => $user->created_at,
                    'approved_at' => $user->approved_at,
                    'albums_count' => $user->albums_count,
                    'api_key' => $user->api_key,
                ];
            });

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
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'is_admin' => 'boolean',
            'is_approved' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->is_admin ?? false,
            'is_approved' => $request->is_approved ?? false,
            'approved_at' => $request->is_approved ? now() : null,
            'email_verified_at' => now(), // Auto-verify for admin-created users
            'remember_token' => Str::random(10),
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
                'api_key' => $user->api_key,
                'entry_type_permissions' => $user->entry_type_permissions,
            ],
            'entryTypes' => EntryType::active()->get(['id', 'name', 'slug', 'description']),
        ]);
    }

    /**
     * Update the user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'is_admin' => 'boolean',
            'is_approved' => 'boolean',
            'entry_type_permissions' => 'nullable|array',
            'entry_type_permissions.*' => 'string|exists:entry_types,slug',
        ]);

        // Only update password if provided
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'is_admin' => $request->is_admin,
            'entry_type_permissions' => $request->entry_type_permissions,
        ];

        // Track if approval status changed
        $wasApproved = $user->is_approved;
        $isApprovedNow = $request->is_approved;

        $userData['is_approved'] = $isApprovedNow;

        // Set approved_at timestamp if user is being approved now
        if (! $wasApproved && $isApprovedNow) {
            $userData['approved_at'] = now();
        }

        // If password is being changed
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} updated successfully.");
    }

    /**
     * Remove the user.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevent deleting self
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
        $user->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} has been approved.");
    }

    /**
     * Impersonate a user.
     */
    public function impersonate(User $user): RedirectResponse
    {
        // Store the admin's ID in the session
        session()->put('admin_id', Auth::id());

        // Log in as the target user
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', "You are now impersonating {$user->name}.");
    }

    /**
     * Stop impersonating a user.
     */
    public function stopImpersonating(): RedirectResponse
    {
        // Get the admin ID from session
        $adminId = session()->pull('admin_id');

        if ($adminId) {
            $admin = User::findOrFail($adminId);

            // Log back in as admin
            Auth::login($admin);

            return redirect()->route('admin.users.index')
                ->with('success', 'Returned to your admin account.');
        }

        return redirect()->route('dashboard');
    }

    /**
     * Regenerate API key for a user.
     */
    public function regenerateApiKey(User $user): RedirectResponse
    {
        $newApiKey = $user->regenerateApiKey();

        return redirect()->back()
            ->with('success', "API key regenerated successfully for {$user->name}.");
    }
}
