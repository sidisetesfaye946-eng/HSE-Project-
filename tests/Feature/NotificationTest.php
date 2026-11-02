<?php

use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('notifies site officer when hse officer assigns materials to a project', function () {
    $hseOfficer = User::factory()->create(['name' => 'HSE Lead']);
    $hseOfficer->assignRole('HSE Officer');

    $siteOfficer = User::factory()->create(['name' => 'Site Lead']);
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'Alpha',
        'project_code' => 'AL-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 10,
    ]);

    $this->actingAs($hseOfficer)
        ->post(route('material-assignments.store'), [
            'project_id' => $project->id,
            'assignments' => [
                ['material_id' => $material->id, 'quantity' => 5],
            ],
        ])
        ->assertRedirect();

    expect($siteOfficer->fresh()->unreadNotifications)->toHaveCount(1)
        ->and($siteOfficer->fresh()->unreadNotifications->first()->data['category'])->toBe('material_assigned_to_project')
        ->and($siteOfficer->fresh()->unreadNotifications->first()->data['title'])->toBe('Materials received at site');
});

it('notifies hse officers when site officer assigns materials to employees', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $hseOfficer = User::factory()->create();
    $hseOfficer->assignRole('HSE Officer');

    $siteOfficer = User::factory()->create(['name' => 'Site Lead']);
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'Beta',
        'project_code' => 'BE-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Vest',
        'material_description' => 'Vest',
        'quantity' => 0,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'quantity' => 10,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => $hseOfficer->id,
    ]);

    $employee = Employee::create([
        'first_name' => 'John',
        'last_name' => 'Worker',
        'gender' => 'Male',
        'job_title' => 'Technician',
    ]);

    $this->actingAs($siteOfficer)
        ->post(route('site-officer.employee-assignments.store'), [
            'project_id' => $project->id,
            'assignments' => [
                [
                    'material_id' => $material->id,
                    'employee_id' => $employee->id,
                    'quantity' => 3,
                    'assigned_date' => now()->toDateString(),
                ],
            ],
        ])
        ->assertRedirect();

    expect($hseOfficer->fresh()->unreadNotifications)->toHaveCount(1)
        ->and($hseOfficer->fresh()->unreadNotifications->first()->data['category'])->toBe('material_assigned_to_employee')
        ->and($admin->fresh()->unreadNotifications)->toHaveCount(0);
});

it('does not notify admins when hse officer assigns materials to a project', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $hseOfficer = User::factory()->create(['name' => 'HSE Lead']);
    $hseOfficer->assignRole('HSE Officer');

    $siteOfficer = User::factory()->create(['name' => 'Site Lead']);
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'Gamma',
        'project_code' => 'GA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Gloves',
        'material_description' => 'Safety gloves',
        'quantity' => 10,
    ]);

    $this->actingAs($hseOfficer)
        ->post(route('material-assignments.store'), [
            'project_id' => $project->id,
            'assignments' => [
                ['material_id' => $material->id, 'quantity' => 5],
            ],
        ])
        ->assertRedirect();

    expect($siteOfficer->fresh()->unreadNotifications)->toHaveCount(1)
        ->and($admin->fresh()->unreadNotifications)->toHaveCount(0);
});

it('returns unread notifications for the notification bell api', function () {
    $user = User::factory()->create();
    $user->assignRole('HSE Officer');

    $user->notify(new WorkflowNotification(
        category: 'material_created',
        title: 'Test notification',
        message: 'A material was created.',
    ));

    $this->actingAs($user)
        ->getJson(route('notifications.unread'))
        ->assertOk()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonPath('notifications.0.title', 'Test notification');
});

it('shows notification bell on dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('HSE Officer');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('notificationBell()', false);
});
