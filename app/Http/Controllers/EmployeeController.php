<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialEmployeeAssignment;
use App\Models\Project;
use App\Services\EmployeeSpreadsheetService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function __construct(private EmployeeSpreadsheetService $spreadsheetService) {}

    public function index(Request $request): View
    {
        $hasFilter = $request->filled('search')
            || $request->filled('project_id')
            || $request->filled('project')
            || $request->filled('from_date')
            || $request->filled('to_date')
            || $request->filled('date')
            || $request->filled('quarter');

        return view('employees.index', [
            'employees' => $this->buildEmployeeQuery($request)->paginate(10)->withQueryString(),
            'projects' => Project::orderBy('project_name')->get(),
            'hasFilter' => $hasFilter,
        ]);
    }

    public function export(Request $request): Response
    {
        $format = strtolower($request->query('format', 'xlsx'));
        $employees = $this->buildEmployeeQuery($request)->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('employees.export', [
                'employees' => $employees,
                'title' => 'Employee Report',
            ]);

            return $pdf->download('employees.pdf');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Employees');

        $sheet->fromArray([
            ['Employee Name', 'Job Title', 'Project', 'Material', 'Date'],
        ], null, 'A1');

        $row = 2;
        foreach ($employees as $employee) {
            $date = $employee->date
                ? Carbon::parse($employee->date)->format('Y-m-d')
                : $employee->created_at->format('Y-m-d');

            $sheet->fromArray([
                $employee->fullName(),
                $employee->job_title ?: '-',
                $employee->project_name_display ?: '-',
                $employee->material_type_display ?: '-',
                $date,
            ], null, "A{$row}");

            $row++;
        }

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="employees.xls"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function buildEmployeeQuery(Request $request)
    {
        $query = Employee::with(['projectRelation', 'materialRelation', 'materialAssignments.project', 'materialAssignments.material'])->latest();

        $search = $request->string('search')->trim()->toString();
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%")
                    ->orWhere('project', 'like', "%{$search}%")
                    ->orWhere('material_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('project_id')) {
            $projectId = $request->input('project_id');
            $query->where(function ($q) use ($projectId): void {
                $q->where('project_id', $projectId)
                    ->orWhereHas('projects', function ($pq) use ($projectId): void {
                        $pq->where('projects.id', $projectId);
                    });
            });
        } elseif ($request->filled('project')) {
            $projectStr = $request->input('project');
            $query->where('project', 'like', "%{$projectStr}%");
        }

        if ($request->filled('from_date')) {
            $query->where(function ($q) use ($request): void {
                $q->whereDate('date', '>=', $request->input('from_date'))
                    ->orWhere(function ($q2) use ($request): void {
                        $q2->whereNull('date')->whereDate('created_at', '>=', $request->input('from_date'));
                    });
            });
        }

        if ($request->filled('to_date')) {
            $query->where(function ($q) use ($request): void {
                $q->whereDate('date', '<=', $request->input('to_date'))
                    ->orWhere(function ($q2) use ($request): void {
                        $q2->whereNull('date')->whereDate('created_at', '<=', $request->input('to_date'));
                    });
            });
        }

        if ($request->filled('date') && ! $request->filled('from_date') && ! $request->filled('to_date')) {
            $date = $request->input('date');
            $query->where(function ($q) use ($date): void {
                $q->whereDate('date', $date)
                    ->orWhere(function ($q2) use ($date): void {
                        $q2->whereNull('date')->whereDate('created_at', $date);
                    });
            });
        }

        if ($request->filled('quarter')) {
            $quarter = $request->input('quarter');
            $year = $request->input('year', date('Y'));

            $months = match ($quarter) {
                'Q1' => [1, 3],
                'Q2' => [4, 6],
                'Q3' => [7, 9],
                'Q4' => [10, 12],
                default => null,
            };

            if ($months) {
                $query->where(function ($q) use ($year, $months): void {
                    $q->where(function ($q1) use ($year, $months): void {
                        $q1->whereYear('date', $year)
                            ->whereMonth('date', '>=', $months[0])
                            ->whereMonth('date', '<=', $months[1]);
                    })->orWhere(function ($q2) use ($year, $months): void {
                        $q2->whereNull('date')
                            ->whereYear('created_at', $year)
                            ->whereMonth('created_at', '>=', $months[0])
                            ->whereMonth('created_at', '<=', $months[1]);
                    });
                });
            }
        }

        return $query;
    }

    public function show(Employee $employee): View
    {
        $employee->load(['materialAssignments' => function ($query): void {
            $query->with(['material', 'project'])->latest('assigned_date');
        }]);

        return view('employees.show', [
            'employee' => $employee,
        ]);
    }

    public function create(): View
    {
        return view('employees.create', [
            'employee' => new Employee,
            'projects' => Project::orderBy('project_name')->get(),
            'materials' => Material::orderBy('material_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $hasNewFields = $request->has('employee_name')
            || $request->has('name')
            || $request->has('project')
            || $request->has('project_id')
            || $request->has('material_type')
            || $request->has('material_id')
            || $request->has('date');

        if ($hasNewFields) {
            $name = $request->input('employee_name') ?? $request->input('name');
            if (! $name && $request->filled('first_name')) {
                $name = trim($request->input('first_name').' '.$request->input('last_name'));
            }

            if (! $name) {
                $request->validate(['employee_name' => ['required', 'string', 'max:255']]);
            }

            $projectIdInput = $request->input('project_id');
            $projectInput = $request->input('project');
            if (! $projectIdInput && ! $projectInput) {
                $request->validate(['project' => ['required']]);
            }

            $materialIdInput = $request->input('material_id');
            $materialTypeInput = $request->input('material_type');
            if (! $materialIdInput && ! $materialTypeInput) {
                $request->validate(['material_type' => ['required']]);
            }

            $dateInput = $request->input('date') ?? $request->input('assigned_date');
            if (! $dateInput) {
                $request->validate(['date' => ['required', 'date']]);
            }

            $parts = explode(' ', trim((string) $name), 2);
            $firstName = $request->input('first_name') ?? ($parts[0] ?? $name);
            $lastName = $request->input('last_name') ?? ($parts[1] ?? '');

            $projectId = is_numeric($projectIdInput) ? (int) $projectIdInput : (is_numeric($projectInput) ? (int) $projectInput : null);
            $projectName = $projectId ? Project::find($projectId)?->project_name : (string) ($projectInput ?: $projectIdInput);

            if (! $projectId && filled($projectName)) {
                $p = Project::where('project_name', $projectName)->first();
                if (! $p) {
                    $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $projectName), 0, 8)) ?: 'PRJ';
                    $p = Project::create([
                        'project_name' => $projectName,
                        'project_code' => $code,
                    ]);
                }
                $projectId = $p->id;
            }

            $materialId = is_numeric($materialIdInput) ? (int) $materialIdInput : (is_numeric($materialTypeInput) ? (int) $materialTypeInput : null);
            $materialTypeName = $materialId ? Material::find($materialId)?->material_name : (string) ($materialTypeInput ?: $materialIdInput);

            if (! $materialId && filled($materialTypeName)) {
                $m = Material::where('material_name', $materialTypeName)->first();
                if (! $m) {
                    $m = Material::create([
                        'material_name' => $materialTypeName,
                        'material_description' => $materialTypeName,
                        'quantity' => 0,
                    ]);
                }
                $materialId = $m->id;
            }

            $dateInput = $dateInput ?: now()->toDateString();

            $employee = Employee::create([
                'first_name' => $firstName ?: 'N/A',
                'last_name' => $lastName,
                'name' => $name,
                'gender' => $request->input('gender', 'Other'),
                'job_title' => $request->input('job_title', 'Staff'),
                'project_id' => $projectId,
                'project' => $projectName,
                'material_id' => $materialId,
                'material_type' => $materialTypeName,
                'date' => $dateInput,
            ]);

            if ($projectId && $materialId) {
                MaterialEmployeeAssignment::firstOrCreate(
                    [
                        'material_id' => $materialId,
                        'project_id' => $projectId,
                        'employee_id' => $employee->id,
                        'assigned_date' => $dateInput,
                    ],
                    [
                        'quantity' => 1,
                        'assigned_by' => $request->user()?->id ?? 1,
                    ]
                );
            }

            if ($projectId) {
                $employee->projects()->syncWithoutDetaching([$projectId]);
            }
        } else {
            $data = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'gender' => ['required', 'string', 'in:Male,Female,Other'],
                'job_title' => ['required', 'string', 'max:255'],
            ]);

            Employee::create($data);
        }

        return Redirect::route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', [
            'employee' => $employee,
            'projects' => Project::orderBy('project_name')->get(),
            'materials' => Material::orderBy('material_name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $hasNewFields = $request->has('employee_name')
            || $request->has('name')
            || $request->has('project')
            || $request->has('project_id')
            || $request->has('material_type')
            || $request->has('material_id')
            || $request->has('date');

        if ($hasNewFields) {
            $name = $request->input('employee_name') ?? $request->input('name') ?? $employee->fullName();
            $parts = explode(' ', trim((string) $name), 2);
            $firstName = $request->input('first_name') ?? ($parts[0] ?? $name);
            $lastName = $request->input('last_name') ?? ($parts[1] ?? '');

            $projectIdInput = $request->input('project_id') ?? $request->input('project');
            $projectId = is_numeric($projectIdInput) ? (int) $projectIdInput : null;
            $projectName = $projectId ? Project::find($projectId)?->project_name : (string) $projectIdInput;

            $materialIdInput = $request->input('material_id') ?? $request->input('material_type');
            $materialId = is_numeric($materialIdInput) ? (int) $materialIdInput : null;
            $materialTypeName = $materialId ? Material::find($materialId)?->material_name : (string) $materialIdInput;

            $dateInput = $request->input('date') ?? $request->input('assigned_date') ?? $employee->date;

            $employee->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $name,
                'gender' => $request->input('gender', $employee->gender ?: 'Other'),
                'job_title' => $request->input('job_title', $employee->job_title ?: 'Staff'),
                'project_id' => $projectId ?: $employee->project_id,
                'project' => $projectName ?: $employee->project,
                'material_id' => $materialId ?: $employee->material_id,
                'material_type' => $materialTypeName ?: $employee->material_type,
                'date' => $dateInput,
            ]);
        } else {
            $data = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'gender' => ['required', 'string', 'in:Male,Female,Other'],
                'job_title' => ['required', 'string', 'max:255'],
            ]);

            $employee->update($data);
        }

        return Redirect::route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return Redirect::route('employees.index')->with('success', 'Employee deleted successfully.');
    }

    public function importForm(): View
    {
        return view('employees.import');
    }

    public function downloadTemplate(): StreamedResponse
    {
        return $this->spreadsheetService->downloadTemplate();
    }

    public function importStore(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv,txt', 'max:10240'],
        ]);

        try {
            $result = $this->spreadsheetService->import($request->file('file'));
        } catch (\Throwable $e) {
            return Redirect::route('employees.import')
                ->with('error', 'Failed to process import file: '.$e->getMessage());
        }

        if ($result['imported'] === 0 && ! empty($result['errors'])) {
            return Redirect::route('employees.import')
                ->with('error', implode(' ', $result['errors']));
        }

        $message = $result['imported'].' employee(s) imported successfully.';

        if (! empty($result['errors'])) {
            $message .= ' Some rows were skipped: '.implode(' ', $result['errors']);
        }

        // Flash success message
        $route = $request->user()?->hasRole('HSE Site Officer') ? 'site-officer.projects.index' : 'employees.index';

        return Redirect::route($route)->with('success', $message);
    }
}
