<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('allows admins to view telescope', function (): void {
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($admin)->allows('viewTelescope'))->toBeTrue();
});

it('denies non-admins from viewing telescope', function (): void {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('viewTelescope'))->toBeFalse();
});
