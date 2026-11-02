<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialEmployeeAssignment;
use App\Models\Project;
use App\Services\WorkflowNotificationService;
use App\SiteMaterialBalanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteOfficerEmployeeAssignmentController extends Controller
{
    public function __construct(private SiteMaterialBalanceService $balanceService) {}

    public function create(Request $request): View
    {
        if ($request->user()->name === 'Daniel Girma' || $request->user()->email === 'danielg@example.com') {
            abort(403, 'Unauthorized action.');
        }

        $projects = Project::query()
            ->when(! $request->user()->hasAnyRole(['Admin', 'HSE Officer']), function ($query) use ($request) {
                return $query->where('site_officer_id', $request->user()->id);
            })
            ->with('employees')
            ->orderBy('project_name')
            ->get();

        $balancesForJs = $projects->mapWithKeys(function (Project $project): array {
            $balances = $this->balanceService->balancesForProject($project)
                ->filter(fn (array $row): bool => $row['available'] > 0)
                ->map(fn (array $row): array => [
                    'material_id' => $row['material']->id,
                    'label' => $row['material']->material_name.' (Available: '.$row['available'].')',
                    'search' => strtolower($row['material']->material_name),
                ])
                ->values()
                ->all();

            return [$project->id => $balances];
        })->all();

        // Build a per-project employees map for the JS employee dropdown
        $employeesByProjectForJs = $projects->mapWithKeys(function (Project $project): array {
            $employees = $project->employees->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'label' => $employee->first_name.' '.$employee->last_name.' — '.$employee->job_title,
                'search' => strtolower($employee->first_name.' '.$employee->last_name.' '.$employee->job_title),
            ])->values()->all();

            return [$project->id => $employees];
        })->all();

        $assignedProject = $request->user()->assignedProject;
        $defaultProjectId = old('project_id', request('project_id', $assignedProject?->id ?? $projects->first()?->id));

        return view('site-officer.employee-assignments.create', [
            'projects' => $projects,
            'balancesForJs' => $balancesForJs,
            'employeesByProjectForJs' => $employeesByProjectForJs,
            'defaultProjectId' => $defaultProjectId,
            'employeeHistoryUrlTemplate' => route('site-officer.employees.assignment-history', ['employee' => '__EMPLOYEE__']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->name === 'Daniel Girma' || $request->user()->email === 'danielg@example.com') {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.material_id' => ['required', 'exists:materials,id'],
            'assignments.*.quantity' => ['required', 'integer', 'min:1'],
            'assignments.*.employee_id' => ['nullable', 'exists:employees,id'],
            'assignments.*.assigned_date' => ['required', 'date'],
        ]);

        $project = Project::findOrFail($validated['project_id']);

        abort_unless($project->isManagedBy($request->user()), 403);

        $assignments = collect($validated['assignments'])
            ->filter(fn (array $row): bool => filled($row['material_id'] ?? null))
            ->values();

        if ($assignments->isEmpty()) {
            throw ValidationException::withMessages([
                'assignments' => 'Add at least one material assignment.',
            ]);
        }

        $requestedByMaterial = $assignments->groupBy('material_id')->map(
            fn ($rows) => $rows->sum(fn (array $row): int => (int) $row['quantity'])
        );

        foreach ($requestedByMaterial as $materialId => $totalQuantity) {
            $available = $this->balanceService->availableQuantity($project, (int) $materialId);

            if ($available < $totalQuantity) {
                throw ValidationException::withMessages([
                    'assignments' => 'Insufficient site balance for one or more materials.',
                ]);
            }
        }

        DB::transaction(function () use ($assignments, $project, $request): void {
            foreach ($assignments as $row) {
                MaterialEmployeeAssignment::create([
                    'material_id' => $row['material_id'],
                    'project_id' => $project->id,
                    'employee_id' => $row['employee_id'] ?? null,
                    'quantity' => $row['quantity'],
                    'assigned_date' => $row['assigned_date'],
                    'assigned_by' => $request->user()->id,
                ]);
            }
        });

        $materials = Material::query()->whereIn('id', $assignments->pluck('material_id'))->get()->keyBy('id');
        $employeeIds = $assignments->pluck('employee_id')->filter()->unique();
        $employees = $employeeIds->isNotEmpty()
            ? Employee::query()->whereIn('id', $employeeIds)->get()->keyBy('id')
            : collect();

        $assignmentDetails = $assignments
            ->map(fn (array $row): array => [
                'material' => $materials->get($row['material_id']),
                'employee' => isset($row['employee_id']) ? $employees->get($row['employee_id']) : null,
                'quantity' => (int) $row['quantity'],
            ])
            ->filter(fn (array $row): bool => $row['material'] !== null);

        app(WorkflowNotificationService::class)->materialsAssignedToEmployees(
            $project,
            $assignmentDetails,
            $request->user(),
        );

        return Redirect::route('site-officer.projects.show', $project)
            ->with('success', 'Materials assigned to employees successfully.');
    }

    public function index(Request $request): View
    {
        $projectIds = Project::query()
            ->when(! $request->user()->hasAnyRole(['Admin', 'HSE Officer']), function ($query) use ($request) {
                return $query->where('site_officer_id', $request->user()->id);
            })
            ->pluck('id');

        $assignmentsQuery = MaterialEmployeeAssignment::query()
            ->whereIn('project_id', $projectIds)
            ->with(['material', 'project', 'employee']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $assignmentsQuery->where(function ($query) use ($search): void {
                $query
                    ->whereHas('material', function ($materialQuery) use ($search): void {
                        $materialQuery->where('material_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('employee', function ($employeeQuery) use ($search): void {
                        $employeeQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('project_id')) {
            $assignmentsQuery->where('project_id', $request->get('project_id'));
        }

        if ($request->filled('from_date')) {
            $assignmentsQuery->whereDate('assigned_date', '>=', $request->get('from_date'));
        }
        if ($request->filled('to_date')) {
            $assignmentsQuery->whereDate('assigned_date', '<=', $request->get('to_date'));
        }

        if ($request->filled('quarter')) {
            $quarter = (int) $request->get('quarter');
            $assignmentsQuery
                ->whereMonth('assigned_date', '>=', (($quarter - 1) * 3) + 1)
                ->whereMonth('assigned_date', '<=', $quarter * 3);
        }

        $assignments = $assignmentsQuery->latest()->paginate(15)->withQueryString();

        $projects = Project::query()
            ->when(! $request->user()->hasAnyRole(['Admin', 'HSE Officer']), function ($query) use ($request) {
                return $query->where('site_officer_id', $request->user()->id);
            })
            ->orderBy('project_name')
            ->get();

        return view('site-officer.employee-assignments.index', [
            'assignments' => $assignments,
            'projects' => $projects,
            'employeeHistoryUrlTemplate' => route('site-officer.employees.assignment-history', ['employee' => '__EMPLOYEE__']),
        ]);
    }

    public function employeeHistory(Request $request, Employee $employee): JsonResponse
    {
        $projectIds = Project::query()
            ->when(! $request->user()->hasAnyRole(['Admin', 'HSE Officer']), function ($query) use ($request) {
                return $query->where('site_officer_id', $request->user()->id);
            })
            ->pluck('id');

        $assignments = MaterialEmployeeAssignment::query()
            ->where('employee_id', $employee->id)
            ->whereIn('project_id', $projectIds)
            ->with(['material', 'project'])
            ->orderByDesc('assigned_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MaterialEmployeeAssignment $assignment): array => [
                'date' => $assignment->assigned_date->format('M d, Y'),
                'project_code' => $assignment->project->project_code,
                'project_name' => $assignment->project->project_name,
                'material' => $assignment->material->material_name,
                'quantity' => $assignment->quantity,
            ]);

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->fullName(),
                'job_title' => $employee->job_title,
            ],
            'assignments' => $assignments,
        ]);
    }

    public function export(Request $request): Response
    {
        $format = strtolower($request->query('format', 'xlsx'));

        $projectIds = Project::query()
            ->when(! $request->user()->hasAnyRole(['Admin', 'HSE Officer']), function ($query) use ($request) {
                return $query->where('site_officer_id', $request->user()->id);
            })
            ->pluck('id');

        $assignmentsQuery = MaterialEmployeeAssignment::query()
            ->whereIn('project_id', $projectIds)
            ->with(['material', 'project', 'employee']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $assignmentsQuery->where(function ($query) use ($search): void {
                $query
                    ->whereHas('material', function ($materialQuery) use ($search): void {
                        $materialQuery->where('material_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('employee', function ($employeeQuery) use ($search): void {
                        $employeeQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('project_id')) {
            $assignmentsQuery->where('project_id', $request->get('project_id'));
        }

        if ($request->filled('from_date')) {
            $assignmentsQuery->whereDate('assigned_date', '>=', $request->get('from_date'));
        }
        if ($request->filled('to_date')) {
            $assignmentsQuery->whereDate('assigned_date', '<=', $request->get('to_date'));
        }

        if ($request->filled('quarter')) {
            $quarter = (int) $request->get('quarter');
            $assignmentsQuery
                ->whereMonth('assigned_date', '>=', (($quarter - 1) * 3) + 1)
                ->whereMonth('assigned_date', '<=', $quarter * 3);
        }

        $assignments = $assignmentsQuery->latest()->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('site-officer.employee-assignments.exports.assignment-pdf', [
                'assignments' => $assignments,
                'title' => 'Employee Assignment History Report',
            ]);

            return $pdf->download('employee_assignment_history.pdf');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Employee Assignments');

        $sheet->fromArray([
            ['Date', 'Project Code', 'Project Name', 'Material', 'Quantity', 'Employee Name', 'Employee Job Title'],
        ], null, 'A1');

        $row = 2;
        foreach ($assignments as $assignment) {
            $sheet->fromArray([
                $assignment->assigned_date->format('Y-m-d'),
                $assignment->project->project_code,
                $assignment->project->project_name,
                $assignment->material->material_name,
                $assignment->quantity,
                $assignment->employee->first_name.' '.$assignment->employee->last_name,
                $assignment->employee->job_title,
            ], null, "A{$row}");
            $row++;
        }

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="employee_assignment_history.xls"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
