<?php

namespace App;

use App\Models\Material;
use App\Models\MaterialEmployeeAssignment;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use Illuminate\Support\Collection;

class MaterialDashboardService
{
    public function __construct(private SiteMaterialBalanceService $balanceService) {}

    /**
     * @return array<string, mixed>
     */
    public function forHeadOffice(): array
    {
        $projects = Project::query()->orderBy('project_name')->get();

        return $this->buildDashboard($projects, isHeadOffice: true);
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<string, mixed>
     */
    public function forSiteOfficer(Collection $projects): array
    {
        return $this->buildDashboard($projects, isHeadOffice: false);
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<string, mixed>
     */
    private function buildDashboard(Collection $projects, bool $isHeadOffice): array
    {
        $projectIds = $projects->pluck('id');

        $totalAssignedToProjects = $this->sumProjectAssignments($projectIds);
        $totalAssignedToEmployees = $isHeadOffice ? null : $this->sumEmployeeAssignments($projectIds);
        $siteRemaining = $isHeadOffice ? null : max(0, $totalAssignedToProjects - $totalAssignedToEmployees);
        $headOfficeStock = $isHeadOffice ? (int) Material::query()->sum('quantity') : null;

        $projectBreakdown = $projects->map(function (Project $project): array {
            $balances = $this->balanceService->balancesForProject($project);

            return [
                'id' => $project->id,
                'code' => $project->project_code,
                'name' => $project->project_name,
                'received' => (int) $balances->sum('received'),
                'distributed' => (int) $balances->sum('distributed'),
                'available' => (int) $balances->sum('available'),
            ];
        })->values()->all();

        $materialRows = $this->materialBalanceRows($projectIds, $isHeadOffice);

        $projectLabels = array_column($projectBreakdown, 'code');

        return [
            'is_head_office' => $isHeadOffice,
            'total_projects' => $projects->count(),
            'head_office_stock' => $headOfficeStock,
            'total_assigned_to_projects' => $totalAssignedToProjects,
            'total_assigned_to_employees' => $totalAssignedToEmployees,
            'site_remaining_balance' => $siteRemaining,
            'project_breakdown' => $projectBreakdown,
            'material_rows' => $materialRows,
            'charts' => [
                'project_comparison' => $isHeadOffice ? [
                    'labels' => [],
                    'received' => [],
                    'distributed' => [],
                    'available' => [],
                ] : [
                    'labels' => $projectLabels,
                    'received' => array_column($projectBreakdown, 'received'),
                    'distributed' => array_column($projectBreakdown, 'distributed'),
                    'available' => array_column($projectBreakdown, 'available'),
                ],
                'assignment_flow' => $isHeadOffice ? [
                    'labels' => [],
                    'values' => [],
                ] : [
                    'labels' => ['Received at Site', 'Assigned to Employees', 'Remaining Balance'],
                    'values' => [
                        $totalAssignedToProjects,
                        $totalAssignedToEmployees,
                        $siteRemaining,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, int>  $projectIds
     * @return list<array{name: string, head_office_stock: int|null, assigned_to_projects: int, assigned_to_employees: int, site_remaining: int}>
     */
    private function materialBalanceRows(Collection $projectIds, bool $isHeadOffice): array
    {
        $assignedToProjectsByMaterial = $this->projectAssignmentsByMaterial($projectIds);
        $assignedToEmployeesByMaterial = $this->employeeAssignmentsByMaterial($projectIds);

        if ($isHeadOffice) {
            $materials = Material::query()->orderBy('material_name')->get();
        } else {
            $materialIds = $assignedToProjectsByMaterial->keys()
                ->merge($assignedToEmployeesByMaterial->keys())
                ->unique();

            if ($materialIds->isEmpty()) {
                return [];
            }

            $materials = Material::query()
                ->whereIn('id', $materialIds)
                ->orderBy('material_name')
                ->get();
        }

        return $materials->map(function (Material $material) use (
            $assignedToProjectsByMaterial,
            $assignedToEmployeesByMaterial,
            $isHeadOffice
        ): array {
            $assignedToProjects = (int) ($assignedToProjectsByMaterial[$material->id] ?? 0);
            $assignedToEmployees = (int) ($assignedToEmployeesByMaterial[$material->id] ?? 0);

            return [
                'name' => $material->material_name,
                'head_office_stock' => $isHeadOffice ? $material->quantity : null,
                'assigned_to_projects' => $assignedToProjects,
                'assigned_to_employees' => $assignedToEmployees,
                'site_remaining' => max(0, $assignedToProjects - $assignedToEmployees),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, int>  $projectIds
     */
    private function sumProjectAssignments(Collection $projectIds): int
    {
        if ($projectIds->isEmpty()) {
            return 0;
        }

        return (int) MaterialProjectAssignment::query()
            ->whereIn('project_id', $projectIds)
            ->sum('quantity');
    }

    /**
     * @param  Collection<int, int>  $projectIds
     */
    private function sumEmployeeAssignments(Collection $projectIds): int
    {
        if ($projectIds->isEmpty()) {
            return 0;
        }

        return (int) MaterialEmployeeAssignment::query()
            ->whereIn('project_id', $projectIds)
            ->sum('quantity');
    }

    /**
     * @param  Collection<int, int>  $projectIds
     * @return Collection<int|string, int>
     */
    private function projectAssignmentsByMaterial(Collection $projectIds): Collection
    {
        if ($projectIds->isEmpty()) {
            return collect();
        }

        return MaterialProjectAssignment::query()
            ->whereIn('project_id', $projectIds)
            ->selectRaw('material_id, SUM(quantity) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id')
            ->map(fn ($total): int => (int) $total);
    }

    /**
     * @param  Collection<int, int>  $projectIds
     * @return Collection<int|string, int>
     */
    private function employeeAssignmentsByMaterial(Collection $projectIds): Collection
    {
        if ($projectIds->isEmpty()) {
            return collect();
        }

        return MaterialEmployeeAssignment::query()
            ->whereIn('project_id', $projectIds)
            ->selectRaw('material_id, SUM(quantity) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id')
            ->map(fn ($total): int => (int) $total);
    }
}
