<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'branch_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_type',
        'demand_type',
        'pricing_tier',
        'status',
        'scheduled_for',
        'total_units',
        'total_weight_grams',
        'subtotal_amount',
        'discount_amount',
        'total_amount',
        'notes',
        'rejection_reason',
        'accepted_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class);
    }
}
