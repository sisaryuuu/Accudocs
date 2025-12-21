<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DvRequest;
use Carbon\Carbon;

class DebugAgingDates extends Command
{
    protected $signature = 'debug:aging-dates {--request-id=1}';
    protected $description = 'Debug date calculations for aging documents';

    public function handle()
    {
        $requestId = $this->option('request-id');
        $request = DvRequest::find($requestId);

        if (!$request) {
            $this->error("Request ID {$requestId} not found!");
            return 1;
        }

        $this->info('=== DATE DEBUGGING ===');
        $this->newLine();

        // Current settings
        $this->info('Config Settings:');
        $this->line("App Timezone: " . config('app.timezone'));
        $this->line("DB Timezone: " . config('database.connections.mysql.timezone', 'UTC'));
        $this->newLine();

        // Request dates
        $this->info('Request Details:');
        $this->line("ID: {$request->id}");
        $this->line("Reference: {$request->reference_number}");
        $this->newLine();

        // Raw database value
        $this->info('Raw Database Values:');
        $rawCreated = \DB::table('dv_requests')->where('id', $request->id)->value('created_at');
        $this->line("created_at (raw): {$rawCreated}");
        $this->newLine();

        // Laravel model dates
        $this->info('Laravel Model Dates:');
        $this->line("created_at: {$request->created_at}");
        $this->line("created_at (format): " . $request->created_at->format('Y-m-d H:i:s'));
        $this->line("created_at (timezone): " . $request->created_at->timezone);
        $this->newLine();

        // Current time comparisons
        $this->info('Current Time Comparisons:');
        $now = now();
        $this->line("now(): {$now}");
        $this->line("now() timezone: {$now->timezone}");
        $this->line("now() (format): " . $now->format('Y-m-d H:i:s'));
        $this->newLine();

        // Timestamp calculations
        $this->info('Timestamp Calculations:');
        $createdTimestamp = strtotime($request->created_at);
        $currentTimestamp = time();
        $secondsDiff = $currentTimestamp - $createdTimestamp;
        $daysDiff = floor($secondsDiff / 86400);

        $this->line("Created timestamp: {$createdTimestamp} (" . date('Y-m-d H:i:s', $createdTimestamp) . ")");
        $this->line("Current timestamp: {$currentTimestamp} (" . date('Y-m-d H:i:s', $currentTimestamp) . ")");
        $this->line("Seconds difference: {$secondsDiff}");
        $this->line("Days difference (floor): {$daysDiff}");
        $this->line("Is past? " . ($secondsDiff > 0 ? 'YES' : 'NO'));
        $this->newLine();

        // Carbon diffInDays
        $this->info('Carbon Calculations:');
        $carbonDiff = $now->diffInDays($request->created_at);
        $carbonDiffAbs = $now->diffInDays($request->created_at, false);
        $this->line("diffInDays (absolute): {$carbonDiff}");
        $this->line("diffInDays (signed): {$carbonDiffAbs}");
        $this->line("isPast: " . ($request->created_at->isPast() ? 'YES' : 'NO'));
        $this->line("isFuture: " . ($request->created_at->isFuture() ? 'YES' : 'NO'));
        $this->newLine();

        // Different calculation methods
        $this->info('Alternative Calculations:');
        
        // Method 1: Using Carbon diff
        $method1 = Carbon::now()->diffInDays($request->created_at, false);
        $this->line("Method 1 (Carbon signed): {$method1}");
        
        // Method 2: Using timestamps
        $method2 = floor((time() - strtotime($request->created_at)) / 86400);
        $this->line("Method 2 (PHP timestamps): {$method2}");
        
        // Method 3: Using Carbon startOfDay
        $method3 = Carbon::now()->startOfDay()->diffInDays($request->created_at->startOfDay(), false);
        $this->line("Method 3 (Carbon startOfDay signed): {$method3}");
        
        $this->newLine();

        // Recommendations
        $this->info('=== RECOMMENDATIONS ===');
        if ($secondsDiff < 0) {
            $this->error("⚠️  Document created_at is in the FUTURE!");
            $this->error("This suggests a timezone mismatch issue.");
            $this->newLine();
            $this->info("Possible fixes:");
            $this->line("1. Check your .env file: APP_TIMEZONE should be 'Asia/Manila'");
            $this->line("2. Check database timezone configuration");
            $this->line("3. Update existing records with correct timezone");
        } else {
            $this->info("✅ Dates look correct!");
            $this->line("Document is {$daysDiff} days old");
        }

        return 0;
    }
}