<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class SiteOfficerProjectEmployeeController extends Controller
{
    public function index(Request $request, Project $project): View
    {
        abort_if($request->user()->hasRole('HSE Site Officer'), 403);
        abort_unless($project->isManagedBy($request->user()), 403);

        $allEmployees = Employee::whereDoesntHave('projects')
            ->orderBy('first_name')->orderBy('last_name')->get();

        return view('site-officer.projects.employees', [
            'project' => $project,
            'allEmployees' => $allEmployees,
        ]);
    }

    public function sync(Request $request, Project $project): RedirectResponse
    {
        abort_if($request->user()->hasRole('HSE Site Officer'), 403);
        abort_unless($project->isManagedBy($request->user()), 403);

        $validated = $request->validate([
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['exists:employees,id'],
        ]);

        if (! empty($validated['employee_ids'])) {
            $project->employees()->syncWithoutDetaching($validated['employee_ids']);
        }

        return Redirect::route('site-officer.projects.show', $project)
            ->with('success', 'Employees added to project successfully.');
    }

    public function destroy(Request $request, Project $project, Employee $employee): RedirectResponse
    {
        abort_if($request->user()->hasRole('HSE Site Officer'), 403);
        abort_unless($project->isManagedBy($request->user()), 403);

        $project->employees()->detach($employee->id);

        return Redirect::back()->with('success', 'Employee removed from project successfully.');
    }
}
