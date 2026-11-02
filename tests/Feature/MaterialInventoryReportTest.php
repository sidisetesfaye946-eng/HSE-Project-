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

it('shows head office material inventory report for hse officer', function () {
    $hseOfficer = User::factory()->create();
    $hseOfficer->assignRole('HSE Officer');

    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'Alpha',
        'project_code' => 'AL-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 5,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'quantity' => 20,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => $hseOfficer->id,
    ]);

    $this->actingAs($hseOfficer)
        ->get(route('material-reports.inventory'))
        ->assertOk()
        ->assertSee('Head Office Material Inventory Report')
        ->assertSee('Material Detail Report')
        ->assertSee('Helmet')
        ->assertSee('Safety helmet')
        ->assertSee('5')
        ->assertSee('20')
        ->assertSee('25');
});

it('shows only project-specific materials when filtering by project', function () {
    $hseOfficer = User::factory()->create();
    $hseOfficer->assignRole('HSE Officer');

    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $projectA = Project::create([
        'project_name' => 'Project A',
        'project_code' => 'PA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $projectB = Project::create([
        'project_name' => 'Project B',
        'project_code' => 'PB-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $materialA = Material::create([
        'material_name' => 'Gloves',
        'material_description' => 'Safety gloves',
        'quantity' => 10,
    ]);

    $materialB = Material::create([
        'material_name' => 'Boots',
        'material_description' => 'Work boots',
        'quantity' => 8,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $materialA->id,
        'project_id' => $projectA->id,
        'quantity' => 12,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => $hseOfficer->id,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $materialB->id,
        'project_id' => $projectB->id,
        'quantity' => 5,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => $hseOfficer->id,
    ]);

    $this->actingAs($hseOfficer)
        ->get(route('material-reports.inventory', ['project_id' => $projectA->id]))
        ->assertOk()
        ->assertSee('Gloves')
        ->assertDontSee('Boots');
});

it('shows site material balance report scoped to site officer projects', function () {
    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $otherOfficer = User::factory()->create();
    $otherOfficer->assignRole('HSE Site Officer');

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
        'material_name' => 'Vest',
        'material_description' => 'High-vis vest',
        'quantity' => 0,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $myProject->id,
        'quantity' => 15,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => User::factory()->create()->id,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $otherProject->id,
        'quantity' => 40,
        'receiver_id' => $otherOfficer->id,
        'assigned_by' => User::factory()->create()->id,
    ]);

    $employee = Employee::create([
        'first_name' => 'Sam',
        'last_name' => 'Lee',
        'gender' => 'Male',
        'job_title' => 'Worker',
    ]);

    MaterialEmployeeAssignment::create([
        'material_id' => $material->id,
        'project_id' => $myProject->id,
        'employee_id' => $employee->id,
        'quantity' => 6,
        'assigned_date' => now()->toDateString(),
        'assigned_by' => $siteOfficer->id,
    ]);

    $this->actingAs($siteOfficer)
        ->get(route('site-officer.material-reports.index'))
        ->assertOk()
        ->assertSee('Site Material Balance Report')
        ->assertSee('Material Detail Report')
        ->assertSee('Vest')
        ->assertSee('MS-001')
        ->assertSee('9')
        ->assertDontSee('Other Site')
        ->assertDontSee('OS-001');
});

it('prevents site officer from accessing head office inventory report', function () {
    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $this->actingAs($siteOfficer)
        ->get(route('material-reports.inventory'))
        ->assertForbidden();
});
