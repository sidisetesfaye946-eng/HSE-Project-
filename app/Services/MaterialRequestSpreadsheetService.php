<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestEmployee;
use App\Models\MaterialRequestItem;
use App\Models\Project;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialRequestSpreadsheetService
{
    /**
     * @var list<string>
     */
    public const HEADERS = ['Employee-Name', 'Title', 'Project'];

    public function __construct(private WorkflowNotificationService $notificationService) {}

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Material Requests');

        $sheet->fromArray([self::HEADERS], null, 'A1');
        $sheet->fromArray([
            ['John Doe', 'Technician', 'Site Alpha'],
            ['Jane Smith', 'Engineer', 'Site Alpha'],
        ], null, 'A2');

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="material_requests_template.xls"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function import(
        ?UploadedFile $file,
        Project $project,
        User $user,
        array|int $itemsOrMaterialId,
        int|string|null $quantityOrDescription = null,
        ?int $quantityPerEmployee = null,
        ?string $description = null,
        bool $isLegacy = false
    ): array {
        if (is_array($itemsOrMaterialId)) {
            $items = $itemsOrMaterialId;
            $description = is_string($quantityOrDescription) ? $quantityOrDescription : $description;
            $quantityPerEmployee = is_int($quantityOrDescription) ? $quantityOrDescription : $quantityPerEmployee;
        } else {
            $materialId = (int) $itemsOrMaterialId;
            $quantity = (int) $quantityOrDescription;
            $items = [['material_id' => $materialId, 'quantity' => $quantity]];
            $isLegacy = true;
        }

        if ($file === null) {
            $importedCount = 0;
            DB::transaction(function () use ($project, $user, $items, $description, &$importedCount): void {
                $totalQuantity = (int) collect($items)->sum('quantity');
                $parentMaterialId = count($items) === 1 ? (int) $items[0]['material_id'] : null;

                $materialRequest = MaterialRequest::create([
                    'material_id' => $parentMaterialId,
                    'project_id' => $project->id,
                    'requested_by' => $user->id,
                    'quantity' => $totalQuantity,
                    'description' => $description ?? 'Requested directly (no employee list).',
                    'employee_file' => null,
                    'status' => 'pending',
                ]);

                foreach ($items as $item) {
                    MaterialRequestItem::create([
                        'material_request_id' => $materialRequest->id,
                        'material_id' => (int) $item['material_id'],
                        'quantity' => (int) $item['quantity'],
                    ]);
                }

                $materialSummary = collect($items)->map(function (array $item): string {
                    $material = Material::find($item['material_id']);

                    return "{$item['quantity']} × ".($material?->material_name ?? 'Item');
                })->join(', ');

                $this->notificationService->notifyRoleUsers(
                    ['HSE Officer'],
                    new WorkflowNotification(
                        category: 'material_request_created',
                        title: 'Material request from site',
                        message: "{$user->name} requested [{$materialSummary}] for project {$project->project_code}. Review and approve.",
                        actionUrl: route('hse-officer.material-requests.index'),
                    ),
                );

                $importedCount = 1;
            });

            return ['imported' => $importedCount, 'errors' => []];
        }

        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        if ($rows === []) {
            return ['imported' => 0, 'errors' => ['The file is empty.']];
        }

        $headerRow = array_map(
            fn ($value): string => strtolower(trim((string) $value)),
            $rows[0] ?? []
        );

        if (! $this->headersAreValid($headerRow)) {
            return [
                'imported' => 0,
                'errors' => ['Invalid template. Download the template and use columns: Employee-Name, Title, Project.'],
            ];
        }

        foreach ($items as $item) {
            $material = Material::find($item['material_id'] ?? null);
            if ($material === null) {
                return ['imported' => 0, 'errors' => ['Selected material not found in the system.']];
            }
        }

        $parsedRows = [];
        $errors = [];
        $projectEmployees = $project->employees()->get()->keyBy('id');

        foreach (array_slice($rows, 1) as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->mapRowToData($row);

            if ($this->rowIsEmpty($data)) {
                continue;
            }

            $validator = Validator::make($data, [
                'employee_name' => ['required', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                $errors[] = 'Row '.$rowNumber.': '.implode(' ', $validator->errors()->all());

                continue;
            }

            $parsedRows[] = [
                'employee_name' => trim($data['employee_name']),
                'title' => trim($data['title']),
                'project' => trim($data['project']),
            ];
        }

        if ($errors !== []) {
            return ['imported' => 0, 'errors' => $errors];
        }

        if ($parsedRows === []) {
            return ['imported' => 0, 'errors' => ['No valid employee records found in the file.']];
        }

        // Check for duplicate employee entries in uploaded file
        $seenNames = [];
        foreach ($parsedRows as $item) {
            $name = $item['employee_name'];
            $normalized = strtolower(trim($name));
            if (isset($seenNames[$normalized])) {
                $duplicateName = $seenNames[$normalized];

                return [
                    'imported' => 0,
                    'errors' => ["Duplicate employee detected: {$duplicateName} has been added more than once in this request. Please remove duplicate entries."],
                ];
            }
            $seenNames[$normalized] = trim($name);
        }

        $importedCount = 0;

        DB::transaction(function () use ($parsedRows, $project, $user, $items, $quantityPerEmployee, $isLegacy, $description, &$projectEmployees, &$importedCount): void {
            $resolvedEmployees = [];

            foreach ($parsedRows as $rowItem) {
                $employeeName = $rowItem['employee_name'];
                $jobTitle = $rowItem['title'] !== '' ? $rowItem['title'] : 'Worker';

                // Look up employee in project
                $employee = $projectEmployees->first(function (Employee $emp) use ($employeeName): bool {
                    return strcasecmp($emp->fullName(), $employeeName) === 0;
                });

                if (! $employee) {
                    [$firstName, $lastName] = $this->splitName($employeeName);

                    $employee = Employee::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'gender' => 'Not specified',
                        'job_title' => $jobTitle,
                    ]);

                    $project->employees()->syncWithoutDetaching([$employee->id]);
                    $projectEmployees->put($employee->id, $employee);
                }

                $resolvedEmployees[] = $employee->id;
            }

            $itemsToSave = $items;
            if ($isLegacy && $quantityPerEmployee !== null && count($itemsToSave) === 1) {
                $totalQuantity = count($resolvedEmployees) * $quantityPerEmployee;
                $itemsToSave[0]['quantity'] = $totalQuantity;
                $empQuantity = $quantityPerEmployee;
            } else {
                $totalQuantity = (int) collect($itemsToSave)->sum('quantity');
                $empQuantity = $quantityPerEmployee ?? 1;
            }

            $parentMaterialId = count($itemsToSave) === 1 ? (int) $itemsToSave[0]['material_id'] : null;

            $materialRequest = MaterialRequest::create([
                'material_id' => $parentMaterialId,
                'project_id' => $project->id,
                'requested_by' => $user->id,
                'quantity' => $totalQuantity,
                'description' => $description ?? 'Imported from employee list spreadsheet.',
                'employee_file' => null,
                'status' => 'pending',
            ]);

            foreach ($itemsToSave as $item) {
                MaterialRequestItem::create([
                    'material_request_id' => $materialRequest->id,
                    'material_id' => (int) $item['material_id'],
                    'quantity' => (int) $item['quantity'],
                ]);
            }

            foreach ($resolvedEmployees as $employeeId) {
                MaterialRequestEmployee::create([
                    'material_request_id' => $materialRequest->id,
                    'employee_id' => $employeeId,
                    'quantity' => $empQuantity,
                ]);
            }

            $materialSummary = collect($itemsToSave)->map(function (array $item): string {
                $material = Material::find($item['material_id']);

                return "{$item['quantity']} × ".($material?->material_name ?? 'Item');
            })->join(', ');

            app(WorkflowNotificationService::class)->notifyRoleUsers(
                ['HSE Officer'],
                new WorkflowNotification(
                    category: 'material_request_created',
                    title: 'Material request from site',
                    message: "{$user->name} requested [{$materialSummary}] for project {$project->project_code}. Review and approve.",
                    actionUrl: route('hse-officer.material-requests.index'),
                ),
            );

            $importedCount = count($parsedRows);
        });

        return ['imported' => $importedCount, 'errors' => []];
    }

    /**
     * @param  list<mixed>  $headerRow
     */
    private function headersAreValid(array $headerRow): bool
    {
        if ($headerRow === []) {
            return false;
        }

        $col0 = strtolower(trim((string) ($headerRow[0] ?? '')));
        $col0 = str_replace(['-', '_'], ' ', $col0);

        return in_array($col0, ['employee name', 'employeename', 'employee', 'employee-name'], true);
    }

    /**
     * @param  list<mixed>  $row
     * @return array{employee_name: string, title: string, project: string}
     */
    private function mapRowToData(array $row): array
    {
        return [
            'employee_name' => trim((string) ($row[0] ?? '')),
            'title' => trim((string) ($row[1] ?? '')),
            'project' => trim((string) ($row[2] ?? '')),
        ];
    }

    /**
     * @param  array{employee_name: string, title: string, project: string}  $data
     */
    private function rowIsEmpty(array $data): bool
    {
        return $data['employee_name'] === '' && $data['title'] === '' && $data['project'] === '';
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
