<?php

namespace App\Crypto;

class EnvironmentMasterKeyProvider implements MasterKeyProvider
{
    private ?MasterKey $cachedMasterKey = null;

    public function current(): MasterKey
    {
        if ($this->cachedMasterKey !== null) {
            return $this->cachedMasterKey;
        }

        $configured = config('keysha.master_key');

        if (! empty($configured)) {
            $keyBytes = base64_decode($configured, true);
            if ($keyBytes === false || strlen($keyBytes) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
                // If raw hex or string, hash/pad or decode
                if (strlen($configured) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
                    $keyBytes = $configured;
                } else {
                    $keyBytes = sodium_crypto_generichash($configured, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
                }
            }

            $this->cachedMasterKey = new MasterKey($keyBytes, 'v1');

            return $this->cachedMasterKey;
        }

        $keyFilePath = storage_path('app/keysha.master.key');

        if (file_exists($keyFilePath)) {
            $contents = file_get_contents($keyFilePath);
            if ($contents !== false && strlen($contents) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
                $this->cachedMasterKey = new MasterKey($contents, 'v1');

                return $this->cachedMasterKey;
            }
        }

        // Generate persistent local master key
        $newKeyBytes = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        @file_put_contents($keyFilePath, $newKeyBytes);
        @chmod($keyFilePath, 0600);

        $this->cachedMasterKey = new MasterKey($newKeyBytes, 'v1');

        return $this->cachedMasterKey;
    }

    public function byVersion(string $version): MasterKey
    {
        return $this->current();
    }
}
