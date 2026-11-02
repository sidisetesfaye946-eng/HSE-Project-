<?php

namespace App\Http\Controllers;

use App\MaterialDashboardService;
use App\Models\MaterialEmployeeAssignment;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteOfficerDashboardController extends Controller
{
    public function __construct(private MaterialDashboardService $dashboardService) {}

    public function index(Request $request): View
    {
        $projects = Project::query()
            ->where('site_officer_id', $request->user()->id)
            ->orderBy('project_name')
            ->get();

        $stats = $this->dashboardService->forSiteOfficer($projects);

        $incomingHistory = MaterialProjectAssignment::query()
            ->whereIn('project_id', $projects->pluck('id'))
            ->with(['material', 'project', 'assignedBy'])
            ->latest()
            ->limit(8)
            ->get();

        $employeeAssignmentHistory = MaterialEmployeeAssignment::query()
            ->whereIn('project_id', $projects->pluck('id'))
            ->with(['material', 'project', 'employee', 'assignedBy'])
            ->latest()
            ->limit(8)
            ->get();

        return view('site-officer.dashboard', [
            'projects' => $projects,
            'stats' => $stats,
            'incomingHistory' => $incomingHistory,
            'employeeAssignmentHistory' => $employeeAssignmentHistory,
        ]);
    }
}
