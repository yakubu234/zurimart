<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReceiptCreatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_displays_the_name_of_the_user_who_created_the_order(): void
    {
        $creator = User::factory()->create([
            'name' => 'Yakubu Abiola',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        $branch = Branch::query()->create([
            'code' => 'ABJ',
            'name' => 'Abuja Production',
            'manager_name' => 'Branch Manager',
            'daily_capacity_units' => 1000,
            'status' => 'available',
        ]);
        $order = Order::query()->create([
            'order_number' => 'ORD-TEST-1',
            'branch_id' => $branch->id,
            'created_by' => $creator->id,
            'customer_name' => 'Test Customer',
            'customer_type' => 'public_retailer',
            'demand_type' => 'retail',
            'pricing_tier' => 'retail',
            'status' => 'pending',
            'scheduled_for' => now()->toDateString(),
        ]);

        $this->actingAs($creator)
            ->get(route('orders.show', $order, false))
            ->assertOk()
            ->assertSee('Created By:')
            ->assertSee($creator->name);
    }
}
