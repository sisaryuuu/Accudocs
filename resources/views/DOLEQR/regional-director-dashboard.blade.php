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

        <!-- Bulk Actions Section -->
        <div id="bulkActionsSection" class="hidden bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="selectAllDVs" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="selectAllDVs" class="ml-3 text-sm font-medium text-gray-700">Select All</label>
                    </div>
                    <div class="text-sm text-gray-600">
                        <span id="selectedCount">0</span> DVs selected
                    </div>
                    <div id="selectedStatusInfo" class="text-sm text-gray-500 hidden">
                        <!-- Will show status breakdown of selected DVs -->
                    </div>
                </div>
                <div class="flex space-x-3">
                    <!-- Bulk Receive Button (for RANCA_validated DVs) -->
                    <button id="bulkReceiveBtn" class="hidden inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200 ease-in-out">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Bulk Receive
                    </button>
                    
                    <!-- Bulk Sign & Approve Button (for Regional_Director_received DVs) -->
                    <button id="bulkSignApproveBtn" class="hidden inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200 ease-in-out">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        Bulk Sign & Approve
                    </button>
                    
                    <button id="clearSelectionBtn" class="inline-flex items-center px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg shadow-sm transition-colors duration-200 ease-in-out">
                        Clear Selection
                    </button>
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
                                <th class="px-6 py-5 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider bg-gray-100 border-r border-gray-200 w-12">
                                    <!-- Checkbox column -->
                                </th>
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
                               <td class="px-6 py-6 whitespace-nowrap text-center">
                                    @if(in_array($dv->status, ['RANCA_validated', 'Regional_Director_received']))
                                        <!-- Show checkbox for both receivable and approvable DVs -->
                                        <input type="checkbox" name="dvCheckbox" value="{{ $dv->id }}" 
                                            class="dv-checkbox w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    @else
                                        <!-- Show checkmark icon for completed DVs -->
                                        <div class="w-5 h-5 flex items-center justify-center text-green-500">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @endif
                                </td>
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
                @if($dv->qr_code_data)
                    <img 
                        src="{{ $dv->qr_code_data }}" 
                        width="80" 
                        height="80"
                        alt="QR Code for DV {{ $dv->qr_code_token }}" 
                        class="rounded-xl border-2 border-gray-200 shadow-md hover:shadow-xl transition-all duration-300 cursor-pointer hover:scale-105 hover:border-blue-300"
                        onclick="showQRModal({{ $dv->id }})"
                        loading="lazy"
                    >
                @else
                    <img 
                        src="{{ route('dv.generate-qr', $dv->id) }}?v={{ time() }}" 
                        width="80" 
                        height="80"
                        alt="QR Code for DV {{ $dv->qrCodeToken }}" 
                        class="rounded-xl border-2 border-gray-200 shadow-md hover:shadow-xl transition-all duration-300 cursor-pointer hover:scale-105 hover:border-blue-300"
                        onclick="showQRModal({{ $dv->id }})"
                        loading="lazy"
                    >
                @endif
            </div>
            
            <div class="text-center">
                @if($dv->qr_status === 'active')
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-300 shadow-sm">
                        QR Active
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-300 shadow-sm">
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
<div id="qrModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900">QR Code Details</h3>
            <button onclick="closeQRModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div id="qrContent" class="text-center"></div>
        <button id="downloadQRBtn" onclick="downloadQR()" class="hidden mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg transition-colors duration-200">
            Download QR Code
        </button>
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

<!-- Add Pusher Script -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<script>
// Global variables
let currentQRData = null;
let pusher = null;
let rdChannel = null;
let selectedDVs = new Set();

// ========== PUSHER REAL-TIME UPDATES ==========
function initializePusher() {
    try {
        if (typeof Pusher === 'undefined') {
            console.error('[Pusher] Pusher library not loaded. Please include pusher.min.js');
            updateRealtimeStatus(false);
            return;
        }
        
        console.log('[Pusher] Initializing...');
        
        pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
            cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
            encrypted: true,
            enabledTransports: ['ws', 'wss']
        });

        console.log('[Pusher] ✓ Instance created with Laravel config');

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

        rdChannel = pusher.subscribe('regional-director-dashboard');
        
        console.log('[Channel] Subscribing to regional-director-dashboard');
        
        rdChannel.bind('pusher:subscription_succeeded', function() {
            console.log('[Channel] ✓ Subscription succeeded');
        });

        rdChannel.bind('pusher:subscription_error', function(status) {
            console.error('[Channel] Subscription error:', status);
        });
        
        rdChannel.bind('dv-status-updated', function(data) {
            console.log('[Event] DV Status Updated:', data);
            handleDvStatusUpdate(data);
        });

        rdChannel.bind('new-dv-added', function(data) {
            console.log('[Event] New DV Added:', data);
            handleNewDvAdded(data);
        });

        rdChannel.bind('dv-removed', function(data) {
            console.log('[Event] DV Removed:', data);
            handleDvRemoved(data);
        });

        rdChannel.bind('stats-updated', function(data) {
            console.log('[Event] Stats Updated:', data);
            updateStatisticsDisplay(data.stats);
        });

        rdChannel.bind('bulk-receive-completed', function(data) {
            console.log('[Event] Bulk Receive Completed:', data);
            handleBulkReceiveCompleted(data);
        });

        rdChannel.bind('bulk-sign-approve-completed', function(data) {
            console.log('[Event] Bulk Sign & Approve Completed:', data);
            handleBulkSignApproveCompleted(data);
        });

    } catch (error) {
        console.error('[Pusher] Error initializing:', error);
        updateRealtimeStatus(false);
    }
}

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

// ========== PUSHER EVENT HANDLERS ==========
function handleDvStatusUpdate(data) {
    console.log('[Handler] Processing status update for DV:', data.dv_id);
    
    const dvRow = document.querySelector(`tr[data-dv-id="${data.dv_id}"]`);
    
    if (dvRow) {
        updateDvRowInTable(data.dv_id, data.new_status);
        
        if (data.new_status === 'Regional_Director_received') {
            removeCheckboxForReceivedDV(data.dv_id);
        }
        
        if (!data.is_bulk_action) {
            showToast(`DV ${data.dv_number} status updated to ${data.new_status.replace(/_/g, ' ')}`, 'success');
        }
    }
    
    updateLastUpdatedTime();
    updateTableHeader();
}

function handleNewDvAdded(data) {
    console.log('[Handler] New DV added:', data.dv.dv_number);
    
    const existingRow = document.querySelector(`tr[data-dv-id="${data.dv.id}"]`);
    if (existingRow) {
        console.log('[Handler] DV already exists in table, skipping');
        return;
    }
    
    showToast(`New DV ${data.dv.dv_number} ready for review`, 'info');
    updateLastUpdatedTime();
}

function handleDvRemoved(data) {
    console.log('[Handler] DV removed:', data.dv_id);
    
    const dvRow = document.querySelector(`tr[data-dv-id="${data.dv_id}"]`);
    if (dvRow) {
        if (data.reason === 'bulk_received') {
            console.log('DV marked as received via bulk action, keeping row');
        } else {
            dvRow.style.transition = 'all 0.5s ease-out';
            dvRow.style.opacity = '0';
            dvRow.style.transform = 'translateX(-100%)';
            
            setTimeout(() => {
                dvRow.remove();
                updateStatistics();
                checkEmptyState();
                updateTableHeader();
            }, 500);
            
            showToast(`DV ${data.dv_number} ${data.reason === 'approved' ? 'approved and forwarded' : 'removed'}`, 'info');
        }
    }
    
    updateLastUpdatedTime();
}

function handleBulkReceiveCompleted(data) {
    console.log('[Handler] Bulk receive completed:', data);
    
    data.successful_dvs.forEach(dv => {
        updateDvRowInTable(dv.dv_id, dv.new_status);
        removeCheckboxForReceivedDV(dv.dv_id);
    });
    
    updateStatistics();
    
    let message = `Bulk receive completed: ${data.successful_count} DVs received`;
    if (data.failed_count > 0) {
        message += `, ${data.failed_count} failed`;
    }
    showToast(message, data.failed_count > 0 ? 'warning' : 'success');
    
    clearBulkSelection();
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.classList.add('hidden');
    }
    closeBulkActionModal();
    
    updateLastUpdatedTime();
    updateTableHeader();
}

function handleBulkSignApproveCompleted(data) {
    console.log('[Handler] Bulk sign & approve completed:', data);
    
    data.successful_dvs.forEach(dv => {
        // Remove the DV row from the table since it's completed and forwarded to cashier
        const dvRow = document.querySelector(`tr[data-dv-id="${dv.dv_id}"]`);
        if (dvRow) {
            dvRow.style.transition = 'all 0.5s ease-out';
            dvRow.style.opacity = '0';
            dvRow.style.transform = 'translateX(-100%)';
            
            setTimeout(() => {
                dvRow.remove();
                updateStatistics();
                checkEmptyState();
                updateTableHeader();
            }, 500);
        }
    });
    
    updateStatistics();
    
    let message = `Bulk sign & approve completed: ${data.successful_count} DVs approved`;
    if (data.failed_count > 0) {
        message += `, ${data.failed_count} failed`;
    }
    showToast(message, data.failed_count > 0 ? 'warning' : 'success');
    
    clearBulkSelection();
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.classList.add('hidden');
    }
    closeBulkActionModal();
    
    updateLastUpdatedTime();
    updateTableHeader();
}

// ========== BULK ACTIONS FUNCTIONS ==========
function initializeBulkActions() {
    const bulkSection = document.getElementById('bulkActionsSection');
    const selectAllCheckbox = document.getElementById('selectAllDVs');
    const dvCheckboxes = document.querySelectorAll('.dv-checkbox');
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
        
        // Show bulk receive button if any selected DVs are receivable
        if (hasReceivableDVs) {
            bulkReceiveBtn.classList.remove('hidden');
        } else {
            bulkReceiveBtn.classList.add('hidden');
        }
        
        // Show bulk sign & approve button if any selected DVs are approvable
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
    
    dvCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                selectedDVs.add(this.value);
            } else {
                selectedDVs.delete(this.value);
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                }
            }
            updateBulkSectionVisibility();
        });
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
            const dvNumber = dvRow.querySelector('td:nth-child(2) .text-base')?.textContent || 'N/A';
            const payee = dvRow.querySelector('td:nth-child(3) .text-base')?.textContent || 'N/A';
            const amount = dvRow.querySelector('td:nth-child(4) .text-xl')?.textContent || 'N/A';
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
    
    // Show actionable DVs first
    actionDVs.forEach(item => listContainer.appendChild(item.listItem));
    otherDVs.forEach(item => listContainer.appendChild(item.listItem));
    
    // Update confirm button
    confirmBtn.textContent = `Confirm ${actionTextCapitalized}`;
    confirmBtn.onclick = function() {
        if (actionType === 'receive') {
            processBulkReceive();
        } else {
            processBulkSignApprove();
        }
    };
    
    // Show/hide confirm button based on whether there are actionable DVs
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
            loadingMessage.textContent = 'Waiting for real-time updates...';
            console.log('Bulk receive initiated, waiting for Pusher updates');
        } else {
            throw new Error(result.message || 'Bulk receive failed');
        }
        
    } catch (error) {
        console.error('Error in bulk receive:', error);
        showToast('Error processing bulk receive: ' + error.message, 'error');
        
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
        closeBulkActionModal();
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
            loadingMessage.textContent = 'Waiting for real-time updates...';
            console.log('Bulk sign & approve initiated, waiting for Pusher updates');
        } else {
            throw new Error(result.message || 'Bulk sign & approve failed');
        }
        
    } catch (error) {
        console.error('Error in bulk sign & approve:', error);
        showToast('Error processing bulk sign & approve: ' + error.message, 'error');
        
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
        closeBulkActionModal();
    }
}

function removeCheckboxForReceivedDV(dvId) {
    const dvRow = document.querySelector(`tr[data-dv-id="${dvId}"]`);
    if (!dvRow) return;
    
    const checkboxCell = dvRow.querySelector('td:nth-child(1)');
    if (checkboxCell) {
        checkboxCell.innerHTML = `
            <div class="w-5 h-5 flex items-center justify-center text-green-500">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            </div>
        `;
    }
    
    if (selectedDVs.has(dvId.toString())) {
        selectedDVs.delete(dvId.toString());
        updateBulkSectionVisibility();
    }
}

function updateDvRowInTable(dvId, newStatus) {
    const dvRow = document.querySelector(`tr[data-dv-id="${dvId}"]`);
    if (!dvRow) return;
    
    dvRow.setAttribute('data-dv-status', newStatus);
    
    if (newStatus === 'Regional_Director_received') {
        removeCheckboxForReceivedDV(dvId);
    }
    
    const statusCell = dvRow.querySelector('td:nth-child(7)');
    if (statusCell) {
        const isAwaitingApproval = newStatus === 'Regional_Director_received';
        statusCell.innerHTML = `
            <div class="flex flex-col space-y-2">
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium ${
                    isAwaitingApproval ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : 'bg-orange-100 text-orange-800 border border-orange-200'
                }">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ${newStatus.replace(/_/g, ' ')}
                </span>
                <div class="text-xs text-gray-500">
                    ${formatDateTime(new Date())}
                </div>
            </div>
        `;
    }
    
    const actionButton = dvRow.querySelector('td:nth-child(9) a');
    if (actionButton) {
        const buttonText = newStatus === 'Regional_Director_received' ? 'Sign & Approve' : 'Receive DV';
        actionButton.innerHTML = `
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            ${buttonText}
        `;
    }
    
    dvRow.style.transition = 'all 0.3s ease';
    dvRow.style.backgroundColor = '#dbeafe';
    
    setTimeout(() => {
        dvRow.style.backgroundColor = '';
    }, 1000);
}

function clearBulkSelection() {
    selectedDVs.clear();
    document.querySelectorAll('.dv-checkbox').forEach(cb => {
        cb.checked = false;
    });
    const selectAll = document.getElementById('selectAllDVs');
    if (selectAll) selectAll.checked = false;
    document.getElementById('bulkActionsSection').classList.add('hidden');
}

function updateTableHeader() {
    const selectAllHeader = document.querySelector('thead th:nth-child(1)');
    if (!selectAllHeader) return;
    
    const selectableDVs = document.querySelectorAll('.dv-checkbox');
    const receivedDVs = document.querySelectorAll('tr[data-dv-status="Regional_Director_received"]');
    
    if (selectableDVs.length === 0 && receivedDVs.length > 0) {
        selectAllHeader.innerHTML = `
            <div class="flex items-center justify-center">
                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            </div>
        `;
    } else {
        selectAllHeader.innerHTML = `
            <input type="checkbox" id="selectAllDVs" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
        `;
        initializeSelectAllCheckbox();
    }
}

function initializeSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById('selectAllDVs');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            const dvCheckboxes = document.querySelectorAll('.dv-checkbox');
            dvCheckboxes.forEach(checkbox => {
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
}

// ========== QR CODE FUNCTIONS ==========
function showQRModal(dvId) {
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
        const dvRow = document.querySelector(`tr[data-dv-id="${dvId}"]`);
        if (!dvRow) {
            throw new Error('DV row not found');
        }
        
        const qrImage = dvRow.querySelector('td:nth-child(8) img');
        if (!qrImage || !qrImage.src) {
            throw new Error('QR code not found in table');
        }
        
        const qrCodeSrc = qrImage.src;
        const qrCodeToken = qrImage.getAttribute('alt')?.replace('QR Code for DV ', '') || 'Unknown QR Code';
        const displayToken = qrCodeToken.length > 30 ? qrCodeToken.substring(0, 30) + '...' : qrCodeToken;
        
        content.innerHTML = `
            <div class="space-y-4">
                <div class="text-center">
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">QR Token</h4>
                    <div class="bg-gray-100 p-3 rounded-lg border border-gray-300 max-w-full overflow-hidden">
                        <p class="text-sm font-mono text-gray-700 break-all" title="${qrCodeToken}">
                            ${displayToken}
                        </p>
                    </div>
                    <button onclick="copyQrToken('${qrCodeToken}')" 
                            class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium underline transition-colors">
                        Copy Full Token
                    </button>
                </div>
                <div class="bg-white p-6 rounded-xl border-2 border-blue-200 shadow-inner flex justify-center">
                    <img src="${qrCodeSrc}" alt="QR Code for DV ${qrCodeToken}" class="w-64 h-64 mx-auto">
                </div>
                <div class="text-sm text-gray-600 text-center">
                    <p>Scan this QR code to process the disbursement voucher</p>
                    <p class="text-xs text-gray-500 mt-1">Token length: ${qrCodeToken.length} characters</p>
                </div>
            </div>
        `;
        
        currentQRData = { qrUrl: qrCodeSrc, dvId, qrCodeToken };
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
                <p class="text-gray-500 text-sm mt-2">The QR code is not available for this DV.</p>
            </div>
        `;
    }
}

function copyQrToken(token) {
    navigator.clipboard.writeText(token).then(function() {
        showToast('QR token copied to clipboard!', 'success');
    }).catch(function(err) {
        console.error('Failed to copy token: ', err);
        showToast('Failed to copy token', 'error');
    });
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

function showDvDetails(dvId) {
    alert(`Viewing details for DV ID: ${dvId}\n\nThis would typically show detailed information about the disbursement voucher.`);
}

// ========== SEARCH FUNCTIONALITY ==========
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

// ========== STATISTICS FUNCTIONS ==========
async function updateStatistics() {
    try {
        const response = await fetch('{{ route("regional.director.stats") }}');
        const stats = await response.json();
        
        if (stats.success) {
            updateStatisticsDisplay(stats.data);
        }
    } catch (error) {
        console.error('[Error] updating statistics:', error);
    }
}

function updateStatisticsDisplay(stats) {
    const totalPendingEl = document.getElementById('statTotalPending');
    if (totalPendingEl) {
        totalPendingEl.textContent = stats.total_pending;
    }
    
    const awaitingReceiptEl = document.getElementById('statAwaitingReceipt');
    if (awaitingReceiptEl) {
        awaitingReceiptEl.textContent = stats.awaiting_receipt;
    }
    
    const awaitingApprovalEl = document.getElementById('statAwaitingApproval');
    if (awaitingApprovalEl) {
        awaitingApprovalEl.textContent = stats.awaiting_approval;
    }
    
    const dvCountEl = document.getElementById('dvCount');
    if (dvCountEl) {
        dvCountEl.textContent = stats.total_pending;
    }
}

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

// ========== MODAL HANDLERS ==========
function initializeModalHandlers() {
    const qrModal = document.getElementById('qrModal');
    if (qrModal) {
        qrModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeQRModal();
            }
        });
    }
    
    const bulkActionModal = document.getElementById('bulkActionModal');
    if (bulkActionModal) {
        bulkActionModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeBulkActionModal();
            }
        });
    }
}

// ========== TOAST NOTIFICATIONS ==========
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

// ========== UTILITY FUNCTIONS ==========
function formatNumber(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

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

// ========== EVENT LISTENERS ==========
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const qrModal = document.getElementById('qrModal');
        if (qrModal && !qrModal.classList.contains('hidden')) {
            closeQRModal();
        }
        
        const bulkActionModal = document.getElementById('bulkActionModal');
        if (bulkActionModal && !bulkActionModal.classList.contains('hidden')) {
            closeBulkActionModal();
        }
    }
});

document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        window.location.href = '/regional.director/scanner';
    }
});

// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing dashboard...');
    
    updateTableHeader();
    
    if (typeof Pusher !== 'undefined') {
        initializePusher();
    } else {
        console.warn('Pusher not available, real-time features disabled');
        const statusElement = document.getElementById('realtimeStatus');
        if (statusElement) {
            statusElement.innerHTML = `
                <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                <span class="text-sm text-yellow-700 font-medium">Real-time Disabled</span>
            `;
            statusElement.className = 'flex items-center space-x-2 px-4 py-2 bg-yellow-50 border border-yellow-200 rounded-lg';
        }
        
        setInterval(updateStatistics, 30000);
    }
    
    initializeSearch();
    initializeModalHandlers();
    initializeBulkActions();
    
    console.log('Dashboard initialization complete');
});

window.addEventListener('beforeunload', function() {
    if (pusher) {
        pusher.disconnect();
        console.log('[Pusher] Disconnected');
    }
});
</script>
@endsection