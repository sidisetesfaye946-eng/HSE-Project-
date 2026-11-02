<?php

use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows site officer to access employees report scoped to assigned project', function () {
    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $projectA = Project::create([
        'project_name' => 'Project Alpha',
        'project_code' => 'PA-101',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $projectB = Project::create([
        'project_name' => 'Project Beta',
        'project_code' => 'PB-202',
    ]);

    $empA = Employee::create([
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'job_title' => 'Engineer',
        'project_id' => $projectA->id,
        'project' => $projectA->project_name,
        'date' => '2026-02-15',
    ]);

    $empB = Employee::create([
        'first_name' => 'Bob',
        'last_name' => 'Jones',
        'job_title' => 'Inspector',
        'project_id' => $projectB->id,
        'project' => $projectB->project_name,
        'date' => '2026-02-15',
    ]);

    $response = $this->actingAs($siteOfficer)->get(route('site-officer.reports.employees'));

    $response->assertOk();
    $response->assertSee('Alice');
    $response->assertSee('Engineer');
    $response->assertDontSee('Bob');
});

it('filters employees report by search query and quarter', function () {
    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'Project Gamma',
        'project_code' => 'PG-303',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $empQ1 = Employee::create([
        'first_name' => 'Charlie',
        'last_name' => 'Brown',
        'job_title' => 'Supervisor',
        'project_id' => $project->id,
        'project' => $project->project_name,
        'date' => '2026-01-10',
    ]);

    $empQ2 = Employee::create([
        'first_name' => 'David',
        'last_name' => 'Miller',
        'job_title' => 'Technician',
        'project_id' => $project->id,
        'project' => $project->project_name,
        'date' => '2026-05-20',
    ]);

    // Search for Charlie
    $searchResponse = $this->actingAs($siteOfficer)->get(route('site-officer.reports.employees', ['search' => 'Charlie']));
    $searchResponse->assertOk();
    $searchResponse->assertSee('Charlie');
    $searchResponse->assertDontSee('David');

    // Quarter filter Q1
    $quarterResponse = $this->actingAs($siteOfficer)->get(route('site-officer.reports.employees', ['quarter' => 'Q1', 'year' => '2026']));
    $quarterResponse->assertOk();
    $quarterResponse->assertSee('Charlie');
    $quarterResponse->assertDontSee('David');
});

it('exports employees report in excel and pdf formats', function () {
    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'Project Delta',
        'project_code' => 'PD-404',
        'site_officer_id' => $siteOfficer->id,
    ]);

    Employee::create([
        'first_name' => 'Emma',
        'last_name' => 'Watson',
        'job_title' => 'Operator',
        'project_id' => $project->id,
        'project' => $project->project_name,
        'date' => '2026-03-01',
    ]);

    // Export Excel
    $excelResponse = $this->actingAs($siteOfficer)->get(route('site-officer.reports.employees.export', ['format' => 'xlsx']));
    $excelResponse->assertOk();
    expect($excelResponse->headers->get('Content-Disposition'))->toContain('employees_report.xls');

    // Export PDF
    $pdfResponse = $this->actingAs($siteOfficer)->get(route('site-officer.reports.employees.export', ['format' => 'pdf']));
    $pdfResponse->assertOk();
    expect($pdfResponse->headers->get('Content-Disposition'))->toContain('employees_report.pdf');
});

it('allows site officer to view edit and delete assigned project employees', function () {
    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'Project Epsilon',
        'project_code' => 'PE-505',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $emp = Employee::create([
        'first_name' => 'Frank',
        'last_name' => 'Wright',
        'job_title' => 'Architect',
        'project_id' => $project->id,
        'project' => $project->project_name,
        'date' => '2026-04-01',
    ]);

    // View
    $this->actingAs($siteOfficer)->get(route('employees.show', $emp))->assertOk();

    // Edit
    $this->actingAs($siteOfficer)->get(route('employees.edit', $emp))->assertOk();

    // Delete
    $this->actingAs($siteOfficer)->delete(route('employees.destroy', $emp))->assertRedirect(route('employees.index'));
    expect(Employee::find($emp->id))->toBeNull();
});
