<div class="p-6 max-w-4xl mx-auto space-y-6">
    <div class="pb-4 border-b border-zinc-800">
        <h1 class="text-xl font-semibold text-white tracking-tight">System Recovery & Master Key Backup</h1>
        <p class="text-sm text-zinc-400">Manage recovery keys to restore vault master key access in self-hosted instances.</p>
    </div>

    @if (session()->has('message'))
        <div class="p-3 rounded-md bg-zinc-900 border border-zinc-800 text-zinc-200 text-sm">
            {{ session('message') }}
        </div>
    @endif

    @if ($generatedKey)
        <div class="p-4 bg-emerald-950/40 border border-emerald-800 rounded-lg space-y-3">
            <h3 class="text-sm font-semibold text-emerald-400">Save Your Keysha Recovery Key</h3>
            <p class="text-xs text-zinc-300">Save this key in your password manager (1Password / Bitwarden). Keysha cannot recover this key for you.</p>
            <div class="p-3 bg-black border border-emerald-900 rounded font-mono text-sm text-emerald-300 select-all">
                {{ $generatedKey }}
            </div>
        </div>
    @endif

    <!-- System Recovery Status Card -->
    <div class="bg-zinc-950 border border-zinc-800 rounded-lg p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-white">Recovery Backup Status</h3>
                <p class="text-xs text-zinc-400">Encrypted master key copy stored in PostgreSQL.</p>
            </div>
            <div>
                @if ($systemRecovery)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-mono bg-emerald-950 border border-emerald-800 text-emerald-400">
                        <span>✓</span> Backup Active
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-mono bg-amber-950 border border-amber-800 text-amber-400">
                        <span>!</span> Backup Uninitialized
                    </span>
                @endif
            </div>
        </div>

        @if ($systemRecovery)
            <div class="text-xs font-mono text-zinc-400 space-y-1 bg-zinc-900/50 p-3 rounded border border-zinc-800">
                <div>Master Key Fingerprint: <span class="text-white">{{ $systemRecovery->master_key_fingerprint }}</span></div>
                <div>Recovery Schema: <span class="text-white">v{{ $systemRecovery->recovery_schema_version }}</span></div>
            </div>
        @endif

        <div class="pt-2">
            <button 
                wire:click="generateNewRecoveryKey"
                class="px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-xs font-medium rounded-md transition-colors"
            >
                Generate & Initialize Recovery Key
            </button>
        </div>
    </div>

    <!-- Test Recovery Key -->
    <div class="bg-zinc-950 border border-zinc-800 rounded-lg p-6 space-y-4">
        <div>
            <h3 class="text-sm font-semibold text-white">Test Recovery Key</h3>
            <p class="text-xs text-zinc-400">Verify that your saved recovery key can successfully decrypt the master key backup without exposing plaintext.</p>
        </div>

        <form wire:submit.prevent="testRecoveryKey" class="space-y-3">
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1">Enter Recovery Key</label>
                <input 
                    type="text" 
                    wire:model="testKeyInput" 
                    placeholder="ksha-rk-v1-..."
                    class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white font-mono text-xs focus:outline-hidden focus:border-zinc-500 placeholder-zinc-600"
                />
                @error('testKeyInput') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <button 
                type="submit" 
                class="px-3.5 py-1.5 bg-zinc-800 text-zinc-200 hover:bg-zinc-700 text-xs font-medium rounded-md"
            >
                Run Recovery Test
            </button>
        </form>

        @if ($testResult !== null)
            <div class="p-3 rounded-md text-xs font-mono border {{ $testResult ? 'bg-emerald-950/50 border-emerald-800 text-emerald-400' : 'bg-red-950/50 border-red-800 text-red-400' }}">
                @if ($testResult)
                    ✓ SUCCESS: Entered recovery key is valid and decrypts master key backup cleanly.
                @else
                    ✗ FAILURE: Invalid recovery key. Master key backup could not be decrypted.
                @endif
            </div>
        @endif
    </div>
</div>
