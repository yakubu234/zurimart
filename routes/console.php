<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\InventoryAlertService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('inventory:send-opening-stock-summary {--date=}', function (InventoryAlertService $alerts) {
    $alerts->sendOpeningStockSummary($this->option('date'));
    $this->info('Opening stock summary notification dispatched.');
})->purpose('Send the daily opening stock summary to admin recipients.');

Artisan::command('inventory:send-closing-stock-summary {--date=}', function (InventoryAlertService $alerts) {
    $alerts->sendClosingStockSummary($this->option('date'));
    $this->info('Closing stock summary notification dispatched.');
})->purpose('Send the daily closing stock summary to admin recipients.');

Artisan::command('inventory:send-stale-stock-summary', function (InventoryAlertService $alerts) {
    $alerts->sendStaleStockSummary();
    $this->info('Stale stock summary notification dispatched.');
})->purpose('Send stale stock alerts for batches older than 72 hours.');

Schedule::command('inventory:send-opening-stock-summary')->dailyAt('06:00');
Schedule::command('inventory:send-closing-stock-summary')->dailyAt('20:00');
Schedule::command('inventory:send-stale-stock-summary')->dailyAt('08:00');
