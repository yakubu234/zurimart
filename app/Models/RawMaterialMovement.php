<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'raw_material_id',
        'recorded_by',
        'movement_date',
        'movement_type',
        'quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'quantity' => 'decimal:3',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
