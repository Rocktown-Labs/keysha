<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectVariable extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'project_id',
        'key',
        'classification',
        'description',
        'provider_hint',
        'required',
        'position',
        'created_by',
    ];

    protected $casts = [
        'required' => 'boolean',
        'position' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bindings(): HasMany
    {
        return $this->hasMany(EnvironmentBinding::class);
    }
}
