<?php

namespace App\Livewire\Settings;

use App\Crypto\EnvironmentMasterKeyProvider;
use App\Crypto\RecoveryKey;
use App\Crypto\VaultEngine;
use App\Models\EnvironmentBinding;
use App\Models\Project;
use App\Models\ProjectVariable;
use App\Models\SystemRecovery;
use App\Models\VaultEntry;
use App\Services\AuditService;
use Livewire\Component;

class RecoverySettings extends Component
{
    public string $testKeyInput = '';

    public ?bool $testResult = null;

    public ?string $generatedKey = null;

    public function mount()
    {
        if (! SystemRecovery::exists()) {
            $this->generateNewRecoveryKey();
        }
    }

    public function generateNewRecoveryKey()
    {
        $provider = new EnvironmentMasterKeyProvider;
        $recovery = new RecoveryKey($provider);

        $newKey = $recovery->generate();
        $recovery->initializeRecovery($newKey);

        $this->generatedKey = $newKey;
        session()->flash('message', 'New recovery key generated and master key backup stored.');
    }

    public function saveToKeyshaProject(VaultEngine $vault, AuditService $audit)
    {
        if (empty($this->generatedKey)) {
            return;
        }

        $user = auth()->user();
        $workspace = $user->currentWorkspace();

        $project = Project::firstOrCreate(
            ['workspace_id' => $workspace->id, 'slug' => 'keysha-vault'],
            [
                'name' => 'Keysha Vault',
                'description' => 'System & Master Key Recovery Configuration Vault',
                'created_by' => $user->id,
            ]
        );

        if ($project->environments()->count() === 0) {
            $project->environments()->createMany([
                ['name' => 'Development', 'slug' => 'development', 'position' => 1, 'protected' => false],
                ['name' => 'Preview', 'slug' => 'preview', 'position' => 2, 'protected' => false],
                ['name' => 'Production', 'slug' => 'production', 'position' => 3, 'protected' => true],
            ]);
        }

        $variable = ProjectVariable::firstOrCreate(
            ['project_id' => $project->id, 'key' => 'KEYSHA_RECOVERY_KEY'],
            [
                'classification' => 'secret',
                'provider_hint' => 'custom:Keysha',
                'description' => 'Master recovery key',
                'required' => true,
                'position' => 1,
                'created_by' => $user->id,
            ]
        );

        foreach ($project->environments as $environment) {
            $binding = EnvironmentBinding::where('environment_id', $environment->id)
                ->where('project_variable_id', $variable->id)
                ->first();

            if ($binding && $binding->vaultEntry) {
                $vaultEntry = $binding->vaultEntry;
            } else {
                $vaultEntry = VaultEntry::create([
                    'workspace_id' => $workspace->id,
                    'label' => "KEYSHA_RECOVERY_KEY [{$environment->name}]",
                    'classification' => 'secret',
                    'sharing_mode' => 'shared',
                    'created_by' => $user->id,
                ]);

                EnvironmentBinding::updateOrCreate(
                    ['environment_id' => $environment->id, 'project_variable_id' => $variable->id],
                    ['vault_entry_id' => $vaultEntry->id, 'created_by' => $user->id]
                );
            }

            $vault->encryptSecret($workspace, $vaultEntry, $this->generatedKey, $user);
        }

        // Clean up any orphaned recovery vault entries without bindings
        VaultEntry::where('workspace_id', $workspace->id)
            ->where('label', 'like', '%KEYSHA_RECOVERY_KEY%')
            ->whereDoesntHave('bindings')
            ->delete();

        session()->flash('message', "✓ Successfully saved KEYSHA_RECOVERY_KEY into 'Keysha Vault' project across all environments!");
    }

    public function testRecoveryKey()
    {
        $this->validate([
            'testKeyInput' => 'required|string',
        ]);

        $provider = new EnvironmentMasterKeyProvider;
        $recovery = new RecoveryKey($provider);

        $this->testResult = $recovery->verifyRecoveryKey($this->testKeyInput);
    }

    public function render()
    {
        $systemRecovery = SystemRecovery::first();

        return view('livewire.settings.recovery-settings', [
            'systemRecovery' => $systemRecovery,
            'generatedKey' => $this->generatedKey,
        ])->layout('layouts.app', ['title' => 'Vault Recovery Settings']);
    }
}
