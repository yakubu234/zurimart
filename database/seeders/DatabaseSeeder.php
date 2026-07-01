<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchCapacitySlot;
use App\Models\Order;
use App\Models\AppSetting;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Order::query()->delete();
        SystemNotification::query()->delete();
        BranchCapacitySlot::query()->delete();
        User::query()->delete();
        Role::query()->delete();
        Permission::query()->delete();
        Product::query()->delete();
        ProductCategory::query()->delete();
        AppSetting::query()->delete();
        Branch::query()->delete();

        $permissions = collect(config('access.permissions', []))
            ->mapWithKeys(function (array $permission) {
                $record = Permission::query()->create([
                    'slug' => $permission['slug'],
                    'name' => $permission['name'],
                    'group' => $permission['group'],
                    'description' => $permission['description'] ?? null,
                    'is_system' => true,
                ]);

                return [$record->slug => $record];
            });

        $roles = collect(config('access.roles', []))
            ->mapWithKeys(function (array $role) use ($permissions) {
                $record = Role::query()->create([
                    'slug' => $role['slug'],
                    'name' => $role['name'],
                    'description' => $role['description'] ?? null,
                    'is_system' => true,
                ]);

                $record->permissions()->sync(
                    collect($role['permissions'] ?? [])
                        ->map(fn (string $slug) => $permissions[$slug]->id ?? null)
                        ->filter()
                        ->all()
                );

                return [$record->slug => $record];
            });

        $branches = collect([
            [
                'code' => 'BR-IKJ',
                'name' => 'Ikeja Production',
                'manager_name' => 'Tunde Bello',
                'email' => 'orders@zurimartbakeryservices.com',
                'phone' => '+234 801 111 2233',
                'address' => 'Ikeja, Lagos',
                'daily_capacity_units' => 1200,
                'status' => 'available',
            ],
            [
                'code' => 'BR-LKK',
                'name' => 'Lekki Production',
                'manager_name' => 'Amaka Eze',
                'email' => 'orders@zurimartbakeryservices.com',
                'phone' => '+234 802 333 4455',
                'address' => 'Lekki, Lagos',
                'daily_capacity_units' => 900,
                'status' => 'overly_booked',
            ],
            [
                'code' => 'BR-SRL',
                'name' => 'Surulere Production',
                'manager_name' => 'Femi Ade',
                'email' => 'orders@zurimartbakeryservices.com',
                'phone' => '+234 803 555 6677',
                'address' => 'Surulere, Lagos',
                'daily_capacity_units' => 1000,
                'status' => 'available',
            ],
            [
                'code' => 'BR-ABJ',
                'name' => 'Abuja Production',
                'manager_name' => 'Hauwa Musa',
                'email' => 'orders@zurimartbakeryservices.com',
                'phone' => '+234 805 777 8899',
                'address' => 'Wuse, Abuja',
                'daily_capacity_units' => 1500,
                'status' => 'available',
            ],
        ])->mapWithKeys(fn (array $branch) => [$branch['code'] => Branch::query()->create($branch)]);

        $categories = collect([
            ['name' => 'Core', 'slug' => 'core', 'description' => 'Core bread varieties in the ZuriMart menu.', 'sort_order' => 1, 'is_active' => true],
            ['name' => 'Loaf', 'slug' => 'loaf', 'description' => 'Alternative loaf size variations.', 'sort_order' => 2, 'is_active' => true],
            ['name' => 'Specialty', 'slug' => 'specialty', 'description' => 'Special bakery items and burger bread packs.', 'sort_order' => 3, 'is_active' => true],
        ])->mapWithKeys(fn (array $category) => [$category['name'] => ProductCategory::query()->create($category)]);

        $products = collect([
            ['sku' => 'P-001', 'name' => 'Sandine 600g', 'category' => 'Core', 'weight_grams' => 600, 'retail_price' => 800, 'wholesale_price' => 720, 'stock_units' => 420],
            ['sku' => 'P-002', 'name' => 'Chocolate 600g', 'category' => 'Core', 'weight_grams' => 600, 'retail_price' => 1000, 'wholesale_price' => 900, 'stock_units' => 310],
            ['sku' => 'P-003', 'name' => 'Fruit 600g', 'category' => 'Core', 'weight_grams' => 600, 'retail_price' => 950, 'wholesale_price' => 860, 'stock_units' => 180],
            ['sku' => 'P-004', 'name' => 'Coconut 600g', 'category' => 'Core', 'weight_grams' => 600, 'retail_price' => 950, 'wholesale_price' => 860, 'stock_units' => 220],
            ['sku' => 'P-005', 'name' => 'Wheat 600g', 'category' => 'Core', 'weight_grams' => 600, 'retail_price' => 1100, 'wholesale_price' => 990, 'stock_units' => 260],
            ['sku' => 'P-006', 'name' => 'Mini Loaf 400g', 'category' => 'Loaf', 'weight_grams' => 400, 'retail_price' => 600, 'wholesale_price' => 540, 'stock_units' => 540],
            ['sku' => 'P-007', 'name' => 'Standard 850g', 'category' => 'Loaf', 'weight_grams' => 850, 'retail_price' => 1200, 'wholesale_price' => 1080, 'stock_units' => 360],
            ['sku' => 'P-008', 'name' => 'Bigi 1.2kg', 'category' => 'Loaf', 'weight_grams' => 1200, 'retail_price' => 1700, 'wholesale_price' => 1530, 'stock_units' => 140],
            ['sku' => 'P-009', 'name' => 'Family Size 1.2kg', 'category' => 'Loaf', 'weight_grams' => 1200, 'retail_price' => 1800, 'wholesale_price' => 1620, 'stock_units' => 120],
            ['sku' => 'P-010', 'name' => 'Burger Bread (Pack of 6)', 'category' => 'Specialty', 'weight_grams' => 480, 'retail_price' => 900, 'wholesale_price' => 810, 'stock_units' => 95],
            ['sku' => 'P-011', 'name' => 'Burger Bread (Pack of 12)', 'category' => 'Specialty', 'weight_grams' => 960, 'retail_price' => 1700, 'wholesale_price' => 1530, 'stock_units' => 60],
        ])->mapWithKeys(fn (array $product) => [$product['sku'] => Product::query()->create($product + ['category_id' => $categories[$product['category']]->id])]);

        $users = [
            ['name' => 'Tunde Bello', 'email' => 'tunde@zurimartbakeryservices.com', 'phone' => '+234 801 111 2233', 'role' => 'production_branch_manager', 'status' => 'active', 'branch_id' => $branches['BR-IKJ']->id],
            ['name' => 'Amaka Eze', 'email' => 'amaka@zurimartbakeryservices.com', 'phone' => '+234 802 333 4455', 'role' => 'production_branch_manager', 'status' => 'active', 'branch_id' => $branches['BR-LKK']->id],
            ['name' => 'Hauwa Musa', 'email' => 'hauwa@zurimartbakeryservices.com', 'phone' => '+234 805 777 8899', 'role' => 'production_branch_manager', 'status' => 'active', 'branch_id' => $branches['BR-ABJ']->id],
            ['name' => 'Mama Ngozi Minimart', 'email' => 'ngozi@minimart.ng', 'phone' => '+234 803 000 1111', 'role' => 'internal_outlet', 'status' => 'active', 'branch_id' => null],
            ['name' => 'BigBite Wholesalers', 'email' => 'ops@bigbite.ng', 'phone' => '+234 804 222 3333', 'role' => 'whole_marketer', 'status' => 'active', 'branch_id' => null],
            ['name' => 'Chinedu Obi', 'email' => 'info@zurimartbakeryservices.com', 'phone' => '+234 800 123 4567', 'role' => 'super_admin', 'status' => 'active', 'branch_id' => null],
        ];

        foreach ($users as $user) {
            $role = $roles[$user['role']];

            User::query()->create($user + [
                'role_code' => $role->slug,
                'role_id' => $role->id,
                'password' => Hash::make('password'),
            ]);
        }

        foreach ($branches as $branch) {
            foreach (range(0, 6) as $offset) {
                BranchCapacitySlot::query()->create([
                    'branch_id' => $branch->id,
                    'production_date' => Carbon::now()->addDays($offset)->toDateString(),
                    'capacity_units' => $branch->daily_capacity_units,
                    'locked_units' => match ($branch->code) {
                        'BR-IKJ' => $offset === 0 ? 870 : 0,
                        'BR-LKK' => $offset === 0 ? 900 : 0,
                        'BR-SRL' => $offset === 0 ? 540 : 0,
                        'BR-ABJ' => $offset === 0 ? 1100 : 0,
                        default => 0,
                    },
                ]);
            }
        }

        $seedOrders = [
            [
                'order_number' => 'ORD-10241',
                'branch_id' => $branches['BR-IKJ']->id,
                'customer_name' => 'Mama Ngozi Minimart',
                'customer_email' => 'ngozi@minimart.ng',
                'customer_phone' => '+234 803 000 1111',
                'customer_type' => 'internal_outlet',
                'demand_type' => 'retail',
                'pricing_tier' => 'retail',
                'status' => 'accepted',
                'scheduled_for' => now()->toDateString(),
                'total_units' => 35,
                'total_weight_grams' => 21000,
                'subtotal_amount' => 28000,
                'discount_amount' => 0,
                'total_amount' => 28000,
                'accepted_at' => now()->subHours(3),
                'created_at' => now()->subHours(5),
            ],
            [
                'order_number' => 'ORD-10242',
                'branch_id' => $branches['BR-ABJ']->id,
                'customer_name' => 'BigBite Wholesalers',
                'customer_email' => 'ops@bigbite.ng',
                'customer_phone' => '+234 804 222 3333',
                'customer_type' => 'whole_marketer',
                'demand_type' => 'wholesale',
                'pricing_tier' => 'wholesale',
                'status' => 'pending',
                'scheduled_for' => now()->addDays(1)->toDateString(),
                'total_units' => 220,
                'total_weight_grams' => 132000,
                'subtotal_amount' => 198000,
                'discount_amount' => 22000,
                'total_amount' => 198000,
                'created_at' => now()->subHours(2),
            ],
            [
                'order_number' => 'ORD-10243',
                'branch_id' => $branches['BR-SRL']->id,
                'customer_name' => 'Walk-in Retailer',
                'customer_email' => null,
                'customer_phone' => '+234 802 100 2000',
                'customer_type' => 'public_retailer',
                'demand_type' => 'retail',
                'pricing_tier' => 'retail',
                'status' => 'completed',
                'scheduled_for' => now()->subDay()->toDateString(),
                'total_units' => 4,
                'total_weight_grams' => 2400,
                'subtotal_amount' => 4400,
                'discount_amount' => 0,
                'total_amount' => 4400,
                'created_at' => now()->subDay(),
            ],
            [
                'order_number' => 'ORD-10244',
                'branch_id' => $branches['BR-LKK']->id,
                'customer_name' => 'Lekki Outlet',
                'customer_email' => 'lekki.outlet@zurimartbakeryservices.com',
                'customer_phone' => '+234 801 888 9999',
                'customer_type' => 'internal_outlet',
                'demand_type' => 'wholesale',
                'pricing_tier' => 'wholesale',
                'status' => 'rejected',
                'scheduled_for' => now()->toDateString(),
                'total_units' => 80,
                'total_weight_grams' => 48000,
                'subtotal_amount' => 72000,
                'discount_amount' => 8000,
                'total_amount' => 72000,
                'rejected_at' => now()->subHours(7),
                'rejection_reason' => 'Selected branch is already fully booked for today.',
                'created_at' => now()->subHours(9),
            ],
        ];

        foreach ($seedOrders as $seedOrder) {
            $order = Order::query()->create($seedOrder);

            $order->items()->create([
                'product_id' => $products['P-001']->id,
                'product_name' => $products['P-001']->name,
                'product_sku' => $products['P-001']->sku,
                'unit_weight_grams' => $products['P-001']->weight_grams,
                'quantity' => max(1, intdiv($order->total_units, 2)),
                'unit_price' => $order->pricing_tier === 'wholesale' ? $products['P-001']->wholesale_price : $products['P-001']->retail_price,
                'line_total' => max(1, intdiv($order->total_units, 2)) * (float) ($order->pricing_tier === 'wholesale' ? $products['P-001']->wholesale_price : $products['P-001']->retail_price),
            ]);

            $order->items()->create([
                'product_id' => $products['P-002']->id,
                'product_name' => $products['P-002']->name,
                'product_sku' => $products['P-002']->sku,
                'unit_weight_grams' => $products['P-002']->weight_grams,
                'quantity' => $order->total_units - max(1, intdiv($order->total_units, 2)),
                'unit_price' => $order->pricing_tier === 'wholesale' ? $products['P-002']->wholesale_price : $products['P-002']->retail_price,
                'line_total' => ($order->total_units - max(1, intdiv($order->total_units, 2))) * (float) ($order->pricing_tier === 'wholesale' ? $products['P-002']->wholesale_price : $products['P-002']->retail_price),
            ]);
        }

        SystemNotification::query()->create([
            'branch_id' => $branches['BR-ABJ']->id,
            'order_id' => Order::query()->where('order_number', 'ORD-10242')->value('id'),
            'channel' => 'email',
            'title' => 'New wholesale order tagged to Abuja Production',
            'message' => 'BigBite Wholesalers has tagged Abuja Production for a 220-unit booking scheduled for tomorrow.',
            'status' => 'queued',
        ]);

        AppSetting::query()->insert([
            [
                'group' => 'notifications',
                'key' => 'notifications.email_enabled',
                'value' => '0',
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.email_host',
                'value' => env('MAIL_HOST', '127.0.0.1'),
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.email_port',
                'value' => (string) env('MAIL_PORT', 2525),
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.email_from_address',
                'value' => env('MAIL_FROM_ADDRESS', 'info@zurimartbakeryservices.com'),
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.email_from_name',
                'value' => env('MAIL_FROM_NAME', 'ZuriMart Bakery'),
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.admin_email_recipient',
                'value' => 'info@zurimartbakeryservices.com',
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.whatsapp_enabled',
                'value' => '0',
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.admin_whatsapp_recipient',
                'value' => '',
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.low_stock_threshold',
                'value' => '150',
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'orders',
                'key' => 'orders.retail_minimum_units',
                'value' => '1',
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'orders',
                'key' => 'orders.wholesale_minimum_units',
                'value' => '50',
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.event_order_placed',
                'value' => '1',
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.event_raw_material_low_stock',
                'value' => '1',
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.event_order_accepted',
                'value' => '1',
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.event_order_rejected',
                'value' => '1',
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.event_low_stock',
                'value' => '1',
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'notifications',
                'key' => 'notifications.event_branch_overbooked',
                'value' => '1',
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
