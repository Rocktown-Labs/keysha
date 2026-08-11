<?php

namespace App\Services;

class DeviceAuthorizationCode
{
    public static function normalize(string $code): string
    {
        return str_replace(['-', ' ', '_'], '', strtoupper(trim($code)));
    }

    public static function hash(string $code): string
    {
        return hash('sha256', static::normalize($code));
    }
}
