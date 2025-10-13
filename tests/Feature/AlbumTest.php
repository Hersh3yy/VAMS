<?php

declare(strict_types=1);

use App\Models\User;

it('allows authenticated user to create an album', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $albumData = [
        'title' => 'My Test Album',
        'description' => 'This is a test album description',
    ];

    $response = $this->post('/albums', $albumData);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('albums', [
        'title' => 'My Test Album',
        'description' => 'This is a test album description',
        'user_id' => $user->id,
    ]);
});

it('requires title when creating an album', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $albumData = [
        'description' => 'This is a test album description',
    ];

    $response = $this->post('/albums', $albumData);

    $response->assertSessionHasErrors(['title']);
    $this->assertDatabaseMissing('albums', [
        'description' => 'This is a test album description',
    ]);
});

it('prevents guest users from creating albums', function () {
    $this->assertGuest();

    $albumData = [
        'title' => 'Unauthorized Album',
        'description' => 'This should not be created',
    ];

    $response = $this->post('/albums', $albumData);

    $response->assertRedirect('/login');
    $this->assertDatabaseMissing('albums', [
        'title' => 'Unauthorized Album',
    ]);
});
