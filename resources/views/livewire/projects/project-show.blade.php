<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Top Breadcrumb & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-zinc-500 font-mono">
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-zinc-300">Projects</a>
                <span>/</span>
                <span class="text-zinc-300">{{ $project->name }}</span>
            </div>
            <h1 class="text-2xl font-semibold text-white tracking-tight mt-1">{{ $project->name }}</h1>
        </div>

        <div class="flex items-center gap-2">
            @if ($this->isUnlocked())
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-mono bg-emerald-950/60 border border-emerald-800/80 text-emerald-400">
                    <span class="size-1.5 rounded-full bg-emerald-400"></span>
                    <span>Vault Unlocked</span>
                </span>
            @else
                <button 
                    wire:click="$set('showUnlockModal', true)"
                    class="px-3 py-1.5 bg-zinc-900 border border-zinc-800 hover:bg-zinc-800 text-zinc-300 text-xs font-medium rounded-md transition-colors flex items-center gap-1.5"
                >
                    <flux:icon icon="lock-closed" class="size-3.5" />
                    <span>Unlock Vault</span>
                </button>
            @endif

            <button 
                wire:click="openAddVariableModal"
                class="px-3 py-1.5 bg-white text-black hover:bg-zinc-200 text-xs font-medium rounded-md transition-colors flex items-center gap-1.5"
            >
                <flux:icon icon="plus" class="size-3.5" />
                <span>Add Variable</span>
            </button>

            <button 
                wire:click="$set('showImportModal', true)"
                class="px-3 py-1.5 bg-zinc-900 border border-zinc-800 hover:bg-zinc-800 text-zinc-300 text-xs font-medium rounded-md transition-colors flex items-center gap-1.5"
            >
                <flux:icon icon="arrow-down-tray" class="size-3.5" />
                <span>Import .env</span>
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="p-3 rounded-md bg-zinc-900 border border-zinc-800 text-zinc-200 text-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Environment Tabs & Sub-Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-800 pb-2">
        <!-- Main Navigation Tabs -->
        <div class="flex items-center gap-6 text-sm font-medium">
            <button 
                wire:click="$set('activeTab', 'variables')"
                class="pb-2 border-b-2 transition-colors {{ $activeTab === 'variables' ? 'border-white text-white' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}"
            >
                Variables & Vault
            </button>
            <button 
                wire:click="$set('activeTab', 'diff')"
                class="pb-2 border-b-2 transition-colors {{ $activeTab === 'diff' ? 'border-white text-white' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}"
            >
                Environment Compare
            </button>
            <button 
                wire:click="$set('activeTab', 'template')"
                class="pb-2 border-b-2 transition-colors {{ $activeTab === 'template' ? 'border-white text-white' : 'border-transparent text-zinc-500 hover:text-zinc-300' }}"
            >
                .env.example Template
            </button>
        </div>

        <!-- Environments Picker (when in Variables tab) -->
        @if ($activeTab === 'variables')
            <div class="flex items-center gap-1.5 bg-zinc-950 p-1 border border-zinc-800 rounded-md">
                @foreach ($project->environments as $env)
                    <button 
                        wire:click="$set('activeEnvSlug', '{{ $env->slug }}')"
                        class="px-2.5 py-1 text-xs font-mono rounded transition-colors {{ $activeEnvSlug === $env->slug ? 'bg-zinc-800 text-white font-semibold' : 'text-zinc-400 hover:text-zinc-200' }}"
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
                <div class="text-xs text-amber-400/90 bg-amber-950/30 border border-amber-900/50 px-3 py-1.5 rounded font-mono">
                    Missing in {{ $activeEnv->name }}: {{ implode(', ', array_slice($missingKeys, 0, 3)) }}{{ count($missingKeys) > 3 ? '...' : '' }}
                </div>
            @else
                <div class="text-xs text-emerald-400/90 bg-emerald-950/30 border border-emerald-900/50 px-3 py-1.5 rounded font-mono flex items-center gap-1.5">
                    <span>✓</span>
                    <span>All expected variables configured for {{ $activeEnv->name }}</span>
                </div>
            @endif
        </div>

        <!-- Variables Table -->
        <div class="bg-zinc-950 border border-zinc-800 rounded-lg overflow-hidden">
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
                <table class="w-full text-left text-sm text-zinc-300">
                    <thead class="bg-zinc-900/50 text-xs uppercase tracking-wider text-zinc-400 border-b border-zinc-800">
                        <tr>
                            <th class="py-3 px-4 font-medium">Variable Key</th>
                            <th class="py-3 px-4 font-medium">Provider</th>
                            <th class="py-3 px-4 font-medium">Type</th>
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
                            @endphp
                            <tr class="hover:bg-zinc-900/40 transition-colors">
                                <td class="py-3.5 px-4 font-semibold text-white">
                                    {{ $var->key }}
                                    @if($var->description)
                                        <p class="text-[11px] font-sans font-normal text-zinc-500 mt-0.5">{{ $var->description }}</p>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-zinc-400">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-sans bg-zinc-900 border border-zinc-800 text-zinc-300">
                                        {{ ucfirst($var->provider_hint ?? 'Custom') }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if ($var->classification === 'secret')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-sans bg-zinc-900 border border-zinc-700 text-zinc-300">
                                            Secret
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-sans bg-zinc-900/80 border border-zinc-800 text-zinc-400">
                                            Config
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-sans">
                                    @if ($isConfigured)
                                        <span class="inline-flex items-center gap-1 text-emerald-400 text-xs font-medium">
                                            <span>✓</span> Configured
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-amber-400 text-xs font-medium">
                                            <span>✗</span> Missing
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono text-zinc-400">
                                    @if ($isConfigured)
                                        @if ($isRevealed)
                                            <span class="text-emerald-300 bg-zinc-900 px-2 py-1 rounded border border-zinc-700 select-all">
                                                {{ $revealedSecretValue }}
                                            </span>
                                        @else
                                            @if ($var->classification === 'secret')
                                                <span class="text-zinc-600 select-none">••••••••••••••••</span>
                                            @else
                                                <span class="text-zinc-300">[Configured]</span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-zinc-600 font-sans italic">Not set</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-sans">
                                    <div class="flex items-center justify-end gap-2 text-xs">
                                        @if ($isConfigured)
                                            @if ($isRevealed)
                                                <button wire:click="hideSecret" class="text-zinc-400 hover:text-white underline">
                                                    Hide
                                                </button>
                                            @else
                                                <button wire:click="revealSecret('{{ $var->id }}')" class="text-zinc-300 hover:text-white underline">
                                                    Reveal
                                                </button>
                                            @endif
                                        @endif

                                        <button wire:click="openEditVariableModal('{{ $var->id }}')" class="text-zinc-400 hover:text-white underline">
                                            Edit
                                        </button>

                                        <button wire:click="deleteVariable('{{ $var->id }}')" wire:confirm="Are you sure you want to delete variable {{ $var->key }}?" class="text-zinc-500 hover:text-red-400 underline">
                                            Delete
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
        <!-- Environment Compare Matrix -->
        <div class="bg-zinc-950 border border-zinc-800 rounded-lg p-6 space-y-4">
            <h3 class="text-sm font-semibold text-white">Environment Comparison Matrix</h3>
            <p class="text-xs text-zinc-400">Verifies configuration presence across environments without exposing values.</p>

            <table class="w-full text-left text-xs font-mono border-t border-zinc-800">
                <thead>
                    <tr class="border-b border-zinc-800 text-zinc-400 uppercase">
                        <th class="py-3 px-4 font-medium">Variable</th>
                        @foreach ($project->environments as $env)
                            <th class="py-3 px-4 font-medium text-center">{{ $env->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    @foreach ($project->variables as $var)
                        <tr class="hover:bg-zinc-900/40">
                            <td class="py-3 px-4 text-white font-semibold">{{ $var->key }}</td>
                            @foreach ($project->environments as $env)
                                @php
                                    $binding = $env->bindings->firstWhere('project_variable_id', $var->id);
                                    $isConfigured = $binding && $binding->vaultEntry && $binding->vaultEntry->current_version_id;
                                @endphp
                                <td class="py-3 px-4 text-center">
                                    @if ($isConfigured)
                                        <span class="text-emerald-400 font-bold">✓</span>
                                    @else
                                        <span class="text-amber-400 font-bold">✗</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif ($activeTab === 'template')
        <!-- Dotenv Template Generator -->
        <div class="bg-zinc-950 border border-zinc-800 rounded-lg p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-white">.env.example Template</h3>
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

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-zinc-400 mb-1">Classification</label>
                            <select 
                                wire:model="varClassification"
                                class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-xs focus:outline-hidden"
                            >
                                <option value="secret">Secret (Masked)</option>
                                <option value="config">Config (Visible)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-400 mb-1">Provider</label>
                            <select 
                                wire:model="varProvider"
                                class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-xs focus:outline-hidden"
                            >
                                @foreach ($providers as $key => $info)
                                    <option value="{{ $key }}">{{ $info['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1">
                            Value for {{ $activeEnv->name }} (Encrypted at Rest)
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
                            class="px-3.5 py-1.5 bg-zinc-900 text-zinc-300 hover:bg-zinc-800 text-sm font-medium rounded-md transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-sm font-medium rounded-md transition-colors"
                        >
                            Save Variable
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Import Dotenv Modal -->
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4">
            <div class="bg-zinc-950 border border-zinc-800 rounded-lg max-w-xl w-full p-6 space-y-4 shadow-xl">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <h3 class="text-base font-semibold text-white">Import .env File into {{ $activeEnv->name }}</h3>
                    <button wire:click="$set('showImportModal', false)" class="text-zinc-400 hover:text-white">
                        <flux:icon icon="x-mark" class="size-5" />
                    </button>
                </div>

                @if (empty($importParsedItems))
                    <div class="space-y-3">
                        <p class="text-xs text-zinc-400">Paste the contents of your .env file below. Keysha will parse variable names, auto-detect secret classifications, and encrypt values locally.</p>
                        <textarea 
                            wire:model="importDotenvText" 
                            rows="10" 
                            placeholder="STRIPE_SECRET_KEY=sk_test_...&#10;RESEND_API_KEY=re_..."
                            class="w-full p-3 bg-zinc-900 border border-zinc-800 rounded-md text-white font-mono text-xs focus:outline-hidden focus:border-zinc-500 placeholder-zinc-600"
                        ></textarea>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-800">
                            <button 
                                type="button" 
                                wire:click="$set('showImportModal', false)"
                                class="px-3.5 py-1.5 bg-zinc-900 text-zinc-300 text-sm font-medium rounded-md"
                            >
                                Cancel
                            </button>
                            <button 
                                type="button" 
                                wire:click="parseImportDotenv"
                                class="px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-sm font-medium rounded-md"
                            >
                                Review Import
                            </button>
                        </div>
                    </div>
                @else
                    <div class="space-y-3">
                        <p class="text-xs text-zinc-400">Review detected variables and classification before importing into <span class="text-white font-medium">{{ $activeEnv->name }}</span>.</p>
                        <div class="max-h-60 overflow-y-auto border border-zinc-800 rounded-md bg-zinc-900 divide-y divide-zinc-800 text-xs font-mono">
                            @foreach ($importParsedItems as $idx => $item)
                                <div class="p-2.5 flex items-center justify-between">
                                    <div>
                                        <span class="text-white font-semibold">{{ $item['key'] }}</span>
                                        <span class="ml-2 text-[10px] text-zinc-500 uppercase">({{ $item['classification'] }} · {{ $item['provider'] }})</span>
                                    </div>
                                    <span class="text-zinc-600">••••••••</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-800">
                            <button 
                                type="button" 
                                wire:click="$set('importParsedItems', [])"
                                class="px-3.5 py-1.5 bg-zinc-900 text-zinc-300 text-sm font-medium rounded-md"
                            >
                                Back
                            </button>
                            <button 
                                type="button" 
                                wire:click="commitImport"
                                class="px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-sm font-medium rounded-md"
                            >
                                Confirm & Encrypt Import
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Step-Up Unlock Modal -->
    @if ($showUnlockModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4">
            <div class="bg-zinc-950 border border-zinc-800 rounded-lg max-w-sm w-full p-6 space-y-4 shadow-xl">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <h3 class="text-base font-semibold text-white">Unlock Vault</h3>
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
                            class="px-3.5 py-1.5 bg-zinc-900 text-zinc-300 text-sm font-medium rounded-md"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-sm font-medium rounded-md"
                        >
                            Unlock Vault
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
