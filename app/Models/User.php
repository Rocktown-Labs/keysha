<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function workspaceMemberships(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function createdWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'created_by');
    }

    public function currentWorkspace(): Workspace
    {
        $sessionWorkspaceId = session('current_workspace_id');

        if ($sessionWorkspaceId) {
            $workspace = Workspace::where('id', $sessionWorkspaceId)->first();
            if ($workspace && ($workspace->created_by === $this->id || $workspace->members()->where('user_id', $this->id)->exists())) {
                return $workspace;
            }
        }

        return $this->personalWorkspace();
    }

    public function switchWorkspace(string $workspaceId): bool
    {
        $workspace = Workspace::where('id', $workspaceId)->first();
        if ($workspace && ($workspace->created_by === $this->id || $workspace->members()->where('user_id', $this->id)->exists())) {
            session(['current_workspace_id' => $workspace->id]);

            return true;
        }

        return false;
    }

    public function allWorkspaces()
    {
        return Workspace::where('created_by', $this->id)
            ->orWhereHas('members', fn ($query) => $query->where('user_id', $this->id))
            ->get();
    }

    public function personalWorkspace(): Workspace
    {
        return Workspace::where('created_by', $this->id)
            ->where('personal', true)
            ->first() ?? $this->ensurePersonalWorkspace();
    }

    public function ensurePersonalWorkspace(): Workspace
    {
        $workspace = Workspace::where('created_by', $this->id)
            ->where('personal', true)
            ->first();

        if ($workspace) {
            return $workspace;
        }

        $workspace = Workspace::create([
            'name' => "{$this->name}'s Personal Workspace",
            'slug' => Str::slug("{$this->name}-workspace-".Str::random(4)),
            'personal' => true,
            'created_by' => $this->id,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $this->id,
            'role' => 'owner',
        ]);

        return $workspace;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
