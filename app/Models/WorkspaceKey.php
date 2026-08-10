<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceKey extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id',
        'version',
        'wrapped_key',
        'nonce',
        'master_key_version',
        'active',
    ];

    protected $casts = [
        'version' => 'integer',
        'active' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
