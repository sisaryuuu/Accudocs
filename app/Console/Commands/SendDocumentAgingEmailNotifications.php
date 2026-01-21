<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DvRequest;
use App\Models\AccountingUnit;
use App\Models\DocumentAgingAlert;
use App\Services\SendPulseServices;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendDocumentAgingEmailNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'aging:send-emails 
                            {department : Department to check}
                            {--threshold= : Days threshold for notifications}
                            {--all-departments : Process all departments at once}';

    /**
     * The console command description.
     */
    protected $description = 'Send email notifications for aging documents across all departments';

    /**
     * Department configuration - Basic array only
     */
    protected $departmentConfig = [
        // ⭐ IMSD Department - Documents waiting for IMSD action
        'imsd' => [
            'name' => 'IMSD',
            'statuses' => ['submitted', 'IMSD_received'],
            'program_filter' => null,
            'recipient_role' => 'IMSD',
            'route_name' => 'IMSD.dvrequests.withid',
            'default_threshold' => 3,
            'date_field' => 'imsd_received_at',
            'check_type' => 'department_action',
        ],
        
        // ⭐ GIP Department - IMSD → GIP transition (2-day clock)
        'gip' => [
            'name' => 'Incoming-GIP',
            'statuses' => ['IMSD_verified'],
            'program_filter' => ['GIP'],
            'recipient_role' => 'Incoming-GIP',
            'route_name' => 'Incoming.GIP.dvrequests.withid',
            'default_threshold' => 2,
            'date_field' => 'imsd_verified_at',
            'check_type' => 'transition',
            'expected_next' => 'Incoming-GIP_received',
            'reset_field' => 'gip_received_at',
        ],
        
        // ⭐ GIP Received - Documents waiting in GIP department
        'gip_received' => [
            'name' => 'Incoming-GIP (Received)',
            'statuses' => ['Incoming-GIP_received'],
            'program_filter' => ['GIP'],
            'recipient_role' => 'Incoming-GIP',
            'route_name' => 'Incoming.GIP.dvrequests.withid',
            'default_threshold' => 3,
            'date_field' => 'gip_received_at',
            'check_type' => 'department_action',
        ],
        
        // ⭐ TUPAD Department - IMSD → TUPAD transition (2-day clock)
        'tupad' => [
            'name' => 'Incoming-TUPAD',
            'statuses' => ['IMSD_verified'],
            'program_filter' => ['TUPAD'],
            'recipient_role' => 'Incoming-TUPAD',
            'route_name' => 'Incoming.TUPAD.dvrequests.withid',
            'default_threshold' => 2,
            'date_field' => 'imsd_verified_at',
            'check_type' => 'transition',
            'expected_next' => 'Incoming-TUPAD_received',
            'reset_field' => 'tupad_received_at',
        ],
        
        // ⭐ TUPAD Received - Documents waiting in TUPAD department
        'tupad_received' => [
            'name' => 'Incoming-TUPAD (Received)',
            'statuses' => ['Incoming-TUPAD_received'],
            'program_filter' => ['TUPAD'],
            'recipient_role' => 'Incoming-TUPAD',
            'route_name' => 'Incoming.TUPAD.dvrequests.withid',
            'default_threshold' => 3,
            'date_field' => 'tupad_received_at',
            'check_type' => 'department_action',
        ],
        
        // ⭐ TEV Department - IMSD → TEV transition (2-day clock)
        'tev' => [
            'name' => 'Incoming-TEV',
            'statuses' => ['IMSD_verified'],
            'program_filter' => ['TEV'],
            'recipient_role' => 'Incoming-TEV',
            'route_name' => 'Incoming.TEV.dvrequests.withid',
            'default_threshold' => 2,
            'date_field' => 'imsd_verified_at',
            'check_type' => 'transition',
            'expected_next' => 'Incoming-TEV_received',
            'reset_field' => 'tev_received_at',
        ],
        
        // ⭐ TEV Received - Documents waiting in TEV department
        'tev_received' => [
            'name' => 'Incoming-TEV (Received)',
            'statuses' => ['Incoming-TEV_received'],
            'program_filter' => ['TEV'],
            'recipient_role' => 'Incoming-TEV',
            'route_name' => 'Incoming.TEV.dvrequests.withid',
            'default_threshold' => 3,
            'date_field' => 'tev_received_at',
            'check_type' => 'department_action',
        ],
        
       'budget' => [
        'name' => 'Budget_Officer',
        'statuses' => ['Incoming-GIP_verified', 'Incoming-TUPAD_verified', 'Incoming-TEV_verified'],
        'program_filter' => null,
        'recipient_role' => 'Budget_Officer',
        'route_name' => 'BudgetOfficer.dvrequests.withid',
        'default_threshold' => 3,
        'date_field_callback' => 'getBudgetDateField',
        'check_type' => 'transition',
        'expected_next' => 'ORS_obligated', // Budget sets this when they finish
        'reset_field' => 'budget_verified_at',
    ],
    
    // ⭐ BUDGET Received - Documents waiting in Budget department
    'budget_received' => [
        'name' => 'Budget_Officer (Received)',
        'statuses' => ['ORS_received'],
        'program_filter' => null,
        'recipient_role' => 'Budget_Officer',
        'route_name' => 'BudgetOfficer.dvrequests.withid',
        'default_threshold' => 3,
        'date_field' => 'budget_received_at',
        'check_type' => 'department_action',
    ],
    
    'outgoing' => [
    'name' => 'Outgoing',
    'statuses' => ['ORS_obligated'],
    'program_filter' => null,
    'recipient_role' => 'Outgoing',
    'route_name' => 'Outgoing.dvrequests.withid',
    'default_threshold' => 2,
    'date_field_callback' => 'getOutgoingDateField', // Use callback
    'check_type' => 'transition',
    'expected_next' => 'Outgoing_received', // This should be the immediate next status
    'reset_field' => 'outgoing_received_at',
],
    
    // ⭐ OUTGOING Received - Documents waiting in Outgoing department
    'outgoing_received' => [
        'name' => 'Outgoing (Received)',
        'statuses' => ['Outgoing_received'],
        'program_filter' => null,
        'recipient_role' => 'Outgoing',
        'route_name' => 'Outgoing.dvrequests.withid',
        'default_threshold' => 3,
        'date_field' => 'outgoing_received_at',
        'check_type' => 'department_action',
    ],
    
    // ⭐ ACCOUNTANT Department - Outgoing → Accountant transition (3-day clock)
    'accountant' => [
        'name' => 'Accountant_III',
        'statuses' => ['Outgoing_finalized'], // After Outgoing sends to Accountant
        'program_filter' => null,
        'recipient_role' => 'Accountant_III',
        'route_name' => 'Accountant.dvrequests.withid',
        'default_threshold' => 3,
        'date_field' => 'outgoing_verified_at', // Date when Outgoing sent to Accountant
        'check_type' => 'transition',
        'expected_next' => 'Accountant_III_verified',
        'reset_field' => 'accountant_received_at',
    ],
    
    // ⭐ ACCOUNTANT Received - Documents waiting in Accountant department
    'accountant_received' => [
        'name' => 'Accountant_III (Received)',
        'statuses' => ['RANCA_received'], // Actually, this should be a different status if Accountant has a "received" status
        'program_filter' => null,
        'recipient_role' => 'Accountant_III',
        'route_name' => 'Accountant.dvrequests.withid',
        'default_threshold' => 3,
        'date_field' => 'accountant_received_at',
        'check_type' => 'department_action',
    ],
    ];

    /**
     * Get the route URL for a department
     */
    protected function getRouteUrl($routeName, $requestId)
    {
        try {
            return route($routeName, $requestId);
        } catch (\Exception $e) {
            Log::warning("Route not found: {$routeName}", ['request_id' => $requestId]);
            return '#';
        }
    }

    /**
     * Callback method for Budget department date field
     */
    protected function getBudgetDateField($request)
    {
        if ($request->gip_verified_at) return 'gip_verified_at';
        if ($request->tupad_verified_at) return 'tupad_verified_at';
        if ($request->tev_verified_at) return 'tev_verified_at';
        return 'created_at';
    }

    /**
     * Execute the console command.
     */
   /**
 * Execute the console command.
 */
public function handle()
{
    // If --all-departments flag is used, process all
    if ($this->option('all-departments')) {
        return $this->handleAllDepartments();
    }
    
    $department = strtolower($this->argument('department'));

    if (!isset($this->departmentConfig[$department])) {
        $this->error("❌ Invalid department: {$department}");
        $this->info("Valid departments: " . implode(', ', array_keys($this->departmentConfig)));
        return 1;
    }

    $config = $this->departmentConfig[$department];
    
    // Determine threshold
    $threshold = $this->option('threshold') !== null 
        ? (int) $this->option('threshold') 
        : $config['default_threshold'];
    
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("  📧 DOCUMENT TRANSITION MONITORING");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("Department: {$config['name']}");
    $this->info("Threshold: {$threshold}+ days (Default: {$config['default_threshold']}+ days)");
    $this->info("Recipient Role: {$config['recipient_role']}");
    
    if (isset($config['check_type']) && $config['check_type'] === 'transition') {
        $expectedNext = $config['expected_next'] ?? 'NEXT_STATUS';
        $this->info("Transition: " . implode('/', $config['statuses']) . " → {$expectedNext}");
        if (isset($config['reset_field'])) {
            $this->info("Clock resets when: {$config['reset_field']} is set");
        }
    }
    
    $this->newLine();

    // Calculate cutoff date - FIX: For threshold=0, we want ALL documents regardless of date
    $cutoffDate = $threshold > 0 
        ? Carbon::today()->subDays($threshold) 
        : Carbon::today()->addDay(); // For threshold=0, use tomorrow to include all
    
    $this->line("📅 Cutoff Date: {$cutoffDate->toDateString()} (threshold={$threshold})");
    
    // Build query based on department type
    $query = DvRequest::with(['focalPerson', 'program', 'respocenter', 'accountingUnit'])
        ->whereIn('status', $config['statuses']);

    // Apply program filter if specified
    if ($config['program_filter']) {
        $query->whereHas('program', function($q) use ($config) {
            $q->whereIn('prcode', $config['program_filter']);
        });
    }

    // Handle different query conditions based on check type
    if (isset($config['check_type']) && $config['check_type'] === 'transition') {
        // For transition checks, we need documents that:
        // 1. Have the appropriate status
        // 2. Have the start date (date_field) set
        // 3. Have NOT been received by next department (reset_field is null)
        
        if ($department === 'budget') {
            // Special handling for budget since it has dynamic date field
            $query->where(function($q) use ($cutoffDate, $threshold) {
                // Documents verified by GIP
                $q->where(function($sub) use ($cutoffDate, $threshold) {
                    $sub->where('status', 'Incoming-GIP_verified')
                        ->whereNotNull('gip_verified_at');
                    
                    // Only apply date filter if threshold > 0
                    if ($threshold > 0) {
                        $sub->whereDate('gip_verified_at', '<=', $cutoffDate);
                    }
                });
                
                // Documents verified by TUPAD
                $q->orWhere(function($sub) use ($cutoffDate, $threshold) {
                    $sub->where('status', 'Incoming-TUPAD_verified')
                        ->whereNotNull('tupad_verified_at');
                    
                    if ($threshold > 0) {
                        $sub->whereDate('tupad_verified_at', '<=', $cutoffDate);
                    }
                });
                
                // Documents verified by TEV
                $q->orWhere(function($sub) use ($cutoffDate, $threshold) {
                    $sub->where('status', 'Incoming-TEV_verified')
                        ->whereNotNull('tev_verified_at');
                    
                    if ($threshold > 0) {
                        $sub->whereDate('tev_verified_at', '<=', $cutoffDate);
                    }
                });
            });
        } else {
            // For other departments with standard date_field
            if (isset($config['date_field'])) {
                $query->whereNotNull($config['date_field']);
                
                // Only apply date filter if threshold > 0
                if ($threshold > 0) {
                    $query->whereDate($config['date_field'], '<=', $cutoffDate);
                }
            }
        }
        
        // Exclude documents that have already been received by next department
        if (isset($config['reset_field'])) {
            $query->whereNull($config['reset_field']);
        }
    } else {
        // Regular department aging check
        if (isset($config['date_field'])) {
            $query->whereNotNull($config['date_field']);
            
            if ($threshold > 0) {
                $query->whereDate($config['date_field'], '<=', $cutoffDate);
            }
        } else {
            if ($threshold > 0) {
                $query->whereDate('created_at', '<=', $cutoffDate);
            }
        }
    }

    // Debug: Show the query
    $this->line("🔍 Query conditions:");
    $this->line("  • Status IN: " . implode(', ', $config['statuses']));
    
    if ($config['program_filter']) {
        $this->line("  • Program IN: " . implode(', ', $config['program_filter']));
    }
    
    if (isset($config['date_field'])) {
        $this->line("  • {$config['date_field']} IS NOT NULL");
        if ($threshold > 0) {
            $this->line("  • {$config['date_field']} <= {$cutoffDate->toDateString()}");
        }
    }
    
    if (isset($config['reset_field'])) {
        $this->line("  • {$config['reset_field']} IS NULL");
    }
    
    $this->newLine();

    $agingRequests = $query->get();

    // For debugging, show what we found
    if ($agingRequests->isNotEmpty()) {
        $this->info("📄 Documents found in query ({$agingRequests->count()}):");
        foreach ($agingRequests as $request) {
            $daysOld = $this->calculateDaysOld($request, $config);
            $controlNumber = $this->getControlNumber($request);
            $this->line("  - {$controlNumber} (ID: {$request->id}):");
            $this->line("    Status: {$request->status}");
            $this->line("    Days Old: {$daysOld}");
            
            if ($department === 'budget') {
                $dateField = $this->getBudgetDateField($request);
                $dateValue = $request->$dateField ?? 'N/A';
                $this->line("    Date Field: {$dateField} = {$dateValue}");
                $this->line("    budget_received_at: " . ($request->budget_received_at ?? 'NULL'));
            }
        }
        $this->newLine();
    }

    if ($agingRequests->isEmpty()) {
        $this->info('✨ No aging documents found.');
        
        // Enhanced debugging
        $this->line("🔍 DEBUGGING INFORMATION:");
        
        // Check total count with these statuses
        $totalQuery = DvRequest::whereIn('status', $config['statuses']);
        if ($config['program_filter']) {
            $totalQuery->whereHas('program', function($q) use ($config) {
                $q->whereIn('prcode', $config['program_filter']);
            });
        }
        $totalCount = $totalQuery->count();
        $this->line("  • Total documents with status " . implode('/', $config['statuses']) . ": {$totalCount}");
        
        // Check how many have reset_field already set
        if (isset($config['reset_field'])) {
            $withResetField = $totalQuery->whereNotNull($config['reset_field'])->count();
            $this->line("  • Documents with {$config['reset_field']} NOT NULL: {$withResetField}");
            $this->line("  • Documents with {$config['reset_field']} NULL: " . ($totalCount - $withResetField));
        }
        
        // Show sample documents
        if ($totalCount > 0) {
            $this->line("  • Sample documents (first 3):");
            $samples = $totalQuery->limit(3)->get();
            
            foreach ($samples as $sample) {
                $this->line("    - ID: {$sample->id}, Status: {$sample->status}");
                $this->line("      accounting_control_number: " . ($sample->accounting_control_number ?? 'N/A'));
                
                // Show relevant date fields
                if ($department === 'budget') {
                    $dateFields = ['gip_verified_at', 'tupad_verified_at', 'tev_verified_at'];
                    foreach ($dateFields as $field) {
                        if ($sample->$field) {
                            $this->line("      {$field}: " . Carbon::parse($sample->$field)->format('Y-m-d H:i:s'));
                        }
                    }
                    $this->line("      budget_received_at: " . ($sample->budget_received_at ?? 'NULL'));
                }
            }
        }
        
        return 0;
    }

    $this->info("Found {$agingRequests->count()} document(s) pending action.");
    $this->newLine();

    $sentCount = 0;
    $failedCount = 0;
    $skippedCount = 0;
    $belowThresholdCount = 0;

    foreach ($agingRequests as $request) {
        // Calculate days old
        $daysOld = $this->calculateDaysOld($request, $config);
        
        // Skip if below threshold (shouldn't happen since we filtered in query, but just in case)
        if ($daysOld < $threshold) {
            $controlNumber = $this->getControlNumber($request);
            $dateInfo = $this->getDateInfo($request, $config);
            $this->line("⏩ Skipping {$controlNumber} - Only {$daysOld} days old (needs {$threshold}+)");
            $this->line("   {$dateInfo}");
            $belowThresholdCount++;
            continue;
        }

        $controlNumber = $this->getControlNumber($request);

        // Check if alert already sent today
        $alertSentToday = DocumentAgingAlert::where('dv_request_id', $request->id)
            ->where('alert_date', today())
            ->where('department', $department)
            ->exists();

        if ($alertSentToday) {
            $this->line("⏭️  Skipping {$controlNumber} - Alert already sent today");
            $skippedCount++;
            continue;
        }

        // Get recipients
        $recipients = AccountingUnit::whereNotNull('email')
            ->whereHas('roles', function($query) use ($config) {
                $query->where('name', $config['recipient_role']);
            })
            ->get();

        if ($recipients->isEmpty()) {
            $this->warn("⚠️  No accounting units found with role '{$config['recipient_role']}'");
            continue;
        }

        $emailsSentForThisDoc = 0;

        // Send email to each recipient
        foreach ($recipients as $recipient) {
            try {
                $this->sendAgingEmail($recipient, $request, $daysOld, $threshold, $config);
                $emailsSentForThisDoc++;
                $this->line("  ✓ Email sent to {$recipient->email}");
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("  ✗ Failed to send to {$recipient->email}: {$e->getMessage()}");
                Log::error('Failed to send aging email', [
                    'recipient' => $recipient->email,
                    'dv_request_id' => $request->id,
                    'department' => $department,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Record the alert
        if ($emailsSentForThisDoc > 0) {
            try {
                DocumentAgingAlert::create([
                    'dv_request_id' => $request->id,
                    'alert_date' => today(),
                    'days_old' => $daysOld,
                    'urgency' => $this->getUrgencyLevel($daysOld),
                    'department' => $department,
                    'threshold_used' => $threshold,
                    'date_field_used' => $this->getDateFieldUsed($request, $config),
                    'base_date' => $this->getBaseDate($request, $config),
                    'check_type' => $config['check_type'] ?? 'department_action',
                    'transition_from' => $config['statuses'][0] ?? null,
                    'transition_to' => $config['expected_next'] ?? null,
                ]);
                
                $sentCount += $emailsSentForThisDoc;
                
                $dateInfo = $this->getDateInfo($request, $config);
                $this->info("✅ {$controlNumber} - Alert logged ({$daysOld} days, {$emailsSentForThisDoc} email(s))");
                $this->line("   {$dateInfo}");
                
                if (isset($config['check_type']) && $config['check_type'] === 'transition') {
                    $this->line("   Expected next: {$config['expected_next']}");
                }
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $this->line("ℹ️  Alert already exists for {$controlNumber}");
                    $skippedCount++;
                } else {
                    Log::error('Failed to create aging alert record', [
                        'dv_request_id' => $request->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    $this->newLine();
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("           📊 SUMMARY REPORT");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    
    $summary = [
        ['Department', $config['name']],
        ['Check Type', $config['check_type'] ?? 'department_action'],
        ['Threshold Used', "{$threshold}+ days"],
        ['Default Threshold', "{$config['default_threshold']}+ days"],
        ['Total Documents Found', $agingRequests->count()],
        ['Below Threshold', $belowThresholdCount],
        ['Already Alerted (Skipped)', $skippedCount],
        ['Emails Sent Successfully', $sentCount],
        ['Failed Emails', $failedCount],
    ];
    
    if (isset($config['check_type']) && $config['check_type'] === 'transition') {
        $summary[] = ['Transition', "{$config['statuses'][0]} → {$config['expected_next']}"];
    }
    
    $summary[] = ['Total Processed', $sentCount + $skippedCount];
    
    $this->table(['Metric', 'Value'], $summary);

    if ($failedCount > 0) {
        $this->warn("⚠️  Some emails failed to send. Check logs for details.");
        return 1;
    }

    $this->info("✅ Process completed successfully!");
    return 0;
}
    
    /**
     * Handle all departments at once
     */
    protected function handleAllDepartments()
    {
        $this->info("🚀 PROCESSING ALL DEPARTMENT TRANSITIONS");
        $this->info("========================================");
        $this->newLine();
        
        $results = [];
        
        foreach ($this->departmentConfig as $deptKey => $config) {
            $this->info("Processing: {$config['name']}...");
            
            // Run the command for this department
            $result = $this->call('aging:send-emails', [
                'department' => $deptKey,
                '--threshold' => $config['default_threshold']
            ]);
            
            $results[] = [
                'department' => $config['name'],
                'status' => $result === 0 ? '✅ Success' : '❌ Failed',
                'key' => $deptKey
            ];
            
            $this->newLine();
        }
        
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("       📋 ALL DEPARTMENTS SUMMARY");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        $this->table(['Department', 'Status', 'Command Key'], $results);
        
        $failedCount = count(array_filter($results, fn($r) => str_contains($r['status'], 'Failed')));
        
        if ($failedCount > 0) {
            $this->error("⚠️  {$failedCount} department(s) failed to process.");
            return 1;
        }
        
        $this->info("✅ All departments processed successfully!");
        return 0;
    }
    
    /**
     * Calculate days old based on configuration
     */
  /**
 * Calculate days old based on configuration
 */
/**
 * Calculate days old based on configuration
 */
protected function calculateDaysOld($request, $config)
{
    $dateField = null;
    
    // Get the date field to use
    if (isset($config['date_field_callback'])) {
        $method = $config['date_field_callback'];
        $dateField = $this->$method($request);
    } else {
        $dateField = $config['date_field'] ?? null;
    }
    
    // Get the base date
    $baseDate = null;
    
    if ($dateField && $request->$dateField) {
        $baseDate = $request->$dateField;
    } else {
        $baseDate = $request->created_at;
    }
    
    // If this is a transition check AND the clock was already reset, return 0
    if (isset($config['check_type']) && $config['check_type'] === 'transition') {
        $resetField = $config['reset_field'] ?? null;
        if ($resetField && $request->$resetField) {
            return 0; // Clock was reset, document is "new" to this department
        }
    }
    
    // Ensure we have a Carbon instance
    if (!$baseDate instanceof \Carbon\Carbon) {
        try {
            $baseDate = \Carbon\Carbon::parse($baseDate);
        } catch (\Exception $e) {
            // Fallback to created_at if parsing fails
            $baseDate = $request->created_at;
        }
    }
    
    // Calculate days difference
    return $baseDate->startOfDay()->diffInDays(now()->startOfDay());
}
    /**
     * Get control number for display
     */
    protected function getControlNumber($request)
    {
        return $request->accounting_control_number 
            ? 'CN #-' . now()->year . '-' . str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT)
            : 'Pending-CN';
    }
    
    /**
     * Get date information for display
     */
   /**
 * Get date information for display
 */
protected function getDateInfo($request, $config)
{
    $dateField = null;
    $label = 'created_at';
    
    // Get the date field
    if (isset($config['date_field_callback'])) {
        $method = $config['date_field_callback'];
        $dateField = $this->$method($request);
        $label = $dateField;
    } else {
        $dateField = $config['date_field'] ?? null;
        $label = $dateField;
    }
    
    // FIX: Handle both Carbon instances and strings
    if ($dateField && $request->$dateField) {
        $dateValue = $request->$dateField;
        if (!$dateValue instanceof \Carbon\Carbon) {
            $dateValue = \Carbon\Carbon::parse($dateValue);
        }
        $value = $dateValue->format('M d, Y');
    } else {
        $value = $request->created_at->format('M d, Y');
    }
    
    return "Date used: {$label} = {$value}";
}
    /**
     * Get date field used for storage
     */
    protected function getDateFieldUsed($request, $config)
    {
        if (isset($config['date_field_callback'])) {
            $method = $config['date_field_callback'];
            return $this->$method($request) ?: 'created_at';
        }
        
        return $config['date_field'] ?? 'created_at';
    }
    
    /**
     * Get base date for storage
     */
   /**
 * Get base date for storage
 */
protected function getBaseDate($request, $config)
{
    $dateField = null;
    
    if (isset($config['date_field_callback'])) {
        $method = $config['date_field_callback'];
        $dateField = $this->$method($request);
    } else {
        $dateField = $config['date_field'] ?? null;
    }
    
    $baseDate = $dateField && $request->$dateField 
        ? $request->$dateField 
        : $request->created_at;
    
    // FIX: Convert to Carbon if needed
    if (!$baseDate instanceof \Carbon\Carbon) {
        $baseDate = \Carbon\Carbon::parse($baseDate);
    }
    
    return $baseDate;
}

    /**
     * Send aging notification email
     */
    /**
 * Send aging notification email
 */
protected function sendAgingEmail($recipient, $request, $daysOld, $threshold, $config)
{
    $controlNumber = $this->getControlNumber($request);
    $submittedBy = trim(($request->focalPerson->fname ?? '') . ' ' . ($request->focalPerson->lname ?? ''));
    $program = $request->program->prcode ?? 'N/A';
    
    $urgencyLevel = $this->getUrgencyLevel($daysOld);
    $urgencyColor = $this->getUrgencyColor($urgencyLevel);
    $urgencyText = $this->getUrgencyText($urgencyLevel);
    
    $dateInfo = $this->getDateInfo($request, $config);
    $baseDate = $this->getBaseDate($request, $config);
    $viewUrl = $this->getRouteUrl($config['route_name'], $request->id);
    
    // ⭐ FIX: Get recipient name correctly from fname and lname
    $recipientName = trim(($recipient->fname ?? '') . ' ' . ($recipient->lname ?? ''));
    if (empty($recipientName)) {
        $recipientName = $recipient->username ?? 'User';
    }
    
    $emailData = [
        'recipient_name' => $recipientName, // ⭐ Use the correct name
        'department_name' => $config['name'],
        'control_number' => $controlNumber,
        'submitted_by' => $submittedBy,
        'program' => $program,
        'days_old' => $daysOld,
        'threshold' => $threshold,
        'status' => $request->status,
        'amount' => number_format($request->amount, 2),
        'particulars' => $request->particulars,
        'submitted_date' => $request->created_at->format('M d, Y h:i A'),
        'urgency_level' => $urgencyLevel,
        'urgency_color' => $urgencyColor,
        'urgency_text' => $urgencyText,
        'view_url' => $viewUrl,
        'date_info' => $dateInfo,
        'base_date' => $baseDate->format('M d, Y h:i A'),
        'check_type' => $config['check_type'] ?? 'department_action',
        'transition_from' => $config['statuses'][0] ?? null,
        'transition_to' => $config['expected_next'] ?? null,
        'action_required' => isset($config['check_type']) && $config['check_type'] === 'transition' 
            ? "Document needs to take action in {$config['name']}" 
            : "Document needs action in {$config['name']}",
    ];

    // Use SendPulseServices directly
    try {
        $sendPulseService = new \App\Services\SendPulseServices();
        
        // Choose the correct method based on check_type
        if (isset($config['check_type']) && $config['check_type'] === 'transition') {
            $result = $sendPulseService->sendTransitionNotification(
                $recipient->email,
                $recipientName, // ⭐ Pass the correct name
                $emailData
            );
        } else {
            $result = $sendPulseService->sendAgingNotification(
                $recipient->email,
                $recipientName, // ⭐ Pass the correct name
                $emailData
            );
        }

        if ($result) {
            Log::info('SendPulse email sent', [
                'recipient' => $recipient->email,
                'dv_request_id' => $request->id,
                'department' => $config['name'],
                'check_type' => $config['check_type'] ?? 'department_action',
                'days_old' => $daysOld,
            ]);
            return;
        }
    } catch (\Exception $e) {
        Log::warning('SendPulse failed, falling back to SMTP', [
            'error' => $e->getMessage(),
            'recipient' => $recipient->email,
        ]);
    }

    // Fallback to SMTP (this should work since you have the email template)
    try {
        Mail::send('emails.document_aging_notification', $emailData, function($message) use ($recipient, $recipientName, $subject) {
            $message->to($recipient->email, $recipientName)
                   ->subject($subject)
                   ->from(config('mail.from.address'), config('mail.from.name'));
        });

        Log::info('SMTP aging email sent', [
            'recipient' => $recipient->email,
            'dv_request_id' => $request->id,
            'department' => $config['name'],
            'check_type' => $config['check_type'] ?? 'department_action',
        ]);
    } catch (\Exception $e) {
        Log::error('Both SendPulse and SMTP failed for aging email', [
            'recipient' => $recipient->email,
            'dv_request_id' => $request->id,
            'error' => $e->getMessage()
        ]);
        throw $e; // Re-throw to be caught by the calling code
    }
}
/**
 * Callback method for Outgoing department date field
 */
protected function getOutgoingDateField($request)
{
    // First choice: budget_verified_at from DV request
    if ($request->budget_verified_at) {
        return 'budget_verified_at';
    }
    
    // Second choice: budget_verified_at from ORS request
    if ($request->orsRequest && $request->orsRequest->budget_verified_at) {
        return 'orsRequest.budget_verified_at';
    }
    
    // Third choice: created_at as fallback
    return 'created_at';
}
    /**
     * Get urgency level based on days old
     */
    protected function getUrgencyLevel($daysOld)
    {
        if ($daysOld >= 10) return 'critical';
        if ($daysOld >= 7) return 'high';
        if ($daysOld >= 3) return 'medium';
        return 'low';
    }

    /**
     * Get urgency color for email styling
     */
    protected function getUrgencyColor($level)
    {
        return [
            'critical' => '#dc2626',
            'high' => '#ea580c',
            'medium' => '#d97706',
            'low' => '#2563eb'
        ][$level] ?? '#6b7280';
    }

    /**
     * Get urgency text
     */
    protected function getUrgencyText($level)
    {
        return [
            'critical' => '🚨 CRITICAL - IMMEDIATE ACTION REQUIRED',
            'high' => '⚠️ HIGH PRIORITY',
            'medium' => '📋 MEDIUM PRIORITY',
            'low' => 'ℹ️ NOTIFICATION'
        ][$level] ?? 'NOTIFICATION';
    }
}