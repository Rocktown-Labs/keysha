<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemRecovery extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'master_key_fingerprint',
        'encrypted_master_key_backup',
        'recovery_nonce',
        'recovery_schema_version',
    ];

    protected $casts = [
        'recovery_schema_version' => 'integer',
    ];
}
