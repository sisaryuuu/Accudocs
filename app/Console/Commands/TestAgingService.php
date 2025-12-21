<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AgingDocumentService;

class TestAgingService extends Command
{
    protected $signature = 'test:aging {role?} {action?}';
    protected $description = 'Test the aging document service';

    public function handle()
    {
        $service = new AgingDocumentService();
        
        if (!$this->argument('role')) {
            // Test all roles
            $this->info("Testing ALL roles...");
            $result = $service->notifyAllFocalPersons(10);
            $this->table(
                ['Role', 'Status', 'Notified', 'Already Notified', 'Duration'],
                collect($result['results'])->map(function ($item, $key) {
                    return [
                        $key,
                        $item['success'] ? '✅ Success' : '❌ Failed',
                        $item['notified_count'] ?? 0,
                        $item['already_notified_today'] ?? 0,
                        $item['duration_ms'] . 'ms'
                    ];
                })->toArray()
            );
        } else {
            // Test specific role
            $result = $service->processRole(
                $this->argument('role'),
                $this->argument('action') ?? 'notify'
            );
            
            $this->info("Result for {$this->argument('role')}:");
            $this->info(json_encode($result, JSON_PRETTY_PRINT));
        }
    }
}