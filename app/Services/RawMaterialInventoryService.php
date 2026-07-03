<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\RawMaterial;
use App\Models\RawMaterialMovement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function recentMovements(Branch $branch, int $perPage = 10, string $pageName = 'activity_page'): LengthAwarePaginator
    {
        return RawMaterialMovement::query()
            ->with(['rawMaterial', 'recorder'])
            ->where('branch_id', $branch->id)
            ->latest('movement_date')
            ->latest('id')
            ->paginate($perPage, ['*'], $pageName);
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

    public function updateMovement(RawMaterialMovement $movement, array $data): RawMaterialMovement
    {
        return DB::transaction(function () use ($movement, $data) {
            $movement = RawMaterialMovement::query()->lockForUpdate()->findOrFail($movement->id);
            $oldMaterialId = (int) $movement->raw_material_id;
            $newMaterialId = (int) $data['raw_material_id'];
            $materialIds = array_values(array_unique([$oldMaterialId, $newMaterialId]));

            $otherMovements = RawMaterialMovement::query()
                ->where('branch_id', $movement->branch_id)
                ->whereIn('raw_material_id', $materialIds)
                ->whereKeyNot($movement->id)
                ->lockForUpdate()
                ->get()
                ->groupBy('raw_material_id');

            $remainingOldBalance = $this->movementBalance($otherMovements->get($oldMaterialId, collect()));

            if ($oldMaterialId !== $newMaterialId && $remainingOldBalance < 0) {
                $this->throwNegativeBalanceError($oldMaterialId);
            }

            $newBalance = $this->movementBalance($otherMovements->get($newMaterialId, collect()))
                + ($data['movement_type'] === 'received' ? (float) $data['quantity'] : -((float) $data['quantity']));

            if ($newBalance < 0) {
                $this->throwNegativeBalanceError($newMaterialId);
            }

            $movement->update([
                'raw_material_id' => $newMaterialId,
                'movement_type' => $data['movement_type'],
                'quantity' => $data['quantity'],
                'movement_date' => $data['movement_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            return $movement->load(['rawMaterial', 'branch', 'recorder']);
        });
    }

    public function deleteMovement(RawMaterialMovement $movement): void
    {
        DB::transaction(function () use ($movement): void {
            $movement = RawMaterialMovement::query()->lockForUpdate()->findOrFail($movement->id);
            $remainingMovements = RawMaterialMovement::query()
                ->where('branch_id', $movement->branch_id)
                ->where('raw_material_id', $movement->raw_material_id)
                ->whereKeyNot($movement->id)
                ->lockForUpdate()
                ->get();

            if ($this->movementBalance($remainingMovements) < 0) {
                $this->throwNegativeBalanceError((int) $movement->raw_material_id);
            }

            $movement->delete();
        });
    }

    private function movementBalance(Collection $movements): float
    {
        return (float) $movements->sum(
            fn (RawMaterialMovement $movement) => $movement->movement_type === 'received'
                ? (float) $movement->quantity
                : -((float) $movement->quantity)
        );
    }

    private function throwNegativeBalanceError(int $materialId): never
    {
        $material = RawMaterial::query()->find($materialId);
        $materialName = $material?->name ?? 'this raw material';

        throw ValidationException::withMessages([
            'movement' => "This change cannot be saved because it would leave {$materialName} with negative stock.",
        ]);
    }

    private function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');
    }
}
