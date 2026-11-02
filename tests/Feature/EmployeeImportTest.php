<?php

use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeSpreadsheetService;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows hse head office to download employee import template', function () {
    $headOffice = User::factory()->create();
    $headOffice->assignRole('HSE Head Office');

    $this->actingAs($headOffice)
        ->get(route('employees.import.template'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.ms-excel');
});

it('imports employee history data from xls file by hse head office', function () {
    $headOffice = User::factory()->create();
    $headOffice->assignRole('HSE Head Office');

    $path = storage_path('app/test_employees.xls');
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([EmployeeSpreadsheetService::HEADERS], null, 'A1');
    $sheet->fromArray([
        ['Alice Brown', 'Site Supervisor', 'Alpha Project', 'Safety Gloves', '2026-07-23'],
        ['Bob Green', 'Technician', 'Beta Site', 'Safety Glasses', '2026-07-23'],
    ], null, 'A2');
    (new Xls($spreadsheet))->save($path);

    $this->actingAs($headOffice)
        ->post(route('employees.import.store'), [
            'file' => new UploadedFile($path, 'employees.xls', 'application/vnd.ms-excel', null, true),
        ])
        ->assertRedirect(route('employees.index'))
        ->assertSessionHas('success');

    expect(Employee::count())->toBe(2);
    expect(Employee::where('name', 'Alice Brown')->exists())->toBeTrue();

    @unlink($path);
});

it('imports employees with normalized headers and flexible date formats', function () {
    $headOffice = User::factory()->create();
    $headOffice->assignRole('HSE Head Office');

    $path = storage_path('app/test_normalized_import.xls');
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    // Headers with case, space, and name variations
    $sheet->fromArray([['EMPLOYEE NAME', 'Job Title', 'PROJECT', 'Material Type', 'Date']], null, 'A1');
    $sheet->fromArray([
        ['Charlie Day', 'Officer', 'Alpha Project', 'Helmet', '07/23/2026'],
        ['David Miller', 'Engineer', 'Beta Site', 'Gloves', '2026/07/23'],
        ['Eva Green', 'Inspector', 'Gamma Site', 'Vest', 45861], // Excel serial integer
    ], null, 'A2');
    (new Xls($spreadsheet))->save($path);

    $this->actingAs($headOffice)
        ->post(route('employees.import.store'), [
            'file' => new UploadedFile($path, 'employees_norm.xls', 'application/vnd.ms-excel', null, true),
        ])
        ->assertRedirect(route('employees.index'))
        ->assertSessionHas('success');

    expect(Employee::where('name', 'Charlie Day')->where('date', '2026-07-23')->exists())->toBeTrue();
    expect(Employee::where('name', 'David Miller')->where('date', '2026-07-23')->exists())->toBeTrue();

    @unlink($path);
});

it('returns clear error messages when importing invalid files', function () {
    $headOffice = User::factory()->create();
    $headOffice->assignRole('HSE Head Office');

    $path = storage_path('app/invalid_headers.xls');
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([['Unknown Header', 'Foo', 'Bar']], null, 'A1');
    (new Xls($spreadsheet))->save($path);

    $this->actingAs($headOffice)
        ->post(route('employees.import.store'), [
            'file' => new UploadedFile($path, 'invalid.xls', 'application/vnd.ms-excel', null, true),
        ])
        ->assertRedirect(route('employees.import'))
        ->assertSessionHas('error');

    @unlink($path);
});

it('allows hse site officer to access import employees page', function () {
    $user = User::factory()->create();
    $user->assignRole('HSE Site Officer');

    $this->actingAs($user)->get(route('employees.import'))->assertOk();
});
