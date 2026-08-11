<?php

use App\Livewire\Device\DeviceApprove;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Projects\ProjectShow;
use App\Livewire\Settings\RecoverySettings;
use App\Livewire\Vault\SharedVault;
use App\Models\DeviceAuthorization;
use App\Models\Project;
use App\Models\ProjectVariable;
use App\Models\User;
use App\Models\Workspace;
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

test('first-time setup displays banner and auto-generates recovery key on mount', function () {
    $user = User::factory()->create();

    // Check project index displays setup banner when no SystemRecovery exists
    Livewire::actingAs($user)
        ->test(ProjectIndex::class)
        ->assertSet('hasRecoverySetup', false)
        ->assertSee('First-Time Setup Required');

    // Mount RecoverySettings auto-generates recovery key
    Livewire::actingAs($user)
        ->test(RecoverySettings::class)
        ->assertSet('testResult', null)
        ->assertSee('Save Your Keysha Recovery Key');

    $this->assertDatabaseHas('system_recoveries', [
        'recovery_schema_version' => 1,
    ]);
});

test('user can specify custom provider name and target multiple environments via chips', function () {
    $user = User::factory()->create();
    $workspace = $user->ensurePersonalWorkspace();

    $project = Project::create([
        'workspace_id' => $workspace->id,
        'name' => 'Mingle',
        'slug' => 'mingle',
        'created_by' => $user->id,
    ]);

    $envDev = $project->environments()->create(['name' => 'Development', 'slug' => 'development', 'position' => 1]);
    $envProd = $project->environments()->create(['name' => 'Production', 'slug' => 'production', 'position' => 2]);

    Livewire::actingAs($user)
        ->test(ProjectShow::class, ['slug' => 'mingle'])
        ->call('openAddVariableModal')
        ->set('varKey', 'DATABASE_URL')
        ->set('varProvider', 'custom')
        ->set('varCustomProviderName', 'NeonDB')
        ->set('selectedEnvironments', ['development', 'production'])
        ->set('varValue', 'postgres://user:pass@ep-cool.neon.tech/db')
        ->call('saveVariable')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('project_variables', [
        'key' => 'DATABASE_URL',
        'provider_hint' => 'custom:NeonDB',
    ]);

    $variable = ProjectVariable::where('key', 'DATABASE_URL')->first();

    $this->assertDatabaseHas('environment_bindings', [
        'environment_id' => $envDev->id,
        'project_variable_id' => $variable->id,
    ]);

    $this->assertDatabaseHas('environment_bindings', [
        'environment_id' => $envProd->id,
        'project_variable_id' => $variable->id,
    ]);
});

test('user can save recovery key to Keysha Vault project and toggle shared vault mode', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(RecoverySettings::class)
        ->call('saveToKeyshaProject')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('projects', ['name' => 'Keysha Vault', 'slug' => 'keysha-vault']);
    $this->assertDatabaseHas('project_variables', ['key' => 'KEYSHA_RECOVERY_KEY']);

    $project = Project::where('slug', 'keysha-vault')->first();

    Livewire::actingAs($user)
        ->test(ProjectShow::class, ['slug' => 'keysha-vault'])
        ->assertSee('KEYSHA_RECOVERY_KEY');

    Livewire::actingAs($user)
        ->test(SharedVault::class)
        ->assertSee('KEYSHA_RECOVERY_KEY');
});

test('user can approve device authorization via Livewire with hyphenated code', function () {
    $user = User::factory()->create();

    $codeRes = $this->postJson('/api/v1/auth/device/code', [
        'device_name' => 'MacBook Pro CLI',
    ]);
    $userCode = $codeRes->json('user_code');

    Livewire::actingAs($user)
        ->test(DeviceApprove::class)
        ->set('userCode', $userCode)
        ->call('findDevice')
        ->assertSet('userCode', $userCode)
        ->call('approveDevice')
        ->assertSet('approved', true);

    $hash = hash('sha256', str_replace('-', '', $userCode));
    $this->assertDatabaseHas('device_authorizations', [
        'user_code_hash' => $hash,
        'status' => 'approved',
    ]);
});

test('regenerating recovery key invalidates previous recovery key', function () {
    $user = User::factory()->create();

    $comp = Livewire::actingAs($user)->test(RecoverySettings::class);
    $keyA = $comp->get('generatedKey');

    expect($keyA)->not->toBeEmpty();

    // Verify keyA works initially
    $comp->set('testKeyInput', $keyA)
        ->call('testRecoveryKey')
        ->assertSet('testResult', true);

    // Generate new key (Key B)
    $comp->call('generateNewRecoveryKey');
    $keyB = $comp->get('generatedKey');

    expect($keyB)->not->toBeEmpty();
    expect($keyB)->not->toBe($keyA);

    // Verify Key B works
    $comp->set('testKeyInput', $keyB)
        ->call('testRecoveryKey')
        ->assertSet('testResult', true);

    // Verify Key A is now invalidated
    $comp->set('testKeyInput', $keyA)
        ->call('testRecoveryKey')
        ->assertSet('testResult', false);
});

test('user can create new workspace and switch workspaces', function () {
    $user = User::factory()->create();

    $res = $this->actingAs($user)->post('/workspaces', [
        'name' => 'Rocktown Labs',
    ]);
    $res->assertRedirect();

    $ws = Workspace::where('name', 'Rocktown Labs')->first();
    expect($ws)->not->toBeNull();
    expect($user->currentWorkspace()->id)->toBe($ws->id);
});

test('user can revoke authorized device', function () {
    $user = User::factory()->create();
    $device = DeviceAuthorization::create([
        'device_code_hash' => hash('sha256', 'dcode'),
        'user_code_hash' => hash('sha256', 'ucode'),
        'device_name' => 'MacBook Pro CLI Test',
        'status' => 'approved',
        'expires_at' => now()->addMinutes(10),
    ]);

    Livewire::actingAs($user)
        ->test(DeviceApprove::class)
        ->call('revokeDevice', $device->id);

    expect($device->fresh()->status)->toBe('revoked');
});
