@extends('layouts.app')

@section('title', 'Budget Officer - ORS Requests')

@section('content')
<div class="min-h-screen bg-gray-50 pt-24 pb-8">
    <div class="max-w-[95rem] mx-auto px-6">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Received DV Requests</h1>
                <p class="mt-2 text-sm text-gray-600">Manage and obligate disbursement voucher requests with ORS numbers</p>
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

        <!-- ORS Aging Alerts Container -->
        <div id="ors-aging-alerts-container" class="mb-6">
            <!-- Alert will be populated by JavaScript -->
        </div>

        <!-- ORS Aging Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-700 text-sm font-semibold">Critical</p>
                        <p id="ors-stat-critical" class="text-2xl font-bold text-red-600">0</p>
                        <p class="text-xs text-red-600">7+ days old</p>
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
                        <p id="ors-stat-high" class="text-2xl font-bold text-orange-600">0</p>
                        <p class="text-xs text-orange-600">5-7 days old</p>
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
                        <p id="ors-stat-medium" class="text-2xl font-bold text-yellow-600">0</p>
                        <p class="text-xs text-yellow-600">3-5 days old</p>
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
                        <p id="ors-stat-total" class="text-2xl font-bold text-blue-600">0</p>
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
                Show Critical Only (7+ days)
            </button>
            <button id="show-all" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg text-sm font-medium hover:bg-green-200 transition flex items-center gap-2" style="display: none;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
                Show All
            </button>
        </div>

        <!-- Search and Pagination Info -->
        <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex-1">
                <input type="text" id="searchInput" 
                       placeholder="🔍 Search DV Requests (Control No, ORS No, Program, Respocenter, Submitted By)..."
                       class="w-full md:w-2/3 px-4 py-2 border rounded-lg shadow-sm focus:ring focus:ring-blue-200 focus:outline-none">
            </div>
            <div id="pagination-info" class="text-sm text-gray-600">
                Showing <span id="current-range">0</span> of <span id="total-records">0</span> records
            </div>
        </div>

        <!-- Main Content Card -->
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

<!-- Toast Container -->
<div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col space-y-2"></div>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentUserId = {{ auth()->id() }};
    const tableContainer = document.getElementById('dv-table-container');
    const searchInput = document.getElementById('searchInput');
    const connectionStatus = document.getElementById('connection-status');
    const toastContainer = document.getElementById('toast-container');
    const orsAgingAlertsContainer = document.getElementById('ors-aging-alerts-container');
    
    // Pagination elements
    const perPageSelect = document.getElementById('per-page-select');
    const firstPageBtn = document.getElementById('first-page');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');
    const lastPageBtn = document.getElementById('last-page');
    const pageNumbersContainer = document.getElementById('page-numbers');
    const paginationInfo = document.getElementById('pagination-info');
    const currentRangeSpan = document.getElementById('current-range');
    const totalRecordsSpan = document.getElementById('total-records');

    let allRequests = [];
    let filteredRequests = [];
    let searchTerm = "";
    let cachedOrsAgingData = [];
    let isCriticalOnlyView = false;
    let currentSort = 'priority';
    
    // Pagination state
    let currentPage = 1;
    let perPage = 25;
    let totalPages = 1;
    let totalRecords = 0;

    const statusColors = {
        'Incoming-GIP_verified': ['bg-gray-100 text-gray-800','bg-gray-400','Pending'],
        'Incoming-TUPAD_verified': ['bg-gray-100 text-gray-800','bg-gray-400','Pending'],
        'Incoming-TEV_verified': ['bg-gray-100 text-gray-800','bg-gray-400','Pending'],
        'ORS_received': ['bg-blue-100 text-blue-800','bg-blue-400','Received'],
        'ORS_in_process': ['bg-indigo-100 text-indigo-800','bg-indigo-400','In Process'],
        'ORS_onhold': ['bg-yellow-100 text-yellow-800','bg-yellow-400','On Hold'],
        'ORS_for_revision': ['bg-orange-100 text-orange-800','bg-orange-400','For Revision'],
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

    // Subscribe to Budget Officer channel
    const budgetChannel = pusher.subscribe('dv-requests-budget-officer');

    // ============ PUSHER EVENT LISTENERS ============
    
    // Listen for new DV request verifications from incoming units
    budgetChannel.bind('dv-request-verified', function(data) {
        console.log('DV request verified by incoming unit:', data);
        fetchDvRequests();
        setTimeout(() => {
            fetchOrsAgingDocuments();
            updateOrsStatistics();
        }, 500);
    });

    // Listen for received events
    budgetChannel.bind('dv-request-received', function(data) {
        console.log('DV request received by Budget Officer:', data);
        
        if (data.action_performer_id === currentUserId) {
            const controlNumber = data.accounting_control_number || 
                                 data.dv_request?.accounting_control_number ||
                                 data.control_number ||
                                 'Unknown Document';
            
            showToast(`Document ${controlNumber} received successfully`, 'success');
        }
        
        fetchDvRequests();
        setTimeout(() => {
            fetchOrsAgingDocuments();
            updateOrsStatistics();
        }, 500);
    });

    // Listen for processing started events
    budgetChannel.bind('dv-request-processing', function(data) {
        console.log('DV request processing started:', data);
        
        if (data.action_performer_id === currentUserId) {
            showToast('Document processing started successfully', 'success');
        }
        
        fetchDvRequests();
        setTimeout(() => {
            fetchOrsAgingDocuments();
            updateOrsStatistics();
        }, 500);
    });

    // Listen for obligated events
    budgetChannel.bind('ors-obligated', function(data) {
        console.log('ORS obligated:', data);
        
        if (data.action_performer_id === currentUserId) {
            showToast(`ORS ${data.ors_number || 'document'} has been obligated`, 'success');
        }
        
        fetchDvRequests();
        setTimeout(() => {
            fetchOrsAgingDocuments();
            updateOrsStatistics();
        }, 1000);
    });

    // Listen for onhold events
    budgetChannel.bind('ors-onhold', function(data) {
        console.log('ORS put on hold:', data);
        
        if (data.action_performer_id === currentUserId) {
            showToast('Document put on hold', 'warning');
        }
        
        fetchDvRequests();
        setTimeout(() => {
            fetchOrsAgingDocuments();
            updateOrsStatistics();
        }, 1000);
    });

    // Listen for revised events
    budgetChannel.bind('ors-revised', function(data) {
        console.log('ORS revised:', data);
        
        if (data.action_performer_id === currentUserId) {
            showToast('Document marked for revision', 'info');
        }
        
        fetchDvRequests();
        setTimeout(() => {
            fetchOrsAgingDocuments();
            updateOrsStatistics();
        }, 1000);
    });

    // Listen for status updates
    budgetChannel.bind('status-updated', function(data) {
        console.log('DV request status updated:', data);
        
        if (data.action_performer_id === currentUserId) {
            showToast('Document status updated', 'info');
        }
        
        fetchDvRequests();
        setTimeout(() => {
            fetchOrsAgingDocuments();
            updateOrsStatistics();
        }, 1000);
    });

    // Listen for amount updates
    budgetChannel.bind('amount-updated', function(data) {
        console.log('Amount updated:', data);
        
        if (data.action_performer_id === currentUserId) {
            showToast(`Amount updated for request #${data.dv_request_id}`, 'success');
        }
        
        fetchDvRequests();
        setTimeout(() => {
            fetchOrsAgingDocuments();
            updateOrsStatistics();
        }, 1000);
    });

    // Listen for ORS aging alerts
    budgetChannel.bind('ors-aging-alert', function(data) {
        console.log('ORS Aging alert received:', data);
        showToast(`Aging ORS document: ${data.ors_number || data.control_number} is ${data.days_in_status} days old`, 'warning');
        fetchOrsAgingDocuments();
        updateOrsStatistics();
    });

    budgetChannel.bind('ORS-aging-update', function(data) {
        console.log('ORS aging update received:', data);
        showToast('ORS aging data updated', 'info');
        fetchOrsAgingDocuments();
        updateOrsStatistics();
    });

    // ============ TOAST FUNCTIONS ============
    function showToast(message, type = 'success') {
        const toastId = 'toast-' + Date.now();
        const typeConfig = {
            'success': { borderColor: 'border-green-500', bgColor: 'bg-green-100', textColor: 'text-green-500', icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>` },
            'error': { borderColor: 'border-red-500', bgColor: 'bg-red-100', textColor: 'text-red-500', icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>` },
            'warning': { borderColor: 'border-orange-500', bgColor: 'bg-orange-100', textColor: 'text-orange-500', icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>` },
            'info': { borderColor: 'border-blue-500', bgColor: 'bg-blue-100', textColor: 'text-blue-500', icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>` }
        };
        
        const config = typeConfig[type] || typeConfig.success;
        
        const toastHTML = `
            <div id="${toastId}" class="toast-notification transform transition-all duration-300 ease-in-out translate-x-full opacity-0">
                <div class="flex items-center w-full max-w-sm p-4 mb-4 text-gray-500 bg-white rounded-lg shadow-lg border-l-4 ${config.borderColor}" role="alert">
                    <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg ${config.textColor} ${config.bgColor}">
                        ${config.icon}
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

        setTimeout(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
            toast.classList.add('translate-x-0', 'opacity-100');
        }, 100);

        setTimeout(() => {
            dismissToast(toastId);
        }, 4000);
    }

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

    // ============ ACTION HANDLERS ============
    window.handleReceiveAction = async function(requestId, button) {
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
                fetchDvRequests();
                fetchOrsAgingDocuments();
                updateOrsStatistics();
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

    // Manual refresh function
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
        
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.value = '';
        
        allRequests = [];
        cachedOrsAgingData = [];
        searchTerm = "";
        isCriticalOnlyView = false;
        currentSort = 'priority';
        
        fetchDvRequests();
        fetchOrsAgingDocuments();
        updateOrsStatistics();
        updateSortButtonStyles();
    }

    // ============ PAGINATION FUNCTIONS ============
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
        const maxVisiblePages = 5;
        
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        if (startPage > 1) {
            pages.push(1);
            if (startPage > 2) pages.push('...');
        }
        
        for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) pages.push('...');
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
                    page === currentPage 
                        ? 'bg-blue-500 text-white' 
                        : 'text-gray-700 hover:bg-gray-200'
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

    function getPaginatedData() {
        const startIndex = (currentPage - 1) * perPage;
        const endIndex = startIndex + perPage;
        return filteredRequests.slice(startIndex, endIndex);
    }

    // ============ SEARCH AND FILTER ============
    function filterRequests(requests, searchTerm) {
        if (!searchTerm) return requests;
        
        const searchLower = searchTerm.toLowerCase();
        return requests.filter(request => {
            return (
                (request.control_no && request.control_no.toLowerCase().includes(searchLower)) ||
                (request.ors_number && request.ors_number.toLowerCase().includes(searchLower)) ||
                (request.submitted_by && request.submitted_by.toLowerCase().includes(searchLower)) ||
                (request.program && request.program.toLowerCase().includes(searchLower)) ||
                (request.respocenter && request.respocenter.toLowerCase().includes(searchLower)) ||
                (request.particulars && request.particulars.toLowerCase().includes(searchLower))
            );
        });
    }
function performSearch() {
    // Start with all requests
    filteredRequests = allRequests;
    
    // Apply search filter first
    if (searchTerm) {
        filteredRequests = filterRequests(filteredRequests, searchTerm);
    }
    
    // FIX: Calculate days properly for all requests
    filteredRequests = filteredRequests.map(req => {
        let days = 0;
        
        // Use the same calculation as in renderTable
        if (req.days_old !== undefined && req.days_old !== null) {
            days = Math.abs(Math.round(parseFloat(req.days_old))) || 0;
        } else if (req.days_in_status !== undefined && req.days_in_status !== null) {
            days = Math.abs(Math.round(parseFloat(req.days_in_status))) || 0;
        } else {
            // Calculate from date if not provided
            const requestDate = req.created_at_timestamp ? new Date(req.created_at_timestamp) : new Date(req.date);
            const now = new Date();
            const diffTime = Math.abs(now - requestDate);
            days = Math.round(diffTime / (1000 * 60 * 60 * 24));
        }
        
        // Ensure fresh requests show 0 days
        if (days < 1) {
            days = 0;
        }
        
        return {
            ...req,
            days_in_status: days,
            days_old: days
        };
    });
    
    // Apply critical filter if active
    if (isCriticalOnlyView) {
        filteredRequests = filteredRequests.filter(req => req.days_old >= 7);
    }
    
    // Apply sorting
    if (currentSort === 'priority') {
        sortByPriority();
    } else if (currentSort === 'date') {
        sortByDate();
    }
    
    totalRecords = filteredRequests.length;
    totalPages = Math.ceil(totalRecords / perPage);
    currentPage = 1;
    
    renderTable(getPaginatedData());
    updatePaginationInfo();
}
    function sortByPriority() {
    filteredRequests.sort((a, b) => {
        // 1. PRIMARY SORT: ORS_received status comes FIRST
        const aIsOrsReceived = a.status === 'ORS_received';
        const bIsOrsReceived = b.status === 'ORS_received';
        
        if (aIsOrsReceived && !bIsOrsReceived) return -1;
        if (!aIsOrsReceived && bIsOrsReceived) return 1;
        
        // 2. SECONDARY SORT: Incoming verified statuses come second
        const aIsIncomingVerified = ['Incoming-GIP_verified', 'Incoming-TUPAD_verified', 'Incoming-TEV_verified'].includes(a.status);
        const bIsIncomingVerified = ['Incoming-GIP_verified', 'Incoming-TUPAD_verified', 'Incoming-TEV_verified'].includes(b.status);
        
        if (aIsIncomingVerified && !bIsIncomingVerified) return -1;
        if (!aIsIncomingVerified && bIsIncomingVerified) return 1;
        
        // 3. TERTIARY SORT: Age-based priority for the rest
        const aDays = Math.abs(Math.floor(a.days_old || a.days_in_status)) || 0;
        const bDays = Math.abs(Math.floor(b.days_old || b.days_in_status)) || 0;
        
        const aCritical = aDays >= 7;
        const bCritical = bDays >= 7;
        
        if (aCritical && !bCritical) return -1;
        if (!aCritical && bCritical) return 1;
        
        const aHigh = aDays >= 5;
        const bHigh = bDays >= 5;
        
        if (aHigh && !bHigh) return -1;
        if (!aHigh && bHigh) return 1;
        
        const aMedium = aDays >= 3;
        const bMedium = bDays >= 3;
        
        if (aMedium && !bMedium) return -1;
        if (!aMedium && bMedium) return 1;
        
        // Then by days old descending within same priority
        return bDays - aDays;
    });
}

    function sortByDate() {
        filteredRequests.sort((a, b) => {
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
                    Showing Critical Only (7+ days)
                `;
            } else {
                criticalBtn.className = 'px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-medium hover:bg-red-200 transition flex items-center gap-2';
                criticalBtn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.954-.833-2.724 0L4.348 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    Show Critical Only (7+ days)
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
                ${isCriticalOnlyView ? '<p class="text-sm text-red-600 mt-2">No critical documents (7+ days) found.</p>' : ''}
            </div>
        `;
        return;
    }

    let html = `
        <div class="overflow-x-auto">
            <table class="w-full table-fixed border-collapse divide-y divide-gray-200 text-[13.5px]">
                <thead class="bg-gray-50 text-[13px]">
                    <tr>
                        <th class="px-5 py-3 w-[12%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Control No.</th>
                        <th class="px-5 py-3 w-[10%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ORS No.</th>
                        <th class="px-5 py-3 w-[14%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted By</th>
                        <th class="px-5 py-3 w-[12%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                        <th class="px-5 py-3 w-[14%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Particulars</th>
                        <th class="px-5 py-3 w-[12%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-5 py-3 w-[14%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 w-[12%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Submitted</th>
                        <th class="px-5 py-3 w-[10%] text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-[13.5px]">
    `;

    dvRequests.forEach(request => {
        const [bgClass, dotClass, label] = statusColors[request.status] || ['bg-gray-100 text-gray-800','bg-gray-400', request.status];
        
        // FIX: More accurate days calculation - use Math.round() instead of Math.floor()
        let daysInStatus = 0;
        
        // Try to get days_old from the request data
        if (request.days_old !== undefined && request.days_old !== null) {
            // Handle both positive and negative values with Math.abs()
            daysInStatus = Math.abs(Math.round(parseFloat(request.days_old))) || 0;
        } else if (request.days_in_status !== undefined && request.days_in_status !== null) {
            // Fallback to days_in_status
            daysInStatus = Math.abs(Math.round(parseFloat(request.days_in_status))) || 0;
        } else {
            // Calculate from date if not provided
            const requestDate = request.created_at_timestamp ? new Date(request.created_at_timestamp) : new Date(request.date);
            const now = new Date();
            const diffTime = Math.abs(now - requestDate);
            daysInStatus = Math.round(diffTime / (1000 * 60 * 60 * 24));
        }
        
        // Ensure it's not showing 1 day for fresh requests - anything less than 24 hours should be 0
        if (daysInStatus < 1) {
            daysInStatus = 0;
        }
        
        // Log for debugging
        console.log('Request days calculation:', {
            control_no: request.control_no,
            days_old: request.days_old,
            days_in_status: request.days_in_status,
            calculated_days: daysInStatus
        });
        
        // Determine row styling based on age
        let rowClasses = 'hover:bg-gray-50 transition-colors duration-150';
        let textColorClass = 'text-gray-900';
        let rowBgClass = '';
        
        if (daysInStatus >= 7) {
            rowBgClass = 'bg-red-50/50 hover:bg-red-100/50';
            rowClasses = 'border-l-4 border-red-400';
        } else if (daysInStatus >= 5) {
            rowBgClass = 'bg-orange-50/50 hover:bg-orange-100/50';
            rowClasses = 'border-l-4 border-orange-300';
        } else if (daysInStatus >= 3) {
            rowBgClass = 'bg-yellow-50/50 hover:bg-yellow-100/50';
            rowClasses = 'border-l-4 border-yellow-300';
        } else {
            rowBgClass = 'bg-white hover:bg-gray-50';
        }
        
        // Button styling based on age and status
        let buttonClass = 'bg-green-100 hover:bg-green-200 text-green-700';
        let buttonText = 'Receive';
        
        if (['Incoming-GIP_verified', 'Incoming-TUPAD_verified', 'Incoming-TEV_verified'].includes(request.status)) {
            if (daysInStatus >= 7) {
                buttonClass = 'bg-green-100 hover:bg-green-200 text-green-700 border border-green-300 font-medium';
                buttonText = 'RECEIVE';
            } else if (daysInStatus >= 5) {
                buttonClass = 'bg-orange-100 hover:bg-orange-200 text-orange-700 border border-orange-300';
            } else if (daysInStatus >= 3) {
                buttonClass = 'bg-yellow-100 hover:bg-yellow-200 text-yellow-700 border border-yellow-300';
            }
        } else {
            // For already received documents
            if (daysInStatus >= 7) {
                buttonClass = 'bg-red-100 hover:bg-red-200 text-red-700 border border-red-300 font-medium';
                buttonText = 'VIEW / PROCESS';
            } else if (daysInStatus >= 5) {
                buttonClass = 'bg-orange-100 hover:bg-orange-200 text-orange-700 border border-orange-300';
                buttonText = 'View / Process';
            } else if (daysInStatus >= 3) {
                buttonClass = 'bg-yellow-100 hover:bg-yellow-200 text-yellow-700 border border-yellow-300';
                buttonText = 'View / Process';
            } else {
                buttonClass = 'bg-blue-100 hover:bg-blue-200 text-blue-700';
                buttonText = 'View / Process';
            }
        }
        
        html += `
            <tr class="${rowClasses} ${rowBgClass}">
                <td class="px-6 py-4 text-sm font-medium ${textColorClass}">
                    ${request.control_no ?? '—'}
                </td>
                <td class="px-6 py-4 text-sm ${textColorClass}">${request.ors_number ?? '—'}</td>
                <td class="px-6 py-4 text-sm ${textColorClass}">${request.submitted_by}</td>
                <td class="px-6 py-4 text-sm">
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${
                        daysInStatus >= 7 ? 'bg-red-100 text-red-800' : 
                        daysInStatus >= 5 ? 'bg-orange-100 text-orange-800' : 
                        daysInStatus >= 3 ? 'bg-yellow-100 text-yellow-800' : 
                        'bg-blue-100 text-blue-800'
                    }">
                        ${request.program}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm font-semibold ${textColorClass}">${request.particulars}</td>
                <td class="px-6 py-4 text-sm font-semibold ${textColorClass}">₱${request.amount}</td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${
                        daysInStatus >= 7 ? 'bg-red-100 text-red-800 border border-red-200' : 
                        daysInStatus >= 5 ? 'bg-orange-100 text-orange-800 border border-orange-200' : 
                        daysInStatus >= 3 ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : 
                        bgClass
                    }">
                        <span class="w-2 h-2 mr-2 rounded-full ${
                            daysInStatus >= 7 ? 'bg-red-500' : 
                            daysInStatus >= 5 ? 'bg-orange-400' : 
                            daysInStatus >= 3 ? 'bg-yellow-400' : 
                            dotClass
                        }"></span>
                        ${label}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm ${textColorClass}">${request.date}</div>
                    <div class="text-xs ${
                        daysInStatus >= 7 ? 'text-red-600' : 
                        daysInStatus >= 5 ? 'text-orange-600' : 
                        daysInStatus >= 3 ? 'text-yellow-600' : 
                        'text-gray-500'
                    } font-medium mt-1">
                        ${daysInStatus} ${daysInStatus === 1 ? 'day' : 'days'} old
                    </div>
                </td>
                <td class="px-5 py-4 text-center text-sm font-medium">
                    ${['Incoming-GIP_verified', 'Incoming-TUPAD_verified', 'Incoming-TEV_verified'].includes(request.status) 
                        ? `<button type="button" onclick="handleReceiveAction(${request.id}, this)" 
                           class="inline-flex items-center justify-center px-3 py-2 ${buttonClass} text-[13px] font-medium rounded-md transition-all duration-200 min-w-[120px]">
                           ${buttonText}
                           </button>`    
                        : `<a href="/BudgetOfficer/dvrequests/${request.id}" 
                           class="inline-flex items-center justify-center px-3 py-2 ${buttonClass} text-xs font-medium rounded-md transition-colors duration-200 min-w-[120px]">
                           ${buttonText}
                           </a>`
                    }
                </td>
            </tr>`;
    });

    html += `</tbody></table></div>`;
    tableContainer.innerHTML = html;
}
    async function fetchDvRequests() {
        try {
            const res = await fetch("{{ route('BudgetOfficer.dvrequests.fetch') }}?search=" + encodeURIComponent(searchTerm));
            const data = await res.json();
            
            // Log the data to see what's coming from backend
            console.log('DV Requests from backend:', data.map(req => ({
                id: req.id,
                control_no: req.control_no,
                days_old: req.days_old,
                days_in_status: req.days_in_status,
                status: req.status
            })));
            
            allRequests = data;
            performSearch();
            
        } catch (err) {
            console.error('Failed to fetch DV requests', err);
            tableContainer.innerHTML = `<div class="text-center py-16 text-red-500">Failed to load DV requests.</div>`;
        }
    }

    // ============ ORS AGING DOCUMENTS FUNCTIONS ============
    async function fetchOrsAgingDocuments() {
        try {
            const res = await fetch('{{ route("BudgetOfficer.ors-aging.documents") }}');
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const data = await res.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch ORS aging documents');
            }
            
            cachedOrsAgingData = data.documents || [];
            
            if (orsAgingAlertsContainer) {
                renderOrsAgingAlerts(data.documents, data.aging_count);
            }
            
        } catch (err) {
            console.error('Failed to fetch ORS aging documents:', err);
            
            if (!err.message.includes('404')) {
                showToast('Failed to load ORS aging documents: ' + err.message, 'error');
            }
            
            if (orsAgingAlertsContainer) {
                orsAgingAlertsContainer.innerHTML = '';
            }
        }
    }

    async function updateOrsStatistics() {
        try {
            const res = await fetch('{{ route("BudgetOfficer.ors-aging.statistics") }}');
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const data = await res.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch ORS statistics');
            }
            
            document.getElementById('ors-stat-critical').textContent = data.critical || 0;
            document.getElementById('ors-stat-high').textContent = data.high || 0;
            document.getElementById('ors-stat-medium').textContent = data.medium || 0;
            document.getElementById('ors-stat-total').textContent = data.total_aging || 0;
            
        } catch (err) {
            console.error('Failed to fetch ORS statistics:', err);
        }
    }

   function renderOrsAgingAlerts(documents, count) {
    if (!orsAgingAlertsContainer || !documents || documents.length === 0) {
        if (orsAgingAlertsContainer) orsAgingAlertsContainer.innerHTML = '';
        return;
    }

    // Filter for aging ORS documents
    const agingDocs = documents.filter(doc => !['ORS_obligated', 'completed'].includes(doc.status));
    
    if (agingDocs.length === 0) {
        orsAgingAlertsContainer.innerHTML = '';
        return;
    }

    // Ensure positive days_in_status values using Math.abs()
    agingDocs.forEach(doc => {
        const days = Math.abs(Math.floor(Number(doc.days_in_status))) || 0;
        doc.days_in_status = days;
        doc.urgency = days >= 7 ? 'critical' : days >= 5 ? 'high' : days >= 3 ? 'medium' : 'low';
    });

    // Calculate statistics
    const stats = {
        critical: agingDocs.filter(doc => doc.days_in_status >= 7).length,
        high: agingDocs.filter(doc => doc.days_in_status >= 5 && doc.days_in_status < 7).length,
        medium: agingDocs.filter(doc => doc.days_in_status >= 3 && doc.days_in_status < 5).length,
        total: agingDocs.length
    };

    // Find oldest document
    const oldestDoc = agingDocs.reduce((oldest, doc) => {
        const docDays = Math.abs(Math.floor(doc.days_in_status)) || 0;
        const oldestDays = Math.abs(Math.floor(oldest.days_in_status)) || 0;
        return docDays > oldestDays ? doc : oldest;
    }, agingDocs[0]);

    // Calculate average age
    const avgAge = Math.abs(Math.floor(agingDocs.reduce((sum, doc) => sum + (Math.abs(Math.floor(doc.days_in_status)) || 0), 0) / agingDocs.length));

    // Determine overall urgency level
    const highestUrgency = stats.critical > 0 ? 'critical' : 
                          stats.high > 0 ? 'high' : 
                          stats.medium > 0 ? 'medium' : 'low';

    const config = {
        'critical': { 
            icon: '🚨', 
            bg: 'bg-red-50 border-red-500', 
            text: 'text-red-900',
            accentBg: 'bg-red-100',
            accentText: 'text-red-700'
        },
        'high': { 
            icon: '⚠️', 
            bg: 'bg-orange-50 border-orange-500', 
            text: 'text-orange-900',
            accentBg: 'bg-orange-100',
            accentText: 'text-orange-700'
        },
        'medium': { 
            icon: '⏰', 
            bg: 'bg-yellow-50 border-yellow-500', 
            text: 'text-yellow-900',
            accentBg: 'bg-yellow-100',
            accentText: 'text-yellow-700'
        },
        'low': { 
            icon: 'ℹ️', 
            bg: 'bg-blue-50 border-blue-500', 
            text: 'text-blue-900',
            accentBg: 'bg-blue-100',
            accentText: 'text-blue-700'
        }
    };

    const c = config[highestUrgency];

    // Check if minimized
    const alertState = JSON.parse(localStorage.getItem('orsAgingAlertMinimized') || 'false');
    const isMinimized = alertState === true;

    // Build summary text
    let summaryText = `You currently have <strong>${stats.total} aging ORS document${stats.total > 1 ? 's' : ''}</strong> that require attention. `;
    
    // Add breakdown by priority
    const priorities = [];
    if (stats.critical > 0) priorities.push(`<strong class="text-red-700">${stats.critical} critical</strong> (7+ days old)`);
    if (stats.high > 0) priorities.push(`<strong class="text-orange-700">${stats.high} high priority</strong> (5-7 days)`);
    if (stats.medium > 0) priorities.push(`<strong class="text-yellow-700">${stats.medium} medium priority</strong> (3-5 days)`);
    
    if (priorities.length > 0) {
        if (priorities.length === 1) {
            summaryText += `This includes ${priorities[0]}. `;
        } else if (priorities.length === 2) {
            summaryText += `This includes ${priorities[0]} and ${priorities[1]}. `;
        } else {
            summaryText += `This includes ${priorities[0]}, ${priorities[1]}, and ${priorities[2]}. `;
        }
    }
    
    summaryText += `The average age of pending ORS documents is <strong>${avgAge} days</strong>, with the oldest being <strong class="${c.accentText}">${oldestDoc.control_number || oldestDoc.ors_number || 'Unknown'}</strong> at <strong>${oldestDoc.days_in_status} days old</strong> from ${oldestDoc.submitted_by || 'Unknown'}.`;
    
    // Add urgent action message if critical
    let urgentMessage = '';
    if (stats.critical > 0) {
        urgentMessage = `
            <div class="mt-3 pt-3 border-t ${c.accentText === 'text-red-700' ? 'border-red-200' : 'border-gray-200'}">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-sm text-red-800 leading-relaxed">
                        <strong>Urgent:</strong> ${stats.critical} ORS document${stats.critical > 1 ? 's have' : ' has'} exceeded the 7-day processing threshold and require${stats.critical === 1 ? 's' : ''} immediate attention.
                    </p>
                </div>
            </div>
        `;
    }

    orsAgingAlertsContainer.innerHTML = `
        <div class="rounded-lg border-l-4 p-4 ${c.bg} shadow-md ors-aging-alert-container">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 flex-1">
                    <span class="text-2xl flex-shrink-0">${c.icon}</span>
                    <div class="flex-1">
                        <h3 class="font-bold text-base ${c.text} mb-2">ORS Aging Documents Summary</h3>
                        <div class="ors-alert-content transition-all duration-300 ease-in-out ${isMinimized ? 'hidden' : 'block'}">
                            <p class="text-sm ${c.text} leading-relaxed opacity-90">
                                ${summaryText}
                            </p>
                            ${urgentMessage}
                        </div>
                        <div class="ors-alert-collapsed transition-all duration-300 ease-in-out ${isMinimized ? 'block' : 'hidden'}">
                            <p class="text-sm ${c.text} opacity-90">
                                <strong>${stats.total}</strong> aging ORS document${stats.total > 1 ? 's' : ''} 
                                ${stats.critical > 0 ? `(<strong class="text-red-700">${stats.critical} critical</strong>)` : ''} • 
                                Oldest: <strong>${oldestDoc.days_in_status} days</strong>
                            </p>
                        </div>
                    </div>
                </div>
                <button class="minimize-ors-alert-btn text-gray-600 hover:text-gray-800 transition-colors p-1 rounded hover:bg-white/50 flex-shrink-0" 
                        title="${isMinimized ? 'Expand' : 'Minimize'}">
                    <svg class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         style="${isMinimized ? 'transform: rotate(-90deg);' : ''}">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="${isMinimized ? 'M9 5l7 7-7 7' : 'M19 9l-7 7-7-7'}"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;

    // Add event listener for the minimize button
    const minimizeBtn = orsAgingAlertsContainer.querySelector('.minimize-ors-alert-btn');
    const alertContent = orsAgingAlertsContainer.querySelector('.ors-alert-content');
    const alertCollapsed = orsAgingAlertsContainer.querySelector('.ors-alert-collapsed');
    
    if (minimizeBtn && alertContent && alertCollapsed) {
        minimizeBtn.addEventListener('click', function() {
            const isCurrentlyMinimized = alertContent.classList.contains('hidden');
            
            if (isCurrentlyMinimized) {
                // Expand
                alertContent.classList.remove('hidden');
                alertContent.classList.add('block');
                alertCollapsed.classList.remove('block');
                alertCollapsed.classList.add('hidden');
                this.innerHTML = `
                    <svg class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                `;
                this.setAttribute('title', 'Minimize');
                localStorage.setItem('orsAgingAlertMinimized', 'false');
            } else {
                // Minimize
                alertContent.classList.remove('block');
                alertContent.classList.add('hidden');
                alertCollapsed.classList.remove('hidden');
                alertCollapsed.classList.add('block');
                this.innerHTML = `
                    <svg class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transform: rotate(-90deg);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                `;
                this.setAttribute('title', 'Expand');
                localStorage.setItem('orsAgingAlertMinimized', 'true');
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
        showToast('Sorted by priority (most delayed first)', 'info');
    });

    document.getElementById('sort-by-date')?.addEventListener('click', function() {
        currentSort = 'date';
        sortByDate();
        currentPage = 1;
        renderTable(getPaginatedData());
        updatePaginationInfo();
        updateSortButtonStyles();
        showToast('Sorted by submission date (oldest first)', 'info');
    });

    // Show Critical Only button
    document.getElementById('show-critical-only')?.addEventListener('click', function() {
        if (!isCriticalOnlyView) {
            isCriticalOnlyView = true;
            searchInput.value = ''; // Clear search when switching to critical view
            searchTerm = '';
            
            // Count critical documents from all requests
            const criticalCount = allRequests.filter(req => {   
                const days = Math.abs(Math.floor(req.days_old)) || 0;
                return days >= 7;
            }).length;
            
            if (criticalCount === 0) {
                showToast('No critical documents (7+ days) found', 'warning');
                isCriticalOnlyView = false;
            } else {
                showToast(`Showing ${criticalCount} critical document(s) only`, 'info');
            }
        } else {
            isCriticalOnlyView = false;
            showToast('Showing all documents', 'info');
        }
        
        performSearch();
        updateSortButtonStyles();
    });

    // Show All button
    document.getElementById('show-all')?.addEventListener('click', function() {
        isCriticalOnlyView = false;
        searchInput.value = ''; // Clear search as well
        searchTerm = '';
        performSearch();
        updateSortButtonStyles();
        showToast('Showing all documents', 'info');
    });

    // ============ AUTO-REFRESH ============
    setInterval(fetchOrsAgingDocuments, 2 * 60 * 1000); // Every 2 minutes
    setInterval(updateOrsStatistics, 2 * 60 * 1000);
    setInterval(fetchDvRequests, 5 * 60 * 1000); // Every 5 minutes

    // ============ INITIALIZATION ============
    updatePaginationInfo();
    updateSortButtonStyles();
    fetchDvRequests();
    fetchOrsAgingDocuments();
    updateOrsStatistics();
});
</script>
@endsection