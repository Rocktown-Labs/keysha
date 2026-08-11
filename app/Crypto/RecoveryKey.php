<?php

namespace App\Crypto;

use App\Models\SystemRecovery;
use InvalidArgumentException;

class RecoveryKey
{
    public function __construct(
        private readonly MasterKeyProvider $masterKeyProvider
    ) {}

    public function generate(): string
    {
        $entropy = random_bytes(32);

        return 'ksha-rk-v1-'.bin2hex($entropy);
    }

    public function deriveKey(string $recoveryKeyStr): string
    {
        $clean = trim($recoveryKeyStr);
        if (! str_starts_with($clean, 'ksha-rk-v1-')) {
            throw new InvalidArgumentException('Invalid recovery key format.');
        }

        $hex = substr($clean, strlen('ksha-rk-v1-'));
        $raw = hex2bin($hex);
        if ($raw === false || strlen($raw) < 16) {
            throw new InvalidArgumentException('Invalid recovery key entropy.');
        }

        return sodium_crypto_generichash($raw, 'keysha-rk-salt16b', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    public function initializeRecovery(string $recoveryKeyStr): SystemRecovery
    {
        $masterKey = $this->masterKeyProvider->current();
        $derivedKey = $this->deriveKey($recoveryKeyStr);

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $encryptedBackup = sodium_crypto_secretbox($masterKey->keyBytes, $nonce, $derivedKey);

        SystemRecovery::query()->delete();

        return SystemRecovery::create([
            'master_key_fingerprint' => $masterKey->fingerprint(),
            'encrypted_master_key_backup' => base64_encode($encryptedBackup),
            'recovery_nonce' => base64_encode($nonce),
            'recovery_schema_version' => 1,
        ]);
    }

    public function verifyRecoveryKey(string $recoveryKeyStr): bool
    {
        $systemRecovery = SystemRecovery::first();
        if (! $systemRecovery) {
            return false;
        }

        try {
            $derivedKey = $this->deriveKey($recoveryKeyStr);
            $nonce = base64_decode($systemRecovery->recovery_nonce);
            $cipher = base64_decode($systemRecovery->encrypted_master_key_backup);

            $decryptedMasterKeyBytes = sodium_crypto_secretbox_open($cipher, $nonce, $derivedKey);
            if ($decryptedMasterKeyBytes === false) {
                return false;
            }

            $fingerprint = hash('sha256', $decryptedMasterKeyBytes);

            return hash_equals($systemRecovery->master_key_fingerprint, $fingerprint);
        } catch (\Throwable) {
            return false;
        }
    }
}
