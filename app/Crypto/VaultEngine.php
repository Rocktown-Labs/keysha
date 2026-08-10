<?php

namespace App\Crypto;

use App\Models\User;
use App\Models\VaultEntry;
use App\Models\VaultEntryVersion;
use App\Models\Workspace;
use App\Models\WorkspaceKey;
use Illuminate\Support\Str;
use RuntimeException;

class VaultEngine
{
    public function __construct(
        private readonly MasterKeyProvider $masterKeyProvider
    ) {}

    public function getOrCreateWorkspaceKey(Workspace $workspace): string
    {
        $activeKeyModel = WorkspaceKey::where('workspace_id', $workspace->id)
            ->where('active', true)
            ->first();

        $masterKey = $this->masterKeyProvider->current();

        if ($activeKeyModel) {
            $wrappedKeyBytes = base64_decode($activeKeyModel->wrapped_key);
            $nonceBytes = base64_decode($activeKeyModel->nonce);

            $workspaceKek = sodium_crypto_secretbox_open($wrappedKeyBytes, $nonceBytes, $masterKey->keyBytes);
            if ($workspaceKek === false) {
                throw new RuntimeException('Failed to decrypt workspace key. Invalid master key.');
            }

            return $workspaceKek;
        }

        // Generate new workspace key
        $workspaceKek = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $nonceBytes = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $wrappedKeyBytes = sodium_crypto_secretbox($workspaceKek, $nonceBytes, $masterKey->keyBytes);

        WorkspaceKey::create([
            'workspace_id' => $workspace->id,
            'version' => 1,
            'wrapped_key' => base64_encode($wrappedKeyBytes),
            'nonce' => base64_encode($nonceBytes),
            'master_key_version' => $masterKey->version,
            'active' => true,
        ]);

        return $workspaceKek;
    }

    public function encryptSecret(
        Workspace $workspace,
        VaultEntry $entry,
        string $plaintext,
        ?User $actor = null
    ): VaultEntryVersion {
        $workspaceKek = $this->getOrCreateWorkspaceKey($workspace);

        $versionId = (string) Str::uuid7();

        // 1. Generate DEK (Data Encryption Key)
        $dekBytes = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

        // 2. Wrap DEK with Workspace KEK
        $dekNonceBytes = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $wrappedDekBytes = sodium_crypto_secretbox($dekBytes, $dekNonceBytes, $workspaceKek);

        // 3. Prepare AEAD Associated Data
        $associatedData = json_encode([
            'schema' => 1,
            'workspace_id' => (string) $workspace->id,
            'entry_id' => (string) $entry->id,
            'version_id' => (string) $versionId,
        ], JSON_UNESCAPED_SLASHES);

        // 4. Encrypt Plaintext using DEK & AEAD
        $payloadNonceBytes = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $ciphertextBytes = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plaintext,
            $associatedData,
            $payloadNonceBytes,
            $dekBytes
        );

        $version = VaultEntryVersion::create([
            'id' => $versionId,
            'vault_entry_id' => $entry->id,
            'ciphertext' => base64_encode($ciphertextBytes),
            'nonce' => base64_encode($payloadNonceBytes),
            'wrapped_data_key' => base64_encode($wrappedDekBytes),
            'wrapped_data_key_nonce' => base64_encode($dekNonceBytes),
            'algorithm' => 'XChaCha20-Poly1305',
            'crypto_schema_version' => 1,
            'workspace_key_version' => 1,
            'created_by' => $actor?->id ?? $entry->created_by,
        ]);

        $entry->update([
            'current_version_id' => $version->id,
        ]);

        return $version;
    }

    public function decryptSecret(VaultEntryVersion $version, Workspace $workspace): string
    {
        $workspaceKek = $this->getOrCreateWorkspaceKey($workspace);

        $wrappedDekBytes = base64_decode($version->wrapped_data_key);
        $dekNonceBytes = base64_decode($version->wrapped_data_key_nonce);

        $dekBytes = sodium_crypto_secretbox_open($wrappedDekBytes, $dekNonceBytes, $workspaceKek);
        if ($dekBytes === false) {
            throw new RuntimeException('Failed to decrypt data key. Workspace key mismatch or corruption.');
        }

        $ciphertextBytes = base64_decode($version->ciphertext);
        $payloadNonceBytes = base64_decode($version->nonce);

        $associatedData = json_encode([
            'schema' => 1,
            'workspace_id' => (string) $workspace->id,
            'entry_id' => (string) $version->vault_entry_id,
            'version_id' => (string) $version->id,
        ], JSON_UNESCAPED_SLASHES);

        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertextBytes,
            $associatedData,
            $payloadNonceBytes,
            $dekBytes
        );

        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed. Ciphertext, nonce, or associated data tampered with.');
        }

        return $plaintext;
    }
}
