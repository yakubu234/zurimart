<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'branch_id',
        'role',
        'role_code',
        'role_id',
        'status',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function roleRecord(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps()->orderBy('group')->orderBy('name');
    }

    public function roleKey(): ?string
    {
        if ($this->relationLoaded('roleRecord') && $this->getRelation('roleRecord')) {
            return $this->getRelation('roleRecord')->slug;
        }

        return $this->role_code ?: $this->role;
    }

    public function hasRole(string $role): bool
    {
        return $this->roleKey() === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->roleKey(), $roles, true);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $roleKey = $this->roleKey();
        $defaultPermissions = collect(config('access.roles', []))
            ->firstWhere('slug', $roleKey)['permissions'] ?? [];

        if (in_array($permission, $defaultPermissions, true)) {
            return true;
        }

        if ($this->relationLoaded('permissions')) {
            if ($this->permissions->contains(fn (Permission $item) => $item->slug === $permission)) {
                return true;
            }
        } elseif ($this->permissions()->where('slug', $permission)->exists()) {
            return true;
        }

        if ($this->relationLoaded('roleRecord') && $this->getRelation('roleRecord')?->relationLoaded('permissions')) {
            return $this->getRelation('roleRecord')->permissions->contains(fn (Permission $item) => $item->slug === $permission);
        }

        return $this->roleRecord()
            ->whereHas('permissions', fn ($query) => $query->where('slug', $permission))
            ->exists();
    }

    public static function legacyRoleKeys(): array
    {
        return collect(config('access.roles', []))
            ->pluck('slug')
            ->filter(fn (?string $slug) => ! is_null($slug))
            ->values()
            ->all();
    }

    public function directPermissionLabels(): Collection
    {
        return $this->permissions->pluck('name');
    }
}
