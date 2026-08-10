<?php

namespace App\Crypto;

interface MasterKeyProvider
{
    public function current(): MasterKey;

    public function byVersion(string $version): MasterKey;
}
