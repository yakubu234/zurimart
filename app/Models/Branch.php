<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'manager_name',
        'email',
        'phone',
        'whatsapp_phone',
        'notification_preferences',
        'address',
        'daily_capacity_units',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'notification_preferences' => 'array',
        ];
    }

    public function capacitySlots(): HasMany
    {
        return $this->hasMany(BranchCapacitySlot::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function notificationEnabled(string $eventKey, string $channel): bool
    {
        return (bool) data_get($this->notification_preferences ?? [], "{$eventKey}.{$channel}", true);
    }
}
