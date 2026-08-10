<?php

namespace App\Livewire\Vault;

use App\Models\VaultEntry;
use Livewire\Component;

class SharedVault extends Component
{
    public function render()
    {
        $user = auth()->user();
        $workspace = $user->personalWorkspace();

        $entries = VaultEntry::where('workspace_id', $workspace->id)
            ->where('sharing_mode', 'shared')
            ->with(['providerProfile', 'bindings.environment.project'])
            ->latest()
            ->get();

        return view('livewire.vault.shared-vault', [
            'entries' => $entries,
        ])->layout('layouts.app', ['title' => 'Shared Vault']);
    }
}
