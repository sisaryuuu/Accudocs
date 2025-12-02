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
        <p class="text-2xl font-bold text-yellow-600" id="kpi-pending">{{ $pendingRequests ?? 0 }}</p>
    </div>
    <div class="flex-1 bg-white p-4 rounded-lg shadow text-center">
        <h2 class="text-lg font-semibold">Approved</h2>
        <p class="text-2xl font-bold text-green-600" id="kpi-approved">{{ $approvedRequests ?? 0 }}</p>
    </div>
    <div class="flex-1 bg-white p-4 rounded-lg shadow text-center">
        <h2 class="text-lg font-semibold">Rejected</h2>
        <p class="text-2xl font-bold text-red-600" id="kpi-rejected">{{ $rejectedRequests ?? 0 }}</p>
    </div>
    <div class="flex-1 bg-white p-4 rounded-lg shadow text-center">
       <h3 class="text-sm font-medium text-purple-800">Average Processing Time</h3>
                <p class="text-2xl font-bold text-purple-600" id="sla-avg-days">{{ round($slaAnalysis['avg_working_days'], 1) }} days</p>
    </div>
</div>

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
       <!-- Replace the existing Compliance Rate section with this enhanced version -->
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

    // Initialize all main charts
    initRequestsPerProgramChart();
    initBudgetPerProgramChart();
    initDocumentsPerRoleChart();
    initMonthlyRequestsChart();
    initBottleneckChart();
    initStaffPerformanceChart();

    // Add window resize handler to maintain chart sizes
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
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Programs',   // X-axis label
                        font: {
                            size: 14,
                        }
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Number of Requests', // Y-axis label
                        font: {
                            size: 14,
                        }
                    },
                    beginAtZero: true
                }
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
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `₱${context.parsed.y.toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Program',
                        font: {
                            size: 14,

                        },
                        padding: {top: 10}
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Amount (₱)',
                        font: {
                            size: 14,
                        },
                        padding: {bottom: 10}
                    },
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return '₱' + (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return '₱' + (value / 1000).toFixed(1) + 'K';
                            }
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
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
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
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Month',
                        font: {
                            size: 14,
                        },
                        padding: {top: 10}
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Requests',
                        font: {
                            size: 14,

                        },
                        padding: {bottom: 10}
                    },
                    beginAtZero: true,
                    ticks: {
                        precision: 0 // Only show whole numbers
                    }
                }
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
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.parsed.y.toFixed(1)} hours`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Roles',
                            font: {
                                size: 14,

                            },
                            padding: {top: 10}
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Hours',
                            font: {
                                size: 14,
                            },
                            padding: {bottom: 10}
                        },
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1) + ' hrs';
                            }
                        }
                    }
                }
            }
        });
    } else {
        // Create empty state
        ctx.getContext('2d').font = '16px Arial';
        ctx.getContext('2d').fillStyle = '#6b7280';
        ctx.getContext('2d').textAlign = 'center';
        ctx.getContext('2d').fillText('No bottleneck data available', ctx.width / 2, ctx.height / 2);
    }
}

function initStaffPerformanceChart() {
    const ctx = document.getElementById('staffPerformanceChart');
    if (!ctx) return;
    
    const staffData = @json($staffPerformance['individual_metrics'] ?? []);
    const workloadData = @json($staffPerformance['workload_by_role'] ?? []);
    
    if (staffData.length > 0) {
        updateStaffChartByView('productivity', staffData, workloadData);
    } else {
        // Create empty state
        ctx.getContext('2d').font = '16px Arial';
        ctx.getContext('2d').fillStyle = '#6b7280';
        ctx.getContext('2d').textAlign = 'center';
        ctx.getContext('2d').fillText('No staff performance data available', ctx.width / 2, ctx.height / 2);
    }
}

// Staff View Functions
function getActiveStaffView() {
    if (document.getElementById('btn-productivity').classList.contains('bg-blue-600')) {
        return 'productivity';
    } else if (document.getElementById('btn-efficiency').classList.contains('bg-blue-600')) {
        return 'efficiency';
    } else if (document.getElementById('btn-workload').classList.contains('bg-blue-600')) {
        return 'workload';
    }
    return 'productivity'; // default
}

function updateStaffChartByView(view, staffData, workloadData) {
    const ctx = document.getElementById('staffPerformanceChart');
    if (!ctx) return;
    
    // Destroy existing chart
    if (staffCharts.productivity) {
        staffCharts.productivity.destroy();
    }
    
    if (view === 'productivity') {
        // Productivity View
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
                    title: { display: true, text: 'Top 10 Unit by Productivity' }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Accounting Unit',
                            font: {
                                size: 14,
                            },
                            padding: {top: 10}
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Documents Processed',
                            font: {
                                size: 14,

                            },
                            padding: {bottom: 10}
                        },
                        beginAtZero: true,
                        ticks: {
                            precision: 0 // Only show whole numbers
                        }
                    }
                }
            }
        });
        
    } else if (view === 'efficiency') {
        // Efficiency View
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
                    title: { display: true, text: 'Top 10 Most Efficient Unit' }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Accounting Unit',
                            font: {
                                size: 14,

                            },
                            padding: {top: 10}
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Hours',
                            font: {
                                size: 14,

                            },
                            padding: {bottom: 10}
                        },
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1) + ' hrs';
                            }
                        }
                    }
                }
            }
        });
        
    } else if (view === 'workload') {
        // Workload Distribution View
        const labels = workloadData.map(w => w.role || 'Unknown');
        const workloadCounts = workloadData.map(w => w.total_documents || 0);
        
        staffCharts.productivity = new Chart(ctx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: workloadCounts,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    title: { 
                        display: true, 
                        text: 'Workload Distribution by Role',
                        padding: {
                            bottom: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} documents (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
}
function toggleStaffView(view) {
    const ctx = document.getElementById('staffPerformanceChart');
    if (!ctx) return;
    
    // Update button states
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

// Make toggle function available globally
window.toggleStaffView = toggleStaffView;

function updateStaffPerformanceCharts(data) {
    if (data && data.staffPerformance) {
        // Destroy existing staff chart if it exists
        if (staffCharts.productivity) {
            staffCharts.productivity.destroy();
        }
        
        const staffData = data.staffPerformance.individual_metrics || [];
        const workloadData = data.staffPerformance.workload_by_role || [];
        const ctx = document.getElementById('staffPerformanceChart');
        
        if (!ctx || staffData.length === 0) return;
        
        // Get current active view
        const activeView = getActiveStaffView();
        updateStaffChartByView(activeView, staffData, workloadData);
    }
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
    
    // Update KPI cards with animation - with null checks
    safelyAnimateValue('kpi-total', data.totalRequests || 0);
    safelyAnimateValue('kpi-pending', data.pendingRequests || 0);
    safelyAnimateValue('kpi-approved', data.approvedRequests || 0);
    safelyAnimateValue('kpi-rejected', data.rejectedRequests || 0);

    // Update SLA compliance - check if SLA data exists
   if (data.slaAnalysis) {
        // Animate the rate percentage change using animateRateChange
        const newRate = 100 - (data.slaAnalysis.violation_rate_percent || 0);
        safelyAnimateValue('sla-compliance-rate-text', newRate);
        
        // Update all SLA elements
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

        // Update staff performance chart safely
        if (data.staffPerformance) {
            updateStaffPerformanceCharts(data);
        }

        // Update bottleneck chart safely
        if (charts.bottleneck && data.bottlenecks) {
            try {
                const labels = data.bottlenecks.map(d => d.role_name || d.staff_name);
                const hoursData = data.bottlenecks.map(d => d.avg_hours || 0);
                
                charts.bottleneck.data.labels = labels;
                charts.bottleneck.data.datasets[0].data = hoursData;
                charts.bottleneck.update('none');
            } catch (error) {
                console.error('Error updating bottleneck chart:', error);
            }
        }


        // Update insights
        updateInsights(data.insights || []);
        
    } catch (error) {
        console.error('Error updating charts:', error);
    }
    
    console.log('✅ Dashboard updated successfully');
}

// Helper function to safely animate values
function safelyAnimateValue(elementId, newValue) {
    const element = document.getElementById(elementId);
    if (!element) {
        console.warn(`Element with ID ${elementId} not found`);
        return;
    }
    animateValue(elementId, newValue);
}

// Also add this helper function at the top level
function safeUpdateElement(id, value, isHTML = false) {
    const element = document.getElementById(id);
    if (element) {
        if (isHTML) {
            element.innerHTML = value;
        } else {
            element.textContent = value;
        }
    } else {
        console.warn(`Element ${id} not found`);
    }
}

function updateSLACompliance(slaData) {
    if (!slaData || !slaData.total_completed_requests || slaData.total_completed_requests === 0) {
        console.warn('No SLA data available');
        return;
    }
    
    // Calculate on-time rate
    const onTimeRate = 100 - (slaData.violation_rate_percent || 0);
    const targetRate = 75;
    
    // Update basic elements
    const elements = {
        'sla-total': { value: slaData.total_completed_requests, isHTML: false },
        'sla-within': { value: slaData.within_sla, isHTML: false },
        'sla-violations': { value: slaData.sla_violations, isHTML: false },
        'sla-avg-days': { value: `${parseFloat(slaData.avg_working_days).toFixed(1)} days`, isHTML: false },
        'sla-compliance-rate-text': { value: `${onTimeRate.toFixed(1)}%`, isHTML: false }
    };
    
    Object.entries(elements).forEach(([id, config]) => {
        safeUpdateElement(id, config.value, config.isHTML);
    });
    
    // Update progress bar
    const progressBar = document.getElementById('sla-progress-bar');
    if (progressBar) {
        // Get previous width
        const prevWidth = parseFloat(progressBar.style.width) || 0;
        
        // Update with animation
        progressBar.style.width = `${onTimeRate}%`;
        
        // Update color based on compliance
        let bgColor;
        let textColor;
        
        if (onTimeRate >= 90) {
            bgColor = '#10b981'; // Green
            textColor = 'rate-excellent';
        } else if (onTimeRate >= targetRate) {
            bgColor = '#3b82f6'; // Blue
            textColor = 'rate-good';
        } else if (onTimeRate >= 60) {
            bgColor = '#f59e0b'; // Orange
            textColor = 'rate-warning';
        } else {
            bgColor = '#ef4444'; // Red
            textColor = 'rate-poor';
        }
        
        progressBar.style.backgroundColor = bgColor;
        
        // Add animation class
        progressBar.classList.add('progress-update');
        setTimeout(() => {
            progressBar.classList.remove('progress-update');
        }, 1000);
        
        // Add visual feedback for improvement/decline
        if (onTimeRate > prevWidth) {
            showRateChange('improvement', onTimeRate - prevWidth);
        } else if (onTimeRate < prevWidth) {
            showRateChange('decline', prevWidth - onTimeRate);
        }
    }
    
    // Update compliance status badge
    updateComplianceStatusBadge(onTimeRate, targetRate);
    
    // Update worst performer if exists
    if (slaData.worst_performer > 10) {
        updateWorstPerformer(slaData.worst_performer);
    }
}

function updateComplianceStatusBadge(onTimeRate, targetRate) {
    const statusElement = document.getElementById('sla-compliance-status');
    if (!statusElement) return;
    
    let statusText, bgClass, textClass;
    
    if (onTimeRate >= 90) {
        statusText = 'Excellent';
        bgClass = 'bg-green-100';
        textClass = 'text-green-800';
    } else if (onTimeRate >= targetRate) {
        statusText = 'Target Met';
        bgClass = 'bg-blue-100';
        textClass = 'text-blue-800';
    } else if (onTimeRate >= 60) {
        statusText = 'Needs Improvement';
        bgClass = 'bg-yellow-100';
        textClass = 'text-yellow-800';
    } else {
        statusText = 'Below Target';
        bgClass = 'bg-red-100';
        textClass = 'text-red-800';
    }
    
    // Add change animation
    statusElement.classList.add('status-change');
    
    statusElement.innerHTML = `
        <span class="${bgClass} ${textClass} px-2 py-1 rounded-full text-xs font-medium">
            ${statusText}
        </span>
    `;
    
    // Remove animation class after animation completes
    setTimeout(() => {
        statusElement.classList.remove('status-change');
    }, 500);
}

function showRateChange(type, amount) {
    if (Math.abs(amount) < 0.1) return; // Ignore very small changes
    
    const progressBar = document.getElementById('sla-progress-bar');
    if (!progressBar) return;
    
    // Create change indicator
    const changeIndicator = document.createElement('div');
    changeIndicator.className = `absolute -top-8 text-xs font-bold whitespace-nowrap ${
        type === 'improvement' ? 'text-green-600' : 'text-red-600'
    }`;
    changeIndicator.innerHTML = `${type === 'improvement' ? '↑' : '↓'} ${Math.abs(amount).toFixed(1)}%`;
    changeIndicator.style.left = '50%';
    changeIndicator.style.transform = 'translateX(-50%)';
    
    progressBar.parentElement.style.position = 'relative';
    progressBar.parentElement.appendChild(changeIndicator);
    
    // Animate and remove
    setTimeout(() => {
        changeIndicator.style.transition = 'all 0.5s ease-out';
        changeIndicator.style.opacity = '0';
        changeIndicator.style.transform = 'translateX(-50%) translateY(-10px)';
        
        setTimeout(() => {
            if (changeIndicator.parentNode) {
                changeIndicator.parentNode.removeChild(changeIndicator);
            }
        }, 500);
    }, 1500);
}

function updateWorstPerformer(days) {
    const worstCaseContainer = document.querySelector('.bg-yellow-50');
    if (!worstCaseContainer) {
        // Create warning container if it doesn't exist
        const slaContainer = document.querySelector('.bg-white.p-4.rounded-lg.shadow.mt-6');
        if (slaContainer && days > 10) {
            const warningDiv = document.createElement('div');
            warningDiv.className = 'bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4';
            warningDiv.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-yellow-800 font-medium">
                        Attention: Worst case processing time is ${days} working days
                    </span>
                </div>
            `;
            slaContainer.appendChild(warningDiv);
        }
    } else if (days > 10) {
        // Update existing warning
        const span = worstCaseContainer.querySelector('span');
        if (span) {
            span.textContent = `Attention: Worst case processing time is ${days} working days`;
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
            li.innerHTML = text; // Use innerHTML instead of textContent
            li.className = 'fade-in';
            insightList.appendChild(li);
        });
    }
}

function animateRateChange(elementId, newValue) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    // Extract current value from element text
    const currentText = element.textContent.trim();
    const currentValue = parseFloat(currentText.replace('%', '')) || 0;
    
    const duration = 800;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function for smooth animation
        let easeOut;
        if (progress < 0.5) {
            easeOut = 2 * progress * progress;
        } else {
            easeOut = -1 + (4 - 2 * progress) * progress;
        }
        
        const value = currentValue + (newValue - currentValue) * easeOut;
        element.textContent = value.toFixed(1) + '%';
        
        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            element.textContent = newValue.toFixed(1) + '%';
        }
    }
    
    requestAnimationFrame(update);
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
        location.reload();
    } catch (error) {
        console.error('❌ Manual refresh failed:', error);
        showNotification('Refresh failed. Please try again.', 'error');
    }
}

// Export functions for global access
window.manualRefresh = manualRefresh;

console.log('🚀 Dashboard initialized');
</script>
