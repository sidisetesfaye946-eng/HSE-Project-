<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Rules\EecCompanyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::with('roles')->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        $dbRoles = Role::whereIn('name', ['HSE Officer', 'HSE Site Officer'])->orderBy('name')->pluck('name');
        $roles = $dbRoles->isNotEmpty() ? $dbRoles : collect(['HSE Officer', 'HSE Site Officer']);

        return view('users.create', [
            'user' => new User,
            'roles' => $roles,
            'projects' => Project::orderBy('project_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', new EecCompanyEmail, 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['HSE Officer', 'HSE Site Officer'])],
            'project_id' => ['nullable', 'exists:projects,id'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['exists:projects,id'],
        ]);

        $role = $data['role'];
        $projectIds = $data['project_ids'] ?? [];
        $projectId = $data['project_id'] ?? ($projectIds[0] ?? null);
        unset($data['role'], $data['project_ids']);

        $data['project_id'] = $projectId;

        $user = User::create($data);
        $user->assignRole($role);

        // Ensure primary project is included in pivot table if selected
        if ($projectId && ! in_array($projectId, $projectIds)) {
            $projectIds[] = (int) $projectId;
        }
        $user->projects()->sync($projectIds);

        // If user is a Site Officer and assigned a primary project, update project's site_officer_id
        if ($role === 'HSE Site Officer' && $projectId) {
            Project::where('id', $projectId)->update(['site_officer_id' => $user->id]);
        }

        return Redirect::route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $dbRoles = Role::whereIn('name', ['HSE Officer', 'HSE Site Officer'])->orderBy('name')->pluck('name');
        $roles = $dbRoles->isNotEmpty() ? $dbRoles : collect(['HSE Officer', 'HSE Site Officer']);

        return view('users.edit', [
            'user' => $user->load('projects'),
            'roles' => $roles,
            'projects' => Project::orderBy('project_name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', new EecCompanyEmail, 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['HSE Officer', 'HSE Site Officer'])],
            'project_id' => ['nullable', 'exists:projects,id'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['exists:projects,id'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $role = $data['role'];
        $projectIds = $data['project_ids'] ?? [];
        $projectId = $data['project_id'] ?? ($projectIds[0] ?? null);
        unset($data['role'], $data['project_ids']);

        $data['project_id'] = $projectId;

        $user->update($data);
        $user->syncRoles([$role]);

        if ($projectId && ! in_array($projectId, $projectIds)) {
            $projectIds[] = (int) $projectId;
        }
        $user->projects()->sync($projectIds);

        if ($role === 'HSE Site Officer' && $projectId) {
            Project::where('id', $projectId)->update(['site_officer_id' => $user->id]);
        }

        return Redirect::route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return Redirect::route('users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return Redirect::route('users.index')->with('success', 'User deleted successfully.');
    }
}
