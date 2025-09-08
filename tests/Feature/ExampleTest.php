<?php

declare(strict_types=1);

use App\Models\User;

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

it('allows authenticated users to access dashboard', function () {
    $user = User::factory()->create([
        'is_approved' => true,
        'email_verified_at' => now()
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);
});
