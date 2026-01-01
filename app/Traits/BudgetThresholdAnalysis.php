<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Setting;

trait BudgetThresholdAnalysis
{
    /**
     * Get budget analysis with thresholds for each program
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    protected function getBudgetAnalysisWithThresholds($dateFrom, $dateTo)
    {
        // Get budget allocations from settings
        $allocatedBudgets = [
            'TUPAD' => (float) Setting::get('budget_tupad', 10000000.00),
            'GIP'   => (float) Setting::get('budget_gip', 5000000.00),
            'TEV'   => (float) Setting::get('budget_tev', 3000000.00)
        ];

        // Get actual obligated amounts within date range
        $obligatedBudgets = DB::table('budget_obligation_history as h')
            ->join('dv_requests as d', 'h.dv_request_id', '=', 'd.id')
            ->join('programs as p', 'd.program_id', '=', 'p.id')
            ->where('h.status_after', 'ORS_obligated')
            ->where('h.verification_step', 'obligate')
            ->whereBetween('h.created_at', [$dateFrom, $dateTo])
            ->select(
                'p.prcode',
                DB::raw('SUM(h.obligated_amount) as total_obligated'),
                DB::raw('COUNT(DISTINCT d.id) as request_count')
            )
            ->groupBy('p.prcode')
            ->get()
            ->keyBy('prcode');

        // Calculate budget status for each program
        $budgetAnalysis = [];
        
        foreach (['TUPAD', 'GIP', 'TEV'] as $program) {
            $allocated = $allocatedBudgets[$program] ?? 0;
            $obligated = $obligatedBudgets->get($program)->total_obligated ?? 0;
            $requestCount = $obligatedBudgets->get($program)->request_count ?? 0;
            
            $utilizationRate = $allocated > 0 ? ($obligated / $allocated) * 100 : 0;
            $remaining = $allocated - $obligated;
            
            // Determine status and alert level
            $status = $this->determineBudgetStatus($utilizationRate);
            $allowNewRequests = $utilizationRate < 100;
            
            $budgetAnalysis[$program] = [
                'program' => $program,
                'allocated_budget' => $allocated,
                'obligated_budget' => $obligated,
                'remaining_budget' => $remaining,
                'utilization_rate' => round($utilizationRate, 2),
                'request_count' => $requestCount,
                'status' => $status,
                'allow_new_requests' => $allowNewRequests,
                'alert_level' => $this->getAlertLevel($utilizationRate),
                'description' => $this->getBudgetDescription($program, $utilizationRate, $remaining, $allowNewRequests)
            ];
        }

        return [
            'programs' => $budgetAnalysis,
            'overall_status' => $this->getOverallBudgetStatus($budgetAnalysis),
            'critical_programs' => $this->getCriticalPrograms($budgetAnalysis),
            'recommendations' => $this->generateBudgetRecommendations($budgetAnalysis)
        ];
    }

    /**
     * SIMPLE BUDGET CHECK - Use this in DvRequestController
     * 
     * @param string $programCode
     * @return array
     */
    public function canAcceptNewRequest($programCode)
    {
        try {
            // Get allocated budget from settings
            $allocatedBudgets = [
                'TUPAD' => (float) Setting::get('budget_tupad', 10000000.00),
                'GIP'   => (float) Setting::get('budget_gip', 5000000.00),
                'TEV'   => (float) Setting::get('budget_tev', 3000000.00)
            ];
            
            $allocated = $allocatedBudgets[$programCode] ?? 0;
            
            if ($allocated <= 0) {
                return [
                    'allowed' => true, // Allow if no budget set
                    'reason' => 'No budget allocation configured',
                    'allocated_budget' => 0,
                    'obligated_budget' => 0,
                    'remaining_budget' => 0
                ];
            }

            // Get obligated amount for current year
            $obligatedData = DB::table('budget_obligation_history as h')
                ->join('dv_requests as d', 'h.dv_request_id', '=', 'd.id')
                ->join('programs as p', 'd.program_id', '=', 'p.id')
                ->where('h.status_after', 'ORS_obligated')
                ->where('h.verification_step', 'obligate')
                ->where('p.prcode', $programCode)
                ->whereYear('h.created_at', date('Y'))
                ->select(DB::raw('SUM(h.obligated_amount) as total_obligated'))
                ->first();
            
            $obligated = $obligatedData->total_obligated ?? 0;
            $remaining = $allocated - $obligated;
            
            // Budget is exceeded if remaining is negative
            $budgetExceeded = $remaining < 0;
            
            return [
                'allowed' => !$budgetExceeded,
                'program' => $programCode,
                'allocated_budget' => $allocated,
                'obligated_budget' => $obligated,
                'remaining_budget' => $remaining,
                'budget_exceeded' => $budgetExceeded,
                'reason' => $budgetExceeded 
                    ? "Budget exceeded. Obligated: ₱" . number_format($obligated, 2) . 
                      " / Allocated: ₱" . number_format($allocated, 2)
                    : "Available budget: ₱" . number_format($remaining, 2)
            ];
            
        } catch (\Exception $e) {
            \Log::error('Budget check failed', [
                'program' => $programCode,
                'error' => $e->getMessage()
            ]);
            
            // Fail-safe: allow submission if check fails
            return [
                'allowed' => true,
                'reason' => 'Budget check temporarily unavailable',
                'allocated_budget' => 0,
                'obligated_budget' => 0,
                'remaining_budget' => 0
            ];
        }
    }

    /**
     * Check if a specific amount can be accommodated
     * 
     * @param string $programCode
     * @param float $requestAmount
     * @return array
     */
    public function canAccommodateAmount($programCode, $requestAmount)
    {
        $budgetCheck = $this->canAcceptNewRequest($programCode);
        
        if (!$budgetCheck['allowed']) {
            return [
                'allowed' => false,
                'reason' => $budgetCheck['reason'],
                'remaining_budget' => $budgetCheck['remaining_budget']
            ];
        }
        
        $remaining = $budgetCheck['remaining_budget'];
        
        if ($requestAmount > $remaining) {
            return [
                'allowed' => false,
                'reason' => "Request amount (₱" . number_format($requestAmount, 2) . 
                           ") Due to not fit within remaining budget.",
                'remaining_budget' => $remaining
            ];
        }
        
        return [
            'allowed' => true,
            'reason' => "Amount can be accommodated. Remaining budget: ₱" . 
                       number_format($remaining, 2),
            'remaining_budget' => $remaining
        ];
    }

    /**
     * Determine budget status based on utilization rate
     */
    private function determineBudgetStatus($utilizationRate)
    {
        if ($utilizationRate >= 100) {
            return 'EXCEEDED';
        } elseif ($utilizationRate >= 90) {
            return 'CRITICAL';
        } elseif ($utilizationRate >= 75) {
            return 'WARNING';
        } elseif ($utilizationRate >= 50) {
            return 'MODERATE';
        } else {
            return 'HEALTHY';
        }
    }

    /**
     * Get alert level for UI styling
     */
    private function getAlertLevel($utilizationRate)
    {
        if ($utilizationRate >= 100) {
            return 'danger';
        } elseif ($utilizationRate >= 90) {
            return 'critical';
        } elseif ($utilizationRate >= 75) {
            return 'warning';
        } else {
            return 'normal';
        }
    }

    /**
     * Generate descriptive analysis for each program
     */
    private function getBudgetDescription($program, $utilizationRate, $remaining, $allowNewRequests)
    {
        $description = [];

        if ($utilizationRate >= 100) {
            $description[] = "⛔ <strong>{$program} has EXCEEDED its allocated budget</strong> ({$utilizationRate}% utilized).";
            $description[] = "🚫 All incoming DV requests for {$program} are <strong>NOT ALLOWED</strong> until budget is replenished.";
            $description[] = "⚠️ Overrun amount: ₱" . number_format(abs($remaining), 2);
        } elseif ($utilizationRate >= 90) {
            $description[] = "🔴 <strong>CRITICAL:</strong> {$program} is at {$utilizationRate}% budget utilization.";
            $description[] = "⚠️ Only ₱" . number_format($remaining, 2) . " remaining.";
            $description[] = "📌 Exercise extreme caution when approving new requests.";
            $description[] = "💡 Consider implementing temporary restrictions or seeking budget augmentation.";
        } elseif ($utilizationRate >= 75) {
            $description[] = "🟡 <strong>WARNING:</strong> {$program} has used {$utilizationRate}% of allocated budget.";
            $description[] = "💰 Remaining budget: ₱" . number_format($remaining, 2);
            $description[] = "📊 Monitor closely and prioritize essential requests only.";
        } elseif ($utilizationRate >= 50) {
            $description[] = "🟢 {$program} budget utilization is at {$utilizationRate}% - within acceptable range.";
            $description[] = "💰 Remaining budget: ₱" . number_format($remaining, 2);
            $description[] = "✅ Continue normal operations with standard monitoring.";
        } else {
            $description[] = "✅ {$program} budget is <strong>HEALTHY</strong> at {$utilizationRate}% utilization.";
            $description[] = "💰 Sufficient funds remaining: ₱" . number_format($remaining, 2);
            $description[] = "📈 Good budget management. Continue current practices.";
        }

        return implode('<br>', $description);
    }

    /**
     * Get overall budget health status
     */
    private function getOverallBudgetStatus($budgetAnalysis)
    {
        $statuses = collect($budgetAnalysis)->pluck('status');
        
        if ($statuses->contains('EXCEEDED')) {
            return [
                'status' => 'CRITICAL',
                'message' => 'One or more programs have exceeded their budget allocation.',
                'color' => 'red'
            ];
        } elseif ($statuses->contains('CRITICAL')) {
            return [
                'status' => 'HIGH_RISK',
                'message' => 'Some programs are critically close to budget limits.',
                'color' => 'orange'
            ];
        } elseif ($statuses->contains('WARNING')) {
            return [
                'status' => 'ATTENTION_NEEDED',
                'message' => 'Monitor programs approaching budget thresholds.',
                'color' => 'yellow'
            ];
        } else {
            return [
                'status' => 'HEALTHY',
                'message' => 'All programs are within acceptable budget ranges.',
                'color' => 'green'
            ];
        }
    }

    /**
     * Identify critical programs that need immediate attention
     */
    private function getCriticalPrograms($budgetAnalysis)
    {
        return collect($budgetAnalysis)
            ->filter(function ($program) {
                return in_array($program['status'], ['EXCEEDED', 'CRITICAL']);
            })
            ->values()
            ->all();
    }

    /**
     * Generate actionable recommendations
     */
    private function generateBudgetRecommendations($budgetAnalysis)
    {
        $recommendations = [];

        foreach ($budgetAnalysis as $program => $data) {
            if ($data['status'] === 'EXCEEDED') {
                $recommendations[] = [
                    'priority' => 'HIGH',
                    'program' => $program,
                    'action' => 'IMMEDIATE',
                    'message' => "Suspend all new {$program} DV requests. Request budget realignment or augmentation immediately."
                ];
            } elseif ($data['status'] === 'CRITICAL') {
                $recommendations[] = [
                    'priority' => 'HIGH',
                    'program' => $program,
                    'action' => 'URGENT',
                    'message' => "Implement strict approval controls for {$program}. Prepare budget revision documents."
                ];
            } elseif ($data['status'] === 'WARNING') {
                $recommendations[] = [
                    'priority' => 'MEDIUM',
                    'program' => $program,
                    'action' => 'MONITOR',
                    'message' => "Increase monitoring frequency for {$program}. Review upcoming commitments."
                ];
            }
        }

        return $recommendations;
    }
}