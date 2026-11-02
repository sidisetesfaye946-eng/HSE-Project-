<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteOfficerEmployeeReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $projects = Project::query()
            ->when(! $user->hasAnyRole(['Admin', 'HSE Officer']), function ($query) use ($user) {
                return $query->where(function ($q) use ($user): void {
                    $q->where('site_officer_id', $user->id)
                        ->orWhere('id', $user->assigned_project_id);
                });
            })
            ->orderBy('project_name')
            ->get();

        $hasFilter = $request->filled('search')
            || $request->filled('project_id')
            || $request->filled('quarter')
            || $request->filled('date')
            || $request->filled('from_date')
            || $request->filled('to_date');

        $query = $this->buildEmployeeReportQuery($request, $projects);

        return view('site-officer.reports.employees', [
            'employees' => $query->paginate(10)->withQueryString(),
            'projects' => $projects,
            'hasFilter' => $hasFilter,
        ]);
    }

    public function export(Request $request): Response
    {
        $user = $request->user();
        $projects = Project::query()
            ->when(! $user->hasAnyRole(['Admin', 'HSE Officer']), function ($query) use ($user) {
                return $query->where(function ($q) use ($user): void {
                    $q->where('site_officer_id', $user->id)
                        ->orWhere('id', $user->assigned_project_id);
                });
            })
            ->orderBy('project_name')
            ->get();

        $employees = $this->buildEmployeeReportQuery($request, $projects)->get();
        $format = strtolower($request->query('format', 'xlsx'));

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('site-officer.reports.exports.employees-pdf', [
                'employees' => $employees,
                'title' => 'Employees Report',
            ]);

            return $pdf->download('employees_report.pdf');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Employees Report');

        $sheet->fromArray([
            ['Employee Name', 'Title', 'Projects', 'Material', 'Date'],
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
            'Content-Disposition' => 'attachment; filename="employees_report.xls"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function buildEmployeeReportQuery(Request $request, $projects)
    {
        $user = $request->user();

        $query = Employee::with([
            'projectRelation',
            'materialRelation',
            'materialAssignments.project',
            'materialAssignments.material',
            'projects',
        ])->latest();

        if (! $user->hasAnyRole(['Admin', 'HSE Officer'])) {
            $projectIds = $projects->pluck('id')->filter()->unique()->values()->all();
            $projectNames = $projects->pluck('project_name')->filter()->unique()->values()->all();

            $query->where(function ($q) use ($projectIds, $projectNames): void {
                $q->whereIn('project_id', $projectIds)
                    ->orWhereIn('project', $projectNames)
                    ->orWhereHas('projects', function ($pq) use ($projectIds): void {
                        $pq->whereIn('projects.id', $projectIds);
                    })
                    ->orWhereHas('materialAssignments', function ($mq) use ($projectIds): void {
                        $mq->whereIn('project_id', $projectIds);
                    });
            });
        }

        $search = $request->string('search')->trim()->toString();
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%")
                    ->orWhere('project', 'like', "%{$search}%")
                    ->orWhere('material_type', 'like', "%{$search}%")
                    ->orWhereHas('projects', function ($pq) use ($search): void {
                        $pq->where('project_name', 'like', "%{$search}%")
                            ->orWhere('project_code', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('project_id')) {
            $selectedProjectId = $request->input('project_id');
            $query->where(function ($q) use ($selectedProjectId): void {
                $q->where('project_id', $selectedProjectId)
                    ->orWhereHas('projects', function ($pq) use ($selectedProjectId): void {
                        $pq->where('projects.id', $selectedProjectId);
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
}
