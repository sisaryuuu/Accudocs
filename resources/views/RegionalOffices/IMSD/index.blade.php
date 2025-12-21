@extends('layouts.app')

@section('title', 'IMSD - DV Requests')

@section('content')
<div class="min-h-screen bg-gray-50 pt-24 pb-8"> 
    <div class="max-w-[95rem] mx-auto px-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Received DV Requests</h1>
                <p class="mt-2 text-sm text-gray-600">Manage and verify submitted disbursement voucher requests</p>
            </div>
            <div class="mt-4 sm:mt-0 flex gap-2">
                <span id="connection-status" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                    <span class="w-2 h-2 mr-2 rounded-full bg-gray-400 animate-pulse"></span>
                    Connecting...
                </span>
                <button onclick="refreshAllData()" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Aging Alerts Container -->
        <div id="aging-alerts-container" class="mb-6">
            <!-- Alert will be populated by JavaScript -->
        </div>

        <!-- Aging Statistics Card -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-700 text-sm font-semibold">Critical</p>
                        <p id="stat-critical" class="text-2xl font-bold text-red-600">0</p>
                        <p class="text-xs text-red-600">10+ days old</p>
                    </div>
                    <svg class="w-8 h-8 text-red-500 opacity-30" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-700 text-sm font-semibold">High</p>
                        <p id="stat-high" class="text-2xl font-bold text-orange-600">0</p>
                        <p class="text-xs text-orange-600">7-10 days old</p>
                    </div>
                    <svg class="w-8 h-8 text-orange-500 opacity-30" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v1h8v-1zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3m0 0v.01M2 15v3m0 0v.01"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-4 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-700 text-sm font-semibold">Medium</p>
                        <p id="stat-medium" class="text-2xl font-bold text-yellow-600">0</p>
                        <p class="text-xs text-yellow-600">3-7 days old</p>
                    </div>
                    <svg class="w-8 h-8 text-yellow-500 opacity-30" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 16H5v-4m0-5V7a1 1 0 012-1h6a1 1 0 012 1v1m0 5v4m0 0h2a1 1 0 001-1v-4a1 1 0 00-1-1h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 00-.293.707V12m0 0H9a1 1 0 001-1V9a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm0 5V9m0 8a2 2 0 100-4 2 2 0 000 4z"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-700 text-sm font-semibold">Total Aging</p>
                        <p id="stat-total" class="text-2xl font-bold text-blue-600">0</p>
                        <p class="text-xs text-blue-600">3+ days old</p>
                    </div>
                    <svg class="w-8 h-8 text-blue-500 opacity-30" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000-2H6a6 6 0 100 12H4a2 2 0 01-2-2V5zm11.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Sorting Controls -->
        <div class="flex gap-2 mb-6">
            <button id="sort-by-priority" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path>
                </svg>
                🚨 Sort by Priority
            </button>
            <button id="sort-by-date" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Sort by Date
            </button>
            <button id="show-critical-only" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-medium hover:bg-red-200 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.954-.833-2.724 0L4.348 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                Show Critical Only (10+ days)
            </button>
            <button id="show-all" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg text-sm font-medium hover:bg-green-200 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
                Show All
            </button>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulk-actions-bar" class="mb-4 hidden">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-blue-900">
                        <span id="selected-count">0</span> items selected
                    </span>
                    <button onclick="clearSelection()" class="text-sm text-blue-600 hover:text-blue-800 underline">
                        Clear Selection
                    </button>
                </div>
                <div class="flex gap-2">
                    <button id="bulk-receive-btn" onclick="showBulkActionModal('receive')" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Bulk Receive
                    </button>
                    <button id="bulk-verify-btn" onclick="showBulkActionModal('verify')" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Bulk Verify
                    </button>
                </div>
            </div>
        </div>

        <!-- Search and Pagination Info -->
        <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex-1">
                <input type="text" id="search-input" 
                       placeholder="🔍 Search DV Requests (Control No, ORS No, Program, Respocenter, Submitted By)..."
                       class="w-full md:w-2/3 px-4 py-2 border rounded-lg shadow-sm focus:ring focus:ring-blue-200 focus:outline-none">
            </div>
            <div id="pagination-info" class="text-sm text-gray-600">
                Showing <span id="current-range">0</span> of <span id="total-records">0</span> records
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div id="dv-table-container">
                <div class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-gray-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">Loading requests...</p>
                </div>
            </div>

            <!-- Pagination Controls -->
            <div id="pagination-controls" class="flex items-center justify-between border-t border-gray-200 px-6 py-4 bg-gray-50">
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-700">Rows per page:</span>
                    <select id="per-page-select" class="text-sm border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button id="first-page" class="p-2 rounded hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <button id="prev-page" class="p-2 rounded hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    
                    <div id="page-numbers" class="flex items-center space-x-1">
                        <!-- Page numbers will be generated here -->
                    </div>
                    
                    <button id="next-page" class="p-2 rounded hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    <button id="last-page" class="p-2 rounded hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Action Modal -->
<div id="bulkActionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-4 max-h-[80vh] flex flex-col">
        <div class="flex justify-between items-center mb-6">
            <h3 id="bulkActionModalTitle" class="text-2xl font-bold text-gray-900">Confirm Bulk Action</h3>
            <button onclick="closeBulkActionModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="mb-6 flex-1 overflow-hidden">
            <p id="bulkActionModalMessage" class="text-gray-700 mb-4">You are about to process the following DV Requests:</p>
            <div id="bulkActionList" class="h-64 overflow-y-auto border border-gray-200 rounded-lg p-4 bg-gray-50">
                <!-- Selected DVs will be listed here -->
            </div>
            <div id="bulkVerifyRemarks" class="mt-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Remarks (Optional)</label>
                <textarea id="bulk-verify-remarks" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="Add any remarks for verification..."></textarea>
            </div>
            <p class="text-sm text-gray-600 mt-4">
                <span class="font-semibold">Note:</span> Only DVs with appropriate status will be processed. 
                Grayed-out items are not eligible for this action.
            </p>
        </div>
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button onclick="closeBulkActionModal()" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors duration-200">
                Cancel
            </button>
            <button id="confirmBulkActionBtn" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                Confirm Action
            </button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 text-center">
        <div class="flex justify-center mb-4">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Processing DVs</h3>
        <p class="text-gray-600" id="loadingMessage">Please wait while we process your request...</p>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
@endsection

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
// ============ GLOBAL STATE ============
let selectedRequests = new Set();
let allRequests = []; // Store all requests
let filteredRequests = []; // Store filtered requests for display
let originalFilteredRequests = []; // Store original filtered requests for reset
let isCriticalOnlyView = false; // Track if we're showing critical only
let currentSort = 'priority'; // Current sort type: 'priority', 'date', or 'none'

// ============ GLOBAL FUNCTIONS (MUST BE OUTSIDE DOMContentLoaded) ============
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

window.handleReceiveAction = async function(requestId, button) {
    const originalText = button.innerHTML;
    const originalState = button.disabled;
    
    button.innerHTML = `
        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        Processing...
    `;
    button.disabled = true;

    try {
        const response = await fetch(`/RegionalOffices/IMSD/dvrequests/${requestId}/receive`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({})
        });

        const result = await response.json();

        if (response.ok && result.success) {
            let message = result.message || 'Document has been received by IMSD.';
            
            window.showToast(message, 'success');
            
            // Trigger data refresh
            if (window.fetchDvRequests) window.fetchDvRequests();
            if (window.fetchAgingDocuments) window.fetchAgingDocuments();
            if (window.updateStatistics) window.updateStatistics();
            
        } else {
            const errorMessage = result.message || `Failed to receive document. (Status: ${response.status})`;
            window.showToast(errorMessage, 'error');
            button.innerHTML = originalText;
            button.disabled = originalState;
        }
    } catch (error) {
        console.error('Error receiving document:', error);
        window.showToast('Network error occurred while processing the request.', 'error');
        button.innerHTML = originalText;
        button.disabled = originalState;
    }
}

window.toggleSelectRequest = function(requestId, status) {
    // Check if already selected
    const existing = Array.from(selectedRequests).find(req => req.id === requestId);
    
    if (existing) {
        // Remove from set
        selectedRequests.forEach(req => {
            if (req.id === requestId) {
                selectedRequests.delete(req);
            }
        });
    } else {
        // Add to set
        selectedRequests.add({ id: requestId, status: status });
    }
    updateBulkActionsBar();
}

window.toggleSelectAll = function(checkbox) {
    const checkboxes = document.querySelectorAll('.request-checkbox');
    const currentPageData = window.getPaginatedData(); // Use window.getPaginatedData
    
    // Get all IDs from current page
    const currentPageIds = currentPageData.map(item => item.id);
    
    if (checkbox.checked) {
        // Add only visible items on current page
        checkboxes.forEach(cb => {
            const requestId = parseInt(cb.dataset.requestId);
            const status = cb.dataset.status;
            
            // Only check and add if it's on current page
            if (currentPageIds.includes(requestId)) {
                cb.checked = true;
                
                // Check if not already in set
                const exists = Array.from(selectedRequests).some(req => req.id === requestId);
                if (!exists) {
                    selectedRequests.add({ id: requestId, status: status });
                }
            }
        });
    } else {
        // Remove only visible items on current page
        checkboxes.forEach(cb => {
            const requestId = parseInt(cb.dataset.requestId);
            
            // Only uncheck and remove if it's on current page
            if (currentPageIds.includes(requestId)) {
                cb.checked = false;
                
                // Remove from set
                selectedRequests.forEach(req => {
                    if (req.id === requestId) {
                        selectedRequests.delete(req);
                    }
                });
            }
        });
    }
    updateBulkActionsBar();
}

window.clearSelection = function() {
    selectedRequests.clear();
    document.querySelectorAll('.request-checkbox').forEach(cb => cb.checked = false);
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    }
    updateBulkActionsBar();
}

window.showBulkActionModal = function(actionType) {
    const modal = document.getElementById('bulkActionModal');
    const modalTitle = document.getElementById('bulkActionModalTitle');
    const modalMessage = document.getElementById('bulkActionModalMessage');
    const listContainer = document.getElementById('bulkActionList');
    const confirmBtn = document.getElementById('confirmBulkActionBtn');
    const remarksSection = document.getElementById('bulkVerifyRemarks');
    
    if (!modal || !modalTitle || !modalMessage || !listContainer || !confirmBtn) return;
    
    const isReceive = actionType === 'receive';
    const actionText = isReceive ? 'receive' : 'verify';
    const actionTextCapitalized = isReceive ? 'Receive' : 'Verify';
    
    modalTitle.textContent = `Confirm Bulk ${actionTextCapitalized}`;
    modalMessage.textContent = `You are about to ${actionText} the following DV Requests:`;
    
    // Show/hide remarks section
    if (remarksSection) {
        if (actionType === 'verify') {
            remarksSection.classList.remove('hidden');
        } else {
            remarksSection.classList.add('hidden');
        }
    }
    
    listContainer.innerHTML = '';
    
    let actionDVs = [];
    let otherDVs = [];
    
    const selectedArray = Array.from(selectedRequests);
    const eligibleRequests = isReceive 
        ? selectedArray.filter(req => req.status === 'submitted')
        : selectedArray.filter(req => req.status === 'IMSD_received' || req.status === 'IMSD_in_process');
    
    selectedArray.forEach(requestData => {
        // Find the request in the complete dataset (allRequests)
        const request = allRequests.find(req => req.id == requestData.id);
        if (request) {
            const isActionable = isReceive 
                ? request.status === 'submitted'
                : request.status === 'IMSD_received' || request.status === 'IMSD_in_process';
            
            const listItem = document.createElement('div');
            listItem.className = `flex justify-between items-center py-2 border-b border-gray-200 last:border-b-0 ${!isActionable ? 'opacity-50' : ''}`;
            listItem.innerHTML = `
                <div>
                    <div class="font-medium text-gray-900">${request.accounting_control_number}</div>
                    <div class="text-sm text-gray-600">${request.submitted_by}</div>
                    <div class="text-xs text-gray-500 mt-1">Status: ${request.status.replace(/_/g, ' ')}</div>
                </div>
                <div class="text-right">
                    <div class="font-semibold text-green-600">₱${request.amount}</div>
                    ${!isActionable ? '<div class="text-xs text-red-500 mt-1">Not eligible</div>' : ''}
                </div>
            `;
            
            if (isActionable) {
                actionDVs.push({ requestData, listItem });
            } else {
                otherDVs.push({ requestData, listItem });
            }
        }
    });
    
    // Show actionable DVs first
    actionDVs.forEach(item => listContainer.appendChild(item.listItem));
    otherDVs.forEach(item => listContainer.appendChild(item.listItem));
    
    // Update confirm button
    confirmBtn.textContent = `Confirm ${actionTextCapitalized}`;
    confirmBtn.onclick = function() {
        if (actionType === 'receive') {
            processBulkReceive();
        } else {
            processBulkVerify();
        }
    };
    
    // Show/hide confirm button based on whether there are actionable DVs
    confirmBtn.style.display = actionDVs.length > 0 ? 'block' : 'none';
    
    modal.classList.remove('hidden');
}

window.closeBulkActionModal = function() {
    const modal = document.getElementById('bulkActionModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    // Clear remarks
    const remarksTextarea = document.getElementById('bulk-verify-remarks');
    if (remarksTextarea) {
        remarksTextarea.value = '';
    }
}

window.processBulkReceive = async function() {
    const selectedArray = Array.from(selectedRequests);
    const submittedRequests = selectedArray.filter(req => req.status === 'submitted');
    
    if (submittedRequests.length === 0) {
        window.showToast('No submitted requests selected for bulk receive', 'warning');
        return;
    }

    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingMessage = document.getElementById('loadingMessage');
    
    if (loadingOverlay) {
        loadingOverlay.classList.remove('hidden');
        loadingMessage.textContent = 'Processing bulk receive...';
    }

    try {
        const response = await fetch('/RegionalOffices/IMSD/dvrequests/bulk-receive', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                dv_request_ids: submittedRequests.map(req => req.id)
            })
        });

        const result = await response.json();

        if (response.ok && result.success) {
            window.showToast(result.message, 'success');
            
            // Show detailed results
            if (result.results && result.results.failed && result.results.failed.length > 0) {
                result.results.failed.forEach(fail => {
                    window.showToast(`Failed: ${fail.control_number} - ${fail.error}`, 'error');
                });
            }
            
            if (result.results && result.results.skipped && result.results.skipped.length > 0) {
                result.results.skipped.forEach(skip => {
                    window.showToast(`Skipped: ${skip.control_number} - ${skip.reason}`, 'warning');
                });
            }
            
            // Clear selection BEFORE fetching new data
            clearSelection();
            
            // Refresh data with a slight delay to allow state to clear
            setTimeout(() => {
                if (window.fetchDvRequests) window.fetchDvRequests();
                if (window.fetchAgingDocuments) window.fetchAgingDocuments();
                if (window.updateStatistics) window.updateStatistics();
            }, 100);
            
        } else {
            window.showToast(result.message || 'Bulk receive failed', 'error');
        }
    } catch (error) {
        console.error('Error in bulk receive:', error);
        window.showToast('Network error occurred during bulk receive', 'error');
    } finally {
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
        closeBulkActionModal();
    }
}

window.processBulkVerify = async function() {
    const selectedArray = Array.from(selectedRequests);
    const receivedRequests = selectedArray.filter(req => 
        req.status === 'IMSD_received' || req.status === 'IMSD_in_process'
    );
    
    if (receivedRequests.length === 0) {
        window.showToast('No received/in-process requests selected for bulk verify', 'warning');
        return;
    }

    const remarks = document.getElementById('bulk-verify-remarks')?.value.trim() || 'Bulk verified by IMSD';
    
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingMessage = document.getElementById('loadingMessage');
    
    if (loadingOverlay) {
        loadingOverlay.classList.remove('hidden');
        loadingMessage.textContent = 'Processing bulk verify...';
    }

    try {
        const response = await fetch('/RegionalOffices/IMSD/dvrequests/bulk-verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                dv_request_ids: receivedRequests.map(req => req.id),
                remarks: remarks
            })
        });

        const result = await response.json();

        if (response.ok && result.success) {
            window.showToast(result.message, 'success');
            
            // Show detailed results
            if (result.results && result.results.failed && result.results.failed.length > 0) {
                result.results.failed.forEach(fail => {
                    window.showToast(`Failed: ${fail.control_number} - ${fail.error}`, 'error');
                });
            }
            
            if (result.results && result.results.skipped && result.results.skipped.length > 0) {
                result.results.skipped.forEach(skip => {
                    window.showToast(`Skipped: ${skip.control_number} - ${skip.reason}`, 'warning');
                });
            }
            
            clearSelection();
            
            if (window.fetchDvRequests) window.fetchDvRequests();
            if (window.fetchAgingDocuments) window.fetchAgingDocuments();
            if (window.updateStatistics) window.updateStatistics();
            
        } else {
            window.showToast(result.message || 'Bulk verify failed', 'error');
        }
    } catch (error) {
        console.error('Error in bulk verify:', error);
        window.showToast('Network error occurred during bulk verify', 'error');
    } finally {
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
        closeBulkActionModal();
    }
}

function updateBulkActionsBar() {
    const bulkActionsBar = document.getElementById('bulk-actions-bar');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkReceiveBtn = document.getElementById('bulk-receive-btn');
    const bulkVerifyBtn = document.getElementById('bulk-verify-btn');
    
    const count = selectedRequests.size;
    selectedCountSpan.textContent = count;
    
    if (count > 0) {
        bulkActionsBar.classList.remove('hidden');
        
        // Check if any selected items can be received (status = 'submitted')
        const selectedArray = Array.from(selectedRequests);
        const hasSubmitted = selectedArray.some(req => req.status === 'submitted');
        const hasReceived = selectedArray.some(req => 
            req.status === 'IMSD_received' || req.status === 'IMSD_in_process'
        );
        
        bulkReceiveBtn.disabled = !hasSubmitted;
        bulkVerifyBtn.disabled = !hasReceived;
    } else {
        bulkActionsBar.classList.add('hidden');
    }
    
    // Update the select all checkbox state
    updateSelectAllCheckbox();
}

function updateSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    if (!selectAllCheckbox) return;
    
    const currentPageData = window.getPaginatedData(); // Use window.getPaginatedData
    const currentPageIds = currentPageData.map(item => item.id);
    
    // Count how many items on current page are selected
    const selectedOnPage = Array.from(selectedRequests).filter(req => 
        currentPageIds.includes(req.id)
    ).length;
    
    if (selectedOnPage === 0) {
        // None selected
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (selectedOnPage === currentPageData.length) {
        // All selected
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        // Some selected
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

window.refreshAllData = function() {
    const tableContainer = document.getElementById('dv-table-container');
    if (tableContainer) {
        tableContainer.innerHTML = `
            <div class="text-center py-16">
                <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-12 h-12 text-gray-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                <p class="text-gray-500">Refreshing requests...</p>
            </div>
        `;
    }
    
    const searchInput = document.getElementById('search-input');
    if (searchInput) searchInput.value = '';
    
    clearSelection();
    
    // Reset view
    isCriticalOnlyView = false;
    currentSort = 'priority';
    updateSortButtonStyles();
    
    if (window.fetchDvRequests) window.fetchDvRequests();
    if (window.fetchAgingDocuments) window.fetchAgingDocuments();
    if (window.updateStatistics) window.updateStatistics();
}

window.showToast = function(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) return;
    
    const toastId = 'toast-' + Date.now();
    
    const typeConfig = {
        'success': {
            borderColor: 'border-green-500',
            bgColor: 'bg-green-100',
            textColor: 'text-green-500',
            icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
            </svg>`
        },
        'error': {
            borderColor: 'border-red-500',
            bgColor: 'bg-red-100',
            textColor: 'text-red-500',
            icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>`
        },
        'warning': {
            borderColor: 'border-orange-500',
            bgColor: 'bg-orange-100',
            textColor: 'text-orange-500',
            icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>`
        },
        'info': {
            borderColor: 'border-blue-500',
            bgColor: 'bg-blue-100',
            textColor: 'text-blue-500',
            icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>`
        }
    };
    
    const config = typeConfig[type] || typeConfig.success;
    
    const toastHTML = `
        <div id="${toastId}" class="toast-notification transform transition-all duration-300 ease-in-out translate-x-full opacity-0">
            <div class="flex items-center w-full max-w-sm p-4 mb-4 text-gray-500 bg-white rounded-lg shadow-lg border-l-4 ${config.borderColor}" role="alert">
                <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg ${config.textColor} ${config.bgColor}">
                    ${config.icon}
                </div>
                <div class="ml-3 text-sm font-medium text-gray-900">${message}</div>
                <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8" onclick="window.dismissToast('${toastId}')">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    const toast = document.getElementById(toastId);
    
    setTimeout(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
        toast.classList.add('translate-x-0', 'opacity-100');
    }, 100);
    
    setTimeout(() => {
        window.dismissToast(toastId);
    }, 5000);
}

// ============ MAIN APPLICATION ============
document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.getElementById('dv-table-container');
    const searchInput = document.getElementById('search-input');
    const connectionStatus = document.getElementById('connection-status');
    const agingAlertsContainer = document.getElementById('aging-alerts-container');
    
    // Pagination elements
    const perPageSelect = document.getElementById('per-page-select');
    const firstPageBtn = document.getElementById('first-page');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');
    const lastPageBtn = document.getElementById('last-page');
    const pageNumbersContainer = document.getElementById('page-numbers');
    const currentRangeSpan = document.getElementById('current-range');
    const totalRecordsSpan = document.getElementById('total-records');
    let activeBulkOperation = null;
    let bulkOperationTimeout = null;
    let searchTerm = "";
    let cachedAgingData = [];
    
    // Pagination state
    let currentPage = 1;
    let perPage = 25;
    let totalPages = 1;
    let totalRecords = 0;
    
    let notificationQueue = {
    received: [],
    timeout: null
};
    const statusColors = {
        'submitted': ['bg-gray-100 text-gray-800','bg-gray-400','Pending'],
        'IMSD_received': ['bg-blue-100 text-blue-800','bg-blue-400','Received'],
        'IMSD_in_process': ['bg-blue-100 text-blue-800','bg-blue-400','In Process'],
        'IMSD_verified': ['bg-green-100 text-green-800','bg-green-400','Verified']
    };

    // Initialize Pusher
    const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
        cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
        encrypted: true
    });

    // Update connection status
    function updateConnectionStatus(state) {
        const statusMap = {
            'connecting': { text: 'Connecting...', color: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400 animate-pulse' },
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

    // Subscribe to IMSD channel
    const generalChannel = pusher.subscribe('dv-requests-imsd');

   // ============ PUSHER EVENT LISTENERS ============

        // Listen for new DV request submissions
        generalChannel.bind('dv-request-submitted', function(data) {
            console.log('✅ New DV request submitted:', data);
              
            fetchDvRequests();
            setTimeout(() => {
                fetchAgingDocuments();
                updateStatistics();
            }, 1000);
        });
        
        // Listen for DV request updates
        generalChannel.bind('dv-request-updated', function(data) {
            console.log('📝 DV request updated by focal person:', data);
            
            window.showToast(
                `Document ${data.control_number} updated by ${data.updated_by}`, 
                'info'
            );
            
            fetchDvRequests();
            setTimeout(() => {
                fetchAgingDocuments();
                updateStatistics();
            }, 1000);
        });
        
        // Listen for DV request deletions
        generalChannel.bind('dv-request-deleted', function(data) {
            console.log('🗑️ DV request deleted by focal person:', data);
            
            window.showToast(
                `Document ${data.control_number} was deleted by ${data.deleted_by}`, 
                'warning'
            );
            
            const deletedRow = document.querySelector(`tr[data-dv-id="${data.dv_request_id}"]`);
            if (deletedRow) {
                deletedRow.style.transition = 'opacity 0.3s ease-out';
                deletedRow.style.opacity = '0';
                setTimeout(() => {
                    deletedRow.remove();
                    fetchDvRequests();
                }, 300);
            } else {
                fetchDvRequests();
            }
            
            setTimeout(() => {
                fetchAgingDocuments();
                updateStatistics();
            }, 1000);
        });
        
     // Listen for request received events (single receive)
    generalChannel.bind('dv-request-received', function(data) {
    console.log('DV request received:', data);
    
    const currentUserId = {{ auth()->id() }};
    
    // Check if this is part of a bulk operation
    const isBulkOperation = activeBulkOperation && 
                           activeBulkOperation.processed_ids.includes(data.dv_request_id) &&
                           (Date.now() - activeBulkOperation.timestamp) < 2000;
    
    if (data.locked_by && data.locked_by !== currentUserId) {
        console.log('Removing locked request from view:', data.dv_request_id);
        
        // Only process if NOT part of bulk operation (bulk operation already handled removal)
        if (!isBulkOperation) {
            // Remove from allRequests and filteredRequests arrays
            allRequests = allRequests.filter(req => req.id !== data.dv_request_id);
            filteredRequests = filteredRequests.filter(req => req.id !== data.dv_request_id);
            
            // Update totals
            totalRecords = filteredRequests.length;
            totalPages = Math.ceil(totalRecords / perPage);
            
            // Remove from selection if it was selected
            selectedRequests.forEach(req => {
                if (req.id === data.dv_request_id) {
                    selectedRequests.delete(req);
                }
            });
            
            // Re-render the table immediately
            renderTable(getPaginatedData());
            updatePaginationInfo();
            updateBulkActionsBar();
            
            // Update aging and statistics
            setTimeout(() => {
                fetchAgingDocuments();
                updateStatistics();
            }, 500);
        }
    } else if (data.locked_by === currentUserId) {
        // Only show toast for single operations, not bulk
        if (!isBulkOperation) {
            let message = `Document ${data.control_number} received successfully`;
            window.showToast(message, 'success');
        }
        
        // Still update aging and statistics for the user who received (only if not bulk)
        if (!isBulkOperation) {
            setTimeout(() => {
                fetchAgingDocuments();
                updateStatistics();
            }, 500);
        }
    }
    });
        
        // Listen for bulk operation completed
        generalChannel.bind('bulk-operation-completed', function(data) {
            console.log('Bulk operation completed:', data);
            
            const currentUserId = {{ auth()->id() }};
            
            if ((data.operation === 'bulk_receive' || data.operation === 'bulk_verify') && data.processed_ids) {
                // Only remove from other users' views (not the user who performed the action)
                if (data.performed_by && data.performed_by !== currentUserId) {
                    console.log(`Removing ${data.operation} requests from view for other user`);
                    
                    // Remove all processed requests from arrays
                    data.processed_ids.forEach(dvId => {
                        allRequests = allRequests.filter(req => req.id !== dvId);
                        filteredRequests = filteredRequests.filter(req => req.id !== dvId);
                        
                        // Remove from selection if it was selected
                        selectedRequests.forEach(req => {
                            if (req.id === dvId) {
                                selectedRequests.delete(req);
                            }
                        });
                    });
                    
                    // Update totals and re-render
                    totalRecords = filteredRequests.length;
                    totalPages = Math.ceil(totalRecords / perPage);
                    
                    // Adjust current page if needed
                    if (currentPage > totalPages && totalPages > 0) {
                        currentPage = totalPages;
                    }
                    
                    renderTable(getPaginatedData());
                    updatePaginationInfo();
                    updateBulkActionsBar();
                    
                    // Show notification
                    window.showToast(
                        `Bulk ${data.operation.replace('_', ' ')}: ${data.success_count} processed by another user`,
                        'info'
                    );
                }
                
                // Always update aging and statistics for all users (including the one who performed the action)
                setTimeout(() => {
                    fetchAgingDocuments();
                    updateStatistics();
                }, 500);
            }
        });
        
        // Listen for other status updates
        generalChannel.bind('dv-request-processing', function(data) {
            console.log('DV request processing started:', data);
            fetchDvRequests();
            setTimeout(() => {
                fetchAgingDocuments();
                updateStatistics();
            }, 1000);
        });
        
        generalChannel.bind('dv-request-verified', function(data) {
            console.log('DV request verified:', data);
            fetchDvRequests();
             setTimeout(() => {
                fetchAgingDocuments();
                updateStatistics();
            }, 500);
        });
        
        generalChannel.bind('status-updated', function(data) {
            console.log('DV request status updated:', data);
            fetchDvRequests();
            setTimeout(() => {
                fetchAgingDocuments();
                updateStatistics();
            }, 1000);
        });
        
        // Aging alerts
        generalChannel.bind('document-aging-alert', function(data) {
            console.log('IMSD Aging alert received:', data);
            window.showToast(`Aging document: ${data.control_number} is ${data.days_old} days old`, 'warning');
            fetchAgingDocuments();
            updateStatistics();
        });
        
        generalChannel.bind('imsd-aging-update', function(data) {
            console.log('IMSD aging update received:', data);
            window.showToast('Aging data updated', 'info');
            fetchAgingDocuments();
            updateStatistics();
        });

    // ============ PAGINATION FUNCTIONS ============
    window.getPaginatedData = function() {
        const startIndex = (currentPage - 1) * perPage;
        const endIndex = startIndex + perPage;
        return filteredRequests.slice(startIndex, endIndex);
    }

    function updatePaginationInfo() {
        const start = ((currentPage - 1) * perPage) + 1;
        const end = Math.min(currentPage * perPage, totalRecords);
        
        currentRangeSpan.textContent = `${start}-${end}`;
        totalRecordsSpan.textContent = totalRecords;
        
        firstPageBtn.disabled = currentPage === 1;
        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === totalPages;
        lastPageBtn.disabled = currentPage === totalPages;
        
        generatePageNumbers();
    }

    function generatePageNumbers() {
        const pages = [];
        const max = 5;
        let start = Math.max(1, currentPage - Math.floor(max / 2));
        let end = Math.min(totalPages, start + max - 1);
        
        if (end - start + 1 < max) start = Math.max(1, end - max + 1);
        
        if (start > 1) {
            pages.push(1);
            if (start > 2) pages.push('...');
        }
        
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
        
        if (end < totalPages) {
            if (end < totalPages - 1) pages.push('...');
            pages.push(totalPages);
        }
        
        pageNumbersContainer.innerHTML = '';
        pages.forEach(page => {
            if (page === '...') {
                const span = document.createElement('span');
                span.className = 'px-3 py-1 text-gray-500';
                span.textContent = '...';
                pageNumbersContainer.appendChild(span);
            } else {
                const button = document.createElement('button');
                button.className = `px-3 py-1 rounded text-sm font-medium transition ${
                    page === currentPage ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-200'
                }`;
                button.textContent = page;
                button.onclick = () => goToPage(page);
                pageNumbersContainer.appendChild(button);
            }
        });
    }

    function goToPage(page) {
        if (page < 1 || page > totalPages || page === currentPage) return;
        currentPage = page;
        renderTable(getPaginatedData());
        updatePaginationInfo();
        tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ============ SEARCH FUNCTIONALITY ============
    function filterRequests(requests, searchTerm) {
        if (!searchTerm) return requests;
        
        const searchLower = searchTerm.toLowerCase();
        return requests.filter(request => {
            return (
                (request.accounting_control_number && request.accounting_control_number.toLowerCase().includes(searchLower)) ||
                (request.submitted_by && request.submitted_by.toLowerCase().includes(searchLower)) ||
                (request.program && request.program.toLowerCase().includes(searchLower)) ||
                (request.ors_number && request.ors_number.toLowerCase().includes(searchLower)) ||
                (request.respocenter && request.respocenter.toLowerCase().includes(searchLower))
            );
        });
    }

   // Update the performSearch function to include sorting by days_old
function performSearch() {
    filteredRequests = filterRequests(allRequests, searchTerm);
    
    // Ensure days_old is positive for all requests
    filteredRequests = filteredRequests.map(req => ({
        ...req,
        days_old: Math.abs(req.days_old) || 0
    }));
    
    // Apply priority sort by default (sort by days_old descending)
    if (currentSort === 'priority') {
        filteredRequests.sort((a, b) => b.days_old - a.days_old);
    } else if (currentSort === 'date') {
        // Sort by date (created_at)
        filteredRequests.sort((a, b) => new Date(a.created_at_timestamp || a.date) - new Date(b.created_at_timestamp || b.date));
    }
    
    // Apply critical filter if active
    if (isCriticalOnlyView) {
        filteredRequests = filteredRequests.filter(req => req.days_old >= 10);
    }
    
    totalRecords = filteredRequests.length;
    totalPages = Math.ceil(totalRecords / perPage);
    currentPage = 1;
    
    renderTable(getPaginatedData());
    updatePaginationInfo();
}
// Update the sortByPriority function
function sortByPriority() {
    filteredRequests.sort((a, b) => {
        // Critical (10+ days) comes first
        const aCritical = a.days_old >= 10;
        const bCritical = b.days_old >= 10;
        
        if (aCritical && !bCritical) return -1;
        if (!aCritical && bCritical) return 1;
        
        // Then high priority (7-9 days)
        const aHigh = a.days_old >= 7;
        const bHigh = b.days_old >= 7;
        
        if (aHigh && !bHigh) return -1;
        if (!aHigh && bHigh) return 1;
        
        // Then medium priority (3-6 days)
        const aMedium = a.days_old >= 3;
        const bMedium = b.days_old >= 3;
        
        if (aMedium && !bMedium) return -1;
        if (!aMedium && bMedium) return 1;
        
        // Then by days old descending within same priority
        return b.days_old - a.days_old;
    });
}

// Update the sortByDate function
function sortByDate() {
    filteredRequests.sort((a, b) => {
        // Use created_at_timestamp if available, otherwise parse the date string
        const aDate = a.created_at_timestamp ? new Date(a.created_at_timestamp) : new Date(a.date);
        const bDate = b.created_at_timestamp ? new Date(b.created_at_timestamp) : new Date(b.date);
        return aDate - bDate;
    });
}
    
    function updateSortButtonStyles() {
        const priorityBtn = document.getElementById('sort-by-priority');
        const dateBtn = document.getElementById('sort-by-date');
        const criticalBtn = document.getElementById('show-critical-only');
        const showAllBtn = document.getElementById('show-all');
        
        if (priorityBtn) {
            if (currentSort === 'priority') {
                priorityBtn.className = 'px-4 py-2 bg-blue-700 text-white rounded-lg text-sm font-medium hover:bg-blue-800 transition flex items-center gap-2';
            } else {
                priorityBtn.className = 'px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition flex items-center gap-2';
            }
        }
        
        if (dateBtn) {
            if (currentSort === 'date') {
                dateBtn.className = 'px-4 py-2 bg-gray-300 text-gray-800 rounded-lg text-sm font-medium hover:bg-gray-400 transition flex items-center gap-2';
            } else {
                dateBtn.className = 'px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition flex items-center gap-2';
            }
        }
        
        if (criticalBtn) {
            if (isCriticalOnlyView) {
                criticalBtn.className = 'px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center gap-2';
                criticalBtn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.954-.833-2.724 0L4.348 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    Showing Critical Only (10+ days)
                `;
            } else {
                criticalBtn.className = 'px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-medium hover:bg-red-200 transition flex items-center gap-2';
                criticalBtn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.954-.833-2.724 0L4.348 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    Show Critical Only (10+ days)
                `;
            }
        }
        
        if (showAllBtn) {
            if (isCriticalOnlyView) {
                showAllBtn.style.display = 'flex';
            } else {
                showAllBtn.style.display = 'none';
            }
        }
    }

    // ============ DATA FETCHING FUNCTIONS ============
    async function fetchDvRequests() {
        try {
            const res = await fetch("{{ route('RegionalOffices.IMSD.fetchAll') }}");
            const data = await res.json();
            
            allRequests = data;
            performSearch(); // Apply current search filter to the new data
            
        } catch (err) {
            console.error('Failed to fetch DV requests', err);
            tableContainer.innerHTML = `<div class="text-center py-16 text-red-500">Failed to load DV requests.</div>`;
        }
    }

    async function fetchAgingDocuments() {
        try {
            const res = await fetch('{{ route("IMSD.aging.documents") }}');
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed to fetch aging documents');
            
            cachedAgingData = data.documents || [];
            renderAgingAlerts(data.documents, data.aging_count);
            
        } catch (err) {
            console.error('Failed to fetch aging documents:', err);
        }
    }

    async function updateStatistics() {
        try {
            const res = await fetch('{{ route("IMSD.aging.statistics") }}');
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed to fetch statistics');
            
            document.getElementById('stat-critical').textContent = data.critical || 0;
            document.getElementById('stat-high').textContent = data.high || 0;
            document.getElementById('stat-medium').textContent = data.medium || 0;
            document.getElementById('stat-total').textContent = data.total_aging || 0;
            
        } catch (err) {
            console.error('Failed to fetch statistics:', err);
        }
    }

    // Make functions available globally for window.handleReceiveAction
    window.fetchDvRequests = fetchDvRequests;
    window.fetchAgingDocuments = fetchAgingDocuments;
    window.updateStatistics = updateStatistics;

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
                <p class="text-gray-500">${searchTerm ? 'Try adjusting your search filter.' : 'No requests available.'}</p>
                ${isCriticalOnlyView ? '<p class="text-sm text-red-600 mt-2">No critical documents (10+ days) found.</p>' : ''}
            </div>
        `;
        updateSelectAllCheckbox();
        return;
    }

    let html = `
        <div class="overflow-x-auto">
            <table class="w-full table-fixed border-collapse divide-y divide-gray-200 text-[13.5px]">
                <thead class="bg-gray-50 text-[13px]">
                    <tr>
                        <th class="px-5 py-3 w-[5%] text-center">
                            <input type="checkbox" id="select-all-checkbox" onclick="toggleSelectAll(this)" class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                        </th>
                        <th class="px-5 py-3 w-[13%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Control No.</th>
                        <th class="px-5 py-3 w-[16%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted By</th>
                        <th class="px-5 py-3 w-[12%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                        <th class="px-5 py-3 w-[11%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-5 py-3 w-[13%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 w-[18%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Submitted</th>
                        <th class="px-5 py-3 w-[12%] text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-[13.5px]">
    `;

    dvRequests.forEach(request => {
        const [bgClass, dotClass, label] = statusColors[request.status] || ['bg-gray-100 text-gray-800','bg-gray-400', request.status];
        const formattedDate = request.date;
        
        const isSelected = Array.from(selectedRequests).some(req => req.id === request.id);
        
        // ✅ Get days_old from the API response
        const daysOld = Math.round(request.days_old) || 0;
        
        // SIMPLIFIED: Determine row styling based on age - LESS INTENSE
        let rowClasses = 'hover:bg-gray-50 transition-colors duration-150';
        let textColorClass = 'text-gray-900';
        let urgencyBadge = '';
        let rowBgClass = '';
        
        if (daysOld >= 10) {
            // CRITICAL: 10+ days - Subtle red tint, NO pulsing
            rowBgClass = 'bg-red-50/50 hover:bg-red-100/50';
            rowClasses = 'border-l-4 border-red-400'; // Removed animate-pulse
            textColorClass = 'text-gray-900'; // Keep text normal color
            urgencyBadge = `
                <div class="inline-flex items-center ml-2">
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 border border-red-200 rounded-full">
                        ${daysOld} DAYS
                    </span>
                </div>
            `;
        } else if (daysOld >= 7) {
            // HIGH: 7-9 days - Subtle orange tint
            rowBgClass = 'bg-orange-50/50 hover:bg-orange-100/50';
            rowClasses = 'border-l-4 border-orange-300';
            textColorClass = 'text-gray-900';
            urgencyBadge = `
                <div class="inline-flex items-center ml-2">
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-orange-700 bg-orange-100 border border-orange-200 rounded-full">
                        ${daysOld} DAYS
                    </span>
                </div>
            `;
        } else if (daysOld >= 3) {
            // MEDIUM: 3-6 days - Subtle yellow tint
            rowBgClass = 'bg-yellow-50/50 hover:bg-yellow-100/50';
            rowClasses = 'border-l-4 border-yellow-300';
            textColorClass = 'text-gray-900';
            urgencyBadge = `
                <div class="inline-flex items-center ml-2">
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-yellow-700 bg-yellow-100 border border-yellow-200 rounded-full">
                        ${daysOld} DAYS
                    </span>
                </div>
            `;
        } else {
            // LOW: 0-2 days - Normal
            rowBgClass = 'bg-white hover:bg-gray-50';
            rowClasses = '';
            textColorClass = 'text-gray-900';
        }
        
        // SIMPLIFIED: Button styling based on age
        let buttonClass = 'bg-green-100 hover:bg-green-200 text-green-700';
        let buttonText = 'Receive';
        
        if (request.status === 'submitted') {
            if (daysOld >= 10) {
                buttonClass = 'bg-green-100 hover:bg-green-200 text-green-700 border border-green-300 font-medium'; // No animation, less intense
                buttonText = 'RECEIVE';
            } else if (daysOld >= 7) {
                buttonClass = 'bg-orange-100 hover:bg-orange-200 text-orange-700 border border-orange-300';
                buttonText = 'Receive';
            } else if (daysOld >= 3) {
                buttonClass = 'bg-yellow-100 hover:bg-yellow-200 text-yellow-700 border border-yellow-300';
                buttonText = 'Receive';
            }
        }
        
        html += `
            <tr class="${rowClasses} ${rowBgClass}" data-dv-id="${request.id}">
                <td class="px-5 py-4 text-center">
                    <input type="checkbox" class="request-checkbox w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500" 
                           data-request-id="${request.id}" 
                           data-status="${request.status}"
                           ${isSelected ? 'checked' : ''}
                           onclick="toggleSelectRequest(${request.id}, '${request.status}')">
                </td>
                <td class="px-6 py-4 text-sm font-medium ${textColorClass}">
                    <div class="flex items-center">
                        <span>${request.accounting_control_number}</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-sm ${textColorClass}">${request.submitted_by}</td>
                <td class="px-6 py-4 text-sm">
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${
                        daysOld >= 10 ? 'bg-red-100 text-red-800' : 
                        daysOld >= 7 ? 'bg-orange-100 text-orange-800' : 
                        daysOld >= 3 ? 'bg-yellow-100 text-yellow-800' : 
                        'bg-blue-100 text-blue-800'
                    }">
                        ${request.program}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm font-semibold ${textColorClass}">
                    ₱${request.amount}
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${
                        daysOld >= 10 ? 'bg-red-100 text-red-800 border border-red-200' : 
                        daysOld >= 7 ? 'bg-orange-100 text-orange-800 border border-orange-200' : 
                        daysOld >= 3 ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : 
                        bgClass
                    }">
                        <span class="w-2 h-2 mr-2 rounded-full ${
                            daysOld >= 10 ? 'bg-red-500' : 
                            daysOld >= 7 ? 'bg-orange-400' : 
                            daysOld >= 3 ? 'bg-yellow-400' : 
                            dotClass
                        }"></span>
                        ${label}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm ${textColorClass}">${formattedDate}</div>
                    <div class="text-xs ${
                        daysOld >= 10 ? 'text-red-600' : 
                        daysOld >= 7 ? 'text-orange-600' : 
                        daysOld >= 3 ? 'text-yellow-600' : 
                        'text-gray-500'
                    } font-medium mt-1">
                        ${daysOld} ${daysOld === 1 ? 'day' : 'days'} ago
                    </div>
                </td>
                <td class="px-5 py-4 text-center text-sm font-medium">
                    ${request.status === 'submitted' 
                        ? `<button type="button" onclick="handleReceiveAction(${request.id}, this)" 
                           class="inline-flex items-center justify-center px-3 py-2 ${buttonClass} text-[13px] font-medium rounded-md transition-all duration-200 min-w-[120px]">
                           ${buttonText}
                           </button>`    
                        : `<a href="/RegionalOffices/IMSD/dvrequests/${request.id}" 
                           class="inline-flex items-center justify-center px-3 py-2 ${
                               daysOld >= 10 ? 'bg-red-100 hover:bg-red-200 text-red-700 border border-red-300' : 
                               daysOld >= 7 ? 'bg-orange-100 hover:bg-orange-200 text-orange-700 border border-orange-300' : 
                               daysOld >= 3 ? 'bg-yellow-100 hover:bg-yellow-200 text-yellow-700 border border-yellow-300' : 
                               'bg-blue-100 hover:bg-blue-200 text-blue-700'
                           } text-xs font-medium rounded-md transition-colors duration-200 min-w-[120px]">
                           View / Process
                           </a>`
                    }
                </td>
            </tr>`;
    });

    html += `</tbody></table></div>`;
    tableContainer.innerHTML = html;
    
    updateSelectAllCheckbox();

}
   function renderAgingAlerts(documents, count) {
    if (!agingAlertsContainer || !documents || documents.length === 0) {
        if (agingAlertsContainer) agingAlertsContainer.innerHTML = '';
        return;
    }

    const agingDocs = documents.filter(doc => !['IMSD_verified', 'completed'].includes(doc.status));
    if (agingDocs.length === 0) {
        agingAlertsContainer.innerHTML = '';
        return;
    }

    // ✅ FIX: Use absolute value for days_old throughout
    agingDocs.forEach(doc => {
        const days = Math.abs(Math.floor(Number(doc.days_old))) || 0; // Ensure positive whole number
        doc.days_old = days; // Update the document with positive value
        doc.urgency = days >= 10 ? 'critical' : days >= 7 ? 'high' : days >= 3 ? 'medium' : 'low';
    });

    const highestUrgency = agingDocs.reduce((highest, doc) => {
        const order = { 'critical': 4, 'high': 3, 'medium': 2, 'low': 1 };
        return order[doc.urgency] > order[highest] ? doc.urgency : highest;
    }, 'low');

    const config = {
        'critical': { icon: '🚨', bg: 'bg-red-50 border-red-500', text: 'text-red-900' },
        'high': { icon: '⚠️', bg: 'bg-orange-50 border-orange-500', text: 'text-orange-900' },
        'medium': { icon: '⏰', bg: 'bg-yellow-50 border-yellow-500', text: 'text-yellow-900' },
        'low': { icon: 'ℹ️', bg: 'bg-blue-50 border-blue-500', text: 'text-blue-900' }
    };

    const c = config[highestUrgency];

    // Check if we have a saved state for the alert container
    const alertState = JSON.parse(localStorage.getItem('agingAlertMinimized') || 'false');
    const isMinimized = alertState === true;

    agingAlertsContainer.innerHTML = `
        <div class="rounded-lg border-l-4 p-5 ${c.bg} shadow-md aging-alert-container">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">${c.icon}</span>
                    <div>
                        <h3 class="font-bold text-lg ${c.text}">Documents Need Processing</h3>
                        <p class="text-sm opacity-80 ${c.text}">${agingDocs.length} document(s) older than 3 days</p>
                    </div>
                </div>
                <button class="minimize-alert-btn text-gray-600 hover:text-gray-800 transition-colors p-1 rounded hover:bg-white/50" 
                        title="${isMinimized ? 'Expand' : 'Minimize'}">
                    <svg class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         style="${isMinimized ? 'transform: rotate(-90deg);' : ''}">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="${isMinimized ? 'M9 5l7 7-7 7' : 'M19 9l-7 7-7-7'}"></path>
                    </svg>
                </button>
            </div>
            <div class="alert-content transition-all duration-300 ease-in-out ${isMinimized ? 'hidden' : 'block'}">
                <div class="space-y-2 mt-4">
                    ${agingDocs.slice(0, 5).map(doc => {
                        const daysOld = Math.abs(Math.floor(doc.days_old)) || 0; // ✅ Ensure positive whole number
                        return `
                            <div class="p-3 bg-white/50 border rounded-lg">
                                <p class="font-semibold text-sm ${c.text}">${doc.control_number}</p>
                                <p class="text-xs opacity-70 ${c.text}">${doc.submitted_by} • ${doc.program}</p>
                                <p class="text-xs font-medium mt-1 text-red-700">${daysOld} days old</p>
                            </div>
                        `;
                    }).join('')}
                    ${agingDocs.length > 5 ? `<p class="text-xs mt-3 opacity-75 ${c.text}">+${agingDocs.length - 5} more documents</p>` : ''}
                </div>
            </div>
        </div>
    `;

    // Add event listener for the minimize button
    const minimizeBtn = agingAlertsContainer.querySelector('.minimize-alert-btn');
    const alertContent = agingAlertsContainer.querySelector('.alert-content');
    
    if (minimizeBtn && alertContent) {
        minimizeBtn.addEventListener('click', function() {
            const isCurrentlyMinimized = alertContent.classList.contains('hidden');
            
            if (isCurrentlyMinimized) {
                // Expand
                alertContent.classList.remove('hidden');
                alertContent.classList.add('block');
                this.innerHTML = `
                    <svg class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                `;
                this.setAttribute('title', 'Minimize');
                localStorage.setItem('agingAlertMinimized', 'false');
            } else {
                // Minimize
                alertContent.classList.remove('block');
                alertContent.classList.add('hidden');
                this.innerHTML = `
                    <svg class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transform: rotate(-90deg);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                `;
                this.setAttribute('title', 'Expand');
                localStorage.setItem('agingAlertMinimized', 'true');
            }
        });
    }
}

    // ============ EVENT LISTENERS ============
    perPageSelect.addEventListener('change', function() {
        perPage = parseInt(this.value);
        currentPage = 1;
        totalPages = Math.ceil(totalRecords / perPage);
        renderTable(getPaginatedData());
        updatePaginationInfo();
    });

    firstPageBtn.addEventListener('click', () => goToPage(1));
    prevPageBtn.addEventListener('click', () => goToPage(currentPage - 1));
    nextPageBtn.addEventListener('click', () => goToPage(currentPage + 1));
    lastPageBtn.addEventListener('click', () => goToPage(totalPages));

    // Search with debounce
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchTerm = this.value;
            performSearch();
        }, 300);
    });

    // Sorting buttons
    document.getElementById('sort-by-priority')?.addEventListener('click', function() {
        currentSort = 'priority';
        sortByPriority();
        currentPage = 1;
        renderTable(getPaginatedData());
        updatePaginationInfo();
        updateSortButtonStyles();
        window.showToast('Sorted by priority (most delayed first)', 'info');
    });

    document.getElementById('sort-by-date')?.addEventListener('click', function() {
        currentSort = 'date';
        sortByDate();
        currentPage = 1;
        renderTable(getPaginatedData());
        updatePaginationInfo();
        updateSortButtonStyles();
        window.showToast('Sorted by submission date (oldest first)', 'info');
    });

    document.getElementById('show-critical-only')?.addEventListener('click', function() {
        if (!isCriticalOnlyView) {
            isCriticalOnlyView = true;
            const criticalCount = filteredRequests.filter(req => req.days_old >= 10).length;
            
            if (criticalCount === 0) {
                window.showToast('No critical documents (10+ days) found', 'warning');
                isCriticalOnlyView = false;
            } else {
                window.showToast(`Showing ${criticalCount} critical document(s) only`, 'info');
            }
        } else {
            isCriticalOnlyView = false;
            window.showToast('Showing all documents', 'info');
        }
        
        performSearch();
        updateSortButtonStyles();
    });

    document.getElementById('show-all')?.addEventListener('click', function() {
        isCriticalOnlyView = false;
        performSearch();
        updateSortButtonStyles();
        window.showToast('Showing all documents', 'info');
    });

    // Modal event handlers
    const bulkActionModal = document.getElementById('bulkActionModal');
    if (bulkActionModal) {
        bulkActionModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeBulkActionModal();
            }
        });
    }

    // ============ AUTO-REFRESH ============
    setInterval(fetchAgingDocuments, 2 * 60 * 1000); // Every 2 minutes
    setInterval(updateStatistics, 2 * 60 * 1000);
    setInterval(fetchDvRequests, 5 * 60 * 1000); // Every 5 minutes

    // ============ INITIALIZATION ============
    updateSortButtonStyles();
    updatePaginationInfo();
    fetchDvRequests();
    fetchAgingDocuments();
    updateStatistics();
});
</script>