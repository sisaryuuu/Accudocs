@extends('layouts.app')

@section('title', 'RANCA - DV Requests')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">RANCA DV Requests</h1>
                <p class="mt-2 text-sm text-gray-600">Manage DV requests received from Outgoing for cash availability validation</p>
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

        <!-- Statistics Cards -->
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

        <!-- Search Input -->
        <div class="mb-4">
            <input type="text" id="searchInput" 
                   placeholder="🔍 Search DV Requests (Control No, ORS No, Program, Respocenter, Submitted By)..."
                   class="w-full md:w-1/2 px-4 py-2 border rounded-lg shadow-sm focus:ring focus:ring-blue-200 focus:outline-none">
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
    const agingAlertsContainer = document.getElementById('aging-alerts-container');
    let allRequests = [];
    let searchTerm = "";
    let cachedAgingData = [];

    const statusColors = {
        'Outgoing_finalized': ['bg-gray-100 text-gray-800','bg-gray-400','Pending'],
        'RANCA_received': ['bg-yellow-100 text-yellow-800','bg-yellow-400','Received'],
        'RANCA_in_process': ['bg-blue-100 text-blue-800','bg-blue-400','In Process'],
        'RANCA_validated': ['bg-green-100 text-green-800','bg-green-400','Validated'],
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

    // Monitor Pusher connection state changes
    pusher.connection.bind('state_change', function(states) {
        console.log('🔄 Pusher state changed:', states.previous, '→', states.current);
        updateConnectionStatus(states.current);
    });

    // Subscribe to RANCA channel
    const rancaChannel = pusher.subscribe('dv-requests-ranca');

    // Log when subscription succeeds
    rancaChannel.bind('pusher:subscription_succeeded', function() {
        console.log('✅ Successfully subscribed to dv-requests-ranca channel');
    });

    // Log when subscription fails
    rancaChannel.bind('pusher:subscription_error', function(status) {
        console.error('❌ Failed to subscribe to dv-requests-ranca channel:', status);
    });

    // ============ CATCH ALL EVENTS FOR DEBUGGING ============
    rancaChannel.bind_global(function(eventName, data) {
        console.log('📡 [RANCA Channel] Event received:', eventName, data);
    });

    // ============ PUSHER EVENT LISTENERS ============

    // Listen for new DV request submissions from Outgoing - NO FILTERING (global event)
    rancaChannel.bind('dv-request-submitted', function(data) {
        console.log('✅ New DV request received from Outgoing:', data);
        showToast(`New request from ${data.submitted_by || 'Outgoing'} - ${data.control_number || '#' + data.dv_request_id}`, 'success');
        
        // Immediately refresh the table
        fetchDvRequests();
        
        // Check if this new document affects aging
        setTimeout(() => {
            fetchAgingDocuments();
            updateStatistics();
        }, 500);
    });

    // ALTERNATIVE EVENT NAME - in case Outgoing uses different name
    rancaChannel.bind('outgoing-finalized', function(data) {
        console.log('✅ Document finalized by Outgoing:', data);
        showToast(`New request from Outgoing - ${data.control_number || 'N/A'}`, 'success');
        
        fetchDvRequests();
        setTimeout(() => {
            fetchAgingDocuments();
            updateStatistics();
        }, 500);
    });

    // Listen for received events - WITH USER FILTERING
    rancaChannel.bind('dv-request-received', function(data) {
        console.log('📥 DV request received:', data);
        
        // Only show toast if current user performed the action
        if (data.action_performer_id === currentUserId) {
            let message = `Document ${data.control_number || data.dv_request?.control_number} received successfully`;
            showToast(message, 'success');
        } else {
            // Silent refresh for other users' actions
            console.log(`🔕 Document received by another user: ${data.received_by || 'Unknown'}`);
        }
        
        fetchDvRequests();
        // Update aging data since status changed
        setTimeout(() => {
            fetchAgingDocuments();
            updateStatistics();
        }, 500);
    });

    // Listen for processing started events - WITH USER FILTERING
    rancaChannel.bind('dv-request-processing', function(data) {
        console.log('⚙️ DV request processing started:', data);
        
        // Only show toast if current user performed the action
        if (data.action_performer_id === currentUserId) {
            showToast(`Document processing started successfully`, 'success');
        } else {
            // Silent refresh for other users' actions
            console.log(`🔕 Document processing started by another user`);
        }
        
        fetchDvRequests();
        // Update aging data since status changed
        setTimeout(() => {
            fetchAgingDocuments();
            updateStatistics();
        }, 500);
    });

    // Listen for validated events - WITH USER FILTERING
    rancaChannel.bind('dv-request-validated', function(data) {
        console.log('✅ DV request validated:', data);
        
        // Only show toast if current user performed the action
        if (data.action_performer_id === currentUserId) {
            showToast(`Document validated successfully`, 'success');
        } else {
            // Silent refresh for other users' actions
            console.log(`🔕 Document validated by another user`);
        }
        
        fetchDvRequests();
        // Update aging data since document is completed
        setTimeout(() => {
            fetchAgingDocuments();
            updateStatistics();
        }, 1000);
    });

    // Listen for rejected events - WITH USER FILTERING
    rancaChannel.bind('dv-request-rejected', function(data) {
        console.log('❌ DV request rejected:', data);
        
        // Only show toast if current user performed the action
        if (data.action_performer_id === currentUserId) {
            showToast(`Document rejected`, 'error');
        } else {
            // Silent refresh for other users' actions
            console.log(`🔕 Document rejected by another user`);
        }
        
        fetchDvRequests();
        // Update aging data since document status changed
        setTimeout(() => {
            fetchAgingDocuments();
            updateStatistics();
        }, 1000);
    });

    // Listen for status updates - WITH USER FILTERING
    rancaChannel.bind('status-updated', function(data) {
        console.log('🔄 DV request status updated:', data);
        
        // Only show toast if current user performed the action
        if (data.action_performer_id === currentUserId) {
            showToast(`Document status updated`, 'info');
        } else {
            // Silent refresh for other users' actions
            console.log(`🔕 Document status updated by another user`);
        }
        
        fetchDvRequests();
        // Update aging data since status changed
        setTimeout(() => {
            fetchAgingDocuments();
            updateStatistics();
        }, 1000);
    });

    // ============ AGING ALERTS ============
    // Listen for aging document alerts - NO FILTERING (global alerts)
    rancaChannel.bind('document-aging-alert', function(data) {
        console.log('⚠️ RANCA Aging alert received:', data);
        showToast(`Aging document: ${data.control_number} is ${data.days_old} days old`, 'warning');
        // Refresh aging data immediately
        fetchAgingDocuments();
        updateStatistics();
    });

    // Listen for time-based aging updates (sent by scheduled task) - NO FILTERING (global updates)
    rancaChannel.bind('ranca-aging-update', function(data) {
        console.log('⏰ RANCA aging update received:', data);
        showToast('Aging data updated', 'info');
        fetchAgingDocuments();
        updateStatistics();
    });

        // ============ CONNECTION MONITORING ============
    pusher.connection.bind('connected', function() {
        console.log('✅ Pusher connected successfully');
        console.log('📍 Current user ID:', currentUserId);
        console.log('📡 Listening on channel: dv-requests-ranca');
        updateConnectionStatus('connected');
        
        // Refresh data on reconnection
        fetchDvRequests();
        fetchAgingDocuments();
        updateStatistics();
    });

    pusher.connection.bind('disconnected', function() {
        console.log('❌ Pusher disconnected');
        updateConnectionStatus('unavailable');
    });

    pusher.connection.bind('failed', function() {
        console.log('❌ Pusher connection failed');
        updateConnectionStatus('failed');
    });

    pusher.connection.bind('error', function(err) {
        console.error('❌ Pusher error:', err);
    });

    // Log initial state
    console.log('🚀 RANCA Pusher initialized');
    console.log('🔑 Pusher State:', pusher.connection.state);
    console.log('📡 Channel:', 'dv-requests-ranca');
    console.log('👤 Current User ID:', currentUserId);


    // ============ TOAST FUNCTIONS ============
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
            const response = await fetch(`/Accounting/dvrequests/${requestId}/receive`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                let message = result.message || 'Document has been received by RANCA.';
                
                showToast(message, 'success');
                // Refresh all data
                fetchDvRequests();
                fetchAgingDocuments();
                updateStatistics();
                
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
        // Show loading state
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
        
        // Clear search and cached data
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.value = '';
        
        if (typeof allRequests !== 'undefined') allRequests = [];
        if (typeof cachedAgingData !== 'undefined') cachedAgingData = [];
        if (typeof searchTerm !== 'undefined') searchTerm = "";
        
        // Refresh all data
        fetchDvRequests();
        fetchAgingDocuments();
        updateStatistics();
    }

    // ============ TABLE RENDERING ============
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
            <div class="overflow-x-auto">
                <table class="w-full table-fixed border-collapse divide-y divide-gray-200 text-[13.5px]">
                    <thead class="bg-gray-50 text-[13px]">
                        <tr>
                            <th class="px-5 py-3 w-[14%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Control No.</th>
                            <th class="px-5 py-3 w-[14%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DV Number</th>
                            <th class="px-5 py-3 w-[14%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ORS Number</th>
                            <th class="px-5 py-3 w-[18%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted By</th>
                            <th class="px-5 py-3 w-[14%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                            <th class="px-5 py-3 w-[12%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-5 py-3 w-[14%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 w-[16%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Submitted</th>
                            <th class="px-5 py-3 w-[12%] text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-[13.5px]">
        `;

        dvRequests.forEach(request => {
            const [bgClass, dotClass, label] = statusColors[request.status] || ['bg-gray-100 text-gray-800','bg-gray-400', request.status];
            html += `
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        #${request.id}
                    </td>
                     <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        ${request.dv_number || '—'}
                    </td>
                     <td class="px-6 py-4 text-sm font-medium text-gray-900">
                         ${request.ors_number || '—'}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">${request.submitted_by}</td>
                    <td class="px-6 py-4 text-sm text-gray-600"><span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${request.program}</span></td>
                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">₱${request.amount}</td>
                    <td class="px-6 py-4"><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${bgClass}">
                        <span class="w-2 h-2 mr-2 rounded-full ${dotClass}"></span>${label}</span></td>
                    <td class="px-6 py-4 text-sm text-gray-600">${request.date}</td>
                    <td class="px-5 py-4 text-center text-sm font-medium">
                        ${
                            request.status === 'Outgoing_finalized'
                            ? `<button type="button" 
                                    onclick="handleReceiveAction(${request.id}, this)"
                                    class="inline-flex px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 text-[13px] font-medium rounded-md transition-colors duration-200">
                                    Receive
                            </button>`    
                            : `<a href="/Accounting/dvrequests/${request.id}" 
                                    class="inline-flex px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md transition-colors duration-200">
                                    View / Process
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
            const res = await fetch("{{ route('Accounting.dvrequests.fetch') }}?search=" + encodeURIComponent(searchTerm));
            const data = await res.json();
            allRequests = data;
            renderTable(data);
        } catch (err) {
            console.error('Failed to fetch DV requests', err);
            tableContainer.innerHTML = `<div class="text-center py-16 text-red-500">Failed to load DV requests.</div>`;
        }
    }

    // ============ AGING DOCUMENTS FUNCTIONS ============
    async function fetchAgingDocuments() {
        try {
            console.log('Fetching RANCA aging documents...');
            
            const res = await fetch('{{ route("Accounting.aging.documents") }}');
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const data = await res.json();
            console.log('RANCA aging documents response:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch aging documents');
            }
            
            cachedAgingData = data.documents || [];
            
            if (agingAlertsContainer) {
                renderAgingAlerts(data.documents, data.aging_count);
            }
            
        } catch (err) {
            console.error('Failed to fetch aging documents:', err);
            
            // Don't show toast for 404 - it's expected if routes aren't set up
            if (!err.message.includes('404')) {
                showToast('Failed to load aging documents: ' + err.message, 'error');
            }
            
            if (agingAlertsContainer) {
                agingAlertsContainer.innerHTML = '';
            }
        }
    }

    async function updateStatistics() {
        try {
            const res = await fetch('{{ route("Accounting.aging.statistics") }}');
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const data = await res.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch statistics');
            }
            
            document.getElementById('stat-critical').textContent = data.critical || 0;
            document.getElementById('stat-high').textContent = data.high || 0;
            document.getElementById('stat-medium').textContent = data.medium || 0;
            document.getElementById('stat-total').textContent = data.total_aging || 0;
            
        } catch (err) {
            console.error('Failed to fetch statistics:', err);
            // Don't show toast for statistics errors to avoid spam
        }
    }

    function renderAgingAlerts(documents, count) {
        if (!agingAlertsContainer) {
            console.error('Aging alerts container is null');
            return;
        }

        if (!documents || documents.length === 0) {
            agingAlertsContainer.innerHTML = '';
            return;
        }

        const agingDocs = documents.filter(doc => !['RANCA_validated', 'completed'].includes(doc.status));
        
        if (agingDocs.length === 0) {
            agingAlertsContainer.innerHTML = '';
            return;
        }

        function calculateUrgency(daysOld) {
            const days = Math.abs(Number(daysOld)) || 0;
            if (days >= 10) return 'critical';
            if (days >= 7) return 'high';
            if (days >= 3) return 'medium';
            return 'low';
        }

        agingDocs.forEach(doc => {
            doc.urgency = calculateUrgency(doc.days_old);
        });

        let urgencyIcon = {
            'critical': '🚨',
            'high': '⚠️',
            'medium': '⏰',
            'low': 'ℹ️'
        };

        let bgColor = {
            'critical': 'bg-red-50 border-red-500',
            'high': 'bg-orange-50 border-orange-500',
            'medium': 'bg-yellow-50 border-yellow-500',
            'low': 'bg-blue-50 border-blue-500'
        };

        let textColor = {
            'critical': 'text-red-900',
            'high': 'text-orange-900',
            'medium': 'text-yellow-900',
            'low': 'text-blue-900'
        };

        const highestUrgency = agingDocs.reduce((highest, doc) => {
            const urgencyOrder = { 'critical': 4, 'high': 3, 'medium': 2, 'low': 1 };
            return urgencyOrder[doc.urgency] > urgencyOrder[highest] ? doc.urgency : highest;
        }, 'low');

        let html = `
            <div class="rounded-lg border-l-4 p-5 ${bgColor[highestUrgency]} shadow-md transition-all duration-300">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">${urgencyIcon[highestUrgency]}</span>
                        <div>
                            <h3 class="font-bold text-lg ${textColor[highestUrgency]}">Documents Need Processing</h3>
                            <p class="text-sm opacity-80 ${textColor[highestUrgency]}">${agingDocs.length} document(s) older than 3 days</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button id="minimize-alerts" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg id="minimize-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                            <svg id="expand-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div id="alerts-content" class="transition-all duration-300 overflow-hidden">
                    <div class="space-y-2 mt-4 max-h-64 overflow-y-auto">
                        ${agingDocs.slice(0, 5).map(doc => {
                            const docBgColor = {
                                'critical': 'bg-red-100 border-red-200',
                                'high': 'bg-orange-100 border-orange-200', 
                                'medium': 'bg-yellow-100 border-yellow-200',
                                'low': 'bg-blue-100 border-blue-200'
                            };
                            
                            // Simplified status logic for RANCA
                            const isReceived = ['RANCA_received', 'RANCA_in_process'].includes(doc.status);
                            const statusText = isReceived ? 'Received - Needs Processing' : 'Pending Receipt';
                            
                            return `
                            <div class="flex items-center justify-between p-3 ${docBgColor[doc.urgency]} border rounded-lg hover:opacity-90 transition-all duration-200">
                                <div class="flex-1">
                                    <p class="font-semibold text-sm ${textColor[doc.urgency]}">#${doc.id}</p>
                                    <p class="text-xs opacity-70 ${textColor[doc.urgency]}">${doc.submitted_by} • ${doc.program} • ${doc.status}</p>
                                    <p class="text-xs font-medium mt-1 ${doc.days_old >= 10 ? 'text-red-700' : doc.days_old >= 7 ? 'text-orange-700' : 'text-yellow-700'}">
                                        ${doc.days_old} days old • ${statusText}
                                    </p>
                                </div>
                            </div>
                        `}).join('')}
                    </div>

                    ${agingDocs.length > 5 ? `
                        <p class="text-xs mt-3 opacity-75 ${textColor[highestUrgency]}">
                            +${agingDocs.length - 5} more documents
                        </p>
                    ` : ''}
                </div>
            </div>
        `;

        agingAlertsContainer.innerHTML = html;

        // Add minimize/expand functionality
        const minimizeButton = document.getElementById('minimize-alerts');
        const minimizeIcon = document.getElementById('minimize-icon');
        const expandIcon = document.getElementById('expand-icon');
        const alertsContent = document.getElementById('alerts-content');

        if (minimizeButton && alertsContent) {
            minimizeButton.addEventListener('click', function() {
                if (alertsContent.style.maxHeight === '0px' || alertsContent.style.display === 'none') {
                    alertsContent.style.maxHeight = '1000px';
                    alertsContent.style.display = 'block';
                    minimizeIcon.classList.add('hidden');
                    expandIcon.classList.remove('hidden');
                } else {
                    alertsContent.style.maxHeight = '0px';
                    alertsContent.style.display = 'none';
                    minimizeIcon.classList.remove('hidden');
                    expandIcon.classList.add('hidden');
                }
            });
        }
    }

    // ============ EVENT HANDLERS ============
    
    // Search with debounce
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchTerm = this.value;
            fetchDvRequests();
        }, 300);
    });

    // ============ AUTO-REFRESH ============
    
    // Refresh aging data more frequently (every 2 minutes)
    setInterval(fetchAgingDocuments, 2 * 60 * 1000);
    setInterval(updateStatistics, 2 * 60 * 1000);
    
    // Refresh main table less frequently (every 5 minutes)
    setInterval(fetchDvRequests, 5 * 60 * 1000);

    // ============ INITIAL LOAD ============
    
    // Initial load
    fetchDvRequests();
    fetchAgingDocuments();
    updateStatistics();
});
</script>
@endsection