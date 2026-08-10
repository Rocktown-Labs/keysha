<div class="p-6 max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-zinc-800">
        <div>
            <h1 class="text-xl font-semibold text-white tracking-tight">Shared Vault</h1>
            <p class="text-sm text-zinc-400">Reusable credentials shared across multiple workspace projects.</p>
        </div>
    </div>

    <div class="bg-zinc-950 border border-zinc-800 rounded-lg overflow-hidden">
        @if ($entries->isEmpty())
            <div class="p-12 text-center">
                <flux:icon icon="lock-closed" class="size-8 mx-auto text-zinc-600 mb-3" />
                <h3 class="text-base font-medium text-white">No shared credentials</h3>
                <p class="text-sm text-zinc-400 mt-1 max-w-sm mx-auto">Shared credentials allow multiple projects to bind to a single underlying credential entry (e.g. primary Resend or OpenAI key).</p>
            </div>
        @else
            <table class="w-full text-left text-sm text-zinc-300">
                <thead class="bg-zinc-900/50 text-xs uppercase tracking-wider text-zinc-400 border-b border-zinc-800">
                    <tr>
                        <th class="py-3 px-4 font-medium">Credential Label</th>
                        <th class="py-3 px-4 font-medium">Classification</th>
                        <th class="py-3 px-4 font-medium">Bound Projects</th>
                        <th class="py-3 px-4 font-medium">Last Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60 font-mono text-xs">
                    @foreach ($entries as $entry)
                        <tr class="hover:bg-zinc-900/40">
                            <td class="py-3.5 px-4 font-semibold text-white">
                                {{ $entry->label }}
                            </td>
                            <td class="py-3.5 px-4 text-zinc-400 font-sans">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] uppercase tracking-wider bg-zinc-900 border border-zinc-800 text-zinc-300">
                                    {{ $entry->classification }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-zinc-400 font-sans">
                                {{ $entry->bindings->count() }} bindings
                            </td>
                            <td class="py-3.5 px-4 text-zinc-500 font-sans text-xs">
                                {{ $entry->updated_at->diffForHumans() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
