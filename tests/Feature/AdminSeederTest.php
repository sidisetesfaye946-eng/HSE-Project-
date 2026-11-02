<?php

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('seeds an admin user with the admin role', function () {
    $this->seed(AdminSeeder::class);

    $admin = User::where('email', 'admin@example.com')->first();

    expect($admin)->not->toBeNull();
    expect($admin->name)->toBe('System Admin');
    expect($admin->hasRole('Admin'))->toBeTrue();
    expect(Role::where('name', 'Admin')->exists())->toBeTrue();
});

it('allows admin to manage users and roles', function () {
    $this->seed(AdminSeeder::class);

    $admin = User::where('email', 'admin@example.com')->first();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('roles.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'HSE User',
            'email' => 'hse@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'HSE Officer',
        ])
        ->assertRedirect(route('users.index'));

    expect(User::where('email', 'hse@example.com')->first()->hasRole('HSE Officer'))->toBeTrue();

    $this->actingAs($admin)
        ->post(route('roles.store'), [
            'name' => 'Supervisor',
        ])
        ->assertRedirect(route('roles.index'));

    expect(Role::where('name', 'Supervisor')->exists())->toBeTrue();
});

it('denies non admin users from managing users and roles', function () {
    $user = User::factory()->create();
    $user->assignRole('Employee');

    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    $this->actingAs($user)->get(route('roles.index'))->assertForbidden();
});
