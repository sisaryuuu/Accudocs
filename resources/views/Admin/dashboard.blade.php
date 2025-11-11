@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<!-- Dashboard Header with Controls -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Dashboard Overview</h1>
        <p class="text-gray-600">Real-time monitoring of DV request activities</p>
    </div>
    <div class="flex space-x-3">
        <!-- Date Filter -->
        <div class="flex items-center space-x-2">
            <input type="date" id="startDate" class="border rounded px-3 py-2 text-sm">
            <span class="text-gray-500">to</span>
            <input type="date" id="endDate" class="border rounded px-3 py-2 text-sm">
            <button onclick="applyDateFilter()" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                Apply Filter
            </button>
            <button onclick="clearDateFilter()" class="bg-gray-500 text-white px-4 py-2 rounded text-sm hover:bg-gray-600">
                Clear
            </button>
        </div>
        
        <!-- Manual Refresh Button -->
        <button onclick="manualRefresh()" class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>Refresh Data</span>
        </button>
    </div>
</div>

<!-- KPI Cards - Inline -->
<div class="flex flex-col md:flex-row gap-4 mb-8">
    <div class="flex-1 bg-white p-4 rounded-lg shadow text-center">
        <h2 class="text-lg font-semibold">Total Requests</h2>
        <p class="text-2xl font-bold text-blue-600" id="kpi-total">{{ $totalRequests ?? 0 }}</p>
    </div>
    <div class="flex-1 bg-white p-4 rounded-lg shadow text-center">
        <h2 class="text-lg font-semibold">Pending</h2>
        <p class="text-2xl font-bold text-yellow-600" id="kpi-pending">{{ $pendingRequests }}</p>
    </div>
    <div class="flex-1 bg-white p-4 rounded-lg shadow text-center">
        <h2 class="text-lg font-semibold">Approved</h2>
        <p class="text-2xl font-bold text-green-600" id="kpi-approved">{{ $approvedRequests }}</p>
    </div>
    <div class="flex-1 bg-white p-4 rounded-lg shadow text-center">
        <h2 class="text-lg font-semibold">Rejected</h2>
        <p class="text-2xl font-bold text-red-600" id="kpi-rejected">{{ $rejectedRequests }}</p>
    </div>
    <div class="flex-1 bg-white p-4 rounded-lg shadow text-center">
        <h2 class="text-lg font-semibold">Avg. Processing Time</h2>
        <p class="text-2xl font-bold text-purple-600" id="kpi-avg-time">
            @php
                $minutes = round($averageProcessingTime);
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                echo $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
            @endphp
        </p>
    </div>
</div>
<!-- SLA Compliance Section -->
<div class="bg-white p-4 rounded-lg shadow mt-6">
    <h2 class="text-lg font-semibold mb-4">10-Working-Day SLA Compliance</h2>
    
    @if(isset($slaAnalysis) && $slaAnalysis['total_completed_requests'] > 0)
        <!-- SLA KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg border text-center">
                <h3 class="text-sm font-medium text-blue-800">Total Completed</h3>
                <p class="text-2xl font-bold text-blue-600">{{ $slaAnalysis['total_completed_requests'] }}</p>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg border text-center">
                <h3 class="text-sm font-medium text-green-800">Within SLA</h3>
                <p class="text-2xl font-bold text-green-600">{{ $slaAnalysis['within_sla'] }}</p>
            </div>
            
            <div class="bg-red-50 p-4 rounded-lg border text-center">
                <h3 class="text-sm font-medium text-red-800">SLA Violations</h3>
                <p class="text-2xl font-bold text-red-600">{{ $slaAnalysis['sla_violations'] }}</p>
            </div>
            
            <div class="bg-purple-50 p-4 rounded-lg border text-center">
                <h3 class="text-sm font-medium text-purple-800">Avg. Processing</h3>
                <p class="text-2xl font-bold text-purple-600">{{ $slaAnalysis['avg_working_days'] }} days</p>
            </div>
        </div>

        <!-- Compliance Rate -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">SLA Compliance Rate</span>
                <span class="text-sm font-bold {{ $slaAnalysis['violation_rate_percent'] == 0 ? 'text-green-600' : ($slaAnalysis['violation_rate_percent'] <= 10 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ 100 - $slaAnalysis['violation_rate_percent'] }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="h-4 rounded-full {{ $slaAnalysis['violation_rate_percent'] == 0 ? 'bg-green-500' : ($slaAnalysis['violation_rate_percent'] <= 10 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                     style="width: {{ 100 - $slaAnalysis['violation_rate_percent'] }}%">
                </div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>0%</span>
                <span>Target: 90%+</span>
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
    
    <!-- Staff Performance -->
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Accounting Staff Performance</h2>
        <div class="chart-container" style="height: 300px;">
            <canvas id="staffPerformanceChart"></canvas>
        </div>
    </div>
    
  <!-- Bottleneck Chart - Complete Roles -->
<div class="bg-white p-4 rounded-lg shadow">
    <h2 class="text-lg font-semibold mb-4">Workflow Bottlenecks by Role (Processing Time)</h2>
    
    <div class="chart-container" style="height: 300px;">
        <canvas id="bottleneckChart"></canvas>
    </div>

<!-- Insights -->
<div class="bg-white p-4 rounded-lg shadow mt-6">
    <h2 class="text-lg font-semibold mb-2">Insights & Highlights</h2>
    <ul class="list-disc list-inside text-gray-700" id="insights-list">
        @foreach($insights as $insight)
            <li>{{ $insight }}</li>
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
</style>
@endpush

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Pusher -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<script>
// Real-time Dashboard Configuration
const DashboardConfig = {
    pusherKey: '{{ env('PUSHER_APP_KEY') }}',
    pusherCluster: '{{ env('PUSHER_APP_CLUSTER') }}',
    refreshInterval: 30000, // 30 seconds fallback
};

// Chart instances
let charts = {};

document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

function initializeDashboard() {
    initializeCharts();
    initializeRealTimeUpdates();
}

function initializeCharts() {
    // Common chart options for consistent sizing
    const commonChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1000,
            easing: 'easeInOutQuart'
        },
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 0
                }
            },
            y: {
                beginAtZero: true
            }
        }
    };

    // Requests per Program Chart
    const requestsPerProgramCtx = document.getElementById('requestsPerProgram').getContext('2d');
    charts.requestsPerProgram = new Chart(requestsPerProgramCtx, {
        type: 'bar',
        data: {
            labels: @json($requestsPerProgram->keys()),
            datasets: [{
                label: 'Requests',
                data: @json($requestsPerProgram->values()),
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16']
            }]
        },
        options: commonChartOptions
    });

    // Budget per Program Chart
    const budgetPerProgramCtx = document.getElementById('budgetPerProgram').getContext('2d');
    charts.budgetPerProgram = new Chart(budgetPerProgramCtx, {
        type: 'bar',
        data: {
            labels: @json($budgetPerProgram->keys()),
            datasets: [{
                label: 'Budget (₱)',
                data: @json($budgetPerProgram->values()),
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16']
            }]
        },
        options: {
            ...commonChartOptions,
            plugins: {
                ...commonChartOptions.plugins,
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `₱${context.parsed.y.toLocaleString()}`;
                        }
                    }
                }
            }
        }
    });

    // Documents per Role Chart
    const documentsPerRoleCtx = document.getElementById('documentsPerRole').getContext('2d');
    charts.documentsPerRole = new Chart(documentsPerRoleCtx, {
        type: 'pie',
        data: {
            labels: @json($documentsPerRole->keys()),
            datasets: [{
                data: @json($documentsPerRole->values()),
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });

    // Monthly Requests Chart
    const monthlyRequestsCtx = document.getElementById('monthlyRequestsChart').getContext('2d');
    charts.monthlyRequests = new Chart(monthlyRequestsCtx, {
        type: 'line',
        data: {
            labels: @json($monthlyRequests->keys()),
            datasets: [{
                label: 'Requests',
                data: @json($monthlyRequests->values()),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: commonChartOptions
    });

    // Staff Performance Chart (Fixed)
    const staffPerformanceCtx = document.getElementById('staffPerformanceChart').getContext('2d');
    charts.staffPerformance = new Chart(staffPerformanceCtx, {
        type: 'line',
        data: {
            labels: @json($staffPerformance->pluck('month_label')),
            datasets: [{
                label: 'Average Requests Handled per Month',
                data: @json($staffPerformance->pluck('total_requests')),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.2)',
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Requests'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            }
        }
    });

    // Bottleneck Chart - Per Role (Fixed Initialization)
const bottleneckCtx = document.getElementById('bottleneckChart').getContext('2d');

// Handle the data safely
const bottleneckLabels = {!! json_encode($bottlenecks->pluck('role_name')) !!};
const bottleneckData = {!! json_encode($bottlenecks->pluck('avg_hours')) !!};
const bottleneckProcessed = {!! json_encode($bottlenecks->pluck('total_processed')) !!};

// Ensure we have valid data
const hasBottleneckData = bottleneckLabels.length > 0 && bottleneckData.some(val => val > 0);

if (hasBottleneckData) {
    charts.bottleneck = new Chart(bottleneckCtx, {
        type: 'bar',
        data: {
            labels: bottleneckLabels,
            datasets: [{
                label: 'Avg Processing Time (hours)',
                data: bottleneckData,
                backgroundColor: [
                    '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16'
                ],
                borderColor: [
                    '#dc2626', '#ea580c', '#d97706', '#ca8a04', '#65a30d'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            const index = context.dataIndex;
                            return `Requests Processed: ${bottleneckProcessed[index]}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Hours'
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0
                    }
                }
            }
        }
    });
} else {
    // Create empty chart with message
    charts.bottleneck = new Chart(bottleneckCtx, {
        type: 'bar',
        data: {
            labels: ['No data available'],
            datasets: [{
                label: 'Avg Processing Time (hours)',
                data: [0],
                backgroundColor: ['#6b7280'],
                borderColor: ['#374151'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            },
            scales: {
                y: { beginAtZero: true },
                x: { ticks: { maxRotation: 45, minRotation: 0 } }
            }
        }
    });
    
    // Add a message to the canvas
    setTimeout(() => {
        bottleneckCtx.font = '16px Arial';
        bottleneckCtx.fillStyle = '#6b7280';
        bottleneckCtx.textAlign = 'center';
        bottleneckCtx.fillText('Waiting for bottleneck data...', 
            bottleneckCtx.canvas.width / 2, 
            bottleneckCtx.canvas.height / 2
        );
    }, 100);
}

    // Add window resize handler to maintain chart sizes
    window.addEventListener('resize', function() {
        Object.values(charts).forEach(chart => {
            if (chart) {
                chart.resize();
            }
        });
    });
}

function initializeRealTimeUpdates() {
    try {
        console.log('🔄 Initializing real-time updates...');
        
        // Check if Pusher credentials are available
        if (!DashboardConfig.pusherKey || DashboardConfig.pusherKey === '') {
            console.warn('⚠️ Pusher credentials not configured, using fallback polling');
            setInterval(fetchLatestData, DashboardConfig.refreshInterval);
            return;
        }

        // Initialize Pusher
        const pusher = new Pusher(DashboardConfig.pusherKey, {
            cluster: DashboardConfig.pusherCluster,
            encrypted: true
        });

        // Subscribe to dashboard channel
        const channel = pusher.subscribe('dashboard');

        // Listen for dashboard updates
        channel.bind('dashboard.updated', function(data) {
            console.log('✅ Real-time dashboard update received:', data);
            updateDashboard(data.payload || data);
            showNotification('Dashboard updated in real-time', 'success');
        });

        // Connection status handlers
        pusher.connection.bind('connected', function() {
            console.log('✅ Pusher connected successfully');
        });

        pusher.connection.bind('disconnected', function() {
            console.warn('⚠️ Pusher disconnected');
        });

        pusher.connection.bind('failed', function() {
            console.error('❌ Pusher connection failed');
        });

        // Set up fallback polling
        setInterval(fetchLatestData, DashboardConfig.refreshInterval);

    } catch (error) {
        console.error('❌ Failed to initialize real-time updates:', error);
        // Fallback to polling only
        setInterval(fetchLatestData, DashboardConfig.refreshInterval);
    }
}

async function fetchLatestData() {
    try {
        console.log('🔄 Fetching latest dashboard data...');
        const response = await fetch('{{ route("admin.dashboard.data") }}');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            updateDashboard(result.data);
            console.log('✅ Dashboard data updated successfully');
        } else {
            throw new Error(result.message || 'Failed to fetch data');
        }
    } catch (error) {
        console.error('❌ Failed to fetch latest data:', error);
        showNotification('Failed to refresh data', 'error');
    }
}

function updateDashboard(data) {
    console.log('📊 Updating dashboard with new data...');
    
    if (!data) {
        console.error('❌ No data provided for update');
        return;
    }
    
    // Update KPI cards with animation
    animateValue('kpi-total', data.totalRequests || 0);
    animateValue('kpi-pending', data.pendingRequests || 0);
    animateValue('kpi-approved', data.approvedRequests || 0);
    animateValue('kpi-rejected', data.rejectedRequests || 0);


      updateSLACompliance(data.slaAnalysis || {});

    // Update average processing time
    const minutes = Math.round(data.averageProcessingTime || 0);
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    document.getElementById('kpi-avg-time').textContent = 
        hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;

    // Update basic charts safely
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

    // Update staff performance chart safely
    if (charts.staffPerformance && data.staffPerformance) {
        try {
            charts.staffPerformance.data.labels = data.staffPerformance.map(d => d.month_label || d.staff_name);
            charts.staffPerformance.data.datasets[0].data = data.staffPerformance.map(d => d.total_requests || d.total_processed || 0);
            charts.staffPerformance.update('none');
        } catch (error) {
            console.error('Error updating staff performance chart:', error);
        }
    }

    // FIXED: Update bottleneck chart safely - handle both role_name and staff_name
    if (charts.bottleneck && data.bottlenecks) {
        try {
            // Handle both role_name (new) and staff_name (old) for compatibility
            const labels = data.bottlenecks.map(d => d.role_name || d.staff_name);
            const hoursData = data.bottlenecks.map(d => d.avg_hours || 0);
            
            charts.bottleneck.data.labels = labels;
            charts.bottleneck.data.datasets[0].data = hoursData;
            charts.bottleneck.update('none');
            
            console.log('✅ Bottleneck chart updated:', { labels, hoursData });
        } catch (error) {
            console.error('Error updating bottleneck chart:', error);
            // If update fails, reinitialize the chart
            reinitializeBottleneckChart(data.bottlenecks);
        }
    }

    // Update insights
    updateInsights(data.insights || []);
    
    console.log('✅ Dashboard updated successfully');
}
function updateSLACompliance(slaData) {
    if (!slaData || !slaData.total_completed_requests || slaData.total_completed_requests === 0) {
        return;
    }
    
    // Update SLA KPIs if elements exist
    const elements = {
        'sla-total': slaData.total_completed_requests,
        'sla-within': slaData.within_sla,
        'sla-violations': slaData.sla_violations,
        'sla-avg-days': slaData.avg_working_days,
        'sla-compliance-rate': 100 - slaData.violation_rate_percent
    };
    
    Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            if (id === 'sla-compliance-rate') {
                element.textContent = `${value}%`;
            } else if (id === 'sla-avg-days') {
                element.textContent = `${value} days`;
            } else {
                element.textContent = value;
            }
        }
    });
    
    // Update progress bar
    const progressBar = document.querySelector('.h-4.rounded-full');
    const complianceRate = 100 - slaData.violation_rate_percent;
    
    if (progressBar) {
        progressBar.style.width = `${complianceRate}%`;
        
        // Update color based on compliance
        progressBar.className = 'h-4 rounded-full ' + (
            complianceRate >= 90 ? 'bg-green-500' :
            complianceRate >= 80 ? 'bg-yellow-500' :
            'bg-red-500'
        );
    }
    
    // Update worst performer alert
    const worstPerformerAlert = document.querySelector('.bg-yellow-50');
    if (worstPerformerAlert && slaData.worst_performer > 10) {
        const span = worstPerformerAlert.querySelector('span');
        if (span) {
            span.textContent = `Worst case: ${slaData.worst_performer} working days`;
        }
    }
}

// Add this function to handle chart reinitialization if needed
function reinitializeBottleneckChart(bottlenecksData) {
    try {
        const bottleneckCtx = document.getElementById('bottleneckChart').getContext('2d');
        
        // Destroy existing chart if it exists
        if (charts.bottleneck) {
            charts.bottleneck.destroy();
        }
        
        // Handle both role_name (new) and staff_name (old) for compatibility
        const labels = bottlenecksData.map(d => d.role_name || d.staff_name);
        const hoursData = bottlenecksData.map(d => d.avg_hours || 0);
        const processedData = bottlenecksData.map(d => d.total_processed || 0);
        
        charts.bottleneck = new Chart(bottleneckCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Avg Processing Time (hours)',
                    data: hoursData,
                    backgroundColor: [
                        '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16'
                    ],
                    borderColor: [
                        '#dc2626', '#ea580c', '#d97706', '#ca8a04', '#65a30d'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                return `Requests Processed: ${processedData[index]}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Hours'
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                }
            }
        });
        
        console.log('✅ Bottleneck chart reinitialized');
    }  catch (error) {
        console.error('❌ Failed to fetch latest data:', error);
        showNotification('Failed to refresh data', 'error');
        
        // Don't stop real-time updates on single failure
        console.log('🔄 Continuing real-time updates despite error...');
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
            li.textContent = text;
            li.className = 'fade-in';
            insightList.appendChild(li);
        });
    }
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
        
        // Easing function
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

function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.dashboard-notification');
    existingNotifications.forEach(el => {
        el.remove();
    });
    
    const notification = document.createElement('div');
    notification.className = `dashboard-notification fixed top-20 right-4 z-50 p-4 rounded-lg shadow-lg text-white max-w-sm transform transition-transform duration-300 translate-x-0`;
    
    const backgroundColor = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
        warning: 'bg-yellow-500'
    }[type] || 'bg-blue-500';
    
    notification.className += ` ${backgroundColor}`;
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <span class="flex-1">${message}</span>
            <button onclick="this.closest('.dashboard-notification').remove()" class="ml-4 text-white hover:text-gray-200 flex-shrink-0">
                &times;
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.transition = 'all 0.3s ease-in-out';
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, 3000);
}

// Manual refresh function - FIXED
async function manualRefresh() {
    try {
        showNotification('Refreshing dashboard data...', 'info');
        
        // Option 1: Full page reload (simplest)
        location.reload();
        
    } catch (error) {
        console.error('❌ Manual refresh failed:', error);
        showNotification('Refresh failed. Please try again.', 'error');
    }
}

async function applyDateFilter() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        showNotification('Please select both start and end dates', 'warning');
        return;
    }
    
    try {
        showNotification('Applying date filter...', 'info');
        
        const response = await fetch(`{{ route('admin.dashboard.filter') }}?start_date=${startDate}&end_date=${endDate}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            updateDashboard(result.data);
            showNotification('Date filter applied successfully', 'success');
        } else {
            throw new Error(result.message || 'Failed to apply filter');
        }
    } catch (error) {
        console.error('❌ Filter application failed:', error);
        showNotification('Filter failed to apply', 'error');
    }
}

function clearDateFilter() {
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    fetchLatestData();
    showNotification('Filters cleared', 'info');
}

// Export functions for global access
window.manualRefresh = manualRefresh;
window.applyDateFilter = applyDateFilter;
window.clearDateFilter = clearDateFilter;

console.log('🚀 Dashboard initialized');
</script>