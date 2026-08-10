<?php

use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Projects\ProjectShow;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('user can view project index and create project', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ProjectIndex::class)
        ->set('name', 'GigStax')
        ->set('description', 'Gig platform')
        ->call('createProject')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('projects', ['name' => 'GigStax']);
});

test('user can manage variables and dotenv import in project show', function () {
    $user = User::factory()->create();
    $workspace = $user->ensurePersonalWorkspace();

    $project = Project::create([
        'workspace_id' => $workspace->id,
        'name' => 'SoundKit',
        'slug' => 'soundkit',
        'created_by' => $user->id,
    ]);

    $project->environments()->create([
        'name' => 'Production',
        'slug' => 'production',
        'position' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(ProjectShow::class, ['slug' => 'soundkit'])
        ->set('varKey', 'RESEND_API_KEY')
        ->set('varValue', 're_123456789')
        ->call('saveVariable')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('project_variables', ['key' => 'RESEND_API_KEY']);
});
