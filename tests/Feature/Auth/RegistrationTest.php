<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can render registration screen', function () {
    $response = $this->get('/register');

    $response->assertSuccessful();
});

it('registers a new user pending approval and does not log them in', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => true,
    ]);

    $this->assertGuest();
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'is_approved' => false,
    ]);
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('success');
});

it('requires terms acceptance to register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('terms');
    $this->assertGuest();
});
