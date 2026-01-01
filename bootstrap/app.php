<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'auth.focal' => \App\Http\Middleware\AuthenticateFocal::class,
            'signed.focal' => \App\Http\Middleware\ValidateFocalSignature::class,
            'session.timeout' => \App\Http\Middleware\CheckSessionTimeout::class,
        ]);
        
        $middleware->web(append: [
            \App\Http\Middleware\CheckSessionTimeout::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {

    Log::info('Scheduler loaded: ' . now());

    // ==========================================================
    // 1️⃣ MORNING DASHBOARD UPDATES (3+ days) - ALL ROLES
    // ==========================================================
    
    $schedule->call(function () {
        Log::info('🔄 Morning dashboard update started (all roles, 3+ days)');
        
        $result = app(\App\Services\AgingDocumentService::class)
            ->updateAllDashboards(daysThreshold: 3);
        
        // Log summary
        $summary = $result['summary'];
        Log::info("✅ Morning dashboard update completed", [
            'successful' => $summary['successful'],
            'failed' => $summary['failed'],
            'total_alerted' => $summary['total_alerted'],
            'duration_ms' => $summary['total_duration_ms'],
        ]);
    })
    ->name('morning-dashboard-all')
    ->dailyAt('8:00')
    ->weekdays()
    ->timezone('Asia/Manila');

    // ==========================================================
    // 2️⃣ FOCAL PERSON NOTIFICATIONS (10+ days) - ALL ROLES
    // ==========================================================
    
    $schedule->call(function () {
        Log::info('📧 Focal notifications started (all roles, 10+ days)');
        
        $result = app(\App\Services\AgingDocumentService::class)
            ->notifyAllFocalPersons(daysThreshold: 10);
        
        // Log summary
        $summary = $result['summary'];
        Log::info("✅ Focal notifications completed", [
            'successful' => $summary['successful'],
            'failed' => $summary['failed'],
            'total_notified' => $summary['total_notified'],
            'total_skipped' => $summary['total_skipped'],
            'duration_ms' => $summary['total_duration_ms'],
        ]);
    })
    ->name('focal-notifications-all')
    ->dailyAt('8:30')
    ->weekdays()
    ->timezone('Asia/Manila');

    // ==========================================================
    // 3️⃣ AFTERNOON CRITICAL ALERTS (10+ days) - ALL ROLES
    // ==========================================================
    
    $schedule->call(function () {
        Log::info('🚨 Critical alerts started (all roles, 10+ days)');
        
        $result = app(\App\Services\AgingDocumentService::class)
            ->sendAllCriticalAlerts(daysThreshold: 10);
        
        // Log summary
        $summary = $result['summary'];
        Log::info("✅ Critical alerts completed", [
            'successful' => $summary['successful'],
            'failed' => $summary['failed'],
            'total_alerted' => $summary['total_alerted'],
            'duration_ms' => $summary['total_duration_ms'],
        ]);
    })
    ->name('critical-alerts-all')
    ->dailyAt('15:00')
    ->weekdays()
    ->timezone('Asia/Manila');

    // ==========================================================
    // 4️⃣ SESSION CLEANUP
    // ==========================================================
    
    $schedule->call(function () {
        Log::info('🧹 Session cleanup started');
        app()->make(\App\Http\Controllers\Auth\LoginController::class)
            ->cleanupExpiredSessions();
        Log::info('🧹 Session cleanup completed');
    })
    ->name('cleanup-expired-sessions')
    ->hourly();
})
->create();