<?php

use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialEmployeeAssignment;
use App\Models\MaterialProjectAssignment;
use App\Models\Project;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('shows material balances for all projects and a specific project', function () {
    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'Site Gamma',
        'project_code' => 'SG-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Mask',
        'material_description' => 'Safety mask',
        'quantity' => 0,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'quantity' => 12,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => $officer->id,
    ]);

    $this->actingAs($officer)
        ->get(route('material-reports.index'))
        ->assertOk()
        ->assertSee('Site Gamma')
        ->assertSee('Mask')
        ->assertSee('12');

    $this->actingAs($officer)
        ->get(route('material-reports.show', $project))
        ->assertOk()
        ->assertSee('Total assigned balance')
        ->assertSee('12');
});

it('allows hse officer to add head office quantity', function () {
    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    $material = Material::create([
        'material_name' => 'Goggles',
        'material_description' => 'Safety goggles',
        'quantity' => 3,
    ]);

    $this->actingAs($officer)
        ->patch(route('material-quantities.update', $material), [
            'quantity_to_add' => 7,
        ])
        ->assertRedirect(route('material-quantities.index'));

    expect($material->fresh()->quantity)->toBe(10);
});

it('allows hse officer to deduct head office quantity and correctly recalculates balance', function () {
    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    // Material Helmet starts with 305 initial stock and quantity
    $material = Material::create([
        'material_name' => 'Helmet',
        'material_description' => 'Safety Helmet',
        'quantity' => 305,
    ]);

    expect($material->quantity)->toBe(305);
    expect($material->initial_stock)->toBe(305);

    // Subtract 137 using adjustment_type = subtract
    $this->actingAs($officer)
        ->patch(route('material-quantities.update', $material), [
            'quantity_to_add' => 137,
            'adjustment_type' => 'subtract',
        ])
        ->assertRedirect(route('material-quantities.index'))
        ->assertSessionHas('success');

    $material->refresh();

    // 305 - 137 = 168
    expect($material->quantity)->toBe(168);
    expect($material->initial_stock)->toBe(168);

    // In Head Office Report, Initial Stock = 168, Distributed = 0, Physical Available = 168
    $this->actingAs($officer)
        ->get(route('material-reports.head-office'))
        ->assertOk()
        ->assertSee('Helmet')
        ->assertSee('168');

    // Test direct negative adjustment number: -68
    $this->actingAs($officer)
        ->patch(route('material-quantities.update', $material), [
            'quantity_to_add' => -68,
        ])
        ->assertRedirect(route('material-quantities.index'));

    $material->refresh();
    // 168 - 68 = 100
    expect($material->quantity)->toBe(100);
    expect($material->initial_stock)->toBe(100);

    // Test preventing deduction exceeding available balance (e.g. trying to deduct 150 when only 100 available)
    $this->actingAs($officer)
        ->patch(route('material-quantities.update', $material), [
            'quantity_to_add' => 150,
            'adjustment_type' => 'subtract',
        ])
        ->assertSessionHasErrors(['quantity_to_add']);

    expect($material->fresh()->quantity)->toBe(100);
});

it('filters and paginates the head office material report', function () {
    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'Quarter One Site',
        'project_code' => 'Q1-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    foreach (range(1, 16) as $index) {
        $material = Material::create([
            'material_name' => sprintf('Helmet %02d', $index),
            'material_description' => 'Safety helmet',
            'quantity' => 20,
        ]);

        $assignment = MaterialProjectAssignment::create([
            'material_id' => $material->id,
            'project_id' => $project->id,
            'quantity' => 5,
            'receiver_id' => $siteOfficer->id,
            'assigned_by' => $officer->id,
        ]);

        $assignment->forceFill([
            'created_at' => '2026-02-15 09:00:00',
            'updated_at' => '2026-02-15 09:00:00',
        ])->save();
    }

    $excludedMaterial = Material::create([
        'material_name' => 'Boots',
        'material_description' => 'Safety boots',
        'quantity' => 10,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $excludedMaterial->id,
        'project_id' => $project->id,
        'quantity' => 3,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => $officer->id,
    ]);

    $this->actingAs($officer)
        ->get(route('material-reports.head-office', [
            'search' => 'Helmet',
            'project_id' => $project->id,
            'from_date' => '2026-01-01',
            'to_date' => '2026-03-31',
            'quarter' => 1,
        ]))
        ->assertOk()
        ->assertSee('Helmet 01')
        ->assertDontSee('Boots')
        ->assertViewHas('materials', function ($materials): bool {
            return $materials instanceof LengthAwarePaginator
                && $materials->total() === 16
                && $materials->count() === 15;
        });
});

it('filters and paginates the site material report', function () {
    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'Quarter Two Site',
        'project_code' => 'Q2-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $employee = Employee::create([
        'first_name' => 'Alex',
        'last_name' => 'Reed',
        'gender' => 'Male',
        'job_title' => 'Worker',
    ]);

    foreach (range(1, 16) as $index) {
        $material = Material::create([
            'material_name' => sprintf('Harness %02d', $index),
            'material_description' => 'Fall arrest harness',
            'quantity' => 0,
        ]);

        $assignment = MaterialProjectAssignment::create([
            'material_id' => $material->id,
            'project_id' => $project->id,
            'quantity' => 5,
            'receiver_id' => $siteOfficer->id,
            'assigned_by' => $officer->id,
        ]);

        $assignment->forceFill([
            'created_at' => '2026-05-15 09:00:00',
            'updated_at' => '2026-05-15 09:00:00',
        ])->save();

        MaterialEmployeeAssignment::create([
            'material_id' => $material->id,
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'quantity' => 2,
            'assigned_date' => '2026-05-20',
            'assigned_by' => $siteOfficer->id,
        ]);
    }

    $excludedMaterial = Material::create([
        'material_name' => 'Gloves',
        'material_description' => 'Safety gloves',
        'quantity' => 0,
    ]);

    MaterialProjectAssignment::create([
        'material_id' => $excludedMaterial->id,
        'project_id' => $project->id,
        'quantity' => 5,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => $officer->id,
    ]);

    $this->actingAs($officer)
        ->get(route('material-reports.site', [
            'search' => 'Harness',
            'project_id' => $project->id,
            'from_date' => '2026-04-01',
            'to_date' => '2026-06-30',
            'quarter' => 2,
        ]))
        ->assertOk()
        ->assertSee('Harness 01')
        ->assertSee('Q2-001')
        ->assertDontSee('Gloves')
        ->assertViewHas('materials', function ($materials): bool {
            return $materials instanceof LengthAwarePaginator
                && $materials->total() === 16
                && $materials->count() === 15;
        });
});

it('displays initial stock, total distributed, and physical available in head office report', function () {
    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    $project = Project::create([
        'project_name' => 'Project Alpha',
        'project_code' => 'PA-001',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $material = Material::create([
        'material_name' => 'Safety Gloves',
        'material_description' => 'Heavy duty gloves',
        'quantity' => 100,
    ]);

    expect($material->initial_stock)->toBe(100);

    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'quantity' => 30,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => $officer->id,
    ]);
    $material->deductQuantity(30);

    expect($material->fresh()->initial_stock)->toBe(100);

    // Add 50 units stock addition
    $material->addQuantity(50);
    $material->recordHistory('stock_added', 50, 'Added stock', $officer->id);

    // Initial stock pool increments with added quantity (100 + 50 = 150)
    expect($material->fresh()->initial_stock)->toBe(150);
    expect($material->fresh()->quantity)->toBe(120); // 70 + 50

    // PHYSICAL AVAILABLE = INITIAL STOCK (150) - TOTAL DISTRIBUTED (30) = 120
    expect($material->fresh()->getPhysicalAvailable(30))->toBe(120);

    $this->actingAs($officer)
        ->get(route('material-reports.head-office'))
        ->assertOk()
        ->assertSee('INITIAL STOCK')
        ->assertSee('Total Distributed')
        ->assertSee('PHYSICAL AVAILABLE')
        ->assertSee('Safety Gloves')
        ->assertSee('150')
        ->assertSee('30')
        ->assertSee('120');
});

it('recalculates base stock pool and prevents negative balances when updating head office quantity on edit', function () {
    $officer = requestTestHseOfficer();
    $siteOfficer = requestTestSiteOfficer();

    $project = Project::create([
        'project_name' => 'Project Beta',
        'project_code' => 'PB-002',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $unit = UnitOfMeasure::create(['name' => 'Pieces']);

    $material = Material::create([
        'material_name' => 'Dust Mask Special',
        'material_description' => 'Fine particle mask',
        'quantity' => 10,
        'unit_of_measure_id' => $unit->id,
    ]);

    // 40 units distributed to project
    MaterialProjectAssignment::create([
        'material_id' => $material->id,
        'project_id' => $project->id,
        'quantity' => 40,
        'receiver_id' => $siteOfficer->id,
        'assigned_by' => $officer->id,
    ]);

    // Material currently has quantity 10 in head office, and 40 distributed.
    // If user edits material from /materials/{id}/edit and sets Head Office Quantity to 25:
    $this->actingAs($officer)
        ->put(route('materials.update', $material), [
            'material_name' => 'Dust Mask Special',
            'material_description' => 'Fine particle mask',
            'quantity' => 25,
            'unit_of_measure_id' => $unit->id,
        ])
        ->assertRedirect(route('materials.index'));

    $material->refresh();
    expect($material->quantity)->toBe(25);
    // Base/initial stock pool is now 25 + 40 = 65
    expect($material->initial_stock)->toBe(65);

    // In the Head Office report, Physical Available = 65 - 40 = 25 (matches Head Office quantity exactly!)
    $response = $this->actingAs($officer)
        ->get(route('material-reports.head-office'));

    $response->assertOk()
        ->assertSee('Dust Mask Special')
        ->assertSee('65')
        ->assertSee('40')
        ->assertSee('25');

    // If user updates quantity to 0:
    $this->actingAs($officer)
        ->put(route('materials.update', $material), [
            'material_name' => 'Dust Mask Special',
            'material_description' => 'Fine particle mask',
            'quantity' => 0,
            'unit_of_measure_id' => $unit->id,
        ]);

    $material->refresh();
    expect($material->quantity)->toBe(0);
    // Base pool is 0 + 40 = 40
    expect($material->initial_stock)->toBe(40);

    // Report should show 0 available, never negative
    $response = $this->actingAs($officer)
        ->get(route('material-reports.head-office'));

    $response->assertOk()
        ->assertSee('Dust Mask Special')
        ->assertSee('40')
        ->assertSee('0');
});
