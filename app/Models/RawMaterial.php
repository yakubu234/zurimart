<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'unit',
        'low_stock_threshold',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'low_stock_threshold' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(RawMaterialMovement::class);
    }
}
