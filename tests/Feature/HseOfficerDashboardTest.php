<?php

use App\Models\Material;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function createHseOfficerUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('HSE Officer');

    return $user;
}

it('shows head office dashboard for hse officer', function () {
    $hseOfficer = createHseOfficerUser();

    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'North Site',
        'project_code' => 'NS-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Vest',
        'material_description' => 'Safety vest',
        'quantity' => 25,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'quantity' => 10,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => $hseOfficer->id,
    ]);

    $this->actingAs($hseOfficer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Head Office Dashboard')
        ->assertSee('Total Projects')
        ->assertSee('Head Office Stock')
        ->assertDontSee('Material Balance by Project')
        ->assertDontSee('Assignment Flow Overview')
        ->assertDontSee('Assigned to Employees')
        ->assertDontSee('Remaining Site Balance')
        ->assertSee('Vest')
        ->assertSee('NS-001');
});

it('does not show head office dashboard to other roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Admin Dashboard')
        ->assertSee('Total Users')
        ->assertSee('Total Roles')
        ->assertSee('Users vs Roles')
        ->assertSee('Projects')
        ->assertDontSee('Head Office Dashboard');
});
