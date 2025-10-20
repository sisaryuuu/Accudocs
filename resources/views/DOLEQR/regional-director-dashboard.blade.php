@extends('layouts.app')

@section('title', 'Regional Director Dashboard')

@section('content')
<!-- Main Content Section -->
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-[150rem] mx-auto px-6">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Regional Director Dashboard</h1>
                <p class="mt-2 text-sm text-gray-600">Review and approve disbursement voucher requests</p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-4">
                <!-- Real-time Status Indicator -->
                <div id="realtimeStatus" class="flex items-center space-x-2 px-4 py-2 bg-green-50 border border-green-200 rounded-lg">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-sm text-green-700 font-medium">Live Updates Active</span>
                </div>
                <!-- QR Scanner Button -->
                <a href="{{ route('regional.director.scanner') }}" 
                   class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200 ease-in-out">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    Open QR Scanner
                </a>
            </div>
        </div>
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Pending Card -->
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Total Pending</p>
                        <h3 class="text-3xl font-bold mt-2" id="statTotalPending">{{ $stats['total_pending'] }}</h3>
                        <p class="text-purple-100 text-sm mt-2">DVs requiring action</p>
                    </div>
                    <div class="bg-purple-400 bg-opacity-20 p-4 rounded-full">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Awaiting Receipt Card -->
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm font-medium">Awaiting Receipt</p>
                        <h3 class="text-3xl font-bold mt-2" id="statAwaitingReceipt">{{ $stats['awaiting_receipt'] }}</h3>
                        <p class="text-orange-100 text-sm mt-2">Ready to receive</p>
                    </div>
                    <div class="bg-orange-400 bg-opacity-20 p-4 rounded-full">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Awaiting Approval Card -->
            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-indigo-100 text-sm font-medium">Awaiting Approval</p>
                        <h3 class="text-3xl font-bold mt-2" id="statAwaitingApproval">{{ $stats['awaiting_approval'] }}</h3>
                        <p class="text-indigo-100 text-sm mt-2">Ready to sign and approve</p>
                    </div>
                    <div class="bg-indigo-400 bg-opacity-20 p-4 rounded-full">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- DVs Pending for Review - Full Width -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h3 class="text-xl font-semibold text-gray-900">DVs Pending for Review</h3>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" id="searchDVs" placeholder="Search DV Number, Payee, Program..."
                               class="w-80 pl-10 pr-4 py-3 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M21 21l-4.35-4.35M16 10a6 6 0 11-12 0 6 6 0 0112 0z"/>
                        </svg>
                    </div>
                    <div class="text-sm text-gray-600">
                        Last updated: <span id="lastUpdated">{{ now()->format('M d, Y h:i A') }}</span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto" id="dvTableContainer">
                @if($pendingDvs->count() > 0)
                    <table class="w-full table-auto divide-y divide-gray-200 min-w-full" id="dvTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">DV Number</th>
                                <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">Payee Details</th>
                                <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">Amount</th>
                                <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">Program</th>
                                <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">Particulars</th>
                                <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">Status</th>
                                <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">QR Code</th>
                                <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="dvTableBody">
                            @foreach($pendingDvs as $dv)
                            <tr class="hover:bg-gray-50 transition-colors duration-150 group" data-dv-id="{{ $dv->id }}" data-dv-status="{{ $dv->status }}">
                                <td class="px-8 py-6 whitespace-nowrap">
                                    <div class="text-base font-bold text-gray-900">{{ $dv->dv_number }}</div>
                                    <div class="text-sm text-gray-500 mt-1">Ref: {{ $dv->reference_number ?? 'N/A' }}</div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-base font-semibold text-gray-900">{{ $dv->focalPerson->fname }} {{ $dv->focalPerson->lname }}</div>
                                    <div class="text-xs text-gray-500 mt-1">TIN: {{ $dv->focalPerson->TIN_number ?? 'N/A' }}</div>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap">
                                    <div class="text-xl font-bold text-green-600">₱{{ number_format($dv->amount, 2) }}</div>
                                    <div class="text-sm text-gray-500 mt-1">{{ $dv->mode_of_payment ?? 'Cash' }}</div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col space-y-2">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800 max-w-xs">
                                            {{ $dv->program->prcode ?? 'N/A' }} - {{ $dv->program->description ?? 'N/A' }}
                                        </span>
                                    </div>
                                </td>
                                     <td class="px-8 py-6">
                                    <div class="flex flex-col space-y-2">   
                                        <div class="text-sm text-gray-600">
                                            {{ $dv->particulars ? Str::limit($dv->particulars, 50) : 'No particulars' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap">
                                    <div class="flex flex-col space-y-2">
                                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium 
                                            {{ $dv->status == 'RANCA_validated' ? 'bg-orange-100 text-orange-800 border border-orange-200' : 'bg-indigo-100 text-indigo-800 border border-indigo-200' }}">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ str_replace('_', ' ', $dv->status) }}
                                        </span>
                                        <div class="text-xs text-gray-500">
                                            {{ $dv->updated_at->format('M d, Y h:i A') }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    @if($dv->qr_code_token)
                                        <div class="flex flex-col items-center space-y-3">
                                            <div class="flex justify-center relative group/qr">
                                                <img 
                                                    src="{{ route('dv.generate-qr', $dv->id) }}?v={{ time() }}" 
                                                    width="80" 
                                                    height="80"
                                                    alt="QR Code for DV {{ $dv->dv_number }}" 
                                                    class="rounded-xl border-2 border-gray-200 shadow-md hover:shadow-xl transition-all duration-300 cursor-pointer hover:scale-105 hover:border-blue-300"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                                    onclick="showQRModal({{ $dv->id }})"
                                                    loading="lazy"
                                                >
                                                <div class="hidden text-red-400 text-xs text-center">
                                                    <svg class="w-12 h-12 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                                    </svg>
                                                    Failed to load
                                                </div>
                                            </div>
                                            
                                            <div class="text-center">
                                                @if($dv->qr_status === 'active')
                                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-300 shadow-sm">
                                                        <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                        </svg>
                                                        QR Active
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-300 shadow-sm">
                                                        <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 1a9 9 0 100 18A9 9 0 0010 1zm0 16a7 7 0 100-14 7 7 0 000 14zm1-7a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>
                                                        </svg>
                                                        QR Inactive
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <button onclick="showQRModal({{ $dv->id }})" 
                                                    class="text-sm text-blue-600 hover:text-blue-800 font-medium underline transition-colors">
                                                View Large QR
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-center py-4">
                                            <div class="mx-auto w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mb-3 border-2 border-gray-200">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                                </svg>
                                            </div>
                                            <span class="text-gray-500 font-medium block">No QR Code</span>
                                            <span class="text-gray-400 text-sm">Not yet generated</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col space-y-3">
                                        <a href="{{ route('dv.qr.interface', $dv->qr_code_token) }}" 
                                           class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ $dv->status == 'RANCA_validated' ? 'Receive DV' : 'Sign & Approve' }}
                                        </a>
                                        <div class="text-center">
                                            <button onclick="showDvDetails({{ $dv->id }})" 
                                                    class="text-sm text-blue-600 hover:text-blue-800 font-medium underline">
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-20" id="emptyState">
                        <div class="mx-auto w-24 h-24 bg-gray-100 rounded-3xl flex items-center justify-center mb-6 border-2 border-gray-200">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">No Pending DVs</h3>
                        <p class="text-gray-600 text-lg mb-8 max-w-md mx-auto">All disbursement vouchers have been reviewed. Check back later for new requests.</p>
                        <div class="flex justify-center space-x-4">
                            <a href="{{ route('regional.director.scanner') }}" 
                               class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors duration-200 shadow-lg">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                </svg>
                                Open QR Scanner
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            @if($pendingDvs->count() > 0)
            <div class="px-8 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-semibold" id="dvCount">{{ $pendingDvs->count() }}</span> DVs pending for review
                    </div>
                    <div class="text-sm text-gray-600">
                        Last updated: <span id="lastUpdatedFooter">{{ now()->format('M d, Y h:i A') }}</span>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- QR Modal -->
<div id="qrModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900">QR Code</h3>
            <button onclick="closeQRModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div id="qrContent" class="text-center"></div>
        <button id="downloadQRBtn" onclick="downloadQR()" class="hidden mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg">
            Download QR Code
        </button>
    </div>
</div>

<!-- Pusher Scripts -->
<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script>
// Global variables
let currentQRData = null;
let pusher = null;
let rdChannel = null;

// Update last updated timestamp - WITH NULL CHECKS
function updateLastUpdatedTime() {
    const now = formatDateTime(new Date());
    
    const lastUpdatedHeader = document.getElementById('lastUpdated');
    if (lastUpdatedHeader) {
        lastUpdatedHeader.textContent = now;
    }
    
    const lastUpdatedFooter = document.getElementById('lastUpdatedFooter');
    if (lastUpdatedFooter) {
        lastUpdatedFooter.textContent = now;
    }
}

// Update statistics counters - WITH NULL CHECKS
async function updateStatistics() {
    try {
        const response = await fetch('/regional.director/dashboard/stats');
        const stats = await response.json();
        
        if (stats.success) {
            const totalPendingEl = document.getElementById('statTotalPending');
            if (totalPendingEl) {
                totalPendingEl.textContent = stats.data.total_pending;
            }
            
            const awaitingReceiptEl = document.getElementById('statAwaitingReceipt');
            if (awaitingReceiptEl) {
                awaitingReceiptEl.textContent = stats.data.awaiting_receipt;
            }
            
            const awaitingApprovalEl = document.getElementById('statAwaitingApproval');
            if (awaitingApprovalEl) {
                awaitingApprovalEl.textContent = stats.data.awaiting_approval;
            }
            
            const dvCountEl = document.getElementById('dvCount');
            if (dvCountEl) {
                dvCountEl.textContent = stats.data.total_pending;
            }
        }
    } catch (error) {
        console.error('[Error] updating statistics:', error);
    }
}

// Update real-time status indicator - WITH NULL CHECK
function updateRealtimeStatus(isConnected) {
    const statusElement = document.getElementById('realtimeStatus');
    
    if (!statusElement) {
        console.warn('[Warn] realtimeStatus element not found');
        return;
    }
    
    if (isConnected) {
        statusElement.innerHTML = `
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span class="text-sm text-green-700 font-medium">Live Updates Active</span>
        `;
        statusElement.className = 'flex items-center space-x-2 px-4 py-2 bg-green-50 border border-green-200 rounded-lg';
    } else {
        statusElement.innerHTML = `
            <div class="w-2 h-2 bg-red-500 rounded-full"></div>
            <span class="text-sm text-red-700 font-medium">Disconnected</span>
        `;
        statusElement.className = 'flex items-center space-x-2 px-4 py-2 bg-red-50 border border-red-200 rounded-lg';
    }
}

// Initialize Pusher for real-time updates
function initializePusher() {
    try {
        console.log('[Pusher] Initializing...');
        
        pusher = new Pusher('687d829e0e8bad4e0b71', {
            cluster: 'ap3',
            encrypted: true,
            forceTLS: true
        });

        console.log('[Pusher] ✓ Instance created');

        // Set up connection listeners
        pusher.connection.bind('connecting', function() {
            console.log('[Pusher] Connecting...');
            updateRealtimeStatus(false);
        });

        pusher.connection.bind('connected', function() {
            console.log('[Pusher] ✓ Connected');
            updateRealtimeStatus(true);
        });

        pusher.connection.bind('disconnected', function() {
            console.log('[Pusher] Disconnected');
            updateRealtimeStatus(false);
        });

        pusher.connection.bind('error', function(err) {
            console.error('[Pusher] Error:', err);
            updateRealtimeStatus(false);
        });

        // Subscribe to regional director dashboard channel
        rdChannel = pusher.subscribe('regional-director-dashboard');
        
        console.log('[Channel] Subscribing to regional-director-dashboard');
        
        rdChannel.bind('pusher:subscription_succeeded', function() {
            console.log('[Channel] ✓ Subscription succeeded');
        });

        rdChannel.bind('pusher:subscription_error', function(status) {
            console.error('[Channel] Subscription error:', status);
        });
        
        // Listen for DV status updates
        rdChannel.bind('dv-status-updated', function(data) {
            console.log('[Event] DV Status Updated:', data);
            handleDvStatusUpdate(data);
        });

        // Listen for new DV added
        rdChannel.bind('new-dv-added', function(data) {
            console.log('[Event] New DV Added:', data);
            handleNewDvAdded(data);
        });

        // Listen for DV removed (approved/moved)
        rdChannel.bind('dv-removed', function(data) {
            console.log('[Event] DV Removed:', data);
            handleDvRemoved(data);
        });
    rdChannel.bind('dv-validated-by-accounting', function(data) {
    console.log('[Event] DV Validated by Accounting III:', data);
    handleNewDvValidated(data);
});
    } catch (error) {
        console.error('[Pusher] Error initializing:', error);
        updateRealtimeStatus(false);
    }
}
/ Handle new DV added to RD queue (when Accounting III finishes validation)
async function handleNewDvValidated(data) {
    console.log('[Handler] Processing DV validated by Accounting III:', data.dv_id);
    
    // Check if DV already exists in table
    const existingRow = document.querySelector(`tr[data-dv-id="${data.dv_id}"]`);
    if (existingRow) {
        console.log('[Handler] DV already in RD queue, updating row');
        // Update existing row status
        const statusCell = existingRow.querySelector('td:nth-child(6) span');
        if (statusCell) {
            statusCell.className = 'inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-orange-100 text-orange-800 border border-orange-200';
            statusCell.innerHTML = `
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                RANCA validated
            `;
        }
        return;
    }
    
    try {
        // Fetch complete DV details from backend
        const response = await fetch(`/api/dv/${data.dv_id}/details`);
        const dvData = await response.json();
        
        if (dvData.success) {
            // Add DV to table
            addDvRowToTable(dvData.dv);
            
            // Update statistics
            updateStatistics();
            
            // Show notification
            showToast(`DV ${data.dv_number} is now ready for Regional Director review`, 'success');
            
            console.log('[Handler] DV added to RD queue successfully');
        } else {
            console.error('[Handler] Failed to fetch DV details:', dvData.message);
        }
    } catch (error) {
        console.error('[Handler] Error fetching DV details:', error);
    }
    
    updateLastUpdatedTime();
        }

        // Enhanced initialization - trigger refresh on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[App] DOM loaded, initializing dashboard');
            
            initializePusher();
            initializeSearch();
            initializeModalHandlers();
            
            // Force refresh stats on load to catch any pending DVs
            updateStatistics();
            
            console.log('[App] ✓ Dashboard initialization complete');
        });

        // Optional: Auto-refresh statistics every 30 seconds as fallback
        setInterval(function() {
            updateStatistics();
        }, 30000);
        console.log('[Pusher] ✓ Initialization complete');

// Handle DV status update from Pusher
async function handleDvStatusUpdate(data) {
    console.log('[Handler] Processing status update for DV:', data.dv_id);
    
    const dvRow = document.querySelector(`tr[data-dv-id="${data.dv_id}"]`);
    
    if (dvRow) {
        // Check if DV should still be shown in RD dashboard
        const shouldRemove = !['RANCA_validated', 'RD_received'].includes(data.new_status);
        
        if (shouldRemove) {
            console.log('[Handler] Removing row - DV moved out of RD queue');
            // Remove with animation
            dvRow.style.transition = 'all 0.5s ease-out';
            dvRow.style.opacity = '0';
            dvRow.style.transform = 'translateX(100%)';
            
            setTimeout(() => {
                dvRow.remove();
                updateStatistics();
                checkEmptyState();
            }, 500);
            
            showToast(`DV ${data.dv_number} moved to ${data.new_status.replace(/_/g, ' ')}`, 'info');
        } else {
            console.log('[Handler] Updating row - DV still in RD queue');
            // Update status badge with animation
            const statusCell = dvRow.querySelector('td:nth-child(6)');
            if (statusCell) {
                statusCell.style.transition = 'all 0.3s ease';
                statusCell.style.backgroundColor = '#dbeafe';
                
                setTimeout(() => {
                    updateDvRow(data);
                    statusCell.style.backgroundColor = '';
                }, 300);
            }
            
            showToast(`DV ${data.dv_number} status updated`, 'success');
        }
    } else {
        console.warn('[Handler] DV row not found for ID:', data.dv_id);
    }
    
    updateLastUpdatedTime();
}

// Handle new DV added to RD queue
async function handleNewDvAdded(data) {
    console.log('[Handler] New DV added:', data.dv.dv_number);
    
    // Check if DV already exists
    const existingRow = document.querySelector(`tr[data-dv-id="${data.dv.id}"]`);
    if (existingRow) {
        console.log('[Handler] DV already exists in table, skipping');
        return;
    }
    
    try {
        // Fetch the complete DV data
        const response = await fetch(`/api/dv/${data.dv.id}/details`);
        const dvData = await response.json();
        
        if (dvData.success) {
            addDvRowToTable(dvData.dv);
            updateStatistics();
            showToast(`New DV ${data.dv.dv_number} ready for review`, 'success');
        } else {
            console.error('[Handler] Failed to fetch DV details:', dvData.message);
        }
    } catch (error) {
        console.error('[Handler] Error fetching DV details:', error);
    }
    
    updateLastUpdatedTime();
}

// Handle DV removed from queue
function handleDvRemoved(data) {
    console.log('[Handler] DV removed:', data.dv_id);
    
    const dvRow = document.querySelector(`tr[data-dv-id="${data.dv_id}"]`);
    if (dvRow) {
        dvRow.style.transition = 'all 0.5s ease-out';
        dvRow.style.opacity = '0';
        dvRow.style.transform = 'translateX(-100%)';
        
        setTimeout(() => {
            dvRow.remove();
            updateStatistics();
            checkEmptyState();
        }, 500);
        
        showToast(`DV ${data.dv_number} ${data.reason === 'approved' ? 'approved and forwarded' : 'removed'}`, 'info');
    } else {
        console.warn('[Handler] DV row not found for ID:', data.dv_id);
    }
    
    updateLastUpdatedTime();
}

// Update DV row with new data
function updateDvRow(data) {
    const dvRow = document.querySelector(`tr[data-dv-id="${data.dv_id}"]`);
    if (!dvRow) {
        console.warn('[Update] DV row not found:', data.dv_id);
        return;
    }
    
    // Update status attribute
    dvRow.setAttribute('data-dv-status', data.new_status);
    
    // Update status badge
    const statusCell = dvRow.querySelector('td:nth-child(6) span');
    if (statusCell) {
        const isAwaiting = data.new_status === 'RANCA_validated';
        statusCell.className = `inline-flex items-center px-4 py-2 rounded-full text-sm font-medium ${
            isAwaiting ? 'bg-orange-100 text-orange-800 border border-orange-200' : 'bg-indigo-100 text-indigo-800 border border-indigo-200'
        }`;
        statusCell.innerHTML = `
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            ${data.new_status.replace(/_/g, ' ')}
        `;
    }
    
    // Update timestamp
    const timeCell = dvRow.querySelector('td:nth-child(6) .text-xs');
    if (timeCell) {
        timeCell.textContent = formatDateTime(new Date());
    }
    
    // Update action button text
    const actionButton = dvRow.querySelector('td:nth-child(8) a');
    if (actionButton) {
        const buttonText = data.new_status === 'RANCA_validated' ? 'Receive DV' : 'Sign & Approve';
        actionButton.innerHTML = `
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            ${buttonText}
        `;
    }
}

// Add new DV row to table
function addDvRowToTable(dv) {
    const tableContainer = document.getElementById('dvTableContainer');
    let tableBody = document.getElementById('dvTableBody');
    
    // If no table body exists, create the table
    if (!tableBody) {
        tableContainer.innerHTML = `
            <table class="w-full table-auto divide-y divide-gray-200 min-w-full" id="dvTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">DV Number</th>
                        <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">Payee Details</th>
                        <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">Amount</th>
                        <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">Program</th>
                        <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">Particulars</th>
                        <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">Status</th>
                        <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200">QR Code</th>
                        <th class="px-8 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="dvTableBody"></tbody>
            </table>
        `;
        tableBody = document.getElementById('dvTableBody');
    }
    
    const newRow = createDvRow(dv);
    
    // Add with animation
    newRow.style.opacity = '0';
    newRow.style.transform = 'translateX(-20px)';
    tableBody.insertBefore(newRow, tableBody.firstChild);
    
    setTimeout(() => {
        newRow.style.transition = 'all 0.5s ease-out';
        newRow.style.opacity = '1';
        newRow.style.transform = 'translateX(0)';
    }, 10);
}

// Create DV table row
function createDvRow(dv) {
    const row = document.createElement('tr');
    row.className = 'hover:bg-gray-50 transition-colors duration-150 group';
    row.setAttribute('data-dv-id', dv.id);
    row.setAttribute('data-dv-status', dv.status);
    
    const isAwaiting = dv.status === 'RANCA_validated';
    const buttonText = isAwaiting ? 'Receive DV' : 'Sign & Approve';
    
    row.innerHTML = `
        <td class="px-8 py-6 whitespace-nowrap">
            <div class="text-base font-bold text-gray-900">${dv.dv_number}</div>
            <div class="text-sm text-gray-500 mt-1">Ref: ${dv.reference_number || 'N/A'}</div>
        </td>
        <td class="px-8 py-6">
            <div class="text-base font-semibold text-gray-900">${dv.focal_person.fname} ${dv.focal_person.lname}</div>
            <div class="text-xs text-gray-500 mt-1">TIN: ${dv.focal_person.tin_number || 'N/A'}</div>
        </td>
        <td class="px-8 py-6 whitespace-nowrap">
            <div class="text-xl font-bold text-green-600">₱${formatNumber(dv.amount)}</div>
            <div class="text-sm text-gray-500 mt-1">${dv.mode_of_payment || 'Cash'}</div>
        </td>
        <td class="px-8 py-6">
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800 max-w-xs">
                ${dv.program.prcode || 'N/A'} - ${dv.program.description || 'N/A'}
            </span>
        </td>
        <td class="px-8 py-6">
            <div class="text-sm text-gray-600">
                ${dv.particulars ? limitText(dv.particulars, 50) : 'No particulars'}
            </div>
        </td>
        <td class="px-8 py-6 whitespace-nowrap">
            <div class="flex flex-col space-y-2">
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium ${
                    isAwaiting ? 'bg-orange-100 text-orange-800 border border-orange-200' : 'bg-indigo-100 text-indigo-800 border border-indigo-200'
                }">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ${dv.status.replace(/_/g, ' ')}
                </span>
                <div class="text-xs text-gray-500">
                    ${formatDateTime(new Date())}
                </div>
            </div>
        </td>
        <td class="px-8 py-6">
            ${dv.qr_code_token ? `
                <div class="flex flex-col items-center space-y-3">
                    <img 
                        src="/dv/${dv.id}/generate-qr?v=${Date.now()}" 
                        width="80" 
                        height="80"
                        alt="QR Code for DV ${dv.dv_number}" 
                        class="rounded-xl border-2 border-gray-200 shadow-md hover:shadow-xl transition-all duration-300 cursor-pointer hover:scale-105 hover:border-blue-300"
                        onclick="showQRModal(${dv.id})"
                        loading="lazy"
                    >
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-300 shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        QR Active
                    </span>
                </div>
            ` : `
                <div class="text-center py-4">
                    <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <span class="text-gray-500 font-medium text-sm">No QR</span>
                </div>
            `}
        </td>
        <td class="px-8 py-6">
            <div class="flex flex-col space-y-3">
                <a href="/dv/qr/${dv.qr_code_token}" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ${buttonText}
                </a>
                <button onclick="showDvDetails(${dv.id})" 
                        class="text-sm text-blue-600 hover:text-blue-800 font-medium underline">
                    View Details
                </button>
            </div>
        </td>
    `;
    
    return row;
}

// Check if table is empty and show empty state
function checkEmptyState() {
    const tableBody = document.getElementById('dvTableBody');
    const rows = tableBody ? tableBody.querySelectorAll('tr') : [];
    
    if (rows.length === 0) {
        const container = document.getElementById('dvTableContainer');
        if (container) {
            container.innerHTML = `
                <div class="text-center py-20" id="emptyState">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-3xl flex items-center justify-center mb-6 border-2 border-gray-200">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">No Pending DVs</h3>
                    <p class="text-gray-600 text-lg mb-8 max-w-md mx-auto">All DVs have been reviewed.</p>
                </div>
            `;
        }
    }
}

// Format number with commas
function formatNumber(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Format date time
function formatDateTime(date) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = months[date.getMonth()];
    const day = date.getDate();
    const year = date.getFullYear();
    let hours = date.getHours();
    const minutes = date.getMinutes().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    
    return `${month} ${day}, ${year} ${hours}:${minutes} ${ampm}`;
}

// Limit text length
function limitText(text, length) {
    return text.length > length ? text.substring(0, length) + '...' : text;
}

// Toast notification function
function showToast(message, type = 'success') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
        warning: 'bg-yellow-500'
    };
    
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium transform transition-all duration-300 translate-x-full ${colors[type] || colors.success}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// QR Modal functions
async function showQRModal(dvId) {
    const modal = document.getElementById('qrModal');
    if (!modal) return;
    
    const content = document.getElementById('qrContent');
    
    modal.classList.remove('hidden');
    content.innerHTML = `
        <div class="flex items-center justify-center mb-4">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
        <p class="text-gray-600">Loading QR Code...</p>
    `;
    
    const downloadBtn = document.getElementById('downloadQRBtn');
    if (downloadBtn) {
        downloadBtn.classList.add('hidden');
    }
    
    try {
        const qrUrl = `/dv/${dvId}/generate-qr?v=${new Date().getTime()}`;
        
        content.innerHTML = `
            <div class="space-y-4">
                <div class="bg-white p-4 rounded-xl border-2 border-blue-200 shadow-inner">
                    <img src="${qrUrl}" alt="QR Code" class="w-full h-auto mx-auto">
                </div>
                <div class="text-sm text-gray-600">
                    <p>Scan this QR code to process the DV</p>
                </div>
            </div>
        `;
        
        currentQRData = { qrUrl, dvId };
        if (downloadBtn) {
            downloadBtn.classList.remove('hidden');
        }
        
    } catch (error) {
        console.error('Error loading QR:', error);
        content.innerHTML = `
            <div class="text-center py-4">
                <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-red-600 font-semibold">Failed to load QR Code</p>
            </div>
        `;
    }
}

function closeQRModal() {
    const modal = document.getElementById('qrModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    currentQRData = null;
}

function downloadQR() {
    if (!currentQRData) return;
    
    const link = document.createElement('a');
    link.href = currentQRData.qrUrl;
    link.download = `DV_QR_${currentQRData.dvId}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchDVs');
    if (!searchInput) return;
    
    searchInput.addEventListener('keyup', function() {
        let query = this.value.toLowerCase();
        let rows = document.querySelectorAll('#dvTable tbody tr');
        
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            if (text.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

// Initialize modal handlers
function initializeModalHandlers() {
    const qrModal = document.getElementById('qrModal');
    if (!qrModal) return;
    
    qrModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeQRModal();
        }
    });
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const qrModal = document.getElementById('qrModal');
        if (qrModal && !qrModal.classList.contains('hidden')) {
            closeQRModal();
        }
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        window.location.href = '/regional.director/scanner';
    }
});

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('[App] DOM loaded, initializing dashboard');
    
    initializePusher();
    initializeSearch();
    initializeModalHandlers();
    
    console.log('[App] ✓ Dashboard initialization complete');
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (pusher) {
        pusher.disconnect();
        console.log('[Pusher] Disconnected');
    }
});
</script>

<style>
.table-container::-webkit-scrollbar {
    height: 8px;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.transition-all {
    transition: all 0.3s ease-in-out;
}

.hover-lift:hover {
    transform: translateY(-2px);
}
</style>
@endsection