<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncRunItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'sync_run_id',
        'integration_mapping_id',
        'status',
        'error_code',
        'sanitized_error',
    ];

    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(SyncRun::class);
    }

    public function mapping(): BelongsTo
    {
        return $this->belongsTo(IntegrationMapping::class, 'integration_mapping_id');
    }
}
