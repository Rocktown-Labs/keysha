<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaultEntryVersion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'vault_entry_id',
        'ciphertext',
        'nonce',
        'wrapped_data_key',
        'wrapped_data_key_nonce',
        'algorithm',
        'crypto_schema_version',
        'workspace_key_version',
        'created_by',
    ];

    protected $casts = [
        'crypto_schema_version' => 'integer',
        'workspace_key_version' => 'integer',
    ];

    public function vaultEntry(): BelongsTo
    {
        return $this->belongsTo(VaultEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
