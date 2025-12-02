@extends('layouts.app')

@section('title', 'Budget Officer - ORS Requests')


@section('content')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
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
                   <button onclick="refreshAllData()" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
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

<script>
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
    console.log('🔧 handleReceiveAction called for DV request:', requestId);
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
        console.log('🔧 Server response:', result);

        if (response.ok && result.success) {
            let message = result.message || 'Document has been received by Budget Officer.';
        
            
            showToast(message, 'success');
            
            // ✅ FORCE REFRESH ALL DATA
            await fetchDvRequests();
            await fetchOrsAgingDocuments(); // Wait for this to complete
            await updateOrsStatistics();
            
        } else {
            showToast(result.message || 'Failed to receive document.', 'error');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    } catch (error) {
        console.error('❌ Error receiving document:', error);
        showToast('An error occurred while processing the request.', 'error');
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

async function fetchOrsAgingDocuments() {
    console.log('Fetching ORS aging documents...');
    try {
        const res = await fetch('/BudgetOfficer/ors-aging/documents');
        
        console.log('Response status:', res.status);
        
        if (!res.ok) {
            const errorText = await res.text();
            console.error('Server response:', errorText);
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const data = await res.json();
        console.log('ORS Aging data received:', data);
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to fetch ORS aging documents');
        }
        
        console.log('Documents count:', data.documents ? data.documents.length : 0);
        
        renderOrsAgingAlerts(data.documents || [], data.aging_count || 0);
        updateOrsStatistics();
        
    } catch (err) {
        console.error('Failed to fetch ORS aging documents:', err);
        showToast('Failed to load ORS aging documents: ' + err.message, 'error');
    }
}

async function updateOrsStatistics() {
    try {
        const res = await fetch('/BudgetOfficer/ors-aging/statistics');
        
        if (!res.ok) {
            const errorText = await res.text();
            console.error('Server response:', errorText);
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
        showToast('Failed to load ORS statistics: ' + err.message, 'error');
    }
}

function renderOrsAgingAlerts(documents, count) {
    console.log('renderOrsAgingAlerts called with:', { documents, count });
    

    const agingContainer = document.getElementById('ors-aging-alerts-container');
    
    if (!agingContainer) {
        console.error('ORS aging alerts container not found!');
        return;
    }
    
    if (!documents || documents.length === 0) {
        console.log('No documents to display');
        agingContainer.innerHTML = '';
        return;
    }

    // Filter out completed/obligated documents
    const agingDocs = documents.filter(doc => !['ORS_obligated', 'completed'].includes(doc.status));
    
    console.log('Documents after filtering:', agingDocs);
    
    if (agingDocs.length === 0) {
        console.log('No aging documents after filtering');
        agingContainer.innerHTML = '';
        return;
    }

    // Calculate urgency for each document
    function calculateUrgency(daysInStatus) {
        const days = Math.abs(Number(daysInStatus)) || 0;
        console.log('Calculating urgency for days:', days);
        if (days >= 7) return 'critical';
        if (days >= 5) return 'high';
        if (days >= 3) return 'medium';
        return 'low';
    }

    agingDocs.forEach(doc => {
        doc.urgency = calculateUrgency(doc.days_in_status);
        console.log(`Document ${doc.control_number}: days_in_status=${doc.days_in_status}, urgency=${doc.urgency}`);
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

    console.log('Highest urgency:', highestUrgency);
    console.log('Rendering alerts HTML...');

    
    let html = `
        <div class="rounded-lg border-l-4 p-5 ${bgColor[highestUrgency]} shadow-md transition-all duration-300">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">${urgencyIcon[highestUrgency]}</span>
                    <div>
                        <h3 class="font-bold text-lg ${textColor[highestUrgency]}">ORS Documents Need Processing</h3>
                        <p class="text-sm opacity-80 ${textColor[highestUrgency]}">${agingDocs.length} ORS document(s) older than 3 days</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button id="minimize-ors-alerts" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg id="minimize-ors-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                        <svg id="expand-ors-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div id="ors-alerts-content" class="transition-all duration-300 overflow-hidden">
                <div class="space-y-2 mt-4 max-h-64 overflow-y-auto">
                    ${agingDocs.slice(0, 5).map(doc => {
                        const docBgColor = {
                            'critical': 'bg-red-100 border-red-200',
                            'high': 'bg-orange-100 border-orange-200', 
                            'medium': 'bg-yellow-100 border-yellow-200',
                            'low': 'bg-blue-100 border-blue-200'
                        };
                        
                        const statusLabels = {
                            'Incoming-GIP_verified': 'Pending - Not Received',
                            'Incoming-TUPAD_verified': 'Pending - Not Received',
                            'Incoming-TEV_verified': 'Pending - Not Received',
                            'ORS_received': 'Received - Needs Processing',
                            'ORS_in_process': 'In Process - Stalled', 
                            'ORS_onhold': 'On Hold - Needs Attention',
                            'ORS_for_revision': 'For Revision - Pending'
                        };

                        
                        return `
                        <div class="flex items-center justify-between p-3 ${docBgColor[doc.urgency]} border rounded-lg hover:opacity-90 transition-all duration-200">
                            <div class="flex-1">
                                <p class="font-semibold text-sm ${textColor[doc.urgency]}">${doc.control_number || 'N/A'}</p>
                                <p class="text-xs opacity-70 ${textColor[doc.urgency]}">
                                    ${doc.ors_number ? 'ORS: ' + doc.ors_number + ' • ' : ''}
                                    ${doc.submitted_by || 'Unknown'} • ${doc.program || 'N/A'}
                                </p>
                                <p class="text-xs font-medium mt-1 ${doc.urgency === 'critical' ? 'text-red-700' : doc.urgency === 'high' ? 'text-orange-700' : 'text-yellow-700'}">
                                    ${doc.days_in_status || 0} days old • ${statusLabels[doc.current_status] || doc.current_status}
                                </p>

                            </div>
                        </div>
                    `}).join('')}
                </div>

                ${agingDocs.length > 5 ? `
                    <p class="text-xs mt-3 opacity-75 ${textColor[highestUrgency]}">
                        +${agingDocs.length - 5} more ORS documents
                    </p>
                ` : ''}
            </div>
        </div>
    `;

    console.log('Setting innerHTML...');
    agingContainer.innerHTML = html;
    console.log('HTML set successfully');


    // Add minimize/expand functionality
    const minimizeButton = document.getElementById('minimize-ors-alerts');
    const minimizeIcon = document.getElementById('minimize-ors-icon');
    const expandIcon = document.getElementById('expand-ors-icon');
    const alertsContent = document.getElementById('ors-alerts-content');

    if (minimizeButton && alertsContent) {
        minimizeButton.addEventListener('click', function() {
            if (alertsContent.style.maxHeight === '0px' || alertsContent.style.display === 'none') {
                alertsContent.style.maxHeight = '1000px';
                alertsContent.style.display = 'block';
                minimizeIcon.classList.remove('hidden');
                expandIcon.classList.add('hidden');
            } else {
                alertsContent.style.maxHeight = '0px';
                alertsContent.style.display = 'none';
                minimizeIcon.classList.add('hidden');
                expandIcon.classList.remove('hidden');
            }
        });
    }
}

// ====================================
// MAIN APPLICATION CODE
// ====================================

document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.getElementById('dv-table-container');
    const searchInput = document.getElementById('searchInput');
    const connectionStatus = document.getElementById('connection-status');
    const currentUserId = {{ auth()->id() }};
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

    // Consolidated event handler
    function handlePusherUpdate(eventType, data) {
        console.log(`${eventType}:`, data);
        
        switch(eventType) {
            case 'dv-request-verified':
                fetchOrsAgingDocuments();
                updateOrsStatistics();
                break;
                
            case 'dv-request-received':
                    if (data.action_performer_id === currentUserId) {
                    let message = `Document ${data.control_number || data.dv_request?.control_number} received successfully`;
                    showToast(message, 'success');
                } else {
                    // Silent refresh for other users' actions
                    console.log(`Document received by another user: ${data.received_by}`);
                }
                    

                showToast(message, 'success');
                
                // Refresh data
                if (!isFetching) {
                    fetchDvRequests();
                }
                fetchOrsAgingDocuments();
                updateOrsStatistics();
                break;
                
            case 'ors-obligated':
            if (data.action_performer_id === currentUserId) {
                showToast(`ORS ${data.ors_number} has been obligated`, 'success');
                } else {
                    // Silent refresh for other users' actions
                    console.log(`Document verified by another user`);
                }
                break;
                
            case 'amount-updated':
                  if (data.action_performer_id === currentUserId) {
                 showToast(`Amount updated for request #${data.dv_request_id}`, 'success');
                } else {
                    // Silent refresh for other users' actions
                    console.log(`Document verified by another user`);
                }
                break;
                
            case 'ors-aging-alert':
                showToast(`New ORS aging alert: ${data.ors_number || 'Document'} needs attention`, 'warning');
                fetchOrsAgingDocuments();
                updateOrsStatistics();
                break;
            
        }
        
        // Debounce table refresh
        if (!isFetching) {
            fetchDvRequests();
        }
    }

    // Bind all events to single handler
    ['dv-request-verified', 'dv-request-received', 'dv-request-processing', 
     'ors-obligated', 'ors-onhold', 'ors-revised', 'status-updated', 'amount-updated',
     'ors-aging-alert'].forEach(event => {
        budgetChannel.bind(event, (data) => handlePusherUpdate(event, data));
    });

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
        fetchOrsAgingDocuments();
        updateOrsStatistics();
        
        // Refresh recent requests if available
        if (typeof loadRecentRequests === 'function') {
            setTimeout(loadRecentRequests, 500);
        }
    }
    
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
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">${req.control_no ?? '—'}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">${req.submitted_by}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${req.program}</td>
                     <td class="px-6 py-4 text-sm text-gray-600">${req.particulars}</td>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Particulars</th>
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

    // Make functions globally accessible
    window.fetchDvRequests = fetchDvRequests;
    window.showToast = showToast;
    window.dismissToast = dismissToast;
    window.handleReceiveAction = handleReceiveAction;
    window.fetchOrsAgingDocuments = fetchOrsAgingDocuments;
    window.updateOrsStatistics = updateOrsStatistics;

    // Initial fetches
    fetchDvRequests();
    
    // Fetch ORS aging documents
  
        fetchOrsAgingDocuments();
        updateOrsStatistics();
        
        // Refresh ORS aging documents every 5 minutes
        setInterval(fetchOrsAgingDocuments, 5 * 60 * 1000);
});
</script>
@endsection