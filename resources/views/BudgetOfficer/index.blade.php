@extends('layouts.app')

@section('title', 'Budget Officer - ORS Requests')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Received DV Requests</h1>
                <p class="mt-2 text-sm text-gray-600">Manage and obligate disbursement voucher requests with ORS numbers</p>
            </div>
        </div>

        <!-- Search -->
        <div class="mb-4">
            <input type="text" id="searchInput" 
                   placeholder="🔍 Search DV Requests (Control No, ORS No, Program, Respocenter, Submitted By)..."
                   class="w-full md:w-1/2 px-4 py-2 border rounded-lg shadow-sm focus:ring focus:ring-blue-200 focus:outline-none">
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div id="dv-table-container" class="overflow-x-auto">
                <!-- Table will be injected by JS -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.getElementById('dv-table-container');
    const searchInput = document.getElementById('searchInput');
    let allRequests = [];

    const statusColors = {
        'Incoming-GIP_verified': ['bg-gray-100 text-gray-800','bg-gray-400','Pending'],
        'Incoming-TUPAD_verified': ['bg-gray-100 text-gray-800','bg-gray-400','Pending'],
        'Incoming-Misc_verified': ['bg-green-100 text-green-800','bg-green-400','Pending'],
        'ORS_received': ['bg-blue-100 text-blue-800','bg-blue-400','Received/In Process']
    };

    // Renders the table
    function renderTable(dvRequests) {
        if (!dvRequests.length) {
            tableContainer.innerHTML = `
                <div class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No DV requests found</h3>
                    <p class="text-gray-500">Try adjusting your search filter.</p>
                </div>
            `;
            return;
        }

        let html = `
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Control No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ORS Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Responsibility Center</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date Submitted</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
        `;

        dvRequests.forEach(req => {
            const [bgClass, dotClass, label] = statusColors[req.status] || ['bg-gray-100 text-gray-800','bg-gray-400', req.status];
            html += `
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">#${req.id}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">${req.ors_number ?? '—'}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">${req.submitted_by}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${req.program}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${req.respocenter}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">₱${req.amount}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${bgClass}">
                            <span class="w-2 h-2 mr-2 rounded-full ${dotClass}"></span>
                            ${label}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">${req.date}</td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="/BudgetOfficer/dvrequests/${req.id}" 
                           class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md">
                           View / Obligate
                        </a>
                    </td>
                </tr>
            `;
        });

        html += `</tbody></table>`;
        tableContainer.innerHTML = html;
    }

    // Fetch DV requests from server
    async function fetchDvRequests() {
        try {
            const res = await fetch("{{ route('BudgetOfficer.dvrequests.fetch') }}");
            const data = await res.json();
            allRequests = data;
            renderTable(allRequests);
        } catch (err) {
            console.error('Failed to fetch DV requests:', err);
        }
    }

    // Search filter
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase();
        const filtered = allRequests.filter(r =>
            r.id.toString().includes(query) ||
            (r.ors_number ?? '').toLowerCase().includes(query) ||
            r.submitted_by.toLowerCase().includes(query) ||
            r.program.toLowerCase().includes(query) ||
            r.respocenter.toLowerCase().includes(query)
        );
        renderTable(filtered);
    });

    // Initial fetch and polling every 5 seconds
    fetchDvRequests();
    setInterval(fetchDvRequests, 5000);
});
</script>
@endsection
