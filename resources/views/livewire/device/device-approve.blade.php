<div class="p-6 max-w-3xl mx-auto space-y-6">
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
                        wire:model.live="userCode" 
                        placeholder="e.g. M7KC-P2QV"
                        class="flex-1 px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white font-mono text-sm uppercase tracking-wider text-center focus:outline-hidden focus:border-zinc-500 placeholder-zinc-600"
                    />
                    <button 
                        wire:click="findDevice"
                        class="px-3.5 py-2 bg-zinc-800 text-white hover:bg-zinc-700 text-xs font-medium rounded-md cursor-pointer"
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
                        class="w-full py-2.5 bg-white text-black hover:bg-zinc-200 text-sm font-semibold rounded-md transition-colors cursor-pointer"
                    >
                        Approve Device Access
                    </button>
                </div>
            @endif
        @endif
    </div>

    <!-- Connected Devices & Tokens Table -->
    <div class="space-y-3 pt-2">
        <div class="flex items-center justify-between border-b border-zinc-800 pb-2">
            <h2 class="text-sm font-semibold text-white tracking-tight flex items-center gap-2">
                <flux:icon icon="cpu-chip" class="size-4 text-emerald-400" />
                <span>Authorized CLI Devices</span>
            </h2>
            <span class="text-xs font-mono text-zinc-500">{{ $authorizedDevices->count() }} registered</span>
        </div>

        @if (session()->has('message'))
            <div class="p-3 rounded-md bg-zinc-900 border border-zinc-800 text-zinc-200 text-xs">
                {{ session('message') }}
            </div>
        @endif

        <div class="bg-zinc-950 border border-zinc-800 rounded-lg overflow-x-auto">
            @if ($authorizedDevices->isEmpty())
                <div class="p-6 text-center text-xs text-zinc-500">
                    No authorized CLI devices found. Run <code class="bg-zinc-900 px-1.5 py-0.5 rounded text-emerald-400 font-mono">keysha login</code> in your terminal.
                </div>
            @else
                <table class="w-full text-left text-xs text-zinc-300 font-mono">
                    <thead class="bg-zinc-900/50 uppercase tracking-wider text-[10px] text-zinc-400 border-b border-zinc-800">
                        <tr>
                            <th class="py-2.5 px-4">Device</th>
                            <th class="py-2.5 px-4">Status</th>
                            <th class="py-2.5 px-4">Authorized</th>
                            <th class="py-2.5 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/60">
                        @foreach ($authorizedDevices as $device)
                            <tr class="hover:bg-zinc-900/40 transition-colors">
                                <td class="py-2.5 px-4 text-white font-semibold truncate max-w-[220px]">
                                    {{ $device->device_name }}
                                </td>
                                <td class="py-2.5 px-3 font-sans">
                                    @if ($device->status === 'approved' || $device->status === 'consumed')
                                        <span class="inline-flex items-center gap-1 text-emerald-400 text-[11px] font-medium">
                                            <span class="size-1.5 rounded-full bg-emerald-400"></span> Active
                                        </span>
                                    @elseif ($device->status === 'pending')
                                        <span class="inline-flex items-center gap-1 text-amber-400 text-[11px] font-medium">
                                            <span class="size-1.5 rounded-full bg-amber-400 animate-pulse"></span> Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-red-400 text-[11px] font-medium">
                                            <span class="size-1.5 rounded-full bg-red-400"></span> Revoked
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-zinc-400 font-sans text-[11px]">
                                    {{ $device->approved_at ? $device->approved_at->diffForHumans() : $device->created_at->diffForHumans() }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-sans">
                                    @if ($device->status !== 'revoked')
                                        <button 
                                            wire:click="revokeDevice('{{ $device->id }}')" 
                                            wire:confirm="Revoke CLI access for {{ $device->device_name }}?" 
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-mono bg-red-950/60 border border-red-800/80 text-red-400 hover:bg-red-900/80 hover:text-white transition-colors cursor-pointer"
                                            title="Revoke device access token"
                                        >
                                            <span>✕</span> Revoke
                                        </button>
                                    @else
                                        <span class="text-zinc-600 text-[11px]">Revoked</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
