<?php

namespace App;

use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialEmployeeAssignment;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $roles = Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $latestUsers = User::query()
            ->with('roles')
            ->latest()
            ->limit(5)
            ->get();

        $latestProjects = Project::query()
            ->with('siteOfficer')
            ->latest()
            ->limit(5)
            ->get();

        return [
            'total_users' => User::query()->count(),
            'total_roles' => $roles->count(),
            'total_projects' => Project::query()->count(),
            'total_employees' => Employee::query()->count(),
            'total_materials' => Material::query()->count(),
            'head_office_stock' => Material::query()->sum('quantity'),
            'assigned_to_projects' => MaterialProjectAssignment::query()->sum('quantity'),
            'assigned_to_employees' => MaterialEmployeeAssignment::query()->sum('quantity'),
            'roles' => $roles,
            'max_role_users' => max(1, (int) $roles->max('users_count')),
            'latest_users' => $latestUsers,
            'latest_projects' => $latestProjects,
        ];
    }
}
