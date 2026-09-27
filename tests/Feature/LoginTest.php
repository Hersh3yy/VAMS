<?php

declare(strict_types=1);

use App\Models\User;

it('allows user to login with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'remember' => false,
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticated();
});

it('rejects login with invalid credentials', function (): void {
    $response = $this->post('/login', [
        'email' => 'wrong@example.com',
        'password' => 'wrongpassword',
        'remember' => false,
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});
