<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Workspace extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'personal',
        'created_by',
    ];

    protected $casts = [
        'personal' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function providerProfiles(): HasMany
    {
        return $this->hasMany(ProviderProfile::class);
    }

    public function vaultEntries(): HasMany
    {
        return $this->hasMany(VaultEntry::class);
    }

    public function activeKey(): HasOne
    {
        return $this->hasOne(WorkspaceKey::class)->where('active', true)->latestOfMany();
    }

    public function keys(): HasMany
    {
        return $this->hasMany(WorkspaceKey::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }
}
