<?php

use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialEmployeeAssignment;
use App\Models\MaterialProjectAssignment;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestEmployee;
use App\Models\MaterialRequestItem;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function requestTestHseOfficer(): User
{
    $user = User::factory()->create();
    $user->assignRole('HSE Officer');

    return $user;
}

function requestTestSiteOfficer(): User
{
    $user = User::factory()->create();
    $user->assignRole('HSE Site Officer');

    return $user;
}

function createMaterialRequestWithEmployees(
    Project $project,
    Material $material,
    User $siteOfficer,
    array $employeeRows,
    ?int $quantity = null,
): MaterialRequest {
    $materialRequest = MaterialRequest::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'requested_by' => $siteOfficer->id,
        'quantity' => $quantity ?? collect($employeeRows)->sum('quantity'),
        'status' => 'pending',
    ]);

    foreach ($employeeRows as $row) {
        MaterialRequestEmployee::create([
            'material_request_id' => $materialRequest->id,
            'employee_id' => $row['employee_id'],
            'quantity' => $row['quantity'],
        ]);
    }

    return $materialRequest;
}

it('allows hse officer to view material requests index', function () {
    $hseOfficer = requestTestHseOfficer();
    $siteOfficer = requestTestSiteOfficer();

    $project = Project::create([
        'project_name' => 'Site Alpha',
        'project_code' => 'SA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 100,
    ]);

    $employee = Employee::create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'gender' => 'Female',
        'job_title' => 'Worker',
    ]);

    createMaterialRequestWithEmployees($project, $material, $siteOfficer, [
        ['employee_id' => $employee->id, 'quantity' => 10],
    ]);

    $this->actingAs($hseOfficer)
        ->get(route('hse-officer.material-requests.index'))
        ->assertOk()
        ->assertSee('Helmet')
        ->assertSee('View');
});

it('allows hse officer to approve all requested employees and assign materials', function () {
    $hseOfficer = requestTestHseOfficer();
    $siteOfficer = requestTestSiteOfficer();

    $project = Project::create([
        'project_name' => 'Site Alpha',
        'project_code' => 'SA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 100,
    ]);

    $employeeA = Employee::create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'gender' => 'Female',
        'job_title' => 'Worker',
    ]);

    $employeeB = Employee::create([
        'first_name' => 'Tom',
        'last_name' => 'Lee',
        'gender' => 'Male',
        'job_title' => 'Worker',
    ]);

    $materialRequest = createMaterialRequestWithEmployees($project, $material, $siteOfficer, [
        ['employee_id' => $employeeA->id, 'quantity' => 4],
        ['employee_id' => $employeeB->id, 'quantity' => 6],
    ], 10);

    $response = $this->actingAs($hseOfficer)
        ->post(route('hse-officer.material-requests.approve', $materialRequest), [
            'permitted_quantities' => [
                $employeeA->id => 4,
                $employeeB->id => 6,
            ],
        ]);

    $response->assertRedirect(route('hse-officer.material-requests.index'));
    expect($materialRequest->fresh()->isApproved())->toBeTrue();
    expect($materialRequest->fresh()->isDistributed())->toBeTrue();
    expect($materialRequest->fresh()->approved_by)->toBe($hseOfficer->id);
    expect($material->fresh()->quantity)->toBe(90);

    $assignment = MaterialProjectAssignment::first();
    expect($assignment)->not->toBeNull();
    expect($assignment->quantity)->toBe(10);

    expect(MaterialEmployeeAssignment::count())->toBe(2);
    expect(MaterialEmployeeAssignment::where('employee_id', $employeeA->id)->first()->quantity)->toBe(4);
    expect(MaterialEmployeeAssignment::where('employee_id', $employeeB->id)->first()->quantity)->toBe(6);
});

it('allows hse officer to partially approve selected employees', function () {
    $hseOfficer = requestTestHseOfficer();
    $siteOfficer = requestTestSiteOfficer();

    $project = Project::create([
        'project_name' => 'Site Alpha',
        'project_code' => 'SA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 100,
    ]);

    $employeeA = Employee::create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'gender' => 'Female',
        'job_title' => 'Worker',
    ]);

    $employeeB = Employee::create([
        'first_name' => 'Tom',
        'last_name' => 'Lee',
        'gender' => 'Male',
        'job_title' => 'Worker',
    ]);

    $materialRequest = createMaterialRequestWithEmployees($project, $material, $siteOfficer, [
        ['employee_id' => $employeeA->id, 'quantity' => 4],
        ['employee_id' => $employeeB->id, 'quantity' => 6],
    ], 10);

    // First: set 4 for employeeA and 0 for employeeB
    $this->actingAs($hseOfficer)
        ->post(route('hse-officer.material-requests.approve', $materialRequest), [
            'permitted_quantities' => [
                $employeeA->id => 4,
                $employeeB->id => 0,
            ],
        ])
        ->assertRedirect(route('hse-officer.material-requests.index'));

    expect($materialRequest->fresh()->isApproved())->toBeTrue();
    expect($material->fresh()->quantity)->toBe(96);
    expect(MaterialEmployeeAssignment::count())->toBe(1);
    expect(MaterialEmployeeAssignment::first()->employee_id)->toBe($employeeA->id);
    expect(MaterialEmployeeAssignment::first()->quantity)->toBe(4);
});

it('allows hse officer to approve a bulk request with no employees attached', function () {
    $hseOfficer = requestTestHseOfficer();
    $siteOfficer = requestTestSiteOfficer();

    $project = Project::create([
        'project_name' => 'Site Beta',
        'project_code' => 'SB-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Safety Shoe',
        'material_description' => 'Steel toe safety shoe',
        'quantity' => 50,
    ]);

    // Create a bulk request with NO employee rows
    $materialRequest = MaterialRequest::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'requested_by' => $siteOfficer->id,
        'quantity' => 20,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($hseOfficer)
        ->post(route('hse-officer.material-requests.approve', $materialRequest), [
            'bulk_permitted_quantity' => 15,
        ]);

    $response->assertRedirect(route('hse-officer.material-requests.index'));

    expect($materialRequest->fresh()->isApproved())->toBeTrue();
    expect($materialRequest->fresh()->isDistributed())->toBeTrue();
    expect($materialRequest->fresh()->permitted_quantity)->toBe(15);
    expect($material->fresh()->quantity)->toBe(35); // 50 - 15
    expect(MaterialProjectAssignment::count())->toBe(1);
    expect(MaterialProjectAssignment::first()->quantity)->toBe(15);
    expect(MaterialEmployeeAssignment::count())->toBe(0);
});

it('allows hse officer to reject a pending material request with reason', function () {
    $hseOfficer = requestTestHseOfficer();
    $siteOfficer = requestTestSiteOfficer();

    $project = Project::create([
        'project_name' => 'Site Alpha',
        'project_code' => 'SA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 100,
    ]);

    $employee = Employee::create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'gender' => 'Female',
        'job_title' => 'Worker',
    ]);

    $materialRequest = createMaterialRequestWithEmployees($project, $material, $siteOfficer, [
        ['employee_id' => $employee->id, 'quantity' => 10],
    ]);

    $response = $this->actingAs($hseOfficer)
        ->post(route('hse-officer.material-requests.reject', $materialRequest), [
            'rejection_reason' => 'Duplicate request',
        ]);

    $response->assertRedirect(route('hse-officer.material-requests.index'));
    expect($materialRequest->fresh()->isRejected())->toBeTrue();
    expect($materialRequest->fresh()->rejection_reason)->toBe('Duplicate request');
    expect($material->fresh()->quantity)->toBe(100);
});

it('denies non-hse-officers from approving or rejecting material requests', function () {
    $nonOfficer = User::factory()->create();
    $nonOfficer->assignRole('HSE Site Officer');

    $siteOfficer = requestTestSiteOfficer();

    $project = Project::create([
        'project_name' => 'Site Alpha',
        'project_code' => 'SA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 100,
    ]);

    $employee = Employee::create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'gender' => 'Female',
        'job_title' => 'Worker',
    ]);

    $materialRequest = createMaterialRequestWithEmployees($project, $material, $siteOfficer, [
        ['employee_id' => $employee->id, 'quantity' => 10],
    ]);

    $this->actingAs($nonOfficer)
        ->post(route('hse-officer.material-requests.approve', $materialRequest), [
            'permitted_quantities' => [
                $employee->id => 10,
            ],
        ])
        ->assertForbidden();

    $this->actingAs($nonOfficer)
        ->post(route('hse-officer.material-requests.reject', $materialRequest), [
            'rejection_reason' => 'Reason',
        ])
        ->assertForbidden();
});

it('allows hse officer to view and approve multi-material requests without errors', function () {
    $hseOfficer = requestTestHseOfficer();
    $siteOfficer = requestTestSiteOfficer();

    $project = Project::create([
        'project_name' => 'Site Theta',
        'project_code' => 'ST-999',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $materialA = Material::create([
        'material_name' => 'Safety Gloves A',
        'material_description' => 'Gloves',
        'quantity' => 100,
    ]);

    $materialB = Material::create([
        'material_name' => 'Protective Mask B',
        'material_description' => 'Masks',
        'quantity' => 50,
    ]);

    // Create multi-material request (material_id is null)
    $materialRequest = MaterialRequest::create([
        'material_id' => null,
        'project_id' => $project->id,
        'requested_by' => $siteOfficer->id,
        'quantity' => 15,
        'description' => 'Multi-material requisition for Site Theta',
        'status' => 'pending',
    ]);

    $itemA = MaterialRequestItem::create([
        'material_request_id' => $materialRequest->id,
        'material_id' => $materialA->id,
        'quantity' => 10,
    ]);

    $itemB = MaterialRequestItem::create([
        'material_request_id' => $materialRequest->id,
        'material_id' => $materialB->id,
        'quantity' => 5,
    ]);

    // Test GET show page works without error
    $this->actingAs($hseOfficer)
        ->get(route('hse-officer.material-requests.show', $materialRequest))
        ->assertOk()
        ->assertSee('Safety Gloves A')
        ->assertSee('Protective Mask B')
        ->assertSee('Decision Panel');

    // Test approving with custom or full quantities
    $response = $this->actingAs($hseOfficer)
        ->post(route('hse-officer.material-requests.approve', $materialRequest), [
            'item_permitted_quantities' => [
                $itemA->id => 10,
                $itemB->id => 5,
            ],
        ]);

    $response->assertRedirect(route('hse-officer.material-requests.index'));
    $response->assertSessionHas('success');

    expect($materialRequest->fresh()->isApproved())->toBeTrue();
    expect($materialRequest->fresh()->permitted_quantity)->toBe(15);
    expect($materialA->fresh()->quantity)->toBe(90);
    expect($materialB->fresh()->quantity)->toBe(45);

    expect(MaterialProjectAssignment::where('material_id', $materialA->id)->where('project_id', $project->id)->first()->quantity)->toBe(10);
    expect(MaterialProjectAssignment::where('material_id', $materialB->id)->where('project_id', $project->id)->first()->quantity)->toBe(5);
});

it('allows hse officer to approve some items and reject other items in a multi-item request', function () {
    $hseOfficer = requestTestHseOfficer();
    $siteOfficer = requestTestSiteOfficer();

    $project = Project::create([
        'project_name' => 'Site Gamma',
        'project_code' => 'SG-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $materialA = Material::create([
        'material_name' => 'Caution Tape',
        'material_description' => 'Yellow safety caution tape',
        'quantity' => 20,
    ]);

    $materialB = Material::create([
        'material_name' => 'Cotton Flag',
        'material_description' => 'Red cotton marker flag',
        'quantity' => 0,
    ]);

    $materialRequest = MaterialRequest::create([
        'material_id' => null,
        'project_id' => $project->id,
        'requested_by' => $siteOfficer->id,
        'quantity' => 12,
        'description' => 'Site materials',
        'status' => 'pending',
    ]);

    $itemA = MaterialRequestItem::create([
        'material_request_id' => $materialRequest->id,
        'material_id' => $materialA->id,
        'quantity' => 10,
        'status' => 'pending',
    ]);

    $itemB = MaterialRequestItem::create([
        'material_request_id' => $materialRequest->id,
        'material_id' => $materialB->id,
        'quantity' => 2,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($hseOfficer)
        ->post(route('hse-officer.material-requests.decide-items', $materialRequest), [
            'items' => [
                $itemA->id => [
                    'action' => 'approve',
                    'permitted_quantity' => 7,
                ],
                $itemB->id => [
                    'action' => 'reject',
                    'rejection_reason' => 'Out of stock in Head Office',
                ],
            ],
        ]);

    $response->assertRedirect(route('hse-officer.material-requests.show', $materialRequest));
    $response->assertSessionHas('success');

    $itemA->refresh();
    $itemB->refresh();
    $materialRequest->refresh();

    expect($itemA->isApproved())->toBeTrue();
    expect($itemA->permitted_quantity)->toBe(7);
    expect($itemA->approved_by)->toBe($hseOfficer->id);

    expect($itemB->isRejected())->toBeTrue();
    expect($itemB->rejection_reason)->toBe('Out of stock in Head Office');
    expect($itemB->permitted_quantity)->toBe(0);

    expect($materialRequest->status)->toBe('partial_approved');
    expect($materialRequest->permitted_quantity)->toBe(7);

    // Stock deduction check
    expect($materialA->fresh()->quantity)->toBe(13); // 20 - 7
    expect($materialB->fresh()->quantity)->toBe(0);

    // Assignment check
    expect(MaterialProjectAssignment::where('material_id', $materialA->id)->where('project_id', $project->id)->first()->quantity)->toBe(7);
    expect(MaterialProjectAssignment::where('material_id', $materialB->id)->where('project_id', $project->id)->count())->toBe(0);
});

it('marks request as rejected when all items are rejected via decide-items', function () {
    $hseOfficer = requestTestHseOfficer();
    $siteOfficer = requestTestSiteOfficer();

    $project = Project::create([
        'project_name' => 'Site Delta',
        'project_code' => 'SD-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Earplugs',
        'material_description' => 'Ear protection plugs',
        'quantity' => 0,
    ]);

    $materialRequest = MaterialRequest::create([
        'material_id' => null,
        'project_id' => $project->id,
        'requested_by' => $siteOfficer->id,
        'quantity' => 10,
        'status' => 'pending',
    ]);

    $item = MaterialRequestItem::create([
        'material_request_id' => $materialRequest->id,
        'material_id' => $material->id,
        'quantity' => 10,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($hseOfficer)
        ->post(route('hse-officer.material-requests.decide-items', $materialRequest), [
            'items' => [
                $item->id => [
                    'action' => 'reject',
                    'rejection_reason' => 'Currently unavailable',
                ],
            ],
        ]);

    $response->assertRedirect(route('hse-officer.material-requests.show', $materialRequest));
    expect($materialRequest->fresh()->isRejected())->toBeTrue();
    expect($item->fresh()->isRejected())->toBeTrue();
    expect($material->fresh()->quantity)->toBe(0);
});
