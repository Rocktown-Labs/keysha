<div class="p-6 max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-zinc-800">
        <div>
            <h1 class="text-xl font-semibold text-white tracking-tight">Shared Vault</h1>
            <p class="text-sm text-zinc-400">Reusable credentials shared across multiple workspace projects.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="p-3 rounded-md bg-zinc-900 border border-zinc-800 text-zinc-200 text-sm">
            {{ session('message') }}
        </div>
    @endif

    <div class="bg-zinc-950 border border-zinc-800 rounded-lg overflow-x-auto">
        @if ($entries->isEmpty())
            <div class="p-12 text-center">
                <flux:icon icon="lock-closed" class="size-8 mx-auto text-zinc-600 mb-3" />
                <h3 class="text-base font-medium text-white">No shared credentials</h3>
                <p class="text-sm text-zinc-400 mt-1 max-w-sm mx-auto">Shared credentials allow multiple projects to bind to a single underlying credential entry (e.g. primary Resend or OpenAI key). Mark any variable as "Shared Vault" in project settings to share it here.</p>
            </div>
        @else
            <table class="w-full text-left text-sm text-zinc-300 min-w-[750px]">
                <thead class="bg-zinc-900/50 text-xs uppercase tracking-wider text-zinc-400 border-b border-zinc-800">
                    <tr>
                        <th class="py-3 px-4 font-medium">Credential Label</th>
                        <th class="py-3 px-4 font-medium">Classification</th>
                        <th class="py-3 px-4 font-medium">Bound Projects</th>
                        <th class="py-3 px-4 font-medium">Value Preview</th>
                        <th class="py-3 px-4 text-right font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60 font-mono text-xs">
                    @foreach ($entries as $entry)
                        @php
                            $isRevealed = ($revealedEntryId === $entry->id);
                        @endphp
                        <tr class="hover:bg-zinc-900/40">
                            <td class="py-3.5 px-4 font-semibold text-white">
                                {{ str_replace('Keysha Vault / ', '', $entry->label) }}
                            </td>
                            <td class="py-3.5 px-4 text-zinc-400 font-sans">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase tracking-wider bg-zinc-900 border border-zinc-800 text-zinc-300">
                                    {{ $entry->classification }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-zinc-400 font-sans">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @forelse ($entry->bindings as $binding)
                                        @if ($binding->environment && $binding->environment->project)
                                            <a href="{{ route('projects.show', $binding->environment->project->slug) }}" wire:navigate class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono bg-zinc-900 border border-zinc-800 text-zinc-300 hover:border-zinc-700">
                                                {{ $binding->environment->project->name }} / {{ $binding->environment->name }}
                                            </a>
                                        @endif
                                    @empty
                                        <span class="text-zinc-600 italic">Unbound</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-zinc-400">
                                @if ($isRevealed)
                                    <div x-data="{ copied: false }" class="flex items-center gap-2 max-w-[260px]">
                                        <span class="text-emerald-300 bg-zinc-900 px-2 py-1 rounded border border-zinc-700 select-all truncate block flex-1">
                                            {{ $revealedSecretValue }}
                                        </span>
                                        <button 
                                            @click="navigator.clipboard.writeText('{{ $revealedSecretValue }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="text-xs font-sans text-zinc-400 hover:text-emerald-400 shrink-0"
                                            title="Copy secret value"
                                        >
                                            <span x-text="copied ? '✓' : '📋'"></span>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-zinc-600 select-none">••••••••••••••••</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right font-sans">
                                <div class="flex items-center justify-end gap-2 text-xs">
                                    @if ($isRevealed)
                                        <button wire:click="hideSecret" class="text-zinc-400 hover:text-white underline">
                                            Hide
                                        </button>
                                    @else
                                        <button wire:click="revealSharedSecret('{{ $entry->id }}')" class="text-zinc-300 hover:text-white underline">
                                            Reveal
                                        </button>
                                    @endif

                                    <button wire:click="unshareEntry('{{ $entry->id }}')" wire:confirm="Unshare this credential from Shared Vault?" class="text-zinc-500 hover:text-amber-400 underline">
                                        Unshare
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

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

                <form wire:submit.prevent="unlockVault" class="space-y-4">
                    <p class="text-xs text-zinc-400">Enter your account password to decrypt and reveal vault secrets.</p>

                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1">Account Password</label>
                        <input 
                            type="password" 
                            wire:model="unlockPassword" 
                            placeholder="••••••••"
                            class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-sm focus:outline-hidden focus:border-zinc-500 placeholder-zinc-600"
                        />
                        @error('unlockPassword') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-800">
                        <button 
                            type="button" 
                            wire:click="$set('showUnlockModal', false)"
                            class="px-3 py-1.5 bg-zinc-900 text-zinc-300 hover:bg-zinc-800 text-xs font-medium rounded-md transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-xs font-semibold rounded-md transition-colors"
                        >
                            Unlock Vault
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
