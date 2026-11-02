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

function createSiteOfficerUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('HSE Site Officer');

    return $user;
}

it('shows site officer dashboard with only their project balances', function () {
    $siteOfficer = createSiteOfficerUser();
    $otherOfficer = createSiteOfficerUser();

    $myProject = Project::create([
        'project_name' => 'My Site',
        'project_code' => 'MS-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $otherProject = Project::create([
        'project_name' => 'Other Site',
        'project_code' => 'OS-001',
        'site_officer_id' => $otherOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 0,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $myProject->id,
        'quantity' => 20,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => User::factory()->create()->id,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $otherProject->id,
        'quantity' => 50,
        'receiver_id' => $otherOfficer->id,
        'assigned_by' => User::factory()->create()->id,
    ]);

    $this->actingAs($siteOfficer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('My Site')
        ->assertSee('Total Projects')
        ->assertSee('Material Balance by Project')
        ->assertSee('My Site')
        ->assertSee('MS-001')
        ->assertSee('Helmet')
        ->assertDontSee('Other Site')
        ->assertDontSee('OS-001')
        ->assertDontSee('Head Office Stock');
});

it('prevents site officer from viewing another officers project', function () {
    $siteOfficer = createSiteOfficerUser();
    $otherOfficer = createSiteOfficerUser();

    $otherProject = Project::create([
        'project_name' => 'Other Site',
        'project_code' => 'OS-001',
        'site_officer_id' => $otherOfficer->id,
    ]);

    $this->actingAs($siteOfficer)
        ->get(route('site-officer.projects.show', $otherProject))
        ->assertForbidden();
});

it('shows project detail with site balance breakdown', function () {
    $siteOfficer = createSiteOfficerUser();

    $project = Project::create([
        'project_name' => 'Alpha',
        'project_code' => 'AL-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Gloves',
        'material_description' => 'Gloves',
        'quantity' => 0,
    ]);

    $hseOfficer = User::factory()->create();
    $hseOfficer->assignRole('HSE Officer');

    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'quantity' => 10,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => $hseOfficer->id,
    ]);

    $employee = Employee::create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'gender' => 'Male',
        'job_title' => 'Technician',
    ]);

    MaterialEmployeeAssignment::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'employee_id' => $employee->id,
        'quantity' => 3,
        'assigned_date' => now()->toDateString(),
        'assigned_by' => $siteOfficer->id,
    ]);

    $this->actingAs($siteOfficer)
        ->get(route('site-officer.projects.show', $project))
        ->assertOk()
        ->assertSee('Gloves')
        ->assertSee('10')
        ->assertSee('3')
        ->assertSee('7')
        ->assertSee('John Doe');
});

it('displays assigned site name in site officer dashboard header', function () {
    $siteOfficer = createSiteOfficerUser();

    $project = Project::create([
        'project_name' => 'Metro Expansion Site',
        'project_code' => 'ME-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $this->actingAs($siteOfficer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Metro Expansion Site Dashboard');
});

it('exposes assignedSiteName and assignedProject accessors on User model', function () {
    $siteOfficer = createSiteOfficerUser();

    $project = Project::create([
        'project_name' => 'Solar Power Station',
        'project_code' => 'SP-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    expect($siteOfficer->assignedSiteName)->toBe('Solar Power Station');
    expect($siteOfficer->assignedProject->id)->toBe($project->id);
});
