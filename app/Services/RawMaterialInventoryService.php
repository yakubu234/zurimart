<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\RawMaterial;
use App\Models\RawMaterialMovement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RawMaterialInventoryService
{
    public function __construct(
        private readonly NotificationDispatchService $notifications,
        private readonly AppSettingsService $settings
    ) {
    }

    public function stockRows(Branch $branch): Collection
    {
        $balances = RawMaterialMovement::query()
            ->select('raw_material_id')
            ->selectRaw("SUM(CASE WHEN movement_type = 'received' THEN quantity ELSE -quantity END) as balance")
            ->where('branch_id', $branch->id)
            ->groupBy('raw_material_id')
            ->pluck('balance', 'raw_material_id');

        return RawMaterial::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (RawMaterial $material) use ($balances) {
                $balance = (float) ($balances[$material->id] ?? 0);

                return [
                    'material' => $material,
                    'balance' => $balance,
                    'is_low' => $balance <= (float) $material->low_stock_threshold,
                ];
            });
    }

    public function recentMovements(Branch $branch, int $limit = 25): Collection
    {
        return RawMaterialMovement::query()
            ->with(['rawMaterial', 'recorder'])
            ->where('branch_id', $branch->id)
            ->latest('movement_date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function recordMovement(Branch $branch, RawMaterial $material, User $user, array $data): RawMaterialMovement
    {
        [$movement, $balance, $shouldAlert] = DB::transaction(function () use ($branch, $material, $user, $data) {
            $material = RawMaterial::query()->lockForUpdate()->findOrFail($material->id);
            $existingMovements = RawMaterialMovement::query()
                ->where('branch_id', $branch->id)
                ->where('raw_material_id', $material->id)
                ->lockForUpdate()
                ->get();

            $previousBalance = $existingMovements->sum(
                fn (RawMaterialMovement $movement) => $movement->movement_type === 'received'
                    ? (float) $movement->quantity
                    : -((float) $movement->quantity)
            );
            $quantity = (float) $data['quantity'];

            if ($data['movement_type'] === 'used' && $quantity > $previousBalance) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$this->formatQuantity($previousBalance)} {$material->unit} of {$material->name} is available at {$branch->name}.",
                ]);
            }

            $balance = $previousBalance + ($data['movement_type'] === 'received' ? $quantity : -$quantity);
            $movement = RawMaterialMovement::query()->create([
                'branch_id' => $branch->id,
                'raw_material_id' => $material->id,
                'recorded_by' => $user->id,
                'movement_date' => $data['movement_date'],
                'movement_type' => $data['movement_type'],
                'quantity' => $quantity,
                'notes' => $data['notes'] ?? null,
            ]);

            $threshold = (float) $material->low_stock_threshold;
            $shouldAlert = $balance <= $threshold
                && ($existingMovements->isEmpty() || $previousBalance > $threshold);

            return [$movement, $balance, $shouldAlert];
        });

        if ($shouldAlert && $this->settings->bool('notifications.event_raw_material_low_stock', true)) {
            $this->notifications->notifyRawMaterialLowStock($material, $branch, $balance);
        }

        return $movement->load(['rawMaterial', 'branch', 'recorder']);
    }

    private function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');
    }
}
