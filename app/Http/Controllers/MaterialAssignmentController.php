<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use App\Services\WorkflowNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaterialAssignmentController extends Controller
{
    public function create(): View
    {
        return view('material-assignments.create', [
            'projects' => Project::with('siteOfficer')->orderBy('project_name')->get(),
            'materials' => Material::orderBy('material_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.material_id' => ['required', 'exists:materials,id'],
            'assignments.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $project = Project::with('siteOfficer')->findOrFail($validated['project_id']);
        $siteOfficer = $project->assigned_site_officer ?? $project->siteOfficer;

        if (! $siteOfficer) {
            throw ValidationException::withMessages([
                'project_id' => 'This project does not have an HSE Site Officer assigned.',
            ]);
        }

        $assignments = collect($validated['assignments'])
            ->filter(fn (array $row): bool => filled($row['material_id'] ?? null))
            ->values();

        if ($assignments->isEmpty()) {
            throw ValidationException::withMessages([
                'assignments' => 'At least one valid material row is required.',
            ]);
        }

        $materialIds = $assignments->pluck('material_id');
        if ($materialIds->count() !== $materialIds->unique()->count()) {
            throw ValidationException::withMessages([
                'assignments' => 'Each material can only be selected once per assignment.',
            ]);
        }

        $materials = Material::whereIn('id', $materialIds)->get()->keyBy('id');

        foreach ($assignments as $index => $row) {
            $material = $materials->get($row['material_id']);

            if ($material === null) {
                continue;
            }

            if (! $material->hasAvailableQuantity((int) $row['quantity'])) {
                return Redirect::route('material-quantities.edit', $material)
                    ->with('error', "Insufficient head office balance for {$material->material_name}. Please add quantity before assigning.");
            }
        }

        DB::transaction(function () use ($assignments, $materials, $project, $request): void {
            foreach ($assignments as $row) {
                $material = $materials->get($row['material_id']);

                if ($material === null) {
                    continue;
                }

                $quantity = (int) $row['quantity'];

                MaterialProjectAssignment::create([
                    'material_id' => $material->id,
                    'project_id' => $project->id,
                    'quantity' => $quantity,
                    'receiver_id' => $project->site_officer_id,
                    'assigned_by' => $request->user()->id,
                ]);

                $material->deductQuantity($quantity);
                $material->recordHistory('assigned_to_project', -$quantity, "Assigned to project {$project->project_code}.", $request->user()->id);
            }
        });

        $assignmentDetails = $assignments
            ->map(fn (array $row): array => [
                'material' => $materials->get($row['material_id']),
                'quantity' => (int) $row['quantity'],
            ])
            ->filter(fn (array $row): bool => $row['material'] !== null);

        app(WorkflowNotificationService::class)->materialsAssignedToProject(
            $project,
            $assignmentDetails,
            $request->user(),
        );

        return Redirect::route('material-reports.show', $project)
            ->with('success', 'Materials assigned to '.$project->project_code.' successfully.');
    }
}
