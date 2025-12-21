<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\IMSD\DvRequestAgingController;
use App\Models\DvRequest;
use Illuminate\Support\Facades\DB;

class SendAgingNotifications extends Command
{
    protected $signature = 'notifications:aging-documents 
                          {--dry-run : Show what would be notified without actually sending}
                          {--threshold=10 : Days threshold for notification}';
    
    protected $description = 'Send notifications when DV requests reach 10 days old';

    public function handle()
    {
        $threshold = (int) $this->option('threshold');
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No notifications will be sent');
        }
        
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📬 Aging Document Notification System");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("Threshold: {$threshold} days");
        $this->line("Current Date: " . now()->format('Y-m-d H:i:s'));
        $this->line("Target Date: " . now()->subDays($threshold)->format('Y-m-d'));
        $this->newLine();

        if ($isDryRun) {
            return $this->dryRun($threshold);
        }
        
        // Actual notification sending
        $controller = app(DvRequestAgingController::class);
        $result = $controller->notifyFocalPersonsOfAgingDocuments();
        
        $data = $result->getData();
        
        if ($data->success) {
            $this->newLine();
            $this->info("✅ Successfully notified {$data->notified_count} focal person(s)");
            
            if (isset($data->already_notified_today) && $data->already_notified_today > 0) {
                $this->line("ℹ️  Skipped {$data->already_notified_today} (already notified today)");
            }
            
            if (isset($data->skipped_count) && $data->skipped_count > 0) {
                $this->line("⚠️  Skipped {$data->skipped_count} (no focal person or day mismatch)");
            }
            
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        } else {
            $this->error("❌ Failed: {$data->message}");
        }
        
        return 0;
    }
protected function dryRun($threshold)
{
    // CHANGE: Use <= instead of between
    $cutoffTime = now()->subDays($threshold);

    $requests = DvRequest::with(['focalPerson', 'program'])
        ->whereIn('status', ['submitted', 'IMSD_received', 'IMSD_in_process', 
                            'Incoming-TUPAD_received', 'Incoming-TUPAD_in_process',
                            'Incoming-GIP_received', 'Incoming-GIP_in_process',
                            'Incoming-TEV_received', 'Incoming-TEV_in_process',
                            'ORS_received', 'ORS_in_process', 'ORS_onhold',
                            'Outgoing_received', 'Outgoing_in_process',
                            'RANCA_received', 'RANCA_in_process'])
        ->where('created_at', '<=', $cutoffTime) // CHANGED
        ->get();

    if ($requests->isEmpty()) {
        $this->warn("No requests found that are {$threshold}+ days old");
        $this->line("Cutoff date: " . $cutoffTime->format('Y-m-d H:i:s'));
        return 0;
    }

    $this->info("Found {$requests->count()} request(s) that would be notified (≥{$threshold} days old):");
    $this->newLine();

    $table = [];
    foreach ($requests as $request) {
        $daysOld = now()->diffInDays($request->created_at);
        
        // Check if already notified today
        $alreadySent = DB::table('notifications')
            ->where('notifiable_type', get_class($request->focalPerson))
            ->where('notifiable_id', $request->focalPerson->id)
            ->whereDate('created_at', today())
            ->whereRaw("JSON_EXTRACT(data, '$.dv_request_id') = ?", [$request->id])
            ->exists();
        
        $table[] = [
            'ID' => $request->id,
            'Reference' => $request->reference_number,
            'Focal Person' => $request->focalPerson 
                ? trim($request->focalPerson->fname . ' ' . $request->focalPerson->lname)
                : 'N/A',
            'Days' => $daysOld,
            'Status' => $request->status,
            'Created' => $request->created_at->format('M d, Y'),
            'Cutoff' => $cutoffTime->format('M d, Y'),
            'Already Sent' => $alreadySent ? '✓' : '✗',
        ];
    }

    $this->table(
        ['ID', 'Reference', 'Focal Person', 'Days', 'Status', 'Created', 'Cutoff', 'Already Sent'],
        $table
    );

    return 0;
}
}