<?php

namespace App;

use App\Models\Material;
use App\Models\MaterialEmployeeAssignment;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use Illuminate\Support\Collection;

class SiteMaterialBalanceService
{
    /**
     * @return Collection<int, array{material: Material, received: int, distributed: int, available: int}>
     */
    public function balancesForProject(Project $project): Collection
    {
        $received = MaterialProjectAssignment::query()
            ->where('project_id', $project->id)
            ->selectRaw('material_id, SUM(quantity) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id');

        $distributed = MaterialEmployeeAssignment::query()
            ->where('project_id', $project->id)
            ->selectRaw('material_id, SUM(quantity) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id');

        $materialIds = $received->keys()->merge($distributed->keys())->unique();

        return $materialIds->map(function (int|string $materialId) use ($received, $distributed): array {
            $material = Material::find($materialId);

            $receivedQty = max(0, (int) ($received[$materialId] ?? 0));
            $distributedQty = max(0, (int) ($distributed[$materialId] ?? 0));

            return [
                'material' => $material,
                'received' => $receivedQty,
                'distributed' => $distributedQty,
                'available' => max(0, $receivedQty - $distributedQty),
            ];
        })->filter(fn (array $row): bool => $row['material'] !== null)->values();
    }

    public function availableQuantity(Project $project, int $materialId): int
    {
        $balance = $this->balancesForProject($project)
            ->firstWhere(fn (array $row): bool => $row['material']->id === $materialId);

        return max(0, $balance['available'] ?? 0);
    }
}
