<?php

namespace App\Support;

class NotificationEvents
{
    public const BRANCH = [
        'order_placed' => 'New order placed to branch',
        'order_accepted' => 'Order accepted by branch',
        'order_rejected' => 'Order rejected by branch',
        'low_stock' => 'Low stock triggered by branch activity',
        'raw_material_low_stock' => 'Raw material stock is low',
        'branch_overbooked' => 'Branch overly booked',
    ];

    public const USER = [
        'order_placed' => 'New order placed',
        'order_accepted' => 'Order accepted',
        'order_rejected' => 'Order rejected',
        'low_stock' => 'Low stock alert',
        'raw_material_low_stock' => 'Raw material low-stock alert',
        'branch_overbooked' => 'Branch overly booked',
        'opening_stock' => 'Daily opening stock summary',
        'closing_stock' => 'Daily closing stock summary',
        'stale_stock' => '72-hour stale stock alert',
    ];

    public static function sanitize(array $events, array $input): array
    {
        return collect($events)
            ->keys()
            ->mapWithKeys(function (string $event) use ($input) {
                return [
                    $event => [
                        'email' => filter_var(data_get($input, "{$event}.email", true), FILTER_VALIDATE_BOOL),
                        'whatsapp' => filter_var(data_get($input, "{$event}.whatsapp", true), FILTER_VALIDATE_BOOL),
                    ],
                ];
            })
            ->all();
    }
}
