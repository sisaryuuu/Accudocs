@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
@php
    // Helper functions for safe calculations
    function safeDivide($numerator, $denominator, $default = 0) {
        return $denominator != 0 ? $numerator / $denominator : $default;
    }
    
    function safePercentage($part, $whole, $default = 0) {
        return $whole != 0 ? round(($part / $whole) * 100, 2) : $default;
    }
    
    // Initialize variables with defaults
    $totalRequests = $totalRequests ?? 0;
    $pendingRequests = $pendingRequests ?? 0;
    $approvedRequests = $approvedRequests ?? 0;
    $rejectedRequests = $rejectedRequests ?? 0;
    $requestsPerProgram = $requestsPerProgram ?? collect([]);
    $budgetPerProgram = $budgetPerProgram ?? collect([]);
    $documentsPerRole = $documentsPerRole ?? collect([]);
    $monthlyRequests = $monthlyRequests ?? collect([]);
    $slaAnalysis = $slaAnalysis ?? [
        'total_completed_requests' => 0,
        'within_sla' => 0,
        'sla_violations' => 0,
        'violation_rate_percent' => 0,
        'avg_working_days' => 0,
        'worst_performer' => 0
    ];
    $bottlenecks = $bottlenecks ?? collect([]);
    $staffPerformance = $staffPerformance ?? [
        'individual_metrics' => collect([]),
        'monthly_productivity' => collect([]),
        'workload_by_role' => collect([]),
        'top_performers' => collect([]),
        'insights' => []
    ];
    $insights = $insights ?? [];
    $budgetAnalysis = $budgetAnalysis ?? [
        'programs' => [],
        'overall_status' => ['status' => 'HEALTHY', 'message' => ''],
        'recommendations' => []
    ];
@endphp

<!-- Dashboard Header with Controls -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Dashboard Overview</h1>
        <p class="text-gray-600">Real-time monitoring of DV request activities</p>
    </div>
    <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-3 w-full md:w-auto">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-col md:flex-row gap-2 items-start md:items-center w-full">
            <div class="flex flex-col md:flex-row gap-3 w-full">
                <div class="flex items-center space-x-2">
                    <label for="date_from" class="text-sm font-medium text-gray-700 whitespace-nowrap">From:</label>
                    <input type="date" 
                           id="date_from" 
                           name="date_from" 
                           value="{{ request('date_from', now()->subMonths(12)->format('Y-m-d')) }}"
                           class="border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full md:w-auto"
                           max="{{ now()->format('Y-m-d') }}">
                </div>
                
                <div class="flex items-center space-x-2">
                    <label for="date_to" class="text-sm font-medium text-gray-700 whitespace-nowrap">To:</label>
                    <input type="date" 
                           id="date_to" 
                           name="date_to" 
                           value="{{ request('date_to', now()->format('Y-m-d')) }}"
                           class="border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full md:w-auto"
                           max="{{ now()->format('Y-m-d') }}"
                           min="{{ request('date_from', now()->subMonths(12)->format('Y-m-d')) }}">
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 flex items-center justify-center space-x-2 whitespace-nowrap w-full md:w-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        <span>Apply Filter</span>
                    </button>
                    
                    @if(request('date_from') || request('date_to'))
                    <a href="{{ route('admin.dashboard') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-400 flex items-center justify-center space-x-2 whitespace-nowrap w-full md:w-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>Clear</span>
                    </a>
                    @endif
                </div>
            </div>
        </form>
        
        <!-- Quick Filter Presets -->
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.dashboard.preset', 'today') }}" class="px-3 py-2 bg-blue-100 text-blue-700 text-xs rounded hover:bg-blue-200">
                Today
            </a>
            <a href="{{ route('admin.dashboard.preset', 'this_week') }}" class="px-3 py-2 bg-blue-100 text-blue-700 text-xs rounded hover:bg-blue-200">
                This Week
            </a>
            <a href="{{ route('admin.dashboard.preset', 'this_month') }}" class="px-3 py-2 bg-blue-100 text-blue-700 text-xs rounded hover:bg-blue-200">
                This Month
            </a>
            <a href="{{ route('admin.dashboard.preset', 'last_month') }}" class="px-3 py-2 bg-blue-100 text-blue-700 text-xs rounded hover:bg-blue-200">
                Last Month
            </a>
            <a href="{{ route('admin.dashboard.preset', 'last_3_months') }}" class="px-3 py-2 bg-blue-100 text-blue-700 text-xs rounded hover:bg-blue-200">
                Last 3 Months
            </a>
        </div>
            
        <!-- Manual Refresh Button -->
        <button onclick="manualRefresh()" class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 flex items-center justify-center space-x-2 whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>Refresh</span>
        </button>
    </div>
</div>

<!-- Active Filter Display -->
@if(request('date_from') || request('date_to'))
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-blue-800 font-medium">
                Showing data from {{ \Carbon\Carbon::parse(request('date_from', now()->subMonths(12)))->format('M d, Y') }} 
                to {{ \Carbon\Carbon::parse(request('date_to', now()))->format('M d, Y') }}
            </span>
        </div>
        <span class="text-sm text-blue-600">
            ({{ \Carbon\Carbon::parse(request('date_from', now()->subMonths(12)))->diffInDays(\Carbon\Carbon::parse(request('date_to', now()))) }} days)
        </span>
    </div>
</div>
@endif

<!-- KPI Cards - Clickable with Hover Effects -->
<div class="flex flex-col md:flex-row gap-4 mb-8">
    <div onclick="openKPIModal('total')" class="flex-1 bg-white p-4 rounded-lg shadow text-center cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-blue-50 group">
        <h2 class="text-lg font-semibold text-gray-700 group-hover:text-blue-700">Total Requests</h2>
        <p class="text-2xl font-bold text-blue-600" id="kpi-total">{{ $totalRequests }}</p>
        <p class="text-xs text-gray-500 mt-1 group-hover:text-blue-600">Click for details</p>
    </div>
    <div onclick="openKPIModal('pending')" class="flex-1 bg-white p-4 rounded-lg shadow text-center cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-yellow-50 group">
        <h2 class="text-lg font-semibold text-gray-700 group-hover:text-yellow-700">Pending</h2>
        <p class="text-2xl font-bold text-yellow-600" id="kpi-pending">{{ $pendingRequests }}</p>
        <p class="text-xs text-gray-500 mt-1 group-hover:text-yellow-600">Click for details</p>
    </div>
    <div onclick="openKPIModal('approved')" class="flex-1 bg-white p-4 rounded-lg shadow text-center cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-green-50 group">
        <h2 class="text-lg font-semibold text-gray-700 group-hover:text-green-700">Approved</h2>
        <p class="text-2xl font-bold text-green-600" id="kpi-approved">{{ $approvedRequests }}</p>
        <p class="text-xs text-gray-500 mt-1 group-hover:text-green-600">Click for details</p>
    </div>
    <div onclick="openKPIModal('rejected')" class="flex-1 bg-white p-4 rounded-lg shadow text-center cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-red-50 group">
        <h2 class="text-lg font-semibold text-gray-700 group-hover:text-red-700">Rejected</h2>
        <p class="text-2xl font-bold text-red-600" id="kpi-rejected">{{ $rejectedRequests }}</p>
        <p class="text-xs text-gray-500 mt-1 group-hover:text-red-600">Click for details</p>
    </div>
    <div onclick="openKPIModal('processing')" class="flex-1 bg-white p-4 rounded-lg shadow text-center cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-purple-50 group">
        <h3 class="text-sm font-medium text-gray-700 group-hover:text-purple-700">Average Processing Time</h3>
        <p class="text-2xl font-bold text-purple-600" id="sla-avg-days">{{ round($slaAnalysis['avg_working_days'], 1) }} days</p>
        <p class="text-xs text-gray-500 mt-1 group-hover:text-purple-600">Click for details</p>
    </div>
</div>

<!-- KPI Details Modal -->
<div id="kpiModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden transform transition-all">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex justify-between items-center">
            <h3 id="modalTitle" class="text-xl font-bold text-white">Details</h3>
            <button onclick="closeKPIModal()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 140px);">
            <div id="modalContent">
                <!-- Content will be dynamically loaded -->
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3 border-t">
            <button onclick="exportKPIData()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span>Export</span>
            </button>
            <button onclick="closeKPIModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<!-- SLA Compliance Section -->
<div class="bg-white p-4 rounded-lg shadow mt-6">
    <h2 class="text-lg font-semibold mb-4">10-Working-Day Processing Time Summary</h2>
    
    @if($slaAnalysis['total_completed_requests'] > 0)
        <!-- SLA KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg border text-center">
                <h3 class="text-sm font-medium text-blue-800">Total Completed</h3>
                <p class="text-2xl font-bold text-blue-600" id="sla-total">{{ $slaAnalysis['total_completed_requests'] }}</p>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg border text-center">
                <h3 class="text-sm font-medium text-green-800">Processed Within 10 Days</h3>
                <p class="text-2xl font-bold text-green-600" id="sla-within">{{ $slaAnalysis['within_sla'] }}</p>
            </div>
            
            <div class="bg-red-50 p-4 rounded-lg border text-center">
                <h3 class="text-sm font-medium text-red-800">Exceeded 10 Days</h3>
                <p class="text-2xl font-bold text-red-600" id="sla-violations">{{ $slaAnalysis['sla_violations'] }}</p>
            </div>
            
            <div class="bg-purple-50 p-4 rounded-lg border text-center">
                <h3 class="text-sm font-medium text-purple-800">Average Processing Time</h3>
                <p class="text-2xl font-bold text-purple-600" id="sla-avg-days">{{ round($slaAnalysis['avg_working_days'], 1) }} days</p>
            </div>
        </div>

        <!-- Compliance Rate -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">On-Time Rate</span>
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-bold" id="sla-compliance-rate-text">
                        {{ 100 - $slaAnalysis['violation_rate_percent'] }}%
                    </span>
                    <span class="text-xs px-2 py-1 rounded-full" id="sla-compliance-status">
                        @php
                            $complianceRate = 100 - $slaAnalysis['violation_rate_percent'];
                            $status = $complianceRate >= 75 ? 'Target Met' : 'Below Target';
                            $bgColor = $complianceRate >= 90 ? 'bg-green-100 text-green-800' : 
                                       ($complianceRate >= 75 ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800');
                        @endphp
                        <span class="{{ $bgColor }}">{{ $status }}</span>
                    </span>
                </div>
            </div>
            
            <!-- Progress Bar Container -->
            <div class="w-full bg-gray-200 rounded-full h-4 relative">
                <!-- Target Line -->
                <div class="absolute top-0 bottom-0 w-0.5 bg-gray-700" style="left: 75%; transform: translateX(-50%);">
                    <div class="absolute -top-6 text-xs text-gray-600 font-medium whitespace-nowrap" style="left: 50%; transform: translateX(-50%);">
                        Target
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="h-4 rounded-full transition-all duration-500 ease-in-out" 
                     id="sla-progress-bar"
                     style="width: {{ 100 - $slaAnalysis['violation_rate_percent'] }}%;
                            @php
                                $rate = 100 - $slaAnalysis['violation_rate_percent'];
                                $color = $rate >= 90 ? '#10b981' : ($rate >= 75 ? '#3b82f6' : '#ef4444');
                            @endphp
                            background-color: {{ $color }};">
                </div>
            </div>
            
            <!-- Progress Labels -->
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>0%</span>
                <span class="text-gray-700 font-medium">Target: 75%</span>
                <span>100%</span>
            </div>
        </div>

        <!-- Worst Performers -->
        @if($slaAnalysis['worst_performer'] > 10)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-yellow-800 font-medium">
                        Worst case: {{ $slaAnalysis['worst_performer'] }} working days
                    </span>
                </div>
            </div>
        @endif

    @else
        <!-- No SLA Data Available -->
        <div class="text-center py-8 text-gray-500">
            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="mt-2">No completed requests available for SLA analysis</p>
            <p class="text-sm">Requests need to reach Regional Director to be included</p>
        </div>
    @endif
</div>

<div class="bg-white rounded-lg shadow-lg overflow-hidden mt-6">
    <!-- Section Header -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h2 class="text-xl font-bold text-white">Budget Threshold Analysis</h2>
                    <p class="text-purple-100 text-sm">Real-time budget utilization and controls</p>
                </div>
            </div>
            <div id="overall-status-badge" class="px-4 py-2 rounded-full text-sm font-semibold bg-white text-purple-700">
                @php
                    $overallStatus = $budgetAnalysis['overall_status']['status'] ?? 'HEALTHY';
                @endphp
                {{ str_replace('_', ' ', $overallStatus) }}
            </div>
        </div>
    </div>

    <!-- Overall Status Alert -->
    @php
        $overallAlert = $budgetAnalysis['overall_status'] ?? ['status' => 'HEALTHY', 'message' => ''];
    @endphp
    @if($overallAlert['status'] !== 'HEALTHY')
    <div id="overall-budget-alert" class="px-6 py-4 border-l-4 
        {{ $overallAlert['status'] === 'CRITICAL' ? 'bg-red-50 border-red-500 text-red-900' : 
           ($overallAlert['status'] === 'HIGH_RISK' ? 'bg-orange-50 border-orange-500 text-orange-900' : 
           ($overallAlert['status'] === 'ATTENTION_NEEDED' ? 'bg-yellow-50 border-yellow-500 text-yellow-900' : 
           'bg-green-50 border-green-500 text-green-900')) }}">
        <div class="flex items-start">
            <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
                <h3 id="overall-alert-title" class="font-semibold text-lg mb-1">{{ str_replace('_', ' ', $overallAlert['status']) }}</h3>
                <p id="overall-alert-message" class="text-sm">{{ $overallAlert['message'] ?? '' }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Program Budget Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
        @foreach(['TUPAD', 'GIP', 'TEV'] as $program)
            @php
                $programData = $budgetAnalysis['programs'][$program] ?? [
                    'allocated_budget' => 0,
                    'obligated_budget' => 0,
                    'remaining_budget' => 0,
                    'request_count' => 0,
                    'utilization_rate' => 0,
                    'status' => 'No Data',
                    'alert_level' => 'normal',
                    'description' => 'No budget data available',
                    'allow_new_requests' => true
                ];
                $prefix = strtolower($program);
                
                // Colors based on program
                $colors = [
                    'TUPAD' => ['bg' => 'from-blue-50 to-blue-100', 'text' => 'text-blue-900', 'border' => 'border-blue-200'],
                    'GIP' => ['bg' => 'from-green-50 to-green-100', 'text' => 'text-green-900', 'border' => 'border-green-200'],
                    'TEV' => ['bg' => 'from-orange-50 to-orange-100', 'text' => 'text-orange-900', 'border' => 'border-orange-200']
                ];
                
                // Alert level styling
                $alertClasses = [
                    'danger' => ['badge' => 'bg-red-100 text-red-800', 'progress' => 'bg-red-500', 'border' => 'border-red-500'],
                    'critical' => ['badge' => 'bg-orange-100 text-orange-800', 'progress' => 'bg-orange-500', 'border' => 'border-orange-500'],
                    'warning' => ['badge' => 'bg-yellow-100 text-yellow-800', 'progress' => 'bg-yellow-500', 'border' => 'border-yellow-500'],
                    'normal' => ['badge' => 'bg-green-100 text-green-800', 'progress' => 'bg-green-500', 'border' => 'border-green-500']
                ];
                
                $alertClass = $alertClasses[$programData['alert_level']] ?? $alertClasses['normal'];
            @endphp
            
            <div class="budget-card" data-program="{{ $program }}">
                <div class="bg-white border-2 rounded-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <!-- Card Header -->
                    <div class="px-4 py-3 border-b bg-gradient-to-r {{ $colors[$program]['bg'] }}">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-lg {{ $colors[$program]['text'] }}">{{ $program }}</h3>
                            <span id="{{ $prefix }}-status-badge" class="px-3 py-1 rounded-full text-xs font-semibold {{ $alertClass['badge'] }}">
                                {{ $programData['status'] }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 space-y-4">
                        <!-- Budget Overview -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Allocated</p>
                                <p id="{{ $prefix }}-allocated" class="text-lg font-bold text-gray-900">₱{{ number_format($programData['allocated_budget'], 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Obligated</p>
                                <p id="{{ $prefix }}-obligated" class="text-lg font-bold {{ $colors[$program]['text'] }}">₱{{ number_format($programData['obligated_budget'], 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Remaining</p>
                                <p id="{{ $prefix }}-remaining" class="text-lg font-bold text-green-600">₱{{ number_format($programData['remaining_budget'], 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Requests</p>
                                <p id="{{ $prefix }}-requests" class="text-lg font-bold text-gray-700">{{ $programData['request_count'] }}</p>
                            </div>
                        </div>

                        <!-- Utilization Progress Bar -->
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-600">Utilization Rate</span>
                                <span id="{{ $prefix }}-utilization" class="font-bold">{{ round($programData['utilization_rate'], 2) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-4 relative overflow-hidden">
                                <div id="{{ $prefix }}-progress" class="h-4 rounded-full transition-all duration-500 {{ $alertClass['progress'] }}" 
                                     style="width: {{ min($programData['utilization_rate'], 100) }}%">
                                </div>
                                <!-- Threshold markers -->
                                <div class="absolute top-0 bottom-0 w-0.5 bg-yellow-500" style="left: 75%"></div>
                                <div class="absolute top-0 bottom-0 w-0.5 bg-red-500" style="left: 90%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>0%</span>
                                <span class="text-yellow-600">75%</span>
                                <span class="text-red-600">90%</span>
                                <span>100%</span>
                            </div>
                        </div>

                        <!-- Status Description -->
                        <div id="{{ $prefix }}-description" class="text-xs bg-gray-50 p-3 rounded border-l-4 {{ $alertClass['border'] }}">
                            {!! $programData['description'] !!}
                        </div>

                        <!-- Action Status -->
                        <div id="{{ $prefix }}-action" class="flex items-center justify-between p-2 rounded {{ $programData['allow_new_requests'] ? 'bg-green-50' : 'bg-red-50' }}">
                            <span class="text-sm font-medium">New DV Requests</span>
                            <span id="{{ $prefix }}-allow-badge" class="px-3 py-1 rounded-full text-xs font-bold {{ $programData['allow_new_requests'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $programData['allow_new_requests'] ? '✓ ALLOWED' : '✗ BLOCKED' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Recommendations Section -->
    <div id="budget-recommendations" class="px-6 pb-6">
        <h3 class="font-semibold text-lg mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
            Action Items & Recommendations
        </h3>
        <div id="recommendations-list" class="space-y-3">
            @if(!empty($budgetAnalysis['recommendations']))
                @foreach($budgetAnalysis['recommendations'] as $rec)
                    @php
                        $priorityConfig = [
                            'HIGH' => ['bg' => 'bg-red-50', 'border' => 'border-red-500', 'text' => 'text-red-800', 'badge' => 'bg-red-100 text-red-800'],
                            'MEDIUM' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-500', 'text' => 'text-yellow-800', 'badge' => 'bg-yellow-100 text-yellow-800'],
                            'LOW' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-500', 'text' => 'text-blue-800', 'badge' => 'bg-blue-100 text-blue-800']
                        ];
                        $config = $priorityConfig[$rec['priority']] ?? $priorityConfig['LOW'];
                    @endphp
                    <div class="{{ $config['bg'] }} border-l-4 {{ $config['border'] }} p-4 rounded {{ $config['text'] }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="px-2 py-1 rounded text-xs font-bold {{ $config['badge'] }}">
                                        {{ $rec['priority'] }} PRIORITY
                                    </span>
                                    <span class="font-semibold">{{ $rec['program'] }}</span>
                                    <span class="text-xs px-2 py-1 bg-white rounded">{{ $rec['action'] }}</span>
                                </div>
                                <p class="text-sm">{{ $rec['message'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center text-gray-500 py-4">
                    No action items at this time. All budgets are within acceptable ranges.
                </div>
            @endif
        </div>
    </div>
</div>
<!-- Charts Section -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Requests per Program -->
<div class="bg-white p-4 rounded-lg shadow">
    <h2 class="text-lg font-semibold mb-4">Requests per Program</h2>
    <div class="chart-container" style="height: 300px;">
        <canvas id="requestsPerProgram"></canvas>
    </div>
    <div class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
        <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
            <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Key Insights
        </h3>
        
        @php
            $programs = $requestsPerProgram;
            $total = $programs->sum();
            $topProgram = $programs->sortDesc()->keys()->first() ?? 'No data';
            $topCount = $programs->max() ?? 0;
            $topPercentage = $total > 0 ? round(($topCount / $total) * 100, 1) : 0;
            
            // Calculate distribution balance
            $programValues = $programs->values()->toArray();
            $programCount = count($programValues);
            $avgPerProgram = $total > 0 && $programCount > 0 ? round($total / $programCount, 1) : 0;
            $variance = 0;
            foreach ($programValues as $value) {
                $variance += pow($value - $avgPerProgram, 2);
            }
            $variance = $programCount > 0 ? $variance / $programCount : 0;
            $isBalanced = $variance < ($avgPerProgram * 0.5);
        @endphp
        
        <div class="space-y-2 text-sm text-gray-700">
            <p class="text-sm text-gray-700">
                The program analysis reveals that <strong class="text-blue-700">{{ $topProgram }}</strong> leads with 
                <strong>{{ number_format($topCount) }}</strong> requests, accounting for <strong>{{ $topPercentage }}%</strong> 
                of total volume. The distribution across programs is 
                <strong class="{{ $isBalanced ? 'text-green-700' : 'text-yellow-700' }}">{{ $isBalanced ? 'well-balanced' : 'imbalanced' }}</strong>, 
                indicating {{ $isBalanced ? 'effective resource allocation' : 'a need to consider reallocating resources to underserved programs' }}. 
                The average workload stands at <strong>{{ number_format($avgPerProgram, 1) }}</strong> requests per program.
            </p>
            
            @if($topPercentage > 50)
            <div class="mt-3 p-2 bg-yellow-100 border-l-4 border-yellow-500 rounded">
                <p class="text-xs text-yellow-800">
                    <strong>⚠ Recommendation:</strong> {{ $topProgram }} is receiving over 50% of requests. 
                    Ensure adequate staffing and budget allocation to prevent service delays.
                </p>
            </div>
            @endif
        </div>
    </div>
</div>
    
    <!-- Budget per Program -->
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Total Budget Obligated per Program</h2>
        <div class="chart-container" style="height: 300px;">
            <canvas id="budgetPerProgram"></canvas>
        </div>
        <div class="mt-4 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200">
            <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
                <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Budget Insights
            </h3>
            
            @php
                $budgets = $budgetPerProgram;
                $totalBudget = $budgets->sum();
                $topBudgetProgram = $budgets->sortDesc()->keys()->first() ?? 'No data';
                $topBudgetAmount = $budgets->max() ?? 0;
                $topBudgetPercentage = $totalBudget > 0 ? round(($topBudgetAmount / $totalBudget) * 100, 1) : 0;
                
                // Calculate average per request
                $requestCounts = $requestsPerProgram;
                $budgetEfficiency = [];
                foreach ($budgets as $program => $budget) {
                    $requests = $requestCounts->get($program, 1);
                    $budgetEfficiency[$program] = $requests > 0 ? round($budget / $requests, 2) : 0;
                }
                
                if (!empty($budgetEfficiency)) {
                    arsort($budgetEfficiency);
                    $mostExpensiveProgram = array_key_first($budgetEfficiency);
                    $leastExpensiveProgram = array_key_last($budgetEfficiency);
                } else {
                    $mostExpensiveProgram = $leastExpensiveProgram = 'No data';
                    $budgetEfficiency = [];
                }
            @endphp
            
            <div class="space-y-2 text-sm text-gray-700">
                <p class="text-sm text-gray-700">
                    Total obligated budget across all programs amounts to <strong class="text-green-700">₱{{ number_format($totalBudget, 2) }}</strong>. 
                    <strong class="text-green-700">{{ $topBudgetProgram }}</strong> has the highest obligation at 
                    <strong>₱{{ number_format($topBudgetAmount, 2) }}</strong>, representing <strong>{{ $topBudgetPercentage }}%</strong> of total budget.
                    @if(!empty($budgetEfficiency) && $mostExpensiveProgram !== $leastExpensiveProgram)
                    Cost efficiency analysis shows <strong>{{ $mostExpensiveProgram }}</strong> has the highest cost per request at 
                    ₱{{ number_format($budgetEfficiency[$mostExpensiveProgram] ?? 0, 2) }}, while <strong>{{ $leastExpensiveProgram }}</strong> maintains 
                    the most efficient operations at ₱{{ number_format($budgetEfficiency[$leastExpensiveProgram] ?? 0, 2) }}.
                    @endif
                </p>
                
                @if($topBudgetPercentage > 60)
                <div class="mt-3 p-2 bg-blue-100 border-l-4 border-blue-500 rounded">
                    <p class="text-xs text-blue-800">
                        <strong>ℹ️ Note:</strong> {{ $topBudgetProgram }} consumes {{ $topBudgetPercentage }}% of total budget. 
                        Monitor closely to ensure fiscal sustainability.
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Documents per Role -->
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Documents Handled per Role</h2>
        <div class="chart-container" style="height: 300px;">
            <canvas id="documentsPerRole"></canvas>
        </div>
        <div class="mt-4 p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg border border-purple-200">
            <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
                <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Workload Distribution
            </h3>
            
            @php
                $docsPerRole = $documentsPerRole;
                $totalDocs = $docsPerRole->sum();
                
                if ($docsPerRole->isNotEmpty()) {
                    $busiestRole = $docsPerRole->sortDesc()->keys()->first();
                    $busiestCount = $docsPerRole->max();
                    $lightestRole = $docsPerRole->sort()->keys()->first();
                    $lightestCount = $docsPerRole->min();
                    $workloadGap = $busiestCount - $lightestCount;
                    $workloadGapPercent = $lightestCount > 0 ? round((($busiestCount - $lightestCount) / $lightestCount) * 100, 1) : 0;
                } else {
                    $busiestRole = $lightestRole = 'No data';
                    $busiestCount = $lightestCount = $workloadGap = $workloadGapPercent = 0;
                }
            @endphp
            
            <div class="space-y-2 text-sm text-gray-700">
                <p class="text-sm text-gray-700">
                    <strong class="text-purple-700">{{ $busiestRole }}</strong> handles the most documents with 
                    <strong>{{ number_format($busiestCount) }}</strong> requests, accounting for 
                    {{ round(($busiestCount / ($totalDocs > 0 ? $totalDocs : 1)) * 100, 1) }}% of total workload. 
                    There is a workload gap of <strong>{{ number_format($workloadGap) }}</strong> documents between the busiest 
                    ({{ $busiestRole }}) and lightest ({{ $lightestRole }}) roles. 
                    @if($workloadGapPercent > 50)
                    This represents a <strong class="text-orange-700">significant imbalance of {{ $workloadGapPercent }}%</strong>, 
                    indicating a need for task redistribution and load balancing.
                    @elseif($workloadGapPercent > 25)
                    This represents a <strong class="text-yellow-700">moderate imbalance of {{ $workloadGapPercent }}%</strong> 
                    that should be monitored for potential overload.
                    @elseif($busiestCount > 0)
                    The workload distribution is <strong class="text-green-700">relatively balanced</strong> across roles.
                    @endif
                </p>
                
                @if($workloadGapPercent > 50)
                <div class="mt-3 p-2 bg-orange-100 border-l-4 border-orange-500 rounded">
                    <p class="text-xs text-orange-800">
                        <strong>⚠ Action Required:</strong> {{ $busiestRole }} is handling {{ $workloadGapPercent }}% more documents than {{ $lightestRole }}. 
                        Consider adding staff or implementing parallel processing.
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Monthly Requests Trend -->
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Monthly Requests Trend</h2>
        <div class="chart-container" style="height: 300px;">
            <canvas id="monthlyRequestsChart"></canvas>
        </div>
        <div class="mt-4 p-4 bg-gradient-to-r from-orange-50 to-amber-50 rounded-lg border border-orange-200">
            <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
                <svg class="w-4 h-4 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                Trend Analysis
            </h3>
            
            @php
                $monthly = $monthlyRequests;
                $monthlyValues = $monthly->values();
                
                if ($monthly->isNotEmpty()) {
                    $latestMonth = $monthly->keys()->last();
                    $latestValue = $monthlyValues->last();
                    $previousValue = $monthlyValues->count() > 1 ? $monthlyValues->slice(-2, 1)->first() : $latestValue;
                    
                    // Calculate growth
                    $growth = $previousValue > 0 ? round((($latestValue - $previousValue) / $previousValue) * 100, 1) : 0;
                    
                    // Calculate average
                    $avgMonthly = $monthlyValues->avg();
                    
                    // Peak month
                    $peakMonth = $monthly->sortDesc()->keys()->first();
                    $peakValue = $monthly->max();
                    
                    // Trend direction
                    $recentTrend = $monthlyValues->slice(-3)->values();
                    $isTrendingUp = $recentTrend->count() >= 2 && 
                                    $recentTrend->last() > $recentTrend->first();
                    
                    // Volatility
                    $variance = 0;
                    foreach ($monthlyValues as $value) {
                        $variance += pow($value - $avgMonthly, 2);
                    }
                    $stdDev = sqrt($variance / max($monthlyValues->count(), 1));
                    $volatility = $avgMonthly > 0 ? round(($stdDev / $avgMonthly) * 100, 1) : 0;
                } else {
                    $latestMonth = $peakMonth = 'No data';
                    $latestValue = $previousValue = $growth = $avgMonthly = $peakValue = 0;
                    $isTrendingUp = false;
                    $volatility = 0;
                }
            @endphp
            
            <div class="space-y-2 text-sm text-gray-700">
                @if($latestValue > 0)
                <p class="text-sm text-gray-700">
                    In <strong>{{ $latestMonth }}</strong>, there were <strong class="{{ $growth > 0 ? 'text-green-700' : ($growth < 0 ? 'text-red-700' : 'text-gray-700') }}">
                    {{ number_format($latestValue) }} requests</strong>{{ $growth != 0 ? ', representing ' . ($growth > 0 ? 'an increase' : 'a decrease') . ' of ' . abs($growth) . '% compared to the previous month' : '' }}.
                    The data shows a <strong class="{{ $isTrendingUp ? 'text-green-700' : 'text-blue-700' }}">{{ $isTrendingUp ? 'upward trend' : 'stable or declining trend' }}</strong> 
                    over the last three months. The peak month was <strong>{{ $peakMonth }}</strong> with <strong>{{ number_format($peakValue) }}</strong> requests, 
                    {{ $avgMonthly > 0 ? 'which is ' . round(($peakValue / $avgMonthly - 1) * 100, 1) . '% above the average' : '' }}. Volume volatility stands at 
                    <strong>{{ $volatility }}%</strong>, indicating 
                    @if($volatility < 15)
                        <span class="text-green-700">very stable</span>
                    @elseif($volatility < 30)
                        <span class="text-blue-700">moderately stable</span>
                    @else
                        <span class="text-yellow-700">high variation</span>
                    @endif
                    request patterns.
                </p>
                
                @if($growth > 15)
                <div class="mt-3 p-2 bg-green-100 border-l-4 border-green-500 rounded">
                    <p class="text-xs text-green-800">
                        <strong>📈 Growth Alert:</strong> {{ abs($growth) }}% increase indicates rising demand. 
                        Plan for additional capacity and budget allocation for next quarter.
                    </p>
                </div>
                @elseif($growth < -15)
                <div class="mt-3 p-2 bg-blue-100 border-l-4 border-blue-500 rounded">
                    <p class="text-xs text-blue-800">
                        <strong>📉 Volume Drop:</strong> {{ abs($growth) }}% decrease may indicate seasonal variation 
                        or program changes. Investigate if unexpected.
                    </p>
                </div>
                @endif
                @else
                <div class="text-center text-gray-500 py-2">
                    No monthly trend data available
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Staff Performance Chart -->
<div class="bg-white p-4 rounded-lg shadow mb-8">
    <h2 class="text-lg font-semibold mb-4">Accounting Staff Performance Trends</h2>
    
    <!-- Add toggle for different views -->
    <div class="flex space-x-2 mb-4">
        <button onclick="toggleStaffView('productivity')" id="btn-productivity" class="px-3 py-1 text-sm bg-blue-600 text-white rounded">
            Productivity
        </button>
        <button onclick="toggleStaffView('efficiency')" id="btn-efficiency" class="px-3 py-1 text-sm bg-gray-300 text-gray-700 rounded">
            Efficiency
        </button>
        <button onclick="toggleStaffView('workload')" id="btn-workload" class="px-3 py-1 text-sm bg-gray-300 text-gray-700 rounded">
            Workload Distribution
        </button>
    </div>
    
    <div class="chart-container" style="height: 300px;">
        <canvas id="staffPerformanceChart"></canvas>
    </div>
    <div class="mt-4 p-4 bg-gradient-to-r from-cyan-50 to-sky-50 rounded-lg border border-cyan-200">
        <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
            <svg class="w-4 h-4 mr-2 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Performance Insights
        </h3>
        
        @php
            $staffMetrics = $staffPerformance['individual_metrics'];
            $topPerformers = $staffPerformance['top_performers'];
            $workloadByRole = $staffPerformance['workload_by_role'];
            
            // Calculate metrics
            $avgDocsProcessed = $staffMetrics->isNotEmpty() ? $staffMetrics->avg('documents_processed') : 0;
            $avgProcessingHours = $staffMetrics->isNotEmpty() ? $staffMetrics->avg('avg_processing_hours') : 0;
            $totalStaff = $staffMetrics->count();
            
            // Top performer
            $topStaff = $staffMetrics->sortByDesc('documents_processed')->first();
            
            // Fastest processor
            $fastestStaff = $topPerformers->sortBy('avg_processing_hours')->first();
            
            // Performance distribution
            $highPerformers = $staffMetrics->filter(fn($s) => $s->documents_processed > $avgDocsProcessed * 1.2)->count();
            $avgPerformers = $staffMetrics->filter(fn($s) => 
                $s->documents_processed >= $avgDocsProcessed * 0.8 && 
                $s->documents_processed <= $avgDocsProcessed * 1.2
            )->count();
            $lowPerformers = $staffMetrics->filter(fn($s) => $s->documents_processed < $avgDocsProcessed * 0.8)->count();
        @endphp
        
        <div class="space-y-2 text-sm text-gray-700">
            @if($totalStaff > 0)
            <p class="text-sm text-gray-700">
                The team consists of <strong>{{ $totalStaff }}</strong> active staff members processing an average of 
                <strong>{{ number_format($avgDocsProcessed, 1) }}</strong> documents each. 
                @if($topStaff)
                The top performer is <strong class="text-green-700">{{ $topStaff->first_name }} {{ $topStaff->last_name }}</strong> 
                ({{ $topStaff->role }}) with <strong>{{ $topStaff->documents_processed }}</strong> documents processed.
                @endif
                @if($fastestStaff)
                The fastest processor is <strong class="text-blue-700">{{ $fastestStaff->staff_name }}</strong> averaging 
                <strong>{{ $fastestStaff->avg_processing_hours }}</strong> hours per document.
                @endif
                Performance distribution shows <strong class="text-green-600">{{ $highPerformers }}</strong> high performers, 
                <strong class="text-blue-600">{{ $avgPerformers }}</strong> average performers, and 
                <strong class="text-orange-600">{{ $lowPerformers }}</strong> below average performers. 
                Team average processing time is <strong>{{ number_format($avgProcessingHours, 2) }}</strong> hours per document, 
                indicating 
                @if($avgProcessingHours < 3)
                    <span class="text-green-700 font-semibold">excellent efficiency</span>
                @elseif($avgProcessingHours < 5)
                    <span class="text-blue-700 font-semibold">good efficiency</span>
                @else
                    <span class="text-yellow-700 font-semibold">room for improvement</span>
                @endif
                .
            </p>
            
            @if($lowPerformers > $highPerformers && $lowPerformers > 0)
            <div class="mt-3 p-2 bg-yellow-100 border-l-4 border-yellow-500 rounded">
                <p class="text-xs text-yellow-800">
                    <strong>👥 HR Action:</strong> {{ $lowPerformers }} staff member(s) below average performance. 
                    Conduct performance reviews, provide training, and identify support needs.
                </p>
            </div>
            @elseif($highPerformers > $totalStaff * 0.3)
            <div class="mt-3 p-2 bg-green-100 border-l-4 border-green-500 rounded">
                <p class="text-xs text-green-800">
                    <strong>✅ Strong Team:</strong> {{ round(($highPerformers / $totalStaff) * 100) }}% of staff are high performers. 
                    Document best practices and share across the team.
                </p>
            </div>
            @endif
            @else
            <div class="text-center text-gray-500 py-2">
                No staff performance data available
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Bottleneck Chart -->
<div class="bg-white p-4 rounded-lg shadow mb-8">
    <h2 class="text-lg font-semibold mb-4">Workflow Bottlenecks by Role (Processing Time)</h2>
    <div class="chart-container" style="height: 300px;">
        <canvas id="bottleneckChart"></canvas>
    </div>
    <!-- Insights Panel -->
    <div class="mt-4 p-4 bg-gradient-to-r from-red-50 to-rose-50 rounded-lg border border-red-200">
        <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
            <svg class="w-4 h-4 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            Bottleneck Analysis
        </h3>
        
        @php
            $bottlenecks = $bottlenecks;
            
            if ($bottlenecks->isNotEmpty()) {
                $slowestRole = $bottlenecks->sortByDesc('avg_hours')->first();
                $fastestRole = $bottlenecks->sortBy('avg_hours')->first();
                
                // Calculate impact
                $totalAvgHours = $bottlenecks->sum('avg_hours');
                $avgHours = $bottlenecks->avg('avg_hours');
                
                // Critical bottlenecks (>4 hours)
                $criticalBottlenecks = $bottlenecks->filter(fn($b) => $b->avg_hours > 4);
            } else {
                $slowestRole = $fastestRole = null;
                $totalAvgHours = $avgHours = 0;
                $criticalBottlenecks = collect([]);
            }
        @endphp
        
        <div class="space-y-2 text-sm text-gray-700">
            @if($slowestRole)
            <p class="text-sm text-gray-700">
                The primary bottleneck is <strong class="text-red-700">{{ $slowestRole->role_name }}</strong> with an average processing time of 
                <strong>{{ number_format($slowestRole->avg_hours, 2) }}</strong> hours. 
                @if($fastestRole)
                There is a processing gap of <strong>{{ number_format($slowestRole->avg_hours - $fastestRole->avg_hours, 2) }}</strong> hours 
                between the slowest ({{ $slowestRole->role_name }}) and fastest ({{ $fastestRole->role_name }}) stages.
                @endif
                @if($criticalBottlenecks->count() > 0)
                <strong class="text-red-700">{{ $criticalBottlenecks->count() }} critical stage(s)</strong> exceed the 4-hour threshold and require 
                immediate optimization. 
                @elseif($bottlenecks->isNotEmpty())
                There are <span class="text-green-700 font-semibold">no critical bottlenecks</span>, with all stages operating within acceptable ranges.
                @endif
                The cumulative workflow time across all processing stages is <strong>{{ number_format($totalAvgHours, 2) }}</strong> hours, equivalent to 
                <strong>{{ number_format($totalAvgHours / 24, 1) }} days</strong>.
                @if($slowestRole && $slowestRole->avg_days_into_process > 5)
                A <span class="text-orange-700 font-semibold">late-stage delay</span> is noted in {{ $slowestRole->role_name }} at day 
                {{ number_format($slowestRole->avg_days_into_process, 1) }}, contributing to SLA violations.
                @endif
            </p>
            
            @if($criticalBottlenecks->count() > 0)
            <div class="mt-3 p-2 bg-red-100 border-l-4 border-red-500 rounded">
                <p class="text-xs text-red-800">
                    <strong>🚨 Urgent Action:</strong> 
                    @if($slowestRole)
                        Focus on {{ $slowestRole->role_name }} optimization: 
                        streamline approval process, add parallel workflows, or increase staffing at this stage.
                    @endif
                </p>
            </div>
            @elseif($slowestRole && $slowestRole->avg_hours > 3)
            <div class="mt-3 p-2 bg-yellow-100 border-l-4 border-yellow-500 rounded">
                <p class="text-xs text-yellow-800">
                    <strong>💡 Recommendation:</strong> 
                    {{ $slowestRole->role_name }} could be optimized further. 
                    Review process requirements and explore automation opportunities.
                </p>
            </div>
            @endif
            @else
            <div class="text-center text-gray-500 py-2">
                No bottleneck data available
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Insights -->
<div class="bg-white p-4 rounded-lg shadow mt-6">
    <h2 class="text-lg font-semibold mb-2">Insights & Highlights</h2>
    @if(!empty($insights))
    <ul class="list-disc list-inside text-gray-700" id="insights-list">
        @foreach($insights as $insight)
            <li>{!! $insight !!}</li>
        @endforeach
    </ul>
    @else
    <p class="text-gray-500">No insights available for the selected date range.</p>
    @endif
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<style>
    .chart-container {
        position: relative;
        width: 100%;
        min-height: 300px;
        max-height: 300px;
    }
    
    canvas {
        max-width: 100% !important;
        max-height: 100% !important;
        width: auto !important;
        height: auto !important;
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .pulse {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    /* Ensure proper chart responsiveness */
    @media (max-width: 768px) {
        .chart-container {
            height: 250px !important;
            min-height: 250px;
            max-height: 250px;
        }
    }
    
    /* Modal animations */
    #kpiModal.show {
        animation: modalFadeIn 0.3s ease-out;
    }
    
    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Add these styles to your dashboard */
    .budget-card {
        transition: transform 0.2s ease;
    }

    .budget-card:hover {
        transform: translateY(-2px);
    }

    @keyframes pulse-slow {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }

    .pulse-slow {
        animation: pulse-slow 2s ease-in-out infinite;
    }

    /* Status badge colors */
    .status-exceeded {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }

    .status-critical {
        background-color: #fed7aa;
        color: #9a3412;
        border: 1px solid #fb923c;
    }

    .status-warning {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fbbf24;
    }

    .status-healthy {
        background-color: #d1fae5;
        color: #065f46;
        border: 1px solid #34d399;
    }

    /* Progress bar colors */
    .progress-exceeded {
        background: linear-gradient(90deg, #ef4444, #dc2626);
    }

    .progress-critical {
        background: linear-gradient(90deg, #f97316, #ea580c);
    }

    .progress-warning {
        background: linear-gradient(90deg, #f59e0b, #d97706);
    }

    .progress-healthy {
        background: linear-gradient(90deg, #10b981, #059669);
    }
</style>
@endpush

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Pusher -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<script>
// Store current KPI data globally for modal access
let currentKPIData = {
    total: {{ $totalRequests }},
    pending: {{ $pendingRequests }},
    approved: {{ $approvedRequests }},
    rejected: {{ $rejectedRequests }},
    processing: {{ round($slaAnalysis['avg_working_days'], 1) }},
    requestsPerProgram: @json($requestsPerProgram->toArray()),
    monthlyRequests: @json($monthlyRequests->toArray()),
    slaAnalysis: @json($slaAnalysis)
};

// Safe division functions
function safeDivide(numerator, denominator, defaultValue = 0) {
    return denominator !== 0 ? numerator / denominator : defaultValue;
}

function safePercentage(part, whole, defaultValue = 0) {
    return whole !== 0 ? (part / whole) * 100 : defaultValue;
}

// KPI Modal Functions
function openKPIModal(type) {
    const modal = document.getElementById('kpiModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    
    // Set title based on type
    const titles = {
        total: 'Total Requests Analysis',
        pending: 'Pending Requests Details',
        approved: 'Approved Requests Analysis',
        rejected: 'Rejected Requests Analysis',
        processing: 'Processing Time Analysis'
    };
    
    modalTitle.textContent = titles[type] || 'Details';
    
    // Generate content based on type
    modalContent.innerHTML = generateKPIContent(type);
    
    // Show modal
    modal.classList.remove('hidden');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeKPIModal() {
    const modal = document.getElementById('kpiModal');
    modal.classList.add('hidden');
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';
}

function generateKPIContent(type) {
    const data = currentKPIData;
    
    // Helper function for safe calculations
    function safeDiv(num, den, def = 0) {
        return den !== 0 ? num / den : def;
    }
    
    switch(type) {
        case 'total':
            const total = data.total || 1;
            const pendingPct = safeDiv(data.pending, total) * 100;
            const approvedPct = safeDiv(data.approved, total) * 100;
            const rejectedPct = safeDiv(data.rejected, total) * 100;
            
            return `
                <div class="space-y-6">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-blue-900 mb-2">Summary</h4>
                        <p class="text-3xl font-bold text-blue-600">${data.total || 0}</p>
                        <p class="text-sm text-gray-600 mt-1">Total DV requests in the system</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white border-2 border-blue-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600">Pending</p>
                            <p class="text-2xl font-bold text-yellow-600">${data.pending || 0}</p>
                            <p class="text-xs text-gray-500">${pendingPct.toFixed(1)}% of total</p>
                        </div>
                        <div class="bg-white border-2 border-blue-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600">Approved</p>
                            <p class="text-2xl font-bold text-green-600">${data.approved || 0}</p>
                            <p class="text-xs text-gray-500">${approvedPct.toFixed(1)}% of total</p>
                        </div>
                        <div class="bg-white border-2 border-blue-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600">Rejected</p>
                            <p class="text-2xl font-bold text-red-600">${data.rejected || 0}</p>
                            <p class="text-xs text-gray-500">${rejectedPct.toFixed(1)}% of total</p>
                        </div>
                    </div>
                </div>
            `;
            
        case 'pending':
            const totalForPending = data.total || 1;
            const pendingPercentage = safeDiv(data.pending, totalForPending) * 100;
            
            return `
                <div class="space-y-6">
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-yellow-900 mb-2">Pending Requests</h4>
                        <p class="text-3xl font-bold text-yellow-600">${data.pending || 0}</p>
                        <p class="text-sm text-gray-600 mt-1">Requests awaiting processing</p>
                    </div>
                    
                    <div class="bg-white border-2 border-yellow-200 p-4 rounded-lg">
                        <h4 class="font-semibold mb-3">Status Distribution</h4>
                        <div class="space-y-3">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>Pending Rate</span>
                                    <span class="font-bold">${pendingPercentage.toFixed(1)}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="bg-yellow-500 h-3 rounded-full" style="width: ${pendingPercentage}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
        case 'approved':
            const totalForApproved = data.total || 1;
            const processedTotal = (data.approved || 0) + (data.rejected || 0);
            const approvedPercentage = safeDiv(data.approved, totalForApproved) * 100;
            const successRate = safeDiv(data.approved, processedTotal) * 100;
            
            return `
                <div class="space-y-6">
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-green-900 mb-2">Approved Requests</h4>
                        <p class="text-3xl font-bold text-green-600">${data.approved || 0}</p>
                        <p class="text-sm text-gray-600 mt-1">Successfully processed and released</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white border-2 border-green-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-2">Approval Rate</p>
                            <p class="text-3xl font-bold text-green-600">${approvedPercentage.toFixed(1)}%</p>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: ${approvedPercentage}%"></div>
                            </div>
                        </div>
                        <div class="bg-white border-2 border-green-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-2">Success Rate</p>
                            <p class="text-3xl font-bold text-green-600">${successRate.toFixed(1)}%</p>
                            <p class="text-xs text-gray-500 mt-1">Approved vs Total Processed</p>
                        </div>
                    </div>
                </div>
            `;
            
        case 'rejected':
            const totalForRejected = data.total || 1;
            const rejectedPercentage = safeDiv(data.rejected, totalForRejected) * 100;
            
            return `
                <div class="space-y-6">
                    <div class="bg-red-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-red-900 mb-2">Rejected Requests</h4>
                        <p class="text-3xl font-bold text-red-600">${data.rejected || 0}</p>
                        <p class="text-sm text-gray-600 mt-1">Requests that did not meet requirements</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white border-2 border-red-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-2">Rejection Rate</p>
                            <p class="text-3xl font-bold text-red-600">${rejectedPercentage.toFixed(1)}%</p>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-red-500 h-2 rounded-full" style="width: ${rejectedPercentage}%"></div>
                            </div>
                        </div>
                        <div class="bg-white border-2 border-red-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-2">Impact</p>
                            <p class="text-xl font-bold text-gray-700">${(data.approved || 0) + (data.rejected || 0)}</p>
                            <p class="text-xs text-gray-500 mt-1">Total Processed (Approved + Rejected)</p>
                        </div>
                    </div>
                </div>
            `;
            
        case 'processing':
            const sla = data.slaAnalysis;
            const completedTotal = sla.total_completed_requests || 1;
            const withinSla = sla.within_sla || 0;
            const violations = sla.sla_violations || 0;
            const complianceRate = safePercentage(withinSla, completedTotal);
            const violationRate = safePercentage(violations, completedTotal);
            
            return `
                <div class="space-y-6">
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-purple-900 mb-2">Average Processing Time</h4>
                        <p class="text-3xl font-bold text-purple-600">${data.processing || 0} days</p>
                        <p class="text-sm text-gray-600 mt-1">Average working days to complete</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white border-2 border-purple-200 p-4 rounded-lg text-center">
                            <p class="text-sm text-gray-600">Target</p>
                            <p class="text-2xl font-bold text-blue-600">≤10 days</p>
                            <p class="text-xs text-gray-500 mt-1">SLA Standard</p>
                        </div>
                        <div class="bg-white border-2 border-purple-200 p-4 rounded-lg text-center">
                            <p class="text-sm text-gray-600">Within SLA</p>
                            <p class="text-2xl font-bold text-green-600">${withinSla}</p>
                            <p class="text-xs text-gray-500 mt-1">${complianceRate.toFixed(1)}% compliance</p>
                        </div>
                        <div class="bg-white border-2 border-purple-200 p-4 rounded-lg text-center">
                            <p class="text-sm text-gray-600">Exceeded SLA</p>
                            <p class="text-2xl font-bold text-red-600">${violations}</p>
                            <p class="text-xs text-gray-500 mt-1">${violationRate.toFixed(1)}% violations</p>
                        </div>
                    </div>
                </div>
            `;
            
        default:
            return '<p>No data available</p>';
    }
}

function exportKPIData() {
    const type = document.getElementById('modalTitle').textContent.split(' ')[0].toLowerCase();
    let csvContent = "data:text/csv;charset=utf-8,";
    
    csvContent += `KPI Report - ${document.getElementById('modalTitle').textContent}\n`;
    csvContent += `Generated: ${new Date().toLocaleString()}\n\n`;
    csvContent += `Metric,Value\n`;
    csvContent += `Total Requests,${currentKPIData.total}\n`;
    csvContent += `Pending,${currentKPIData.pending}\n`;
    csvContent += `Approved,${currentKPIData.approved}\n`;
    csvContent += `Rejected,${currentKPIData.rejected}\n`;
    csvContent += `Avg Processing Time,${currentKPIData.processing} days\n`;
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `kpi_report_${type}_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Data exported successfully', 'success');
}

// Close modal when clicking outside
document.getElementById('kpiModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeKPIModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeKPIModal();
    }
});

// Real-time Dashboard Configuration
const DashboardConfig = {
    pusherKey: '{{ env('PUSHER_APP_KEY') }}',
    pusherCluster: '{{ env('PUSHER_APP_CLUSTER') }}',
    refreshInterval: 30000,
    dateFrom: '{{ request('date_from', '') }}',
    dateTo: '{{ request('date_to', '') }}'
};

let charts = {};
let staffCharts = {
    productivity: null,
    efficiency: null,
    workload: null
};

// Initialize Dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

function initializeDashboard() {
    initializeCharts();
    initializeRealTimeUpdates();
}

function initializeCharts() {
    initRequestsPerProgramChart();
    initBudgetPerProgramChart();
    initDocumentsPerRoleChart();
    initMonthlyRequestsChart();
    initBottleneckChart();
    initStaffPerformanceChart();

    window.addEventListener('resize', function() {
        Object.values(charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
        if (staffCharts.productivity && typeof staffCharts.productivity.resize === 'function') {
            staffCharts.productivity.resize();
        }
    });
}

function initRequestsPerProgramChart() {
    const ctx = document.getElementById('requestsPerProgram');
    if (!ctx) return;

    const labels = @json($requestsPerProgram->keys());
    const data = @json($requestsPerProgram->values());

    charts.requestsPerProgram = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Requests',
                data: data,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function initBudgetPerProgramChart() {
    const ctx = document.getElementById('budgetPerProgram');
    if (!ctx) return;
    
    const labels = @json($budgetPerProgram->keys());
    const data = @json($budgetPerProgram->values());
    
    charts.budgetPerProgram = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Budget (₱)',
                data: data,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `₱${context.parsed.y.toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) return '₱' + (value / 1000000).toFixed(1) + 'M';
                            else if (value >= 1000) return '₱' + (value / 1000).toFixed(1) + 'K';
                            return '₱' + value;
                        }
                    }
                }
            }
        }
    });
}

function initDocumentsPerRoleChart() {
    const ctx = document.getElementById('documentsPerRole');
    if (!ctx) return;
    
    const labels = @json($documentsPerRole->keys());
    const data = @json($documentsPerRole->values());
    
    charts.documentsPerRole = new Chart(ctx.getContext('2d'), {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

function initMonthlyRequestsChart() {
    const ctx = document.getElementById('monthlyRequestsChart');
    if (!ctx) return;
    
    const labels = @json($monthlyRequests->keys());
    const data = @json($monthlyRequests->values());
    
    charts.monthlyRequests = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Requests',
                data: data,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
}

function initBottleneckChart() {
    const ctx = document.getElementById('bottleneckChart');
    if (!ctx) return;
    
    const bottlenecks = @json($bottlenecks);
    
    if (bottlenecks.length > 0) {
        const labels = bottlenecks.map(b => b.role_name || b.staff_name);
        const data = bottlenecks.map(b => b.avg_hours || 0);
        
        charts.bottleneck = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Avg Processing Time (hours)',
                    data: data,
                    backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
}

function initStaffPerformanceChart() {
    const ctx = document.getElementById('staffPerformanceChart');
    if (!ctx) return;
    
    const staffData = @json($staffPerformance['individual_metrics']);
    const workloadData = @json($staffPerformance['workload_by_role']);
    
    if (staffData.length > 0) {
        updateStaffChartByView('productivity', staffData, workloadData);
    }
}

function updateStaffChartByView(view, staffData, workloadData) {
    const ctx = document.getElementById('staffPerformanceChart');
    if (!ctx) return;
    
    if (staffCharts.productivity) {
        staffCharts.productivity.destroy();
    }
    
    if (view === 'productivity') {
        const labels = staffData.slice(0, 10).map(s => `${s.first_name} ${s.last_name}`);
        const documentsData = staffData.slice(0, 10).map(s => s.documents_processed || 0);
        
        staffCharts.productivity = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Documents Processed',
                    data: documentsData,
                    backgroundColor: '#3b82f6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Top 10 Staff by Productivity' }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
        
    } else if (view === 'efficiency') {
        const efficientStaff = staffData
            .filter(s => (s.avg_processing_hours || 0) > 0)
            .sort((a, b) => (a.avg_processing_hours || 0) - (b.avg_processing_hours || 0))
            .slice(0, 10);
            
        const labels = efficientStaff.map(s => `${s.first_name} ${s.last_name}`);
        const efficiencyData = efficientStaff.map(s => s.avg_processing_hours || 0);
        
        staffCharts.productivity = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Avg Processing Time (hours)',
                    data: efficiencyData,
                    backgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Top 10 Most Efficient Staff' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
    } else if (view === 'workload') {
        const labels = workloadData.map(w => w.role || 'Unknown');
        const workloadCounts = workloadData.map(w => w.total_documents || 0);
        
        staffCharts.productivity = new Chart(ctx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: workloadCounts,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Workload Distribution by Role' }
                }
            }
        });
    }
}

function toggleStaffView(view) {
    document.getElementById('btn-productivity').className = 
        view === 'productivity' ? 'px-3 py-1 text-sm bg-blue-600 text-white rounded' : 'px-3 py-1 text-sm bg-gray-300 text-gray-700 rounded';
    document.getElementById('btn-efficiency').className = 
        view === 'efficiency' ? 'px-3 py-1 text-sm bg-blue-600 text-white rounded' : 'px-3 py-1 text-sm bg-gray-300 text-gray-700 rounded';
    document.getElementById('btn-workload').className = 
        view === 'workload' ? 'px-3 py-1 text-sm bg-blue-600 text-white rounded' : 'px-3 py-1 text-sm bg-gray-300 text-gray-700 rounded';
    
    const staffData = @json($staffPerformance['individual_metrics']);
    const workloadData = @json($staffPerformance['workload_by_role']);
    
    updateStaffChartByView(view, staffData, workloadData);
}

window.toggleStaffView = toggleStaffView;

function initializeRealTimeUpdates() {
    try {
        if (!DashboardConfig.pusherKey || DashboardConfig.pusherKey === '') {
            setInterval(fetchLatestData, DashboardConfig.refreshInterval);
            return;
        }

        const pusher = new Pusher(DashboardConfig.pusherKey, {
            cluster: DashboardConfig.pusherCluster,
            encrypted: true
        });

        const channel = pusher.subscribe('dashboard');
        channel.bind('dashboard.updated', function(data) {
            updateDashboard(data.payload || data);
            showNotification('Dashboard updated in real-time', 'success');
        });

        setInterval(fetchLatestData, DashboardConfig.refreshInterval);
    } catch (error) {
        console.error('Failed to initialize real-time updates:', error);
        setInterval(fetchLatestData, DashboardConfig.refreshInterval);
    }
}

async function fetchLatestData() {
    try {
        const dateFrom = document.getElementById('date_from')?.value || '';
        const dateTo = document.getElementById('date_to')?.value || '';
        
        const params = new URLSearchParams();
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        
        const url = `{{ route('admin.dashboard.data') }}${params.toString() ? '?' + params.toString() : ''}`;
        const response = await fetch(url);
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const result = await response.json();
        
        if (result.success) {
            updateDashboard(result.data);
        }
    } catch (error) {
        console.error('Failed to fetch latest data:', error);
    }
}
function updateBudgetThresholds(data) {
    try {
        console.log('Updating budget thresholds...');
        
        // Check if we have budget data
        const budgetData = data.budgetPerProgram || {};
        const budgetInsights = data.chartInsights?.budgetPerProgram || {};
        
        if (Object.keys(budgetData).length === 0) {
            console.log('No budget data available');
            return;
        }
        
        // Get the budget thresholds container
        const container = document.getElementById('budgetThresholdsContainer');
        if (!container) {
            console.log('Budget thresholds container not found');
            return;
        }
        
        // Clear existing content
        container.innerHTML = '';
        
        // Calculate thresholds based on data
        const totalBudget = budgetInsights.totalBudget || 0;
        const costPerRequest = budgetInsights.costPerRequest || {};
        
        if (totalBudget === 0) {
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    No budget data available for the selected period
                </div>
            `;
            return;
        }
        
        // Create budget threshold indicators
        let html = `
            <div class="bg-white p-4 rounded-lg shadow">
                <h5 class="font-semibold text-gray-700 mb-3">
                    <i class="fas fa-money-bill-wave mr-2"></i>
                    Budget Utilization Analysis
                </h5>
                
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Total Budget Allocated</span>
                            <span class="font-semibold">₱${formatNumber(totalBudget)}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        `;
        
        // Add program-specific budget info
        Object.entries(budgetData).slice(0, 3).forEach(([program, amount]) => {
            const percentage = totalBudget > 0 ? Math.round((amount / totalBudget) * 100) : 0;
            const cost = costPerRequest[program] || 0;
            
            // Determine status color
            let statusColor = 'bg-green-500';
            if (percentage > 40) {
                statusColor = 'bg-yellow-500';
            }
            if (percentage > 60) {
                statusColor = 'bg-red-500';
            }
            
            html += `
                <div class="border rounded p-3">
                    <div class="flex justify-between items-start mb-2">
                        <span class="font-medium text-sm truncate">${program}</span>
                        <span class="text-xs px-2 py-1 ${statusColor.replace('bg-', 'text-')} ${statusColor.replace('bg-', 'bg-')}/10 rounded">${percentage}%</span>
                    </div>
                    <div class="text-xl font-bold">₱${formatNumber(amount)}</div>
                    <div class="text-xs text-gray-500 mt-1">₱${formatNumber(cost)} per request</div>
                    <div class="w-full bg-gray-200 rounded-full h-1 mt-2">
                        <div class="${statusColor} h-1 rounded-full" style="width: ${percentage}%"></div>
                    </div>
                </div>
            `;
        });
        
        html += `
                    </div>
                    
                    <div class="mt-4 pt-4 border-t">
                        <h6 class="text-sm font-medium text-gray-700 mb-2">Budget Alerts</h6>
                        <div class="space-y-2">
        `;
        
        // Add budget alerts
        const alerts = generateBudgetAlerts(budgetData, totalBudget, costPerRequest);
        alerts.forEach(alert => {
            html += `
                <div class="flex items-start text-sm">
                    <i class="${alert.icon} mr-2 mt-0.5 ${alert.color}"></i>
                    <span class="${alert.textColor}">${alert.message}</span>
                </div>
            `;
        });
        
        html += `
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
        
        console.log('Budget thresholds updated successfully');
        
    } catch (error) {
        console.error('Error updating budget thresholds:', error);
    }
}

// Helper function to generate budget alerts
function generateBudgetAlerts(budgetData, totalBudget, costPerRequest) {
    const alerts = [];
    
    // Check for programs using more than 40% of budget
    Object.entries(budgetData).forEach(([program, amount]) => {
        const percentage = totalBudget > 0 ? (amount / totalBudget) * 100 : 0;
        
        if (percentage > 40 && percentage <= 60) {
            alerts.push({
                message: `${program} is using ${Math.round(percentage)}% of total budget`,
                icon: 'fas fa-exclamation-triangle',
                color: 'text-yellow-500',
                textColor: 'text-yellow-700'
            });
        } else if (percentage > 60) {
            alerts.push({
                message: `${program} is using ${Math.round(percentage)}% of total budget - Consider reviewing`,
                icon: 'fas fa-exclamation-circle',
                color: 'text-red-500',
                textColor: 'text-red-700'
            });
        }
    });
    
    // Check for high cost per request
    Object.entries(costPerRequest).forEach(([program, cost]) => {
        if (cost > 10000) { // Adjust threshold as needed
            alerts.push({
                message: `${program} has high cost per request (₱${formatNumber(cost)})`,
                icon: 'fas fa-chart-line',
                color: 'text-orange-500',
                textColor: 'text-orange-700'
            });
        }
    });
    
    // Add general budget health check
    if (totalBudget > 0) {
        const programCount = Object.keys(budgetData).length;
        const avgPercentage = 100 / programCount;
        const unbalancedPrograms = Object.entries(budgetData).filter(([, amount]) => {
            const percentage = (amount / totalBudget) * 100;
            return Math.abs(percentage - avgPercentage) > 20;
        }).length;
        
        if (unbalancedPrograms > programCount / 2) {
            alerts.push({
                message: 'Budget allocation is significantly unbalanced across programs',
                icon: 'fas fa-balance-scale',
                color: 'text-purple-500',
                textColor: 'text-purple-700'
            });
        }
    }
    
    return alerts;
}

// Helper function to format numbers
function formatNumber(num) {
    if (typeof num !== 'number') {
        num = parseFloat(num) || 0;
    }
    
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
function updateDashboard(data) {
    if (!data) return;
    
    // Update global KPI data with safe values
    currentKPIData = {
        total: data.totalRequests || 0,
        pending: data.pendingRequests || 0,
        approved: data.approvedRequests || 0,
        rejected: data.rejectedRequests || 0,
        processing: data.averageProcessingTime || 0,
        requestsPerProgram: data.requestsPerProgram || {},
        monthlyRequests: data.monthlyRequests || {},
        slaAnalysis: data.slaAnalysis || {
            'total_completed_requests': 0,
            'within_sla': 0,
            'sla_violations': 0,
            'violation_rate_percent': 0,
            'avg_working_days': 0,
            'worst_performer': 0
        }
    };
    
    safelyAnimateValue('kpi-total', currentKPIData.total);
    safelyAnimateValue('kpi-pending', currentKPIData.pending);
    safelyAnimateValue('kpi-approved', currentKPIData.approved);
    safelyAnimateValue('kpi-rejected', currentKPIData.rejected);

    if (data.slaAnalysis) {
        updateSLACompliance(data.slaAnalysis);
    }

    try {
        if (charts.requestsPerProgram && data.requestsPerProgram) {
            updateChartData(charts.requestsPerProgram, 
                Object.keys(data.requestsPerProgram), 
                Object.values(data.requestsPerProgram)
            );
        }

        if (charts.budgetPerProgram && data.budgetPerProgram) {
            updateChartData(charts.budgetPerProgram, 
                Object.keys(data.budgetPerProgram), 
                Object.values(data.budgetPerProgram)
            );
        }

        if (charts.documentsPerRole && data.documentsPerRole) {
            updateChartData(charts.documentsPerRole, 
                Object.keys(data.documentsPerRole), 
                Object.values(data.documentsPerRole)
            );
        }

        if (charts.monthlyRequests && data.monthlyRequests) {
            updateChartData(charts.monthlyRequests, 
                Object.keys(data.monthlyRequests), 
                Object.values(data.monthlyRequests)
            );
        }

        updateInsights(data.insights || []);
    } catch (error) {
        console.error('Error updating charts:', error);
    }
    
    if (data.budgetAnalysis) {
        updateBudgetThresholds(data.budgetAnalysis);
    }
}

function safelyAnimateValue(elementId, newValue) {
    const element = document.getElementById(elementId);
    if (!element) return;
    animateValue(elementId, newValue);
}

function animateValue(elementId, newValue) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const currentValue = parseInt(element.textContent.replace(/,/g, '')) || 0;
    const duration = 1000;
    const startTime = performance.now();

    function updateValue(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const value = Math.floor(currentValue + (newValue - currentValue) * easeOut);
        element.textContent = value.toLocaleString();

        if (progress < 1) {
            requestAnimationFrame(updateValue);
        } else {
            element.textContent = newValue.toLocaleString();
        }
    }

    requestAnimationFrame(updateValue);
}

function updateSLACompliance(slaData) {
    if (!slaData || !slaData.total_completed_requests) return;
    
    const onTimeRate = 100 - (slaData.violation_rate_percent || 0);
    
    safeUpdateElement('sla-total', slaData.total_completed_requests);
    safeUpdateElement('sla-within', slaData.within_sla);
    safeUpdateElement('sla-violations', slaData.sla_violations);
    safeUpdateElement('sla-avg-days', `${parseFloat(slaData.avg_working_days).toFixed(1)} days`);
    safeUpdateElement('sla-compliance-rate-text', `${onTimeRate.toFixed(1)}%`);
    
    const progressBar = document.getElementById('sla-progress-bar');
    if (progressBar) {
        progressBar.style.width = `${onTimeRate}%`;
        const bgColor = onTimeRate >= 90 ? '#10b981' : (onTimeRate >= 75 ? '#3b82f6' : '#ef4444');
        progressBar.style.backgroundColor = bgColor;
    }
}

function safeUpdateElement(id, value, isHTML = false) {
    const element = document.getElementById(id);
    if (element) {
        if (isHTML) {
            element.innerHTML = value;
        } else {
            element.textContent = value;
        }
    }
}

function updateChartData(chart, labels, data) {
    if (chart && labels && data) {
        try {
            chart.data.labels = labels;
            chart.data.datasets[0].data = data;
            chart.update('none');
        } catch (error) {
            console.error('Error updating chart:', error);
        }
    }
}

function updateInsights(insights) {
    const insightList = document.getElementById('insights-list');
    if (insightList && insights) {
        insightList.innerHTML = '';
        insights.forEach(text => {
            const li = document.createElement('li');
            li.innerHTML = text;
            li.className = 'fade-in';
            insightList.appendChild(li);
        });
    }
}

function showNotification(message, type = 'info') {
    const existingNotifications = document.querySelectorAll('.dashboard-notification');
    existingNotifications.forEach(el => el.remove());
    
    const notification = document.createElement('div');
    const backgroundColor = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
        warning: 'bg-yellow-500'
    }[type] || 'bg-blue-500';
    
    notification.className = `dashboard-notification fixed top-20 right-4 z-50 p-4 rounded-lg shadow-lg text-white max-w-sm ${backgroundColor}`;
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <span class="flex-1">${message}</span>
            <button onclick="this.closest('.dashboard-notification').remove()" class="ml-4 text-white hover:text-gray-200">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }
    }, 3000);
}

async function manualRefresh() {
    try {
        showNotification('Refreshing dashboard data...', 'info');
        
        const dateFrom = document.getElementById('date_from')?.value || '';
        const dateTo = document.getElementById('date_to')?.value || '';
        
        const params = new URLSearchParams();
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        
        const url = `{{ route('admin.dashboard.data') }}${params.toString() ? '?' + params.toString() : ''}`;
        const response = await fetch(url);
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const result = await response.json();
        
        if (result.success) {
            updateDashboard(result.data);
            showNotification('Dashboard refreshed successfully', 'success');
        }
    } catch (error) {
        console.error('Manual refresh failed:', error);
        showNotification('Refresh failed. Please try again.', 'error');
    }
}

window.manualRefresh = manualRefresh;
window.openKPIModal = openKPIModal;
window.closeKPIModal = closeKPIModal;
window.exportKPIData = exportKPIData;
</script>
