@extends('layouts.app')

@section('title', 'Incoming-TUPAD - DV Requests')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Received Disbursement Voucher Requests</h1>
                <p class="mt-2 text-sm text-gray-600">Manage and verify submitted TUPAD disbursement voucher requests</p>
            </div>
        </div>

        <!-- Search Input -->
        <div class="mb-4">
            <input type="text" id="searchInput" 
                   placeholder="🔍 Search DV Requests (Control No, Program, Respocenter, Submitted By)..."
                   class="w-full md:w-1/2 px-4 py-2 border rounded-lg shadow-sm focus:ring focus:ring-blue-200 focus:outline-none">
        </div>

        <!-- Main Content Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div id="dv-table-container">
                <!-- Table will be dynamically injected here -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.getElementById('dv-table-container');
    const searchInput = document.getElementById('searchInput');
     let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'fixed top-5 right-5 z-50 flex flex-col space-y-2';
        document.body.appendChild(toastContainer);
    }
    let allRequests = []; // store full list for search

    const statusColors = {
        'IMSD_verified': ['bg-gray-100 text-gray-800','bg-gray-400','Pending'],
        'Incoming-TUPAD_received': ['bg-green-100 text-green-800','bg-green-400','Received'],
        'Incoming-TUPAD_in_process': ['bg-blue-100 text-blue-800','bg-blue-400','In Process'],
        'Incoming-TUPAD_verified': ['bg-green-100 text-green-800','bg-green-400','Verified']
    };

      function showToast(message, type = 'success') {
        const toastId = 'toast-' + Date.now();
        const isSuccess = type === 'success';
        
        const toastHTML = `
            <div id="${toastId}" class="toast-notification transform transition-all duration-300 ease-in-out translate-x-full opacity-0">
                <div class="flex items-center w-full max-w-sm p-4 mb-4 text-gray-500 bg-white rounded-lg shadow-lg border-l-4 ${isSuccess ? 'border-green-500' : 'border-red-500'}" role="alert">
                    <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg ${isSuccess ? 'text-green-500 bg-green-100' : 'text-red-500 bg-red-100'}">
                        ${isSuccess ? 
                            '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>' :
                            '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>'
                        }
                    </div>
                    <div class="ml-3 text-sm font-medium text-gray-900">${message}</div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8" onclick="dismissToast('${toastId}')">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
      toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toast = document.getElementById(toastId);
        
        // Trigger animation
        setTimeout(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
            toast.classList.add('translate-x-0', 'opacity-100');
        }, 100);
        
        // Auto dismiss after 4 seconds
        setTimeout(() => {
            dismissToast(toastId);
        }, 4000);
    }

    // Dismiss toast function
    window.dismissToast = function(toastId) {
        const toast = document.getElementById(toastId);
        if (toast) {
            toast.classList.add('translate-x-full', 'opacity-0');
            toast.classList.remove('translate-x-0', 'opacity-100');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
    }

    // Define globally
    window.handleReceiveAction = async function(requestId, button) {
        const originalText = button.innerHTML;
        button.innerHTML = 'Processing...';
        button.disabled = true;

        try {
            const response = await fetch(`/Incoming/TUPAD/dvrequests/${requestId}/receive`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                showToast(result.message || 'Document has been received by Incoming-GIP.', 'success');
                await fetchDvRequests(); // refresh
            } else {
                showToast(result.message || 'Failed to receive document.', 'error');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        } catch (error) {
            console.error('Error receiving document:', error);
            showToast('An error occurred while processing the request.', 'error');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }




    // Render table
    function renderTable(dvRequests) {
        if (dvRequests.length === 0) {
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
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900">Request History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Control No.</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted By</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsibility Center</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Submitted</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
        `;

        dvRequests.forEach(request => {
            const [bgClass, dotClass, label] = statusColors[request.status] || ['bg-gray-100 text-gray-800','bg-gray-400', request.status];
            html += `
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                   <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            Control #${request.accounting_control_number}
                            ${request.user_sequence !== undefined 
                                ? `<br><span class="text-xs text-gray-500">Seq: ${request.user_sequence}</span>` 
                                : ''}
                        </td>
                    <td class="px-6 py-4 text-sm text-gray-900">${request.submitted_by}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${request.program}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${request.respocenter}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">₱${request.amount}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${bgClass}">
                            <span class="w-2 h-2 mr-2 rounded-full ${dotClass}"></span>
                            ${label}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${request.date}</td>
                    ${
                            request.status === 'IMSD_verified'
                            ? `<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button type="button" 
                                    onclick="handleReceiveAction(${request.id}, this)"
                                    class="inline-flex px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-md transition-colors duration-200">
                                    Receive
                                </button>
                            </td>`
                            : `<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/Incoming/TUPAD/dvrequests/${request.id}" 
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md">
                                    View / Process
                                </a>
                            </td>`
                        }
                </tr>`;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;
        tableContainer.innerHTML = html;
    }

    // Fetch requests
    async function fetchDvRequests() {
        try {
            const res = await fetch("{{ route('Incoming.TUPAD.fetchAll') }}");
            const data = await res.json();
            allRequests = data; // keep a copy
            renderTable(allRequests);
        } catch (err) {
            console.error('Failed to fetch DV requests', err);
        }
    }

    // Filter requests by search
    function filterRequests() {
        const query = searchInput.value.toLowerCase();
        const filtered = allRequests.filter(r =>
            r.id.toString().includes(query) ||
            r.submitted_by.toLowerCase().includes(query) ||
            r.program.toLowerCase().includes(query) ||
            r.respocenter.toLowerCase().includes(query)
        );
        renderTable(filtered);
    }

    // Event: search input
    searchInput.addEventListener('input', filterRequests);

    // Initial fetch + polling
    fetchDvRequests();
    setInterval(fetchDvRequests, 5000);
});
</script>
@endsection
