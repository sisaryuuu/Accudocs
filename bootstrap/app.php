<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
 ->withCommands([
        \App\Console\Commands\SendDocumentAgingEmailNotifications::class,
        // Add other commands here
    ])
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
    })
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*');
    
    })->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {

    Log::info('Scheduler loaded: ' . now());

    // ==========================================================
    // 0️⃣ MORNING AGING EMAIL NOTIFICATIONS - ALL DEPARTMENTS
    // ==========================================================
    
    // GIP Department - 2 days threshold (default)
    $schedule->command('aging:send-emails gip')
        ->name('aging-emails-gip')
        ->dailyAt('7:00')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ GIP aging emails sent');
        });

    // TUPAD Department - 2 days threshold (default)
    $schedule->command('aging:send-emails tupad')
        ->name('aging-emails-tupad')
        ->dailyAt('7:05')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ TUPAD aging emails sent');
        });

    // TEV Department - 2 days threshold (default)
    $schedule->command('aging:send-emails tev')
        ->name('aging-emails-tev')
        ->dailyAt('7:10')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ TEV aging emails sent');
        });

    // Budget Officer Department - 3 days threshold (default)
    $schedule->command('aging:send-emails budget')
        ->name('aging-emails-budget')
        ->dailyAt('7:15')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ Budget Officer aging emails sent');
        });

    // Accountant Department - 3 days threshold (default)
    $schedule->command('aging:send-emails accountant')
        ->name('aging-emails-accountant')
        ->dailyAt('7:20')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ Accountant aging emails sent');
        });

    // Outgoing Department - 2 days threshold (default)
    $schedule->command('aging:send-emails outgoing')
        ->name('aging-emails-outgoing')
        ->dailyAt('7:25')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ Outgoing aging emails sent');
        });

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
    // 4️⃣ EVENING AGING EMAIL REMINDERS - ALL DEPARTMENTS
    // ==========================================================
    
    // Budget Officer - Evening reminder (use 2 days for urgency)
    $schedule->command('aging:send-emails budget --threshold=2')
        ->name('evening-aging-emails-budget')
        ->dailyAt('16:00')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ Evening Budget Officer aging emails sent');
        });

    // Accountant - Evening reminder (use 2 days for urgency)
    $schedule->command('aging:send-emails accountant --threshold=2')
        ->name('evening-aging-emails-accountant')
        ->dailyAt('16:05')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ Evening Accountant aging emails sent');
        });

    // ==========================================================
    // 5️⃣ SESSION CLEANUP
    // ==========================================================
    
    $schedule->call(function () {
        Log::info('🧹 Session cleanup started');
        app()->make(\App\Http\Controllers\Auth\LoginController::class)
            ->cleanupExpiredSessions();
        Log::info('🧹 Session cleanup completed');
    })
    ->name('cleanup-expired-sessions')
    ->hourly();

    // ==========================================================
    // 6️⃣ WEEKLY SUMMARY REPORT (Monday morning)
    // ==========================================================
    
    $schedule->call(function () {
        Log::info('📊 Weekly aging summary started');
        
        // Run all departments with stricter threshold for weekly report
        $commands = [
            'aging:send-emails gip --threshold=5',
            'aging:send-emails tupad --threshold=5',
            'aging:send-emails tev --threshold=5',
            'aging:send-emails budget --threshold=5',
            'aging:send-emails accountant --threshold=5',
            'aging:send-emails outgoing --threshold=5',
        ];
        
        foreach ($commands as $command) {
            try {
                \Artisan::call($command);
                Log::info("✅ Weekly command executed: {$command}");
            } catch (\Exception $e) {
                Log::error("❌ Weekly command failed: {$command}", ['error' => $e->getMessage()]);
            }
        }
        
        Log::info('📊 Weekly aging summary completed');
    })
    ->name('weekly-aging-summary')
    ->weeklyOn(1, '7:30')  // Every Monday at 7:30 AM
    ->timezone('Asia/Manila');

})
->create();