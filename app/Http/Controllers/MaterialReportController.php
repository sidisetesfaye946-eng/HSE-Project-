<?php

namespace App\Http\Controllers;

use App\MaterialInventoryReportService;
use App\Models\Material;
use App\Models\MaterialEmployeeAssignment;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialReportController extends Controller
{
    public function __construct(private MaterialInventoryReportService $inventoryReport) {}

    public function inventory(Request $request): View
    {
        $report = $this->inventoryReport->forHeadOffice(
            search: $request->get('search'),
            fromDate: $request->get('from_date'),
            toDate: $request->get('to_date'),
            quarter: $request->get('quarter'),
            projectId: $request->get('project_id'),
        );

        $projects = Project::query()
            ->orderBy('project_name')
            ->get();

        return view('material-reports.inventory', [
            'summary' => $report['summary'],
            'materials' => $report['materials'],
            'projects' => $projects,
        ]);
    }

    public function index(): View
    {
        $projects = Project::with('siteOfficer')
            ->orderBy('project_name')
            ->get();

        $balancesByProject = $this->balancesGroupedByProject(
            MaterialProjectAssignment::query()
                ->with(['material', 'receiver'])
                ->get()
        );

        return view('material-reports.index', [
            'projects' => $projects,
            'balancesByProject' => $balancesByProject,
        ]);
    }

    public function show(Project $project): View
    {
        $project->load('siteOfficer');

        $balances = MaterialProjectAssignment::query()
            ->where('project_id', $project->id)
            ->with(['material', 'receiver', 'assignedBy'])
            ->latest()
            ->get()
            ->groupBy('material_id')
            ->map(function (Collection $assignments): array {
                $first = $assignments->first();

                return [
                    'material' => $first->material,
                    'total_quantity' => $assignments->sum('quantity'),
                    'assignments' => $assignments,
                ];
            })
            ->values();

        return view('material-reports.show', [
            'project' => $project,
            'balances' => $balances,
        ]);
    }

    public function balance(Request $request): View
    {
        $materials = $this->getMaterialBalances();

        // If a specific material is requested, get its breakdown
        $selectedMaterial = null;
        $selectedMaterialBreakdown = null;

        if ($request->get('material_id')) {
            $selectedMaterial = Material::find($request->get('material_id'));
            if ($selectedMaterial) {
                $selectedMaterialBreakdown = $this->getMaterialBreakdown($selectedMaterial);
            }
        }

        return view('material-reports.balance', [
            'materials' => $materials,
            'selectedMaterial' => $selectedMaterial,
            'selectedMaterialBreakdown' => $selectedMaterialBreakdown,
        ]);
    }

    public function headOfficeReport(Request $request): View
    {
        // Build the sub-query for filtered distributed totals per material
        $distributedSubQuery = DB::table('material_project_assignments')
            ->selectRaw('material_id, SUM(quantity) as total_distributed')
            ->groupBy('material_id');

        if ($request->filled('from_date')) {
            $distributedSubQuery->whereDate('created_at', '>=', $request->get('from_date'));
        }
        if ($request->filled('to_date')) {
            $distributedSubQuery->whereDate('created_at', '<=', $request->get('to_date'));
        }
        if ($request->filled('quarter')) {
            $quarter = (int) $request->get('quarter');
            $distributedSubQuery
                ->whereMonth('created_at', '>=', (($quarter - 1) * 3) + 1)
                ->whereMonth('created_at', '<=', $quarter * 3);
        }
        if ($request->filled('project_id')) {
            $distributedSubQuery->where('project_id', $request->get('project_id'));
        }

        // Main query: GROUP BY at SQL level so paginate() counts grouped material rows
        $query = DB::table('materials')
            ->leftJoinSub($distributedSubQuery, 'dist', 'materials.id', '=', 'dist.material_id')
            ->leftJoin('unit_of_measures', 'materials.unit_of_measure_id', '=', 'unit_of_measures.id')
            ->select(
                'materials.id',
                'materials.material_name',
                'unit_of_measures.name as unit_of_measure',
                DB::raw('COALESCE(materials.initial_stock, 0) as initial_stock'),
                DB::raw('COALESCE(dist.total_distributed, 0) as total_distributed'),
                DB::raw('CASE WHEN (COALESCE(materials.initial_stock, 0) - COALESCE(dist.total_distributed, 0)) > 0 THEN (COALESCE(materials.initial_stock, 0) - COALESCE(dist.total_distributed, 0)) ELSE 0 END as physical_available')
            )
            ->orderBy('materials.material_name');

        if ($request->filled('search')) {
            $query->where('materials.material_name', 'like', '%'.$request->get('search').'%');
        }

        $materials = $query->paginate(15)->withQueryString();

        $projects = Project::orderBy('project_name')->get();

        return view('material-reports.head-office', [
            'materials' => $materials,
            'projects' => $projects,
        ]);
    }

    public function siteReport(Request $request): View
    {
        $projects = Project::with('siteOfficer')
            ->orderBy('project_name')
            ->get();

        if ($request->filled('project_id')) {
            $projects = $projects->where('id', $request->get('project_id'));
        }

        // Collect raw rows per project × material, then aggregate to avoid duplicate material rows
        $rawRows = [];

        foreach ($projects as $project) {
            // Get unique material IDs assigned to this project (with optional filters)
            $materialIdsQuery = MaterialProjectAssignment::query()
                ->where('project_id', $project->id)
                ->select('material_id');

            if ($request->filled('from_date')) {
                $materialIdsQuery->whereDate('created_at', '>=', $request->get('from_date'));
            }
            if ($request->filled('to_date')) {
                $materialIdsQuery->whereDate('created_at', '<=', $request->get('to_date'));
            }
            if ($request->filled('quarter')) {
                $quarter = (int) $request->get('quarter');
                $materialIdsQuery
                    ->whereMonth('created_at', '>=', (($quarter - 1) * 3) + 1)
                    ->whereMonth('created_at', '<=', $quarter * 3);
            }

            $materialIds = $materialIdsQuery->distinct()->pluck('material_id');

            foreach ($materialIds as $materialId) {
                $material = Material::with('unitOfMeasure')->find($materialId);
                if (! $material) {
                    continue;
                }

                if ($request->filled('search')) {
                    $search = strtolower($request->get('search'));
                    if (strpos(strtolower($material->material_name), $search) === false) {
                        continue;
                    }
                }

                // Sum all quantities assigned to this project for this material
                $assignedSumQuery = MaterialProjectAssignment::query()
                    ->where('project_id', $project->id)
                    ->where('material_id', $materialId);

                if ($request->filled('from_date')) {
                    $assignedSumQuery->whereDate('created_at', '>=', $request->get('from_date'));
                }
                if ($request->filled('to_date')) {
                    $assignedSumQuery->whereDate('created_at', '<=', $request->get('to_date'));
                }
                if ($request->filled('quarter')) {
                    $quarter = (int) $request->get('quarter');
                    $assignedSumQuery
                        ->whereMonth('created_at', '>=', (($quarter - 1) * 3) + 1)
                        ->whereMonth('created_at', '<=', $quarter * 3);
                }

                $totalAssigned = (int) $assignedSumQuery->sum('quantity');

                // Sum employee distributions for this project + material
                $employeeDistributedQuery = MaterialEmployeeAssignment::query()
                    ->where('project_id', $project->id)
                    ->where('material_id', $materialId);

                if ($request->filled('from_date')) {
                    $employeeDistributedQuery->whereDate('assigned_date', '>=', $request->get('from_date'));
                }
                if ($request->filled('to_date')) {
                    $employeeDistributedQuery->whereDate('assigned_date', '<=', $request->get('to_date'));
                }
                if ($request->filled('quarter')) {
                    $quarter = (int) $request->get('quarter');
                    $employeeDistributedQuery
                        ->whereMonth('assigned_date', '>=', (($quarter - 1) * 3) + 1)
                        ->whereMonth('assigned_date', '<=', $quarter * 3);
                }

                $totalDistributed = (int) $employeeDistributedQuery->sum('quantity');

                $rawRows[] = [
                    'material_name' => $material->material_name,
                    'unit_of_measure' => $material->unitOfMeasure?->name ?? '—',
                    'project_name' => $project->project_name,
                    'project_code' => $project->project_code,
                    'assigned_count' => $totalAssigned,
                    'distributed_to_employee' => $totalDistributed,
                    'physical_available' => max(0, $totalAssigned - $totalDistributed),
                ];
            }
        }

        // Deduplicate: group by material_name + project_name and sum quantities
        $reportData = collect($rawRows)
            ->groupBy(fn ($row) => $row['material_name'].'|'.$row['project_name'])
            ->map(function ($group) {
                $first = $group->first();
                $totalAssigned = $group->sum('assigned_count');
                $totalDistributed = $group->sum('distributed_to_employee');

                return [
                    'material_name' => $first['material_name'],
                    'unit_of_measure' => $first['unit_of_measure'],
                    'project_name' => $first['project_name'],
                    'project_code' => $first['project_code'],
                    'assigned_count' => $totalAssigned,
                    'distributed_to_employee' => $totalDistributed,
                    'physical_available' => max(0, $totalAssigned - $totalDistributed),
                ];
            })
            ->values()
            ->toArray();

        $page = $request->get('page', 1);
        $perPage = 15;
        $total = count($reportData);
        $items = array_slice($reportData, ($page - 1) * $perPage, $perPage);
        $materials = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->except('page'),
            ]
        );

        $projects = Project::orderBy('project_name')->get();

        return view('material-reports.site-materials', [
            'materials' => $materials,
            'projects' => $projects,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getMaterialBalances(): array
    {
        $materials = Material::with(['unitOfMeasure', 'assignments'])
            ->orderBy('material_name')
            ->get();

        return $materials->map(function ($material) {
            $projectAssignments = MaterialProjectAssignment::query()
                ->where('material_id', $material->id)
                ->sum('quantity');

            $employeeAssignments = MaterialEmployeeAssignment::query()
                ->where('material_id', $material->id)
                ->sum('quantity');

            $headOfficeBalance = $material->quantity;
            $siteBalance = max(0, $projectAssignments - $employeeAssignments);
            $totalBalance = $headOfficeBalance + $siteBalance;

            return [
                'id' => $material->id,
                'name' => $material->material_name,
                'description' => $material->material_description,
                'unit_of_measure' => $material->unitOfMeasure?->name,
                'head_office_balance' => $headOfficeBalance,
                'site_balance' => $siteBalance,
                'total_balance' => $totalBalance,
                'assigned_to_projects' => $projectAssignments,
                'assigned_to_employees' => $employeeAssignments,
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function getMaterialBreakdown(Material $material): array
    {
        $projects = Project::with('siteOfficer')
            ->orderBy('project_name')
            ->get();

        $projectBreakdown = $projects->map(function ($project) use ($material) {
            $projectAssigned = MaterialProjectAssignment::query()
                ->where('material_id', $material->id)
                ->where('project_id', $project->id)
                ->sum('quantity');

            $employeeAssigned = MaterialEmployeeAssignment::query()
                ->where('material_id', $material->id)
                ->where('project_id', $project->id)
                ->sum('quantity');

            if ($projectAssigned === 0) {
                return null;
            }

            return [
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'project_code' => $project->project_code,
                'site_officer' => $project->siteOfficer?->name,
                'assigned_to_project' => $projectAssigned,
                'assigned_to_employees' => $employeeAssigned,
                'available_balance' => max(0, $projectAssigned - $employeeAssigned),
            ];
        })->filter()->values();

        $totalProjectAssigned = MaterialProjectAssignment::query()
            ->where('material_id', $material->id)
            ->sum('quantity');

        $totalEmployeeAssigned = MaterialEmployeeAssignment::query()
            ->where('material_id', $material->id)
            ->sum('quantity');

        return [
            'material_id' => $material->id,
            'material_name' => $material->material_name,
            'material_description' => $material->material_description,
            'unit_of_measure' => $material->unitOfMeasure?->name,
            'head_office_balance' => $material->quantity,
            'total_assigned_to_projects' => $totalProjectAssigned,
            'total_assigned_to_employees' => $totalEmployeeAssigned,
            'total_site_balance' => max(0, $totalProjectAssigned - $totalEmployeeAssigned),
            'projects' => $projectBreakdown->all(),
        ];
    }

    /**
     * @param  Collection<int, MaterialProjectAssignment>  $assignments
     * @return array<int, Collection<int, array{material: Material, total_quantity: int}>>
     */
    private function balancesGroupedByProject(Collection $assignments): array
    {
        return $assignments
            ->groupBy('project_id')
            ->map(function (Collection $projectAssignments): Collection {
                return $projectAssignments
                    ->groupBy('material_id')
                    ->map(function (Collection $materialAssignments): array {
                        $first = $materialAssignments->first();

                        return [
                            'material' => $first->material,
                            'total_quantity' => $materialAssignments->sum('quantity'),
                        ];
                    })
                    ->values();
            })
            ->all();
    }

    public function exportHeadOffice(Request $request): Response
    {
        $format = strtolower($request->query('format', 'xlsx'));

        // Pre-aggregate distributed quantities per material via SQL GROUP BY
        $distributedQuery = MaterialProjectAssignment::query()
            ->selectRaw('material_id, SUM(quantity) as total_distributed')
            ->groupBy('material_id');

        if ($request->filled('from_date')) {
            $distributedQuery->whereDate('created_at', '>=', $request->get('from_date'));
        }
        if ($request->filled('to_date')) {
            $distributedQuery->whereDate('created_at', '<=', $request->get('to_date'));
        }
        if ($request->filled('quarter')) {
            $quarter = (int) $request->get('quarter');
            $distributedQuery
                ->whereMonth('created_at', '>=', (($quarter - 1) * 3) + 1)
                ->whereMonth('created_at', '<=', $quarter * 3);
        }
        if ($request->filled('project_id')) {
            $distributedQuery->where('project_id', $request->get('project_id'));
        }

        $distributedByMaterial = $distributedQuery->pluck('total_distributed', 'material_id')
            ->map(fn ($v): int => (int) $v);

        $materialQuery = Material::with('unitOfMeasure')->orderBy('material_name');

        if ($request->filled('search')) {
            $materialQuery->where('material_name', 'like', '%'.$request->get('search').'%');
        }

        $materials = $materialQuery->get()
            ->map(function (Material $material) use ($distributedByMaterial): array {
                $initialStock = (int) $material->initial_stock;
                $totalDistributed = $distributedByMaterial->get($material->id, 0);

                return [
                    'material_name' => $material->material_name,
                    'unit_of_measure' => $material->unitOfMeasure?->name ?? '—',
                    'initial_stock' => $initialStock,
                    'total_distributed' => $totalDistributed,
                    'physical_available' => max(0, $initialStock - $totalDistributed),
                ];
            });

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('material-reports.exports.head-office-pdf', [
                'materials' => $materials,
                'title' => 'Head Office Material Report',
            ]);

            return $pdf->download('head_office_material_report.pdf');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Head Office Report');

        $sheet->fromArray([
            ['Material Name', 'Unit of Measurement', 'Initial Stock', 'Total Distributed', 'Physical Available'],
        ], null, 'A1');

        $row = 2;
        foreach ($materials as $material) {
            $sheet->fromArray([
                $material['material_name'],
                $material['unit_of_measure'],
                $material['initial_stock'],
                $material['total_distributed'],
                $material['physical_available'],
            ], null, "A{$row}");
            $row++;
        }

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="head_office_material_report.xls"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportSite(Request $request): Response
    {
        $format = strtolower($request->query('format', 'xlsx'));

        $projects = Project::with('siteOfficer')
            ->orderBy('project_name')
            ->get();

        if ($request->filled('project_id')) {
            $projects = $projects->where('id', $request->get('project_id'));
        }

        // Collect one aggregated row per unique material × project combination
        $rawRows = [];

        foreach ($projects as $project) {
            $materialIdsQuery = MaterialProjectAssignment::query()
                ->where('project_id', $project->id)
                ->select('material_id');

            if ($request->filled('from_date')) {
                $materialIdsQuery->whereDate('created_at', '>=', $request->get('from_date'));
            }
            if ($request->filled('to_date')) {
                $materialIdsQuery->whereDate('created_at', '<=', $request->get('to_date'));
            }
            if ($request->filled('quarter')) {
                $quarter = (int) $request->get('quarter');
                $materialIdsQuery
                    ->whereMonth('created_at', '>=', (($quarter - 1) * 3) + 1)
                    ->whereMonth('created_at', '<=', $quarter * 3);
            }

            $materialIds = $materialIdsQuery->distinct()->pluck('material_id');

            foreach ($materialIds as $materialId) {
                $material = Material::with('unitOfMeasure')->find($materialId);
                if (! $material) {
                    continue;
                }

                if ($request->filled('search')) {
                    $search = strtolower($request->get('search'));
                    if (strpos(strtolower($material->material_name), $search) === false) {
                        continue;
                    }
                }

                $assignedSumQuery = MaterialProjectAssignment::query()
                    ->where('project_id', $project->id)
                    ->where('material_id', $materialId);

                if ($request->filled('from_date')) {
                    $assignedSumQuery->whereDate('created_at', '>=', $request->get('from_date'));
                }
                if ($request->filled('to_date')) {
                    $assignedSumQuery->whereDate('created_at', '<=', $request->get('to_date'));
                }
                if ($request->filled('quarter')) {
                    $quarter = (int) $request->get('quarter');
                    $assignedSumQuery
                        ->whereMonth('created_at', '>=', (($quarter - 1) * 3) + 1)
                        ->whereMonth('created_at', '<=', $quarter * 3);
                }

                $totalAssigned = (int) $assignedSumQuery->sum('quantity');

                $employeeDistributedQuery = MaterialEmployeeAssignment::query()
                    ->where('project_id', $project->id)
                    ->where('material_id', $materialId);

                if ($request->filled('from_date')) {
                    $employeeDistributedQuery->whereDate('assigned_date', '>=', $request->get('from_date'));
                }
                if ($request->filled('to_date')) {
                    $employeeDistributedQuery->whereDate('assigned_date', '<=', $request->get('to_date'));
                }
                if ($request->filled('quarter')) {
                    $quarter = (int) $request->get('quarter');
                    $employeeDistributedQuery
                        ->whereMonth('assigned_date', '>=', (($quarter - 1) * 3) + 1)
                        ->whereMonth('assigned_date', '<=', $quarter * 3);
                }

                $totalDistributed = (int) $employeeDistributedQuery->sum('quantity');

                $rawRows[] = [
                    'material_name' => $material->material_name,
                    'unit_of_measure' => $material->unitOfMeasure?->name ?? '—',
                    'project_name' => $project->project_name,
                    'project_code' => $project->project_code,
                    'assigned_count' => $totalAssigned,
                    'distributed_to_employee' => $totalDistributed,
                    'physical_available' => max(0, $totalAssigned - $totalDistributed),
                ];
            }
        }

        // Final deduplication pass (safety net)
        $reportData = collect($rawRows)
            ->groupBy(fn ($row) => $row['material_name'].'|'.$row['project_name'])
            ->map(function ($group) {
                $first = $group->first();
                $totalAssigned = $group->sum('assigned_count');
                $totalDistributed = $group->sum('distributed_to_employee');

                return [
                    'material_name' => $first['material_name'],
                    'unit_of_measure' => $first['unit_of_measure'],
                    'project_name' => $first['project_name'],
                    'project_code' => $first['project_code'],
                    'assigned_count' => $totalAssigned,
                    'distributed_to_employee' => $totalDistributed,
                    'physical_available' => max(0, $totalAssigned - $totalDistributed),
                ];
            })
            ->values()
            ->toArray();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('material-reports.exports.site-pdf', [
                'materials' => $reportData,
                'title' => 'Site Material Reports',
            ]);

            return $pdf->download('site_material_report.pdf');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Site Material Reports');

        $sheet->fromArray([
            ['Material Name', 'Unit of Measurement', 'Project Name', 'Project Code', 'Assigned Count', 'Distributed to Employee', 'Physical Available'],
        ], null, 'A1');

        $row = 2;
        foreach ($reportData as $material) {
            $sheet->fromArray([
                $material['material_name'],
                $material['unit_of_measure'],
                $material['project_name'],
                $material['project_code'],
                $material['assigned_count'],
                $material['distributed_to_employee'],
                $material['physical_available'],
            ], null, "A{$row}");
            $row++;
        }

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="site_material_report.xls"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportInventory(Request $request): Response
    {
        $format = strtolower($request->query('format', 'xlsx'));

        $report = $this->inventoryReport->forHeadOffice(
            search: $request->get('search'),
            fromDate: $request->get('from_date'),
            toDate: $request->get('to_date'),
            quarter: $request->get('quarter'),
            projectId: $request->get('project_id'),
        );

        $materials = $report['materials'];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('material-reports.exports.inventory-pdf', [
                'materials' => $materials,
                'summary' => $report['summary'],
                'title' => 'Head Office Material Inventory Report',
            ]);

            return $pdf->download('inventory_report.pdf');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventory Report');

        $summary = $report['summary'];
        $sheet->fromArray([
            ['Head Office Material Inventory Summary'],
            ['Total Materials', $summary['total_materials']],
            ['Total Stocked Quantity', $summary['total_stocked_quantity']],
            ['Total HO Available', $summary['total_head_office_available']],
            ['Total Assigned to Projects', $summary['total_assigned_to_projects']],
            ['Total Assigned to Employees', $summary['total_assigned_to_employees']],
            ['Total Site Remaining', $summary['total_site_remaining']],
            ['Total in System', $summary['total_in_system']],
            [], // empty row
            ['Material', 'Description', 'Unit of Measure', 'Opening Stock', 'Physical Balance', 'Assigned to Sites', 'Available Balance', 'Total in System'],
        ], null, 'A1');

        $row = 11;
        foreach ($materials as $material) {
            $sheet->fromArray([
                $material['name'],
                $material['description'],
                $material['unit_of_measure'] ?? '—',
                $material['opening_stock'],
                $material['physical_balance'] ?? 0,
                $material['assigned_to_sites'],
                $material['site_remaining'],
                $material['total_in_system'],
            ], null, "A{$row}");
            $row++;
        }

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A8')->getFont()->setBold(true);
        $sheet->getStyle('A10:H10')->getFont()->setBold(true);

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="inventory_report.xls"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportBalance(Request $request): Response
    {
        $format = strtolower($request->query('format', 'xlsx'));
        $materials = $this->getMaterialBalances();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('material-reports.exports.balance-pdf', [
                'materials' => $materials,
                'title' => 'Material Balance Report',
            ]);

            return $pdf->download('material_balance_report.pdf');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Material Balance');

        $sheet->fromArray([
            ['Material Name', 'Description', 'Unit of Measurement', 'Head Office Balance', 'On Sites Balance', 'Total Balance'],
        ], null, 'A1');

        $row = 2;
        foreach ($materials as $material) {
            $sheet->fromArray([
                $material['name'],
                $material['description'],
                $material['unit_of_measure'] ?? '—',
                $material['head_office_balance'],
                $material['site_balance'],
                $material['total_balance'],
            ], null, "A{$row}");
            $row++;
        }

        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="material_balance_report.xls"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportProject(Request $request, Project $project): Response
    {
        $format = strtolower($request->query('format', 'xlsx'));
        $project->load('siteOfficer');

        $balances = MaterialProjectAssignment::query()
            ->where('project_id', $project->id)
            ->with(['material', 'receiver', 'assignedBy'])
            ->latest()
            ->get()
            ->groupBy('material_id')
            ->map(function (Collection $assignments): array {
                $first = $assignments->first();

                return [
                    'material' => $first->material,
                    'total_quantity' => $assignments->sum('quantity'),
                    'assignments' => $assignments,
                ];
            })
            ->values();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('material-reports.exports.project-pdf', [
                'project' => $project,
                'balances' => $balances,
                'title' => "Project Material Balance Report - {$project->project_code}",
            ]);

            return $pdf->download("project_material_balance_{$project->project_code}.pdf");
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Project Balance');

        $sheet->fromArray([
            ["Project Material Balance: {$project->project_code} - {$project->project_name}"],
            ['Site Officer: '.($project->siteOfficer?->name ?? '—')],
            [],
            ['Material Name', 'Date Assigned', 'Quantity', 'Receiver', 'Assigned By'],
        ], null, 'A1');

        $row = 5;
        foreach ($balances as $balance) {
            foreach ($balance['assignments'] as $assignment) {
                $sheet->fromArray([
                    $balance['material']->material_name,
                    $assignment->created_at->format('Y-m-d H:i'),
                    $assignment->quantity,
                    $assignment->receiver->name,
                    $assignment->assignedBy->name,
                ], null, "A{$row}");
                $row++;
            }
        }

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A4:E4')->getFont()->setBold(true);

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="project_material_balance_'.$project->project_code.'.xls"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
