@extends('layouts.app')
@vite(['resources/css/app.css','resources/js/app.js'])
@section('title', 'Admin Dashboard')

@section('content')
<!-- Welcome Card -->
<div class="bg-white rounded-xl shadow p-6 mb-6">
    <h2 class="text-2xl font-semibold text-gray-700 mb-2">
        Welcome, {{ Auth::user()->full_name }}
    </h2>
    <p class="text-gray-600">Email: <span class="font-medium">{{ Auth::user()->email }}</span></p>
    <p class="text-gray-600">Program: <span class="font-medium">{{ Auth::user()->program->name ?? 'N/A' }}</span></p>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-4 rounded-lg shadow text-center">
        <h2 class="text-lg font-semibold">Total Requests</h2>
        <p class="text-2xl font-bold text-blue-600">{{ $totalRequests ?? 0 }}</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow text-center">
        <h2 class="text-lg font-semibold">Pending</h2>
        <p class="text-2xl font-bold text-yellow-600">{{ $pendingRequests }}</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow text-center">
        <h2 class="text-lg font-semibold">Approved</h2>
        <p class="text-2xl font-bold text-green-600">{{ $approvedRequests }}</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow text-center">
        <h2 class="text-lg font-semibold">Avg. Processing Time</h2>
        <p class="text-2xl font-bold text-red-600">
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
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-2">Requests per Program</h2>
        <canvas id="requestsPerProgram"></canvas>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-2">Total Budget Obligated per Program</h2>
        <canvas id="budgetPerProgram"></canvas>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-2">Documents Handled per Role</h2>
        <canvas id="documentsPerRole"></canvas>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-2">Monthly Requests Trend</h2>
        <canvas id="monthlyRequestsChart"></canvas>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-2">Accounting Staff Performance</h2>
        <canvas id="staffPerformanceChart"></canvas>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-2">Top 5 Bottlenecks (Slowest Avg Processing Time)</h2>
        <canvas id="bottleneckChart"></canvas>
    </div>
</div>

<!-- Insights -->
<div class="bg-white p-4 rounded-lg shadow mt-6">
    <h2 class="text-lg font-semibold mb-2">Insights & Highlights</h2>
    <ul class="list-disc list-inside text-gray-700">
        @foreach($insights as $insight)
            <li>{{ $insight }}</li>
        @endforeach
    </ul>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize charts
    const requestsPerProgramChart = new Chart(document.getElementById('requestsPerProgram'), {
        type: 'bar',
        data: {
            labels: @json($requestsPerProgram->keys()),
            datasets: [{ label: 'Requests', data: @json($requestsPerProgram->values()), backgroundColor: ['#3b82f6','#10b981','#f59e0b'] }]
        }
    });

    const budgetPerProgramChart = new Chart(document.getElementById('budgetPerProgram'), {
        type: 'bar',
        data: {
            labels: @json($budgetPerProgram->keys()),
            datasets: [{ label: 'Budget', data: @json($budgetPerProgram->values()), backgroundColor: ['#3b82f6','#10b981','#f59e0b'] }]
        }
    });

    const documentsPerRoleChart = new Chart(document.getElementById('documentsPerRole'), {
        type: 'pie',
        data: {
            labels: @json($documentsPerRole->keys()),
            datasets: [{ data: @json($documentsPerRole->values()), backgroundColor: ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6'] }]
        }
    });

    const monthlyRequestsChart = new Chart(document.getElementById('monthlyRequestsChart'), {
        type: 'line',
        data: {
            labels: @json($monthlyRequests->keys()),
            datasets: [{ label: 'Requests', data: @json($monthlyRequests->values()), borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.2)', fill: true }]
        }
    });

    const staffPerformanceChart = new Chart(document.getElementById('staffPerformanceChart'), {
        type: 'bar',
        data: {
            labels: @json($staffPerformance->pluck('staff_name')),
            datasets: [
                { label: 'Total Processed', data: @json($staffPerformance->pluck('total_processed')), backgroundColor: '#3b82f6' },
                { label: 'Avg Processing Time (hours)', data: @json($staffPerformance->pluck('avg_hours')), backgroundColor: '#f59e0b' }
            ]
        }
    });

    const bottleneckChart = new Chart(document.getElementById('bottleneckChart'), {
        type: 'bar',
        data: {
            labels: @json($bottlenecks->pluck('staff_name')),
            datasets: [{ label: 'Avg Processing Time (hours)', data: @json($bottlenecks->pluck('avg_hours')), backgroundColor: '#ef4444' }]
        }
    });

    // Real-time polling every 5 seconds
    applyRealtimeUpdates();
    setInterval(applyRealtimeUpdates, 5000);

    async function applyRealtimeUpdates() {
        try {
            const res = await fetch(`{{ route('admin.dashboard.fetch') }}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();

            // Update KPI cards
            document.querySelector('.text-blue-600').textContent = data.totalRequests;
            document.querySelector('.text-yellow-600').textContent = data.pendingRequests;
            document.querySelector('.text-green-600').textContent = data.approvedRequests;

            let minutes = Math.round(data.averageProcessingTime ?? 0);
            let hours = Math.floor(minutes / 60);
            let mins = minutes % 60;
            document.querySelector('.text-red-600').textContent = hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;

            // Update charts
            requestsPerProgramChart.data.labels = Object.keys(data.requestsPerProgram);
            requestsPerProgramChart.data.datasets[0].data = Object.values(data.requestsPerProgram);
            requestsPerProgramChart.update();

            budgetPerProgramChart.data.labels = Object.keys(data.budgetPerProgram);
            budgetPerProgramChart.data.datasets[0].data = Object.values(data.budgetPerProgram);
            budgetPerProgramChart.update();

            documentsPerRoleChart.data.labels = Object.keys(data.documentsPerRole);
            documentsPerRoleChart.data.datasets[0].data = Object.values(data.documentsPerRole);
            documentsPerRoleChart.update();

            monthlyRequestsChart.data.labels = Object.keys(data.monthlyRequests);
            monthlyRequestsChart.data.datasets[0].data = Object.values(data.monthlyRequests);
            monthlyRequestsChart.update();

            staffPerformanceChart.data.labels = data.staffPerformance.map(d => d.staff_name);
            staffPerformanceChart.data.datasets[0].data = data.staffPerformance.map(d => d.total_processed);
            staffPerformanceChart.data.datasets[1].data = data.staffPerformance.map(d => d.avg_hours);
            staffPerformanceChart.update();

            bottleneckChart.data.labels = data.bottlenecks.map(d => d.staff_name);
            bottleneckChart.data.datasets[0].data = data.bottlenecks.map(d => d.avg_hours);
            bottleneckChart.update();

            // Update insights
            const insightList = document.querySelector('ul.list-disc');
            insightList.innerHTML = '';
            data.insights.forEach(text => {
                const li = document.createElement('li');
                li.textContent = text;
                insightList.appendChild(li);
            });
        } catch (err) {
            console.error('Error updating dashboard:', err);
        }
    }
});
</script>
@endsection
