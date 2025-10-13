<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);
});

it('allows user to login with valid credentials', function () {
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'remember' => false,
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticated();
});

it('rejects login with invalid email', function () {
    $response = $this->post('/login', [
        'email' => 'wrong@example.com',
        'password' => 'password123',
        'remember' => false,
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

it('rejects login with invalid password', function () {
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
        'remember' => false,
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

it('allows user to login with remember me checked', function () {
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'remember' => true,
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticated();

    // Check that remember token is set
    $this->assertNotNull($this->user->fresh()->remember_token);
});

it('redirects authenticated user away from login page', function () {
    $this->actingAs($this->user);

    $response = $this->get('/login');

    $response->assertRedirect('/');
});

it('shows login form for guest users', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

it('allows user to logout', function () {
    $this->actingAs($this->user);

    $response = $this->post('/logout');

    $response->assertRedirect('/');
    $this->assertGuest();
});
