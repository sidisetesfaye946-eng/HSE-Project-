<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::withCount('users')->orderBy('name')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('roles.create', [
            'role' => new Role,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        return Redirect::route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        return view('roles.edit', [
            'role' => $role,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
        ]);

        $role->update(['name' => $data['name']]);

        return Redirect::route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'Admin') {
            return Redirect::route('roles.index')->with('error', 'The Admin role cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return Redirect::route('roles.index')->with('error', 'Cannot delete a role that is assigned to users.');
        }

        $role->delete();

        return Redirect::route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
