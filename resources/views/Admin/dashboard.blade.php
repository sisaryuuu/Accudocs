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



<!-- Welcome Card -->
<div class="bg-white rounded-xl shadow p-6 mb-6">
    <h2 class="text-2xl font-semibold text-gray-700 mb-2">
        Welcome, {{ Auth::user()->full_name }}
    </h2>
    <p class="text-gray-600">Email: <span class="font-medium">{{ Auth::user()->email }}</span></p>
    <p class="text-gray-600">Program: <span class="font-medium">{{ Auth::user()->program->name ?? 'N/A' }}</span></p>
</div>

<!-- KPI Cards -->
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
    
    <!-- Bottleneck Chart -->
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Top 5 Bottlenecks (Slowest Avg Processing Time)</h2>
        <div class="chart-container" style="height: 300px;">
            <canvas id="bottleneckChart"></canvas>
        </div>
    </div>
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

    // Staff Performance Chart
    const staffPerformanceCtx = document.getElementById('staffPerformanceChart').getContext('2d');
    charts.staffPerformance = new Chart(staffPerformanceCtx, {
        type: 'bar',
        data: {
            labels: @json($staffPerformance->pluck('staff_name')),
            datasets: [
                {
                    label: 'Total Processed',
                    data: @json($staffPerformance->pluck('total_processed')),
                    backgroundColor: '#3b82f6',
                    order: 2
                },
                {
                    label: 'Avg Hours',
                    data: @json($staffPerformance->pluck('avg_hours')),
                    backgroundColor: '#f59e0b',
                    type: 'line',
                    order: 1,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            ...commonChartOptions,
            scales: {
                ...commonChartOptions.scales,
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Total Processed'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Avg Hours'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });

    // Bottleneck Chart
    const bottleneckCtx = document.getElementById('bottleneckChart').getContext('2d');
    charts.bottleneck = new Chart(bottleneckCtx, {
        type: 'bar',
        data: {
            labels: @json($bottlenecks->pluck('staff_name')),
            datasets: [{
                label: 'Avg Processing Time (hours)',
                data: @json($bottlenecks->pluck('avg_hours')),
                backgroundColor: '#ef4444'
            }]
        },
        options: commonChartOptions
    });

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

    // Update average processing time
    const minutes = Math.round(data.averageProcessingTime || 0);
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    document.getElementById('kpi-avg-time').textContent = 
        hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;

    // Update all charts
    updateChartData(charts.requestsPerProgram, 
        Object.keys(data.requestsPerProgram || {}), 
        Object.values(data.requestsPerProgram || {})
    );

    updateChartData(charts.budgetPerProgram, 
        Object.keys(data.budgetPerProgram || {}), 
        Object.values(data.budgetPerProgram || {})
    );

    updateChartData(charts.documentsPerRole, 
        Object.keys(data.documentsPerRole || {}), 
        Object.values(data.documentsPerRole || {})
    );

    updateChartData(charts.monthlyRequests, 
        Object.keys(data.monthlyRequests || {}), 
        Object.values(data.monthlyRequests || {})
    );

    // Update complex charts
    if (data.staffPerformance) {
        charts.staffPerformance.data.labels = data.staffPerformance.map(d => d.staff_name);
        charts.staffPerformance.data.datasets[0].data = data.staffPerformance.map(d => d.total_processed);
        charts.staffPerformance.data.datasets[1].data = data.staffPerformance.map(d => d.avg_hours);
        charts.staffPerformance.update();
    }

    if (data.bottlenecks) {
        charts.bottleneck.data.labels = data.bottlenecks.map(d => d.staff_name);
        charts.bottleneck.data.datasets[0].data = data.bottlenecks.map(d => d.avg_hours);
        charts.bottleneck.update();
    }

    // Update insights
    updateInsights(data.insights || []);
    
    console.log('✅ Dashboard updated successfully');
}

function updateChartData(chart, labels, data) {
    if (chart && labels && data) {
        chart.data.labels = labels;
        chart.data.datasets[0].data = data;
        chart.update('none');
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

// Manual refresh function
async function manualRefresh() {
    try {
        showNotification('Refreshing dashboard data...', 'info');
        await fetchLatestData();
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