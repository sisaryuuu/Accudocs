@extends('layouts.app')

@section('title', 'Regional Director Dashboard')

@section('content')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-[95rem] mx-auto px-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Regional Director Dashboard</h1>
                <p class="mt-2 text-sm text-gray-600">Review and approve disbursement voucher requests</p>
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

        <!-- Bulk Actions Section -->
        <div id="bulkActionsSection" class="hidden bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="selectAllDVs" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="selectAllDVs" class="ml-2 text-sm font-medium text-gray-700">Select All</label>
                    </div>
                    <div class="text-sm text-gray-600">
                        <span id="selectedCount">0</span> DVs selected
                    </div>
                    <div id="selectedStatusInfo" class="text-sm text-gray-500 hidden">
                        <!-- Will show status breakdown of selected DVs -->
                    </div>
                </div>
                <div class="flex space-x-2">
                    <!-- Bulk Receive Button (for RANCA_validated DVs) -->
                    <button id="bulkReceiveBtn" class="hidden inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Bulk Receive
                    </button>
                    
                    <!-- Bulk Sign & Approve Button (for Regional_Director_received DVs) -->
                    <button id="bulkSignApproveBtn" class="hidden inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        Bulk Sign & Approve
                    </button>
                    
                    <button id="clearSelectionBtn" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded-md transition-colors duration-200">
                        Clear Selection
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Total Pending Card -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-700 text-sm font-semibold">Total Pending</p>
                        <p id="stat-total-pending" class="text-2xl font-bold text-blue-600">{{ $stats['total_pending'] }}</p>
                        <p class="text-xs text-blue-600">DVs requiring action</p>
                    </div>
                    <svg class="w-8 h-8 text-blue-500 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>

            <!-- Awaiting Receipt Card -->
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-4 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-700 text-sm font-semibold">Awaiting Receipt</p>
                        <p id="stat-awaiting-receipt" class="text-2xl font-bold text-yellow-600">{{ $stats['awaiting_receipt'] }}</p>
                        <p class="text-xs text-yellow-600">Ready to receive</p>
                    </div>
                    <svg class="w-8 h-8 text-yellow-500 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                </div>
            </div>

            <!-- Awaiting Approval Card -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-700 text-sm font-semibold">Awaiting Approval</p>
                        <p id="stat-awaiting-approval" class="text-2xl font-bold text-green-600">{{ $stats['awaiting_approval'] }}</p>
                        <p class="text-xs text-green-600">Ready to sign and approve</p>
                    </div>
                    <svg class="w-8 h-8 text-green-500 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <!-- QR Scanner Card -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-700 text-sm font-semibold">Quick Actions</p>
                        <a href="{{ route('regional.director.scanner') }}" 
                           class="inline-flex items-center px-4 py-2 mt-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                            Open QR Scanner
                        </a>
                    </div>
                    <svg class="w-8 h-8 text-purple-500 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="mb-4">
            <input type="text" id="searchInput" 
                   placeholder="🔍 Search DVs (DV Number, Payee, Program, Particulars)..."
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
                    <p class="text-gray-500">Loading DVs...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Modal -->
<div id="qrModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-900">QR Code - <span id="qrDVNumber"></span></h3>
            <button onclick="closeQRModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="text-center">
            <div id="qrCodeContainer" class="flex justify-center mb-4">
                <!-- QR code will be displayed here -->
            </div>
            
            <!-- QR Token Display -->
            <div id="qrTokenContainer" class="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-sm font-medium text-gray-700 mb-1">QR Token:</p>
                <div class="flex items-center justify-between">
                    <code id="qrTokenDisplay" class="text-xs text-gray-600 break-all flex-1 text-left mr-2"></code>
                    <button onclick="copyQrToken()" class="flex-shrink-0 p-1 text-gray-500 hover:text-blue-600 transition-colors" title="Copy token">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <p id="qrModalMessage" class="text-gray-600 mb-4"></p>
            
            <div class="flex justify-center space-x-3">
                <button onclick="closeQRModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">
                    Close
                </button>
                <button id="downloadQRBtn" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors hidden">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download PNG
                </button>
                <button id="openQRScannerBtn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors hidden">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    Open Scanner
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Copy Success Toast -->
<div id="copyToast" class="hidden fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg transition-opacity duration-300">
    Token copied to clipboard!
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
            <p id="bulkActionModalMessage" class="text-gray-700 mb-4">You are about to process the following DVs:</p>
            <div id="bulkActionList" class="h-64 overflow-y-auto border border-gray-200 rounded-lg p-4 bg-gray-50">
                <!-- Selected DVs will be listed here -->
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
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Processing DVs</h3>
        <p class="text-gray-600" id="loadingMessage">Please wait while we process your request...</p>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col space-y-2"></div>

<script>
// ====================================
// TOAST NOTIFICATIONS
// ====================================
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

// ====================================
// BULK ACTIONS FUNCTIONALITY
// ====================================
let selectedDVs = new Set();

function initializeBulkActions() {
    const bulkSection = document.getElementById('bulkActionsSection');
    const selectAllCheckbox = document.getElementById('selectAllDVs');
    const selectedCount = document.getElementById('selectedCount');
    const bulkReceiveBtn = document.getElementById('bulkReceiveBtn');
    const bulkSignApproveBtn = document.getElementById('bulkSignApproveBtn');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
    const statusInfo = document.getElementById('selectedStatusInfo');
    
    function updateBulkSectionVisibility() {
        if (selectedDVs.size > 0) {
            bulkSection.classList.remove('hidden');
            updateBulkButtonsVisibility();
            updateSelectedStatusInfo();
        } else {
            bulkSection.classList.add('hidden');
            bulkReceiveBtn.classList.add('hidden');
            bulkSignApproveBtn.classList.add('hidden');
        }
        if (selectedCount) {
            selectedCount.textContent = selectedDVs.size;
        }
    }
    
    function updateBulkButtonsVisibility() {
        let hasReceivableDVs = false;
        let hasApprovableDVs = false;
        
        selectedDVs.forEach(dvId => {
            const dvRow = document.querySelector(`tr[data-dv-id="${dvId}"]`);
            if (dvRow) {
                const status = dvRow.getAttribute('data-dv-status');
                if (status === 'RANCA_validated') {
                    hasReceivableDVs = true;
                } else if (status === 'Regional_Director_received') {
                    hasApprovableDVs = true;
                }
            }
        });
        
        if (hasReceivableDVs) {
            bulkReceiveBtn.classList.remove('hidden');
        } else {
            bulkReceiveBtn.classList.add('hidden');
        }
        
        if (hasApprovableDVs) {
            bulkSignApproveBtn.classList.remove('hidden');
        } else {
            bulkSignApproveBtn.classList.add('hidden');
        }
    }
    
    function updateSelectedStatusInfo() {
        let receivableCount = 0;
        let approvableCount = 0;
        
        selectedDVs.forEach(dvId => {
            const dvRow = document.querySelector(`tr[data-dv-id="${dvId}"]`);
            if (dvRow) {
                const status = dvRow.getAttribute('data-dv-status');
                if (status === 'RANCA_validated') {
                    receivableCount++;
                } else if (status === 'Regional_Director_received') {
                    approvableCount++;
                }
            }
        });
        
        let infoText = '';
        if (receivableCount > 0 && approvableCount > 0) {
            infoText = `${receivableCount} to receive, ${approvableCount} to approve`;
        } else if (receivableCount > 0) {
            infoText = `${receivableCount} ready to receive`;
        } else if (approvableCount > 0) {
            infoText = `${approvableCount} ready to approve`;
        }
        
        if (statusInfo) {
            statusInfo.textContent = infoText;
            statusInfo.classList.toggle('hidden', !infoText);
        }
    }
    
    // Handle individual checkbox changes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('dv-checkbox')) {
            if (e.target.checked) {
                selectedDVs.add(e.target.value);
            } else {
                selectedDVs.delete(e.target.value);
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                }
            }
            updateBulkSectionVisibility();
        }
    });
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            const checkboxes = document.querySelectorAll('.dv-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
                if (isChecked) {
                    selectedDVs.add(checkbox.value);
                } else {
                    selectedDVs.delete(checkbox.value);
                }
            });
            updateBulkSectionVisibility();
        });
    }
    
    if (bulkReceiveBtn) {
        bulkReceiveBtn.addEventListener('click', function() {
            if (selectedDVs.size > 0) {
                showBulkActionModal('receive');
            }
        });
    }
    
    if (bulkSignApproveBtn) {
        bulkSignApproveBtn.addEventListener('click', function() {
            if (selectedDVs.size > 0) {
                showBulkActionModal('sign_approve');
            }
        });
    }
    
    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function() {
            selectedDVs.clear();
            document.querySelectorAll('.dv-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            updateBulkSectionVisibility();
        });
    }
    
    updateBulkSectionVisibility();
}

function showBulkActionModal(actionType) {
    const modal = document.getElementById('bulkActionModal');
    const modalTitle = document.getElementById('bulkActionModalTitle');
    const modalMessage = document.getElementById('bulkActionModalMessage');
    const listContainer = document.getElementById('bulkActionList');
    const confirmBtn = document.getElementById('confirmBulkActionBtn');
    
    if (!modal || !modalTitle || !modalMessage || !listContainer || !confirmBtn) return;
    
    const isReceive = actionType === 'receive';
    const isSignApprove = actionType === 'sign_approve';
    const actionText = isReceive ? 'receive' : 'sign and approve';
    const actionTextCapitalized = isReceive ? 'Receive' : 'Sign & Approve';
    
    modalTitle.textContent = `Confirm Bulk ${actionTextCapitalized}`;
    modalMessage.textContent = `You are about to ${actionText} the following DVs:`;
    
    listContainer.innerHTML = '';
    
    let actionDVs = [];
    let otherDVs = [];
    
    selectedDVs.forEach(dvId => {
        const dvRow = document.querySelector(`tr[data-dv-id="${dvId}"]`);
        if (dvRow) {
            const dvNumber = dvRow.querySelector('td:nth-child(2)')?.textContent || 'N/A';
            const payee = dvRow.querySelector('td:nth-child(3)')?.textContent || 'N/A';
            const amount = dvRow.querySelector('td:nth-child(4)')?.textContent || 'N/A';
            const status = dvRow.getAttribute('data-dv-status');
            
            const isActionable = isReceive ? 
                status === 'RANCA_validated' : 
                status === 'Regional_Director_received';
            
            const listItem = document.createElement('div');
            listItem.className = `flex justify-between items-center py-2 border-b border-gray-200 last:border-b-0 ${!isActionable ? 'opacity-50' : ''}`;
            listItem.innerHTML = `
                <div>
                    <div class="font-medium text-gray-900">${dvNumber}</div>
                    <div class="text-sm text-gray-600">${payee}</div>
                    <div class="text-xs text-gray-500 mt-1">Status: ${status.replace(/_/g, ' ')}</div>
                </div>
                <div class="text-right">
                    <div class="font-semibold text-green-600">${amount}</div>
                    ${!isActionable ? '<div class="text-xs text-red-500 mt-1">Not eligible</div>' : ''}
                </div>
            `;
            
            if (isActionable) {
                actionDVs.push({ dvId, listItem });
            } else {
                otherDVs.push({ dvId, listItem });
            }
        }
    });
    
    actionDVs.forEach(item => listContainer.appendChild(item.listItem));
    otherDVs.forEach(item => listContainer.appendChild(item.listItem));
    
    confirmBtn.textContent = `Confirm ${actionTextCapitalized}`;
    confirmBtn.onclick = function() {
        if (actionType === 'receive') {
            processBulkReceive();
        } else {
            processBulkSignApprove();
        }
    };
    
    confirmBtn.style.display = actionDVs.length > 0 ? 'block' : 'none';
    
    modal.classList.remove('hidden');
}

function closeBulkActionModal() {
    const modal = document.getElementById('bulkActionModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

async function processBulkReceive() {
    if (selectedDVs.size === 0) return;
    
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingMessage = document.getElementById('loadingMessage');
    
    if (loadingOverlay) {
        loadingOverlay.classList.remove('hidden');
        loadingMessage.textContent = 'Processing bulk receive...';
    }
    
    try {
        const dvIds = Array.from(selectedDVs);
        
        const response = await fetch('{{ route("regional.director.bulk-receive") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                dv_ids: dvIds
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showToast(`Successfully received ${result.received_count || dvIds.length} DVs`, 'success');
            closeBulkActionModal();
            refreshAllData();
        } else {
            throw new Error(result.message || 'Bulk receive failed');
        }
        
    } catch (error) {
        console.error('Error in bulk receive:', error);
        showToast('Error processing bulk receive: ' + error.message, 'error');
    } finally {
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
    }
}

async function processBulkSignApprove() {
    if (selectedDVs.size === 0) return;
    
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingMessage = document.getElementById('loadingMessage');
    
    if (loadingOverlay) {
        loadingOverlay.classList.remove('hidden');
        loadingMessage.textContent = 'Processing bulk sign & approve...';
    }
    
    try {
        const dvIds = Array.from(selectedDVs);
        
        const response = await fetch('{{ route("regional.director.bulk-sign-approve") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                dv_ids: dvIds
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showToast(`Successfully signed and approved ${result.approved_count || dvIds.length} DVs`, 'success');
            closeBulkActionModal();
            refreshAllData();
        } else {
            throw new Error(result.message || 'Bulk sign & approve failed');
        }
        
    } catch (error) {
        console.error('Error in bulk sign & approve:', error);
        showToast('Error processing bulk sign & approve: ' + error.message, 'error');
    } finally {
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
    }
}
// ====================================
// QR MODAL FUNCTIONALITY
// ====================================
let currentQRData = {
    dvId: null,
    dvNumber: null,
    qrToken: null,
    qrImageUrl: null
};

function showQRCode(dvId, dvNumber, qrToken, qrCodeData) {
    const modal = document.getElementById('qrModal');
    const qrContainer = document.getElementById('qrCodeContainer');
    const message = document.getElementById('qrModalMessage');
    const downloadBtn = document.getElementById('downloadQRBtn');
    const openScannerBtn = document.getElementById('openQRScannerBtn');
    const dvNumberElement = document.getElementById('qrDVNumber');
    const tokenDisplay = document.getElementById('qrTokenDisplay');
    const tokenContainer = document.getElementById('qrTokenContainer');
    
    if (!modal || !qrContainer || !message) return;
    
    // Reset current data
    currentQRData = { dvId, dvNumber, qrToken, qrImageUrl: null };
    
    // Set modal title and message
    if (dvNumberElement) dvNumberElement.textContent = dvNumber;
    message.textContent = `Scan this QR code to update DV status`;
    
    // Display QR token
    if (tokenDisplay && qrToken) {
        tokenDisplay.textContent = qrToken;
        tokenContainer.classList.remove('hidden');
    } else {
        tokenContainer.classList.add('hidden');
    }
    
    // Clear previous QR code
    qrContainer.innerHTML = '';
    
    if (qrCodeData) {
        // If we have pre-generated QR code data, display it
        const img = document.createElement('img');
        img.src = qrCodeData;
        img.alt = `QR Code for DV ${dvNumber}`;
        img.id = 'qrImage';
        img.className = 'w-48 h-48 border border-gray-200 rounded-lg';
        img.crossOrigin = 'anonymous'; // Important for download
        qrContainer.appendChild(img);
        
        currentQRData.qrImageUrl = qrCodeData;
        
        // Show download button
        if (downloadBtn) {
            downloadBtn.classList.remove('hidden');
            downloadBtn.onclick = function() {
                downloadQRCode(dvNumber, qrCodeData);
            };
        }
    } else if (qrToken) {
        // If we have a token but no QR image, generate one on the fly
        const qrData = JSON.stringify({
            dv_id: dvId,
            dv_number: dvNumber,
            token: qrToken,
            action_url: `${window.location.origin}/dv/qr/update-status/${qrToken}`,
            timestamp: Date.now(),
            type: 'dv_status_update'
        });
        
        // Generate QR code using a service
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrData)}&format=png&margin=10`;
        
        const img = document.createElement('img');
        img.src = qrUrl;
        img.alt = `QR Code for DV ${dvNumber}`;
        img.id = 'qrImage';
        img.className = 'w-48 h-48 border border-gray-200 rounded-lg';
        img.crossOrigin = 'anonymous';
        img.onload = function() {
            currentQRData.qrImageUrl = qrUrl;
            
            // Show download button after image loads
            if (downloadBtn) {
                downloadBtn.classList.remove('hidden');
                downloadBtn.onclick = function() {
                    downloadQRCode(dvNumber, qrUrl);
                };
            }
        };
        qrContainer.appendChild(img);
        
        // Show download button immediately (will work if image is cached)
        if (downloadBtn) {
            downloadBtn.classList.remove('hidden');
            downloadBtn.onclick = function() {
                downloadQRCode(dvNumber, qrUrl);
            };
        }
    } else {
        // No QR data available
        qrContainer.innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <svg class="w-16 h-16 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p>QR Code not available</p>
            </div>
        `;
        if (downloadBtn) downloadBtn.classList.add('hidden');
    }
    
    // Setup scanner button
    if (openScannerBtn) {
        openScannerBtn.classList.remove('hidden');
        openScannerBtn.onclick = function() {
            window.location.href = "{{ route('regional.director.scanner') }}";
        };
    }
    
    // Show modal
    modal.classList.remove('hidden');
}

function downloadQRCode(dvNumber, imageUrl) {
    try {
        // Create a temporary anchor element
        const link = document.createElement('a');
        link.href = imageUrl;
        link.download = `QR_${dvNumber}_${Date.now()}.png`;
        link.style.display = 'none';
        
        // Append to body, click, and remove
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showToast(`QR code for ${dvNumber} downloaded successfully`, 'success');
        
    } catch (error) {
        console.error('Error downloading QR code:', error);
        showToast('Error downloading QR code. Please try again.', 'error');
        
        // Fallback: open in new tab
        window.open(imageUrl, '_blank');
    }
}

function copyQrToken() {
    const tokenDisplay = document.getElementById('qrTokenDisplay');
    if (!tokenDisplay || !currentQRData.qrToken) return;
    
    // Create a temporary textarea to copy from
    const textarea = document.createElement('textarea');
    textarea.value = currentQRData.qrToken;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        const successful = document.execCommand('copy');
        document.body.removeChild(textarea);
        
        if (successful) {
            showCopyToast();
        } else {
            showToast('Failed to copy token', 'error');
        }
    } catch (err) {
        document.body.removeChild(textarea);
        showToast('Failed to copy token', 'error');
    }
}

function showCopyToast() {
    const toast = document.getElementById('copyToast');
    if (!toast) return;
    
    toast.classList.remove('hidden');
    toast.classList.add('block');
    
    setTimeout(() => {
        toast.classList.remove('block');
        toast.classList.add('hidden');
    }, 2000);
}

function closeQRModal() {
    const modal = document.getElementById('qrModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    // Reset current data
    currentQRData = { dvId: null, dvNumber: null, qrToken: null, qrImageUrl: null };
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('qrModal');
    if (e.target === modal) {
        closeQRModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeQRModal();
    }
});
// ====================================
// MAIN APPLICATION CODE
// ====================================

// Handle new DVs from RANCA
function handleNewDvValidated(data) {
    console.log('📥 New DV received from RANCA:', data);
    
    // Just refresh the whole table for now (simple solution)
    fetchDVs();
    updateStatistics();
    showToast('New DV available from RANCA', 'success');
}

// Format numbers nicely
function formatNumber(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Update a DV row when status changes
function updateDvRowInTable(dvId, newStatus) {
    console.log('Updating DV:', dvId, 'to status:', newStatus);
    // Simple solution: just refresh the table
     updateStatistics();
    fetchDVs();
}


document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.getElementById('dv-table-container');
    const searchInput = document.getElementById('searchInput');
    const connectionStatus = document.getElementById('connection-status');
    const currentUserId = {{ auth()->id() }};
    let allDVs = [];
    let searchTerm = "";
    let searchTimeout;
    let isFetching = false;
    let abortController = null;

    const statusColors = {
        'RANCA_validated': ['bg-yellow-100 text-yellow-800', 'bg-yellow-400', 'Pending'],
        'Regional_Director_received': ['bg-blue-100 text-blue-800', 'bg-blue-400', 'Received - View/Process'],
        'Regional_Director_signed_approved': ['bg-green-100 text-green-800', 'bg-green-400', 'Signed & Approved'],
        'completed': ['bg-gray-100 text-gray-800', 'bg-gray-400', 'Completed']
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

    // Subscribe to Regional Director channel
   const regionalDirectorChannel = pusher.subscribe('regional-director-dashboard');

    // Consolidated event handler
    function handlePusherUpdate(eventType, data) {
        console.log(`${eventType}:`, data);
        
        switch(eventType) {
            case 'new-dv-available':
                // ✅ FIXED: Actually handle the new DV data
                if (data.dv_data) {
                      updateStatistics();
                    handleNewDvValidated(data);
                } else {
                    // Fallback: just refresh if no detailed data
                    if (!isFetching) {
                         updateStatistics();
                        fetchDVs();
                    }
                }
                break;
                
            case 'dv-status-updated':
                if (data.action_performer_id === currentUserId) {
                    showToast(`DV ${data.dv_number} status updated to ${data.new_status.replace(/_/g, ' ')}`, 'success');
                }
                // Always update the table for status changes
                if (!isFetching) {
                    fetchDVs();
                }
                break;
                
            case 'regional-director-received':
                if (data.action_performer_id === currentUserId) {
                    showToast(`DV ${data.dv_number} received successfully`, 'success');
                }
                if (!isFetching) {
                    fetchDVs();
                }
                break;
                
            case 'regional-director-approved':
                if (data.action_performer_id === currentUserId) {
                    showToast(`DV ${data.dv_number} signed and approved successfully`, 'success');
                }
                if (!isFetching) {
                    fetchDVs();
                }
                break;

            case 'bulk-receive-completed':
                showToast(`Bulk receive completed: ${data.successful_count} DVs received`, 'success');
                refreshAllData();
                break;

            case 'bulk-sign-approve-completed':
                showToast(`Bulk sign & approve completed: ${data.successful_count} DVs approved`, 'success');
                refreshAllData();
                break;

            // ✅ ADD THIS: Handle the RANCA validation event specifically
            case 'dv-validated-by-accounting':
                console.log('DV validated by accounting:', data);
                if (data.dv_data) {
                    handleNewDvValidated(data);
                } else {
                    // Create a compatible data structure from the available data
                    const dvData = {
                        dv_id: data.dv_id,
                        dv_number: data.dv_number,
                        dv_data: {
                            id: data.dv_id,
                            dv_number: data.dv_number,
                            amount: data.amount,
                            status: 'RANCA_validated',
                            focal_person: {
                                fname: data.payee ? data.payee.split(' ')[0] : 'Unknown',
                                lname: data.payee ? data.payee.split(' ').slice(1).join(' ') : 'Payee'
                            },
                            program: {
                                prcode: data.program || 'N/A',
                                description: data.program || 'N/A'
                            },
                            updated_at: data.timestamp || new Date().toISOString()
                        }
                    };
                    handleNewDvValidated(dvData);
                }
                break;
        }
        
        // Refresh statistics for all events
        updateStatistics();
    }

    // Bind all events to single handler
    ['new-dv-available', 'dv-status-updated', 'regional-director-received', 'regional-director-approved', 
    'bulk-receive-completed', 'bulk-sign-approve-completed', 'dv-validated-by-accounting'].forEach(event => {
        regionalDirectorChannel.bind(event, (data) => handlePusherUpdate(event, data));
    });

    // Refresh all data function
    window.refreshAllData = function() {
        // Show loading state
        if (tableContainer) {
            tableContainer.innerHTML = `
                <div class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-gray-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">Refreshing DVs...</p>
                </div>
            `;
        }
        
        // Clear search and cached data
        if (searchInput) searchInput.value = '';
        
        allDVs = [];
        searchTerm = "";
        
        // Refresh all data
        fetchDVs();
        updateStatistics();
    }

   // Optimized table rendering
function renderTable(dvs) {
    if (!dvs.length) {
        tableContainer.innerHTML = `
            <div class="text-center py-16">
                <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No DVs found</h3>
                <p class="text-gray-500">All DVs have been processed or try adjusting your search filter.</p>
                <a href="{{ route('regional.director.scanner') }}" 
                   class="inline-flex items-center px-4 py-2 mt-4 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    Open QR Scanner
                </a>
            </div>
        `;
        return;
    }

    const rows = dvs.map(dv => {
        const [bgClass, dotClass, label] = statusColors[dv.status] || ['bg-gray-100 text-gray-800','bg-gray-400', dv.status];
        const payeeName = dv.focal_person ? `${dv.focal_person.fname} ${dv.focal_person.lname}` : 'Unknown Payee';
        const programInfo = dv.program ? `${dv.program.prcode} - ${dv.program.description}` : 'N/A';
        
        // Determine action button text and availability
        let actionButton = '';
        if (dv.status === 'RANCA_validated' || dv.status === 'Regional_Director_received') {
            const actionText = dv.status === 'RANCA_validated' ? 'Receive DV' : 'Sign & Approve';
            const actionUrl = dv.qr_code_token ? `/dv/qr/update-status/${dv.qr_code_token}` : '#';
            
            actionButton = `
                <div class="flex space-x-2">
                    <a href="${actionUrl}" 
                        class="inline-flex items-center px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-md transition-colors duration-200">
                        ${actionText}
                    </a>
                    <button onclick="showQRCode('${dv.id}', '${dv.dv_number}', '${dv.qr_code_token}', '${dv.qr_code_data}')" 
                        class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md transition-colors duration-200 relative group"
                        title="Show QR Code - Token: ${dv.qr_code_token || 'N/A'}">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        QR
                        <!-- Tooltip -->
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap z-10">
                            Token: ${dv.qr_code_token ? dv.qr_code_token.substring(0, 8) + '...' : 'N/A'}
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                        </div>
                    </button>
                </div>
            `;
        } else {
            actionButton = `<span class="text-gray-400 text-xs">Completed</span>`;
        }
        
        return `
            <tr class="hover:bg-gray-50 transition-colors duration-150" data-dv-id="${dv.id}" data-dv-status="${dv.status}">
                <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                    <input type="checkbox" value="${dv.id}" class="dv-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">  
                </td>
                <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                    ${dv.dv_number || '—'}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">${payeeName}</td>
                <td class="px-6 py-4 text-sm font-semibold text-green-600">₱${parseFloat(dv.amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-6 py-4 text-sm text-gray-600">${programInfo}</td>
                <td class="px-6 py-4 text-sm text-gray-600">${dv.particulars || '—'}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${bgClass}">
                        <span class="w-2 h-2 mr-2 rounded-full ${dotClass}"></span>
                        ${label}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">${formatDate(dv.updated_at)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    ${actionButton}
                </td>
            </tr>`;
    }).join('');

    tableContainer.innerHTML = `
        <div class="overflow-x-auto w-full shadow-sm border border-gray-200 rounded-xl">
            <table class="w-full table-auto divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">DV Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Particulars</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Updated</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    ${rows}
                </tbody>
            </table>
        </div>
    `;

    // Initialize bulk actions after table is rendered
    initializeBulkActions();
}

    // Format date function
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-PH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Update statistics display
    function updateStatisticsDisplay(stats) {
        if (stats.total_pending !== undefined) {
            document.getElementById('stat-total-pending').textContent = stats.total_pending;
        }
        if (stats.awaiting_receipt !== undefined) {
            document.getElementById('stat-awaiting-receipt').textContent = stats.awaiting_receipt;
        }
        if (stats.awaiting_approval !== undefined) {
            document.getElementById('stat-awaiting-approval').textContent = stats.awaiting_approval;
        }
    }

    // Fetch statistics
    async function updateStatistics() {
        try {
            const response = await fetch('{{ route("regional.director.stats") }}');
            const result = await response.json();
            
            if (result.success) {
                updateStatisticsDisplay(result.data);
            }
        } catch (error) {
            console.error('Error updating statistics:', error);
        }
    }

    // Fetch DVs with abort controller
    async function fetchDVs() {
        if (isFetching && abortController) {
            abortController.abort();
        }
        
        isFetching = true;
        abortController = new AbortController();
        
        try {
            const url = "{{ route('regional.director.dvs.fetch') }}?search=" + encodeURIComponent(searchTerm);
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
            
            allDVs = data;
            renderTable(allDVs);
        } catch (err) {
            if (err.name === 'AbortError') {
                console.log('Fetch aborted');
                return;
            }
            console.error('Failed to fetch DVs:', err);
            tableContainer.innerHTML = `
                <div class="text-center py-16 text-red-500">
                    <p class="font-medium">Failed to load DVs.</p>
                    <p class="text-sm mt-2">Error: ${err.message}</p>
                    <button onclick="fetchDVs()" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
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
            fetchDVs();
        }, 300);
    });

    // Make functions globally accessible
    window.fetchDVs = fetchDVs;
    window.showToast = showToast;
    window.dismissToast = dismissToast;
    window.refreshAllData = refreshAllData;

    // Initial fetches
    fetchDVs();
    updateStatistics();
});
</script>
@endsection