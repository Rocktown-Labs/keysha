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

    public string $varCustomProviderName = '';

    public array $selectedEnvironments = [];

    public string $varValue = '';

    public string $varDescription = '';

    public string $varSharingMode = 'restricted';

    // Missing Keys Modal state
    public bool $showMissingModal = false;

    // Inspect Variable Details Modal state
    public bool $showInspectModal = false;

    public ?ProjectVariable $inspectVariable = null;

    // Dotenv Import Drawer / Modal state
    public bool $showImportModal = false;

    public string $importDotenvText = '';

    public array $importParsedItems = [];

    public array $importSelectedEnvironments = [];

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
            $detected = ProviderRegistry::detectProvider($value);
            $this->varProvider = $detected;
            $this->varClassification = ProviderRegistry::classifyKey($value);

            if ($detected === 'custom' && empty($this->varCustomProviderName)) {
                $this->varCustomProviderName = '';
            }
        }
    }

    public function toggleEnvironment(string $slug)
    {
        if (in_array($slug, $this->selectedEnvironments)) {
            $this->selectedEnvironments = array_values(array_diff($this->selectedEnvironments, [$slug]));
        } else {
            $this->selectedEnvironments[] = $slug;
        }
    }

    public function selectAllEnvironments()
    {
        $project = $this->getProject();
        $this->selectedEnvironments = $project->environments->pluck('slug')->toArray();
    }

    public function clearEnvironments()
    {
        $this->selectedEnvironments = [];
    }

    public function formatProviderName(string $providerHint): string
    {
        if (str_starts_with($providerHint, 'custom:')) {
            return substr($providerHint, 7);
        }

        if (array_key_exists($providerHint, ProviderRegistry::PROVIDERS)) {
            return ProviderRegistry::PROVIDERS[$providerHint]['name'];
        }

        return ucfirst($providerHint);
    }

    public function getProject(): Project
    {
        $user = auth()->user();
        $workspace = $user->currentWorkspace();

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

    public function lockVault()
    {
        session()->forget('vault_unlocked_until');
        $this->revealedSecretValue = null;
        $this->revealedVariableId = null;
        session()->flash('message', 'Vault session locked.');
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
        $this->reset(['editingVariableId', 'varKey', 'varClassification', 'varProvider', 'varCustomProviderName', 'varValue', 'varDescription', 'varSharingMode']);
        $this->varClassification = 'secret';
        $this->varProvider = 'custom';

        $project = $this->getProject();
        $this->selectedEnvironments = $project->environments->pluck('slug')->toArray();

        $this->showVariableModal = true;
    }

    public function openEditVariableModal(string $variableId)
    {
        $variable = ProjectVariable::findOrFail($variableId);
        $this->editingVariableId = $variable->id;
        $this->varKey = $variable->key;
        $this->varClassification = $variable->classification;

        $provider = $variable->provider_hint ?? 'custom';
        if (str_starts_with($provider, 'custom:')) {
            $this->varProvider = 'custom';
            $this->varCustomProviderName = substr($provider, 7);
        } elseif (array_key_exists($provider, ProviderRegistry::PROVIDERS)) {
            $this->varProvider = $provider;
            $this->varCustomProviderName = '';
        } else {
            $this->varProvider = 'custom';
            $this->varCustomProviderName = $provider;
        }

        $this->varDescription = $variable->description ?? '';
        $this->varValue = ''; // Don't prepopulate secret values

        $project = $this->getProject();
        $boundEnvIds = EnvironmentBinding::where('project_variable_id', $variable->id)
            ->pluck('environment_id')
            ->toArray();

        $this->selectedEnvironments = $project->environments
            ->whereIn('id', $boundEnvIds)
            ->pluck('slug')
            ->toArray();

        if (empty($this->selectedEnvironments)) {
            $this->selectedEnvironments = [$this->activeEnvSlug];
        }

        $this->showVariableModal = true;
    }

    public function openInspectModal(string $variableId)
    {
        $this->inspectVariable = ProjectVariable::with(['bindings.environment', 'bindings.vaultEntry'])->findOrFail($variableId);
        $this->showInspectModal = true;
    }

    public function toggleShareVariable(string $variableId)
    {
        $variable = ProjectVariable::findOrFail($variableId);
        $bindings = EnvironmentBinding::where('project_variable_id', $variable->id)->get();

        $newMode = 'shared';
        foreach ($bindings as $binding) {
            if ($binding->vaultEntry && $binding->vaultEntry->sharing_mode === 'shared') {
                $newMode = 'restricted';
                break;
            }
        }

        foreach ($bindings as $binding) {
            if ($binding->vaultEntry) {
                $binding->vaultEntry->update(['sharing_mode' => $newMode]);
            }
        }

        $label = $newMode === 'shared' ? 'Shared Vault' : 'Project Restricted';
        session()->flash('message', "Variable '{$variable->key}' sharing mode set to {$label}.");
    }

    public function saveVariable(VaultEngine $vault, AuditService $audit)
    {
        $this->validate([
            'varKey' => 'required|string|max:255',
            'varClassification' => 'required|in:secret,config',
            'varProvider' => 'required|string',
            'varCustomProviderName' => 'nullable|string|max:100',
            'varValue' => 'nullable|string',
            'varDescription' => 'nullable|string',
        ]);

        $user = auth()->user();
        $workspace = $user->currentWorkspace();
        $project = $this->getProject();

        $effectiveProvider = $this->varProvider;
        if ($this->varProvider === 'custom' && ! empty(trim($this->varCustomProviderName))) {
            $effectiveProvider = 'custom:'.trim($this->varCustomProviderName);
        }

        if ($this->editingVariableId) {
            $variable = ProjectVariable::findOrFail($this->editingVariableId);
            $variable->update([
                'key' => strtoupper(trim($this->varKey)),
                'classification' => $this->varClassification,
                'provider_hint' => $effectiveProvider,
                'description' => $this->varDescription,
            ]);
        } else {
            $variable = ProjectVariable::create([
                'project_id' => $project->id,
                'key' => strtoupper(trim($this->varKey)),
                'classification' => $this->varClassification,
                'provider_hint' => $effectiveProvider,
                'description' => $this->varDescription,
                'required' => true,
                'position' => $project->variables()->count() + 1,
                'created_by' => $user->id,
            ]);
        }

        // Apply secret value across all selected target environments
        if ($this->varValue !== '') {
            $targetEnvs = $project->environments->whereIn('slug', $this->selectedEnvironments);

            if ($targetEnvs->isEmpty()) {
                $targetEnvs = collect([$this->getActiveEnvironment()]);
            }

            foreach ($targetEnvs as $environment) {
                $binding = EnvironmentBinding::where('environment_id', $environment->id)
                    ->where('project_variable_id', $variable->id)
                    ->first();

                if ($binding) {
                    $vaultEntry = $binding->vaultEntry;
                } else {
                    $vaultEntry = VaultEntry::create([
                        'workspace_id' => $workspace->id,
                        'label' => "{$project->name} / {$variable->key} [{$environment->name}]",
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

                $vault->encryptSecret($workspace, $vaultEntry, $this->varValue, $user);

                $audit->log(
                    workspace: $workspace,
                    event: 'variable.created',
                    actor: $user,
                    subjectType: ProjectVariable::class,
                    subjectId: $variable->id,
                    projectId: $project->id,
                    environmentId: $environment->id,
                    metadata: ['key' => $variable->key, 'classification' => $variable->classification, 'environment' => $environment->name]
                );
            }
        }

        $this->reset(['showVariableModal', 'editingVariableId', 'varKey', 'varValue', 'varDescription', 'varCustomProviderName', 'selectedEnvironments']);
        session()->flash('message', "Variable '{$variable->key}' saved.");
    }

    public function revealSecret(string $variableId, VaultEngine $vault, AuditService $audit)
    {
        if (! $this->isUnlocked()) {
            $this->showUnlockModal = true;

            return;
        }

        $user = auth()->user();
        $workspace = $user->currentWorkspace();
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
        $workspace = $user->currentWorkspace();
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

    public function openImportModal()
    {
        $this->reset(['importDotenvText', 'importParsedItems']);
        $project = $this->getProject();
        $this->importSelectedEnvironments = $project->environments->pluck('slug')->toArray();
        $this->showImportModal = true;
    }

    public function addImportRow()
    {
        $this->importParsedItems[] = [
            'key' => '',
            'value' => '',
            'provider' => 'custom',
            'classification' => 'secret',
            'import' => true,
        ];
    }

    public function removeImportRow(int $index)
    {
        unset($this->importParsedItems[$index]);
        $this->importParsedItems = array_values($this->importParsedItems);
    }

    public function updatedImportDotenvText()
    {
        $this->parseImportDotenv();
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
        $workspace = $user->currentWorkspace();
        $project = $this->getProject();

        $targetEnvs = $project->environments->whereIn('slug', $this->importSelectedEnvironments);
        if ($targetEnvs->isEmpty()) {
            $targetEnvs = collect([$this->getActiveEnvironment()]);
        }

        $importedCount = 0;
        foreach ($this->importParsedItems as $item) {
            if (! ($item['import'] ?? true)) {
                continue;
            }

            $key = strtoupper(trim($item['key']));
            if (empty($key)) {
                continue;
            }

            $variable = ProjectVariable::firstOrCreate(
                ['project_id' => $project->id, 'key' => $key],
                [
                    'classification' => $item['classification'] ?? 'secret',
                    'provider_hint' => $item['provider'] ?? 'custom',
                    'required' => true,
                    'position' => $project->variables()->count() + 1,
                    'created_by' => $user->id,
                ]
            );

            if (! empty($item['value'])) {
                foreach ($targetEnvs as $environment) {
                    $binding = EnvironmentBinding::where('environment_id', $environment->id)
                        ->where('project_variable_id', $variable->id)
                        ->first();

                    if ($binding && $binding->vaultEntry) {
                        $vaultEntry = $binding->vaultEntry;
                    } else {
                        $vaultEntry = VaultEntry::create([
                            'workspace_id' => $workspace->id,
                            'label' => "{$project->name} / {$key} [{$environment->name}]",
                            'classification' => $variable->classification,
                            'sharing_mode' => 'restricted',
                            'created_by' => $user->id,
                        ]);

                        EnvironmentBinding::updateOrCreate(
                            ['environment_id' => $environment->id, 'project_variable_id' => $variable->id],
                            ['vault_entry_id' => $vaultEntry->id, 'created_by' => $user->id]
                        );
                    }

                    $vault->encryptSecret($workspace, $vaultEntry, $item['value'], $user);
                }
                $importedCount++;
            }
        }

        $audit->log(
            workspace: $workspace,
            event: 'variable.imported',
            actor: $user,
            projectId: $project->id,
            metadata: ['count' => $importedCount, 'environments' => $targetEnvs->pluck('name')->toArray()]
        );

        $this->reset(['showImportModal', 'importDotenvText', 'importParsedItems', 'importSelectedEnvironments']);
        session()->flash('message', "✓ Successfully imported {$importedCount} variables across selected environments!");
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
