@extends('layouts.app')

@section('title', 'Budget Officer - ORS Requests')


@section('content')
<div class="min-h-screen bg-gray-50 py-8">
  <div class="max-w-[95rem] mx-auto px-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Received DV Requests</h1>
                <p class="mt-2 text-sm text-gray-600">Manage and obligate disbursement voucher requests with ORS numbers</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <span id="connection-status" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                    <span class="w-2 h-2 mr-2 rounded-full bg-gray-400"></span>
                    Connecting...
                </span>
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
                <div id="dv-table-container" class="overflow-x-auto w-full">
                <div class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-gray-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">Loading requests...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col space-y-2"></div>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
// ====================================
// GLOBAL FUNCTION DECLARATIONS
// ====================================

// Declare all functions at the top to make them globally accessible
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
    
    const toastContainer = document.getElementById('toast-container');
    if (toastContainer) {
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toast = document.getElementById(toastId);
        
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
            toast.classList.add('translate-x-0', 'opacity-100');
        });
        
        setTimeout(() => dismissToast(toastId), 4000);
    }
}

function dismissToast(toastId) {
    const toast = document.getElementById(toastId);
    if (toast) {
        toast.classList.add('translate-x-full', 'opacity-0');
        toast.classList.remove('translate-x-0', 'opacity-100');
        setTimeout(() => toast.remove(), 300);
    }
}

async function handleReceiveAction(requestId, button) {
    const originalText = button.innerHTML;
    button.innerHTML = 'Processing...';
    button.disabled = true;

    try {
        const response = await fetch(`/BudgetOfficer/dvrequests/${requestId}/receive`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (response.ok && result.success) {
            showToast(result.message || 'Document has been received by Budget Officer.', 'success');
            await fetchDvRequests();
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

// ====================================
// MAIN APPLICATION CODE
// ====================================

document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.getElementById('dv-table-container');
    const searchInput = document.getElementById('searchInput');
    const connectionStatus = document.getElementById('connection-status');
    
    let allRequests = [];
    let searchTerm = "";
    let searchTimeout;
    let isFetching = false;
    let abortController = null;

    const statusColors = {
        'Incoming-GIP_verified': ['bg-gray-100 text-gray-800','bg-gray-400','Pending'],
        'Incoming-TUPAD_verified': ['bg-gray-100 text-gray-800','bg-gray-400','Pending'],
        'Incoming-TEV_verified': ['bg-gray-100 text-gray-800','bg-gray-400','Pending'],
        'ORS_received': ['bg-blue-100 text-blue-800','bg-blue-400','Received'],
        'ORS_in_process': ['bg-indigo-100 text-indigo-800','bg-indigo-400','In Process'],
        'ORS_onhold': ['bg-yellow-100 text-yellow-800','bg-yellow-400','On Hold'],
        'ORS_obligated': ['bg-green-100 text-green-800','bg-green-400','Obligated'],
    };

    // Initialize Pusher
    const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
        cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
        encrypted: true,
        enabledTransports: ['ws', 'wss']
    });

    // Update connection status
    function updateConnectionStatus(state) {
        const statusMap = {
            'connecting': { text: 'Connecting...', color: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400' },
            'connected': { text: 'Live', color: 'bg-green-100 text-green-600', dot: 'bg-green-500' },
            'unavailable': { text: 'Disconnected', color: 'bg-red-100 text-red-600', dot: 'bg-red-500' },
            'failed': { text: 'Connection Failed', color: 'bg-red-100 text-red-600', dot: 'bg-red-500' }
        };

        const status = statusMap[state] || statusMap.connecting;
        connectionStatus.innerHTML = `
            <span class="w-2 h-2 mr-2 rounded-full ${status.dot}"></span>
            ${status.text}
        `;
        connectionStatus.className = `inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${status.color}`;
    }

    // Monitor Pusher connection
    pusher.connection.bind('state_change', function(states) {
        updateConnectionStatus(states.current);
    });

    // Subscribe to Budget Officer channel
    const budgetChannel = pusher.subscribe('dv-requests-budget-officer');

    // Consolidated event handler
    function handlePusherUpdate(eventType, data) {
        console.log(`${eventType}:`, data);
        
        // Show toast for relevant events
        switch(eventType) {
            case 'dv-request-verified':
                showToast(`New DV request from ${data.submitted_by || 'Incoming'} ready for processing`, 'success');
                break;
            case 'dv-request-received':
                showToast(`Request #${data.dv_request_id} has been received`, 'success');
                break;
            case 'ors-obligated':
                showToast(`ORS ${data.ors_number} has been obligated`, 'success');
                break;
            case 'amount-updated':
                showToast(`Amount updated for request #${data.dv_request_id}`, 'success');
                break;
        }
        
        // Debounce multiple rapid updates
        if (!isFetching) {
            fetchDvRequests();
        }
    }

    // Bind all events to single handler
    ['dv-request-verified', 'dv-request-received', 'dv-request-processing', 
     'ors-obligated', 'ors-onhold', 'ors-revised', 'status-updated', 'amount-updated'].forEach(event => {
        budgetChannel.bind(event, (data) => handlePusherUpdate(event, data));
    });

    // Optimized table rendering
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

        const rows = dvRequests.map(req => {
            const [bgClass, dotClass, label] = statusColors[req.status] || ['bg-gray-100 text-gray-800','bg-gray-400', req.status];
            return `
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                  <td class="px-6 py-4 text-sm font-medium text-gray-900">
                    ${req.control_no ?? '—'}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">${req.ors_number ?? '—'}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <span class="font-mono text-xs">${req.uacs_codes}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <span class="font-mono text-xs">${req.adl_code}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <span class="font-mono text-xs">${req.mfopap_code}</span>
                    </td>
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
                    ${req.status === 'Incoming-GIP_verified' || req.status === 'Incoming-TUPAD_verified' || req.status === 'Incoming-TEV_verified'
                        ? `<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button type="button" 
                                onclick="handleReceiveAction(${req.id}, this)"
                                class="inline-flex px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-md transition-colors duration-200">
                                Receive
                            </button>
                        </td>`
                        : `<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="/BudgetOfficer/dvrequests/${req.id}" 
                                class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md">
                                View / Process
                            </a>
                        </td>`
                    }
                </tr>`;
        }).join('');

        tableContainer.innerHTML = `
           <div class="overflow-x-auto w-full shadow-sm border border-gray-200 rounded-xl">
              <table class="w-full table-auto divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Control No.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ORS Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">UACS Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ADL Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">MFO/PAP</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Responsibility Center</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gross Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date Submitted</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${rows}
                    </tbody>
                </table>
            </div>
        `;
    }

    // Fetch DV requests with abort controller
    async function fetchDvRequests() {
        if (isFetching && abortController) {
            abortController.abort();
        }
        
        isFetching = true;
        abortController = new AbortController();
        
        try {
            const url = "{{ route('BudgetOfficer.dvrequests.fetch') }}?search=" + encodeURIComponent(searchTerm);
            console.log('Fetching from:', url);
            
            const res = await fetch(url, {
                signal: abortController.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            if (!res.ok) {
                const errorText = await res.text();
                console.error('Server response:', errorText);
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const data = await res.json();
            
            // Check if the response contains an error
            if (data.error) {
                throw new Error(data.error);
            }
            
            allRequests = data;
            renderTable(allRequests);
        } catch (err) {
            if (err.name === 'AbortError') {
                console.log('Fetch aborted');
                return;
            }
            console.error('Failed to fetch DV requests:', err);
            tableContainer.innerHTML = `
                <div class="text-center py-16 text-red-500">
                    <p class="font-medium">Failed to load DV requests.</p>
                    <p class="text-sm mt-2">Error: ${err.message}</p>
                    <button onclick="fetchDvRequests()" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Retry
                    </button>
                </div>`;
        } finally {
            isFetching = false;
        }
    }

    // Debounced search
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchTerm = this.value;
            fetchDvRequests();
        }, 300);
    });

    // ====================================
    // MAKE FUNCTIONS GLOBALLY ACCESSIBLE
    // ====================================
    
    // Attach functions to window object so they're accessible globally
    window.fetchDvRequests = fetchDvRequests;
    window.showToast = showToast;
    window.dismissToast = dismissToast;
    window.handleReceiveAction = handleReceiveAction;

    // Initial fetch
    fetchDvRequests();
});
</script>
@endsection