<div class="p-6 max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-zinc-800">
        <div>
            <h1 class="text-xl font-semibold text-white tracking-tight">Activity & Audit Log</h1>
            <p class="text-sm text-zinc-400">Complete immutable audit trail of actions taken in your vault.</p>
        </div>

        <div class="flex items-center gap-2">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Filter audit events..."
                class="px-3 py-1.5 bg-zinc-900 border border-zinc-800 rounded-md text-white text-xs placeholder-zinc-600 focus:outline-hidden focus:border-zinc-500"
            />
            <button 
                wire:click="exportCsv" 
                class="px-3 py-1.5 bg-zinc-900 border border-zinc-800 hover:bg-zinc-800 text-zinc-300 text-xs font-mono rounded-md transition-colors flex items-center gap-1.5 cursor-pointer"
                title="Export CSV Audit Trail for Compliance"
            >
                <flux:icon icon="arrow-down-tray" class="size-3.5 text-emerald-400" />
                <span>Export CSV</span>
            </button>
        </div>
    </div>

    <div class="bg-zinc-950 border border-zinc-800 rounded-lg overflow-x-auto">
        @if ($events->isEmpty())
            <div class="p-12 text-center">
                <flux:icon icon="clock" class="size-8 mx-auto text-zinc-600 mb-3" />
                <h3 class="text-base font-medium text-white">No audit events recorded</h3>
                <p class="text-sm text-zinc-400 mt-1">Actions like variable creation, reveal, copy, and device auth will appear here.</p>
            </div>
        @else
            <table class="w-full text-left text-sm text-zinc-300 min-w-[700px]">
                <thead class="bg-zinc-900/50 text-xs uppercase tracking-wider text-zinc-400 border-b border-zinc-800">
                    <tr>
                        <th class="py-3 px-4 font-medium">Event</th>
                        <th class="py-3 px-4 font-medium">Actor</th>
                        <th class="py-3 px-4 font-medium">Project</th>
                        <th class="py-3 px-4 font-medium">IP Address</th>
                        <th class="py-3 px-4 font-medium">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60 text-xs">
                    @foreach ($events as $event)
                        <tr class="hover:bg-zinc-900/40">
                            <td class="py-3.5 px-4 font-mono font-semibold text-white">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-zinc-900 border border-zinc-800 text-zinc-200">
                                    {{ $event->event }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-zinc-300 font-sans">
                                {{ $event->actor?->name ?? 'System / Device' }}
                            </td>
                            <td class="py-3.5 px-4 text-zinc-400 font-sans">
                                {{ $event->project?->name ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-zinc-500 font-mono text-[11px]">
                                {{ $event->ip_address ?? '127.0.0.1' }}
                            </td>
                            <td class="py-3.5 px-4 text-zinc-400 font-sans text-xs">
                                {{ $event->created_at->format('M d, Y H:i:s') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-3 border-t border-zinc-800">
                {{ $events->links() }}
            </div>
        @endif
    </div>
</div>
