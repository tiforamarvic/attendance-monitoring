<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('creates the admin user with the configured password when none exists', function () {
    config(['admin.email' => 'admin@attendease.com', 'admin.password' => 'secret-password']);

    $this->artisan('app:ensure-admin-user-exists')->assertSuccessful();

    $admin = User::where('email', 'admin@attendease.com')->first();

    expect($admin)->not->toBeNull();
    expect(Hash::check('secret-password', $admin->password))->toBeTrue();
});

test('leaves the existing password untouched when ADMIN_PASSWORD is not set', function () {
    config(['admin.email' => 'admin@attendease.com', 'admin.password' => null]);
    $existing = User::factory()->create(['email' => 'admin@attendease.com']);

    $this->artisan('app:ensure-admin-user-exists')->assertSuccessful();

    expect(User::where('email', 'admin@attendease.com')->count())->toBe(1);
    expect($existing->password)->toBe($existing->fresh()->password);
});

test('syncs the existing admin password from ADMIN_PASSWORD when set', function () {
    config(['admin.email' => 'admin@attendease.com', 'admin.password' => 'new-password']);
    $existing = User::factory()->create(['email' => 'admin@attendease.com']);

    $this->artisan('app:ensure-admin-user-exists')->assertSuccessful();

    expect(User::where('email', 'admin@attendease.com')->count())->toBe(1);
    expect(Hash::check('new-password', $existing->fresh()->password))->toBeTrue();
});

test('generates a random password and warns when none is configured', function () {
    config(['admin.email' => 'admin@attendease.com', 'admin.password' => null]);

    $this->artisan('app:ensure-admin-user-exists')
        ->expectsOutputToContain('No ADMIN_PASSWORD env var was set')
        ->assertSuccessful();

    expect(User::where('email', 'admin@attendease.com')->exists())->toBeTrue();
});
