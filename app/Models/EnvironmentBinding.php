<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentBinding extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'environment_id',
        'project_variable_id',
        'vault_entry_id',
        'created_by',
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function projectVariable(): BelongsTo
    {
        return $this->belongsTo(ProjectVariable::class);
    }

    public function vaultEntry(): BelongsTo
    {
        return $this->belongsTo(VaultEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
