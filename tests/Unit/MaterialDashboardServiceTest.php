<?php

use App\MaterialDashboardService;
use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialEmployeeAssignment;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->service = app(MaterialDashboardService::class);
});

it('builds head office dashboard stats across all projects', function () {
    $hseOfficer = User::factory()->create();
    $hseOfficer->assignRole('HSE Officer');

    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'Alpha Site',
        'project_code' => 'AL-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 15,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'quantity' => 20,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => $hseOfficer->id,
    ]);

    $employee = Employee::create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'gender' => 'Female',
        'job_title' => 'Technician',
    ]);

    MaterialEmployeeAssignment::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'employee_id' => $employee->id,
        'quantity' => 8,
        'assigned_date' => now()->toDateString(),
        'assigned_by' => $siteOfficer->id,
    ]);

    $stats = $this->service->forHeadOffice();

    expect($stats['total_projects'])->toBe(1)
        ->and($stats['head_office_stock'])->toBe(15)
        ->and($stats['total_assigned_to_projects'])->toBe(20)
        ->and($stats['total_assigned_to_employees'])->toBeNull()
        ->and($stats['site_remaining_balance'])->toBeNull()
        ->and($stats['project_breakdown'][0]['code'])->toBe('AL-001')
        ->and($stats['project_breakdown'][0]['available'])->toBe(12)
        ->and($stats['charts']['project_comparison']['labels'])->toBeEmpty()
        ->and($stats['charts']['assignment_flow']['labels'])->toBeEmpty();
});

it('scopes site officer dashboard stats to their projects only', function () {
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
        'material_name' => 'Gloves',
        'material_description' => 'Gloves',
        'quantity' => 0,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $myProject->id,
        'quantity' => 10,
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

    $stats = $this->service->forSiteOfficer(collect([$myProject]));

    expect($stats['is_head_office'])->toBeFalse()
        ->and($stats['total_projects'])->toBe(1)
        ->and($stats['total_assigned_to_projects'])->toBe(10)
        ->and($stats['head_office_stock'])->toBeNull()
        ->and($stats['project_breakdown'])->toHaveCount(1)
        ->and($stats['project_breakdown'][0]['code'])->toBe('MS-001');
});
