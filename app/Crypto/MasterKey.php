<?php

namespace App\Crypto;

class MasterKey
{
    public function __construct(
        public readonly string $keyBytes,
        public readonly string $version = 'v1'
    ) {}

    public function fingerprint(): string
    {
        return hash('sha256', $this->keyBytes);
    }
}
