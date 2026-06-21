<?php

declare(strict_types=1);

use App\Models\User;

it('logs out unapproved users and redirects them to login', function () {
    $user = User::factory()->create();
    $user->forceFill(['is_approved' => false, 'approved_at' => null])->save();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('login'))
        ->assertSessionHas('error');

    $this->assertGuest();
});

it('blocks unapproved users from crud routes', function () {
    $user = User::factory()->create();
    $user->forceFill(['is_approved' => false, 'approved_at' => null])->save();

    $this->actingAs($user)
        ->post(route('albums.store'), ['title' => 'Nope'])
        ->assertRedirect(route('login'));

    $this->assertDatabaseMissing('albums', ['title' => 'Nope']);
});

it('allows approved users through', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful();
});

it('allows unapproved admins through', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true, 'is_approved' => false])->save();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSuccessful();
});
