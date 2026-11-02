<?php

namespace App\Http\Controllers;

use App\MaterialDashboardService;
use App\Models\Material;
use App\Models\MaterialProjectAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HseOfficerDashboardController extends Controller
{
    public function __construct(private MaterialDashboardService $dashboardService) {}

    public function index(Request $request): View
    {
        $stats = $this->dashboardService->forHeadOffice();

        $recentProjectAssignments = MaterialProjectAssignment::query()
            ->with(['material', 'project', 'assignedBy'])
            ->latest()
            ->limit(8)
            ->get();

        $recentMaterials = Material::latest()->paginate(9);

        return view('hse-officer.dashboard', [
            'stats' => $stats,
            'recentProjectAssignments' => $recentProjectAssignments,
            'recentMaterials' => $recentMaterials,
        ]);
    }
}
