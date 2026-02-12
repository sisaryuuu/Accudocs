@extends('layouts.app')

@section('title', 'Outgoing - DV Requests')


<style>
    /* ============ URGENT BLINKING ANIMATIONS ============ */

    /* Main row blinking effect */
    @keyframes urgent-blink {
        0% {
            background-color: #fee2e2;
            border-left-color: #ef4444;
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.3);
        }
        25% {
            background-color: #fecaca;
            border-left-color: #dc2626;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
        }
        50% {
            background-color: #fee2e2;
            border-left-color: #ef4444;
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.3);
        }
        75% {
            background-color: #fecaca;
            border-left-color: #dc2626;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
        }
        100% {
            background-color: #fee2e2;
            border-left-color: #ef4444;
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.3);
        }
    }

    .urgent-blink {
        animation: urgent-blink 1.2s ease-in-out infinite;
        transition: all 0.2s ease;
    }

    /* Badge pulsing effect */
    @keyframes urgent-pulse {
        0% {
            transform: scale(1);
            opacity: 1;
            background-color: #dc2626;
        }
        50% {
            transform: scale(1.08);
            opacity: 0.95;
            background-color: #b91c1c;
        }
        100% {
            transform: scale(1);
            opacity: 1;
            background-color: #dc2626;
        }
    }

    .urgent-badge {
        animation: urgent-pulse 1.2s ease-in-out infinite;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Glowing effect for buttons */
    @keyframes urgent-glow {
        0% {
            box-shadow: 0 0 5px rgba(239, 68, 68, 0.5);
            border-color: #ef4444;
        }
        25% {
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.8), 0 0 25px rgba(239, 68, 68, 0.5);
            border-color: #dc2626;
        }
        50% {
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.6), 0 0 30px rgba(220, 38, 38, 0.4);
            border-color: #b91c1c;
        }
        75% {
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.8), 0 0 25px rgba(239, 68, 68, 0.5);
            border-color: #dc2626;
        }
        100% {
            box-shadow: 0 0 5px rgba(239, 68, 68, 0.5);
            border-color: #ef4444;
        }
    }

    .urgent-glow {
        animation: urgent-glow 1.5s ease-in-out infinite;
    }

    /* Dot pulsing animation */
    @keyframes urgent-dot-pulse {
        0% {
            transform: scale(1);
            opacity: 1;
            background-color: #fff;
        }
        50% {
            transform: scale(1.3);
            opacity: 0.9;
            background-color: #fee2e2;
        }
        100% {
            transform: scale(1);
            opacity: 1;
            background-color: #fff;
        }
    }

    .urgent-dot {
        animation: urgent-dot-pulse 1s ease-in-out infinite;
    }

    /* Urgent filter button */
    #show-urgent-only {
        transition: all 0.3s ease;
    }

    #show-urgent-only.active {
        background-color: #dc2626;
        color: white;
        animation: urgent-glow 1.5s ease-in-out infinite;
    }

    /* Urgent count badge */
    .urgent-count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #dc2626;
        color: white;
        font-size: 0.75rem;
        font-weight: bold;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        margin-left: 0.5rem;
        animation: urgent-pulse 1.2s ease-in-out infinite;
    }

    /* Urgent status badge */
    .status-urgent {
        background-color: #dc2626 !important;
        color: white !important;
        font-weight: bold;
        animation: urgent-pulse 1.2s ease-in-out infinite;
    }

    /* Urgent button */
    .btn-urgent-receive {
        background: linear-gradient(45deg, #dc2626, #b91c1c, #991b1b, #b91c1c, #dc2626);
        background-size: 300% 300%;
        animation: gradient-shift 3s ease infinite, urgent-glow 1.5s ease-in-out infinite;
        color: white;
        font-weight: bold;
        border: none;
    }

    @keyframes gradient-shift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .btn-urgent-process {
        background-color: #dc2626;
        color: white;
        font-weight: bold;
        animation: urgent-glow 1.5s ease-in-out infinite;
        border: none;
    }

    .btn-urgent-process:hover {
        background-color: #b91c1c;
        transform: scale(1.05);
    }

    /* Urgent column badge */
    .urgent-column-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        background-color: #dc2626;
        color: white;
        font-size: 0.75rem;
        font-weight: bold;
        border-radius: 9999px;
        animation: urgent-pulse 1.2s ease-in-out infinite;
    }

    /* Ensure table layout is fixed */
    .table-fixed {
        table-layout: fixed;
    }

    /* Column width adjustments for urgent column */
    .w-urgent {
        width: 8%;
    }
</style>


@section('content')
<div class="min-h-screen bg-gray-50 pt-24 pb-8">
    <div class="max-w-[95rem] mx-auto px-6">
        <!-- Header Section -->
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

        <!-- Outgoing Aging Alerts Container -->
        <div id="outgoing-aging-alerts-container" class="mb-6">
            <!-- Alert will be populated by JavaScript -->
        </div>

        <!-- Outgoing Aging Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-700 text-sm font-semibold">Critical</p>
                        <p id="outgoing-stat-critical" class="text-2xl font-bold text-red-600">0</p>
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
                        <p id="outgoing-stat-high" class="text-2xl font-bold text-orange-600">0</p>
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
                        <p id="outgoing-stat-medium" class="text-2xl font-bold text-yellow-600">0</p>
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
                        <p id="outgoing-stat-total" class="text-2xl font-bold text-blue-600">0</p>
                        <p class="text-xs text-blue-600">3+ days old</p>
                    </div>
                    <svg class="w-8 h-8 text-blue-500 opacity-30" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000-2H6a6 6 0 100 12H4a2 2 0 01-2-2V5zm11.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Sorting Controls with Urgent Filter -->
        <div class="flex flex-wrap gap-2 mb-6">
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
            
            <!-- URGENT ONLY BUTTON - NEW -->
            <button id="show-urgent-only" class="px-4 py-2 bg-orange-100 text-orange-700 rounded-lg text-sm font-medium hover:bg-orange-200 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                Show Urgent Only
                <span id="urgent-count-badge" class="hidden urgent-count-badge">0</span>
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
                       placeholder="🔍 Search DV Requests (Control No, DV No, ORS No, Program, Respocenter, Submitted By)..."
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
    const outgoingAgingAlertsContainer = document.getElementById('outgoing-aging-alerts-container');
    
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
    let cachedOutgoingAgingData = [];
    let isCriticalOnlyView = false;
    let isUrgentOnlyView = false; // NEW
    let currentSort = 'priority';
    
    // Pagination state
    let currentPage = 1;
    let perPage = 25;
    let totalPages = 1;
    let totalRecords = 0;

    const statusColors = {
        'ORS_obligated': ['bg-gray-100 text-gray-800','bg-gray-400','Pending'],
        'Outgoing_received': ['bg-yellow-100 text-yellow-800','bg-yellow-400','Received'],
        'Outgoing_in_process': ['bg-indigo-100 text-indigo-800','bg-indigo-400','In Process'],
        'Outgoing_finalized': ['bg-green-100 text-green-800','bg-green-400','Finalized'],
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

    // Subscribe to Outgoing channel
    const outgoingChannel = pusher.subscribe('dv-requests-outgoing');

    // ============ PUSHER EVENT LISTENERS ============
    
        // Listen for new DV request submissions
    outgoingChannel.bind('dv-request-submitted', function(data) {
        console.log('🔔 New obligated document from Budget Officer:', data);
        
        // ✅ Check if this is an urgent document
        const isUrgent = data.is_urgent == 1 || data.is_urgent === true || data.is_urgent === '1';
        
        if (isUrgent) {
            // Play sound for urgent documents
            try {
                const audio = new Audio('/sounds/urgent-notification.mp3');
                audio.play().catch(e => console.log('Audio play failed:', e));
            } catch(e) { console.log('Audio not supported'); }
            
            // Show prominent urgent toast
            showToast(`
                <div class="flex items-center">
                    <span class="text-xl mr-2">🚨🚨🚨</span>
                    <div>
                        <strong>URGENT DOCUMENT FROM BUDGET!</strong><br>
                        <span>ORS: ${data.ors_number || 'N/A'} - ${data.submitted_by || 'Unknown'}</span>
                    </div>
                </div>
            `, 'warning');
            
            // Update urgent count badge
            setTimeout(() => updateUrgentCountBadge(), 500);
        } else {
            showToast(`New obligated document from ${data.submitted_by || 'Budget Officer'}`, 'success');
        }
        
        fetchDvRequests();
        fetchOutgoingAgingDocuments();
        updateOutgoingStatistics();
    });

    // Listen for received events
    outgoingChannel.bind('dv-request-received', function(data) {
        console.log('DV request received:', data);
        
        if (data.action_performer_id === currentUserId) {
            let message = `Document ${data.control_number || data.dv_request?.control_number} received successfully`;
            showToast(message, 'success');
        }
        
        fetchDvRequests();
        setTimeout(() => {
            fetchOutgoingAgingDocuments();
            updateOutgoingStatistics();
        }, 500);
    });

    // Listen for processing started events
    outgoingChannel.bind('dv-request-processing', function(data) {
        console.log('DV request processing started:', data);
        
        if (data.action_performer_id === currentUserId) {
            showToast(`Document processing started successfully`, 'success');
        }
        
        fetchDvRequests();
        setTimeout(() => {
            fetchOutgoingAgingDocuments();
            updateOutgoingStatistics();
        }, 500);
    });

    // Listen for finalized events
    outgoingChannel.bind('dv-request-finalized', function(data) {
        console.log('DV request finalized:', data);
        
        if (data.action_performer_id === currentUserId) {
            showToast(`Document finalized successfully`, 'success');
        }
        
        fetchDvRequests();
        setTimeout(() => {
            fetchOutgoingAgingDocuments();
            updateOutgoingStatistics();
        }, 1000);
    });

    // Listen for rejected events
    outgoingChannel.bind('dv-request-rejected', function(data) {
        console.log('DV request rejected:', data);
        
        if (data.action_performer_id === currentUserId) {
            showToast(`Document rejected`, 'error');
        }
        
        fetchDvRequests();
        setTimeout(() => {
            fetchOutgoingAgingDocuments();
            updateOutgoingStatistics();
        }, 1000);
    });

    // Listen for status updates
    outgoingChannel.bind('status-updated', function(data) {
        console.log('DV request status updated:', data);
        
        if (data.action_performer_id === currentUserId) {
            showToast(`Document status updated`, 'info');
        }
        
        fetchDvRequests();
        setTimeout(() => {
            fetchOutgoingAgingDocuments();
            updateOutgoingStatistics();
        }, 1000);
    });

    // Listen for outgoing aging alerts
    outgoingChannel.bind('document-aging-alert', function(data) {
        console.log('Outgoing Aging alert received:', data);
        showToast(`Aging document: ${data.control_number} is ${data.days_old} days old`, 'warning');
        fetchOutgoingAgingDocuments();
        updateOutgoingStatistics();
    });

    // Listen for time-based aging updates
    outgoingChannel.bind('outgoing-aging-update', function(data) {
        console.log('Outgoing aging update received:', data);
        showToast('Aging data updated', 'info');
        fetchOutgoingAgingDocuments();
        updateOutgoingStatistics();
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
            const response = await fetch(`/Outgoing/dvrequests/${requestId}/receive`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                let message = result.message || 'Document has been received by Outgoing.';
                showToast(message, 'success');
                fetchDvRequests();
                fetchOutgoingAgingDocuments();
                updateOutgoingStatistics();
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
        cachedOutgoingAgingData = [];
        searchTerm = "";
        isCriticalOnlyView = false;
        isUrgentOnlyView = false;
        currentSort = 'priority';
        
        fetchDvRequests();
        fetchOutgoingAgingDocuments();
        updateOutgoingStatistics();
        updateSortButtonStyles();
        updateUrgentCountBadge();
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
                (request.dv_number && request.dv_number.toLowerCase().includes(searchLower)) ||
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
        filteredRequests = [...allRequests];
        
        // Apply search filter first
        if (searchTerm) {
            filteredRequests = filterRequests(filteredRequests, searchTerm);
        }
        
        // Ensure days_old is positive and add is_urgent flag
        filteredRequests = filteredRequests.map(req => ({
            ...req,
            days_old: Math.abs(req.days_old) || 0,
            is_urgent: req.is_urgent || false
        }));
        
        // Apply URGENT filter if active
        if (isUrgentOnlyView) {
            filteredRequests = filteredRequests.filter(req => req.is_urgent === true);
        }
        // Apply critical filter if active and urgent filter is not active
        else if (isCriticalOnlyView) {
            filteredRequests = filteredRequests.filter(req => req.days_old >= 10);
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
            // 1. HIGHEST PRIORITY: Urgent documents ALWAYS come FIRST
            if (a.is_urgent && !b.is_urgent) return -1;
            if (!a.is_urgent && b.is_urgent) return 1;
            
            // 2. SECOND PRIORITY: Outgoing_received status comes next
            const aIsOutgoingReceived = a.status === 'Outgoing_received';
            const bIsOutgoingReceived = b.status === 'Outgoing_received';
            
            if (aIsOutgoingReceived && !bIsOutgoingReceived) return -1;
            if (!aIsOutgoingReceived && bIsOutgoingReceived) return 1;
            
            // 3. THIRD PRIORITY: ORS_obligated status comes third
            const aIsObligated = a.status === 'ORS_obligated';
            const bIsObligated = b.status === 'ORS_obligated';
            
            if (aIsObligated && !bIsObligated) return -1;
            if (!aIsObligated && bIsObligated) return 1;
            
            // 4. FOURTH PRIORITY: Age-based priority for the rest
            return b.days_old - a.days_old;
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
        const urgentBtn = document.getElementById('show-urgent-only');
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
        
        if (urgentBtn) {
            if (isUrgentOnlyView) {
                urgentBtn.className = 'px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center gap-2 active';
                urgentBtn.innerHTML = `
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    Showing Urgent Only
                `;
            } else {
                urgentBtn.className = 'px-4 py-2 bg-orange-100 text-orange-700 rounded-lg text-sm font-medium hover:bg-orange-200 transition flex items-center gap-2';
                urgentBtn.innerHTML = `
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    Show Urgent Only
                `;
            }
        }
        
        if (showAllBtn) {
            if (isCriticalOnlyView || isUrgentOnlyView) {
                showAllBtn.style.display = 'flex';
            } else {
                showAllBtn.style.display = 'none';
            }
        }
    }

    // NEW: Update urgent count badge
    function updateUrgentCountBadge() {
        const urgentCount = allRequests.filter(req => req.is_urgent === true).length;
        const badge = document.getElementById('urgent-count-badge');
        
        if (badge) {
            if (urgentCount > 0) {
                badge.textContent = urgentCount;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }

    // ============ TABLE RENDERING ============
    function renderTable(dvRequests) {
        if (dvRequests.length === 0) {
            let message = '';
            if (isUrgentOnlyView) {
                message = '<p class="text-sm text-red-600 mt-2">No urgent documents found.</p>';
            } else if (isCriticalOnlyView) {
                message = '<p class="text-sm text-red-600 mt-2">No critical documents (10+ days) found.</p>';
            }
            
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
                    ${message}
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
                            <th class="px-5 py-3 w-[12%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DV Number</th>
                            <th class="px-5 py-3 w-[12%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ORS Number</th>
                            <th class="px-5 py-3 w-[8%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Urgent</th>
                            <th class="px-5 py-3 w-[14%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted By</th>
                            <th class="px-5 py-3 w-[14%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Particulars</th>
                            <th class="px-5 py-3 w-[10%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                            <th class="px-5 py-3 w-[10%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-5 py-3 w-[12%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 w-[14%] text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Received</th>
                            <th class="px-5 py-3 w-[12%] text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-[13.5px]">
        `;

        dvRequests.forEach(request => {
            const [bgClass, dotClass, label] = statusColors[request.status] || ['bg-gray-100 text-gray-800','bg-gray-400', request.status];
            const daysOld = Math.round(request.days_old) || 0;
            
            // 🚨 Check if document is urgent
            const isUrgent = request.is_urgent == 1 || request.is_urgent === true || request.is_urgent === '1';
            
            // Determine row styling based on age or urgent flag
            let rowClasses = 'hover:bg-gray-50 transition-colors duration-150';
            let textColorClass = 'text-gray-900';
            let rowBgClass = '';
            
            if (isUrgent) {
                rowBgClass = 'urgent-blink';
                rowClasses = 'border-l-4 border-red-600';
                textColorClass = 'text-gray-900 font-medium';
            } else if (daysOld >= 10) {
                rowBgClass = 'bg-red-50/50 hover:bg-red-100/50';
                rowClasses = 'border-l-4 border-red-400';
            } else if (daysOld >= 7) {
                rowBgClass = 'bg-orange-50/50 hover:bg-orange-100/50';
                rowClasses = 'border-l-4 border-orange-300';
            } else if (daysOld >= 3) {
                rowBgClass = 'bg-yellow-50/50 hover:bg-yellow-100/50';
                rowClasses = 'border-l-4 border-yellow-300';
            } else {
                rowBgClass = 'bg-white hover:bg-gray-50';
            }
            
            // 🚨 Urgent badge for column
            let urgentColumnBadge = '';
            if (isUrgent) {
                urgentColumnBadge = `
                    <span class="inline-flex items-center px-2 py-1 text-xs font-bold text-white bg-red-600 rounded-full urgent-badge">
                        <svg class="w-3 h-3 mr-1 urgent-dot" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        URGENT
                    </span>
                `;
            } else {
                urgentColumnBadge = `<span class="text-gray-400">—</span>`;
            }
            
            // Button styling based on age, status, and urgent flag
            let buttonClass = 'bg-green-100 hover:bg-green-200 text-green-700';
            let buttonText = 'Receive';
            let buttonHref = '';
            let isReceiveButton = false;
            
            if (request.status === 'ORS_obligated') {
                isReceiveButton = true;
                if (isUrgent) {
                    buttonClass = 'btn-urgent-receive';
                    buttonText = '⚡ RECEIVE NOW';
                } else if (daysOld >= 10) {
                    buttonClass = 'bg-green-100 hover:bg-green-200 text-green-700 border border-green-300 font-medium';
                    buttonText = 'RECEIVE';
                } else if (daysOld >= 7) {
                    buttonClass = 'bg-orange-100 hover:bg-orange-200 text-orange-700 border border-orange-300';
                } else if (daysOld >= 3) {
                    buttonClass = 'bg-yellow-100 hover:bg-yellow-200 text-yellow-700 border border-yellow-300';
                }
            } else {
                // For already received documents
                buttonHref = `/Outgoing/dvrequests/${request.id}`;
                if (isUrgent) {
                    buttonClass = 'btn-urgent-process';
                    buttonText = '⚡ PROCESS NOW';
                } else if (daysOld >= 10) {
                    buttonClass = 'bg-red-100 hover:bg-red-200 text-red-700 border border-red-300 font-medium';
                    buttonText = 'VIEW / PROCESS';
                } else if (daysOld >= 7) {
                    buttonClass = 'bg-orange-100 hover:bg-orange-200 text-orange-700 border border-orange-300';
                    buttonText = 'View / Process';
                } else if (daysOld >= 3) {
                    buttonClass = 'bg-yellow-100 hover:bg-yellow-200 text-yellow-700 border border-yellow-300';
                    buttonText = 'View / Process';
                } else {
                    buttonClass = 'bg-blue-100 hover:bg-blue-200 text-blue-700';
                    buttonText = 'View / Process';
                }
            }
            
            // Status badge styling
            let statusBadgeClass = bgClass;
            let statusDotClass = dotClass;
            let statusLabel = label;
            
            if (isUrgent) {
                statusBadgeClass = 'status-urgent';
                statusDotClass = 'bg-white urgent-dot';
                statusLabel = 'URGENT - ' + label;
            } else if (daysOld >= 10) {
                statusBadgeClass = 'bg-red-100 text-red-800 border border-red-200';
                statusDotClass = 'bg-red-500';
            } else if (daysOld >= 7) {
                statusBadgeClass = 'bg-orange-100 text-orange-800 border border-orange-200';
                statusDotClass = 'bg-orange-400';
            } else if (daysOld >= 3) {
                statusBadgeClass = 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                statusDotClass = 'bg-yellow-400';
            }
            
            html += `
                <tr class="${rowClasses} ${rowBgClass}" data-dv-id="${request.id}">
                
                    <td class="px-6 py-4 text-sm font-medium ${textColorClass}">
                        ${request.control_no ?? '—'}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium ${textColorClass}">
                        ${request.dv_number || '—'}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium ${textColorClass}">
                        ${request.ors_number || '—'}
                    </td>
                      <td class="px-6 py-4 text-sm">
                        ${urgentColumnBadge}
                    </td>
                    <td class="px-6 py-4 text-sm ${textColorClass}">${request.submitted_by}</td>
                    <td class="px-6 py-4 text-sm ${textColorClass}">${request.particulars}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${
                            isUrgent ? 'bg-red-100 text-red-800 urgent-badge' :
                            daysOld >= 10 ? 'bg-red-100 text-red-800' : 
                            daysOld >= 7 ? 'bg-orange-100 text-orange-800' : 
                            daysOld >= 3 ? 'bg-yellow-100 text-yellow-800' : 
                            'bg-blue-100 text-blue-800'
                        }">
                            ${request.program}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold ${textColorClass}">₱${request.amount}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${statusBadgeClass}">
                            <span class="w-2 h-2 mr-2 rounded-full ${statusDotClass}"></span>
                            ${statusLabel}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm ${textColorClass}">${request.date}</div>
                        <div class="text-xs ${
                            isUrgent ? 'text-red-600 font-bold' :
                            daysOld >= 10 ? 'text-red-600' : 
                            daysOld >= 7 ? 'text-orange-600' : 
                            daysOld >= 3 ? 'text-yellow-600' : 
                            'text-gray-500'
                        } font-medium mt-1">
                            ${isUrgent ? '⚠️ URGENT - ' : ''}${daysOld} ${daysOld === 1 ? 'day' : 'days'} old
                        </div>
                    </td>
                    <td class="px-5 py-4 text-center text-sm font-medium">
                        ${isReceiveButton 
                            ? `<button type="button" onclick="handleReceiveAction(${request.id}, this)" 
                               class="inline-flex items-center justify-center px-3 py-2 ${buttonClass} text-[13px] font-medium rounded-md transition-all duration-200 min-w-[120px] ${isUrgent ? 'urgent-glow' : ''}">
                               ${buttonText}
                               </button>`    
                            : `<a href="${buttonHref}" 
                               class="inline-flex items-center justify-center px-3 py-2 ${buttonClass} text-xs font-medium rounded-md transition-colors duration-200 min-w-[120px] ${isUrgent ? 'urgent-glow' : ''}">
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
            const res = await fetch("{{ route('Outgoing.dvrequests.fetch') }}?search=" + encodeURIComponent(searchTerm));
            const data = await res.json();
            
            allRequests = data;
            updateUrgentCountBadge();
            performSearch();
            
        } catch (err) {
            console.error('Failed to fetch DV requests', err);
            tableContainer.innerHTML = `<div class="text-center py-16 text-red-500">Failed to load DV requests.</div>`;
        }
    }

    // ============ OUTGOING AGING DOCUMENTS FUNCTIONS ============
    async function fetchOutgoingAgingDocuments() {
        try {
            const res = await fetch('{{ route("Outgoing.aging.documents") }}');
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const data = await res.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch Outgoing aging documents');
            }
            
            cachedOutgoingAgingData = data.documents || [];
            
            if (outgoingAgingAlertsContainer) {
                renderOutgoingAgingAlerts(data.documents, data.aging_count);
            }
            
        } catch (err) {
            console.error('Failed to fetch Outgoing aging documents:', err);
            
            if (!err.message.includes('404')) {
                showToast('Failed to load Outgoing aging documents: ' + err.message, 'error');
            }
            
            if (outgoingAgingAlertsContainer) {
                outgoingAgingAlertsContainer.innerHTML = '';
            }
        }
    }

    async function updateOutgoingStatistics() {
        try {
            const res = await fetch('{{ route("Outgoing.aging.statistics") }}');
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const data = await res.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch Outgoing statistics');
            }
            
            document.getElementById('outgoing-stat-critical').textContent = data.critical || 0;
            document.getElementById('outgoing-stat-high').textContent = data.high || 0;
            document.getElementById('outgoing-stat-medium').textContent = data.medium || 0;
            document.getElementById('outgoing-stat-total').textContent = data.total_aging || 0;
            
        } catch (err) {
            console.error('Failed to fetch Outgoing statistics:', err);
        }
    }

    function renderOutgoingAgingAlerts(documents, count) {
        if (!outgoingAgingAlertsContainer || !documents || documents.length === 0) {
            if (outgoingAgingAlertsContainer) outgoingAgingAlertsContainer.innerHTML = '';
            return;
        }

        // Filter for aging outgoing documents (exclude finalized/completed)
        const agingDocs = documents.filter(doc => !['Outgoing_finalized', 'completed'].includes(doc.status));
        
        if (agingDocs.length === 0) {
            outgoingAgingAlertsContainer.innerHTML = '';
            return;
        }

        // Ensure positive days_old values
        agingDocs.forEach(doc => {
            const days = Math.abs(Math.floor(Number(doc.days_old))) || 0;
            doc.days_old = days;
            doc.urgency = days >= 10 ? 'critical' : days >= 7 ? 'high' : days >= 3 ? 'medium' : 'low';
        });

        // Calculate statistics
        const stats = {
            critical: agingDocs.filter(doc => doc.days_old >= 10).length,
            high: agingDocs.filter(doc => doc.days_old >= 7 && doc.days_old < 10).length,
            medium: agingDocs.filter(doc => doc.days_old >= 3 && doc.days_old < 7).length,
            total: agingDocs.length
        };

        // Find oldest document
        const oldestDoc = agingDocs.reduce((oldest, doc) => 
            doc.days_old > oldest.days_old ? doc : oldest
        , agingDocs[0]);

        // Calculate average age
        const avgAge = Math.round(agingDocs.reduce((sum, doc) => sum + doc.days_old, 0) / agingDocs.length);

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
        const alertState = JSON.parse(localStorage.getItem('outgoingAgingAlertMinimized') || 'false');
        const isMinimized = alertState === true;

        // Build summary text
        let summaryText = `You currently have <strong>${stats.total} aging Outgoing document${stats.total > 1 ? 's' : ''}</strong> that require processing. `;
        
        // Add breakdown by priority
        const priorities = [];
        if (stats.critical > 0) priorities.push(`<strong class="text-red-700">${stats.critical} critical</strong> (10+ days old)`);
        if (stats.high > 0) priorities.push(`<strong class="text-orange-700">${stats.high} high priority</strong> (7-10 days)`);
        if (stats.medium > 0) priorities.push(`<strong class="text-yellow-700">${stats.medium} medium priority</strong> (3-7 days)`);
        
        if (priorities.length > 0) {
            if (priorities.length === 1) {
                summaryText += `This includes ${priorities[0]}. `;
            } else if (priorities.length === 2) {
                summaryText += `This includes ${priorities[0]} and ${priorities[1]}. `;
            } else {
                summaryText += `This includes ${priorities[0]}, ${priorities[1]}, and ${priorities[2]}. `;
            }
        }
        
        summaryText += `The average age of pending Outgoing documents is <strong>${avgAge} days</strong>, with the oldest being <strong class="${c.accentText}">${oldestDoc.control_number || oldestDoc.dv_number || 'Unknown'}</strong> at <strong>${oldestDoc.days_old} days old</strong> from ${oldestDoc.submitted_by || 'Unknown'}.`;
        
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
                            <strong>Urgent:</strong> ${stats.critical} Outgoing document${stats.critical > 1 ? 's have' : ' has'} exceeded the 10-day processing threshold and require${stats.critical === 1 ? 's' : ''} immediate attention.
                        </p>
                    </div>
                </div>
            `;
        }

        outgoingAgingAlertsContainer.innerHTML = `
            <div class="rounded-lg border-l-4 p-4 ${c.bg} shadow-md outgoing-aging-alert-container">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 flex-1">
                        <span class="text-2xl flex-shrink-0">${c.icon}</span>
                        <div class="flex-1">
                            <h3 class="font-bold text-base ${c.text} mb-2">Outgoing Aging Documents Summary</h3>
                            <div class="outgoing-alert-content transition-all duration-300 ease-in-out ${isMinimized ? 'hidden' : 'block'}">
                                <p class="text-sm ${c.text} leading-relaxed opacity-90">
                                    ${summaryText}
                                </p>
                                ${urgentMessage}
                            </div>
                            <div class="outgoing-alert-collapsed transition-all duration-300 ease-in-out ${isMinimized ? 'block' : 'hidden'}">
                                <p class="text-sm ${c.text} opacity-90">
                                    <strong>${stats.total}</strong> aging Outgoing document${stats.total > 1 ? 's' : ''} 
                                    ${stats.critical > 0 ? `(<strong class="text-red-700">${stats.critical} critical</strong>)` : ''} • 
                                    Oldest: <strong>${oldestDoc.days_old} days</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                    <button class="minimize-outgoing-alert-btn text-gray-600 hover:text-gray-800 transition-colors p-1 rounded hover:bg-white/50 flex-shrink-0" 
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
        const minimizeBtn = outgoingAgingAlertsContainer.querySelector('.minimize-outgoing-alert-btn');
        const alertContent = outgoingAgingAlertsContainer.querySelector('.outgoing-alert-content');
        const alertCollapsed = outgoingAgingAlertsContainer.querySelector('.outgoing-alert-collapsed');
        
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
                    localStorage.setItem('outgoingAgingAlertMinimized', 'false');
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
                    localStorage.setItem('outgoingAgingAlertMinimized', 'true');
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
        
        const urgentCount = filteredRequests.filter(req => req.is_urgent).length;
        const urgentMessage = urgentCount > 0 ? ` (${urgentCount} urgent)` : '';
        showToast(`Sorted by priority${urgentMessage} (urgent first)`, 'info');
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
            isUrgentOnlyView = false; // Turn off urgent filter
            searchInput.value = '';
            searchTerm = '';
            
            const criticalCount = allRequests.filter(req => {   
                const days = Math.abs(req.days_old) || 0;
                return days >= 10;
            }).length;
            
            if (criticalCount === 0) {
                showToast('No critical documents (10+ days) found', 'warning');
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

    // Show Urgent Only button - NEW
    document.getElementById('show-urgent-only')?.addEventListener('click', function() {
        if (!isUrgentOnlyView) {
            isUrgentOnlyView = true;
            isCriticalOnlyView = false; // Turn off critical filter
            searchInput.value = '';
            searchTerm = '';
            
            const urgentCount = allRequests.filter(req => req.is_urgent === true).length;
            
            if (urgentCount === 0) {
                showToast('No urgent documents found', 'warning');
                isUrgentOnlyView = false;
            } else {
                showToast(`Showing ${urgentCount} urgent document(s) only`, 'info');
            }
        } else {
            isUrgentOnlyView = false;
            showToast('Showing all documents', 'info');
        }
        
        performSearch();
        updateSortButtonStyles();
    });

    // Show All button
    document.getElementById('show-all')?.addEventListener('click', function() {
        isCriticalOnlyView = false;
        isUrgentOnlyView = false;
        searchInput.value = '';
        searchTerm = '';
        performSearch();
        updateSortButtonStyles();
        showToast('Showing all documents', 'info');
    });

    // ============ AUTO-REFRESH ============
    setInterval(fetchOutgoingAgingDocuments, 2 * 60 * 1000); // Every 2 minutes
    setInterval(updateOutgoingStatistics, 2 * 60 * 1000);
    setInterval(fetchDvRequests, 5 * 60 * 1000); // Every 5 minutes

    // ============ INITIALIZATION ============
    updatePaginationInfo();
    updateSortButtonStyles();
    fetchDvRequests();
    fetchOutgoingAgingDocuments();
    updateOutgoingStatistics();
});
</script>
@endsection