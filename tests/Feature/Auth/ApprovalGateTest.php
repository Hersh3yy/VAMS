<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('blocks an unapproved user from the dashboard and logs them out', function () {
    $user = User::factory()->unapproved()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

it('shows a pending approval message after being blocked', function () {
    $user = User::factory()->unapproved()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertSessionHas('error', 'Your account is pending approval by an administrator.');
});

it('allows an approved user to reach the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertSuccessful();
});

it('allows an unapproved admin to reach the dashboard', function () {
    $admin = User::factory()->unapproved()->admin()->create();

    $response = $this->actingAs($admin)->get('/');

    $response->assertSuccessful();
});

it('blocks an unapproved user from album routes', function () {
    $user = User::factory()->unapproved()->create();

    $response = $this->actingAs($user)->get(route('albums.index'));

    $response->assertRedirect(route('login'));
});

it('blocks an unapproved user from profile mutation routes', function () {
    $user = User::factory()->unapproved()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'New Name',
        'email' => $user->email,
    ]);

    $response->assertRedirect(route('login'));
});
