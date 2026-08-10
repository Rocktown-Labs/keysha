<?php

namespace App\Livewire\Projects;

use App\Crypto\ProviderRegistry;
use App\Crypto\VaultEngine;
use App\Models\Environment;
use App\Models\EnvironmentBinding;
use App\Models\Project;
use App\Models\ProjectVariable;
use App\Models\VaultEntry;
use App\Services\AuditService;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ProjectShow extends Component
{
    public string $slug;

    public string $activeTab = 'variables'; // variables, diff, template

    public string $activeEnvSlug = 'production';

    // Variable Modal state
    public bool $showVariableModal = false;

    public ?string $editingVariableId = null;

    public string $varKey = '';

    public string $varClassification = 'secret';

    public string $varProvider = 'custom';

    public string $varValue = '';

    public string $varDescription = '';

    public string $varSharingMode = 'restricted';

    // Dotenv Import Modal state
    public bool $showImportModal = false;

    public string $importDotenvText = '';

    public array $importParsedItems = [];

    // Secret Reveal & Copy State
    public ?string $revealedSecretValue = null;

    public ?string $revealedVariableId = null;

    // Step-up unlock modal state
    public bool $showUnlockModal = false;

    public string $unlockPassword = '';

    // History Modal state
    public bool $showHistoryModal = false;

    public ?ProjectVariable $historyVariable = null;

    public function mount(string $slug)
    {
        $this->slug = $slug;
        $project = $this->getProject();
        if ($project->environments->isNotEmpty()) {
            $this->activeEnvSlug = $project->environments->first()->slug;
        }
    }

    public function updatedVarKey($value)
    {
        if (! empty($value) && ! $this->editingVariableId) {
            $this->varProvider = ProviderRegistry::detectProvider($value);
            $this->varClassification = ProviderRegistry::classifyKey($value);
        }
    }

    public function getProject(): Project
    {
        $user = auth()->user();
        $workspace = $user->personalWorkspace();

        return Project::where('workspace_id', $workspace->id)
            ->where('slug', $this->slug)
            ->with(['environments.bindings.vaultEntry.currentVersion', 'variables'])
            ->firstOrFail();
    }

    public function getActiveEnvironment(): Environment
    {
        $project = $this->getProject();

        return $project->environments->firstWhere('slug', $this->activeEnvSlug)
            ?? $project->environments->first();
    }

    public function isUnlocked(): bool
    {
        $unlockedUntil = session('vault_unlocked_until');

        return $unlockedUntil && now()->timestamp < $unlockedUntil;
    }

    public function unlockVault()
    {
        $this->validate([
            'unlockPassword' => 'required|string',
        ]);

        $user = auth()->user();
        if (! Hash::check($this->unlockPassword, $user->password)) {
            $this->addError('unlockPassword', 'Incorrect account password.');

            return;
        }

        // Unlock session for 15 minutes
        session(['vault_unlocked_until' => now()->addMinutes(15)->timestamp]);

        $this->reset(['unlockPassword', 'showUnlockModal']);
        session()->flash('message', 'Vault unlocked for 15 minutes.');
    }

    public function openAddVariableModal()
    {
        $this->reset(['editingVariableId', 'varKey', 'varClassification', 'varProvider', 'varValue', 'varDescription', 'varSharingMode']);
        $this->varClassification = 'secret';
        $this->varProvider = 'custom';
        $this->showVariableModal = true;
    }

    public function openEditVariableModal(string $variableId)
    {
        $variable = ProjectVariable::findOrFail($variableId);
        $this->editingVariableId = $variable->id;
        $this->varKey = $variable->key;
        $this->varClassification = $variable->classification;
        $this->varProvider = $variable->provider_hint ?? 'custom';
        $this->varDescription = $variable->description ?? '';
        $this->varValue = ''; // Don't prepopulate secret values

        $this->showVariableModal = true;
    }

    public function saveVariable(VaultEngine $vault, AuditService $audit)
    {
        $this->validate([
            'varKey' => 'required|string|max:255',
            'varClassification' => 'required|in:secret,config',
            'varProvider' => 'required|string',
            'varValue' => 'nullable|string',
            'varDescription' => 'nullable|string',
        ]);

        $user = auth()->user();
        $workspace = $user->personalWorkspace();
        $project = $this->getProject();
        $environment = $this->getActiveEnvironment();

        if ($this->editingVariableId) {
            $variable = ProjectVariable::findOrFail($this->editingVariableId);
            $variable->update([
                'key' => strtoupper(trim($this->varKey)),
                'classification' => $this->varClassification,
                'provider_hint' => $this->varProvider,
                'description' => $this->varDescription,
            ]);
        } else {
            $variable = ProjectVariable::create([
                'project_id' => $project->id,
                'key' => strtoupper(trim($this->varKey)),
                'classification' => $this->varClassification,
                'provider_hint' => $this->varProvider,
                'description' => $this->varDescription,
                'required' => true,
                'position' => $project->variables()->count() + 1,
                'created_by' => $user->id,
            ]);
        }

        // If value provided, create or update vault entry version & binding for active env
        if ($this->varValue !== '') {
            $binding = EnvironmentBinding::where('environment_id', $environment->id)
                ->where('project_variable_id', $variable->id)
                ->first();

            if ($binding) {
                $vaultEntry = $binding->vaultEntry;
            } else {
                $vaultEntry = VaultEntry::create([
                    'workspace_id' => $workspace->id,
                    'label' => "{$project->name} / {$variable->key}",
                    'classification' => $variable->classification,
                    'sharing_mode' => $this->varSharingMode,
                    'created_by' => $user->id,
                ]);

                $binding = EnvironmentBinding::create([
                    'environment_id' => $environment->id,
                    'project_variable_id' => $variable->id,
                    'vault_entry_id' => $vaultEntry->id,
                    'created_by' => $user->id,
                ]);
            }

            $version = $vault->encryptSecret($workspace, $vaultEntry, $this->varValue, $user);

            $audit->log(
                workspace: $workspace,
                event: 'variable.created',
                actor: $user,
                subjectType: ProjectVariable::class,
                subjectId: $variable->id,
                projectId: $project->id,
                environmentId: $environment->id,
                metadata: ['key' => $variable->key, 'classification' => $variable->classification]
            );
        }

        $this->reset(['showVariableModal', 'editingVariableId', 'varKey', 'varValue', 'varDescription']);
        session()->flash('message', "Variable '{$variable->key}' saved.");
    }

    public function revealSecret(string $variableId, VaultEngine $vault, AuditService $audit)
    {
        if (! $this->isUnlocked()) {
            $this->showUnlockModal = true;

            return;
        }

        $user = auth()->user();
        $workspace = $user->personalWorkspace();
        $environment = $this->getActiveEnvironment();
        $variable = ProjectVariable::findOrFail($variableId);

        $binding = EnvironmentBinding::where('environment_id', $environment->id)
            ->where('project_variable_id', $variable->id)
            ->first();

        if (! $binding || ! $binding->vaultEntry || ! $binding->vaultEntry->currentVersion) {
            return;
        }

        $plaintext = $vault->decryptSecret($binding->vaultEntry->currentVersion, $workspace);
        $this->revealedSecretValue = $plaintext;
        $this->revealedVariableId = $variable->id;

        $audit->log(
            workspace: $workspace,
            event: 'secret.revealed',
            actor: $user,
            subjectType: ProjectVariable::class,
            subjectId: $variable->id,
            projectId: $this->getProject()->id,
            environmentId: $environment->id,
            metadata: ['key' => $variable->key]
        );
    }

    public function hideSecret()
    {
        $this->revealedSecretValue = null;
        $this->revealedVariableId = null;
    }

    public function deleteVariable(string $variableId, AuditService $audit)
    {
        $user = auth()->user();
        $workspace = $user->personalWorkspace();
        $project = $this->getProject();
        $variable = ProjectVariable::findOrFail($variableId);

        $key = $variable->key;
        $variable->delete();

        $audit->log(
            workspace: $workspace,
            event: 'variable.deleted',
            actor: $user,
            subjectType: ProjectVariable::class,
            subjectId: $variableId,
            projectId: $project->id,
            metadata: ['key' => $key]
        );

        session()->flash('message', "Variable '{$key}' deleted.");
    }

    public function parseImportDotenv()
    {
        $lines = explode("\n", $this->importDotenvText);
        $parsed = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = substr($line, 7);
            }

            if (! str_contains($line, '=')) {
                continue;
            }

            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);

            // Strip quotes if present
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }

            if (empty($key)) {
                continue;
            }

            $provider = ProviderRegistry::detectProvider($key);
            $classification = ProviderRegistry::classifyKey($key);

            $parsed[] = [
                'key' => strtoupper($key),
                'value' => $val,
                'provider' => $provider,
                'classification' => $classification,
                'import' => true,
            ];
        }

        $this->importParsedItems = $parsed;
    }

    public function commitImport(VaultEngine $vault, AuditService $audit)
    {
        $user = auth()->user();
        $workspace = $user->personalWorkspace();
        $project = $this->getProject();
        $environment = $this->getActiveEnvironment();

        $importedCount = 0;
        foreach ($this->importParsedItems as $item) {
            if (! ($item['import'] ?? true)) {
                continue;
            }

            $key = strtoupper(trim($item['key']));
            $variable = ProjectVariable::firstOrCreate(
                ['project_id' => $project->id, 'key' => $key],
                [
                    'classification' => $item['classification'],
                    'provider_hint' => $item['provider'],
                    'required' => true,
                    'position' => $project->variables()->count() + 1,
                    'created_by' => $user->id,
                ]
            );

            if (! empty($item['value'])) {
                $vaultEntry = VaultEntry::create([
                    'workspace_id' => $workspace->id,
                    'label' => "{$project->name} / {$key}",
                    'classification' => $variable->classification,
                    'sharing_mode' => 'restricted',
                    'created_by' => $user->id,
                ]);

                EnvironmentBinding::updateOrCreate(
                    ['environment_id' => $environment->id, 'project_variable_id' => $variable->id],
                    ['vault_entry_id' => $vaultEntry->id, 'created_by' => $user->id]
                );

                $vault->encryptSecret($workspace, $vaultEntry, $item['value'], $user);
                $importedCount++;
            }
        }

        $audit->log(
            workspace: $workspace,
            event: 'variable.imported',
            actor: $user,
            projectId: $project->id,
            environmentId: $environment->id,
            metadata: ['count' => $importedCount]
        );

        $this->reset(['showImportModal', 'importDotenvText', 'importParsedItems']);
        session()->flash('message', "Imported {$importedCount} variables into {$environment->name}.");
    }

    public function render()
    {
        $project = $this->getProject();
        $activeEnv = $this->getActiveEnvironment();

        // Calculate completeness
        $expectedCount = $project->variables->count();
        $configuredCount = 0;
        $missingKeys = [];

        foreach ($project->variables as $var) {
            $binding = $activeEnv->bindings->firstWhere('project_variable_id', $var->id);
            if ($binding && $binding->vaultEntry && $binding->vaultEntry->current_version_id) {
                $configuredCount++;
            } else {
                $missingKeys[] = $var->key;
            }
        }
        $missingCount = count($missingKeys);

        return view('livewire.projects.project-show', [
            'project' => $project,
            'activeEnv' => $activeEnv,
            'expectedCount' => $expectedCount,
            'configuredCount' => $configuredCount,
            'missingCount' => $missingCount,
            'missingKeys' => $missingKeys,
            'providers' => ProviderRegistry::PROVIDERS,
        ])->layout('layouts.app', ['title' => $project->name]);
    }
}
