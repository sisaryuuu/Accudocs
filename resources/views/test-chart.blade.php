<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descriptive Analytics Dashboard</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-gray-100 p-6">
    <h1 class="text-2xl font-bold mb-6">Descriptive Analytics Dashboard</h1>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold">Total Requests</h2>
            <p class="text-2xl font-bold text-blue-600">120</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold">Approved</h2>
            <p class="text-2xl font-bold text-green-600">90</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold">Pending</h2>
            <p class="text-2xl font-bold text-yellow-600">25</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold">Avg. Processing Time</h2>
            <p class="text-2xl font-bold text-red-600">5 days</p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Bar Chart -->
        <div class="bg-white p-4 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-2">Requests per Program</h2>
            <canvas id="requestsPerProgram"></canvas>
        </div>

        <!-- Pie Chart -->
        <div class="bg-white p-4 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-2">Documents per Department</h2>
            <canvas id="documentsPerDepartment"></canvas>
        </div>
    </div>

    <!-- Line Chart (Monthly Trend) -->
    <div class="bg-white p-4 rounded-lg shadow mb-8">
        <h2 class="text-lg font-semibold mb-2">Monthly Requests Trend</h2>
        <canvas id="monthlyRequestsTrend"></canvas>
    </div>

    <!-- Insights Section -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Insights & Highlights</h2>
        <ul class="list-disc list-inside space-y-2 text-gray-700">
            <li><strong>Program B</strong> received the highest number of requests.</li>
            <li>The <strong>Incoming Department</strong> handles the largest share of documents.</li>
            <li>Overall requests are showing an <strong>upward trend</strong> in the last 3 months.</li>
        </ul>
    </div>
</body>
</html>
