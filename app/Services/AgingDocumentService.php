<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\IMSD\DvRequestAgingController;
use App\Http\Controllers\Incoming\GIPRequestAgingController;
use App\Http\Controllers\Incoming\TEVRequestAgingController;
use App\Http\Controllers\Incoming\TUPADRequestAgingController;
use App\Http\Controllers\BudgetOfficer\ORSRequestAgingController;
use App\Http\Controllers\Outgoing\OutgoingRequestAgingController;
use App\Http\Controllers\AccountingIII\RANCARequestAgingController;

class AgingDocumentService
{
    /**
     * All role controllers that handle aging documents
     * Format: ['Role Name' => ControllerClass]
     */
    protected array $roleControllers = [
        'IMSD' => DvRequestAgingController::class,
        'GIP' => GIPRequestAgingController::class,
        'TEV' => TEVRequestAgingController::class,
        'TUPAD' => TUPADRequestAgingController::class,
        'ORS' => ORSRequestAgingController::class,
        'OUTGOING' => OutgoingRequestAgingController::class,
        'ACCOUNTANT_III' => RANCARequestAgingController::class, 
    ];

    /**
     * Update all dashboards for aging documents
     * This sends Pusher alerts for documents aged 3+ days
     * 
     * @param int $daysThreshold Number of days to consider as aging (default: 3)
     * @return array Results of the operation
     */
    public function updateAllDashboards(int $daysThreshold = 3): array
    {
        $results = [];
        $startTime = now();
        
        Log::info("📊 Starting dashboard updates for all roles", [
            'threshold_days' => $daysThreshold,
            'roles_count' => count($this->roleControllers),
            'started_at' => $startTime->format('Y-m-d H:i:s')
        ]);
        
        foreach ($this->roleControllers as $roleName => $controllerClass) {
            $roleStartTime = microtime(true);
            
            try {
                Log::info("📊 [{$roleName}] Starting dashboard update...");
                
                // Instantiate controller and call sendAgingAlerts(false) for dashboard updates
                $controller = app($controllerClass);
                $response = $controller->sendAgingAlerts(false);
                
                // Extract response data
                $responseData = $response->getData(true);
                $duration = round((microtime(true) - $roleStartTime) * 1000, 2);
                
                $results[$roleName] = [
                    'success' => $responseData['success'] ?? true,
                    'alerted_count' => $responseData['alerted_count'] ?? 0,
                    'message' => $responseData['message'] ?? 'Dashboard updated successfully',
                    'duration_ms' => $duration,
                ];
                
                Log::info("✅ [{$roleName}] Dashboard updated successfully", [
                    'alerted_count' => $results[$roleName]['alerted_count'],
                    'duration_ms' => $duration
                ]);
                
            } catch (\Exception $e) {
                $duration = round((microtime(true) - $roleStartTime) * 1000, 2);
                
                $results[$roleName] = [
                    'success' => false,
                    'alerted_count' => 0,
                    'message' => $e->getMessage(),
                    'duration_ms' => $duration,
                    'error' => true,
                ];
                
                Log::error("❌ [{$roleName}] Dashboard update failed", [
                    'error' => $e->getMessage(),
                    'duration_ms' => $duration,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Summary statistics
        $totalDuration = now()->diffInMilliseconds($startTime);
        $successCount = collect($results)->where('success', true)->count();
        $totalAlerted = collect($results)->sum('alerted_count');
        
        Log::info("📊 Dashboard updates completed", [
            'total_roles' => count($this->roleControllers),
            'successful' => $successCount,
            'failed' => count($this->roleControllers) - $successCount,
            'total_alerted' => $totalAlerted,
            'total_duration_ms' => $totalDuration,
        ]);
        
        return [
            'results' => $results,
            'summary' => [
                'total_roles' => count($this->roleControllers),
                'successful' => $successCount,
                'failed' => count($this->roleControllers) - $successCount,
                'total_alerted' => $totalAlerted,
                'total_duration_ms' => $totalDuration,
            ]
        ];
    }

    /**
     * Notify all focal persons of aging documents
     * Sends email notifications for documents aged 10+ days
     * 
     * @param int $daysThreshold Number of days to consider as aging (default: 10)
     * @return array Results of the operation
     */
    public function notifyAllFocalPersons(int $daysThreshold = 10): array
    {
        $results = [];
        $startTime = now();
        
        Log::info("📧 Starting focal person notifications for all roles", [
            'threshold_days' => $daysThreshold,
            'roles_count' => count($this->roleControllers),
            'started_at' => $startTime->format('Y-m-d H:i:s')
        ]);
        
        foreach ($this->roleControllers as $roleName => $controllerClass) {
            $roleStartTime = microtime(true);
            
            try {
                Log::info("📧 [{$roleName}] Starting focal person notifications...");
                
                // Instantiate controller and call notifyFocalPersonsOfAgingDocuments()
                $controller = app($controllerClass);
                $response = $controller->notifyFocalPersonsOfAgingDocuments();
                
                // Extract response data
                $responseData = $response->getData(true);
                $duration = round((microtime(true) - $roleStartTime) * 1000, 2);
                
                $results[$roleName] = [
                    'success' => $responseData['success'] ?? true,
                    'notified_count' => $responseData['notified_count'] ?? 0,
                    'already_notified_today' => $responseData['already_notified_today'] ?? 0,
                    'skipped_count' => $responseData['skipped_count'] ?? 0,
                    'message' => $responseData['message'] ?? 'Notifications sent successfully',
                    'duration_ms' => $duration,
                ];
                
                Log::info("✅ [{$roleName}] Focal person notifications completed", [
                    'notified_count' => $results[$roleName]['notified_count'],
                    'already_notified' => $results[$roleName]['already_notified_today'],
                    'skipped' => $results[$roleName]['skipped_count'],
                    'duration_ms' => $duration
                ]);
                
            } catch (\Exception $e) {
                $duration = round((microtime(true) - $roleStartTime) * 1000, 2);
                
                $results[$roleName] = [
                    'success' => false,
                    'notified_count' => 0,
                    'message' => $e->getMessage(),
                    'duration_ms' => $duration,
                    'error' => true,
                ];
                
                Log::error("❌ [{$roleName}] Focal person notifications failed", [
                    'error' => $e->getMessage(),
                    'duration_ms' => $duration,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Summary statistics
        $totalDuration = now()->diffInMilliseconds($startTime);
        $successCount = collect($results)->where('success', true)->count();
        $totalNotified = collect($results)->sum('notified_count');
        $totalSkipped = collect($results)->sum('skipped_count');
        
        Log::info("📧 Focal person notifications completed", [
            'total_roles' => count($this->roleControllers),
            'successful' => $successCount,
            'failed' => count($this->roleControllers) - $successCount,
            'total_notified' => $totalNotified,
            'total_skipped' => $totalSkipped,
            'total_duration_ms' => $totalDuration,
        ]);
        
        return [
            'results' => $results,
            'summary' => [
                'total_roles' => count($this->roleControllers),
                'successful' => $successCount,
                'failed' => count($this->roleControllers) - $successCount,
                'total_notified' => $totalNotified,
                'total_skipped' => $totalSkipped,
                'total_duration_ms' => $totalDuration,
            ]
        ];
    }

    /**
     * Send critical alerts for all aging documents
     * Sends Pusher alerts for documents aged 10+ days
     * 
     * @param int $daysThreshold Number of days to consider as critical (default: 10)
     * @return array Results of the operation
     */
    public function sendAllCriticalAlerts(int $daysThreshold = 10): array
    {
        $results = [];
        $startTime = now();
        
        Log::info("🚨 Starting critical alerts for all roles", [
            'threshold_days' => $daysThreshold,
            'roles_count' => count($this->roleControllers),
            'started_at' => $startTime->format('Y-m-d H:i:s')
        ]);
        
        foreach ($this->roleControllers as $roleName => $controllerClass) {
            $roleStartTime = microtime(true);
            
            try {
                Log::info("🚨 [{$roleName}] Starting critical alerts...");
                
                // Instantiate controller and call sendAgingAlerts(true) for critical alerts
                $controller = app($controllerClass);
                $response = $controller->sendAgingAlerts(true);
                
                // Extract response data
                $responseData = $response->getData(true);
                $duration = round((microtime(true) - $roleStartTime) * 1000, 2);
                
                $results[$roleName] = [
                    'success' => $responseData['success'] ?? true,
                    'alerted_count' => $responseData['alerted_count'] ?? 0,
                    'message' => $responseData['message'] ?? 'Critical alerts sent successfully',
                    'duration_ms' => $duration,
                ];
                
                Log::info("✅ [{$roleName}] Critical alerts sent successfully", [
                    'alerted_count' => $results[$roleName]['alerted_count'],
                    'duration_ms' => $duration
                ]);
                
            } catch (\Exception $e) {
                $duration = round((microtime(true) - $roleStartTime) * 1000, 2);
                
                $results[$roleName] = [
                    'success' => false,
                    'alerted_count' => 0,
                    'message' => $e->getMessage(),
                    'duration_ms' => $duration,
                    'error' => true,
                ];
                
                Log::error("❌ [{$roleName}] Critical alerts failed", [
                    'error' => $e->getMessage(),
                    'duration_ms' => $duration,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Summary statistics
        $totalDuration = now()->diffInMilliseconds($startTime);
        $successCount = collect($results)->where('success', true)->count();
        $totalAlerted = collect($results)->sum('alerted_count');
        
        Log::info("🚨 Critical alerts completed", [
            'total_roles' => count($this->roleControllers),
            'successful' => $successCount,
            'failed' => count($this->roleControllers) - $successCount,
            'total_alerted' => $totalAlerted,
            'total_duration_ms' => $totalDuration,
        ]);
        
        return [
            'results' => $results,
            'summary' => [
                'total_roles' => count($this->roleControllers),
                'successful' => $successCount,
                'failed' => count($this->roleControllers) - $successCount,
                'total_alerted' => $totalAlerted,
                'total_duration_ms' => $totalDuration,
            ]
        ];
    }

    
    public function processRole(string $roleName, string $action): array
    {
        $roleName = strtoupper($roleName);
        
        if (!isset($this->roleControllers[$roleName])) {
            Log::warning("Invalid role requested", ['role' => $roleName]);
            return [
                'success' => false,
                'message' => "Invalid role: {$roleName}",
                'available_roles' => array_keys($this->roleControllers),
            ];
        }

        try {
            $controller = app($this->roleControllers[$roleName]);
            $startTime = microtime(true);
            
            Log::info("🎯 Processing single role", [
                'role' => $roleName,
                'action' => $action
            ]);
            
            switch ($action) {
                case 'dashboard':
                    $response = $controller->sendAgingAlerts(false);
                    break;
                    
                case 'notify':
                    $response = $controller->notifyFocalPersonsOfAgingDocuments();
                    break;
                    
                case 'alert':
                    $response = $controller->sendAgingAlerts(true);
                    break;
                    
                default:
                    return [
                        'success' => false,
                        'message' => "Invalid action: {$action}",
                        'available_actions' => ['dashboard', 'notify', 'alert'],
                    ];
            }
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $responseData = $response->getData(true);
            
            Log::info("✅ Single role processing completed", [
                'role' => $roleName,
                'action' => $action,
                'duration_ms' => $duration
            ]);
            
            return array_merge($responseData, [
                'duration_ms' => $duration
            ]);
            
        } catch (\Exception $e) {
            Log::error("❌ Single role processing failed", [
                'role' => $roleName,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => true,
            ];
        }
    }

    /**
     * Get configured roles
     * 
     * @return array List of configured roles
     */
    public function getConfiguredRoles(): array
    {
        return array_keys($this->roleControllers);
    }

    /**
     * Get summary of service configuration
     * 
     * @return array Summary statistics
     */
    public function getServiceInfo(): array
    {
        return [
            'total_roles' => count($this->roleControllers),
            'roles' => $this->getConfiguredRoles(),
            'actions_available' => ['dashboard', 'notify', 'alert'],
            'service_version' => '1.0.0',
            'timestamp' => now()->timezone('Asia/Manila')->toDateTimeString(),
        ];
    }
}