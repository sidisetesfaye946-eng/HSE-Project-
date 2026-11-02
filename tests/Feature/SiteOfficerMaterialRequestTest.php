<?php

use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestEmployee;
use App\Models\Project;
use App\Models\User;
use App\Services\MaterialRequestSpreadsheetService;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function siteOfficerForMaterialRequest(): User
{
    $user = User::factory()->create();
    $user->assignRole('HSE Site Officer');

    return $user;
}

it('allows site officer to submit material request with multiple employees', function () {
    $siteOfficer = siteOfficerForMaterialRequest();

    $project = Project::create([
        'project_name' => 'Site Alpha',
        'project_code' => 'SA-001',
        'site_officer_id' => $siteOfficer->id,
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

    $project->employees()->sync([$employeeA->id, $employeeB->id]);

    $material = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 100,
    ]);

    $this->actingAs($siteOfficer)->post(route('site-officer.material-requests.store'), [
        'project_id' => $project->id,
        'material_id' => $material->id,
        'employee_requests' => [
            ['employee_id' => $employeeA->id, 'quantity' => 2],
            ['employee_id' => $employeeB->id, 'quantity' => 3],
        ],
    ])->assertRedirect(route('site-officer.material-requests.index'));

    $materialRequest = MaterialRequest::first();

    expect($materialRequest)->not->toBeNull();
    expect($materialRequest->quantity)->toBe(5);
    expect(MaterialRequestEmployee::count())->toBe(2);
});

it('creates and attaches a new employee when name is not in project', function () {
    $siteOfficer = siteOfficerForMaterialRequest();

    $project = Project::create([
        'project_name' => 'Site Beta',
        'project_code' => 'SB-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Gloves',
        'material_description' => 'Safety gloves',
        'quantity' => 50,
    ]);

    $this->actingAs($siteOfficer)->post(route('site-officer.material-requests.store'), [
        'project_id' => $project->id,
        'material_id' => $material->id,
        'employee_requests' => [
            ['employee_name' => 'New Worker', 'quantity' => 1],
        ],
    ])->assertRedirect(route('site-officer.material-requests.index'));

    $employee = Employee::where('first_name', 'New')->where('last_name', 'Worker')->first();

    expect($employee)->not->toBeNull();
    expect($project->fresh()->employees->contains($employee))->toBeTrue();
    expect(MaterialRequestEmployee::first()->employee_id)->toBe($employee->id);
});

it('allows site officer to download material request template', function () {
    $siteOfficer = siteOfficerForMaterialRequest();

    $this->actingAs($siteOfficer)
        ->get(route('site-officer.material-requests.import.template'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.ms-excel');
});

it('imports material requests from xls file', function () {
    $siteOfficer = siteOfficerForMaterialRequest();

    $project = Project::create([
        'project_name' => 'Site Alpha',
        'project_code' => 'SA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $materialA = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 100,
    ]);

    $path = storage_path('app/test_material_requests.xls');
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([MaterialRequestSpreadsheetService::HEADERS], null, 'A1');
    $sheet->fromArray([
        ['John Doe'],
        ['Jane Smith'],
        ['Bob Miller'],
    ], null, 'A2');
    (new Xls($spreadsheet))->save($path);

    $this->actingAs($siteOfficer)
        ->post(route('site-officer.material-requests.import'), [
            'project_id' => $project->id,
            'material_id' => $materialA->id,
            'quantity' => 3,
            'quantity_per_employee' => 3,
            'file' => new UploadedFile($path, 'test_material_requests.xls', 'application/vnd.ms-excel', null, true),
        ])
        ->assertRedirect(route('site-officer.material-requests.index'))
        ->assertSessionHas('success');

    expect(MaterialRequest::count())->toBe(1);

    $helmetReq = MaterialRequest::where('material_id', $materialA->id)->first();
    expect($helmetReq)->not->toBeNull();
    expect($helmetReq->quantity)->toBe(9); // 3 employees * 3 quantity each
    expect($helmetReq->requestedEmployees()->count())->toBe(3);

    @unlink($path);
});

it('rejects material requests import with invalid template', function () {
    $siteOfficer = siteOfficerForMaterialRequest();

    $project = Project::create([
        'project_name' => 'Site Alpha',
        'project_code' => 'SA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $materialA = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 100,
    ]);

    $path = storage_path('app/invalid_material_requests.xls');
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([['wrong_header']], null, 'A1');
    $sheet->fromArray([
        ['John Doe'],
    ], null, 'A2');
    (new Xls($spreadsheet))->save($path);

    $this->actingAs($siteOfficer)
        ->post(route('site-officer.material-requests.import'), [
            'project_id' => $project->id,
            'material_id' => $materialA->id,
            'quantity' => 2,
            'quantity_per_employee' => 2,
            'file' => new UploadedFile($path, 'invalid_material_requests.xls', 'application/vnd.ms-excel', null, true),
        ])
        ->assertRedirect(route('site-officer.material-requests.create'))
        ->assertSessionHas('error');

    expect(MaterialRequest::count())->toBe(0);

    @unlink($path);
});

it('imports material requests without a file', function () {
    $siteOfficer = siteOfficerForMaterialRequest();

    $project = Project::create([
        'project_name' => 'Site Alpha',
        'project_code' => 'SA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $materialA = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 100,
    ]);

    $this->actingAs($siteOfficer)
        ->post(route('site-officer.material-requests.import'), [
            'project_id' => $project->id,
            'material_id' => $materialA->id,
            'quantity' => 5,
            'quantity_per_employee' => 5,
        ])
        ->assertRedirect(route('site-officer.material-requests.index'))
        ->assertSessionHas('success');

    expect(MaterialRequest::count())->toBe(1);

    $helmetReq = MaterialRequest::where('material_id', $materialA->id)->first();
    expect($helmetReq)->not->toBeNull();
    expect($helmetReq->quantity)->toBe(5);
    expect($helmetReq->requestedEmployees()->count())->toBe(0);
});

it('denies hse site officer from accessing project employee management routes', function () {
    $siteOfficer = siteOfficerForMaterialRequest();

    $project = Project::create([
        'project_name' => 'Site Alpha',
        'project_code' => 'SA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $this->actingAs($siteOfficer)
        ->get(route('site-officer.projects.employees.index', $project))
        ->assertForbidden();

    $this->actingAs($siteOfficer)
        ->post(route('site-officer.projects.employees.sync', $project), ['employee_ids' => []])
        ->assertForbidden();
});

it('rejects manual material request with duplicate employees', function () {
    $siteOfficer = siteOfficerForMaterialRequest();

    $project = Project::create([
        'project_name' => 'Site Alpha',
        'project_code' => 'SA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $employee = Employee::create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'gender' => 'Male',
        'job_title' => 'Worker',
    ]);

    $material = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 100,
    ]);

    $this->actingAs($siteOfficer)
        ->post(route('site-officer.material-requests.store'), [
            'project_id' => $project->id,
            'material_id' => $material->id,
            'employee_requests' => [
                ['employee_id' => $employee->id, 'quantity' => 1],
                ['employee_id' => $employee->id, 'quantity' => 1],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'Duplicate employee detected: John Doe has been added more than once in this request. Please remove duplicate entries.');

    expect(MaterialRequest::count())->toBe(0);
});

it('rejects import material request with duplicate employees in spreadsheet', function () {
    $siteOfficer = siteOfficerForMaterialRequest();

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

    $path = storage_path('app/dup_material_requests.xls');
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([MaterialRequestSpreadsheetService::HEADERS], null, 'A1');
    $sheet->fromArray([
        ['John Doe'],
        ['John Doe'],
    ], null, 'A2');
    (new Xls($spreadsheet))->save($path);

    $this->actingAs($siteOfficer)
        ->post(route('site-officer.material-requests.import'), [
            'project_id' => $project->id,
            'material_id' => $material->id,
            'quantity' => 1,
            'quantity_per_employee' => 1,
            'file' => new UploadedFile($path, 'dup_material_requests.xls', 'application/vnd.ms-excel', null, true),
        ])
        ->assertRedirect(route('site-officer.material-requests.create'))
        ->assertSessionHas('error', 'Duplicate employee detected: John Doe has been added more than once in this request. Please remove duplicate entries.');

    expect(MaterialRequest::count())->toBe(0);

    @unlink($path);
});

it('auto-fills assigned project on material request creation page', function () {
    $siteOfficer = siteOfficerForMaterialRequest();

    $project = Project::create([
        'project_name' => 'Assigned Project Gamma',
        'project_code' => 'AP-777',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $this->actingAs($siteOfficer)
        ->get(route('site-officer.material-requests.create'))
        ->assertOk()
        ->assertSee('Assigned Project Gamma')
        ->assertSee('AP-777');
});

it('allows site officer to submit material request with multiple items', function () {
    $siteOfficer = siteOfficerForMaterialRequest();

    $project = Project::create([
        'project_name' => 'Site Delta',
        'project_code' => 'SD-101',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $materialA = Material::create([
        'material_name' => 'Safety Gloves',
        'material_description' => 'Heavy duty gloves',
        'quantity' => 100,
    ]);

    $materialB = Material::create([
        'material_name' => 'Goggles',
        'material_description' => 'Eye protection',
        'quantity' => 50,
    ]);

    $response = $this->actingAs($siteOfficer)->post(route('site-officer.material-requests.store'), [
        'project_id' => $project->id,
        'items' => [
            ['material_id' => $materialA->id, 'quantity' => 10],
            ['material_id' => $materialB->id, 'quantity' => 5],
        ],
        'description' => 'Safety equipment requisition for site crew',
    ]);

    $response->assertRedirect(route('site-officer.material-requests.index'));

    $materialRequest = MaterialRequest::latest()->first();

    expect($materialRequest)->not->toBeNull();
    expect($materialRequest->project_id)->toBe($project->id);
    expect($materialRequest->items)->toHaveCount(2);
    expect($materialRequest->total_quantity)->toBe(15);
    expect($materialRequest->items->pluck('quantity')->all())->toEqual([10, 5]);
    expect($materialRequest->material_name)->toContain('Safety Gloves');
    expect($materialRequest->material_name)->toContain('Goggles');

    // Check show page displays both items
    $this->actingAs($siteOfficer)
        ->get(route('site-officer.material-requests.show', $materialRequest))
        ->assertOk()
        ->assertSee('Safety Gloves')
        ->assertSee('Goggles')
        ->assertSee('10')
        ->assertSee('5');
});

it('allows site officer to submit multi-material requisition via import without file', function () {
    $siteOfficer = siteOfficerForMaterialRequest();

    $project = Project::create([
        'project_name' => 'Site Epsilon',
        'project_code' => 'SE-202',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $materialA = Material::create([
        'material_name' => 'Standard Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 100,
    ]);

    $materialB = Material::create([
        'material_name' => 'Ear Plugs',
        'material_description' => 'Hearing protection',
        'quantity' => 200,
    ]);

    $response = $this->actingAs($siteOfficer)->post(route('site-officer.material-requests.import'), [
        'project_id' => $project->id,
        'items' => [
            ['material_id' => $materialA->id, 'quantity' => 25],
            ['material_id' => $materialB->id, 'quantity' => 50],
        ],
        'description' => 'Multi-material request direct import without file',
    ]);

    $response->assertRedirect(route('site-officer.material-requests.index'));
    $response->assertSessionHas('success');

    $materialRequest = MaterialRequest::latest()->first();

    expect($materialRequest)->not->toBeNull();
    expect($materialRequest->project_id)->toBe($project->id);
    expect($materialRequest->items)->toHaveCount(2);
    expect($materialRequest->total_quantity)->toBe(75);
    expect($materialRequest->items->pluck('quantity')->all())->toEqual([25, 50]);
    expect($materialRequest->material_name)->toContain('Standard Helmet');
    expect($materialRequest->material_name)->toContain('Ear Plugs');
});

it('allows site officer to submit multi-material requisition via import with spreadsheet file', function () {
    $siteOfficer = siteOfficerForMaterialRequest();

    $project = Project::create([
        'project_name' => 'Site Zeta',
        'project_code' => 'SZ-303',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $materialA = Material::create([
        'material_name' => 'Safety Boots',
        'material_description' => 'Steel toe boots',
        'quantity' => 50,
    ]);

    $materialB = Material::create([
        'material_name' => 'Reflective Vest',
        'material_description' => 'High visibility vest',
        'quantity' => 80,
    ]);

    $path = storage_path('app/multi_material_import_test.xls');
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([MaterialRequestSpreadsheetService::HEADERS], null, 'A1');
    $sheet->fromArray([
        ['Alice Walker', 'Technician', 'Site Zeta'],
        ['Bob Builder', 'Mason', 'Site Zeta'],
    ], null, 'A2');
    (new Xls($spreadsheet))->save($path);

    $response = $this->actingAs($siteOfficer)->post(route('site-officer.material-requests.import'), [
        'project_id' => $project->id,
        'items' => [
            ['material_id' => $materialA->id, 'quantity' => 2],
            ['material_id' => $materialB->id, 'quantity' => 2],
        ],
        'file' => new UploadedFile($path, 'multi_material_import_test.xls', 'application/vnd.ms-excel', null, true),
        'description' => 'Multi-material request with employee sheet',
    ]);

    @unlink($path);

    $response->assertRedirect(route('site-officer.material-requests.index'));
    $response->assertSessionHas('success');

    $materialRequest = MaterialRequest::latest()->first();

    expect($materialRequest)->not->toBeNull();
    expect($materialRequest->project_id)->toBe($project->id);
    expect($materialRequest->items)->toHaveCount(2);
    expect($materialRequest->total_quantity)->toBe(4);
    expect($materialRequest->items->pluck('quantity')->all())->toEqual([2, 2]);
    expect($materialRequest->requestedEmployees)->toHaveCount(2);
});
