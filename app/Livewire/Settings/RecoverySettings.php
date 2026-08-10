<?php

namespace App\Livewire\Settings;

use App\Crypto\EnvironmentMasterKeyProvider;
use App\Crypto\RecoveryKey;
use App\Models\SystemRecovery;
use Livewire\Component;

class RecoverySettings extends Component
{
    public string $testKeyInput = '';

    public ?bool $testResult = null;

    public ?string $generatedKey = null;

    public function generateNewRecoveryKey()
    {
        $provider = new EnvironmentMasterKeyProvider;
        $recovery = new RecoveryKey($provider);

        $newKey = $recovery->generate();
        $recovery->initializeRecovery($newKey);

        $this->generatedKey = $newKey;
        session()->flash('message', 'New recovery key generated and master key backup stored.');
    }

    public function testRecoveryKey()
    {
        $this->validate([
            'testKeyInput' => 'required|string',
        ]);

        $provider = new EnvironmentMasterKeyProvider;
        $recovery = new RecoveryKey($provider);

        $this->testResult = $recovery->verifyRecoveryKey($this->testKeyInput);
    }

    public function render()
    {
        $systemRecovery = SystemRecovery::first();

        return view('livewire.settings.recovery-settings', [
            'systemRecovery' => $systemRecovery,
        ])->layout('layouts.app', ['title' => 'Vault Recovery Settings']);
    }
}
