<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\AppSettingsService;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderMinimumSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::query()->create([
            'sku' => 'TEST-001',
            'name' => 'Test Loaf',
            'category' => 'Test',
            'weight_grams' => 500,
            'retail_price' => 1000,
            'wholesale_price' => 800,
            'stock_units' => 1000,
            'is_active' => true,
        ]);

        app(AppSettingsService::class)->setMany('orders', [
            'orders.retail_minimum_units' => 5,
            'orders.wholesale_minimum_units' => 20,
        ]);
    }

    public function test_configured_wholesale_minimum_controls_pricing_tier(): void
    {
        $retailSummary = $this->buildSummary(19);
        $wholesaleSummary = $this->buildSummary(20);

        $this->assertSame('retail', $retailSummary['pricing_tier']);
        $this->assertSame(19000.0, $retailSummary['total_amount']);
        $this->assertSame('wholesale', $wholesaleSummary['pricing_tier']);
        $this->assertSame(16000.0, $wholesaleSummary['total_amount']);
    }

    public function test_order_below_configured_retail_minimum_is_rejected(): void
    {
        try {
            $this->buildSummary(4);
            $this->fail('The order should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['A retail order must contain at least 5 units.'],
                $exception->errors()['items']
            );
        }
    }

    private function buildSummary(int $quantity): array
    {
        $method = new \ReflectionMethod(OrderWorkflowService::class, 'buildOrderSummary');

        return $method->invoke(app(OrderWorkflowService::class), [
            'scheduled_for' => now()->toDateString(),
            'items' => [$this->product->id => $quantity],
        ]);
    }
}
