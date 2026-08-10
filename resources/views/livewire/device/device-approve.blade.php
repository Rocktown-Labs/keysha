<div class="p-6 max-w-lg mx-auto space-y-6">
    <div class="text-center space-y-2">
        <flux:icon icon="command-line" class="size-10 mx-auto text-white" />
        <h1 class="text-xl font-semibold text-white tracking-tight">Authorize Keysha CLI Device</h1>
        <p class="text-xs text-zinc-400">Confirm CLI authorization to grant access to your Keysha vault.</p>
    </div>

    <div class="bg-zinc-950 border border-zinc-800 rounded-lg p-6 space-y-4 shadow-xl">
        @if ($approved)
            <div class="text-center py-6 space-y-3">
                <div class="size-10 rounded-full bg-emerald-950 border border-emerald-800 text-emerald-400 flex items-center justify-center mx-auto text-lg">
                    ✓
                </div>
                <h3 class="text-base font-semibold text-white">Device Authorized!</h3>
                <p class="text-xs text-zinc-400">Your CLI terminal is now connected to Keysha. You may close this tab.</p>
            </div>
        @else
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1">Enter User Confirmation Code</label>
                <div class="flex gap-2">
                    <input 
                        type="text" 
                        wire:model="userCode" 
                        placeholder="e.g. M7KC-P2QV"
                        class="flex-1 px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white font-mono text-sm uppercase tracking-wider text-center focus:outline-hidden focus:border-zinc-500 placeholder-zinc-600"
                    />
                    <button 
                        wire:click="findDevice"
                        class="px-3.5 py-2 bg-zinc-800 text-white hover:bg-zinc-700 text-xs font-medium rounded-md"
                    >
                        Verify Code
                    </button>
                </div>
                @error('userCode') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
            </div>

            @if ($authorization)
                <div class="p-4 bg-zinc-900 border border-zinc-800 rounded-md space-y-3 text-xs font-mono">
                    <div class="flex justify-between text-zinc-400">
                        <span>Device:</span>
                        <span class="text-white font-semibold">{{ $authorization->device_name }}</span>
                    </div>
                    <div class="flex justify-between text-zinc-400">
                        <span>Host:</span>
                        <span class="text-white">{{ $authorization->requested_host ?? 'Local Terminal' }}</span>
                    </div>
                    <div class="flex justify-between text-zinc-400">
                        <span>Code:</span>
                        <span class="text-emerald-400 font-bold tracking-widest">{{ $userCode }}</span>
                    </div>
                </div>

                <div class="pt-2">
                    <button 
                        wire:click="approveDevice"
                        class="w-full py-2.5 bg-white text-black hover:bg-zinc-200 text-sm font-semibold rounded-md transition-colors"
                    >
                        Approve Device Access
                    </button>
                </div>
            @endif
        @endif
    </div>
</div>
