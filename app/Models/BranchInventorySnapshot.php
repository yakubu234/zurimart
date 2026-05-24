<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchInventorySnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'product_id',
        'inventory_date',
        'opening_units',
        'produced_units',
        'sold_units',
        'adjustment_units',
        'closing_units',
    ];

    protected function casts(): array
    {
        return [
            'inventory_date' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
