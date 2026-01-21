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
     * NOTIFY ACCOUNTING UNITS (not focal persons)
     * Sends email notifications to accounting units for documents aged 10+ days
     * 
     * @param int $daysThreshold Number of days to consider as aging (default: 10)
     * @return array Results of the operation
     */
    public function notifyAllAccountingUnits(int $daysThreshold = 10): array
    {
        $results = [];
        $startTime = now();
        
        Log::info("📧 Starting accounting unit notifications for all roles", [
            'threshold_days' => $daysThreshold,
            'roles_count' => count($this->roleControllers),
            'started_at' => $startTime->format('Y-m-d H:i:s')
        ]);
        
        foreach ($this->roleControllers as $roleName => $controllerClass) {
            $roleStartTime = microtime(true);
            
            try {
                Log::info("📧 [{$roleName}] Starting accounting unit notifications...");
                
                // Instantiate controller
                $controller = app($controllerClass);
                
                // ✅ FIXED: Different methods for different roles
                if ($roleName === 'GIP') {
                    // GIP already sends emails via sendAgingAlerts()
                    // For 10+ days, use critical alerts
                    $response = $controller->sendAgingAlerts(true); // true = critical only
                    $responseData = $response->getData(true);
                    
                    $results[$roleName] = [
                        'success' => $responseData['success'] ?? true,
                        'notified_count' => $responseData['emails_sent'] ?? 0,
                        'total_documents' => $responseData['total_documents'] ?? 0,
                        'alerts_created' => $responseData['alerts_created'] ?? 0,
                        'recipient_type' => 'accounting_units',
                        'message' => 'GIP accounting units notified via critical alerts',
                        'duration_ms' => round((microtime(true) - $roleStartTime) * 1000, 2),
                    ];
                    
                } else {
                    // For other roles, use their specific notification method
                    // Check if method exists
                    if (method_exists($controller, 'notifyAccountingUnits')) {
                        $response = $controller->notifyAccountingUnits();
                    } elseif (method_exists($controller, 'notifyFocalPersonsOfAgingDocuments')) {
                        // Fallback to focal persons if accounting units method doesn't exist
                        $response = $controller->notifyFocalPersonsOfAgingDocuments();
                        Log::warning("⚠️ [{$roleName}] Using notifyFocalPersonsOfAgingDocuments as fallback");
                    } else {
                        Log::warning("⚠️ [{$roleName}] No notification method found");
                        $results[$roleName] = [
                            'success' => true,
                            'notified_count' => 0,
                            'recipient_type' => 'none',
                            'message' => 'No notification method implemented',
                            'duration_ms' => 0,
                            'skipped' => true,
                        ];
                        continue;
                    }
                    
                    $responseData = $response->getData(true);
                    
                    $results[$roleName] = [
                        'success' => $responseData['success'] ?? true,
                        'notified_count' => $responseData['notified_count'] ?? $responseData['emails_sent'] ?? 0,
                        'total_documents' => $responseData['total_documents'] ?? 0,
                        'recipient_type' => method_exists($controller, 'notifyAccountingUnits') ? 'accounting_units' : 'focal_persons',
                        'message' => $responseData['message'] ?? 'Notifications sent successfully',
                        'duration_ms' => round((microtime(true) - $roleStartTime) * 1000, 2),
                    ];
                }
                
                Log::info("✅ [{$roleName}] Accounting unit notifications completed", [
                    'notified_count' => $results[$roleName]['notified_count'],
                    'recipient_type' => $results[$roleName]['recipient_type'] ?? 'unknown',
                    'duration_ms' => $results[$roleName]['duration_ms']
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
                
                Log::error("❌ [{$roleName}] Accounting unit notifications failed", [
                    'error' => $e->getMessage(),
                    'duration_ms' => $duration,
                ]);
            }
        }
        
        // Summary statistics
        $totalDuration = now()->diffInMilliseconds($startTime);
        $successCount = collect($results)->where('success', true)->count();
        $totalNotified = collect($results)->sum('notified_count');
        $totalDocuments = collect($results)->sum('total_documents');
        
        $recipientBreakdown = collect($results)->groupBy('recipient_type')->map->count();
        
        Log::info("📧 Accounting unit notifications completed", [
            'total_roles' => count($this->roleControllers),
            'successful' => $successCount,
            'failed' => count($this->roleControllers) - $successCount,
            'total_notified' => $totalNotified,
            'total_documents' => $totalDocuments,
            'recipient_breakdown' => $recipientBreakdown->toArray(),
            'total_duration_ms' => $totalDuration,
        ]);
        
        return [
            'results' => $results,
            'summary' => [
                'total_roles' => count($this->roleControllers),
                'successful' => $successCount,
                'failed' => count($this->roleControllers) - $successCount,
                'total_notified' => $totalNotified,
                'total_documents' => $totalDocuments,
                'recipient_breakdown' => $recipientBreakdown->toArray(),
                'total_duration_ms' => $totalDuration,
            ]
        ];
    }

    /**
     * Update all dashboards for aging documents
     * This sends Pusher alerts for documents aged 2+ days
     * 
     * @param int $daysThreshold Number of days to consider as aging (default: 2)
     * @return array Results of the operation
     */
    public function updateAllDashboards(int $daysThreshold = 2): array
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
                
                // Handle GIP-specific response format
                $results[$roleName] = [
                    'success' => $responseData['success'] ?? true,
                    'alerted_count' => $responseData['total_documents'] ?? $responseData['alerted_count'] ?? 0,
                    'emails_sent' => $responseData['emails_sent'] ?? 0,
                    'alerts_created' => $responseData['alerts_created'] ?? 0,
                    'recipient_type' => 'accounting_units', // Always accounting units for dashboards
                    'message' => $responseData['message'] ?? 'Dashboard updated successfully',
                    'duration_ms' => $duration,
                ];
                
                Log::info("✅ [{$roleName}] Dashboard updated successfully", [
                    'alerted_count' => $results[$roleName]['alerted_count'],
                    'recipient_type' => $results[$roleName]['recipient_type'],
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
                ]);
            }
        }
        
        // Summary statistics
        $totalDuration = now()->diffInMilliseconds($startTime);
        $successCount = collect($results)->where('success', true)->count();
        $totalAlerted = collect($results)->sum('alerted_count');
        $totalEmails = collect($results)->sum('emails_sent');
        
        Log::info("📊 Dashboard updates completed", [
            'total_roles' => count($this->roleControllers),
            'successful' => $successCount,
            'failed' => count($this->roleControllers) - $successCount,
            'total_alerted' => $totalAlerted,
            'total_emails' => $totalEmails,
            'total_duration_ms' => $totalDuration,
        ]);
        
        return [
            'results' => $results,
            'summary' => [
                'total_roles' => count($this->roleControllers),
                'successful' => $successCount,
                'failed' => count($this->roleControllers) - $successCount,
                'total_alerted' => $totalAlerted,
                'total_emails' => $totalEmails,
                'total_duration_ms' => $totalDuration,
            ]
        ];
    }

    /**
     * Send critical alerts for all aging documents
     * Sends alerts to accounting units for documents aged 10+ days
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
                    'alerted_count' => $responseData['total_documents'] ?? $responseData['alerted_count'] ?? 0,
                    'emails_sent' => $responseData['emails_sent'] ?? 0,
                    'alerts_created' => $responseData['alerts_created'] ?? 0,
                    'recipient_type' => 'accounting_units', // Always accounting units
                    'message' => $responseData['message'] ?? 'Critical alerts sent successfully',
                    'duration_ms' => $duration,
                ];
                
                Log::info("✅ [{$roleName}] Critical alerts sent successfully", [
                    'alerted_count' => $results[$roleName]['alerted_count'],
                    'recipient_type' => $results[$roleName]['recipient_type'],
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
                ]);
            }
        }
        
        // Summary statistics
        $totalDuration = now()->diffInMilliseconds($startTime);
        $successCount = collect($results)->where('success', true)->count();
        $totalAlerted = collect($results)->sum('alerted_count');
        $totalEmails = collect($results)->sum('emails_sent');
        
        Log::info("🚨 Critical alerts completed", [
            'total_roles' => count($this->roleControllers),
            'successful' => $successCount,
            'failed' => count($this->roleControllers) - $successCount,
            'total_alerted' => $totalAlerted,
            'total_emails' => $totalEmails,
            'total_duration_ms' => $totalDuration,
        ]);
        
        return [
            'results' => $results,
            'summary' => [
                'total_roles' => count($this->roleControllers),
                'successful' => $successCount,
                'failed' => count($this->roleControllers) - $successCount,
                'total_alerted' => $totalAlerted,
                'total_emails' => $totalEmails,
                'total_duration_ms' => $totalDuration,
            ]
        ];
    }

    /**
     * Process a single role
     */
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
                    // For GIP, this is the same as critical alerts
                    if ($roleName === 'GIP') {
                        $response = $controller->sendAgingAlerts(true);
                    } else {
                        // Try accounting units first, then fallback
                        if (method_exists($controller, 'notifyAccountingUnits')) {
                            $response = $controller->notifyAccountingUnits();
                        } elseif (method_exists($controller, 'notifyFocalPersonsOfAgingDocuments')) {
                            $response = $controller->notifyFocalPersonsOfAgingDocuments();
                        } else {
                            return [
                                'success' => false,
                                'message' => "No notification method found for {$roleName}",
                            ];
                        }
                    }
                    break;
                    
                case 'critical':
                    $response = $controller->sendAgingAlerts(true);
                    break;
                    
                default:
                    return [
                        'success' => false,
                        'message' => "Invalid action: {$action}",
                        'available_actions' => ['dashboard', 'notify', 'critical'],
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
                'duration_ms' => $duration,
                'recipient_type' => 'accounting_units',
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
     */
    public function getConfiguredRoles(): array
    {
        return array_keys($this->roleControllers);
    }

    /**
     * Get summary of service configuration
     */
    public function getServiceInfo(): array
    {
        return [
            'total_roles' => count($this->roleControllers),
            'roles' => $this->getConfiguredRoles(),
            'actions_available' => ['dashboard', 'notify', 'critical'],
            'recipient_type' => 'accounting_units', // Primary recipient type
            'service_version' => '1.0.0',
            'timestamp' => now()->timezone('Asia/Manila')->toDateTimeString(),
        ];
    }
}