<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('registration allows only @eeccompany.com email domain', function ($email, $isValid) {
    $response = $this->post('/register', [
        'name' => 'Registration Candidate',
        'email' => $email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    if ($isValid) {
        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();
        expect(User::where('email', strtolower($email))->exists())->toBeTrue();
    } else {
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }
})->with([
    ['john@eeccompany.com', true],
    ['SARA.DOE@EECCOMPANY.COM', true],
    ['john@gmail.com', false],
    ['john@yahoo.com', false],
    ['john@eeccompany.org', false],
    ['john@not-eeccompany.com', false],
    ['john@example.com', false],
]);

test('admin user creation allows only @eeccompany.com email domain', function ($email, $isValid) {
    $admin = User::factory()->create();
    $admin->assignRole('HSE Officer');

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'New Staff',
        'email' => $email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'HSE Site Officer',
    ]);

    if ($isValid) {
        $response->assertRedirect(route('users.index'));
        expect(User::where('email', strtolower($email))->exists())->toBeTrue();
    } else {
        $response->assertSessionHasErrors(['email']);
    }
})->with([
    ['officer@eeccompany.com', true],
    ['officer@gmail.com', false],
    ['officer@customdomain.net', false],
]);

test('admin user update rejects changing email to non @eeccompany.com domain', function () {
    $admin = User::factory()->create();
    $admin->assignRole('HSE Officer');

    $user = User::factory()->create(['email' => 'existing@eeccompany.com']);
    $user->assignRole('HSE Site Officer');

    $response = $this->actingAs($admin)->put(route('users.update', $user), [
        'name' => 'Updated Staff',
        'email' => 'invalid@gmail.com',
        'role' => 'HSE Site Officer',
    ]);

    $response->assertSessionHasErrors(['email']);
    expect($user->fresh()->email)->toBe('existing@eeccompany.com');
});
