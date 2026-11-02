<?php

use App\Models\Material;
use App\Models\MaterialHistory;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('lets hse officers search manage materials', function () {
    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    $uom = UnitOfMeasure::firstOrCreate(['name' => 'Pcs']);

    Material::create([
        'material_name' => 'Safety Helmet',
        'material_description' => 'Head protection',
        'quantity' => 12,
        'unit_of_measure_id' => $uom->id,
    ]);

    Material::create([
        'material_name' => 'Rubber Gloves',
        'material_description' => 'Hand protection',
        'quantity' => 8,
        'unit_of_measure_id' => $uom->id,
    ]);

    $this->actingAs($officer)
        ->get(route('materials.index', ['search' => 'helmet']))
        ->assertOk()
        ->assertSee('Search Materials')
        ->assertSee('@input.debounce.350ms', false)
        ->assertSee('Safety Helmet')
        ->assertDontSee('Rubber Gloves');
});

it('lets hse officers search add quantity materials', function () {
    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    Material::create([
        'material_name' => 'Face Shield',
        'material_description' => 'Eye and face protection',
        'quantity' => 5,
    ]);

    Material::create([
        'material_name' => 'Ear Plugs',
        'material_description' => 'Hearing protection',
        'quantity' => 15,
    ]);

    $this->actingAs($officer)
        ->get(route('material-quantities.index', ['search' => 'shield']))
        ->assertOk()
        ->assertSee('Search Materials')
        ->assertSee('@input.debounce.350ms', false)
        ->assertSee('Face Shield')
        ->assertDontSee('Ear Plugs');
});

it('lets hse officers search material history by material', function () {
    $officer = User::factory()->create();
    $officer->assignRole('HSE Officer');

    $helmet = Material::create([
        'material_name' => 'Welding Helmet',
        'material_description' => 'Welding face protection',
        'quantity' => 10,
    ]);

    $boots = Material::create([
        'material_name' => 'Safety Boots',
        'material_description' => 'Foot protection',
        'quantity' => 10,
    ]);

    MaterialHistory::create([
        'material_id' => $helmet->id,
        'event_type' => 'stock_added',
        'quantity_change' => 3,
        'balance_before' => 7,
        'balance_after' => 10,
        'description' => 'Helmet stock added.',
        'created_by' => $officer->id,
    ]);

    MaterialHistory::create([
        'material_id' => $boots->id,
        'event_type' => 'stock_added',
        'quantity_change' => 2,
        'balance_before' => 8,
        'balance_after' => 10,
        'description' => 'Boot stock added.',
        'created_by' => $officer->id,
    ]);

    $this->actingAs($officer)
        ->get(route('material-histories.index', ['search' => 'welding']))
        ->assertOk()
        ->assertSee('Search Materials')
        ->assertSee('@input.debounce.350ms', false)
        ->assertSee('Welding Helmet')
        ->assertSee('Helmet stock added.')
        ->assertDontSee('Safety Boots')
        ->assertDontSee('Boot stock added.');
});
