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
     ->withCommands([
        __DIR__.'/../app/Console/Commands', // Load all commands from this directory
    ])
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
    // DAILY AGING EMAIL NOTIFICATIONS AT 6 AM
    // All notifications run on weekdays at 6:00-7:00 AM
    // ==========================================================

    // ==========================================================
    // 1️⃣ TRANSITION CHECKS (Documents awaiting transfer between departments)
    // All at 6:00-6:25 AM with 5-minute intervals
    // ==========================================================
    
    // IMSD → GIP transition (2 days threshold)
    $schedule->command('aging:send-emails gip')
        ->name('aging-emails-gip-transition')
        ->dailyAt('6:00')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ GIP transition: IMSD → GIP');
        });

    // IMSD → TUPAD transition (2 days threshold)
    $schedule->command('aging:send-emails tupad')
        ->name('aging-emails-tupad-transition')
        ->dailyAt('6:05')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ TUPAD transition: IMSD → TUPAD');
        });

    // IMSD → TEV transition (2 days threshold)
    $schedule->command('aging:send-emails tev')
        ->name('aging-emails-tev-transition')
        ->dailyAt('6:10')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ TEV transition: IMSD → TEV');
        });

    // Program Departments → Budget transition (3 days threshold)
    $schedule->command('aging:send-emails budget')
        ->name('aging-emails-budget-transition')
        ->dailyAt('6:15')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ Budget transition: Programs → Budget');
        });

    // Budget → Outgoing transition (2 days threshold)
    $schedule->command('aging:send-emails outgoing')
        ->name('aging-emails-outgoing-transition')
        ->dailyAt('6:20')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ Outgoing transition: Budget → Outgoing');
        });

    // Outgoing → Accountant transition (3 days threshold)
    $schedule->command('aging:send-emails accountant')
        ->name('aging-emails-accountant-transition')
        ->dailyAt('6:25')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ Accountant transition: Outgoing → Accountant');
        });

    // ==========================================================
    // 2️⃣ DEPARTMENT ACTION CHECKS (Documents waiting within departments)
    // All at 6:30-7:00 AM with 5-minute intervals
    // ==========================================================
    
    // IMSD department action (3 days threshold)
    $schedule->command('aging:send-emails imsd')
        ->name('aging-emails-imsd-action')
        ->dailyAt('6:30')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ IMSD department action');
        });

    // GIP received action (3 days threshold)
    $schedule->command('aging:send-emails gip_received')
        ->name('aging-emails-gip-received-action')
        ->dailyAt('6:35')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ GIP received action');
        });

    // TUPAD received action (3 days threshold)
    $schedule->command('aging:send-emails tupad_received')
        ->name('aging-emails-tupad-received-action')
        ->dailyAt('6:40')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ TUPAD received action');
        });

    // TEV received action (3 days threshold)
    $schedule->command('aging:send-emails tev_received')
        ->name('aging-emails-tev-received-action')
        ->dailyAt('6:45')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ TEV received action');
        });

    // Budget received action (3 days threshold)
    $schedule->command('aging:send-emails budget_received')
        ->name('aging-emails-budget-received-action')
        ->dailyAt('6:50')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ Budget received action');
        });

    // Outgoing received action (3 days threshold)
    $schedule->command('aging:send-emails outgoing_received')
        ->name('aging-emails-outgoing-received-action')
        ->dailyAt('6:55')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ Outgoing received action');
        });

    // Accountant received action (3 days threshold)
    $schedule->command('aging:send-emails accountant_received')
        ->name('aging-emails-accountant-received-action')
        ->dailyAt('7:00')
        ->weekdays()
        ->timezone('Asia/Manila')
        ->after(function () {
            Log::info('✅ Accountant received action');
        });

    // ==========================================================
    // SYSTEM MAINTENANCE
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