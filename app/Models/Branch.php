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
        'address',
        'daily_capacity_units',
        'status',
    ];

    public function capacitySlots(): HasMany
    {
        return $this->hasMany(BranchCapacitySlot::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
