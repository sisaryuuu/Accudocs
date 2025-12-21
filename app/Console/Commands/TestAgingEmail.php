<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\IMSD\DvRequestAgingController;
use App\Services\PusherService;

class TestAgingEmail extends Command
{
    protected $signature = 'test:aging-email {--request-id= : Specific DV Request ID to test}';
    protected $description = 'Test aging email notifications';

    public function handle()
    {
        $this->info('🧪 Testing Aging Email System...');
        $this->newLine();

        try {
            $controller = new DvRequestAgingController(app(PusherService::class));
            
            if ($requestId = $this->option('request-id')) {
                $this->testSpecificRequest($requestId, $controller);
            } else {
                $this->testAllAging($controller);
            }

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function testSpecificRequest($requestId, $controller)
    {
        $this->info("Testing specific request ID: {$requestId}");
        
        $request = \App\Models\DvRequest::with('focalPerson')->find($requestId);
        
        if (!$request) {
            $this->error("Request not found!");
            return;
        }

        // Calculate days correctly
        $createdAt = \Carbon\Carbon::parse($request->created_at);
        $daysOld = abs($createdAt->diffInDays(now(), false));

        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $request->id],
                ['Reference', $request->reference_number],
                ['Status', $request->status],
                ['Created', $request->created_at],
                ['Days Old', $daysOld . ' days'],
                ['Focal Person', $request->focalPerson->fname . ' ' . $request->focalPerson->lname],
                ['Email', $request->focalPerson->email],
            ]
        );

        if ($this->confirm('Send test email to this focal person?')) {
            $response = $controller->notifyFocalPersonsOfAgingDocuments();
            $data = $response->getData();
            
            $this->newLine();
            $this->info("✅ Notified: {$data->notified_count}");
            $this->info("⏭️  Skipped: {$data->skipped_count}");
            $this->info("📧 Already notified today: {$data->already_notified_today}");
        }
    }

    private function testAllAging($controller)
    {
        $this->info("Testing all aging documents (10+ days)...");
        $this->newLine();

        // Show what will be notified
        $agingRequests = \App\Models\DvRequest::with('focalPerson')
            ->whereNotIn('status', ['cashier_released', '%_rejected'])
            ->where('created_at', '<=', now()->subDays(10))
            ->get();

        $this->info("Found {$agingRequests->count()} aging requests:");
        $this->newLine();

        $tableData = $agingRequests->map(function($req) {
            // Fix: Use absolute value and correct order
            $createdAt = \Carbon\Carbon::parse($req->created_at);
            $daysOld = $createdAt->diffInDays(now());
            
            return [
                $req->id,
                $req->reference_number,
                $req->status,
                $daysOld . ' days',
                $req->focalPerson->email ?? 'No email',
            ];
        })->toArray();

        if (!empty($tableData)) {
            $this->table(
                ['ID', 'Reference', 'Status', 'Age', 'Email'],
                $tableData
            );
        }

        $this->newLine();
        if ($this->confirm('Proceed with sending emails?')) {
            $response = $controller->notifyFocalPersonsOfAgingDocuments();
            $data = $response->getData();
            
            $this->newLine();
            $this->info("✅ Notified: {$data->notified_count}");
            $this->info("⏭️  Skipped: {$data->skipped_count}");
            $this->info("📧 Already notified today: {$data->already_notified_today}");
            
            if (isset($data->errors) && !empty($data->errors)) {
                $this->newLine();
                $this->error("Errors occurred:");
                foreach ($data->errors as $error) {
                    $errorObj = is_array($error) ? $error : (array)$error;
                    $this->error("Request {$errorObj['request_id']}: {$errorObj['error']}");
                }
            }
        }
    }
}