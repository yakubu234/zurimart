<?php

namespace App\Services;

use Carbon\Carbon;

class InventoryAlertService
{
    public function __construct(
        private readonly BranchInventoryService $inventory,
        private readonly NotificationDispatchService $notifications,
        private readonly AppSettingsService $settings,
    ) {
    }

    public function sendOpeningStockSummary(?string $date = null): void
    {
        if (! $this->settings->bool('notifications.event_opening_stock', true)) {
            return;
        }

        $summary = $this->inventory->openingSummary($date ?? now()->toDateString());
        $message = $this->formatBranchSummaryMessage('Opening stock', $summary['date'], $summary['branches']->all(), $summary['combined_total']);

        $this->notifications->notifyAdmins('Daily opening stock summary', $message, 'opening_stock');
    }

    public function sendClosingStockSummary(?string $date = null): void
    {
        if (! $this->settings->bool('notifications.event_closing_stock', true)) {
            return;
        }

        $summary = $this->inventory->closingSummary($date ?? now()->toDateString());
        $message = $this->formatBranchSummaryMessage('Closing stock', $summary['date'], $summary['branches']->all(), $summary['combined_total']);

        $this->notifications->notifyAdmins('Daily closing stock summary', $message, 'closing_stock');
    }

    public function sendStaleStockSummary(?Carbon $now = null): void
    {
        if (! $this->settings->bool('notifications.event_stale_stock', true)) {
            return;
        }

        $summary = $this->inventory->staleStockSummary($now ?? now());

        if ($summary['stale_batches']->isEmpty()) {
            return;
        }

        $staleLines = $summary['stale_batches']->map(function ($batch) {
            return sprintf(
                '%s: %s has %d unsold unit(s) produced on %s',
                $batch->branch?->name ?? 'Unknown branch',
                $batch->product?->name ?? 'Unknown product',
                $batch->remaining_units,
                $batch->produced_date->format('d M Y')
            );
        })->implode("\n");

        $stockLines = collect($summary['branches'])
            ->map(fn (array $row) => "{$row['branch']->name}: {$row['total_units']} units")
            ->implode("\n");

        $message = trim("72-hour stale stock detected.\n\nStale batches:\n{$staleLines}\n\nCurrent branch stock:\n{$stockLines}\n\nCombined total: {$summary['combined_total']} units");

        $this->notifications->notifyAdmins('72-hour stale stock alert', $message, 'stale_stock');
    }

    protected function formatBranchSummaryMessage(string $label, string $date, array $branches, int $combinedTotal): string
    {
        $branchLines = collect($branches)
            ->map(fn (array $row) => "{$row['branch']->name}: {$row['total_units']} units")
            ->implode("\n");

        return trim("{$label} summary for " . Carbon::parse($date)->format('d M Y') . "\n\n{$branchLines}\n\nCombined total: {$combinedTotal} units");
    }
}
