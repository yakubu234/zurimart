<?php

return [
    'permissions' => [
        ['slug' => 'view-dashboard', 'name' => 'View Dashboard', 'group' => 'operations', 'description' => 'Access the main admin dashboard.'],
        ['slug' => 'view-orders', 'name' => 'View Orders', 'group' => 'operations', 'description' => 'View and create bakery orders.'],
        ['slug' => 'manage-order-approvals', 'name' => 'Manage Order Approvals', 'group' => 'operations', 'description' => 'Accept or reject submitted orders.'],
        ['slug' => 'view-bookings', 'name' => 'View Advance Bookings', 'group' => 'operations', 'description' => 'Review upcoming advance bookings.'],
        ['slug' => 'manage-branches', 'name' => 'View Branches', 'group' => 'production', 'description' => 'Review production branches and capacity.'],
        ['slug' => 'manage-branch-master-data', 'name' => 'Manage Branch Master Data', 'group' => 'production', 'description' => 'Create, update, and delete branches.'],
        ['slug' => 'manage-products', 'name' => 'Manage Products', 'group' => 'production', 'description' => 'Create, update, and delete products.'],
        ['slug' => 'manage-categories', 'name' => 'Manage Product Categories', 'group' => 'production', 'description' => 'Create, update, and delete product categories.'],
        ['slug' => 'view-reports', 'name' => 'View Reports', 'group' => 'analytics', 'description' => 'Access reports and operational insights.'],
        ['slug' => 'manage-users', 'name' => 'Manage Users', 'group' => 'administration', 'description' => 'Create, update, suspend, and delete users.'],
        ['slug' => 'manage-roles', 'name' => 'Manage Roles', 'group' => 'administration', 'description' => 'Create and manage role definitions.'],
        ['slug' => 'manage-permissions', 'name' => 'Manage Permissions', 'group' => 'administration', 'description' => 'Create and manage permission definitions.'],
        ['slug' => 'manage-integration-settings', 'name' => 'Manage Integration Settings', 'group' => 'administration', 'description' => 'Update notification and integration settings.'],
    ],

    'roles' => [
        [
            'slug' => 'super_admin',
            'name' => 'Super Admin',
            'description' => 'Full platform control across operations, products, users, and settings.',
            'permissions' => [
                'view-dashboard',
                'view-orders',
                'manage-order-approvals',
                'view-bookings',
                'manage-branches',
                'manage-branch-master-data',
                'manage-products',
                'manage-categories',
                'view-reports',
                'manage-users',
                'manage-roles',
                'manage-permissions',
                'manage-integration-settings',
            ],
        ],
        [
            'slug' => 'production_branch_manager',
            'name' => 'Production Branch Manager',
            'description' => 'Supervises branch operations, approvals, production capacity, and reporting.',
            'permissions' => [
                'view-dashboard',
                'view-orders',
                'manage-order-approvals',
                'view-bookings',
                'manage-branches',
                'manage-products',
                'view-reports',
            ],
        ],
        [
            'slug' => 'internal_outlet',
            'name' => 'Internal Outlet',
            'description' => 'Places and tracks orders for internal outlet demand.',
            'permissions' => [
                'view-dashboard',
                'view-orders',
            ],
        ],
        [
            'slug' => 'whole_marketer',
            'name' => 'Whole Marketer',
            'description' => 'Places and tracks wholesale orders and advance bookings.',
            'permissions' => [
                'view-dashboard',
                'view-orders',
                'view-bookings',
            ],
        ],
        [
            'slug' => 'public_retailer',
            'name' => 'Public Retailer',
            'description' => 'Basic retail ordering profile.',
            'permissions' => [],
        ],
    ],
];
