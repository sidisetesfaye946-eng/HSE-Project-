<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestEmployee;
use App\Models\MaterialRequestItem;
use App\Models\Project;
use App\Notifications\WorkflowNotification;
use App\Services\MaterialRequestSpreadsheetService;
use App\Services\WorkflowNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteOfficerMaterialRequestController extends Controller
{
    public function __construct(private MaterialRequestSpreadsheetService $spreadsheetService) {}

    public function downloadTemplate(): StreamedResponse
    {
        return $this->spreadsheetService->downloadTemplate();
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'items' => ['required_without:material_id', 'array', 'min:1'],
            'items.*.material_id' => ['required_with:items', 'exists:materials,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'material_id' => ['nullable', 'exists:materials,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'quantity_per_employee' => ['nullable', 'integer', 'min:1'],
            'file' => ['nullable', 'file', 'mimes:xls,xlsx', 'max:10240'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $project = Project::findOrFail($validated['project_id']);

        abort_unless($project->isManagedBy($request->user()), 403);

        $itemsData = collect($validated['items'] ?? []);

        // Backward compatibility for legacy single material requests
        $isLegacy = false;
        if ($itemsData->isEmpty() && ! empty($validated['material_id'])) {
            $isLegacy = true;
            $itemsData = collect([[
                'material_id' => (int) $validated['material_id'],
                'quantity' => (int) ($validated['quantity'] ?? $validated['quantity_per_employee'] ?? 1),
            ]]);
        }

        $result = $this->spreadsheetService->import(
            $request->file('file'),
            $project,
            $request->user(),
            $itemsData->all(),
            $validated['description'] ?? null,
            isset($validated['quantity_per_employee']) ? (int) $validated['quantity_per_employee'] : null,
            null,
            $isLegacy
        );

        if ($result['imported'] === 0 && $result['errors'] !== []) {
            return Redirect::route('site-officer.material-requests.create')
                ->withInput()
                ->with('error', implode(' ', $result['errors']));
        }

        $message = $result['imported'].' material request(s) imported successfully.';
        if ($result['errors'] !== []) {
            $message .= ' Some rows were skipped: '.implode(' ', $result['errors']);
        }

        return Redirect::route('site-officer.material-requests.index')
            ->with('success', $message);
    }

    public function create(Request $request): View
    {
        $projects = Project::query()
            ->when(! $request->user()->hasAnyRole(['Admin', 'HSE Officer']), function ($query) use ($request) {
                return $query->where('site_officer_id', $request->user()->id)
                    ->orWhereHas('siteOfficer', fn ($q) => $q->where('id', $request->user()->id));
            })
            ->orderBy('project_name')
            ->get();

        $allowedNames = ['Helmet', 'Safety Shoe', 'Staff Vest', 'Normal Vest'];

        foreach ($allowedNames as $name) {
            Material::firstOrCreate(
                ['material_name' => $name],
                [
                    'material_description' => $name,
                    'quantity' => 100,
                    'initial_stock' => 100,
                ]
            );
        }

        $materials = Material::query()
            ->whereIn('material_name', $allowedNames)
            ->orderBy('material_name')
            ->get();

        $materialsForJs = $materials->map(fn (Material $material): array => [
            'id' => $material->id,
            'label' => $material->material_name,
            'search' => strtolower($material->material_name),
        ])->values()->all();

        // All materials (unrestricted) for the import/upload tab
        $allMaterialsForJs = Material::query()
            ->orderBy('material_name')
            ->get()
            ->map(fn (Material $material): array => [
                'id' => $material->id,
                'label' => $material->material_name,
                'search' => strtolower($material->material_name),
            ])->values()->all();

        $employeesByProjectForJs = $projects
            ->load('employees')
            ->mapWithKeys(function (Project $project): array {
                $employees = $project->employees->map(fn (Employee $employee): array => [
                    'id' => $employee->id,
                    'label' => $employee->fullName().' — '.$employee->job_title,
                    'search' => strtolower($employee->fullName().' '.$employee->job_title),
                    'name' => $employee->fullName(),
                ])->values()->all();

                return [$project->id => $employees];
            })->all();

        $allEmployeesForJs = Employee::all()->map(fn (Employee $employee): array => [
            'id' => $employee->id,
            'label' => $employee->fullName().' — '.$employee->job_title,
            'search' => strtolower($employee->fullName().' '.$employee->job_title),
            'name' => $employee->fullName(),
        ])->values()->all();

        $user = $request->user();
        $defaultProjectId = $user->assigned_project_id ?? $user->project_id ?? $user->assignedProject?->id ?? $projects->first()?->id;

        return view('site-officer.material-requests.create', [
            'projects' => $projects,
            'materials' => $materials,
            'materialsForJs' => $materialsForJs,
            'allMaterialsForJs' => $allMaterialsForJs,
            'employeesByProjectForJs' => $employeesByProjectForJs,
            'allEmployeesForJs' => $allEmployeesForJs,
            'defaultProjectId' => $defaultProjectId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'items' => ['required_without:material_id', 'array', 'min:1'],
            'items.*.material_id' => ['required_with:items', 'exists:materials,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'material_id' => ['nullable', 'exists:materials,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'employee_requests' => ['nullable', 'array'],
            'employee_requests.*.employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'employee_requests.*.employee_name' => ['nullable', 'string', 'max:255'],
            'employee_requests.*.quantity' => ['required_with:employee_requests', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $project = Project::findOrFail($validated['project_id']);

        abort_unless($project->isManagedBy($request->user()), 403);

        $employeeRows = collect($validated['employee_requests'] ?? [])
            ->filter(fn (array $row): bool => filled($row['employee_id'] ?? null) || filled($row['employee_name'] ?? null))
            ->values();

        $resolvedRows = collect();

        if ($employeeRows->isNotEmpty()) {
            // Check raw input for duplicate employee entries
            $seenInput = [];
            foreach ($employeeRows as $row) {
                $empId = filled($row['employee_id'] ?? null) ? (int) $row['employee_id'] : null;
                $empName = trim((string) ($row['employee_name'] ?? ''));

                if ($empId) {
                    $emp = Employee::find($empId);
                    $displayName = $emp ? $emp->fullName() : "Employee ID {$empId}";
                    $key = 'id_'.$empId;
                } else {
                    $displayName = $empName;
                    $key = 'name_'.strtolower($empName);
                }

                if (isset($seenInput[$key])) {
                    return Redirect::back()
                        ->withInput()
                        ->with('error', "Duplicate employee detected: {$displayName} has been added more than once in this request. Please remove duplicate entries.");
                }

                $seenInput[$key] = true;
            }

            $resolvedRows = $this->resolveEmployeeRows($project, $employeeRows);

            // Check resolved employee IDs for duplicates
            $seenResolved = [];
            foreach ($resolvedRows as $row) {
                $empId = $row['employee_id'];
                if (isset($seenResolved[$empId])) {
                    $emp = Employee::find($empId);
                    $displayName = $emp ? $emp->fullName() : 'Employee';

                    return Redirect::back()
                        ->withInput()
                        ->with('error', "Duplicate employee detected: {$displayName} has been added more than once in this request. Please remove duplicate entries.");
                }

                $seenResolved[$empId] = true;
            }
        }

        $itemsData = collect($validated['items'] ?? []);

        // Backward compatibility for single material requests
        if ($itemsData->isEmpty() && ! empty($validated['material_id'])) {
            $totalEmployeeQuantity = $resolvedRows->sum(fn (array $row): int => $row['quantity']);
            $defaultQty = $totalEmployeeQuantity > 0 ? $totalEmployeeQuantity : (int) ($request->input('quantity') ?: 1);
            $itemsData = collect([[
                'material_id' => (int) $validated['material_id'],
                'quantity' => $defaultQty,
            ]]);
        }

        $totalEmployeeQuantity = $resolvedRows->sum(fn (array $row): int => $row['quantity']);
        $totalItemsQuantity = (int) $itemsData->sum('quantity');
        $parentQuantity = $totalEmployeeQuantity > 0 ? $totalEmployeeQuantity : $totalItemsQuantity;
        $parentMaterialId = $itemsData->count() === 1 ? (int) $itemsData->first()['material_id'] : null;

        $materialRequest = DB::transaction(function () use ($validated, $project, $request, $parentMaterialId, $parentQuantity, $resolvedRows, $itemsData): MaterialRequest {
            $materialRequest = MaterialRequest::create([
                'material_id' => $parentMaterialId,
                'project_id' => $project->id,
                'requested_by' => $request->user()->id,
                'quantity' => $parentQuantity,
                'description' => $validated['description'] ?? null,
                'employee_file' => null,
                'status' => 'pending',
            ]);

            // Create material request items
            foreach ($itemsData as $item) {
                MaterialRequestItem::create([
                    'material_request_id' => $materialRequest->id,
                    'material_id' => $item['material_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            foreach ($resolvedRows as $row) {
                MaterialRequestEmployee::create([
                    'material_request_id' => $materialRequest->id,
                    'employee_id' => $row['employee_id'],
                    'quantity' => $row['quantity'],
                ]);
            }

            return $materialRequest;
        });

        // Build summary of requested materials for the notification
        $materialSummary = $itemsData->map(function (array $item): string {
            $material = Material::find($item['material_id']);

            return "{$item['quantity']} × ".($material?->material_name ?? 'Item');
        })->join(', ');

        app(WorkflowNotificationService::class)->notifyRoleUsers(
            ['HSE Officer'],
            new WorkflowNotification(
                category: 'material_request_created',
                title: 'Material request from site',
                message: "{$request->user()->name} requested [{$materialSummary}] for project {$project->project_code}. Review and approve.",
                actionUrl: route('hse-officer.material-requests.index'),
            ),
        );

        return Redirect::route('site-officer.material-requests.index')
            ->with('success', 'Material request submitted for approval successfully.');
    }

    public function index(Request $request): View
    {
        $projectIds = Project::query()
            ->where('site_officer_id', $request->user()->id)
            ->pluck('id');

        $requests = MaterialRequest::query()
            ->whereIn('project_id', $projectIds)
            ->with(['material', 'project', 'requester', 'approver', 'requestedEmployees', 'items.material'])
            ->latest()
            ->get();

        return view('site-officer.material-requests.index', [
            'requests' => $requests,
        ]);
    }

    public function show(Request $request, MaterialRequest $materialRequest): View
    {
        $projectIds = Project::query()
            ->where('site_officer_id', $request->user()->id)
            ->pluck('id');

        abort_unless($projectIds->contains($materialRequest->project_id), 403);

        $materialRequest->load(['material', 'project', 'requester', 'approver', 'requestedEmployees.employee', 'items.material']);

        return view('site-officer.material-requests.show', [
            'request' => $materialRequest,
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array{employee_id:int,quantity:int}>
     */
    private function resolveEmployeeRows(Project $project, Collection $rows): Collection
    {
        $projectEmployees = $project->employees()->get()->keyBy('id');

        return $rows->map(function (array $row) use ($project, &$projectEmployees): array {
            $employeeId = (isset($row['employee_id']) && $row['employee_id'] !== '') ? (int) $row['employee_id'] : null;
            $quantity = (int) $row['quantity'];

            if ($employeeId !== null) {
                $employee = Employee::find($employeeId);
                if (! $employee) {
                    throw ValidationException::withMessages([
                        'employee_requests' => 'Selected employee does not exist.',
                    ]);
                }

                $project->employees()->syncWithoutDetaching([$employee->id]);

                return [
                    'employee_id' => $employeeId,
                    'quantity' => $quantity,
                ];
            }

            $employeeName = trim((string) ($row['employee_name'] ?? ''));
            if ($employeeName === '') {
                throw ValidationException::withMessages([
                    'employee_requests' => 'Employee name is required for new employees.',
                ]);
            }

            // Check if there is an employee in the system with this name (case-insensitive)
            $nameConcat = DB::connection()->getDriverName() === 'sqlite'
                ? "first_name || ' ' || last_name"
                : "CONCAT(first_name, ' ', last_name)";

            $employee = Employee::where('name', $employeeName)
                ->orWhereRaw("{$nameConcat} = ?", [$employeeName])
                ->first();

            if (! $employee) {
                [$firstName, $lastName] = $this->splitName($employeeName);

                $employee = Employee::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'gender' => 'Not specified',
                    'job_title' => 'Worker',
                ]);
            }

            $project->employees()->syncWithoutDetaching([$employee->id]);
            $projectEmployees->put($employee->id, $employee);

            return [
                'employee_id' => $employee->id,
                'quantity' => $quantity,
            ];
        });
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (count($parts) < 2) {
            return [$name, 'Unknown'];
        }

        $firstName = array_shift($parts);
        $lastName = implode(' ', $parts);

        return [$firstName ?: $name, $lastName ?: 'Unknown'];
    }
}
