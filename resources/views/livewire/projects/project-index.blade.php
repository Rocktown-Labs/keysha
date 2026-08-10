<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-zinc-800">
        <div>
            <h1 class="text-xl font-semibold text-white tracking-tight">Projects</h1>
            <p class="text-sm text-zinc-400">Canonical vault configuration source for your applications.</p>
        </div>
        <button 
            wire:click="$set('showCreateModal', true)"
            class="px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-sm font-medium rounded-md transition-colors flex items-center gap-1.5"
        >
            <flux:icon icon="plus" class="size-4" />
            <span>New Project</span>
        </button>
    </div>

    @if (session()->has('message'))
        <div class="p-3 rounded-md bg-zinc-900 border border-zinc-800 text-zinc-200 text-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Projects Table -->
    <div class="bg-zinc-950 border border-zinc-800 rounded-lg overflow-hidden">
        @if ($projects->isEmpty())
            <div class="p-12 text-center">
                <flux:icon icon="folder" class="size-8 mx-auto text-zinc-600 mb-3" />
                <h3 class="text-base font-medium text-white">No projects yet</h3>
                <p class="text-sm text-zinc-400 mt-1 max-w-sm mx-auto">Create a project to manage credentials, variables, and environment configuration.</p>
                <button 
                    wire:click="$set('showCreateModal', true)"
                    class="mt-4 px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-sm font-medium rounded-md transition-colors"
                >
                    Create your first project
                </button>
            </div>
        @else
            <table class="w-full text-left text-sm text-zinc-300">
                <thead class="bg-zinc-900/50 text-xs uppercase tracking-wider text-zinc-400 border-b border-zinc-800">
                    <tr>
                        <th class="py-3 px-4 font-medium">Project</th>
                        <th class="py-3 px-4 font-medium">Environments</th>
                        <th class="py-3 px-4 font-medium">Variables</th>
                        <th class="py-3 px-4 font-medium">Updated</th>
                        <th class="py-3 px-4 text-right font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60">
                    @foreach ($projects as $project)
                        <tr class="hover:bg-zinc-900/40 transition-colors group">
                            <td class="py-3.5 px-4 font-medium text-white">
                                <a href="{{ route('projects.show', $project->slug) }}" wire:navigate class="hover:underline flex items-center gap-2">
                                    <span class="size-2 rounded-full bg-emerald-500"></span>
                                    <span>{{ $project->name }}</span>
                                </a>
                                @if($project->description)
                                    <p class="text-xs text-zinc-500 mt-0.5 truncate max-w-xs">{{ $project->description }}</p>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-zinc-400">
                                <div class="flex items-center gap-1.5">
                                    @foreach ($project->environments as $env)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-zinc-900 border border-zinc-800 text-zinc-300">
                                            {{ $env->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-zinc-400 font-mono text-xs">
                                {{ $project->variables->count() }} expected
                            </td>
                            <td class="py-3.5 px-4 text-zinc-500 text-xs">
                                {{ $project->updated_at->diffForHumans() }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('projects.show', $project->slug) }}" wire:navigate class="text-xs font-medium text-zinc-300 hover:text-white underline">
                                    View →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <!-- Create Project Modal -->
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4">
            <div class="bg-zinc-950 border border-zinc-800 rounded-lg max-w-md w-full p-6 space-y-4 shadow-xl">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <h3 class="text-base font-semibold text-white">Create New Project</h3>
                    <button wire:click="$set('showCreateModal', false)" class="text-zinc-400 hover:text-white">
                        <flux:icon icon="x-mark" class="size-5" />
                    </button>
                </div>
                
                <form wire:submit.prevent="createProject" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1">Project Name</label>
                        <input 
                            type="text" 
                            wire:model="name" 
                            placeholder="e.g. Mingle, GigStax"
                            class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-sm focus:outline-hidden focus:border-zinc-500 placeholder-zinc-600"
                        />
                        @error('name') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1">Description (Optional)</label>
                        <textarea 
                            wire:model="description" 
                            rows="3" 
                            placeholder="Brief description of this project..."
                            class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-sm focus:outline-hidden focus:border-zinc-500 placeholder-zinc-600"
                        ></textarea>
                        @error('description') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="text-xs text-zinc-500 bg-zinc-900/50 p-2.5 rounded border border-zinc-800/80">
                        Initial environments created automatically: <span class="font-mono text-zinc-400">Development</span>, <span class="font-mono text-zinc-400">Preview</span>, <span class="font-mono text-zinc-400">Production</span>.
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-800">
                        <button 
                            type="button" 
                            wire:click="$set('showCreateModal', false)"
                            class="px-3.5 py-1.5 bg-zinc-900 text-zinc-300 hover:bg-zinc-800 text-sm font-medium rounded-md transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-sm font-medium rounded-md transition-colors"
                        >
                            Create Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
