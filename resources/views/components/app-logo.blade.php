@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Keysha" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-zinc-900 border border-emerald-800/80 text-emerald-400">
            <x-app-logo-icon class="size-6" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Keysha" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-zinc-900 border border-emerald-800/80 text-emerald-400">
            <x-app-logo-icon class="size-6" />
        </x-slot>
    </flux:brand>
@endif
