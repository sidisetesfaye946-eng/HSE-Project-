<?php

use App\Models\Project;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('returns json array of projects for site officer', function () {
    Role::firstOrCreate(['name' => 'HSE Site Officer']);

    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'Project Alpha',
        'project_code' => 'PA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $response = $this->actingAs($siteOfficer)
        ->getJson(route('site-officer.projects.index'));

    $response->assertOk()
        ->assertJsonFragment([
            'id' => $project->id,
            'project_name' => 'Project Alpha',
            'project_code' => 'PA-001',
        ]);
});

test('user store and update saves project_id to db and updates project site_officer_id for HSE Site Officer', function () {
    Role::firstOrCreate(['name' => 'Admin']);
    Role::firstOrCreate(['name' => 'HSE Site Officer']);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $project = Project::create([
        'project_name' => 'Test Project Delta',
        'project_code' => 'TPD-001',
    ]);

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'John Site Officer',
        'email' => 'john.so@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'HSE Site Officer',
        'project_id' => $project->id,
    ]);

    $response->assertRedirect(route('users.index'));

    $user = User::where('email', 'john.so@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->project_id)->toBe($project->id);

    $project->refresh();
    expect($project->site_officer_id)->toBe($user->id)
        ->and($project->assigned_site_officer->id)->toBe($user->id);

    // Test edit/update
    $newProject = Project::create([
        'project_name' => 'Test Project Epsilon',
        'project_code' => 'TPE-002',
    ]);

    $updateResponse = $this->actingAs($admin)->put(route('users.update', $user), [
        'name' => 'John Site Officer Updated',
        'email' => 'john.so@example.com',
        'role' => 'HSE Site Officer',
        'project_id' => $newProject->id,
    ]);

    $updateResponse->assertRedirect(route('users.index'));

    $user->refresh();
    expect($user->project_id)->toBe($newProject->id);

    $newProject->refresh();
    expect($newProject->site_officer_id)->toBe($user->id)
        ->and($newProject->assigned_site_officer->id)->toBe($user->id);
});
