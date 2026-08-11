<?php

namespace App\Livewire\Vault;

use App\Crypto\VaultEngine;
use App\Models\VaultEntry;
use App\Services\AuditService;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class SharedVault extends Component
{
    public ?string $revealedSecretValue = null;

    public ?string $revealedEntryId = null;

    public bool $showUnlockModal = false;

    public string $unlockPassword = '';

    public function isUnlocked(): bool
    {
        $unlockedUntil = session('vault_unlocked_until');

        return $unlockedUntil && now()->timestamp < $unlockedUntil;
    }

    public function lockVault()
    {
        session()->forget('vault_unlocked_until');
        $this->revealedSecretValue = null;
        $this->revealedEntryId = null;
        session()->flash('message', 'Vault session locked.');
    }

    public function unlockVault()
    {
        $this->validate(['unlockPassword' => 'required|string']);
        $user = auth()->user();

        if (! Hash::check($this->unlockPassword, $user->password)) {
            $this->addError('unlockPassword', 'Incorrect account password.');

            return;
        }

        session(['vault_unlocked_until' => now()->addMinutes(15)->timestamp]);
        $this->reset(['unlockPassword', 'showUnlockModal']);
    }

    public function revealSharedSecret(string $entryId, VaultEngine $vault, AuditService $audit)
    {
        if (! $this->isUnlocked()) {
            $this->showUnlockModal = true;

            return;
        }

        $user = auth()->user();
        $workspace = $user->currentWorkspace();
        $entry = VaultEntry::where('workspace_id', $workspace->id)
            ->where('id', $entryId)
            ->firstOrFail();

        if (! $entry->currentVersion) {
            return;
        }

        $plaintext = $vault->decryptSecret($entry->currentVersion, $workspace);
        $this->revealedSecretValue = $plaintext;
        $this->revealedEntryId = $entry->id;
    }

    public function hideSecret()
    {
        $this->revealedSecretValue = null;
        $this->revealedEntryId = null;
    }

    public function unshareEntry(string $entryId)
    {
        $user = auth()->user();
        $workspace = $user->currentWorkspace();
        $entry = VaultEntry::where('workspace_id', $workspace->id)
            ->where('id', $entryId)
            ->firstOrFail();

        $entry->update(['sharing_mode' => 'restricted']);
        session()->flash('message', "Credential '{$entry->label}' is no longer shared.");
    }

    public function render()
    {
        $user = auth()->user();
        $workspace = $user->currentWorkspace();

        $entries = VaultEntry::where('workspace_id', $workspace->id)
            ->where('sharing_mode', 'shared')
            ->has('bindings')
            ->with(['providerProfile', 'bindings.environment.project', 'currentVersion'])
            ->latest()
            ->get();

        return view('livewire.vault.shared-vault', [
            'entries' => $entries,
        ])->layout('layouts.app', ['title' => 'Shared Vault']);
    }
}
