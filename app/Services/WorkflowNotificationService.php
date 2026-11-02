<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Material;
use App\Models\Project;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Collection;

class WorkflowNotificationService
{
    /**
     * @param  string|list<string>  $roles
     */
    public function notifyRoleUsers(string|array $roles, WorkflowNotification $notification, ?User $except = null): void
    {
        User::role($roles)
            ->when($except !== null, fn ($query) => $query->where('id', '!=', $except->id))
            ->get()
            ->each(fn (User $user): null => $user->notify($notification));
    }

    public function notifyUser(User $user, WorkflowNotification $notification): void
    {
        $user->notify($notification);
    }

    public function materialCreated(Material $material, User $actor): void
    {
        $this->notifyRoleUsers(
            ['HSE Officer'],
            new WorkflowNotification(
                category: 'material_created',
                title: 'New material created',
                message: "{$material->material_name} was created with {$material->quantity} units at head office by {$actor->name}.",
                actionUrl: route('materials.index'),
            ),
            except: $actor,
        );
    }

    public function materialQuantityUpdated(Material $material, int $quantityAdded, User $actor): void
    {
        $actionMessage = $quantityAdded >= 0
            ? "{$quantityAdded} units of {$material->material_name} were added."
            : abs($quantityAdded)." units of {$material->material_name} were deducted.";

        $this->notifyRoleUsers(
            ['HSE Officer'],
            new WorkflowNotification(
                category: 'material_quantity_updated',
                title: 'Head office stock updated',
                message: "{$actionMessage} Current balance: {$material->quantity}.",
                actionUrl: route('material-quantities.index'),
            ),
            except: $actor,
        );
    }

    public function materialUpdated(Material $material, User $actor): void
    {
        $this->notifyRoleUsers(
            ['HSE Officer'],
            new WorkflowNotification(
                category: 'material_updated',
                title: 'Material updated',
                message: "{$material->material_name} details were updated by {$actor->name}.",
                actionUrl: route('materials.index'),
            ),
            except: $actor,
        );
    }

    /**
     * @param  Collection<int, array{material: Material, quantity: int}>  $assignments
     */
    public function materialsAssignedToProject(Project $project, Collection $assignments, User $actor): void
    {
        $siteOfficer = $project->siteOfficer;

        if ($siteOfficer === null) {
            return;
        }

        $summary = $assignments
            ->map(fn (array $row): string => "{$row['material']->material_name} × {$row['quantity']}")
            ->implode(', ');

        $this->notifyUser(
            $siteOfficer,
            new WorkflowNotification(
                category: 'material_assigned_to_project',
                title: 'Materials received at site',
                message: "{$actor->name} assigned {$summary} to project {$project->project_code} ({$project->project_name}).",
                actionUrl: route('site-officer.projects.show', $project),
            ),
        );

        $this->notifyRoleUsers(
            ['HSE Officer'],
            new WorkflowNotification(
                category: 'material_assigned_to_project',
                title: 'Materials distributed to project',
                message: "{$summary} were sent to {$project->project_code} by {$actor->name}.",
                actionUrl: route('material-reports.show', $project),
            ),
            except: $actor,
        );
    }

    /**
     * @param  Collection<int, array{material: Material, employee: Employee, quantity: int}>  $assignments
     */
    public function materialsAssignedToEmployees(Project $project, Collection $assignments, User $actor): void
    {
        $summary = $assignments
            ->map(function (array $row): string {
                $materialName = $row['material']->material_name;
                $quantity = $row['quantity'];
                $employee = $row['employee'] ?? null;

                return $employee
                    ? "{$materialName} × {$quantity} → {$employee->first_name} {$employee->last_name}"
                    : "{$materialName} × {$quantity}";
            })
            ->implode('; ');

        $this->notifyRoleUsers(
            ['HSE Officer'],
            new WorkflowNotification(
                category: 'material_assigned_to_employee',
                title: 'Materials distributed to employees',
                message: "{$actor->name} assigned materials on {$project->project_code}: {$summary}.",
                actionUrl: route('material-reports.show', $project),
            ),
        );

        $this->notifyUser(
            $actor,
            new WorkflowNotification(
                category: 'material_assigned_to_employee',
                title: 'Employee assignments recorded',
                message: "You assigned materials on {$project->project_code}: {$summary}.",
                actionUrl: route('site-officer.employee-assignments.index'),
            ),
        );
    }
}
