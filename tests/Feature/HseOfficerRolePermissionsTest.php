<?php

use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialEmployeeAssignment;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows hse officers to view and create projects', function () {
    $hseOfficer = User::factory()->create();
    $hseOfficer->assignRole('HSE Officer');

    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $this->actingAs($hseOfficer)
        ->get(route('projects.index'))
        ->assertOk();

    $this->actingAs($hseOfficer)
        ->post(route('projects.store'), [
            'project_name' => 'Test Project',
            'project_code' => 'TP-999',
            'site_officer_id' => $siteOfficer->id,
        ])
        ->assertRedirect(route('projects.index'));

    expect(Project::where('project_code', 'TP-999')->exists())->toBeTrue();
});

it('allows hse officers to view and create users', function () {
    $hseOfficer = User::factory()->create();
    $hseOfficer->assignRole('HSE Officer');

    $this->actingAs($hseOfficer)
        ->get(route('users.index'))
        ->assertOk();

    $this->actingAs($hseOfficer)
        ->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'newuser@eeccompany.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'HSE Site Officer',
        ])
        ->assertRedirect(route('users.index'));

    expect(User::where('email', 'newuser@eeccompany.com')->exists())->toBeTrue();
});

it('allows hse officers to perform employee assignments', function () {
    $hseOfficer = User::factory()->create();
    $hseOfficer->assignRole('HSE Officer');

    $project = Project::create([
        'project_name' => 'Site D',
        'project_code' => 'SD-101',
        'site_officer_id' => User::factory()->create()->id,
    ]);

    $material = Material::create([
        'material_name' => 'Goggles',
        'material_description' => 'Safety goggles',
        'quantity' => 0,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'quantity' => 50,
        'receiver_id' => $project->site_officer_id,
        'assigned_by' => $hseOfficer->id,
    ]);

    $employee = Employee::create([
        'first_name' => 'Mark',
        'last_name' => 'Spencer',
        'gender' => 'Male',
        'job_title' => 'Supervisor',
    ]);

    $this->actingAs($hseOfficer)
        ->get(route('site-officer.employee-assignments.create'))
        ->assertOk();

    $this->actingAs($hseOfficer)
        ->post(route('site-officer.employee-assignments.store'), [
            'project_id' => $project->id,
            'assignments' => [
                [
                    'material_id' => $material->id,
                    'quantity' => 10,
                    'employee_id' => $employee->id,
                    'assigned_date' => '2026-06-16',
                ],
            ],
        ])
        ->assertRedirect(route('site-officer.projects.show', $project));

    $assignment = MaterialEmployeeAssignment::first();
    expect($assignment)->not->toBeNull();
    expect($assignment->quantity)->toBe(10);
    expect($assignment->employee_id)->toBe($employee->id);
});
