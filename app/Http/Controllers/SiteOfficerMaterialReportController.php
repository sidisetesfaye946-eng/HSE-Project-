<?php

namespace App\Http\Controllers;

use App\MaterialInventoryReportService;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteOfficerMaterialReportController extends Controller
{
    public function __construct(private MaterialInventoryReportService $inventoryReport) {}

    public function index(Request $request): View
    {
        $projects = Project::query()
            ->when(! $request->user()->hasAnyRole(['Admin', 'HSE Officer']), function ($query) use ($request) {
                return $query->where('site_officer_id', $request->user()->id);
            })
            ->orderBy('project_name')
            ->get();

        $report = $this->inventoryReport->forSiteOfficer($projects);

        return view('site-officer.material-reports.index', [
            'projects' => $projects,
            'summary' => $report['summary'],
            'materials' => $report['materials'],
        ]);
    }
}
