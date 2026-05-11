<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\SystemNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationDispatchService
{
    public function __construct(private readonly AppSettingsService $settings)
    {
    }

    public function notifyBranch(Branch $branch, Order $order, string $title, string $message): void
    {
        $payload = ['order_number' => $order->order_number, 'branch' => $branch->name];
        $this->send('email', $branch->email, $title, $message, $branch->id, $order->id, $payload);
        $this->send('whatsapp', $branch->phone, $title, $message, $branch->id, $order->id, $payload);
    }

    public function notifyAdmins(string $title, string $message, ?Order $order = null, ?Branch $branch = null, array $payload = []): void
    {
        $payload = array_merge($payload, [
            'order_number' => $order?->order_number,
            'branch' => $branch?->name,
        ]);

        $this->send(
            'email',
            $this->settings->get('notifications.admin_email_recipient'),
            $title,
            $message,
            $branch?->id,
            $order?->id,
            $payload
        );

        $this->send(
            'whatsapp',
            $this->settings->get('notifications.admin_whatsapp_recipient'),
            $title,
            $message,
            $branch?->id,
            $order?->id,
            $payload
        );
    }

    public function notifyLowStock(Product $product, ?Branch $branch = null, ?Order $order = null): void
    {
        $threshold = (int) $this->settings->get('notifications.low_stock_threshold', 150);
        $title = 'Low stock alert';
        $message = "{$product->name} is now at {$product->stock_units} units, below the low stock threshold of {$threshold}.";

        $this->notifyAdmins($title, $message, $order, $branch, [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'stock_units' => $product->stock_units,
            'threshold' => $threshold,
        ]);
    }

    public function notifyBranchOverbooked(Branch $branch, ?Order $order = null): void
    {
        $title = 'Branch capacity reached';
        $message = "{$branch->name} has reached or exceeded the configured oven capacity and is now overly booked.";

        $this->notifyAdmins($title, $message, $order, $branch, [
            'branch_status' => $branch->status,
            'daily_capacity_units' => $branch->daily_capacity_units,
        ]);
    }

    protected function send(
        string $channel,
        ?string $recipient,
        string $title,
        string $message,
        ?int $branchId = null,
        ?int $orderId = null,
        array $payload = []
    ): void {
        if (! filled($recipient) || ! $this->channelEnabled($channel)) {
            return;
        }

        $notification = SystemNotification::query()->create([
            'branch_id' => $branchId,
            'order_id' => $orderId,
            'channel' => $channel,
            'recipient' => $recipient,
            'title' => $title,
            'message' => $message,
            'payload' => $payload,
            'status' => 'queued',
        ]);

        if ($channel === 'email') {
            $this->dispatchEmail($notification);
            return;
        }

        if ($channel === 'whatsapp') {
            $this->dispatchWhatsApp($notification);
        }
    }

    protected function channelEnabled(string $channel): bool
    {
        return match ($channel) {
            'email' => $this->settings->bool('notifications.email_enabled'),
            'whatsapp' => $this->settings->bool('notifications.whatsapp_enabled'),
            default => false,
        };
    }

    protected function dispatchEmail(SystemNotification $notification): void
    {
        try {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.transport', 'smtp');
            Config::set('mail.mailers.smtp.host', $this->settings->get('notifications.email_host', '127.0.0.1'));
            Config::set('mail.mailers.smtp.port', (int) $this->settings->get('notifications.email_port', 2525));
            Config::set('mail.mailers.smtp.encryption', $this->settings->get('notifications.email_encryption'));
            Config::set('mail.mailers.smtp.username', $this->settings->get('notifications.email_username'));
            Config::set('mail.mailers.smtp.password', $this->settings->get('notifications.email_password'));
            Config::set('mail.from.address', $this->settings->get('notifications.email_from_address', config('mail.from.address')));
            Config::set('mail.from.name', $this->settings->get('notifications.email_from_name', config('mail.from.name')));

            Mail::raw($notification->message, function ($mail) use ($notification) {
                $mail->to($notification->recipient)->subject($notification->title);
            });

            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ]);
        } catch (Throwable $throwable) {
            $notification->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $throwable->getMessage(),
            ]);
        }
    }

    protected function dispatchWhatsApp(SystemNotification $notification): void
    {
        try {
            $apiUrl = $this->settings->get('notifications.whatsapp_api_url');
            $token = $this->settings->get('notifications.whatsapp_token');

            if (! filled($apiUrl) || ! filled($token)) {
                throw new \RuntimeException('WhatsApp API URL or token is not configured.');
            }

            Http::withToken($token)
                ->acceptJson()
                ->post($apiUrl, [
                    'to' => $notification->recipient,
                    'message' => $notification->message,
                    'title' => $notification->title,
                    'phone_number_id' => $this->settings->get('notifications.whatsapp_phone_number_id'),
                    'from_number' => $this->settings->get('notifications.whatsapp_from_number'),
                ])
                ->throw();

            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ]);
        } catch (Throwable $throwable) {
            $notification->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $throwable->getMessage(),
            ]);
        }
    }
}
