<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchCapacitySlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'production_date',
        'capacity_units',
        'locked_units',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
