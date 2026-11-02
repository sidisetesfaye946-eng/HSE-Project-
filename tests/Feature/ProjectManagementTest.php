<?php

use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows hse officers to create a project for a site officer', function () {
    $hseOfficer = User::factory()->create();
    $hseOfficer->assignRole('HSE Officer');

    $siteOfficer = User::factory()->create([
        'name' => 'Supply Site Officer',
        'email' => 'site-officer@example.com',
    ]);
    $siteOfficer->assignRole('HSE Site Officer');

    $this->actingAs($hseOfficer)
        ->get(route('projects.create'))
        ->assertOk()
        ->assertSee('Supply Site Officer')
        ->assertSee('HSE Site Officer');

    $this->actingAs($hseOfficer)
        ->post(route('projects.store'), [
            'project_name' => 'Supply Project',
            'project_code' => 'SUP-001',
            'site_officer_id' => $siteOfficer->id,
        ])
        ->assertRedirect(route('projects.index'));

    expect(Project::query()->where('project_code', 'SUP-001')->first())
        ->site_officer_id->toBe($siteOfficer->id);
});

it('allows search projects by first letter for hse officer', function () {
    $hseOfficer = User::factory()->create();
    $hseOfficer->assignRole('HSE Officer');

    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    Project::create([
        'project_name' => 'Antigravity Project',
        'project_code' => 'ANT-01',
        'site_officer_id' => $siteOfficer->id,
    ]);

    Project::create([
        'project_name' => 'Beta Project',
        'project_code' => 'BET-01',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $response = $this->actingAs($hseOfficer)
        ->get(route('projects.index', ['search' => 'A']))
        ->assertOk()
        ->assertSee('Antigravity Project')
        ->assertDontSee('Beta Project');
});

it('allows search projects by first letter for site officer', function () {
    $siteOfficer = User::factory()->create();
    $siteOfficer->assignRole('HSE Site Officer');

    Project::create([
        'project_name' => 'Alpha Project',
        'project_code' => 'ALP-01',
        'site_officer_id' => $siteOfficer->id,
    ]);

    Project::create([
        'project_name' => 'Gamma Project',
        'project_code' => 'GAM-01',
        'site_officer_id' => $siteOfficer->id,
    ]);

    $response = $this->actingAs($siteOfficer)
        ->get(route('site-officer.projects.index', ['search' => 'A']))
        ->assertOk()
        ->assertSee('Alpha Project')
        ->assertDontSee('Gamma Project');
});
