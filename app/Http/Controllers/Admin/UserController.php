<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = User::latest()
            ->with('albums')
            ->withCount('albums')
            ->get()
            ->map(function ($user) {
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
    public function create()
    {
        return Inertia::render('Admin/Users/Create');
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
            'is_approved' => $validated['is_approved'] ?? false,
            'approved_at' => ($validated['is_approved'] ?? false) ? now() : null,
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
    public function edit(User $user)
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
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => $validated['is_admin'],
            'entry_type_permissions' => $validated['entry_type_permissions'] ?? null,
        ];

        $wasApproved = $user->is_approved;
        $isApprovedNow = $validated['is_approved'] ?? false;

        $userData['is_approved'] = $isApprovedNow;

        // Set approved_at timestamp if user is being approved now
        if (! $wasApproved && $isApprovedNow) {
            $userData['approved_at'] = now();
        }

        if (! empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} updated successfully.");
    }

    /**
     * Remove the user.
     */
    public function destroy(User $user)
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
    public function approve(User $user)
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
    public function impersonate(User $user)
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
    public function stopImpersonating()
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
    public function regenerateApiKey(User $user)
    {
        $newApiKey = $user->regenerateApiKey();

        return redirect()->back()
            ->with('success', "API key regenerated successfully for {$user->name}.");
    }
}
