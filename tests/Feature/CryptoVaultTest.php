<?php

use App\Crypto\EnvironmentMasterKeyProvider;
use App\Crypto\MasterKey;
use App\Crypto\ProviderRegistry;
use App\Crypto\RecoveryKey;
use App\Crypto\VaultEngine;
use App\Models\User;
use App\Models\VaultEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('master key provider initializes master key', function () {
    $provider = new EnvironmentMasterKeyProvider;
    $masterKey = $provider->current();

    expect($masterKey)->toBeInstanceOf(MasterKey::class);
    expect(strlen($masterKey->keyBytes))->toBe(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    expect($masterKey->fingerprint())->toBeString();
});

test('encrypt and decrypt round trip works via vault engine', function () {
    $user = User::factory()->create();
    $workspace = $user->ensurePersonalWorkspace();

    $entry = VaultEntry::create([
        'workspace_id' => $workspace->id,
        'label' => 'STRIPE_SECRET_KEY',
        'classification' => 'secret',
        'sharing_mode' => 'restricted',
        'created_by' => $user->id,
    ]);

    $masterProvider = new EnvironmentMasterKeyProvider;
    $vault = new VaultEngine($masterProvider);

    $secretValue = 'sk_test_51MzX90000000000000000000';

    $version = $vault->encryptSecret($workspace, $entry, $secretValue, $user);

    expect($version->ciphertext)->not->toBe($secretValue);
    expect($version->ciphertext)->not->toContain($secretValue);

    $decrypted = $vault->decryptSecret($version, $workspace);

    expect($decrypted)->toBe($secretValue);
});

test('ciphertext tampering causes decryption failure', function () {
    $user = User::factory()->create();
    $workspace = $user->ensurePersonalWorkspace();

    $entry = VaultEntry::create([
        'workspace_id' => $workspace->id,
        'label' => 'RESEND_API_KEY',
        'classification' => 'secret',
        'sharing_mode' => 'restricted',
        'created_by' => $user->id,
    ]);

    $masterProvider = new EnvironmentMasterKeyProvider;
    $vault = new VaultEngine($masterProvider);

    $version = $vault->encryptSecret($workspace, $entry, 're_123456789', $user);

    // Tamper ciphertext
    $rawCipher = base64_decode($version->ciphertext);
    $rawCipher[0] = chr(ord($rawCipher[0]) ^ 0xFF);
    $version->ciphertext = base64_encode($rawCipher);

    expect(fn () => $vault->decryptSecret($version, $workspace))
        ->toThrow(RuntimeException::class);
});

test('recovery key system initializes and restores master key backup', function () {
    $masterProvider = new EnvironmentMasterKeyProvider;
    $recoverySystem = new RecoveryKey($masterProvider);

    $recoveryKeyStr = $recoverySystem->generate();

    expect($recoveryKeyStr)->toStartWith('ksha-rk-v1-');

    $systemRecovery = $recoverySystem->initializeRecovery($recoveryKeyStr);

    expect($systemRecovery->master_key_fingerprint)->toBe($masterProvider->current()->fingerprint());

    $isValid = $recoverySystem->verifyRecoveryKey($recoveryKeyStr);
    expect($isValid)->toBeTrue();

    $isInvalid = $recoverySystem->verifyRecoveryKey('ksha-rk-v1-0000000000000000000000000000000000000000000000000000000000000000');
    expect($isInvalid)->toBeFalse();
});

test('provider registry detects provider and classifies keys correctly', function () {
    expect(ProviderRegistry::detectProvider('STRIPE_SECRET_KEY'))->toBe('stripe');
    expect(ProviderRegistry::detectProvider('RESEND_API_KEY'))->toBe('resend');
    expect(ProviderRegistry::detectProvider('CUSTOM_VAR'))->toBe('custom');

    expect(ProviderRegistry::classifyKey('STRIPE_SECRET_KEY'))->toBe('secret');
    expect(ProviderRegistry::classifyKey('STRIPE_PRICE_ID'))->toBe('config');
    expect(ProviderRegistry::classifyKey('NEXT_PUBLIC_API_URL'))->toBe('config');
});
