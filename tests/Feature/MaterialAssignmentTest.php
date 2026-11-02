<?php

use App\Models\Material;
use App\Models\MaterialHistory;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitOfMeasureSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(UnitOfMeasureSeeder::class);
});

function createHseOfficer(): User
{
    $user = User::factory()->create();

    $user->assignRole('HSE Officer');

    return $user;
}

function createSiteOfficer(): User
{
    $user = User::factory()->create();

    $user->assignRole('HSE Site Officer');

    return $user;
}

it('allows hse officer to assign multiple materials to a project', function () {
    $officer = createHseOfficer();
    $siteOfficer = createSiteOfficer();

    $project = Project::create([
        'project_name' => 'Site Alpha',
        'project_code' => 'SA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $materialA = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety helmet',
        'quantity' => 100,
    ]);

    $materialB = Material::create([
        'material_name' => 'Gloves',
        'material_description' => 'Safety gloves',
        'quantity' => 50,
    ]);

    $response = $this->actingAs($officer)->post(route('material-assignments.store'), [
        'project_id' => $project->id,
        'assignments' => [
            ['material_id' => $materialA->id, 'quantity' => 10],
            ['material_id' => $materialB->id, 'quantity' => 5],
        ],
    ]);

    $response->assertRedirect(route('material-reports.show', $project));

    expect(MaterialProjectAssignment::count())->toBe(2);
    expect($materialA->fresh()->quantity)->toBe(90);
    expect($materialB->fresh()->quantity)->toBe(45);

    $assignment = MaterialProjectAssignment::first();
    expect($assignment->receiver_id)->toBe($siteOfficer->id);
    expect($assignment->assigned_by)->toBe($officer->id);
});

it('redirects to material quantity page when head office balance is insufficient', function () {
    $officer = createHseOfficer();
    $siteOfficer = createSiteOfficer();

    $project = Project::create([
        'project_name' => 'Site Beta',
        'project_code' => 'SB-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Vest',
        'material_description' => 'Safety vest',
        'quantity' => 2,
    ]);

    $response = $this->actingAs($officer)->post(route('material-assignments.store'), [
        'project_id' => $project->id,
        'assignments' => [
            ['material_id' => $material->id, 'quantity' => 5],
        ],
    ]);

    $response->assertRedirect(route('material-quantities.edit', $material));
    $response->assertSessionHas('error');
    expect($material->fresh()->quantity)->toBe(2);
    expect(MaterialProjectAssignment::count())->toBe(0);
});

it('denies non hse officers from assigning materials', function () {
    $user = User::factory()->create();
    $user->assignRole('Employee');

    $this->actingAs($user)
        ->get(route('material-assignments.create'))
        ->assertForbidden();
});

it('shows unit of measure options when hse officer creates a material', function () {
    $officer = createHseOfficer();

    $this->actingAs($officer)
        ->get(route('materials.create'))
        ->assertOk()
        ->assertSee('Unit of Measure')
        ->assertSee('Pcs');
});

it('requires material quantity when hse officer creates a material', function () {
    $officer = createHseOfficer();
    $unitOfMeasure = UnitOfMeasure::first();

    $this->actingAs($officer)->post(route('materials.store'), [
        'material_name' => 'Boots',
        'material_description' => 'Safety boots',
        'quantity' => 25,
        'unit_of_measure_id' => $unitOfMeasure->id,
    ])->assertRedirect(route('materials.index'));

    expect(Material::first()->quantity)->toBe(25);
    expect(Material::first()->unit_of_measure_id)->toBe($unitOfMeasure->id);
});

it('allows hse officer to update material quantity and records history', function () {
    $officer = createHseOfficer();
    $unitOfMeasure = UnitOfMeasure::first();

    $material = Material::create([
        'material_name' => 'Vest',
        'material_description' => 'Safety vest',
        'quantity' => 10,
        'unit_of_measure_id' => $unitOfMeasure->id,
    ]);

    $this->actingAs($officer)->put(route('materials.update', $material), [
        'material_name' => 'Vest Updated',
        'material_description' => 'Safety vest updated',
        'quantity' => 15,
        'unit_of_measure_id' => $unitOfMeasure->id,
    ])->assertRedirect(route('materials.index'));

    expect($material->fresh()->quantity)->toBe(15);
    expect($material->fresh()->material_name)->toBe('Vest Updated');

    $history = MaterialHistory::where('material_id', $material->id)
        ->where('event_type', 'stock_updated')
        ->first();

    expect($history)->not->toBeNull();
    expect($history->quantity_change)->toBe(5);
    expect($history->balance_before)->toBe(10);
    expect($history->balance_after)->toBe(15);
});
