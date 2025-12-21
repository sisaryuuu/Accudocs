@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
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
        <p class="text-2xl font-bold text-blue-600" id="kpi-total">{{ $totalRequests ?? 0 }}</p>
        <p class="text-xs text-gray-500 mt-1 group-hover:text-blue-600">Click for details</p>
    </div>
    <div onclick="openKPIModal('pending')" class="flex-1 bg-white p-4 rounded-lg shadow text-center cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-yellow-50 group">
        <h2 class="text-lg font-semibold text-gray-700 group-hover:text-yellow-700">Pending</h2>
        <p class="text-2xl font-bold text-yellow-600" id="kpi-pending">{{ $pendingRequests ?? 0 }}</p>
        <p class="text-xs text-gray-500 mt-1 group-hover:text-yellow-600">Click for details</p>
    </div>
    <div onclick="openKPIModal('approved')" class="flex-1 bg-white p-4 rounded-lg shadow text-center cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-green-50 group">
        <h2 class="text-lg font-semibold text-gray-700 group-hover:text-green-700">Approved</h2>
        <p class="text-2xl font-bold text-green-600" id="kpi-approved">{{ $approvedRequests ?? 0 }}</p>
        <p class="text-xs text-gray-500 mt-1 group-hover:text-green-600">Click for details</p>
    </div>
    <div onclick="openKPIModal('rejected')" class="flex-1 bg-white p-4 rounded-lg shadow text-center cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-xl hover:bg-red-50 group">
        <h2 class="text-lg font-semibold text-gray-700 group-hover:text-red-700">Rejected</h2>
        <p class="text-2xl font-bold text-red-600" id="kpi-rejected">{{ $rejectedRequests ?? 0 }}</p>
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

<!-- Rest of your dashboard content (SLA, Charts, etc.) -->
<!-- ... (keep all your existing sections) ... -->

<!-- SLA Compliance Section -->
<div class="bg-white p-4 rounded-lg shadow mt-6">
    <h2 class="text-lg font-semibold mb-4">10-Working-Day Processing Time Summary</h2>
    
    @if(isset($slaAnalysis) && $slaAnalysis['total_completed_requests'] > 0)
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

<!-- Charts Section -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Requests per Program -->
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Requests per Program</h2>
        <div class="chart-container" style="height: 300px;">
            <canvas id="requestsPerProgram"></canvas>
        </div>
    </div>
    
    <!-- Budget per Program -->
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Total Budget Obligated per Program</h2>
        <div class="chart-container" style="height: 300px;">
            <canvas id="budgetPerProgram"></canvas>
        </div>
    </div>
    
    <!-- Documents per Role -->
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Documents Handled per Role</h2>
        <div class="chart-container" style="height: 300px;">
            <canvas id="documentsPerRole"></canvas>
        </div>
    </div>
    
    <!-- Monthly Requests Trend -->
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Monthly Requests Trend</h2>
        <div class="chart-container" style="height: 300px;">
            <canvas id="monthlyRequestsChart"></canvas>
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
</div>

<!-- Bottleneck Chart -->
<div class="bg-white p-4 rounded-lg shadow mb-8">
    <h2 class="text-lg font-semibold mb-4">Workflow Bottlenecks by Role (Processing Time)</h2>
    <div class="chart-container" style="height: 300px;">
        <canvas id="bottleneckChart"></canvas>
    </div>
</div>

<!-- Insights -->
<div class="bg-white p-4 rounded-lg shadow mt-6">
    <h2 class="text-lg font-semibold mb-2">Insights & Highlights</h2>
    <ul class="list-disc list-inside text-gray-700" id="insights-list">
        @foreach($insights as $insight)
            <li>{!! $insight !!}</li>
        @endforeach
    </ul>
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
</style>
@endpush

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Pusher -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<script>
// Store current KPI data globally for modal access
let currentKPIData = {
    total: {{ $totalRequests ?? 0 }},
    pending: {{ $pendingRequests ?? 0 }},
    approved: {{ $approvedRequests ?? 0 }},
    rejected: {{ $rejectedRequests ?? 0 }},
    processing: {{ round($slaAnalysis['avg_working_days'] ?? 0, 1) }},
    requestsPerProgram: @json($requestsPerProgram ?? []),
    monthlyRequests: @json($monthlyRequests ?? []),
    slaAnalysis: @json($slaAnalysis ?? [])
};

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
    
    switch(type) {
        case 'total':
            return `
                <div class="space-y-6">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-blue-900 mb-2">Summary</h4>
                        <p class="text-3xl font-bold text-blue-600">${data.total}</p>
                        <p class="text-sm text-gray-600 mt-1">Total DV requests in the system</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white border-2 border-blue-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600">Pending</p>
                            <p class="text-2xl font-bold text-yellow-600">${data.pending}</p>
                            <p class="text-xs text-gray-500">${((data.pending/data.total)*100).toFixed(1)}% of total</p>
                        </div>
                        <div class="bg-white border-2 border-blue-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600">Approved</p>
                            <p class="text-2xl font-bold text-green-600">${data.approved}</p>
                            <p class="text-xs text-gray-500">${((data.approved/data.total)*100).toFixed(1)}% of total</p>
                        </div>
                        <div class="bg-white border-2 border-blue-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600">Rejected</p>
                            <p class="text-2xl font-bold text-red-600">${data.rejected}</p>
                            <p class="text-xs text-gray-500">${((data.rejected/data.total)*100).toFixed(1)}% of total</p>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-semibold mb-3">Requests by Program</h4>
                        <div class="space-y-2">
                            ${Object.entries(data.requestsPerProgram).map(([program, count]) => `
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                    <span class="font-medium">${program}</span>
                                    <div class="flex items-center space-x-3">
                                        <span class="text-2xl font-bold text-blue-600">${count}</span>
                                        <div class="w-32 bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: ${(count/data.total)*100}%"></div>
                                        </div>
                                        <span class="text-sm text-gray-600">${((count/data.total)*100).toFixed(1)}%</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-semibold mb-3">Monthly Trend</h4>
                        <canvas id="modalTrendChart" style="max-height: 250px;"></canvas>
                    </div>
                </div>
            `;
            
        case 'pending':
            return `
                <div class="space-y-6">
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-yellow-900 mb-2">Pending Requests</h4>
                        <p class="text-3xl font-bold text-yellow-600">${data.pending}</p>
                        <p class="text-sm text-gray-600 mt-1">Requests awaiting processing</p>
                    </div>
                    
                    <div class="bg-white border-2 border-yellow-200 p-4 rounded-lg">
                        <h4 class="font-semibold mb-3">Status Distribution</h4>
                        <div class="space-y-3">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>Pending Rate</span>
                                    <span class="font-bold">${((data.pending/data.total)*100).toFixed(1)}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="bg-yellow-500 h-3 rounded-full" style="width: ${(data.pending/data.total)*100}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="font-semibold mb-2 text-blue-900">Action Required</h4>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                            <li>Review and prioritize pending requests</li>
                            <li>Identify bottlenecks in workflow</li>
                            <li>Allocate resources for faster processing</li>
                            <li>Monitor SLA compliance for pending items</li>
                        </ul>
                    </div>
                </div>
            `;
            
        case 'approved':
            return `
                <div class="space-y-6">
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-green-900 mb-2">Approved Requests</h4>
                        <p class="text-3xl font-bold text-green-600">${data.approved}</p>
                        <p class="text-sm text-gray-600 mt-1">Successfully processed and released</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white border-2 border-green-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-2">Approval Rate</p>
                            <p class="text-3xl font-bold text-green-600">${((data.approved/data.total)*100).toFixed(1)}%</p>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: ${(data.approved/data.total)*100}%"></div>
                            </div>
                        </div>
                        <div class="bg-white border-2 border-green-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-2">Success Rate</p>
                            <p class="text-3xl font-bold text-green-600">${((data.approved/(data.approved+data.rejected))*100).toFixed(1)}%</p>
                            <p class="text-xs text-gray-500 mt-1">Approved vs Total Processed</p>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h4 class="font-semibold mb-2 text-green-900">Performance Highlights</h4>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                            <li>Strong approval rate indicates efficient processing</li>
                            <li>Maintaining quality standards in document review</li>
                            <li>Effective collaboration across departments</li>
                            <li>Continue monitoring for sustained performance</li>
                        </ul>
                    </div>
                </div>
            `;
            
        case 'rejected':
            return `
                <div class="space-y-6">
                    <div class="bg-red-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-red-900 mb-2">Rejected Requests</h4>
                        <p class="text-3xl font-bold text-red-600">${data.rejected}</p>
                        <p class="text-sm text-gray-600 mt-1">Requests that did not meet requirements</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white border-2 border-red-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-2">Rejection Rate</p>
                            <p class="text-3xl font-bold text-red-600">${((data.rejected/data.total)*100).toFixed(1)}%</p>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-red-500 h-2 rounded-full" style="width: ${(data.rejected/data.total)*100}%"></div>
                            </div>
                        </div>
                        <div class="bg-white border-2 border-red-200 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-2">Impact</p>
                            <p class="text-xl font-bold text-gray-700">${data.approved + data.rejected}</p>
                            <p class="text-xs text-gray-500 mt-1">Total Processed (Approved + Rejected)</p>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-500">
                        <h4 class="font-semibold mb-2 text-yellow-900">Improvement Opportunities</h4>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                            <li>Analyze common rejection reasons</li>
                            <li>Provide training to reduce submission errors</li>
                            <li>Improve documentation guidelines</li>
                            <li>Implement pre-submission validation checks</li>
                            <li>Follow up with focal persons for corrective actions</li>
                        </ul>
                    </div>
                </div>
            `;
            
        case 'processing':
            const sla = data.slaAnalysis;
            return `
                <div class="space-y-6">
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-purple-900 mb-2">Average Processing Time</h4>
                        <p class="text-3xl font-bold text-purple-600">${data.processing} days</p>
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
                            <p class="text-2xl font-bold text-green-600">${sla.within_sla || 0}</p>
                            <p class="text-xs text-gray-500 mt-1">${sla.total_completed_requests ? ((sla.within_sla/sla.total_completed_requests)*100).toFixed(1) : 0}% compliance</p>
                        </div>
                        <div class="bg-white border-2 border-purple-200 p-4 rounded-lg text-center">
                            <p class="text-sm text-gray-600">Exceeded SLA</p>
                            <p class="text-2xl font-bold text-red-600">${sla.sla_violations || 0}</p>
                            <p class="text-xs text-gray-500 mt-1">${sla.violation_rate_percent?.toFixed(1) || 0}% violations</p>
                        </div>
                    </div>
                    
                    <div class="bg-white border-2 border-gray-200 p-4 rounded-lg">
                        <h4 class="font-semibold mb-3">Processing Time Breakdown</h4>
                        <div class="space-y-3">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>Current Performance</span>
                                    <span class="font-bold">${data.processing} days</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="bg-purple-500 h-3 rounded-full transition-all" style="width: ${Math.min((data.processing/10)*100, 100)}%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>0 days</span>
                                    <span>10 days (Target)</span>
                                </div>
                            </div>
                            ${sla.worst_performer ? `
                            <div class="mt-4 pt-4 border-t">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-red-600 font-medium">Worst Case</span>
                                    <span class="font-bold text-red-600">${sla.worst_performer} days</span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1">Longest processing time recorded</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="${data.processing <= 10 ? 'bg-green-50 border-green-500' : 'bg-red-50 border-red-500'} p-4 rounded-lg border-l-4">
                        <h4 class="font-semibold mb-2 ${data.processing <= 10 ? 'text-green-900' : 'text-red-900'}">
                            ${data.processing <= 10 ? 'Performance Status: Excellent' : 'Performance Status: Needs Improvement'}
                        </h4>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                            ${data.processing <= 10 ? `
                                <li>Currently meeting SLA targets</li>
                                <li>Efficient workflow management</li>
                                <li>Continue monitoring for consistency</li>
                            ` : `
                                <li>Exceeding target processing time</li>
                                <li>Review workflow bottlenecks</li>
                                <li>Implement process improvements</li>
                                <li>Allocate additional resources if needed</li>
                            `}
                        </ul>
                    </div>
                </div>
            `;
            
        default:
            return '<p>No data available</p>';
    }
}

function exportKPIData() {
    // Simple CSV export functionality
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
    
    const bottlenecks = @json($bottlenecks ?? []);
    
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
    
    const staffData = @json($staffPerformance['individual_metrics'] ?? []);
    const workloadData = @json($staffPerformance['workload_by_role'] ?? []);
    
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
    
    const staffData = @json($staffPerformance['individual_metrics'] ?? []);
    const workloadData = @json($staffPerformance['workload_by_role'] ?? []);
    
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

function updateDashboard(data) {
    if (!data) return;
    
    // Update global KPI data
    currentKPIData = {
        total: data.totalRequests || 0,
        pending: data.pendingRequests || 0,
        approved: data.approvedRequests || 0,
        rejected: data.rejectedRequests || 0,
        processing: data.averageProcessingTime || 0,
        requestsPerProgram: data.requestsPerProgram || {},
        monthlyRequests: data.monthlyRequests || {},
        slaAnalysis: data.slaAnalysis || {}
    };
    
    safelyAnimateValue('kpi-total', data.totalRequests || 0);
    safelyAnimateValue('kpi-pending', data.pendingRequests || 0);
    safelyAnimateValue('kpi-approved', data.approvedRequests || 0);
    safelyAnimateValue('kpi-rejected', data.rejectedRequests || 0);

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