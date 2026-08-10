<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationMapping extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'environment_binding_id',
        'integration_target_id',
        'remote_key',
        'remote_type',
        'last_synced_version_id',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function environmentBinding(): BelongsTo
    {
        return $this->belongsTo(EnvironmentBinding::class);
    }

    public function integrationTarget(): BelongsTo
    {
        return $this->belongsTo(IntegrationTarget::class);
    }

    public function lastSyncedVersion(): BelongsTo
    {
        return $this->belongsTo(VaultEntryVersion::class, 'last_synced_version_id');
    }
}
