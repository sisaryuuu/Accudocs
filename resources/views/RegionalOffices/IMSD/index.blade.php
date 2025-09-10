@extends('layouts.app')

@section('title', 'IMSD - DV Requests')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Received DV Requests</h1>
                <p class="mt-2 text-sm text-gray-600">Manage and verify submitted disbursement voucher requests</p>
            </div>
        </div>

        <!-- 🔍 Search bar -->
        <div class="mb-4">
            <input 
                type="text" 
                id="search-input" 
                placeholder="Search DV Requests..." 
                class="w-full sm:w-1/3 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
            >
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div id="dv-table-container" class="text-center py-16 text-gray-500">
                Loading submitted requests...
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.getElementById('dv-table-container');
    const searchInput = document.getElementById('search-input');
    let searchTerm = "";

    const statusColors = {
        'submitted': ['bg-gray-100 text-gray-800','bg-gray-400','Pending'],
        'IMSD_in_process': ['bg-blue-100 text-blue-800','bg-blue-400','In Process'],
        'IMSD_verified': ['bg-green-100 text-green-800','bg-green-400','Verified']
    };

    function renderTable(dvRequests) {
        if (!dvRequests.length) {
            tableContainer.innerHTML = `<div class="text-center py-16">No DV requests found.</div>`;
            return;
        }

        let html = `<div class="overflow-x-auto"><table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Control No.</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted By</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Submitted</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead><tbody class="bg-white divide-y divide-gray-200">`;

        dvRequests.forEach(request => {
            const [bgClass, dotClass, label] = statusColors[request.status] || ['bg-gray-100 text-gray-800','bg-gray-400', request.status];
            html += `<tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">#${request.id}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">${request.submitted_by}</td>
                        <td class="px-6 py-4 text-sm text-gray-600"><span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${request.program}</span></td>
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">₱${request.amount}</td>
                        <td class="px-6 py-4"><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${bgClass}">
                            <span class="w-2 h-2 mr-2 rounded-full ${dotClass}"></span>${label}</span></td>
                        <td class="px-6 py-4 text-sm text-gray-600">${request.date}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <a href="/RegionalOffices/IMSD/dvrequests/${request.id}" class="inline-flex px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md">View / Process</a>
                        </td>
                     </tr>`;
        });

        html += `</tbody></table></div>`;
        tableContainer.innerHTML = html;
    }

    async function fetchDvRequests() {
        try {
            const res = await fetch("{{ route('RegionalOffices.IMSD.fetchAll') }}?search=" + encodeURIComponent(searchTerm));
            const data = await res.json();
            renderTable(data);
        } catch (err) {
            console.error('Failed to fetch DV requests', err);
            tableContainer.innerHTML = `<div class="text-center py-16 text-red-500">Failed to load DV requests.</div>`;
        }
    }

    // Listen to typing in search
    searchInput.addEventListener('input', function() {
        searchTerm = this.value;
        fetchDvRequests();
    });

    // Initial load + polling
    fetchDvRequests();
    setInterval(fetchDvRequests, 5000);
});
</script>
@endsection
