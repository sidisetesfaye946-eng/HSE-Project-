<?php

namespace App\Http\Controllers;

use App\Models\MaterialEmployeeAssignment;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use App\SiteMaterialBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteOfficerProjectController extends Controller
{
    public function __construct(private SiteMaterialBalanceService $balanceService) {}

    public function index(Request $request): View|JsonResponse
    {
        $search = $request->query('search');

        $projects = Project::query()
            ->when(! $request->user()->hasAnyRole(['Admin', 'HSE Officer']), function ($query) use ($request) {
                return $query->where(function ($q) use ($request) {
                    $q->where('site_officer_id', $request->user()->id)
                        ->orWhereHas('siteOfficer', fn ($subQ) => $subQ->where('id', $request->user()->id));
                });
            })
            ->when($search, function ($query) use ($search) {
                return $query->where('project_name', 'like', $search.'%');
            })
            ->orderBy('project_name')
            ->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($projects);
        }

        $balancesByProject = $projects->mapWithKeys(function (Project $project): array {
            return [$project->id => $this->balanceService->balancesForProject($project)];
        });

        return view('site-officer.projects.index', [
            'projects' => $projects,
            'balancesByProject' => $balancesByProject,
        ]);
    }

    public function show(Request $request, Project $project): View
    {
        $this->authorizeProject($request, $project);

        $balances = $this->balanceService->balancesForProject($project);

        $incomingHistory = MaterialProjectAssignment::query()
            ->where('project_id', $project->id)
            ->with(['material', 'assignedBy'])
            ->latest()
            ->get();

        $employeeAssignments = MaterialEmployeeAssignment::query()
            ->where('project_id', $project->id)
            ->with(['material', 'employee'])
            ->latest()
            ->get();

        $attachedEmployees = $project->employees()->orderBy('first_name')->orderBy('last_name')->get();

        return view('site-officer.projects.show', [
            'project' => $project,
            'balances' => $balances,
            'incomingHistory' => $incomingHistory,
            'employeeAssignments' => $employeeAssignments,
            'attachedEmployees' => $attachedEmployees,
        ]);
    }

    private function authorizeProject(Request $request, Project $project): void
    {
        abort_unless($project->isManagedBy($request->user()), 403);
    }
}
