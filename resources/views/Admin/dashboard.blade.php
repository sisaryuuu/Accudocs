@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
@php
    // Add this with your other initialized variables at the top of the file
    $exceededCount = App\Models\DvRequest::whereIn('status', ['submitted', 'IMSD_received', 'IMSD_in_process'])
        ->where('created_at', '<=', now()->subDays(10))
        ->count();
@endphp
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
    $pendingBottlenecks = $pendingBottlenecks ?? collect([]);
    $pendingByDepartment = $pendingByDepartment ?? collect([]);
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
                
                <!-- Quick Filter Dropdown -->
                <div class="relative">
                    <button type="button" 
                            id="quickFilterDropdownBtn" 
                            class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-50 flex items-center justify-center space-x-2 whitespace-nowrap w-full md:w-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                        <span>Quick Filters</span>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div id="quickFilterDropdown" 
                         class="hidden absolute z-10 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg w-48">
                        <div class="p-2">
                            <div class="text-xs text-gray-500 px-3 py-2 uppercase font-semibold">Time Periods</div>
                            <a href="{{ route('admin.dashboard.preset', 'today') }}" 
                               class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Today
                            </a>
                            <a href="{{ route('admin.dashboard.preset', 'this_week') }}" 
                               class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                This Week
                            </a>
                            <a href="{{ route('admin.dashboard.preset', 'this_month') }}" 
                               class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                This Month
                            </a>
                            <div class="border-t my-1"></div>
                            <div class="text-xs text-gray-500 px-3 py-2 uppercase font-semibold">Previous Periods</div>
                            <a href="{{ route('admin.dashboard.preset', 'yesterday') }}" 
                               class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Yesterday
                            </a>
                            <a href="{{ route('admin.dashboard.preset', 'last_week') }}" 
                               class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Last Week
                            </a>
                            <a href="{{ route('admin.dashboard.preset', 'last_month') }}" 
                               class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Last Month
                            </a>
                            <a href="{{ route('admin.dashboard.preset', 'last_3_months') }}" 
                               class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Last 3 Months
                            </a>
                            <div class="border-t my-1"></div>
                            <div class="text-xs text-gray-500 px-3 py-2 uppercase font-semibold">Custom Range</div>
                            <button type="button" onclick="showCustomRange()" 
                                    class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded w-full">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Custom Range
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
            
        <!-- Manual Refresh Button -->
        <button onclick="manualRefresh()" class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 flex items-center justify-center space-x-2 whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>Refresh</span>
        </button>
    </div>
</div>
<!-- At the top of your dashboard, after the filter display -->
@if(isset($debug) && $debug['date_range_applied'] && $totalRequests == 0)
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
            <h3 class="font-semibold text-yellow-800">No Data Found</h3>
            <p class="text-yellow-700 text-sm">
                No requests found for the selected date range ({{ $dateRange['from_formatted'] }} to {{ $dateRange['to_formatted'] }}).
                Try selecting a different date range or view all requests.
            </p>
        </div>
    </div>
</div>
@endif
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
<!-- KPI Cards - Enhanced HCI Design -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    <!-- Total Requests Card -->
    <div onclick="openKPIModal('total')" 
         class="group relative bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 cursor-pointer overflow-hidden border-l-4 border-blue-500 hover:scale-102 transform">
        <!-- Animated gradient background on hover -->
        <div class="absolute inset-0 bg-gradient-to-r from-blue-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        
        <div class="relative p-5">
            <!-- Header with icon and tooltip -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <div class="p-2 bg-blue-100 rounded-lg group-hover:bg-blue-200 transition-colors">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600">Total Requests</h3>
                </div>
                
                <!-- Tooltip trigger -->
                <div class="relative" x-data="{ showTooltip: false }">
                    <button @mouseenter="showTooltip = true" @mouseleave="showTooltip = false" 
                            class="text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    <div x-show="showTooltip" x-cloak 
                         class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap z-50">
                        All DV requests in the system
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                    </div>
                </div>
            </div>
            
            <!-- Main number with animation -->
            <div class="flex items-baseline justify-between">
                <div>
                    <p class="text-3xl font-bold text-gray-800 group-hover:text-blue-600 transition-colors" id="kpi-total">{{ $totalRequests }}</p>
                    <p class="text-xs text-gray-500 mt-1 flex items-center">
                        <span class="inline-block w-2 h-2 rounded-full bg-blue-500 mr-1"></span>
                        All time
                    </p>
                </div>
                
                <!-- Sparkline/mini trend indicator -->
                @php
                    $totalChange = isset($trends['total']) ? $trends['total'] : 0;
                @endphp
                <div class="flex items-center {{ $totalChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span class="text-xs font-medium">{{ abs($totalChange) }}%</span>
                </div>
            </div>
            
            <!-- Progress bar showing completion rate -->
            @php
                $totalForBar = max($totalRequests, 1);
                $processedPercent = min(100, round((($approvedRequests + $rejectedRequests) / $totalForBar) * 100));
            @endphp
            <div class="mt-3">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-500">Processed</span>
                    <span class="font-medium text-gray-700">{{ $processedPercent }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-blue-500 h-1.5 rounded-full transition-all duration-500 group-hover:bg-blue-600" 
                         style="width: {{ $processedPercent }}%"></div>
                </div>
            </div>
            
            <!-- Quick stats footer -->
            <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between text-xs">
                <span class="text-gray-500">Pending: <span class="font-semibold text-yellow-600">{{ $pendingRequests }}</span></span>
                <span class="text-gray-500">Approved: <span class="font-semibold text-green-600">{{ $approvedRequests }}</span></span>
            </div>
        </div>
    </div>

    <!-- Pending Requests Card -->
    <div onclick="openKPIModal('pending')" 
         class="group relative bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 cursor-pointer overflow-hidden border-l-4 border-yellow-500 hover:scale-102 transform">
        
        <!-- Urgent indicator if there are pending items beyond threshold -->
        @if($pendingRequests > 0 && isset($pendingByRole) && collect($pendingByRole)->sum('exceeded_sla_count') > 0)
        <div class="absolute -top-1 -right-1">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
            </span>
        </div>
        @endif
        
        <div class="relative p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <div class="p-2 bg-yellow-100 rounded-lg group-hover:bg-yellow-200 transition-colors">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600">Pending</h3>
                </div>
                
                <div class="relative" x-data="{ showTooltip: false }">
                    <button @mouseenter="showTooltip = true" @mouseleave="showTooltip = false" 
                            class="text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"></path>
                        </svg>
                    </button>
                    <div x-show="showTooltip" x-cloak 
                         class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap z-50">
                        Requests awaiting processing
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-baseline justify-between">
                <div>
                    <p class="text-3xl font-bold text-gray-800 group-hover:text-yellow-600 transition-colors" id="kpi-pending">{{ $pendingRequests }}</p>
                    @php
                        $pendingPercent = $totalRequests > 0 ? round(($pendingRequests / $totalRequests) * 100, 1) : 0;
                    @endphp
                    <p class="text-xs text-gray-500 mt-1">{{ $pendingPercent }}% of total</p>
                </div>
                
                <!-- SLA warning indicator -->
                @if(isset($pendingByRole) && collect($pendingByRole)->sum('exceeded_sla_count') > 0)
                <div class="flex items-center bg-red-100 px-2 py-1 rounded-full">
                    <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse mr-1"></span>
                    <span class="text-xs font-medium text-red-700">{{ collect($pendingByRole)->sum('exceeded_sla_count') }} overdue</span>
                </div>
                @endif
            </div>
            
            <!-- Role breakdown mini-bars -->
            @if(isset($pendingByRole) && collect($pendingByRole)->count() > 0)
            <div class="mt-3 space-y-1.5">
                @foreach(collect($pendingByRole)->sortByDesc('total_pending')->take(3) as $role)
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-600 truncate max-w-[80px]">{{ str_replace('_', ' ', $role->role) }}</span>
                    <div class="flex items-center space-x-2 flex-1 ml-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full" 
                                 style="width: {{ min(100, $pendingRequests > 0 ? ($role->total_pending / $pendingRequests) * 100 : 0) }}%; 
                                        background-color: {{ $role->color ?? '#f59e0b' }}"></div>
                        </div>
                        <span class="font-medium text-gray-700">{{ $role->total_pending }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
            
            <!-- Age indicator -->
            @php
                $maxWait = isset($pendingByRole) ? collect($pendingByRole)->max('avg_wait_days') : 0;
            @endphp
            @if($maxWait > 0)
            <div class="mt-3 pt-2 border-t border-gray-100 flex items-center justify-between text-xs">
                <span class="text-gray-500">Longest waiting:</span>
                <span class="font-semibold {{ $maxWait > 10 ? 'text-red-600' : 'text-yellow-600' }}">
                    {{ round($maxWait, 1) }} days
                </span>
            </div>
            @endif
        </div>
    </div>

    <!-- Approved Requests Card -->
    <div onclick="openKPIModal('approved')" 
         class="group relative bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 cursor-pointer overflow-hidden border-l-4 border-green-500 hover:scale-102 transform">
        
        <div class="relative p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <div class="p-2 bg-green-100 rounded-lg group-hover:bg-green-200 transition-colors">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600">Approved</h3>
                </div>
                
                <div class="relative" x-data="{ showTooltip: false }">
                    <button @mouseenter="showTooltip = true" @mouseleave="showTooltip = false" 
                            class="text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"></path>
                        </svg>
                    </button>
                    <div x-show="showTooltip" x-cloak 
                         class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap z-50">
                        Successfully processed and released
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-baseline justify-between">
                <div>
                    <p class="text-3xl font-bold text-gray-800 group-hover:text-green-600 transition-colors" id="kpi-approved">{{ $approvedRequests }}</p>
                    @php
                        $approvalRate = $totalRequests > 0 ? round(($approvedRequests / $totalRequests) * 100, 1) : 0;
                    @endphp
                    <p class="text-xs text-gray-500 mt-1">{{ $approvalRate }}% approval rate</p>
                </div>
                
                <!-- Success rate indicator -->
                <div class="flex items-center bg-green-100 px-2 py-1 rounded-full">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                    <span class="text-xs font-medium text-green-700">{{ $approvalRate }}%</span>
                </div>
            </div>
            
            <!-- Amount stats if available -->
            @if(isset($budgetPerProgram) && $budgetPerProgram->sum() > 0)
            <div class="mt-2 text-xs text-gray-600 flex items-center justify-between">
                <span>Total amount:</span>
                <span class="font-semibold">₱{{ number_format($budgetPerProgram->sum(), 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Rejected Requests Card -->
    <div onclick="openKPIModal('rejected')" 
         class="group relative bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 cursor-pointer overflow-hidden border-l-4 border-red-500 hover:scale-102 transform">
        
        <div class="relative p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <div class="p-2 bg-red-100 rounded-lg group-hover:bg-red-200 transition-colors">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600">Rejected</h3>
                </div>
                
                <div class="relative" x-data="{ showTooltip: false }">
                    <button @mouseenter="showTooltip = true" @mouseleave="showTooltip = false" 
                            class="text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"></path>
                        </svg>
                    </button>
                    <div x-show="showTooltip" x-cloak 
                         class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap z-50">
                        Requests that did not meet requirements
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-baseline justify-between">
                <div>
                    <p class="text-3xl font-bold text-gray-800 group-hover:text-red-600 transition-colors" id="kpi-rejected">{{ $rejectedRequests }}</p>
                    @php
                        $rejectionRate = $totalRequests > 0 ? round(($rejectedRequests / $totalRequests) * 100, 1) : 0;
                    @endphp
                    <p class="text-xs text-gray-500 mt-1">{{ $rejectionRate }}% rejection rate</p>
                </div>
                
            </div>
            
            <!-- Common reasons if available (you'd need to add this data) -->
            @if($rejectedRequests > 0)
            <div class="mt-3 bg-red-50 rounded-lg p-2">
                <p class="text-xs font-medium text-red-800 mb-1">Common reasons:</p>
                <div class="flex flex-wrap gap-1">
                    <span class="text-[10px] bg-white text-red-700 px-1.5 py-0.5 rounded-full">Incomplete docs</span>
                    <span class="text-[10px] bg-white text-red-700 px-1.5 py-0.5 rounded-full">Budget exceeded</span>
                    <span class="text-[10px] bg-white text-red-700 px-1.5 py-0.5 rounded-full">Invalid info</span>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Average Processing Time Card -->
    <div onclick="openKPIModal('processing')" 
         class="group relative bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 cursor-pointer overflow-hidden border-l-4 border-purple-500 hover:scale-102 transform">
        
        <div class="relative p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <div class="p-2 bg-purple-100 rounded-lg group-hover:bg-purple-200 transition-colors">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600">Processing Time</h3>
                </div>
                
                <div class="relative" x-data="{ showTooltip: false }">
                    <button @mouseenter="showTooltip = true" @mouseleave="showTooltip = false" 
                            class="text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"></path>
                        </svg>
                    </button>
                    <div x-show="showTooltip" x-cloak 
                         class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap z-50">
                        Average working days from receipt to release
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                    </div>
                </div>
            </div>
            
            @php
                $avgDays = round($slaAnalysis['avg_working_days'] ?? 0, 1);
                $slaTarget = 10;
                $performancePercent = min(100, round(($avgDays / $slaTarget) * 100));
                $isWithinSLA = $avgDays <= $slaTarget;
            @endphp
            
            <div class="flex items-baseline justify-between">
                <div>
                    <p class="text-3xl font-bold text-gray-800 group-hover:text-purple-600 transition-colors" id="sla-avg-days">{{ $avgDays }}</p>
                    <p class="text-xs text-gray-500 mt-1">working days</p>
                </div>
                
            </div>
</div>
</div>
</div>
<!-- ONE BUTTON TO RULE THEM ALL -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Dashboard Overview</h1>
        <p class="text-gray-600">Real-time monitoring of DV request activities</p>
    </div>
    
    <!-- ONE URGENT BUTTON -->
    <button onclick="openUrgentModal()" 
            class="relative bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-8 py-4 rounded-xl text-base font-bold flex items-center space-x-4 transition-all duration-200 hover:shadow-2xl transform hover:scale-105 group">
        <!-- Animated warning icon -->
        <div class="relative">
            <svg class="w-7 h-7 group-hover:animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <!-- Pulse animation when there are documents -->
            @if($totalExceededCount > 0)
            <span class="absolute -top-1 -right-1 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-white"></span>
            </span>
            @endif
        </div>
        
        <span class="flex flex-col items-start">
            <span class="text-xs uppercase tracking-wider opacity-90">Action Required</span>
            <span class="text-lg font-bold">Mark Exceeded as Urgent</span>
        </span>
        
        <!-- Big counter badge -->
        <span id="exceededBadge" class="bg-white text-red-700 px-4 py-2 rounded-full text-xl font-black shadow-inner border-2 border-red-300 group-hover:scale-110 transition-transform">
            {{ $totalExceededCount ?? 0 }}
        </span>
    </button>
</div>

<!-- URGENT MODAL - ONE MODAL FOR ALL DEPARTMENTS -->
<div id="urgentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-2 md:p-4 overflow-hidden">
    <div class="bg-white rounded-2xl shadow-2xl max-w-7xl w-full flex flex-col" style="max-height: 95vh;">
        <!-- Modal Header - Fixed at top -->
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-4 md:px-6 py-4 rounded-t-2xl flex justify-between items-center flex-shrink-0">
            <div class="flex items-center space-x-2 md:space-x-3">
                <div class="bg-white bg-opacity-20 p-1.5 md:p-2 rounded-lg">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h3 id="modalTitle" class="text-xl md:text-2xl font-bold text-white">All Exceeded Documents</h3>
                    <p class="text-red-100 text-xs md:text-sm">Select documents to mark as urgent across all departments</p>
                </div>
            </div>
            <button onclick="closeUrgentModal()" class="text-white hover:text-gray-200 bg-black bg-opacity-20 hover:bg-opacity-30 rounded-lg p-1.5 md:p-2 transition">
                <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Scrollable Content Area - Takes remaining height -->
        <div class="overflow-y-auto flex-1 p-4 md:p-6" style="max-height: calc(95vh - 80px);">
            <!-- Summary Stats -->
            <div class="mb-4 md:mb-6 bg-gradient-to-r from-yellow-50 to-amber-50 border-l-8 border-yellow-500 rounded-lg p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <div class="bg-yellow-500 p-1.5 rounded-full">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="text-base md:text-lg font-bold text-yellow-800" id="modalExceededCount">0</span>
                        <span class="text-sm text-yellow-700">documents exceeded SLA</span>
                    </div>
                    <div id="modalExceededSummary" class="flex flex-wrap gap-1.5 md:gap-2">
                        <!-- Department counts will be dynamically populated -->
                    </div>
                </div>
            </div>

            <!-- Department Filter Tabs - Scrollable on mobile -->
            <div class="mb-4 border-b border-gray-200">
                <div class="flex flex-nowrap md:flex-wrap gap-1 md:gap-2 overflow-x-auto pb-1 scrollbar-thin scrollbar-thumb-gray-300">
                    <button id="filter-all" onclick="filterDepartment('all')" class="px-3 md:px-5 py-2 text-xs md:text-sm font-medium rounded-t-lg border-b-2 border-red-600 bg-red-50 text-red-700 whitespace-nowrap flex-shrink-0">
                        All Departments
                    </button>
                    <button id="filter-imsd" onclick="filterDepartment('imsd')" class="px-3 md:px-5 py-2 text-xs md:text-sm font-medium rounded-t-lg border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-900 whitespace-nowrap flex-shrink-0">
                        <span class="flex items-center gap-1 md:gap-2">
                            <span class="w-1.5 h-1.5 md:w-2 md:h-2 bg-red-500 rounded-full"></span>
                            IMSD
                        </span>
                    </button>
                    <button id="filter-incoming" onclick="filterDepartment('incoming')" class="px-3 md:px-5 py-2 text-xs md:text-sm font-medium rounded-t-lg border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-900 whitespace-nowrap flex-shrink-0">
                        <span class="flex items-center gap-1 md:gap-2">
                            <span class="w-1.5 h-1.5 md:w-2 md:h-2 bg-blue-500 rounded-full"></span>
                            Incoming
                        </span>
                    </button>
                    <button id="filter-budget" onclick="filterDepartment('budget')" class="px-3 md:px-5 py-2 text-xs md:text-sm font-medium rounded-t-lg border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-900 whitespace-nowrap flex-shrink-0">
                        <span class="flex items-center gap-1 md:gap-2">
                            <span class="w-1.5 h-1.5 md:w-2 md:h-2 bg-purple-500 rounded-full"></span>
                            Budget
                        </span>
                    </button>
                    <button id="filter-outgoing" onclick="filterDepartment('outgoing')" class="px-3 md:px-5 py-2 text-xs md:text-sm font-medium rounded-t-lg border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-900 whitespace-nowrap flex-shrink-0">
                        <span class="flex items-center gap-1 md:gap-2">
                            <span class="w-1.5 h-1.5 md:w-2 md:h-2 bg-green-500 rounded-full"></span>
                            Outgoing
                        </span>
                    </button>
                    <button id="filter-accountant" onclick="filterDepartment('accountant')" class="px-3 md:px-5 py-2 text-xs md:text-sm font-medium rounded-t-lg border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-900 whitespace-nowrap flex-shrink-0">
                        <span class="flex items-center gap-1 md:gap-2">
                            <span class="w-1.5 h-1.5 md:w-2 md:h-2 bg-indigo-500 rounded-full"></span>
                            Accountant
                        </span>
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="mb-4">
                <div class="relative">
                    <input type="text" 
                           id="searchDocuments"
                           placeholder="Search by control #, ORS #, program, department, status, or focal person..."
                           class="w-full border-2 border-gray-200 rounded-lg md:rounded-xl pl-9 pr-20 py-2.5 md:py-3 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                           onkeyup="searchDocuments()">
                    <svg class="absolute left-3 top-3 h-4 w-4 md:h-5 md:w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <div class="absolute right-3 top-2.5 flex items-center space-x-1.5">
                        <span id="searchResultCount" class="text-xs md:text-sm text-gray-500 font-medium">0</span>
                        <button onclick="clearSearch()" class="text-gray-400 hover:text-gray-600 transition p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    <span class="font-medium">Tip:</span> Search by control number, ORS number, program, department, status, or focal person
                </p>
            </div>

            <!-- Remarks -->
            <div class="mb-4">
                <label class="block text-xs md:text-sm font-semibold text-gray-700 mb-1.5">Remarks <span class="text-gray-400">(Optional)</span></label>
                <textarea id="urgentRemarks" rows="2" 
                    class="w-full border-2 border-gray-200 rounded-lg md:rounded-xl px-3 md:px-4 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    placeholder="Why are these documents being marked as urgent?"></textarea>
            </div>

            <!-- Documents Table Header with Select All -->
            <div class="mb-3 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <h4 class="font-bold text-gray-800 flex items-center gap-1.5 text-sm md:text-base">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Documents Ready for Urgent Marking
                </h4>
                <label class="flex items-center space-x-2 text-xs md:text-sm bg-gray-100 px-3 md:px-4 py-1.5 md:py-2 rounded-lg">
                    <input type="checkbox" id="selectAllDocs" class="rounded border-gray-400 text-red-600 focus:ring-red-500 w-3.5 h-3.5 md:w-4 md:h-4">
                    <span class="font-medium text-gray-700">Select All Visible</span>
                </label>
            </div>
            
            <!-- Documents Table Container with Fixed Height and Scrolling -->
            <div class="border-2 border-gray-100 rounded-lg md:rounded-xl overflow-hidden mb-4">
                <div class="overflow-x-auto">
                    <div class="max-h-64 md:max-h-80 lg:max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase w-10 md:w-12">Select</th>
                                    <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase">Control # / ORS #</th>
                                    <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                                    <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase">Days</th>
                                    <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase">Dept</th>
                                    <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase">Focal</th>
                                </tr>
                            </thead>
                            <tbody id="exceededDocumentsList" class="divide-y divide-gray-200 bg-white">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Actions - Fixed at bottom -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3 p-4 md:p-6 border-t bg-gray-50 rounded-b-2xl flex-shrink-0">
            <div class="text-xs md:text-sm text-gray-600">
                <span id="selectedCount" class="font-bold text-red-600">0</span> document(s) selected
            </div>
            <div class="flex space-x-2 md:space-x-3 w-full sm:w-auto">
                <button onclick="closeUrgentModal()" class="flex-1 sm:flex-none px-4 md:px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg text-sm md:text-base transition">
                    Cancel
                </button>
                <button onclick="submitUrgentMarking()" class="flex-1 sm:flex-none px-4 md:px-6 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-lg flex items-center justify-center gap-1.5 md:gap-2 shadow-lg hover:shadow-xl text-sm md:text-base transition">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Mark Selected
                </button>
            </div>
        </div>
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
    
<!-- Documents per Role - UPDATED INSIGHT SECTION -->
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
                $busiestPercent = $totalDocs > 0 ? round(($busiestCount / $totalDocs) * 100, 1) : 0;
                
                $lightestRole = $docsPerRole->sort()->keys()->first();
                $lightestCount = $docsPerRole->min();
                $lightestPercent = $totalDocs > 0 ? round(($lightestCount / $totalDocs) * 100, 1) : 0;
                
                $workloadGap = $busiestCount - $lightestCount;
                
                // Calculate average workload per role
                $avgDocsPerRole = $totalDocs / $docsPerRole->count();
                
                // Determine balance status
                $isBalanced = $busiestPercent < 30; // No single role dominates
                $isModerateImbalance = $busiestPercent >= 30 && $busiestPercent < 40;
                $isHighImbalance = $busiestPercent >= 40;
            } else {
                $busiestRole = $lightestRole = 'No data';
                $busiestCount = $lightestCount = $workloadGap = 0;
                $busiestPercent = $lightestPercent = 0;
                $avgDocsPerRole = 0;
                $isBalanced = true;
                $isModerateImbalance = $isHighImbalance = false;
            }
        @endphp

        <div class="space-y-2 text-sm text-gray-700">
            <p class="text-sm text-gray-700">
                {{-- UPDATED TEXT TO CLARIFY DATE FILTERING --}}
                For requests <strong class="text-blue-700">created in the selected period</strong>, 
                <strong class="text-purple-700">{{ $busiestRole }}</strong> handled the most documents with 
                <strong>{{ number_format($busiestCount) }}</strong> requests, representing 
                <strong>{{ $busiestPercent }}%</strong> of total workload. 
                
                In contrast, <strong>{{ $lightestRole }}</strong> handled the least with 
                <strong>{{ number_format($lightestCount) }}</strong> requests ({{ $lightestPercent }}% of total). 
                
                The workload gap is <strong>{{ number_format($workloadGap) }}</strong> documents. 
                
                @if($isHighImbalance)
                This represents a <strong class="text-orange-700">significant concentration</strong> of work in one role, 
                indicating a need for workload redistribution and resource reallocation.
                @elseif($isModerateImbalance)
                This shows <strong class="text-yellow-700">moderate concentration</strong> that should be monitored 
                to prevent potential bottlenecks.
                @elseif($busiestCount > 0)
                The workload distribution is <strong class="text-green-700">relatively balanced</strong> across roles, 
                with no single role handling more than 30% of total documents.
                @endif
                
                The average workload is <strong>{{ number_format($avgDocsPerRole, 1) }}</strong> documents per role.
            </p>
            
            {{-- INFO BOX TO EXPLAIN THE METRIC --}}
            <div class="mt-3 p-2 bg-blue-50 border-l-4 border-blue-500 rounded">
                <p class="text-xs text-blue-800">
                    <strong>ℹ️ Note:</strong> This shows how many requests (created in the selected date range) 
                    each role processed during their workflow stage. A request created on Jan 15 will be counted 
                    for all roles that handled it, regardless of when they processed it.
                </p>
            </div>
            
            @if($isHighImbalance)
            <div class="mt-3 p-2 bg-orange-100 border-l-4 border-orange-500 rounded">
                <p class="text-xs text-orange-800">
                    <strong>⚠ Action Required:</strong> {{ $busiestRole }} is handling {{ $busiestPercent }}% of all documents. 
                    Consider redistributing tasks or adding staff support to this role.
                </p>
            </div>
            @elseif($isModerateImbalance)
            <div class="mt-3 p-2 bg-yellow-100 border-l-4 border-yellow-500 rounded">
                <p class="text-xs text-yellow-800">
                    <strong>⚠ Monitor:</strong> {{ $busiestRole }} handles {{ $busiestPercent }}% of total workload. 
                    Continue monitoring to ensure this doesn't become a bottleneck.
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
<!-- ============================================================
     PENDING REQUESTS BY ROLE — Full Section (Drop-in Replacement)
     Replace the entire "Pending Bottleneck Chart" div in your dashboard
============================================================ -->
<div class="bg-white p-4 rounded-lg shadow mb-8">

    {{-- ── HEADER ──────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold"> Bottlenecks by Role (Pending Documents)</h2>
            <p class="text-sm text-gray-600">Current backlog and wait times by role</p>
        </div>
        @php
            $criticalRoles = collect($pendingByRole ?? [])->filter(fn($r) => ($r->exceeded_sla_count ?? 0) > 0)->count();
        @endphp
        @if($criticalRoles > 0)
        <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-bold animate-pulse">
            {{ $criticalRoles }} Role(s) with Delayed Documents
        </span>
        @endif
    </div>

    {{-- ── ROLE FILTER TABS ────────────────────────────────────────── --}}
    <div class="mb-4 border-b border-gray-200 overflow-x-auto">
        <div class="flex flex-nowrap md:flex-wrap gap-1 md:gap-2 pb-1">
            <button onclick="filterRole('all')" id="filter-role-all"
                class="px-3 md:px-4 py-2 text-xs md:text-sm font-medium rounded-t-lg border-b-2 border-blue-600 bg-blue-50 text-blue-700 whitespace-nowrap">
                All Roles
            </button>
            @foreach($pendingByRole ?? [] as $role)
            <button onclick="filterRole('{{ $role->role }}')"
                id="filter-role-{{ Str::slug($role->role) }}"
                class="px-3 md:px-4 py-2 text-xs md:text-sm font-medium rounded-t-lg border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-900 whitespace-nowrap">
                <span class="flex items-center gap-1">
                    {{ str_replace('_', ' ', $role->role) }}
                    <span class="ml-1 text-xs {{ ($role->total_pending ?? 0) > 20 ? 'bg-red-200 text-red-800' : 'bg-gray-200' }} px-1.5 rounded-full">
                        {{ $role->total_pending ?? 0 }}
                    </span>
                </span>
            </button>
            @endforeach
        </div>
    </div>

    {{-- ── CHART ───────────────────────────────────────────────────── --}}
    <div class="chart-container" style="height: 350px;">
        <canvas id="pendingBottleneckChart"></canvas>
    </div>
{{-- ══════════════════════════════════════════════════════════════
     IMPROVED INSIGHTS — summary paragraph style with fixed percentages
══════════════════════════════════════════════════════════════ --}}
@if(!empty($pendingByRole) && collect($pendingByRole)->count() > 0)
@php
    $pendingByRole = collect($pendingByRole);

    // ── Totals ──────────────────────────────────────────────────
    $totalPending   = $pendingByRole->sum('total_pending');
    $totalDelayed   = $pendingByRole->sum('exceeded_sla_count');
    $totalCritical  = $pendingByRole->sum('critical_count');
    $roleCount      = $pendingByRole->count();
    $avgPending     = $roleCount > 0 ? $totalPending / $roleCount : 0;
    $delayRate      = $totalPending > 0 ? round(($totalDelayed / $totalPending) * 100, 1) : 0;
    $maxWait        = $pendingByRole->max('avg_wait_days') ?? 0;

    // ── Key roles ────────────────────────────────────────────────
    $mostBacklogged   = $pendingByRole->sortByDesc('total_pending')->first();
    $longestWait      = $pendingByRole->sortByDesc('avg_wait_days')->first();
    $slaBreachedRoles = $pendingByRole->filter(fn($r) => ($r->exceeded_sla_count ?? 0) > 0);
    $overloadedRoles  = $pendingByRole->filter(fn($r) => ($r->total_pending ?? 0) > ($avgPending * 1.5));
    $slowRoles        = $pendingByRole->filter(fn($r) => ($r->avg_wait_days ?? 0) > 5)->sortByDesc('avg_wait_days');

    $highestBreachRole = $pendingByRole
        ->filter(fn($r) => ($r->total_pending ?? 0) > 0 && ($r->exceeded_sla_count ?? 0) > 0)
        ->sortByDesc(fn($r) => $r->exceeded_sla_count / $r->total_pending)
        ->first();

    // ── Severity ─────────────────────────────────────────────────
    if ($delayRate > 50 || $maxWait > 14) {
        $severity      = 'CRITICAL';
        $sevColor      = 'text-red-700';
        $sevBorder     = 'border-l-red-600';
        $sevBg         = 'bg-red-50 border-red-200';
        $sevBadgeBg    = 'bg-red-100 text-red-800';
        $sevDot        = 'bg-red-500';
        $sevIcon       = '🔴';
    } elseif ($delayRate > 25 || $maxWait > 7) {
        $severity      = 'HIGH';
        $sevColor      = 'text-orange-700';
        $sevBorder     = 'border-l-orange-500';
        $sevBg         = 'bg-orange-50 border-orange-200';
        $sevBadgeBg    = 'bg-orange-100 text-orange-800';
        $sevDot        = 'bg-orange-500';
        $sevIcon       = '🟠';
    } elseif ($delayRate > 0 || $totalDelayed > 0) {
        $severity      = 'MODERATE';
        $sevColor      = 'text-yellow-700';
        $sevBorder     = 'border-l-yellow-500';
        $sevBg         = 'bg-yellow-50 border-yellow-200';
        $sevBadgeBg    = 'bg-yellow-100 text-yellow-800';
        $sevDot        = 'bg-yellow-500';
        $sevIcon       = '🟡';
    } else {
        $severity      = 'HEALTHY';
        $sevColor      = 'text-green-700';
        $sevBorder     = 'border-l-green-500';
        $sevBg         = 'bg-green-50 border-green-200';
        $sevBadgeBg    = 'bg-green-100 text-green-800';
        $sevDot        = 'bg-green-500';
        $sevIcon       = '🟢';
    }

    // ── Cascade detection ────────────────────────────────────────
    $workflowOrder = ['IMSD','Incoming-TUPAD','Incoming-GIP','Incoming-TEV',
                      'Budget_Officer','Outgoing','Accountant_III'];
    $cascades = [];
    foreach ($workflowOrder as $idx => $roleName) {
        $roleObj = $pendingByRole->firstWhere('role', $roleName);
        if ($roleObj && ($roleObj->total_pending ?? 0) > 10 && isset($workflowOrder[$idx + 1])) {
            $cascades[] = str_replace('_',' ',$roleName)
                        . ' → '
                        . str_replace('_',' ',$workflowOrder[$idx + 1]);
        }
    }

    // ── Volume analysis text (FIXED PERCENTAGE CALCULATION) ──────
    $volumeInsights = [];
    foreach ($overloadedRoles as $r) {
        // Calculate percentage of total pending documents this role holds
        $percentageOfTotal = $totalPending > 0 
            ? round(($r->total_pending / $totalPending) * 100, 1) 
            : 0;
        
        $txt = '<strong>' . str_replace('_',' ',$r->role) . '</strong> has '
             . '<strong class="' . $sevColor . '">' . number_format($r->total_pending) . '</strong> documents — '
             . '<strong>' . $percentageOfTotal . '%</strong> of all pending requests';
        
        if (($r->exceeded_sla_count ?? 0) > 0) {
            $breachPercentage = $r->total_pending > 0 
                ? round(($r->exceeded_sla_count / $r->total_pending) * 100, 1) 
                : 0;
            $txt .= ', with <strong class="text-red-600">' . number_format($r->exceeded_sla_count) . ' (' . $breachPercentage . '%) already exceeded turnaround time threshold</strong>';
        }
        $volumeInsights[] = $txt . '.';
    }

    // ── SLA breach text ──────────────────────────────────────────
    $slaInsights = [];
    foreach ($slaBreachedRoles->sortByDesc('exceeded_sla_count') as $r) {
        $bRate = $r->total_pending > 0
            ? round(($r->exceeded_sla_count / $r->total_pending) * 100, 1)
            : 0;
        $urgency = $bRate > 50
            ? '<span class="text-red-600 font-semibold"> — CRITICAL: ' . $bRate . '% overdue</span>'
            : ($bRate > 25 ? '<span class="text-orange-600 font-semibold"> — needs immediate attention (' . $bRate . '%)</span>' : '');
        $slaInsights[] = '<strong>' . str_replace('_',' ',$r->role) . '</strong> has a '
                       . '<strong class="text-orange-600">' . $bRate . '% Pending Documents Rate</strong> '
                       . '(' . number_format($r->exceeded_sla_count) . ' of ' . number_format($r->total_pending) . ' documents)'
                       . $urgency . '.';
    }

    // ── Wait time text ───────────────────────────────────────────
    $waitInsights = [];
    foreach ($slowRoles as $r) {
        $label = ($r->avg_wait_days ?? 0) > 14
            ? '<span class="text-red-600 font-semibold"> — extreme delay, investigate immediately</span>'
            : (($r->avg_wait_days ?? 0) > 7
                ? '<span class="text-orange-600 font-semibold"> — significant delay, process improvement needed</span>'
                : '<span class="text-yellow-700"> — monitor closely</span>');
        $waitInsights[] = '<strong>' . str_replace('_',' ',$r->role) . '</strong> averages '
                        . '<strong class="text-purple-600">' . round($r->avg_wait_days,1) . ' days</strong>'
                        . $label . '.';
    }

    // ── Recommendations ──────────────────────────────────────────
    $recs = [];

    if ($mostBacklogged && ($mostBacklogged->total_pending ?? 0) > 15) {
        $bPct = $totalPending > 0
            ? round(($mostBacklogged->total_pending / $totalPending) * 100, 1)
            : 0;
        $recs[] = [
            'priority' => $severity === 'CRITICAL' ? 'URGENT' : 'HIGH',
            'icon'     => '🚨',
            'text'     => 'Clear the backlog in <strong>' . str_replace('_',' ',$mostBacklogged->role) . '</strong> ('
                        . number_format($mostBacklogged->total_pending) . ' docs, ' . $bPct . '% of total) — '
                        . 'consider temporary staff reassignment or overtime authorisation.',
            'badge'    => $severity === 'CRITICAL' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800',
            'dot'      => $severity === 'CRITICAL' ? 'bg-red-500' : 'bg-orange-500',
        ];
    }

    if ($totalDelayed > 0) {
        $recs[] = [
            'priority' => 'HIGH',
            'icon'     => '⚠️',
            'text'     => 'Immediately triage the <strong>' . number_format($totalDelayed) . ' overdue document'
                        . ($totalDelayed > 1 ? 's' : '') . '</strong> (' . $delayRate . '% of pending).',
            'badge'    => 'bg-orange-100 text-orange-800',
            'dot'      => 'bg-orange-500',
        ];
    }

    if ($highestBreachRole) {
        $bRate = round(($highestBreachRole->exceeded_sla_count / max($highestBreachRole->total_pending,1)) * 100, 1);
        $recs[] = [
            'priority' => $bRate > 50 ? 'URGENT' : 'MEDIUM',
            'icon'     => $bRate > 50 ? '🔥' : '📋',
            'text'     => 'Review workflow in <strong>' . str_replace('_',' ',$highestBreachRole->role) . '</strong> — '
                        . $bRate . '% of its documents are delayed (' . number_format($highestBreachRole->exceeded_sla_count) . ' of ' . number_format($highestBreachRole->total_pending) . '). '
                        . 'Check for incomplete submissions, missing attachments, or understaffing.',
            'badge'    => $bRate > 50 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800',
            'dot'      => $bRate > 50 ? 'bg-red-500' : 'bg-yellow-500',
        ];
    }

    if ($longestWait && ($longestWait->avg_wait_days ?? 0) > 7) {
        $recs[] = [
            'priority' => 'HIGH',
            'icon'     => '⏰',
            'text'     => 'Reduce wait time in <strong>' . str_replace('_',' ',$longestWait->role) . '</strong> — '
                        . round($longestWait->avg_wait_days,1) . '-day average is above threshold. '
                        . 'Consider automating routine checks or introducing daily processing targets.',
            'badge'    => 'bg-orange-100 text-orange-800',
            'dot'      => 'bg-orange-500',
        ];
    }

    if ($overloadedRoles->count() > 1) {
        $roleNames = $overloadedRoles->take(2)->map(fn($r) => str_replace('_',' ',$r->role))->implode(' and ');
        $totalPct = 0;
        foreach ($overloadedRoles as $r) {
            $totalPct += $totalPending > 0 ? ($r->total_pending / $totalPending) * 100 : 0;
        }
        $recs[] = [
            'priority' => 'MEDIUM',
            'icon'     => '⚖️',
            'text'     => 'Rebalance workload — <strong>' . $roleNames . '</strong> are handling '
                        . '<strong>' . round($totalPct, 1) . '%</strong> of all pending documents. '
                        . 'Explore cross-training or parallel processing.',
            'badge'    => 'bg-yellow-100 text-yellow-800',
            'dot'      => 'bg-yellow-500',
        ];
    }

    foreach (array_slice($cascades, 0, 2) as $cascade) {
        $recs[] = [
            'priority' => 'MEDIUM',
            'icon'     => '🔗',
            'text'     => 'Resolve workflow cascade: <strong>' . $cascade . '</strong> — '
                        . 'the upstream queue must clear before downstream roles can proceed.',
            'badge'    => 'bg-blue-100 text-blue-800',
            'dot'      => 'bg-blue-500',
        ];
    }

    if (empty($recs)) {
        $recs[] = [
            'priority' => 'LOW',
            'icon'     => '✅',
            'text'     => 'No urgent actions needed — all roles are within acceptable thresholds. '
                        . 'Continue monitoring for emerging bottlenecks.',
            'badge'    => 'bg-green-100 text-green-800',
            'dot'      => 'bg-green-500',
        ];
    }
@endphp

    <div class="mt-5 space-y-4">

        {{-- ── SUMMARY PARAGRAPH ──────────────────────────────────── --}}
        <div class="rounded-xl border-l-4 {{ $sevBorder }} {{ $sevBg }} border p-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <h4 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 {{ $sevColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Pending Analysis
                </h4>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-full flex-shrink-0 {{ $sevBadgeBg }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $sevDot }}"></span>
                    {{ $sevIcon }} {{ $severity }}
                </span>
            </div>

            {{-- Overview sentence --}}
            <p class="text-sm text-gray-700 leading-relaxed">
                There are currently
                <strong class="{{ $sevColor }}">{{ number_format($totalPending) }} pending documents</strong>
                across {{ $roleCount }} roles.

                @if($totalDelayed > 0)
                    <strong class="text-red-600">{{ number_format($totalDelayed) }}
                    ({{ $delayRate }}%)</strong> have exceeded their turnaround time threshold
                    @if($slaBreachedRoles->count() > 0)
                        — concentrated in
                        <strong>{{ $slaBreachedRoles->map(fn($r) => str_replace('_',' ',$r->role))->implode(', ') }}</strong>
                    @endif.
                @else
                    No documents have exceeded their turnaround time threshold.
                @endif

                @if($mostBacklogged && ($mostBacklogged->total_pending ?? 0) > 0)
                    @php $bPct = $totalPending > 0 ? round(($mostBacklogged->total_pending / $totalPending) * 100) : 0; @endphp
                    The largest backlog is at
                    <strong class="{{ $sevColor }}">{{ str_replace('_',' ',$mostBacklogged->role) }}</strong>
                    holding <strong>{{ $mostBacklogged->total_pending }} documents ({{ $bPct }}% of total)
                    @if(($mostBacklogged->avg_wait_days ?? 0) > 0)
                        with an average wait of {{ round($mostBacklogged->avg_wait_days,1) }} days
                    @endif
                    </strong>.
                @endif

                @if($totalCritical > 0)
                    <strong class="text-red-600">{{ $totalCritical }} critical
                    request{{ $totalCritical > 1 ? 's are' : ' is' }}</strong>
                    waiting more than twice the allowed threshold.
                @endif

                @if(!empty($cascades))
                    A workflow cascade is forming at
                    <strong>{{ implode(' and ', array_slice($cascades,0,2)) }}</strong>,
                    where upstream backlogs are blocking downstream processing.
                @endif
            </p>

            {{-- Volume sub-section --}}
            @if(count($volumeInsights) > 0)
            <div class="mt-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                    📊 Volume
                </p>
                <ul class="space-y-1">
                    @foreach($volumeInsights as $vi)
                    <li class="text-sm text-gray-700 flex items-start gap-1.5 leading-snug">
                        <span class="text-red-400 font-bold flex-shrink-0 mt-0.5">•</span>
                        <span>{!! $vi !!}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- SLA breach sub-section --}}
            @if(count($slaInsights) > 0)
            <div class="mt-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                    ⏱ Turnaround Time Violation
                </p>
                <ul class="space-y-1">
                    @foreach($slaInsights as $si)
                    <li class="text-sm text-gray-700 flex items-start gap-1.5 leading-snug">
                        <span class="text-orange-400 font-bold flex-shrink-0 mt-0.5">•</span>
                        <span>{!! $si !!}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Wait time sub-section --}}
            @if(count($waitInsights) > 0)
            <div class="mt-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                    🕐 Wait Time
                </p>
                <ul class="space-y-1">
                    @foreach($waitInsights as $wi)
                    <li class="text-sm text-gray-700 flex items-start gap-1.5 leading-snug">
                        <span class="text-purple-400 font-bold flex-shrink-0 mt-0.5">•</span>
                        <span>{!! $wi !!}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- No issues fallback --}}
            @if(count($volumeInsights) === 0 && count($slaInsights) === 0 && count($waitInsights) === 0)
            <p class="text-sm text-green-700 mt-2 flex items-center gap-1.5">
                <span>✓</span> All roles are processing within acceptable thresholds.
            </p>
            @endif
        </div>

        {{-- ── RECOMMENDATIONS ────────────────────────────────────── --}}
        <div class="bg-green-50 border border-green-100 rounded-xl p-4">
            <h4 class="text-sm font-bold text-green-800 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Recommended Actions
                <span class="ml-auto text-xs font-normal text-green-700 bg-green-100 px-2 py-0.5 rounded-full">
                    {{ count($recs) }} action{{ count($recs) > 1 ? 's' : '' }}
                </span>
            </h4>

            <div class="space-y-2">
                @foreach($recs as $i => $rec)
                <div class="flex items-start gap-3 bg-white rounded-lg px-3 py-2.5 shadow-sm">
                    <div class="flex-shrink-0 flex flex-col items-center gap-1 pt-0.5">
                        <span class="w-6 h-6 rounded-full {{ $rec['dot'] }} flex items-center justify-center text-white font-extrabold text-xs">
                            {{ $i + 1 }}
                        </span>
                        <span class="text-sm leading-none">{{ $rec['icon'] }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5 mb-0.5">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $rec['badge'] }}">
                                {{ $rec['priority'] }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-700 leading-snug">{!! $rec['text'] !!}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>{{-- end .space-y-4 --}}

    @else
    <div class="mt-4 p-6 bg-gray-50 rounded-xl border border-gray-200 text-center">
        <p class="text-gray-400 text-sm">No pending requests data available.</p>
    </div>
    @endif

</div>{{-- end card --}}
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
    document.addEventListener('DOMContentLoaded', function() {
    const dropdownBtn = document.getElementById('quickFilterDropdownBtn');
    const dropdown = document.getElementById('quickFilterDropdown');
    
    if (dropdownBtn && dropdown) {
        // Toggle dropdown on button click
        dropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
            dropdownBtn.classList.toggle('bg-gray-50');
            dropdownBtn.classList.toggle('border-blue-300');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && !dropdownBtn.contains(e.target)) {
                dropdown.classList.add('hidden');
                dropdownBtn.classList.remove('bg-gray-50');
                dropdownBtn.classList.remove('border-blue-300');
            }
        });
        
        // Close dropdown on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
                dropdownBtn.classList.remove('bg-gray-50');
                dropdownBtn.classList.remove('border-blue-300');
            }
        });
        
        // Highlight active filter in dropdown
        highlightActiveFilter();
    }
});
// Add these variables at the top of your script section
let pendingBottleneckChart = null;
let currentView = 'role';
let currentRoleFilter = 'all';

// Replace your existing initPendingBottleneckChart function
function initPendingBottleneckChart() {
    const ctx = document.getElementById('pendingBottleneckChart');
    if (!ctx) return;
    
    updatePendingChartView(currentView);
}

function updatePendingChartView(view) {
    const ctx = document.getElementById('pendingBottleneckChart');
    if (!ctx) return;
    
    if (pendingBottleneckChart) {
        pendingBottleneckChart.destroy();
    }
    
    if (view === 'role') {
        // Role View - Show pending counts by role
        const roleData = @json($pendingByRole ?? []);
        
        // Filter if needed
        let filteredData = roleData;
        if (currentRoleFilter !== 'all') {
            filteredData = roleData.filter(r => r.role === currentRoleFilter);
        }
        
        const labels = filteredData.map(r => r.role.replace(/_/g, ' '));
        const pendingCounts = filteredData.map(r => r.total_pending);
        
        // Color based on exceeding threshold
        const barColors = filteredData.map(r => {
            // Check if any pending requests have exceeded threshold
            // Using exceeded_sla_count as indicator of problems
            if (r.exceeded_sla_count > 0) {
                const exceedRatio = r.exceeded_sla_count / r.total_pending;
                if (exceedRatio >= 0.5) return '#ef4444'; // Critical - Red (>50% exceeded)
                if (exceedRatio >= 0.25) return '#f97316'; // High - Orange (25-50% exceeded)
                return '#f59e0b'; // Warning - Amber (<25% exceeded)
            }
            return '#10b981'; // Good - Green (no exceeded)
        });
        
        pendingBottleneckChart = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Pending Count',
                        data: pendingCounts,
                        backgroundColor: barColors,
                        borderColor: barColors.map(c => c),
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'top',
                        labels: { 
                            usePointStyle: true,
                            boxWidth: 8,
                            boxHeight: 8
                        }
                    },
                    title: { 
                        display: true, 
                        text: currentRoleFilter === 'all' ? 
                            'Pending Requests by Role' : 
                            `Pending Requests - ${currentRoleFilter.replace(/_/g, ' ')}`,
                        font: { size: 14, weight: 'bold' }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let value = context.parsed.y;
                                
                                if (context.dataset.label === 'Pending Count') {
                                    return `${label}: ${value} requests`;
                                }
                                return label;
                            },
                            afterLabel: function(context) {
                                if (context.dataset.label === 'Pending Count') {
                                    const role = filteredData[context.dataIndex];
                                    return [
                                        `Exceeded SLA: ${role?.exceeded_sla_count || 0}`,
                                        `Critical: ${role?.critical_count || 0}`,
                                        `Threshold: ${role?.threshold || 3} days`
                                    ];
                                }
                                return null;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        ticks: { 
                            precision: 0,
                            callback: function(value) {
                                return value + ' req';
                            }
                        },
                        title: { 
                            display: true, 
                            text: 'Number of Pending Requests',
                            font: { weight: 'bold' }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    }
                }
            }
        });
    }
}

// Add filterRole function
function filterRole(role) {
    currentRoleFilter = role;
    
    // Update filter button styles
    document.querySelectorAll('[id^="filter-role-"]').forEach(btn => {
        if (btn.id === `filter-role-${role}` || (role === 'all' && btn.id === 'filter-role-all')) {
            btn.className = 'px-3 md:px-4 py-2 text-xs md:text-sm font-medium rounded-t-lg border-b-2 border-blue-600 bg-blue-50 text-blue-700 whitespace-nowrap';
        } else {
            btn.className = 'px-3 md:px-4 py-2 text-xs md:text-sm font-medium rounded-t-lg border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-900 whitespace-nowrap';
        }
    });
    
    // Update chart if in role view
    if (currentView === 'role') {
        updatePendingChartView('role');
    }
}

// Add togglePendingView function
function togglePendingView(view) {
    currentView = view;
    updatePendingChartView(view);
}

// Update the toggle button text
function togglePendingView() {
    currentView = currentView === 'role' ? 'status' : 'role';
    const btn = document.getElementById('togglePendingViewBtn');
    btn.textContent = currentView === 'role' ? 'Switch to Status View' : 'Switch to Role View';
    updatePendingChartView(currentView);
}
function highlightActiveFilter() {
    // You can implement this based on your current filter state
    // For example, check the URL parameters and add 'active' class to matching links
    const currentUrl = window.location.href;
    const dropdownLinks = document.querySelectorAll('#quickFilterDropdown a');
    
    dropdownLinks.forEach(link => {
        if (link.href === currentUrl) {
            link.classList.add('active');
        }
    });
}

function showCustomRange() {
    // This function would show a custom range picker
    // For now, just focus on the date inputs
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    
    if (dateFromInput) {
        dateFromInput.focus();
    }
    
    // Close the dropdown
    document.getElementById('quickFilterDropdown').classList.add('hidden');
}
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
     initPendingBottleneckChart();
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
                    backgroundColor: '#ef4444',
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
window.togglePendingView = togglePendingView;
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
// ============ URGENT MODAL FUNCTIONS - ONE BUTTON FOR ALL DEPARTMENTS ============

// Global state
let allExceededDocuments = [];
let currentFilter = 'all';
let currentSearchTerm = '';

// Open Urgent Modal
window.openUrgentModal = function() {
    const modal = document.getElementById('urgentModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        fetchAllExceededDocuments();
    }
};

// Close Urgent Modal
window.closeUrgentModal = function() {
    const modal = document.getElementById('urgentModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
};

// Fetch ALL exceeded documents from ALL departments
function fetchAllExceededDocuments() {
    fetch('/admin/dashboard/all-exceeded-documents')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            allExceededDocuments = data.documents || [];
            
            // Update modal counts
            document.getElementById('modalExceededCount').textContent = data.count || 0;
            
            // Update badge in button
            const badge = document.getElementById('exceededBadge');
            if (badge) {
                badge.textContent = data.count || 0;
                
                // Show/hide pulse animation
                const pulse = document.getElementById('urgentPulse');
                if (pulse) {
                    if (data.count > 0) {
                        pulse.classList.remove('hidden');
                    } else {
                        pulse.classList.add('hidden');
                    }
                }
            }
            
            // Update summary badges
            updateSummaryBadges(data.summary);
            
            // Update quick stats
            updateQuickStats(data.summary);
            
            // Apply current filter and search and render
            applyFilterAndSearch();
        })
        .catch(error => {
            console.error('Error fetching exceeded documents:', error);
            document.getElementById('exceededDocumentsList').innerHTML = `
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-red-500">
                        Error loading documents. Please try again.
                    </td>
                </tr>
            `;
        });
}

// Update summary badges in modal
function updateSummaryBadges(summary) {
    const summaryContainer = document.getElementById('modalExceededSummary');
    if (!summaryContainer) return;
    
    let html = '';
    if (summary) {
        if (summary.imsd > 0) {
            html += `<span class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-800 rounded-full text-xs font-medium">
                        <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span>
                        IMSD: ${summary.imsd}
                    </span>`;
        }
        if (summary.incoming > 0) {
            html += `<span class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                        <span class="w-2 h-2 bg-blue-500 rounded-full mr-1.5"></span>
                        Incoming: ${summary.incoming}
                    </span>`;
        }
        if (summary.budget_officer > 0) {
            html += `<span class="inline-flex items-center px-3 py-1.5 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">
                        <span class="w-2 h-2 bg-purple-500 rounded-full mr-1.5"></span>
                        Budget: ${summary.budget_officer}
                    </span>`;
        }
        if (summary.outgoing > 0) {
            html += `<span class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span>
                        Outgoing: ${summary.outgoing}
                    </span>`;
        }
        if (summary.accountant > 0) {
            html += `<span class="inline-flex items-center px-3 py-1.5 bg-indigo-100 text-indigo-800 rounded-full text-xs font-medium">
                        <span class="w-2 h-2 bg-indigo-500 rounded-full mr-1.5"></span>
                        Accountant: ${summary.accountant}
                    </span>`;
        }
        // Use the summary values directly from backend
        if (summary.urgent > 0) {
            html += `<span class="inline-flex items-center px-3 py-1.5 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">
                        <span class="w-2 h-2 bg-purple-500 rounded-full mr-1.5"></span>
                        Already Urgent: ${summary.urgent}
                    </span>`;
        }
        if (summary.not_urgent > 0) {
            html += `<span class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">
                        <span class="w-2 h-2 bg-gray-500 rounded-full mr-1.5"></span>
                        Ready to Mark: ${summary.not_urgent}
                    </span>`;
        }
    }
    summaryContainer.innerHTML = html;
}

// Update quick stats in main dashboard
function updateQuickStats(summary) {
    const statsContainer = document.getElementById('exceededQuickStats');
    if (!statsContainer) return;
    
    let html = '<div class="flex flex-wrap gap-2">';
    if (summary) {
        if (summary.imsd > 0) {
            html += `<span class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 rounded-lg text-xs font-medium border border-red-200">
                        <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span>
                        IMSD: ${summary.imsd}
                    </span>`;
        }
        if (summary.incoming > 0) {
            html += `<span class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-xs font-medium border border-blue-200">
                        <span class="w-2 h-2 bg-blue-500 rounded-full mr-1.5"></span>
                        Incoming: ${summary.incoming}
                    </span>`;
        }
        if (summary.budget_officer > 0) {
            html += `<span class="inline-flex items-center px-3 py-1.5 bg-purple-50 text-purple-700 rounded-lg text-xs font-medium border border-purple-200">
                        <span class="w-2 h-2 bg-purple-500 rounded-full mr-1.5"></span>
                        Budget: ${summary.budget_officer}
                    </span>`;
        }
        if (summary.outgoing > 0) {
            html += `<span class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-700 rounded-lg text-xs font-medium border border-green-200">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span>
                        Outgoing: ${summary.outgoing}
                    </span>`;
        }
        if (summary.accountant > 0) {
            html += `<span class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-medium border border-indigo-200">
                        <span class="w-2 h-2 bg-indigo-500 rounded-full mr-1.5"></span>
                        Accountant: ${summary.accountant}
                    </span>`;
        }
        // Show urgent status in quick stats
        if (summary.urgent > 0) {
            html += `<span class="inline-flex items-center px-3 py-1.5 bg-purple-50 text-purple-700 rounded-lg text-xs font-medium border border-purple-200">
                        <span class="w-2 h-2 bg-purple-500 rounded-full mr-1.5"></span>
                        Urgent: ${summary.urgent}
                    </span>`;
        }
    }
    html += '</div>';
    statsContainer.innerHTML = html;
}

// Filter department
window.filterDepartment = function(filter) {
    currentFilter = filter;
    
    // Update tab styles
    ['all', 'imsd', 'incoming', 'budget', 'outgoing', 'accountant', 'urgent', 'not_urgent'].forEach(f => {
        const btn = document.getElementById(`filter-${f}`);
        if (btn) {
            if (f === filter) {
                btn.className = 'px-5 py-2.5 text-sm font-medium rounded-t-lg border-b-2 border-red-600 bg-red-50 text-red-700';
            } else {
                btn.className = 'px-5 py-2.5 text-sm font-medium rounded-t-lg border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-900';
            }
        }
    });
    
    applyFilterAndSearch();
};

// Search function
window.searchDocuments = function() {
    const searchInput = document.getElementById('searchDocuments');
    currentSearchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    applyFilterAndSearch();
};

// Clear search
window.clearSearch = function() {
    const searchInput = document.getElementById('searchDocuments');
    if (searchInput) {
        searchInput.value = '';
        currentSearchTerm = '';
        applyFilterAndSearch();
    }
};
function applyFilterAndSearch() {
    let filtered = [...allExceededDocuments];
    
    // Debug: Log all documents to see is_urgent values
    console.log('All documents:', allExceededDocuments.map(d => ({id: d.id, is_urgent: d.is_urgent, department: d.department})));
    
    // Apply department filter
    switch(currentFilter) {
        case 'imsd':
            filtered = filtered.filter(doc => doc.department === 'IMSD');
            break;
        case 'incoming':
            filtered = filtered.filter(doc => doc.department === 'Incoming');
            break;
        case 'budget':
            filtered = filtered.filter(doc => doc.department === 'Budget Officer');
            break;
        case 'outgoing':
            filtered = filtered.filter(doc => doc.department === 'Outgoing');
            break;
        case 'accountant':
            filtered = filtered.filter(doc => doc.department === 'Accountant');
            break;
        case 'urgent':
            filtered = filtered.filter(doc => doc.is_urgent === true);
            console.log('Urgent filter applied, found:', filtered.length);
            break;
        case 'not_urgent':
            filtered = filtered.filter(doc => doc.is_urgent === false);
            console.log('Not urgent filter applied, found:', filtered.length);
            break;
        default:
            // 'all' - no filter
            break;
    }
    
    // Apply search if there's a search term
    if (currentSearchTerm) {
        filtered = filtered.filter(doc => {
            return (
                (doc.control_number && doc.control_number.toLowerCase().includes(currentSearchTerm)) ||
                (doc.ors_number && doc.ors_number.toLowerCase().includes(currentSearchTerm)) ||
                (doc.program && doc.program.toLowerCase().includes(currentSearchTerm)) ||
                (doc.focal && doc.focal.toLowerCase().includes(currentSearchTerm)) ||
                (doc.status && doc.status.toLowerCase().includes(currentSearchTerm)) ||
                (doc.department && doc.department.toLowerCase().includes(currentSearchTerm))
            );
        });
    }
    
    renderDocumentsTable(filtered);
}
// Render documents table
function renderDocumentsTable(documents) {
    const tbody = document.getElementById('exceededDocumentsList');
    const resultCount = document.getElementById('searchResultCount');
    
    if (resultCount) {
        resultCount.textContent = documents.length;
    }
    
    if (!documents || documents.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-4 py-12 text-center">
                    <div class="flex flex-col items-center justify-center">
                        <svg class="w-16 h-16 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-gray-500 font-medium">No documents found</p>
                        <p class="text-gray-400 text-sm mt-1">
                            ${currentSearchTerm ? 'Try adjusting your search terms' : 'Try changing your filter'}
                        </p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    documents.forEach(doc => {
        // Ensure days is always positive using Math.abs() and floor
        const daysOld = Math.floor(Math.abs(doc.days_old || 0));
        
        const daysClass = daysOld >= 15 ? 'bg-red-100 text-red-800' : 
                         daysOld >= 12 ? 'bg-orange-100 text-orange-800' : 
                         'bg-yellow-100 text-yellow-800';
        
        let deptClass = 'bg-blue-100 text-blue-800';
        switch(doc.department) {
            case 'IMSD':
                deptClass = 'bg-red-100 text-red-800';
                break;
            case 'Incoming':
                deptClass = 'bg-blue-100 text-blue-800';
                break;
            case 'Budget Officer':
                deptClass = 'bg-purple-100 text-purple-800';
                break;
            case 'Outgoing':
                deptClass = 'bg-green-100 text-green-800';
                break;
            case 'Accountant':
                deptClass = 'bg-indigo-100 text-indigo-800';
                break;
        }
        
        const rowClass = daysOld >= 15 ? 'bg-red-50' : '';
        
        html += `
            <tr class="${rowClass} hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <input type="checkbox" 
                           class="doc-checkbox rounded border-gray-300 text-red-600 focus:ring-red-500 w-4 h-4" 
                           value="${doc.id}"
                           ${doc.is_urgent ? 'disabled' : ''}
                           onchange="updateSelectedCount()">
                    ${doc.is_urgent ? '<span class="ml-1 text-xs text-purple-600 font-medium">(urgent)</span>' : ''}
                </td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                    ${highlightSearchTerm(doc.control_number)}
                    ${doc.ors_number ? `<br><span class="text-xs text-gray-500">ORS: ${highlightSearchTerm(doc.ors_number)}</span>` : ''}
                </td>
                <td class="px-4 py-3 text-sm">
                    <span class="px-2.5 py-1.5 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">
                        ${highlightSearchTerm(doc.program)}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm">
                    <span class="px-2.5 py-1.5 ${daysClass} rounded-full text-xs font-medium">
                        ${daysOld} days
                    </span>
                </td>
                <td class="px-4 py-3 text-sm">
                    <span class="px-2.5 py-1.5 ${deptClass} rounded-full text-xs font-medium">
                        ${highlightSearchTerm(doc.department)}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm">
                    <span class="px-2.5 py-1.5 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">
                        ${highlightSearchTerm(doc.status)}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm font-semibold text-gray-900">${doc.amount}</td>
                <td class="px-4 py-3 text-sm text-gray-600">${highlightSearchTerm(doc.focal || 'N/A')}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Reset select all checkbox
    const selectAll = document.getElementById('selectAllDocs');
    if (selectAll) {
        selectAll.checked = false;
        selectAll.onchange = function(e) {
            document.querySelectorAll('.doc-checkbox:not(:disabled)').forEach(cb => {
                cb.checked = e.target.checked;
            });
            updateSelectedCount();
        };
    }
    
    updateSelectedCount();
}

// Helper function to highlight search terms
function highlightSearchTerm(text) {
    if (!currentSearchTerm || !text) return text;
    
    const regex = new RegExp(`(${currentSearchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-200 px-0.5 rounded">$1</mark>');
}

// Update selected count
function updateSelectedCount() {
    const selected = document.querySelectorAll('.doc-checkbox:checked').length;
    const countSpan = document.getElementById('selectedCount');
    if (countSpan) {
        countSpan.textContent = selected;
    }
}

// Submit Urgent Marking
window.submitUrgentMarking = function() {
    const selectedDocs = Array.from(document.querySelectorAll('.doc-checkbox:checked'))
        .map(cb => cb.value);
    
    if (selectedDocs.length === 0) {
        showNotification('Please select at least one document to mark as urgent', 'warning');
        return;
    }

    const remarks = document.getElementById('urgentRemarks').value;
    const submitBtn = event.target.closest('button');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<span class="animate-spin">⏳</span> Processing...';
    submitBtn.disabled = true;
    
    fetch('/admin/dashboard/mark-urgent', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            dv_request_ids: selectedDocs,
            remarks: remarks || 'Marked urgent from admin dashboard - Exceeded 10-day SLA'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeUrgentModal();
            // Refresh dashboard data
            if (typeof manualRefresh === 'function') {
                manualRefresh();
            }
            // Refresh data
            fetchAllExceededDocuments();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error marking documents as urgent', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('urgentModal');
    if (e.target === modal) {
        closeUrgentModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeUrgentModal();
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure DOM is fully loaded
    setTimeout(() => {
        fetchAllExceededDocuments();
    }, 100);
});

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