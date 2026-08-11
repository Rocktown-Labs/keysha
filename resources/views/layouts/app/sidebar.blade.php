<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-black text-white antialiased">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-800 bg-zinc-950 text-white">
            <flux:sidebar.header class="space-y-3">
                <div class="flex items-center justify-between gap-2.5 w-full pb-1">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 hover:opacity-90 transition-opacity">
                <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-zinc-900 border border-emerald-800/80 text-emerald-400 shrink-0 shadow-xs">
                    <x-app-logo-icon class="size-5" />
                </div>
                <span class="font-bold text-base text-white tracking-tight">Keysha</span>
            </a>
            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-zinc-900 border border-zinc-800 text-emerald-400">v1.0</span>
        </div>
                
                @auth
                    @php
                        $currentWs = auth()->user()->currentWorkspace();
                        $allWorkspaces = auth()->user()->allWorkspaces();
                    @endphp
                    <div x-data="{ openWsModal: false, openSettingsModal: false }" class="w-full">
                        <flux:dropdown class="w-full">
                            <button class="w-full flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-md bg-zinc-900 border border-zinc-800 text-xs font-mono text-zinc-300 hover:border-zinc-700 transition-colors">
                                <div class="flex items-center gap-2 truncate">
                                    <span class="size-2 rounded-full {{ $currentWs->personal ? 'bg-emerald-400' : 'bg-indigo-400' }}"></span>
                                    <span class="truncate font-medium text-white">{{ $currentWs->name }}</span>
                                </div>
                                <flux:icon icon="chevron-up-down" class="size-3.5 text-zinc-500 shrink-0" />
                            </button>

                            <flux:menu class="w-56">
                                <div class="px-2 py-1 text-[10px] uppercase font-mono tracking-wider text-zinc-500">
                                    Workspaces
                                </div>
                                @foreach ($allWorkspaces as $ws)
                                    <form method="POST" action="{{ route('workspaces.switch') }}">
                                        @csrf
                                        <input type="hidden" name="workspace_id" value="{{ $ws->id }}">
                                        <button type="submit" class="w-full text-left px-2 py-1.5 text-xs text-zinc-300 hover:bg-zinc-800 rounded flex items-center justify-between cursor-pointer">
                                            <span class="truncate">{{ $ws->name }}</span>
                                            @if ($ws->id === $currentWs->id)
                                                <span class="text-emerald-400 text-xs font-bold">✓</span>
                                            @endif
                                        </button>
                                    </form>
                                @endforeach

                                <flux:menu.separator />

                                <button @click="openSettingsModal = true" type="button" class="w-full text-left px-2 py-1.5 text-xs text-zinc-300 hover:bg-zinc-800 rounded flex items-center gap-1.5 font-medium cursor-pointer">
                                    <flux:icon icon="cog-6-tooth" class="size-3.5" />
                                    <span>Workspace Settings...</span>
                                </button>

                                <button @click="openWsModal = true" type="button" class="w-full text-left px-2 py-1.5 text-xs text-emerald-400 hover:bg-zinc-800 rounded flex items-center gap-1.5 font-medium cursor-pointer">
                                    <flux:icon icon="plus" class="size-3.5" />
                                    <span>New Workspace...</span>
                                </button>
                            </flux:menu>
                        </flux:dropdown>

                        <!-- Create Workspace Modal -->
                        <div x-show="openWsModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4">
                            <div class="bg-zinc-950 border border-zinc-800 rounded-lg max-w-sm w-full p-6 space-y-4 shadow-xl">
                                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                                    <h3 class="text-base font-semibold text-white">Create Workspace</h3>
                                    <button @click="openWsModal = false" type="button" class="text-zinc-400 hover:text-white">
                                        <flux:icon icon="x-mark" class="size-5" />
                                    </button>
                                </div>

                                <form method="POST" action="{{ route('workspaces.store') }}" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-400 mb-1">Workspace Name</label>
                                        <input 
                                            type="text" 
                                            name="name" 
                                            placeholder="e.g. Rocktown Labs, Personal, Acme Corp" 
                                            required
                                            class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-sm focus:outline-hidden focus:border-zinc-500 placeholder-zinc-600"
                                        />
                                    </div>

                                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-800">
                                        <button 
                                            type="button" 
                                            @click="openWsModal = false"
                                            class="px-3 py-1.5 bg-zinc-900 text-zinc-300 hover:bg-zinc-800 text-xs font-medium rounded-md transition-colors"
                                        >
                                            Cancel
                                        </button>
                                        <button 
                                            type="submit" 
                                            class="px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-xs font-semibold rounded-md transition-colors"
                                        >
                                            Create & Switch
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Workspace Settings Modal (Rename / Delete) -->
                        <div x-show="openSettingsModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-4">
                            <div class="bg-zinc-950 border border-zinc-800 rounded-lg max-w-md w-full p-6 space-y-5 shadow-xl">
                                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                                    <h3 class="text-base font-semibold text-white flex items-center gap-2">
                                        <flux:icon icon="cog-6-tooth" class="size-4 text-emerald-400" />
                                        <span>Workspace Settings</span>
                                    </h3>
                                    <button @click="openSettingsModal = false" type="button" class="text-zinc-400 hover:text-white">
                                        <flux:icon icon="x-mark" class="size-5" />
                                    </button>
                                </div>

                                <!-- Rename Workspace Form -->
                                <form method="POST" action="{{ route('workspaces.update', $currentWs->id) }}" class="space-y-4">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-400 mb-1">Workspace Name</label>
                                        <input 
                                            type="text" 
                                            name="name" 
                                            value="{{ $currentWs->name }}" 
                                            required
                                            class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-md text-white text-sm focus:outline-hidden focus:border-zinc-500 font-mono"
                                        />
                                    </div>

                                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-800">
                                        <button 
                                            type="button" 
                                            @click="openSettingsModal = false"
                                            class="px-3 py-1.5 bg-zinc-900 text-zinc-300 hover:bg-zinc-800 text-xs font-medium rounded-md"
                                        >
                                            Cancel
                                        </button>
                                        <button 
                                            type="submit" 
                                            class="px-3.5 py-1.5 bg-white text-black hover:bg-zinc-200 text-xs font-semibold rounded-md"
                                        >
                                            Save Changes
                                        </button>
                                    </div>
                                </form>

                                @if (!$currentWs->personal)
                                    <div class="pt-4 border-t border-zinc-800 space-y-2">
                                        <h4 class="text-xs font-semibold text-red-400">Danger Zone</h4>
                                        <p class="text-[11px] text-zinc-500">Deleting a workspace permanently removes all associated projects, secrets, and activity history.</p>
                                        <form method="POST" action="{{ route('workspaces.destroy', $currentWs->id) }}" onsubmit="return confirm('Are you sure you want to delete workspace {{ $currentWs->name }}? All projects will be permanently destroyed.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full py-1.5 bg-red-950/80 border border-red-800 hover:bg-red-900/80 text-red-300 text-xs font-mono rounded-md transition-colors cursor-pointer">
                                                Delete Workspace
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endauth

                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Vault')" class="grid">
                    <flux:sidebar.item icon="folder" :href="route('projects.index')" :current="request()->routeIs('projects.*') || request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Projects') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="lock-closed" :href="route('vault.shared')" :current="request()->routeIs('vault.shared')" wire:navigate>
                        {{ __('Shared Vault') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clock" :href="route('activity.index')" :current="request()->routeIs('activity.index')" wire:navigate>
                        {{ __('Activity') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="command-line" :href="route('device.approve')" :current="request()->routeIs('device.approve')" wire:navigate>
                        {{ __('CLI Authorization') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('System')" class="grid mt-4">
                    <flux:sidebar.item icon="key" :href="route('settings.recovery')" :current="request()->routeIs('settings.recovery')" wire:navigate class="flex items-center justify-between">
                        <span>{{ __('Recovery') }}</span>
                        @if (!\App\Models\SystemRecovery::exists())
                            <span class="size-2 rounded-full bg-amber-400 animate-pulse ml-auto" title="Recovery setup required"></span>
                        @endif
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            @auth
                <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
            @endauth
        </flux:sidebar>

        <!-- Mobile User Menu -->
        @auth
            <flux:header class="lg:hidden">
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

                <flux:spacer />

                <flux:dropdown position="top" align="end">
                    <flux:profile
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevron-down"
                    />

                    <flux:menu>
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <flux:avatar
                                        :name="auth()->user()->name"
                                        :initials="auth()->user()->initials()"
                                    />

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                        <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                                data-test="logout-button"
                            >
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </flux:header>
        @endauth

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
