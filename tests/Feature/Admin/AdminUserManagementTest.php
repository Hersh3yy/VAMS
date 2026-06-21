<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->forceFill(['is_admin' => true])->save();
});

it('forbids non-admin users from the admin user pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertRedirect(route('dashboard'));
});

it('shows the user list without exposing api keys', function () {
    User::factory()->count(2)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users', 3)
            ->missing('users.0.api_key')
        );
});

it('lets an admin create a user with privileged flags', function () {

    $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'secret-password',
        'is_admin' => false,
        'is_approved' => true,
    ]);

    $response->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success');

    $user = User::query()->where('email', 'new@example.com')->firstOrFail();

    expect($user->is_approved)->toBeTrue()
        ->and($user->is_admin)->toBeFalse()
        ->and($user->approved_at)->not->toBeNull()
        ->and($user->api_key)->not->toBeNull();
});

it('validates the create user form', function () {

    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
    ])->assertSessionHasErrors(['name', 'email', 'password']);
});

it('does not expose api keys on the edit page', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.users.edit', $user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Edit')
            ->where('user.has_api_key', true)
            ->missing('user.api_key')
        );
});

it('lets an admin update user flags and permissions', function () {
    $user = User::factory()->create();
    $user->forceFill(['is_approved' => false, 'approved_at' => null])->save();

    $entryType = \App\Models\EntryType::query()->create([
        'name' => 'Project',
        'slug' => 'project',
        'field_config' => [
            ['name' => 'summary', 'label' => 'Summary', 'type' => 'textarea', 'required' => true],
        ],
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->patch(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'is_admin' => false,
        'is_approved' => true,
        'entry_type_permissions' => [$entryType->slug],
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $user->refresh();

    expect($user->is_approved)->toBeTrue()
        ->and($user->approved_at)->not->toBeNull()
        ->and($user->entry_type_permissions)->toBe(['project']);
});

it('prevents privileged fields from being mass assigned', function () {
    $filled = new User([
        'name' => 'Sneaky',
        'email' => 'sneaky@example.com',
        'is_admin' => true,
        'is_approved' => true,
        'api_key' => 'attacker-controlled',
    ]);

    expect($filled->is_admin)->toBeNull()
        ->and($filled->is_approved)->toBeNull()
        ->and($filled->api_key)->toBeNull();
});

it('prevents an admin from deleting their own account', function () {

    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $this->admin))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
});

it('regenerates an api key, flashing the plain-text key once and storing only its hash', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($this->admin)
        ->from(route('admin.users.edit', $user))
        ->post(route('admin.users.regenerate-api-key', $user));

    $response->assertRedirect(route('admin.users.edit', $user))
        ->assertSessionHas('new_api_key');

    $plainTextKey = session('new_api_key');
    $user->refresh();

    expect($user->api_key)->toBe(User::hashApiKey($plainTextKey))
        ->and($user->api_key)->not->toBe($plainTextKey);
});
