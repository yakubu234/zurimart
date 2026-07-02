<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Models\Order;
use App\Support\NotificationEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'notification_preferences',
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
            'notification_preferences' => 'array',
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

    public function createdOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by');
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

    public function isAdministrator(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    public function canManageAllBranches(): bool
    {
        return $this->hasRole('super_admin') || $this->hasPermission('manage-all-branches');
    }

    public function isBranchRestricted(): bool
    {
        return ! $this->canManageAllBranches() && ! is_null($this->branch_id);
    }

    public function canManageAllDailyReports(): bool
    {
        return $this->canManageAllBranches()
            || $this->hasPermission('manage-all-daily-reports');
    }

    public function isDailyReportRestricted(): bool
    {
        return ! $this->isAdministrator() && ! is_null($this->branch_id);
    }

    public function canAccessDailyReportBranch(?int $branchId): bool
    {
        if ($this->isAdministrator()) {
            return true;
        }

        if (is_null($this->branch_id) || is_null($branchId)) {
            return false;
        }

        return (int) $this->branch_id === (int) $branchId;
    }

    public function canManageAllInventory(): bool
    {
        return $this->canManageAllBranches()
            || $this->hasPermission('manage-all-inventory');
    }

    public function isInventoryRestricted(): bool
    {
        return ! $this->isAdministrator() && ! is_null($this->branch_id);
    }

    public function canAccessInventoryBranch(?int $branchId): bool
    {
        if ($this->isAdministrator()) {
            return true;
        }

        if (is_null($this->branch_id) || is_null($branchId)) {
            return false;
        }

        return (int) $this->branch_id === (int) $branchId;
    }

    public function canAccessBranch(?int $branchId): bool
    {
        if ($this->canManageAllBranches()) {
            return true;
        }

        if (is_null($this->branch_id) || is_null($branchId)) {
            return false;
        }

        return (int) $this->branch_id === (int) $branchId;
    }

    public function canEditOrder(Order $order): bool
    {
        if ($this->canManageAllBranches() || $this->hasPermission('manage-users')) {
            return true;
        }

        return ! is_null($order->created_by) && (int) $order->created_by === (int) $this->id;
    }

    public function canDeleteProducts(): bool
    {
        return $this->hasRole('super_admin')
            || $this->hasPermission('delete-products')
            || $this->hasPermission('manage-users');
    }

    public function notificationEnabled(string $eventKey, string $channel): bool
    {
        if (! array_key_exists($eventKey, NotificationEvents::USER)) {
            return true;
        }

        return (bool) data_get($this->notification_preferences ?? [], "{$eventKey}.{$channel}", true);
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
