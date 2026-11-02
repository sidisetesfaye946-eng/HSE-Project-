<?php

namespace App\Http\Controllers;

use App\Models\MaterialEmployeeAssignment;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use App\Models\User;
use App\SiteMaterialBalanceService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $projects = Project::query()
            ->with('siteOfficer')
            ->when(! $request->user()->hasAnyRole(['Admin', 'HSE Officer']), function ($query) use ($request) {
                return $query->where('site_officer_id', $request->user()->id);
            })
            ->when($search, function ($query) use ($search) {
                return $query->where('project_name', 'like', $search.'%');
            })
            ->orderBy('project_name')
            ->paginate(10)
            ->withQueryString();

        $isAdmin = $request->user()->hasAnyRole(['Admin', 'HSE Officer']);

        return view('projects.index', [
            'projects' => $projects,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function show(Project $project): View
    {
        // Resolve the same service used for balance calculation
        $balanceService = app(SiteMaterialBalanceService::class);

        $balances = $balanceService->balancesForProject($project);

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

        return view('projects.show', [
            'project' => $project,
            'balances' => $balances,
            'incomingHistory' => $incomingHistory,
            'employeeAssignments' => $employeeAssignments,
            'attachedEmployees' => $attachedEmployees,
        ]);
    }

    public function create(): View
    {
        return view('projects.create', [
            'project' => new Project,
            'siteOfficers' => $this->siteOfficers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:255'],
            'project_code' => ['required', 'string', 'max:255', 'unique:projects,project_code'],
            'site_officer_id' => ['required', 'exists:users,id'],
        ]);

        $this->ensureSiteOfficer($data['site_officer_id']);

        Project::create($data);

        return Redirect::route('projects.index')->with('success', 'Project created successfully.');
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', [
            'project' => $project,
            'siteOfficers' => $this->siteOfficers(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:255'],
            'project_code' => ['required', 'string', 'max:255', 'unique:projects,project_code,'.$project->id],
            'site_officer_id' => ['required', 'exists:users,id'],
        ]);

        $this->ensureSiteOfficer($data['site_officer_id']);

        $project->update($data);

        return Redirect::route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return Redirect::route('projects.index')->with('success', 'Project deleted successfully.');
    }

    /**
     * @return Collection<int, User>
     */
    private function siteOfficers()
    {
        return User::role('HSE Site Officer')->orderBy('name')->get();
    }

    private function ensureSiteOfficer(int $userId): void
    {
        $user = User::findOrFail($userId);

        if (! $user->hasRole('HSE Site Officer')) {
            abort(422, 'Selected user must have the HSE Site Officer role.');
        }
    }
}
