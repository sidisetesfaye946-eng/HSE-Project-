<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialEmployeeAssignment;
use App\Models\Project;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeSpreadsheetService
{
    /**
     * Primary import template columns (v2 - includes job_title).
     *
     * @var list<string>
     */
    public const HEADERS = ['employee_name', 'job_title', 'project', 'material_type', 'date'];

    /**
     * Legacy template columns (first_name / last_name / gender / job_title).
     *
     * @var list<string>
     */
    public const LEGACY_HEADERS = ['first_name', 'last_name', 'gender', 'job_title'];

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Employees');

        $sheet->fromArray([self::HEADERS], null, 'A1');
        $sheet->fromArray([
            ['John Doe', 'Safety Officer', 'Alpha Project', 'Safety Helmet', date('Y-m-d')],
        ], null, 'A2');

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="employees_import_template.xls"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * @return array{imported: int, errors: list<string>}
     */
    public function import(UploadedFile $file): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray();
        } catch (\Throwable $e) {
            return ['imported' => 0, 'errors' => ['Unable to parse file: '.$e->getMessage()]];
        }

        if ($rows === []) {
            return ['imported' => 0, 'errors' => ['The file is empty.']];
        }

        $rawHeaderRow = $rows[0] ?? [];
        $headerMap = $this->buildHeaderMap($rawHeaderRow);

        $isLegacy = $this->isLegacyHeaderMap($headerMap);
        $isHistory = $this->isHistoryHeaderMap($headerMap);

        if (! $isLegacy && ! $isHistory) {
            return [
                'imported' => 0,
                'errors' => ['Invalid template. Download the template and use columns: employee_name, job_title, project, material_type, date.'],
            ];
        }

        $imported = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $rowNumber = $index + 2;

            try {
                if ($isHistory) {
                    $result = $this->importHistoryRowMap($row, $headerMap, $rowNumber);
                } else {
                    $result = $this->importLegacyRowMap($row, $headerMap, $rowNumber);
                }
            } catch (\Throwable $e) {
                $result = 'Row '.$rowNumber.': '.$e->getMessage();
            }

            if ($result === true) {
                $imported++;
            } elseif (is_string($result)) {
                $errors[] = $result;
            }
            // null => empty row, skip silently
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Map raw header columns to canonical internal keys (case & space insensitive).
     *
     * @param  array<int, mixed>  $rawHeaderRow
     * @return array<int, string> Index to canonical key map
     */
    private function buildHeaderMap(array $rawHeaderRow): array
    {
        $map = [];
        foreach ($rawHeaderRow as $colIndex => $value) {
            $normalized = $this->normalizeHeader((string) $value);
            if ($normalized !== '') {
                $map[$colIndex] = $normalized;
            }
        }

        return $map;
    }

    /**
     * Normalize individual header string to standard internal key.
     */
    private function normalizeHeader(string $header): string
    {
        $clean = strtolower(trim($header));
        $clean = preg_replace('/[\\s_\\-]+/', '_', $clean) ?? $clean;

        return match ($clean) {
            'employee_name', 'employee', 'name', 'employee_full_name', 'full_name', 'staff_name', 'emp_name' => 'employee_name',
            'job_title', 'title', 'position', 'role', 'job', 'designation' => 'job_title',
            'project', 'project_name', 'site', 'project_site' => 'project',
            'material_type', 'material', 'material_name', 'equipment', 'item' => 'material_type',
            'date', 'assigned_date', 'issue_date', 'date_assigned' => 'date',
            'first_name', 'firstname' => 'first_name',
            'last_name', 'lastname' => 'last_name',
            'gender', 'sex' => 'gender',
            default => $clean,
        };
    }

    private function isLegacyHeaderMap(array $headerMap): bool
    {
        $keys = array_values($headerMap);

        return in_array('first_name', $keys, true) && in_array('last_name', $keys, true);
    }

    private function isHistoryHeaderMap(array $headerMap): bool
    {
        $keys = array_values($headerMap);

        return in_array('employee_name', $keys, true)
            || (in_array('project', $keys, true) && in_array('material_type', $keys, true) && in_array('date', $keys, true));
    }

    /**
     * Import one row from the history template using header map.
     */
    private function importHistoryRowMap(array $row, array $headerMap, int $rowNumber): bool|string|null
    {
        $dataByKey = [];
        foreach ($headerMap as $colIndex => $canonicalKey) {
            $dataByKey[$canonicalKey] = trim((string) ($row[$colIndex] ?? ''));
        }

        $name = $dataByKey['employee_name'] ?? '';
        $titleStr = $dataByKey['job_title'] ?? '';
        $projectStr = $dataByKey['project'] ?? '';
        $materialStr = $dataByKey['material_type'] ?? '';
        $dateRaw = $dataByKey['date'] ?? '';

        if ($name === '' && isset($row[0])) {
            $name = trim((string) $row[0]);
        }

        // Skip completely blank rows
        if ($name === '' && $projectStr === '' && $materialStr === '' && $dateRaw === '') {
            return null;
        }

        $validator = Validator::make([
            'employee_name' => $name,
            'project' => $projectStr,
            'material_type' => $materialStr,
            'date' => $dateRaw,
        ], [
            'employee_name' => ['required', 'string', 'max:255'],
            'project' => ['required', 'string', 'max:255'],
            'material_type' => ['required', 'string', 'max:255'],
            'date' => ['required'],
        ]);

        if ($validator->fails()) {
            return 'Row '.$rowNumber.': '.implode(' ', $validator->errors()->all());
        }

        $parts = explode(' ', $name, 2);
        $firstName = $parts[0] ?? $name;
        $lastName = $parts[1] ?? '';

        $projectObj = Project::where('project_name', $projectStr)->orWhere('id', $projectStr)->first();
        if (! $projectObj && filled($projectStr)) {
            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $projectStr), 0, 8)) ?: 'PRJ';
            $projectObj = Project::create([
                'project_name' => $projectStr,
                'project_code' => $code,
            ]);
        }

        $materialObj = Material::where('material_name', $materialStr)->orWhere('id', $materialStr)->first();
        if (! $materialObj && filled($materialStr)) {
            $materialObj = Material::create([
                'material_name' => $materialStr,
                'material_description' => $materialStr,
                'quantity' => 0,
            ]);
        }

        $formattedDate = $this->parseDate($dateRaw);

        $employee = Employee::updateOrCreate(
            ['name' => $name],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => 'Other',
                'job_title' => $titleStr !== '' ? $titleStr : 'Staff',
                'project_id' => $projectObj?->id,
                'project' => $projectStr,
                'material_id' => $materialObj?->id,
                'material_type' => $materialStr,
                'date' => $formattedDate,
            ]
        );

        if ($projectObj && $materialObj) {
            MaterialEmployeeAssignment::firstOrCreate(
                [
                    'material_id' => $materialObj->id,
                    'project_id' => $projectObj->id,
                    'employee_id' => $employee->id,
                    'assigned_date' => $formattedDate,
                ],
                [
                    'quantity' => 1,
                    'assigned_by' => auth()->id() ?? 1,
                ]
            );
        }

        if ($projectObj) {
            $employee->projects()->syncWithoutDetaching([$projectObj->id]);
        }

        return true;
    }

    /**
     * Import one row from the legacy template using header map.
     */
    private function importLegacyRowMap(array $row, array $headerMap, int $rowNumber): bool|string|null
    {
        $dataByKey = [];
        foreach ($headerMap as $colIndex => $canonicalKey) {
            $dataByKey[$canonicalKey] = trim((string) ($row[$colIndex] ?? ''));
        }

        $data = [
            'first_name' => $dataByKey['first_name'] ?? trim((string) ($row[0] ?? '')),
            'last_name' => $dataByKey['last_name'] ?? trim((string) ($row[1] ?? '')),
            'gender' => $dataByKey['gender'] ?? trim((string) ($row[2] ?? '')),
            'job_title' => $dataByKey['job_title'] ?? trim((string) ($row[3] ?? '')),
        ];

        if ($data['first_name'] === '' && $data['last_name'] === '' && $data['gender'] === '' && $data['job_title'] === '') {
            return null;
        }

        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string'],
            'job_title' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return 'Row '.$rowNumber.': '.implode(' ', $validator->errors()->all());
        }

        $fullName = trim($data['first_name'].' '.$data['last_name']);

        Employee::updateOrCreate(
            ['name' => $fullName],
            [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'name' => $fullName,
                'gender' => $data['gender'],
                'job_title' => $data['job_title'],
            ]
        );

        return true;
    }

    /**
     * Parse a date value robustly (handles strings like YYYY-MM-DD, MM/DD/YYYY, DD/MM/YYYY, YYYY/MM/DD, and Excel serial date numbers).
     */
    public function parseDate(mixed $dateVal): string
    {
        if ($dateVal === null || $dateVal === '') {
            return now()->toDateString();
        }

        // Handle Excel serial date integers/floats (e.g. 45861)
        if (is_numeric($dateVal) && (float) $dateVal > 1000 && (float) $dateVal < 100000) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $dateVal);

                return Carbon::instance($dt)->toDateString();
            } catch (\Throwable) {
                // fallback to string parsing below
            }
        }

        $dateStr = trim((string) $dateVal);

        if ($dateStr === '') {
            return now()->toDateString();
        }

        // Try standard explicit formats
        $formats = [
            'Y-m-d',
            'Y/m/d',
            'm/d/Y',
            'd/m/Y',
            'n/j/Y',
            'j/n/Y',
            'm-d-Y',
            'd-m-Y',
            'Y.m.d',
            'd.m.Y',
            'm.d.Y',
            'M d, Y',
            'd M Y',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat('!'.$format, $dateStr);
                if ($parsed !== false) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable) {
                // continue to next format
            }
        }

        // General fallback to Carbon::parse
        try {
            return Carbon::parse($dateStr)->toDateString();
        } catch (InvalidFormatException|\Throwable) {
            return now()->toDateString();
        }
    }
}
