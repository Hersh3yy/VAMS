<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can render confirm password screen', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/confirm-password');

    $response->assertSuccessful();
});

it('can confirm password', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

it('does not confirm password with invalid password', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();
});
