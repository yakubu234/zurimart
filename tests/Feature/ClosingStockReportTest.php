<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventorySnapshot;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClosingStockReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_end_of_period_stock_instead_of_summing_daily_closing_balances(): void
    {
        [$user, $branch, $product] = $this->records();

        $this->snapshot($branch, $product, '2026-08-07', 250);
        $this->snapshot($branch, $product, '2026-08-08', 41);

        $this->actingAs($user)
            ->get(route('dashboard', [
                'date_from' => '2026-08-07',
                'date_to' => '2026-08-08',
                'branch_ids' => [$branch->id],
            ], false))
            ->assertOk()
            ->assertViewHas('closingUnits', 41)
            ->assertViewHas('inventorySummary', function ($summary) use ($branch) {
                return (int) $summary->firstWhere('branch.id', $branch->id)['closing_units'] === 41;
            });
    }

    public function test_reports_page_uses_end_of_period_stock_instead_of_summing_daily_closing_balances(): void
    {
        [$user, $branch, $product] = $this->records();

        $this->snapshot($branch, $product, '2026-08-07', 250);
        $this->snapshot($branch, $product, '2026-08-08', 41);

        $this->actingAs($user)
            ->get(route('reports.index', [
                'date_from' => '2026-08-07',
                'date_to' => '2026-08-08',
                'branch_ids' => [$branch->id],
            ], false))
            ->assertOk()
            ->assertViewHas('inventoryPerformance', function ($summary) use ($branch) {
                return (int) $summary->firstWhere('branch.id', $branch->id)['closing_units'] === 41;
            });
    }

    private function records(): array
    {
        $user = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $branch = Branch::query()->create([
            'code' => 'BR-CLOSING',
            'name' => 'Closing Test Branch',
            'manager_name' => 'Test Manager',
            'daily_capacity_units' => 1000,
            'status' => 'available',
        ]);
        $product = Product::query()->create([
            'sku' => 'CLOSING-LOAF',
            'name' => 'Closing Test Loaf',
            'category' => 'Bread',
            'weight_grams' => 500,
            'retail_price' => 1000,
            'wholesale_price' => 900,
            'stock_units' => 41,
            'is_active' => true,
        ]);

        return [$user, $branch, $product];
    }

    private function snapshot(Branch $branch, Product $product, string $date, int $closing): void
    {
        BranchInventorySnapshot::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'inventory_date' => $date,
            'opening_units' => 0,
            'produced_units' => $closing,
            'sold_units' => 0,
            'adjustment_units' => 0,
            'closing_units' => $closing,
        ]);
    }
}
