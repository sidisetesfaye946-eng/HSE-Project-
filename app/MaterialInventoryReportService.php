<?php

namespace App;

use App\Models\Material;
use App\Models\MaterialEmployeeAssignment;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MaterialInventoryReportService
{
    /**
     * @return array{summary: array<string, int>, materials: list<array<string, mixed>>}
     */
    public function forHeadOffice(
        ?string $search = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $quarter = null,
        ?int $projectId = null,
    ): array {
        $projectIds = Project::query()
            ->when($projectId, fn ($q) => $q->where('id', $projectId))
            ->pluck('id');

        $materials = $this->buildMaterialRows(
            $projectIds,
            isHeadOffice: true,
            search: $search,
            fromDate: $fromDate,
            toDate: $toDate,
            quarter: $quarter,
            restrictToProject: $projectId !== null,
        );

        return [
            'summary' => $this->buildHeadOfficeSummary($materials),
            'materials' => $materials,
        ];
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array{summary: array<string, int>, materials: list<array<string, mixed>>}
     */
    public function forSiteOfficer(
        Collection $projects,
        ?string $search = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $quarter = null,
    ): array {
        $projectIds = $projects->pluck('id');
        $materials = $this->buildMaterialRows(
            $projectIds,
            isHeadOffice: false,
            search: $search,
            fromDate: $fromDate,
            toDate: $toDate,
            quarter: $quarter,
        );

        return [
            'summary' => $this->buildSiteOfficerSummary($materials),
            'materials' => $materials,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $materials
     * @return array<string, int>
     */
    private function buildHeadOfficeSummary(array $materials): array
    {
        return [
            'total_materials' => count($materials),
            'total_stocked_quantity' => (int) collect($materials)->sum('total_stocked_quantity'),
            'total_head_office_available' => (int) collect($materials)->sum('head_office_available'),
            'total_assigned_to_projects' => (int) collect($materials)->sum('assigned_to_projects'),
            'total_assigned_to_employees' => (int) collect($materials)->sum('assigned_to_employees'),
            'total_site_remaining' => (int) collect($materials)->sum('site_remaining'),
            'total_in_system' => (int) collect($materials)->sum('total_in_system'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $materials
     * @return array<string, int>
     */
    private function buildSiteOfficerSummary(array $materials): array
    {
        return [
            'total_materials' => count($materials),
            'total_received' => (int) collect($materials)->sum('assigned_to_projects'),
            'total_distributed' => (int) collect($materials)->sum('assigned_to_employees'),
            'total_available' => (int) collect($materials)->sum('site_remaining'),
        ];
    }

    /**
     * @param  Collection<int, int>  $projectIds
     * @return list<array<string, mixed>>
     */
    private function buildMaterialRows(
        Collection $projectIds,
        bool $isHeadOffice,
        ?string $search = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $quarter = null,
        bool $restrictToProject = false,
    ): array {
        $projectQuery = MaterialProjectAssignment::query();
        $employeeQuery = MaterialEmployeeAssignment::query();

        // Apply date range filters
        if ($quarter) {
            [$quarterStart, $quarterEnd] = $this->getQuarterDateRange($quarter);
            $projectQuery->whereBetween('created_at', [$quarterStart, $quarterEnd]);
            $employeeQuery->whereBetween('created_at', [$quarterStart, $quarterEnd]);
        } else {
            if ($fromDate) {
                $projectQuery->whereDate('created_at', '>=', $fromDate);
                $employeeQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $projectQuery->whereDate('created_at', '<=', $toDate);
                $employeeQuery->whereDate('created_at', '<=', $toDate);
            }
        }

        $assignedToProjectsByMaterial = $this->assignmentsByMaterial(
            $projectQuery,
            $projectIds,
        );

        $assignedToEmployeesByMaterial = $this->assignmentsByMaterial(
            $employeeQuery,
            $projectIds,
        );

        if ($isHeadOffice) {
            if ($restrictToProject) {
                $materialIds = $assignedToProjectsByMaterial->keys()
                    ->merge($assignedToEmployeesByMaterial->keys())
                    ->unique();

                if ($materialIds->isEmpty()) {
                    return [];
                }

                $materials = Material::query()
                    ->with('unitOfMeasure')
                    ->whereIn('id', $materialIds)
                    ->when($search, fn ($q) => $q->where('material_name', 'like', "%{$search}%"))
                    ->orderBy('material_name')
                    ->get();
            } else {
                $materials = Material::query()
                    ->with('unitOfMeasure')
                    ->when($search, fn ($q) => $q->where('material_name', 'like', "%{$search}%"))
                    ->orderBy('material_name')
                    ->get();
            }
        } else {
            $materialIds = $assignedToProjectsByMaterial->keys()
                ->merge($assignedToEmployeesByMaterial->keys())
                ->unique();

            if ($materialIds->isEmpty()) {
                return [];
            }

            $materials = Material::query()
                ->with('unitOfMeasure')
                ->whereIn('id', $materialIds)
                ->when($search, fn ($q) => $q->where('material_name', 'like', "%{$search}%"))
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
            $headOfficeAvailable = $isHeadOffice ? $material->quantity : null;
            $siteRemaining = max(0, $assignedToProjects - $assignedToEmployees);
            $totalStockedQuantity = ($headOfficeAvailable ?? 0) + $assignedToProjects;
            $totalInSystem = ($headOfficeAvailable ?? 0) + $siteRemaining;

            return [
                'id' => $material->id,
                'name' => $material->material_name,
                'description' => $material->material_description,
                'unit_of_measure' => $material->unitOfMeasure?->name,
                'head_office_available' => $headOfficeAvailable,
                'total_stocked_quantity' => $totalStockedQuantity,
                'assigned_to_projects' => $assignedToProjects,
                'assigned_to_employees' => $assignedToEmployees,
                'site_remaining' => $siteRemaining,
                'total_in_system' => $totalInSystem,
                // New field names for clarity
                'opening_stock' => $totalStockedQuantity,
                'assigned_to_sites' => $assignedToProjects,
                'physical_balance' => $headOfficeAvailable,
            ];
        })->values()->all();
    }

    /**
     * @param  Builder<Model>  $query
     * @param  Collection<int, int>  $projectIds
     * @return Collection<int|string, int>
     */
    private function assignmentsByMaterial($query, Collection $projectIds): Collection
    {
        if ($projectIds->isEmpty()) {
            return collect();
        }

        return $query
            ->whereIn('project_id', $projectIds)
            ->selectRaw('material_id, SUM(quantity) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id')
            ->map(fn ($total): int => (int) $total);
    }

    /**
     * @return array<int, Carbon>
     */
    private function getQuarterDateRange(string $quarter): array
    {
        $year = now()->year;
        $quarterNum = (int) $quarter;

        return match ($quarterNum) {
            1 => [Carbon::create($year, 1, 1), Carbon::create($year, 3, 31)->endOfDay()],
            2 => [Carbon::create($year, 4, 1), Carbon::create($year, 6, 30)->endOfDay()],
            3 => [Carbon::create($year, 7, 1), Carbon::create($year, 9, 30)->endOfDay()],
            4 => [Carbon::create($year, 10, 1), Carbon::create($year, 12, 31)->endOfDay()],
            default => [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()],
        };
    }
}
