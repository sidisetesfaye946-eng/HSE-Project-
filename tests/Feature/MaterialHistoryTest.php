<?php

use App\Models\Material;
use App\Models\MaterialHistory;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows hse officer to view material history', function () {
    $uom = UnitOfMeasure::firstOrCreate(['name' => 'Pcs']);

    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    $material = Material::create([
        'material_name' => 'Gloves',
        'material_description' => 'Protective gloves',
        'quantity' => 10,
        'unit_of_measure_id' => $uom->id,
    ]);

    $material->recordHistory('created', 10, 'Initial balance entered.', $officer->id);

    $this->actingAs($officer)
        ->get(route('material-histories.index'))
        ->assertOk()
        ->assertSee('Material History')
        ->assertSee('Gloves')
        ->assertSee('Initial balance entered.');
});

it('denies non hse officer from viewing material history', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $this->actingAs($user)
        ->get(route('material-histories.index'))
        ->assertForbidden();
});

it('exports material history to excel and pdf', function () {
    $uom = UnitOfMeasure::firstOrCreate(['name' => 'Pcs']);

    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    $material = Material::create([
        'material_name' => 'Boots',
        'material_description' => 'Safety boots',
        'quantity' => 20,
        'unit_of_measure_id' => $uom->id,
    ]);

    $material->recordHistory('created', 20, 'Initial stock created.', $officer->id);

    $this->actingAs($officer)
        ->get(route('material-histories.export', ['format' => 'xlsx']))
        ->assertOk()
        ->assertHeaderContains('content-type', 'application/vnd.ms-excel');

    $this->actingAs($officer)
        ->get(route('material-histories.export', ['format' => 'pdf']))
        ->assertOk()
        ->assertHeaderContains('content-type', 'application/pdf');
});

it('paginates 15 rows per page by default without filters', function () {
    $uom = UnitOfMeasure::firstOrCreate(['name' => 'Pcs']);
    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    $material = Material::create([
        'material_name' => 'Gloves',
        'material_description' => 'Protective gloves',
        'quantity' => 10,
        'unit_of_measure_id' => $uom->id,
    ]);

    for ($i = 0; $i < 20; $i++) {
        MaterialHistory::create([
            'material_id' => $material->id,
            'event_type' => 'stock_added',
            'quantity_change' => 1,
            'balance_before' => $i,
            'balance_after' => $i + 1,
            'description' => "Record {$i}",
            'created_by' => $officer->id,
        ]);
    }

    $response = $this->actingAs($officer)
        ->get(route('material-histories.index'))
        ->assertOk();

    $histories = $response->viewData('histories');
    expect(count($histories))->toBe(15);
    expect($histories->total())->toBe(20);
});

it('filters material history by date', function () {
    $uom = UnitOfMeasure::firstOrCreate(['name' => 'Pcs']);
    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    $material = Material::create([
        'material_name' => 'Gloves',
        'material_description' => 'Protective gloves',
        'quantity' => 10,
        'unit_of_measure_id' => $uom->id,
    ]);

    $hToday = MaterialHistory::create([
        'material_id' => $material->id,
        'event_type' => 'stock_added',
        'quantity_change' => 1,
        'balance_before' => 0,
        'balance_after' => 1,
        'description' => "Today's record",
        'created_by' => $officer->id,
    ]);
    $hToday->created_at = now();
    $hToday->save();

    $hPast = MaterialHistory::create([
        'material_id' => $material->id,
        'event_type' => 'stock_added',
        'quantity_change' => 1,
        'balance_before' => 1,
        'balance_after' => 2,
        'description' => 'Past record',
        'created_by' => $officer->id,
    ]);
    $hPast->created_at = now()->subDays(5);
    $hPast->save();

    $response = $this->actingAs($officer)
        ->get(route('material-histories.index', ['date' => now()->format('Y-m-d')]))
        ->assertOk()
        ->assertSee("Today's record")
        ->assertDontSee('Past record');
});

it('filters material history by quarter', function () {
    $uom = UnitOfMeasure::firstOrCreate(['name' => 'Pcs']);
    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    $material = Material::create([
        'material_name' => 'Gloves',
        'material_description' => 'Protective gloves',
        'quantity' => 10,
        'unit_of_measure_id' => $uom->id,
    ]);

    $year = (int) date('Y');

    $hQ1 = MaterialHistory::create([
        'material_id' => $material->id,
        'event_type' => 'stock_added',
        'quantity_change' => 1,
        'balance_before' => 0,
        'balance_after' => 1,
        'description' => 'Q1 record',
        'created_by' => $officer->id,
    ]);
    $hQ1->created_at = Carbon::create($year, 2, 15, 12, 0, 0);
    $hQ1->save();

    $hQ2 = MaterialHistory::create([
        'material_id' => $material->id,
        'event_type' => 'stock_added',
        'quantity_change' => 1,
        'balance_before' => 1,
        'balance_after' => 2,
        'description' => 'Q2 record',
        'created_by' => $officer->id,
    ]);
    $hQ2->created_at = Carbon::create($year, 5, 15, 12, 0, 0);
    $hQ2->save();

    $response = $this->actingAs($officer)
        ->get(route('material-histories.index', ['quarter' => 'Q1', 'year' => $year]))
        ->assertOk()
        ->assertSee('Q1 record')
        ->assertDontSee('Q2 record');
});
