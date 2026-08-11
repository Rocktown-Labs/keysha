<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Top Breadcrumb Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-zinc-500 font-mono">
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-zinc-300">Projects</a>
                <span>/</span>
                <span class="text-zinc-300">{{ $project->name }}</span>
            </div>
            <h1 class="text-2xl font-semibold text-white tracking-tight mt-1">{{ $project->name }}</h1>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="p-3 rounded-md bg-zinc-900 border border-zinc-800 text-zinc-200 text-sm flex items-center justify-between">
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <!-- Environment Tabs & Sub-Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-800 pb-2">
        <!-- Main Navigation Tabs -->
        <div class="flex items-center gap-6 text-sm">
            <button 
                wire:click="$set('activeTab', 'variables')"
                class="pb-2 border-b-2 transition-colors cursor-pointer {{ $activeTab === 'variables' ? 'border-white text-white font-medium' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}"
            >
                Variables
            </button>
            <button 
                wire:click="$set('activeTab', 'diff')"
                class="pb-2 border-b-2 transition-colors cursor-pointer {{ $activeTab === 'diff' ? 'border-white text-white font-medium' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}"
            >
                Env Comparison
            </button>
            <button 
                wire:click="$set('activeTab', 'template')"
                class="pb-2 border-b-2 transition-colors cursor-pointer {{ $activeTab === 'template' ? 'border-white text-white font-medium' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}"
            >
                .env Schema
            </button>
        </div>

        <!-- Environments Picker (when in Variables tab) -->
        @if ($activeTab === 'variables')
            <div class="flex items-center gap-1.5 bg-zinc-950 p-1 border border-zinc-800 rounded-md">
                @foreach ($project->environments as $env)
                    <button 
                        wire:click="$set('activeEnvSlug', '{{ $env->slug }}')"
                        class="px-2.5 py-1 text-xs font-mono rounded transition-colors cursor-pointer {{ $activeEnvSlug === $env->slug ? 'bg-zinc-800 text-white font-semibold' : 'text-zinc-400 hover:text-zinc-200' }}"
                    >
                        {{ $env->name }}
                        @if($env->protected)
                            <span class="ml-1 text-[10px] text-zinc-500">🔒</span>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if ($activeTab === 'variables')
        <!-- Environment Completeness Metric Banner -->
        <div class="bg-zinc-950 border border-zinc-800 rounded-lg p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-6 text-sm">
                <div>
                    <span class="text-zinc-500 text-xs block">Expected</span>
                    <span class="font-mono text-lg font-semibold text-white">{{ $expectedCount }}</span>
                </div>
                <div class="h-8 w-px bg-zinc-800"></div>
                <div>
                    <span class="text-zinc-500 text-xs block">Configured</span>
                    <span class="font-mono text-lg font-semibold text-emerald-400">{{ $configuredCount }}</span>
                </div>
                <div class="h-8 w-px bg-zinc-800"></div>
                <div>
                    <span class="text-zinc-500 text-xs block">Missing</span>
                    <span class="font-mono text-lg font-semibold {{ $missingCount > 0 ? 'text-amber-400' : 'text-zinc-400' }}">{{ $missingCount }}</span>
                </div>
            </div>

            @if ($missingCount > 0)
                <div class="flex items-center gap-2">
                    <span class="text-xs text-amber-400 font-mono font-medium bg-amber-950/40 border border-amber-900/60 px-3 py-1.5 rounded">
                        ⚠️ {{ $missingCount }} missing in {{ $activeEnv->name }}
                    </span>
                    <button 
                        type="button"
                        wire:click="$set('showMissingModal', true)"
                        class="text-xs font-mono text-amber-300 hover:text-white underline cursor-pointer"
                    >
                        View Missing Keys →
                    </button>
                </div>
            @else
                <div class="text-xs text-emerald-400/90 bg-emerald-950/30 border border-emerald-900/50 px-3 py-1.5 rounded font-mono flex items-center gap-1.5">
                    <span>✓</span>
                    <span>All expected variables configured for {{ $activeEnv->name }}</span>
                </div>
            @endif
        </div>

        <!-- Action Toolbar Right Above Table -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-semibold text-white tracking-tight">Environment Variables</h2>
                <span class="text-xs font-mono text-zinc-500">({{ $activeEnv->name }})</span>
            </div>

            <div class="flex items-center gap-2">
                @if ($this->isUnlocked())
                    <button 
                        wire:click="lockVault"
                        class="px-3 py-1.5 bg-emerald-950/80 border border-emerald-800 hover:bg-emerald-900/80 text-emerald-300 text-xs font-mono rounded-md transition-colors flex items-center gap-1.5 cursor-pointer"
                        title="Vault is unlocked. Click to lock vault immediately."
                    >
                        <flux:icon icon="lock-open" class="size-3.5 text-emerald-400" />
                        <span>Vault Unlocked</span>
                    </button>
                @else
                    <button 
                        wire:click="$set('showUnlockModal', true)"
                        class="px-3 py-1.5 bg-zinc-900 border border-zinc-800 hover:bg-zinc-800 text-zinc-300 text-xs font-mono rounded-md transition-colors flex items-center gap-1.5 cursor-pointer"
                    >
                        <flux:icon icon="lock-closed" class="size-3.5 text-amber-400" />
                        <span>Unlock Vault</span>
                    </button>
                @endif

                <button 
                    wire:click="openAddVariableModal"
                    class="px-3 py-1.5 bg-white text-black hover:bg-zinc-200 text-xs font-semibold rounded-md transition-colors flex items-center gap-1.5 cursor-pointer"
                >
                    <flux:icon icon="plus" class="size-3.5" />
                    <span>Add Variable</span>
                </button>

                <button 
                    wire:click="openImportModal"
                    class="px-3 py-1.5 bg-zinc-900 border border-zinc-800 hover:bg-zinc-800 text-zinc-300 text-xs font-medium rounded-md transition-colors flex items-center gap-1.5 cursor-pointer"
                >
                    <flux:icon icon="arrow-down-tray" class="size-3.5" />
                    <span>Import .env</span>
                </button>
            </div>
        </div>

        <!-- Clean Variables Table -->
        <div class="bg-zinc-950 border border-zinc-800 rounded-lg overflow-x-auto">
            @if ($project->variables->isEmpty())
                <div class="p-12 text-center">
                    <flux:icon icon="key" class="size-8 mx-auto text-zinc-600 mb-3" />
                    <h3 class="text-base font-medium text-white">No variables defined</h3>
                    <p class="text-sm text-zinc-400 mt-1 max-w-sm mx-auto">Add a variable or import an existing .env file to get started.</p>
                    <button 
                        wire:click="openAddVariableModal"
                        class="mt-4 px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-xs font-medium rounded-md transition-colors"
                    >
                        Add Variable
                    </button>
                </div>
            @else
                <table class="w-full text-left text-sm text-zinc-300 min-w-[650px]">
                    <thead class="bg-zinc-900/50 text-xs uppercase tracking-wider text-zinc-400 border-b border-zinc-800">
                        <tr>
                            <th class="py-3 px-4 font-medium">Key</th>
                            <th class="py-3 px-4 font-medium">Status</th>
                            <th class="py-3 px-4 font-medium">Value Preview</th>
                            <th class="py-3 px-4 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/60 font-mono text-xs">
                        @foreach ($project->variables as $var)
                            @php
                                $binding = $activeEnv->bindings->firstWhere('project_variable_id', $var->id);
                                $isConfigured = $binding && $binding->vaultEntry && $binding->vaultEntry->current_version_id;
                                $isRevealed = $revealedVariableId === $var->id;
                                $isShared = $var->bindings->pluck('vaultEntry')->filter()->contains('sharing_mode', 'shared');
                            @endphp
                            <tr class="hover:bg-zinc-900/40 transition-colors">
                                <td class="py-3.5 px-4 font-semibold text-white">
                                    <button 
                                        wire:click="openInspectModal('{{ $var->id }}')" 
                                        class="hover:text-emerald-400 transition-colors text-left flex items-center gap-1.5 group cursor-pointer"
                                        title="Click to view full details & settings"
                                    >
                                        <span>{{ $var->key }}</span>
                                        <flux:icon icon="information-circle" class="size-3.5 text-zinc-600 group-hover:text-emerald-400" />
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 font-sans">
                                    <div class="flex items-center gap-2">
                                        @if ($isConfigured)
                                            <span class="inline-flex items-center gap-1 text-emerald-400 text-xs font-medium">
                                                <span>✓</span> {{ ucfirst($var->classification) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-amber-400 text-xs font-medium">
                                                <span>✗</span> Missing
                                            </span>
                                        @endif

                                        @if ($isShared)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono bg-emerald-950/80 border border-emerald-800 text-emerald-400" title="Shared across workspace in Shared Vault">
                                                <span>⚡</span> Shared
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-zinc-400">
                                    @if ($isConfigured)
                                        @if ($isRevealed)
                                            <div x-data="{ copied: false }" class="flex items-center gap-2 max-w-[260px]">
                                                <span class="text-emerald-300 bg-zinc-900 px-2 py-1 rounded border border-zinc-700 select-all truncate block flex-1">
                                                    {{ $revealedSecretValue }}
                                                </span>
                                                <button 
                                                    @click="navigator.clipboard.writeText('{{ $revealedSecretValue }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                                    class="text-xs font-sans text-zinc-400 hover:text-emerald-400 shrink-0 cursor-pointer"
                                                    title="Copy secret value"
                                                >
                                                    <span x-text="copied ? '✓' : '📋'"></span>
                                                </button>
                                                <button wire:click="hideSecret" class="text-zinc-500 hover:text-white shrink-0 cursor-pointer" title="Hide secret">
                                                    <flux:icon icon="eye-slash" class="size-3.5" />
                                                </button>
                                            </div>
                                        @else
                                            <button 
                                                wire:click="revealSecret('{{ $var->id }}')" 
                                                class="text-zinc-500 hover:text-emerald-400 transition-colors cursor-pointer flex items-center gap-1.5 group"
                                                title="Click eye icon to reveal secret value"
                                            >
                                                <flux:icon icon="eye" class="size-4 group-hover:text-emerald-400" />
                                                <span class="text-zinc-600 select-none group-hover:text-zinc-400">••••••••••••••••</span>
                                            </button>
                                        @endif
                                    @else
                                        <span class="text-zinc-600 font-sans italic">Not set</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-sans">
                                    <div class="flex items-center justify-end gap-3">
                                        <button 
                                            wire:click="openEditVariableModal('{{ $var->id }}')" 
                                            class="text-zinc-400 hover:text-white transition-colors cursor-pointer p-1"
                                            title="Edit Variable"
                                        >
                                            <flux:icon icon="pencil-square" class="size-4" />
                                        </button>

                                        <button 
                                            wire:click="deleteVariable('{{ $var->id }}')" 
                                            wire:confirm="Are you sure you want to delete variable {{ $var->key }}?" 
                                            class="text-zinc-500 hover:text-red-400 transition-colors cursor-pointer p-1"
                                            title="Delete Variable"
                                        >
                                            <flux:icon icon="trash" class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @elseif ($activeTab === 'diff')
        <!-- Environment Comparison Matrix -->
        <div class="bg-zinc-950 border border-zinc-800 rounded-lg p-6 space-y-4">
            <h3 class="text-base font-semibold text-white">Environment Matrix & Completeness</h3>
            <p class="text-xs text-zinc-400">View which variables are configured across Development, Preview, and Production.</p>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-mono text-zinc-300 min-w-[700px]">
                    <thead class="bg-zinc-900/50 uppercase tracking-wider text-zinc-400 border-b border-zinc-800">
                        <tr>
                            <th class="py-3 px-4 font-medium">Variable Key</th>
                            @foreach ($project->environments as $env)
                                <th class="py-3 px-4 font-medium text-center">{{ $env->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/60">
                        @foreach ($project->variables as $var)
                            <tr class="hover:bg-zinc-900/40">
                                <td class="py-3 px-4 font-semibold text-white">{{ $var->key }}</td>
                                @foreach ($project->environments as $env)
                                    @php
                                        $binding = $env->bindings->firstWhere('project_variable_id', $var->id);
                                        $isSet = $binding && $binding->vaultEntry && $binding->vaultEntry->current_version_id;
                                    @endphp
                                    <td class="py-3 px-4 text-center">
                                        @if ($isSet)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-sans bg-emerald-950/80 border border-emerald-800 text-emerald-300">
                                                ✓ Set
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-sans bg-amber-950/40 border border-amber-900/50 text-amber-400">
                                                Missing
                                            </span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($activeTab === 'template')
        <!-- .env Schema Template -->
        <div class="bg-zinc-950 border border-zinc-800 rounded-lg p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-white">.env Schema Template</h3>
                    <p class="text-xs text-zinc-400">Safe empty template for repository commitment. Values are never included.</p>
                </div>
            </div>

            <div class="bg-black border border-zinc-800 rounded-md p-4 font-mono text-xs text-zinc-300 relative">
                @foreach ($project->variables as $var)
                    <div>{{ $var->key }}=</div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Variable Add / Edit Modal -->
    @if ($showVariableModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4">
            <div class="bg-zinc-950 border border-zinc-800 rounded-lg max-w-md w-full p-6 space-y-4 shadow-xl">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <h3 class="text-base font-semibold text-white">
                        {{ $editingVariableId ? 'Edit Variable' : 'Add Variable' }}
                    </h3>
                    <button wire:click="$set('showVariableModal', false)" class="text-zinc-400 hover:text-white">
                        <flux:icon icon="x-mark" class="size-5" />
                    </button>
                </div>

                <form wire:submit.prevent="saveVariable" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1">Variable Key</label>
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="varKey" 
                            placeholder="STRIPE_SECRET_KEY"
                            class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-sm font-mono focus:outline-hidden focus:border-zinc-500 uppercase placeholder-zinc-600"
                        />
                        @error('varKey') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-3 gap-2.5">
                        <div>
                            <label class="block text-xs font-medium text-zinc-400 mb-1">Classification</label>
                            <select 
                                wire:model="varClassification"
                                class="w-full px-2.5 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-xs focus:outline-hidden"
                            >
                                <option value="secret">Secret</option>
                                <option value="config">Config</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-400 mb-1">Provider</label>
                            <select 
                                wire:model.live="varProvider"
                                class="w-full px-2.5 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-xs focus:outline-hidden"
                            >
                                @foreach ($providers as $key => $info)
                                    <option value="{{ $key }}">{{ $info['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-400 mb-1">Sharing</label>
                            <select 
                                wire:model="varSharingMode"
                                class="w-full px-2.5 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-xs focus:outline-hidden"
                            >
                                <option value="restricted">Project</option>
                                <option value="shared">Shared Vault</option>
                            </select>
                        </div>
                    </div>

                    @if ($varProvider === 'custom')
                        <div>
                            <label class="block text-xs font-medium text-zinc-400 mb-1">Custom Provider Name</label>
                            <input 
                                type="text" 
                                wire:model="varCustomProviderName" 
                                placeholder="e.g. PostHog, Supabase, Internal Microservice"
                                class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-xs focus:outline-hidden focus:border-zinc-500 placeholder-zinc-600"
                            />
                        </div>
                    @endif

                    <!-- Environment Selection Chips -->
                    <div class="space-y-1.5 pt-1">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-medium text-zinc-400">Target Environments</label>
                            <div class="flex items-center gap-2 text-[11px]">
                                <button type="button" wire:click="selectAllEnvironments" class="text-zinc-400 hover:text-white underline cursor-pointer">Select All</button>
                                <span class="text-zinc-600">•</span>
                                <button type="button" wire:click="clearEnvironments" class="text-zinc-400 hover:text-white underline cursor-pointer">Clear</button>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($project->environments as $env)
                                @php
                                    $isSelected = in_array($env->slug, $selectedEnvironments);
                                @endphp
                                <button 
                                    type="button" 
                                    wire:click="toggleEnvironment('{{ $env->slug }}')"
                                    class="px-3 py-1 rounded-full text-xs font-mono transition-all border cursor-pointer flex items-center gap-1.5 {{ $isSelected ? 'bg-emerald-950/80 border-emerald-700 text-emerald-300 font-medium shadow-xs' : 'bg-zinc-900 border-zinc-800 text-zinc-500 hover:text-zinc-300' }}"
                                >
                                    <span class="size-1.5 rounded-full {{ $isSelected ? 'bg-emerald-400' : 'bg-zinc-600' }}"></span>
                                    <span>{{ $env->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1">
                            Value (Encrypted at Rest)
                        </label>
                        <input 
                            type="password" 
                            wire:model="varValue" 
                            placeholder="Enter credential value..."
                            class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-sm font-mono focus:outline-hidden focus:border-zinc-500 placeholder-zinc-600"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1">Description (Optional)</label>
                        <input 
                            type="text" 
                            wire:model="varDescription" 
                            placeholder="e.g. Primary Stripe API Key for billing"
                            class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-xs focus:outline-hidden placeholder-zinc-600"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-800">
                        <button 
                            type="button" 
                            wire:click="$set('showVariableModal', false)"
                            class="px-3.5 py-1.5 bg-zinc-900 text-zinc-300 hover:bg-zinc-800 text-xs font-medium rounded-md transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-xs font-semibold rounded-md transition-colors"
                        >
                            Save Variable
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Variable Details & Inspect Modal -->
    @if ($showInspectModal && $inspectVariable)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4">
            <div class="bg-zinc-950 border border-zinc-800 rounded-lg max-w-md w-full p-6 space-y-4 shadow-xl">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <h3 class="text-base font-semibold text-white font-mono flex items-center gap-2">
                        <flux:icon icon="key" class="size-4 text-emerald-400" />
                        <span>{{ $inspectVariable->key }}</span>
                    </h3>
                    <button wire:click="$set('showInspectModal', false)" class="text-zinc-400 hover:text-white">
                        <flux:icon icon="x-mark" class="size-5" />
                    </button>
                </div>

                <div class="space-y-3 text-xs font-sans">
                    <div class="flex justify-between items-center py-1 border-b border-zinc-900">
                        <span class="text-zinc-500 font-mono">Provider:</span>
                        <span class="px-2 py-0.5 rounded bg-zinc-900 border border-zinc-800 text-zinc-300 font-mono">
                            {{ $this->formatProviderName($inspectVariable->provider_hint ?? 'custom') }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-zinc-900">
                        <span class="text-zinc-500 font-mono">Classification:</span>
                        <span class="uppercase font-mono text-zinc-300">{{ $inspectVariable->classification }}</span>
                    </div>

                    @if ($inspectVariable->description)
                        <div class="py-1 border-b border-zinc-900 space-y-0.5">
                            <span class="text-zinc-500 font-mono block">Description:</span>
                            <p class="text-zinc-300">{{ $inspectVariable->description }}</p>
                        </div>
                    @endif

                    <div class="py-1 border-b border-zinc-900 space-y-1.5">
                        <span class="text-zinc-500 font-mono block">Target Environments Status:</span>
                        <div class="space-y-1 font-mono">
                            @foreach ($project->environments as $env)
                                @php
                                    $b = $env->bindings->firstWhere('project_variable_id', $inspectVariable->id);
                                    $isEnvSet = $b && $b->vaultEntry && $b->vaultEntry->current_version_id;
                                @endphp
                                <div class="flex items-center justify-between bg-zinc-900/60 px-2.5 py-1 rounded">
                                    <span class="text-zinc-400">{{ $env->name }}</span>
                                    @if ($isEnvSet)
                                        <span class="text-emerald-400">✓ Configured</span>
                                    @else
                                        <span class="text-amber-400">✗ Missing</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-between">
                        <span class="text-zinc-500 font-mono">Sharing Mode:</span>
                        <button 
                            wire:click="toggleShareVariable('{{ $inspectVariable->id }}')" 
                            class="px-3 py-1 rounded text-xs font-mono border cursor-pointer transition-colors {{ $inspectVariable->bindings->pluck('vaultEntry')->filter()->contains('sharing_mode', 'shared') ? 'bg-emerald-950 border-emerald-800 text-emerald-300' : 'bg-zinc-900 border-zinc-800 text-zinc-400 hover:text-white' }}"
                        >
                            {{ $inspectVariable->bindings->pluck('vaultEntry')->filter()->contains('sharing_mode', 'shared') ? '⚡ Shared Vault' : '🔒 Project Restricted' }}
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-3 border-t border-zinc-800">
                    <button 
                        type="button" 
                        wire:click="$set('showInspectModal', false)"
                        class="px-3.5 py-1.5 bg-zinc-900 text-zinc-300 hover:bg-zinc-800 text-xs font-medium rounded-md"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Missing Variables Modal -->
    @if ($showMissingModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4">
            <div class="bg-zinc-950 border border-zinc-800 rounded-lg max-w-md w-full p-6 space-y-4 shadow-xl">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <h3 class="text-base font-semibold text-white flex items-center gap-2">
                        <flux:icon icon="exclamation-triangle" class="size-4 text-amber-400" />
                        <span>Missing Keys in {{ $activeEnv->name }} ({{ count($missingKeys) }})</span>
                    </h3>
                    <button wire:click="$set('showMissingModal', false)" class="text-zinc-400 hover:text-white">
                        <flux:icon icon="x-mark" class="size-5" />
                    </button>
                </div>

                <p class="text-xs text-zinc-400">The following variables are defined in your project schema but have no value set in <strong>{{ $activeEnv->name }}</strong>:</p>

                <div x-data="{ copied: false }" class="space-y-3">
                    <textarea 
                        readonly 
                        rows="8"
                        class="w-full p-3 bg-black border border-zinc-800 rounded-md font-mono text-xs text-amber-300 select-all focus:outline-hidden"
                    >{{ implode("\n", $missingKeys) }}</textarea>

                    <button 
                        type="button"
                        @click="navigator.clipboard.writeText(`{{ implode("\n", $missingKeys) }}`); copied = true; setTimeout(() => copied = false, 2500)"
                        class="w-full py-2 bg-amber-500 hover:bg-amber-400 text-black font-semibold text-xs rounded-md transition-colors flex items-center justify-center gap-2 cursor-pointer shadow-md"
                    >
                        <template x-if="!copied">
                            <div class="flex items-center gap-1.5">
                                <flux:icon icon="clipboard-document" class="size-4" />
                                <span>Copy All Missing Keys</span>
                            </div>
                        </template>
                        <template x-if="copied">
                            <div class="flex items-center gap-1.5 font-bold text-black">
                                <flux:icon icon="check" class="size-4" />
                                <span>Copied to Clipboard!</span>
                            </div>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modern Vercel-Style Import Drawer / Modal -->
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4">
            <div class="bg-zinc-950 border border-zinc-800 rounded-lg max-w-3xl w-full p-6 space-y-5 shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <div>
                        <h3 class="text-base font-semibold text-white">Import Environment Variables</h3>
                        <p class="text-xs text-zinc-400">Paste your .env file or manually add key-value pairs below.</p>
                    </div>
                    <button wire:click="$set('showImportModal', false)" class="text-zinc-400 hover:text-white">
                        <flux:icon icon="x-mark" class="size-5" />
                    </button>
                </div>

                <!-- Target Environments Selection Chips -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-zinc-400">Apply Imported Variables To</label>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($project->environments as $env)
                            @php
                                $isImpSelected = in_array($env->slug, $importSelectedEnvironments);
                            @endphp
                            <button 
                                type="button" 
                                wire:click="$set('importSelectedEnvironments', {{ json_encode(in_array($env->slug, $importSelectedEnvironments) ? array_values(array_diff($importSelectedEnvironments, [$env->slug])) : array_merge($importSelectedEnvironments, [$env->slug])) }})"
                                class="px-3 py-1 rounded-full text-xs font-mono transition-all border cursor-pointer flex items-center gap-1.5 {{ $isImpSelected ? 'bg-emerald-950/80 border-emerald-700 text-emerald-300 font-medium' : 'bg-zinc-900 border-zinc-800 text-zinc-500 hover:text-zinc-300' }}"
                            >
                                <span class="size-1.5 rounded-full {{ $isImpSelected ? 'bg-emerald-400' : 'bg-zinc-600' }}"></span>
                                <span>{{ $env->name }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Quick Paste Section -->
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-zinc-400">Paste .env Content</label>
                    <textarea 
                        wire:model.live.debounce.300ms="importDotenvText" 
                        rows="4" 
                        placeholder="DATABASE_URL=postgres://user:pass@host:5432/db&#10;STRIPE_SECRET_KEY=sk_test_..."
                        class="w-full p-3 bg-black border border-zinc-800 rounded-md text-white font-mono text-xs focus:outline-hidden focus:border-zinc-500 placeholder-zinc-600"
                    ></textarea>
                </div>

                <!-- Parsed / Editable Field Rows Table -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-medium text-zinc-400">Parsed Variables ({{ count($importParsedItems) }})</label>
                        <button 
                            type="button" 
                            wire:click="addImportRow" 
                            class="text-xs font-mono text-emerald-400 hover:text-emerald-300 flex items-center gap-1 cursor-pointer font-medium"
                        >
                            <span>+ Add Row</span>
                        </button>
                    </div>

                    @if (empty($importParsedItems))
                        <div class="p-4 border border-dashed border-zinc-800 rounded-md text-center text-xs text-zinc-500">
                            Paste text above or click "+ Add Row" to define variables.
                        </div>
                    @else
                        <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                            @foreach ($importParsedItems as $index => $item)
                                <div class="grid grid-cols-12 gap-2 items-center bg-zinc-900/60 p-2 border border-zinc-800 rounded-md">
                                    <div class="col-span-4">
                                        <input 
                                            type="text" 
                                            wire:model="importParsedItems.{{ $index }}.key" 
                                            placeholder="KEY" 
                                            class="w-full px-2.5 py-1.5 bg-zinc-950 border border-zinc-800 rounded text-white text-xs font-mono uppercase focus:outline-hidden placeholder-zinc-600"
                                        />
                                    </div>
                                    <div class="col-span-5">
                                        <input 
                                            type="password" 
                                            wire:model="importParsedItems.{{ $index }}.value" 
                                            placeholder="value..." 
                                            class="w-full px-2.5 py-1.5 bg-zinc-950 border border-zinc-800 rounded text-white text-xs font-mono focus:outline-hidden placeholder-zinc-600"
                                        />
                                    </div>
                                    <div class="col-span-2">
                                        <select 
                                            wire:model="importParsedItems.{{ $index }}.classification"
                                            class="w-full px-2 py-1.5 bg-zinc-950 border border-zinc-800 rounded text-zinc-300 text-xs focus:outline-hidden"
                                        >
                                            <option value="secret">Secret</option>
                                            <option value="config">Config</option>
                                        </select>
                                    </div>
                                    <div class="col-span-1 text-right">
                                        <button 
                                            type="button" 
                                            wire:click="removeImportRow({{ $index }})" 
                                            class="text-zinc-500 hover:text-red-400 transition-colors p-1 cursor-pointer"
                                            title="Delete Row"
                                        >
                                            <flux:icon icon="trash" class="size-4 mx-auto" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-800">
                    <button 
                        type="button" 
                        wire:click="$set('showImportModal', false)"
                        class="px-3.5 py-1.5 bg-zinc-900 text-zinc-300 hover:bg-zinc-800 text-xs font-medium rounded-md transition-colors"
                    >
                        Cancel
                    </button>
                    <button 
                        type="button" 
                        wire:click="commitImport"
                        class="px-4 py-1.5 bg-white text-black hover:bg-zinc-200 text-xs font-semibold rounded-md transition-colors cursor-pointer"
                    >
                        Save All Imported Variables
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Step-Up Unlock Modal -->
    @if ($showUnlockModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4">
            <div class="bg-zinc-950 border border-zinc-800 rounded-lg max-w-sm w-full p-6 space-y-4 shadow-xl">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <h3 class="text-base font-semibold text-white flex items-center gap-2">
                        <flux:icon icon="lock-closed" class="size-4 text-amber-400" />
                        <span>Unlock Vault Session</span>
                    </h3>
                    <button wire:click="$set('showUnlockModal', false)" class="text-zinc-400 hover:text-white">
                        <flux:icon icon="x-mark" class="size-5" />
                    </button>
                </div>

                <p class="text-xs text-zinc-400">Revealing or copying sensitive vault credentials requires password confirmation.</p>

                <form wire:submit.prevent="unlockVault" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1">Account Password</label>
                        <input 
                            type="password" 
                            wire:model="unlockPassword" 
                            placeholder="Enter password..."
                            class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-sm focus:outline-hidden focus:border-zinc-500 placeholder-zinc-600"
                        />
                        @error('unlockPassword') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-800">
                        <button 
                            type="button" 
                            wire:click="$set('showUnlockModal', false)"
                            class="px-3.5 py-1.5 bg-zinc-900 text-zinc-300 text-xs font-medium rounded-md"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-xs font-semibold rounded-md"
                        >
                            Unlock Vault
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
