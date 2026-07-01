<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use App\Services\AppSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private readonly AppSettingsService $settings)
    {
    }

    public function edit(): View
    {
        $notificationSettings = $this->settings->group('notifications');
        $orderSettings = $this->settings->group('orders');
        $recentNotifications = SystemNotification::query()->latest()->take(10)->get();

        return view('settings.edit', compact('notificationSettings', 'orderSettings', 'recentNotifications'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email_enabled' => ['nullable', 'boolean'],
            'email_host' => ['nullable', 'string', 'max:255'],
            'email_port' => ['nullable', 'integer', 'min:1'],
            'email_encryption' => ['nullable', 'string', 'max:50'],
            'email_username' => ['nullable', 'string', 'max:255'],
            'email_password' => ['nullable', 'string', 'max:255'],
            'email_from_address' => ['nullable', 'email', 'max:255'],
            'email_from_name' => ['nullable', 'string', 'max:255'],
            'admin_email_recipient' => ['nullable', 'email', 'max:255'],
            'whatsapp_enabled' => ['nullable', 'boolean'],
            'whatsapp_api_url' => ['nullable', 'url', 'max:500'],
            'whatsapp_token' => ['nullable', 'string', 'max:1000'],
            'whatsapp_phone_number_id' => ['nullable', 'string', 'max:255'],
            'whatsapp_from_number' => ['nullable', 'string', 'max:255'],
            'admin_whatsapp_recipient' => ['nullable', 'string', 'max:255'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'retail_minimum_units' => ['required', 'integer', 'min:1'],
            'wholesale_minimum_units' => ['required', 'integer', 'gte:retail_minimum_units'],
            'event_order_placed' => ['nullable', 'boolean'],
            'event_order_accepted' => ['nullable', 'boolean'],
            'event_order_rejected' => ['nullable', 'boolean'],
            'event_low_stock' => ['nullable', 'boolean'],
            'event_raw_material_low_stock' => ['nullable', 'boolean'],
            'event_branch_overbooked' => ['nullable', 'boolean'],
            'event_opening_stock' => ['nullable', 'boolean'],
            'event_closing_stock' => ['nullable', 'boolean'],
            'event_stale_stock' => ['nullable', 'boolean'],
        ]);

        $currentEmailPassword = $this->settings->get('notifications.email_password');
        $currentWhatsAppToken = $this->settings->get('notifications.whatsapp_token');

        $normalized = [
            'notifications.email_enabled' => $request->boolean('email_enabled'),
            'notifications.email_host' => $data['email_host'] ?? null,
            'notifications.email_port' => $data['email_port'] ?? null,
            'notifications.email_encryption' => $data['email_encryption'] ?? null,
            'notifications.email_username' => $data['email_username'] ?? null,
            'notifications.email_password' => filled($data['email_password'] ?? null) ? $data['email_password'] : $currentEmailPassword,
            'notifications.email_from_address' => $data['email_from_address'] ?? null,
            'notifications.email_from_name' => $data['email_from_name'] ?? null,
            'notifications.admin_email_recipient' => $data['admin_email_recipient'] ?? null,
            'notifications.whatsapp_enabled' => $request->boolean('whatsapp_enabled'),
            'notifications.whatsapp_api_url' => $data['whatsapp_api_url'] ?? null,
            'notifications.whatsapp_token' => filled($data['whatsapp_token'] ?? null) ? $data['whatsapp_token'] : $currentWhatsAppToken,
            'notifications.whatsapp_phone_number_id' => $data['whatsapp_phone_number_id'] ?? null,
            'notifications.whatsapp_from_number' => $data['whatsapp_from_number'] ?? null,
            'notifications.admin_whatsapp_recipient' => $data['admin_whatsapp_recipient'] ?? null,
            'notifications.low_stock_threshold' => $data['low_stock_threshold'] ?? 150,
            'notifications.event_order_placed' => $request->boolean('event_order_placed', true),
            'notifications.event_order_accepted' => $request->boolean('event_order_accepted', true),
            'notifications.event_order_rejected' => $request->boolean('event_order_rejected', true),
            'notifications.event_low_stock' => $request->boolean('event_low_stock', true),
            'notifications.event_raw_material_low_stock' => $request->boolean('event_raw_material_low_stock', true),
            'notifications.event_branch_overbooked' => $request->boolean('event_branch_overbooked', true),
            'notifications.event_opening_stock' => $request->boolean('event_opening_stock', true),
            'notifications.event_closing_stock' => $request->boolean('event_closing_stock', true),
            'notifications.event_stale_stock' => $request->boolean('event_stale_stock', true),
        ];

        $this->settings->setMany('notifications', $normalized, [
            'notifications.email_password',
            'notifications.whatsapp_token',
        ]);

        $this->settings->setMany('orders', [
            'orders.retail_minimum_units' => $data['retail_minimum_units'],
            'orders.wholesale_minimum_units' => $data['wholesale_minimum_units'],
        ]);

        return back()->with('success', 'System settings updated successfully.');
    }
}
