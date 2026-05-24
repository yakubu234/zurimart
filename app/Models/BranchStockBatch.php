<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchStockBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'product_id',
        'produced_date',
        'initial_units',
        'remaining_units',
        'last_stale_alerted_at',
    ];

    protected function casts(): array
    {
        return [
            'produced_date' => 'date',
            'last_stale_alerted_at' => 'datetime',
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
