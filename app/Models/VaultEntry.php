<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VaultEntry extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id',
        'provider_profile_id',
        'label',
        'classification',
        'sharing_mode',
        'current_version_id',
        'created_by',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(VaultEntryVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(VaultEntryVersion::class)->latest();
    }

    public function bindings(): HasMany
    {
        return $this->hasMany(EnvironmentBinding::class);
    }
}
