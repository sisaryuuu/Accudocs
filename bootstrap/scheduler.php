<?php

use App\Http\Controllers\IMSD\DvRequestAgingController;
use Illuminate\Console\Scheduling\Schedule;

return function (Schedule $schedule) {
    // Run aging alerts daily at 8:00 AM on weekdays
    $schedule->call(function () {
        app(DvRequestAgingController::class)->sendAgingAlerts();
    })->name('send-aging-alerts')
      ->dailyAt('08:00')
      ->weekdays()
      ->timezone('Asia/Manila')
      ->description('Send aging document alerts to IMSD team');
    
    // Optional: Also run at 3:00 PM for critical documents
    $schedule->call(function () {
        // You could create a separate method for critical alerts only
        app(DvRequestAgingController::class)->sendCriticalAgingAlerts();
    })->name('send-critical-alerts')
      ->dailyAt('15:00')
      ->weekdays()
      ->timezone('Asia/Manila');
};;