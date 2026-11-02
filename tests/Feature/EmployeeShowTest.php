<?php

use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialEmployeeAssignment;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows admin to view employee show page with material history', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $employee = Employee::create([
        'first_name' => 'Alice',
        'last_name' => 'Wonder',
        'gender' => 'Female',
        'job_title' => 'Engineer',
    ]);

    $project = Project::create([
        'project_name' => 'Alpha',
        'project_code' => 'AL-001',
        'site_officer_id' => User::factory()->create()->id,
    ]);

    $material = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 0,
    ]);

    MaterialEmployeeAssignment::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'employee_id' => $employee->id,
        'quantity' => 3,
        'assigned_date' => '2026-06-15',
        'assigned_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('employees.show', $employee))
        ->assertOk()
        ->assertSee('Alice Wonder')
        ->assertSee('Helmet')
        ->assertSee('Jun 15, 2026')
        ->assertSee('Alpha');
});

it('allows hse head office to view employee show page', function () {
    $headOffice = User::factory()->create();
    $headOffice->assignRole('HSE Head Office');

    $employee = Employee::create([
        'first_name' => 'Bob',
        'last_name' => 'Stone',
        'gender' => 'Male',
        'job_title' => 'Technician',
    ]);

    $this->actingAs($headOffice)
        ->get(route('employees.show', $employee))
        ->assertOk()
        ->assertSee('Bob Stone');
});

it('allows hse head office to access employee index', function () {
    $headOffice = User::factory()->create();
    $headOffice->assignRole('HSE Head Office');

    Employee::create([
        'first_name' => 'Carol',
        'last_name' => 'Reed',
        'gender' => 'Female',
        'job_title' => 'Supervisor',
    ]);

    $this->actingAs($headOffice)
        ->get(route('employees.index'))
        ->assertOk()
        ->assertSee('Carol')
        ->assertSee('View');
});

it('allows hse head office to create and store employees with history fields', function () {
    $headOffice = User::factory()->create();
    $headOffice->assignRole('HSE Head Office');

    $project = Project::create([
        'project_name' => 'Delta Construction',
        'project_code' => 'DELTA-01',
    ]);

    $material = Material::create([
        'material_name' => 'Safety Boots',
        'material_description' => 'Heavy duty boots',
        'quantity' => 10,
    ]);

    $this->actingAs($headOffice)
        ->get(route('employees.create'))
        ->assertOk()
        ->assertSee('Employee Name')
        ->assertSee('Project')
        ->assertSee('Material Type')
        ->assertSee('Date');

    $this->actingAs($headOffice)
        ->post(route('employees.store'), [
            'employee_name' => 'John Construction',
            'project_id' => $project->id,
            'material_id' => $material->id,
            'date' => '2026-07-23',
        ])
        ->assertRedirect(route('employees.index'));

    $employee = Employee::where('name', 'John Construction')->first();
    expect($employee)->not->toBeNull();
    expect($employee->project_id)->toBe($project->id);
    expect($employee->material_id)->toBe($material->id);
    expect($employee->date)->toBe('2026-07-23');

    $this->actingAs($headOffice)
        ->get(route('employees.index'))
        ->assertOk()
        ->assertSee('John Construction')
        ->assertSee('Delta Construction')
        ->assertSee('Safety Boots');
});

it('denies unauthorized roles from employee routes', function () {
    $employee = Employee::create([
        'first_name' => 'Eve',
        'last_name' => 'Fox',
        'gender' => 'Female',
        'job_title' => 'Driver',
    ]);

    $user = User::factory()->create();
    $user->assignRole('Employee');

    $this->actingAs($user)->get(route('employees.index'))->assertForbidden();
    $this->actingAs($user)->get(route('employees.show', $employee))->assertForbidden();
    $this->actingAs($user)->get(route('employees.create'))->assertForbidden();
});

it('shows empty state when employee has no material history', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $employee = Employee::create([
        'first_name' => 'Frank',
        'last_name' => 'Zero',
        'gender' => 'Male',
        'job_title' => 'Clerk',
    ]);

    $this->actingAs($admin)
        ->get(route('employees.show', $employee))
        ->assertOk()
        ->assertSee('No materials received yet.');
});

it('allows both admin and hse head office to see employees menu item and access it', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $headOffice = User::factory()->create();
    $headOffice->assignRole('HSE Head Office');

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('employees.index'), false);

    $this->actingAs($admin)
        ->get(route('employees.index'))
        ->assertOk();

    $this->actingAs($headOffice)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('employees.index'), false);

    $this->actingAs($headOffice)
        ->get(route('employees.index'))
        ->assertOk();
});

it('hides employees menu item from hse site officer', function () {
    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $this->actingAs($siteOfficer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('employees.index'), false);
});
