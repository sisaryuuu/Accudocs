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

   <!-- <h1 class="text-2xl font-bold mb-6">Descriptive Analytics Dashboard</h1> -->

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold">Total Requests</h2>
            <p class="text-2xl font-bold text-blue-600">{{ $totalRequests ?? 0}}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow text-center">
        <h2 class="text-lg font-semibold">Pending</h2>
        <p class="text-2xl font-bold text-yellow-600">{{ $pendingRequests }}</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow text-center">
        <h2 class="text-lg font-semibold">Approved</h2>
        <p class="text-2xl font-bold text-green-600">{{ $approvedRequests }}</p>
    </div>
        <!-- Avg. Processing Time -->
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
        <!-- Bar Chart -->
<div class="bg-white p-4 rounded-lg shadow">
    <h2 class="text-lg font-semibold mb-2">Requests per Program</h2>
    <canvas id="requestsPerProgram"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('requestsPerProgram').getContext('2d');

    const data = {
        labels: @json($requestsPerProgram->keys()), // ['TUPAD','GIP','TEV']
        datasets: [{
            label: 'Number of Requests',
            data: @json($requestsPerProgram->values()), // [count values]
            backgroundColor: ['#3b82f6','#10b981','#f59e0b'], // blue, green, yellow
        }]
    };

    new Chart(ctx, {
        type: 'bar',
        data: data,
    });
</script>


        <!-- Pie Chart -->
<div class="bg-white p-4 rounded-lg shadow">
    <h2 class="text-lg font-semibold mb-2">Documents Handled per Role</h2>
    <canvas id="documentsPerRole"></canvas>
</div>

<script>
    const ctxPie = document.getElementById('documentsPerRole').getContext('2d');

    new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: @json($documentsPerRole->keys()),   // ['IMSD', 'Incoming', 'Budget_Officer']
            datasets: [{
                data: @json($documentsPerRole->values()), // [4, 3, 5]
                backgroundColor: [
                    '#3b82f6', // blue
                    '#10b981', // green
                    '#f59e0b', // yellow
                    '#ef4444', // red
                    '#8b5cf6', // violet
                    '#ec4899', // pink
                ],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>


    <!-- Monthly Requests Trend -->
<div class="bg-white p-4 rounded-lg shadow mt-8">
    <h2 class="text-lg font-semibold mb-2">Monthly Requests Trend</h2>
    <canvas id="monthlyRequestsChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const monthlyCtx = document.getElementById('monthlyRequestsChart').getContext('2d');

    const monthlyData = {
        labels: @json($monthlyRequests->keys()), // e.g. ["September 2025","October 2025"]
        datasets: [{
            label: 'Requests',
            data: @json($monthlyRequests->values()), // counts
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.2)',
            fill: true,
            tension: 0.3
        }]
    };

    new Chart(monthlyCtx, {
        type: 'line',
        data: monthlyData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Number of Requests'
                    },
                    beginAtZero: true
                }
            }
        }
    });
</script>





    <!-- Insights & Highlights -->
<div class="bg-white p-4 rounded-lg shadow mt-6">
    <h2 class="text-lg font-semibold mb-2">Insights & Highlights</h2>
    <ul class="list-disc list-inside text-gray-700">
        @foreach($insights as $insight)
            <li>{{ $insight }}</li>
        @endforeach
    </ul>
</div>

@endsection
