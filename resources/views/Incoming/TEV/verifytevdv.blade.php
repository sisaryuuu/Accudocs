@extends('layouts.app')

@section('title', 'Verify DV Request - TEV')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Section - Incoming Form -->
        <div class="lg:col-span-2 bg-white shadow rounded-xl p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Incoming - TEV</h1>

            <form action="{{ route('Incoming.TEV.dvrequests.verify', $dvRequest->id) }}" method="POST" class="space-y-6" id="verifyForm">
                @csrf

                {{-- Mode of Payment --}}
                <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mode of Payment</label>
                    @if($dvRequest->modeOfPayments && $dvRequest->modeOfPayments->count() > 0)
                        <ul class="space-y-2">
                            @foreach($dvRequest->modeOfPayments as $mop)
                                <li class="flex items-center p-3 rounded-lg border
                                    @if($loop->first) border-blue-500 bg-blue-50 @else border-gray-200 bg-white @endif">
                                    <input 
                                        type="radio" 
                                        name="selected_mop" 
                                        value="{{ $mop->id }}" 
                                        class="h-4 w-4 text-blue-600 border-gray-300 mr-2"
                                        @if($loop->first) checked @endif
                                        disabled
                                    >
                                    <span class="text-sm font-medium text-gray-900">{{ $mop->mopname ?? 'N/A' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-400">N/A</p>
                    @endif
                </div>

                {{-- General Info --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Control No.</label>
                        <input type="text" value="{{ $dvRequest->accounting_control_number ?? '' }}" readonly
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name of Payee</label>
                        <input type="text" value="{{ $dvRequest->focalPerson->fname ?? '' }} {{ $dvRequest->focalPerson->lname ?? '' }}" readonly
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Program</label>
                        <input type="text" value="{{ $dvRequest->program->prcode ?? 'N/A'}}" readonly
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                </div>

                {{-- Responsibility Center --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Responsibility Center</label>
                    <input type="text" value="{{ $dvRequest->respocenter->rccode ?? '' }} - {{ $dvRequest->respocenter->description ?? '' }}" readonly
                           class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                </div>

                {{-- Particulars --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Particulars</label>
                    <textarea rows="2" readonly class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">{{ $dvRequest->particulars ?? '' }}</textarea>
                </div>

                <!-- TEV Attachments Checklist -->
                <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            📋 TEV Attachments Verification
                        </h3>
                        
                        <!-- Bulk Action Buttons -->
                        @if($dvRequest->status === 'Incoming-TEV_received' && $tevSelectedModality && $tevAttachments && $tevAttachments->count() > 0)
                            <div class="flex items-center space-x-3" id="bulk-actions">
                                <button type="button" 
                                        onclick="markAllTEVAsPresent()"
                                        class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    All Present
                                </button>
                                <button type="button" 
                                        onclick="markAllTEVAsNA()"
                                        class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    All N/A
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Modality Selection -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select TEV Modality <span class="text-red-500">*</span>
                        </label>
                        <select name="tev_modality" 
                                id="tev_modality_select"
                                required 
                                {{ $dvRequest->status !== 'Incoming-TEV_received' ? 'disabled' : '' }}
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:ring focus:ring-blue-200 text-sm"
                                onchange="handleModalityChange()">
                            <option value="">-- Select Modality --</option>
                            <option value="Cash Advance for Local Travel" {{ $tevSelectedModality == 'Cash Advance for Local Travel' ? 'selected' : '' }}>
                                Cash Advance for Local Travel
                            </option>
                            <option value="Liquidation of Cash Advances" {{ $tevSelectedModality == 'Liquidation of Cash Advances' ? 'selected' : '' }}>
                                Liquidation of Cash Advances
                            </option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Select the TEV modality to verify required attachments</p>
                    </div>

                    <!-- Dynamic Attachments Container -->
                    <div id="tev-attachments-container">
                        @if($tevSelectedModality && $tevAttachments && $tevAttachments->count() > 0)
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                                <p class="text-sm text-blue-800">
                                    <strong>Verification Responsibility:</strong> 
                                    <span class="font-semibold">Incoming-TEV</span> verifies all physical TEV attachments.
                                    Found {{ $tevAttachments->count() }} required attachments for this modality.
                                </p>
                            </div>
                    
                            <div class="space-y-3">
                                @foreach($tevAttachments as $attachment)
                                    @php
                                        // Determine the current status
                                        $isPresent = $attachment->is_present ?? null;
                                        
                                        // Set color classes based on status
                                        if ($isPresent === true || $isPresent === 1) {
                                            $statusClass = 'bg-green-50 border-green-200';
                                            $borderClass = 'border-green-300 bg-green-50';
                                            $textClass = 'text-green-600';
                                            $helperText = '✓ This document is complete and present.';
                                        } elseif ($isPresent === false || $isPresent === 0) {
                                            $statusClass = 'bg-red-50 border-red-200';
                                            $borderClass = 'border-red-300 bg-red-50';
                                            $textClass = 'text-gray-500';
                                            $helperText = 'Describe what information or entries are missing from this document.';
                                        } else {
                                            // Default: N/A
                                            $statusClass = 'bg-gray-50 border-gray-200';
                                            $borderClass = 'border-gray-300 bg-gray-50';
                                            $textClass = 'text-gray-500';
                                            $helperText = 'Explain why this document is not applicable for this request.';
                                        }
                                        
                                        // Set initial remarks text
                                        $remarksText = $attachment->remarks ?? '';
                                        
                                        // For Present status, use default text if empty
                                        if (($isPresent === true || $isPresent === 1) && empty($remarksText)) {
                                            $remarksText = 'The attachment is present';
                                        }
                                        
                                        // Set readonly based on status
                                        $isReadonly = false;
                                        if ($isPresent === true || $isPresent === 1) {
                                            $isReadonly = true;
                                        }
                                    @endphp
                                    
                                    <div class="border rounded-lg p-4 transition-all duration-200 tev-attachment-item {{ $statusClass }}"
                                        data-attachment-id="{{ $attachment->id }}">
                                        
                                        <!-- Document Name & Status Row -->
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="flex-1">
                                                <label class="text-sm font-medium text-gray-900">
                                                    {{ $attachment->name }}
                                                </label>
                                            </div>
                                            
                                            <!-- Status Selection - RADIO BUTTONS -->
                                            <div class="flex items-center space-x-2" id="tev-status-group-{{ $attachment->id }}">
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="radio" 
                                                        name="tev_attachment_status_value[{{ $attachment->id }}]" 
                                                        value="1"
                                                        class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500 tev-attachment-radio present-radio"
                                                        data-attachment-id="{{ $attachment->id }}"
                                                        {{ $isPresent === 1 ? 'checked' : '' }}
                                                        {{ $dvRequest->status === 'Incoming-TEV_received' ? '' : 'disabled' }}
                                                        onchange="handleTEVStatusRadioChange(this)">
                                                    <span class="ml-1.5 text-xs font-medium text-green-700">Present</span>
                                                </label>
                                                
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="radio" 
                                                        name="tev_attachment_status_value[{{ $attachment->id }}]"  
                                                        value="0"
                                                        class="h-4 w-4 text-red-600 border-gray-300 focus:ring-red-500 tev-attachment-radio not-present-radio"
                                                        data-attachment-id="{{ $attachment->id }}"
                                                        {{ $isPresent === 0 ? 'checked' : '' }}
                                                        {{ $dvRequest->status === 'Incoming-TEV_received' ? '' : 'disabled' }}
                                                        onchange="handleTEVStatusRadioChange(this)">
                                                    <span class="ml-1.5 text-xs font-medium text-red-700">Not Present</span>
                                                </label>
                                                
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="radio" 
                                                        name="tev_attachment_status_value[{{ $attachment->id }}]" 
                                                        value="null"
                                                        class="h-4 w-4 text-gray-600 border-gray-300 focus:ring-gray-500 tev-attachment-radio na-radio"
                                                        data-attachment-id="{{ $attachment->id }}"
                                                        {{ is_null($isPresent) ? 'checked' : '' }}
                                                        {{ $dvRequest->status === 'Incoming-TEV_received' ? '' : 'disabled' }}
                                                        onchange="handleTEVStatusRadioChange(this)">
                                                    <span class="ml-1.5 text-xs font-medium text-gray-700">N/A</span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <!-- Remarks/Lacking Input -->
                                        <div class="tev-remarks-container" id="tev-remarks-container-{{ $attachment->id }}">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Remarks <span class="text-red-500">*</span>
                                            </label>
                                            <textarea 
                                                name="tev_attachment_remarks[{{ $attachment->id }}]"
                                                id="tev-remarks-{{ $attachment->id }}"
                                                rows="2"
                                                class="w-full px-3 py-2 text-xs border {{ $borderClass }} rounded-lg focus:ring-2 focus:ring-opacity-50 resize-none"
                                                placeholder="{{ $isPresent === 1 ? 'The attachment is present' : 'Enter remarks...' }}"
                                                {{ $dvRequest->status === 'Incoming-TEV_received' && !$isReadonly ? '' : 'readonly' }}
                                                maxlength="500">{{ $remarksText }}</textarea>
                                            <p class="text-xs {{ $textClass }} mt-1">
                                                {{ $helperText }}
                                            </p>
                                            <p class="text-xs text-red-500 mt-1 hidden" id="remarks-error-{{ $attachment->id }}">
                                                Remarks are required for N/A or Not Present attachments
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <!-- Summary -->
                            <div class="mt-4 flex items-center justify-between text-sm p-3 bg-white rounded-lg border border-gray-200">
                                <div class="flex items-center space-x-4">
                                    <span class="flex items-center">
                                        <span class="w-3 h-3 bg-green-500 rounded-full mr-1"></span>
                                        <span class="text-green-700 font-medium" id="tev-present-count">
                                            Present: {{ $tevAttachments->whereIn('is_present', [true, 1])->count() }}
                                        </span>
                                    </span>
                                    <span class="flex items-center">
                                        <span class="w-3 h-3 bg-red-500 rounded-full mr-1"></span>
                                        <span class="text-red-700 font-medium" id="tev-not-present-count">
                                            Not Present: {{ $tevAttachments->whereIn('is_present', [false, 0])->count() }}
                                        </span>
                                    </span>
                                    <span class="flex items-center">
                                        <span class="w-3 h-3 bg-gray-400 rounded-full mr-1"></span>
                                        <span class="text-gray-700 font-medium" id="tev-na-count">
                                            N/A: {{ $tevAttachments->whereNotIn('is_present', [true, false, 1, 0])->count() }}
                                        </span>
                                    </span>
                                </div>
                                <span class="text-gray-600 font-medium" id="tev-total-count">
                                    Total: {{ $tevAttachments->count() }}
                                </span>
                            </div>
                            
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                                <p class="text-sm text-yellow-800">
                                    <strong>Important:</strong> Remarks are <span class="font-bold">required</span> for:
                                </p>
                                <ul class="list-disc list-inside text-sm text-yellow-700 mt-1 space-y-1">
                                    <li>All attachments marked as <span class="font-bold">"N/A"</span> - Explain why the document is not applicable</li>
                                    <li>All attachments marked as <span class="font-bold">"Not Present"</span> - Specify what is missing or lacking</li>
                                    <li>Attachments marked as <span class="font-bold">"Present"</span> automatically have remarks filled</li>
                                </ul>
                            </div>
                        @elseif($tevSelectedModality && $tevAttachments->count() === 0)
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <span class="text-yellow-800">No attachments found for the "{{ $tevSelectedModality }}" modality.</span>
                                </div>
                                <p class="text-sm text-yellow-700 mt-2">
                                    Please run the TEV attachment seeder or contact the system administrator.
                                </p>
                            </div>
                        @else
                            <div class="bg-gray-100 border border-gray-300 rounded-lg p-4">
                                <p class="text-sm text-gray-600 italic">
                                    Please select a modality above to view required attachments.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Amount --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="text" value="₱{{ number_format($dvRequest->amount, 2) }}" readonly
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                </div>

                {{-- Payment Details --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if($dvRequest->paymentDetails && $dvRequest->paymentDetails->count() > 0)
                        @php
                            $paymentDetail = $dvRequest->paymentDetails->first();
                        @endphp
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                            <input type="text" value="{{ $paymentDetail->bank_name ?? 'N/A' }}" readonly
                                   class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bank Account Number</label>
                            <input type="text" value="{{ $paymentDetail->bank_accnum ?? 'N/A' }}" readonly
                                   class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bank Address</label>
                            <input type="text" value="{{ $paymentDetail->bank_address ?? 'N/A' }}" readonly
                                   class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>
                    @else
                        <p class="text-xs text-gray-400 col-span-3">No payment details available</p>
                    @endif
                </div>

                {{-- Received Info --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Received By</label>
                        <input type="text" value="{{ $dvRequest->accountingUnit->fname ?? '' }} {{ $dvRequest->accountingUnit->lname ?? '' }}" readonly
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Received From</label>
                        <input type="text" value="{{ $dvRequest->focalPerson->fname ?? 'Unknown' }} {{ $dvRequest->focalPerson->lname ?? '' }}" readonly
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                </div>

                {{-- Date and Time --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date Received</label>
                        <input type="date" 
                            value="{{ $dvRequest->created_at ? $dvRequest->created_at->format('Y-m-d') : '' }}"
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Time</label>
                        <input type="time"   
                            value="{{ $dvRequest->created_at ? $dvRequest->created_at->format('H:i') : '' }}"
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                </div>

                {{-- Remarks --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Remarks / Lacking</label>
                    <textarea id="remarks" name="remarks" rows="3"
                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm"
                              placeholder="Enter remarks or notes..."></textarea>
                </div>

                {{-- Buttons --}}
                @if($dvRequest->status === 'Incoming-TEV_received')
                    <div class="flex justify-end space-x-3" id="button-container">
                        <button type="submit"
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200"
                            id="verify-button">
                            Verify
                        </button>
                        <button 
                            type="button"
                            onclick="openRejectModal()"
                            class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Reject
                        </button>
                        <a href="{{ route('Incoming.TEV.index') }}"
                            class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Back
                        </a>
                    </div>
                @elseif($dvRequest->status === 'IMSD_verified')
                    <div id="button-container" class="flex justify-end space-x-3">
                        <button type="button"
                            onclick="handleReceiveAction({{ $dvRequest->id }}, this)"
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200"
                            id="receive-btn">
                            <span class="button-text">Receive</span>
                            <svg class="animate-spin -mr-1 ml-3 h-5 w-5 text-white hidden" id="loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                        <a href="{{ route('Incoming.TEV.index') }}"
                            class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Back
                        </a>
                    </div>
                @else
                    <a href="{{ route('Incoming.TEV.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Back
                    </a>
                @endif
            </form>
        </div>

        <!-- Right Sidebar -->
        <div class="lg:col-span-1">
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-lg bg-white">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-3 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Reject DV Request</h3>
            <button type="button" onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="rejectForm" class="mt-4">
            <div class="mb-4">
                <label for="rejectRemarks" class="block text-sm font-medium text-gray-700 mb-2">
                    Reason for Rejection <span class="text-red-500">*</span>
                </label>
                <textarea 
                    id="rejectRemarks" 
                    name="remarks" 
                    rows="4" 
                    required
                    minlength="10"
                    maxlength="1000"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"
                    placeholder="Please provide a detailed reason for rejection (minimum 10 characters)..."
                    oninput="updateCharCount()"></textarea>
                <div class="flex justify-between items-center mt-1">
                    <p class="text-xs text-gray-500">This message will be sent to the focal person.</p>
                    <p id="char-count" class="text-xs text-gray-400">0 / 1000</p>
                </div>
                <p id="remarksError" class="text-xs text-red-600 mt-1 hidden">Remarks must be at least 10 characters long</p>
            </div>

            <!-- Warning Message -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            This action cannot be undone. The focal person will be notified immediately.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button 
                    type="button" 
                    onclick="closeRejectModal()"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition duration-200">
                    Cancel
                </button>
                <button 
                    type="submit"
                    id="confirmRejectBtn"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition duration-200 flex items-center">
                    <span id="rejectBtnText">Reject Request</span>
                    <svg id="rejectSpinner" class="animate-spin ml-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
#rejectModal {
    transition: opacity 0.3s ease-in-out;
}

#rejectModal > div {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.tev-attachment-checkbox {
    cursor: pointer;
}

.tev-attachment-checkbox:checked {
    background-color: currentColor;
    border-color: currentColor;
}
</style>
<div id="message-container" class="fixed top-4 right-4 z-50"></div>
@endsection

<script>
const TEV_REQUIRED_ATTACHMENTS = {
    'Cash Advance for Local Travel': [
        '2 copies of Approved Itinerary of Travel',
        'Approved Office Order or Travel Order',
        'Certification from the Accountant that previous cash advance has been liquidated and accounted for in the books'
    ],
    'Liquidation of Cash Advances': [
        '2 copies of Liquidation Report (Appendix 44, GAM)',
        '2 copies of Revised Itinerary of Travel, if the previously approved itinerary was not followed',
        'Certificate of Appearance or Attendance',
        'Copy of previously approved Itinerary of Travel',
        'Approved Travel Order or Notice of Inspection or Approved Official Business or equivalent',
        'Paper or electronic plane, boat, or bus tickets; boarding pass; terminal fee receipts',
        'Official invoices/receipts (e.g., hotel bills, Grab or taxi receipts)',
        'Certification by the Head of Agency as to the absolute necessity of the expenses, with corresponding bills/receipts',
        'Certificate of Expense Not Requiring Receipts (CENRR) or Reimbursable Expense Receipt (RER), as applicable',
    ]
};

let currentRejectId = null;
let dvRequestId = "{{ $dvRequest->id }}";

// Function to find which specific required attachment matches the attachment name
function findMatchingRequiredAttachment(attachmentName, modality) {
    const requiredList = TEV_REQUIRED_ATTACHMENTS[modality];
    if (!requiredList) return null;
    
    const normalizedName = attachmentName.toLowerCase().trim();
    
    for (const required of requiredList) {
        const normalizedRequired = required.toLowerCase().trim();
        if (normalizedName === normalizedRequired) {
            return required;
        }
        if (normalizedName.includes(normalizedRequired) && 
            Math.abs(normalizedName.length - normalizedRequired.length) < 10) {
            return required;
        }
    }
    
    return null;
}

// Function to check if all required attachments are marked as Present
function checkTEVRequiredAttachments() {
    const verifyButton = document.getElementById('verify-button');
    const modalitySelect = document.getElementById('tev_modality_select');
    
    if (!verifyButton || !modalitySelect) return true;
    
    const selectedModality = modalitySelect.value;
    if (!selectedModality) return true;
    
    const requiredAttachments = TEV_REQUIRED_ATTACHMENTS[selectedModality];
    if (!requiredAttachments || requiredAttachments.length === 0) return true;
    
    // Get all attachment items
    const attachmentItems = document.querySelectorAll('.tev-attachment-item');
    
    // Create a map to track which required attachments are present
    const requiredAttachmentsStatus = new Map();
    requiredAttachments.forEach(req => {
        requiredAttachmentsStatus.set(req, false);
    });
    
    // Check each attachment item in the UI
    attachmentItems.forEach(item => {
        const attachmentLabel = item.querySelector('label.text-sm.font-medium.text-gray-900');
        if (!attachmentLabel) return;
        
        const attachmentName = attachmentLabel.textContent.trim();
        const attachmentId = item.dataset.attachmentId;
        const presentRadio = item.querySelector(`input[name="tev_attachment_status_value[${attachmentId}]"][value="1"]`);
        
        // Check if this attachment is marked as Present
        if (presentRadio && presentRadio.checked) {
            // Find which required attachment this matches
            const matchedRequired = findMatchingRequiredAttachment(attachmentName, selectedModality);
            if (matchedRequired) {
                requiredAttachmentsStatus.set(matchedRequired, true);
            }
        }
    });
    
    // Get list of missing required attachments
    const missingRequired = [];
    requiredAttachmentsStatus.forEach((isPresent, requiredName) => {
        if (!isPresent) {
            missingRequired.push(requiredName);
        }
    });
    
    // Show/hide warning
    const existingWarning = document.getElementById('tev-required-warning');
    if (existingWarning) {
        existingWarning.remove();
    }
    
    const existingSuccess = document.getElementById('tev-required-success');
    if (existingSuccess) {
        existingSuccess.remove();
    }
    
    if (missingRequired.length > 0) {
        // Disable verify button - NOT ALL required attachments are present
        verifyButton.disabled = true;
        verifyButton.classList.add('opacity-50', 'cursor-not-allowed');
        verifyButton.title = 'All required attachments must be marked as Present';
        
        // Show warning
        const warningDiv = document.createElement('div');
        warningDiv.id = 'tev-required-warning';
        warningDiv.className = 'mt-3 bg-red-50 border border-red-200 rounded-lg p-4';
        warningDiv.innerHTML = `
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div class="flex-1">
                    <span class="text-red-800 font-medium">⚠️ Required Attachments Missing</span>
                    <p class="text-sm text-red-700 mt-1">
                        The following <strong>${missingRequired.length}</strong> required attachment(s) must be marked as <span class="font-bold">"Present"</span> to verify this request:
                    </p>
                    <ul class="list-disc list-inside text-sm text-red-700 mt-2 space-y-1 ml-2">
                        ${missingRequired.map(name => `<li>${name}</li>`).join('')}
                    </ul>
                    <p class="text-xs text-red-600 mt-2">
                        <strong>Note:</strong> All ${requiredAttachments.length} required attachments must be marked as "Present". You currently have ${requiredAttachments.length - missingRequired.length} of ${requiredAttachments.length} marked.
                    </p>
                </div>
            </div>
        `;
        
        const summaryDiv = document.querySelector('.mt-4.flex.items-center.justify-between');
        if (summaryDiv) {
            summaryDiv.parentNode.insertBefore(warningDiv, summaryDiv.nextSibling);
        }
        
        return false;
    } else {
        // Enable verify button - ALL required attachments are present
        verifyButton.disabled = false;
        verifyButton.classList.remove('opacity-50', 'cursor-not-allowed');
        verifyButton.title = 'Verify this request';
        
        // Show success message
        const successDiv = document.createElement('div');
        successDiv.id = 'tev-required-success';
        successDiv.className = 'mt-3 bg-green-50 border border-green-200 rounded-lg p-4';
        successDiv.innerHTML = `
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <div class="flex-1">
                    <span class="text-green-800 font-medium">✓ All Required Attachments Present</span>
                    <p class="text-sm text-green-700 mt-1">
                        All <strong>${requiredAttachments.length}</strong> required attachments are marked as "Present". You may now verify this request.
                    </p>
                </div>
            </div>
        `;
        
        const summaryDiv = document.querySelector('.mt-4.flex.items-center.justify-between');
        if (summaryDiv) {
            summaryDiv.parentNode.insertBefore(successDiv, summaryDiv.nextSibling);
        }
        
        return true;
    }
}

// Handle status change for TEV radio buttons
function handleTEVStatusRadioChange(radioElement) {
    const attachmentId = radioElement.dataset.attachmentId;
    if (!attachmentId) {
        console.error('No attachment ID found for radio button');
        return;
    }
    
    // Only process if the radio button is checked
    if (!radioElement.checked) {
        return;
    }
    
    const value = radioElement.value;
    let displayValue = null;
    
    // Convert radio value to stored value
    if (value === '1') {
        displayValue = 1;
    } else if (value === '0') {
        displayValue = 0;
    } else if (value === 'null') {
        displayValue = null;
    }
    
    // Update UI
    updateTEVAttachmentUI(attachmentId, displayValue);
    
    // Update summary
    updateTEVAttachmentSummary();
    
    // Check required attachments
    checkTEVRequiredAttachments();
    
    // Validate remarks
    validateRemarksForAttachment(attachmentId);
}

function updateTEVAttachmentUI(attachmentId, status) {
    const item = document.querySelector(`[data-attachment-id="${attachmentId}"]`);
    if (!item) {
        console.error('Attachment item not found:', attachmentId);
        return;
    }
    
    const remarksTextarea = document.getElementById(`tev-remarks-${attachmentId}`);
    const remarksHelper = item.querySelector('.tev-remarks-container p.text-xs');
    const remarksError = document.getElementById(`remarks-error-${attachmentId}`);
    
    // Remove all color classes
    item.classList.remove('bg-green-50', 'border-green-200', 'bg-red-50', 'border-red-200', 'bg-gray-50', 'border-gray-200');
    if (remarksTextarea) {
        remarksTextarea.classList.remove('border-green-300', 'border-red-300', 'border-gray-300', 
                                        'bg-green-50', 'bg-red-50', 'bg-gray-50');
    }
    
    if (status === 1 || status === '1') {
        // Present - Green
        item.classList.add('bg-green-50', 'border-green-200');
        
        if (remarksTextarea) {
            // Auto-fill remarks for Present
            if (!remarksTextarea.value || remarksTextarea.value === '') {
                remarksTextarea.value = 'The attachment is present';
            }
            remarksTextarea.classList.add('border-green-300', 'bg-green-50');
            remarksTextarea.placeholder = 'The attachment is present';
            remarksTextarea.readOnly = true;
        }
        if (remarksHelper) {
            remarksHelper.textContent = '✓ This document is complete and present.';
            remarksHelper.className = 'text-xs text-green-600 mt-1';
        }
        if (remarksError) {
            remarksError.classList.add('hidden');
        }
    } else if (status === 0 || status === '0') {
        // Not Present - Red
        item.classList.add('bg-red-50', 'border-red-200');
        
        if (remarksTextarea) {
            // Clear if it has default "present" message
            if (remarksTextarea.value === 'The attachment is present') {
                remarksTextarea.value = '';
            }
            remarksTextarea.classList.add('border-red-300', 'bg-red-50');
            remarksTextarea.placeholder = 'Specify what is lacking or missing in this document...';
            remarksTextarea.readOnly = false;
        }
        if (remarksHelper) {
            remarksHelper.textContent = 'Describe what information or entries are missing from this document.';
            remarksHelper.className = 'text-xs text-gray-500 mt-1';
        }
        if (remarksError) {
            remarksError.textContent = 'Remarks are required for Not Present attachments';
            remarksError.classList.remove('hidden');
        }
    } else {
        // N/A - Gray (default) - Editable remarks
        item.classList.add('bg-gray-50', 'border-gray-200');
        
        if (remarksTextarea) {
            // Clear if it's the default "present" message
            if (remarksTextarea.value === 'The attachment is present') {
                remarksTextarea.value = '';
            }
            remarksTextarea.classList.add('border-gray-300', 'bg-gray-50');
            remarksTextarea.placeholder = 'Explain why this document is not applicable...';
            remarksTextarea.readOnly = false; // Make it editable for N/A
        }
        if (remarksHelper) {
            remarksHelper.textContent = 'Explain why this document is not applicable for this request.';
            remarksHelper.className = 'text-xs text-gray-500 mt-1';
        }
        if (remarksError) {
            remarksError.textContent = 'Remarks are required for N/A attachments';
            remarksError.classList.remove('hidden');
        }
    }
}

// Validate remarks for a specific attachment
function validateRemarksForAttachment(attachmentId) {
    const radios = document.querySelectorAll(`input[name="tev_attachment_status_value[${attachmentId}]"]`);
    const remarksTextarea = document.getElementById(`tev-remarks-${attachmentId}`);
    const remarksError = document.getElementById(`remarks-error-${attachmentId}`);
    
    if (!radios.length || !remarksTextarea || !remarksError) return true;
    
    // Find the checked radio
    let status = null;
    radios.forEach(radio => {
        if (radio.checked) {
            status = radio.value;
        }
    });
    
    const remarks = remarksTextarea.value.trim();
    
    // For Present status, we auto-fill remarks, so always valid
    if (status === '1') {
        remarksError.classList.add('hidden');
        return true;
    }
    
    // For N/A and Not Present, remarks are required
    if ((status === 'null' || status === '0') && !remarks) {
        remarksError.classList.remove('hidden');
        return false;
    }
    
    remarksError.classList.add('hidden');
    return true;
}

// Validate all attachments remarks
function validateAllAttachmentsRemarks() {
    const attachmentItems = document.querySelectorAll('.tev-attachment-item');
    let allValid = true;
    const invalidAttachments = [];
    
    attachmentItems.forEach(item => {
        const attachmentId = item.dataset.attachmentId;
        const isValid = validateRemarksForAttachment(attachmentId);
        
        if (!isValid) {
            allValid = false;
            // Get attachment name for error message
            const label = item.querySelector('label.text-sm.font-medium.text-gray-900');
            if (label) {
                invalidAttachments.push(label.textContent.trim());
            }
        }
    });
    
    return { allValid, invalidAttachments };
}

// Update the summary counts
function updateTEVAttachmentSummary() {
    const attachmentItems = document.querySelectorAll('.tev-attachment-item');
    
    let presentCount = 0;
    let notPresentCount = 0;
    let naCount = 0;
    
    // Count each attachment's status
    attachmentItems.forEach(item => {
        const attachmentId = item.dataset.attachmentId;
        const radios = document.querySelectorAll(`input[name="tev_attachment_status_value[${attachmentId}]"]`);
        
        radios.forEach(radio => {
            if (radio.checked) {
                const value = radio.value;
                
                if (value === '1') {
                    presentCount++;
                } else if (value === '0') {
                    notPresentCount++;
                } else {
                    naCount++;
                }
            }
        });
    });
    
    const total = attachmentItems.length;
    
    const presentCountEl = document.getElementById('tev-present-count');
    const notPresentCountEl = document.getElementById('tev-not-present-count');
    const naCountEl = document.getElementById('tev-na-count');
    const totalCountEl = document.getElementById('tev-total-count');
    
    if (presentCountEl) presentCountEl.textContent = `Present: ${presentCount}`;
    if (notPresentCountEl) notPresentCountEl.textContent = `Not Present: ${notPresentCount}`;
    if (naCountEl) naCountEl.textContent = `N/A: ${naCount}`;
    if (totalCountEl) totalCountEl.textContent = `Total: ${total}`;
}

// Bulk action buttons
function markAllTEVAsPresent() {
    const attachmentItems = document.querySelectorAll('.tev-attachment-item');
    
    attachmentItems.forEach(item => {
        const attachmentId = item.dataset.attachmentId;
        if (!attachmentId) return;
        
        const presentRadio = item.querySelector('.present-radio');
        if (presentRadio && !presentRadio.disabled) {
            presentRadio.checked = true;
            handleTEVStatusRadioChange(presentRadio);
        }
    });
    
    showMessage('All TEV attachments marked as Present', 'success');
    updateTEVAttachmentSummary();
    checkTEVRequiredAttachments();
}

function markAllTEVAsNA() {
    const attachmentItems = document.querySelectorAll('.tev-attachment-item');
    
    attachmentItems.forEach(item => {
        const attachmentId = item.dataset.attachmentId;
        if (!attachmentId) return;
        
        const naRadio = item.querySelector('.na-radio');
        if (naRadio && !naRadio.disabled) {
            naRadio.checked = true;
            handleTEVStatusRadioChange(naRadio);
        }
    });
    
    showMessage('All TEV attachments marked as N/A', 'success');
    updateTEVAttachmentSummary();
    checkTEVRequiredAttachments();
}

async function handleModalityChange() {
    const modalitySelect = document.getElementById('tev_modality_select');
    const selectedModality = modalitySelect.value;
    
    if (!selectedModality) {
        const container = document.getElementById('tev-attachments-container');
        container.innerHTML = `
            <div class="bg-gray-100 border border-gray-300 rounded-lg p-4">
                <p class="text-sm text-gray-600 italic">
                    Please select a modality above to view required attachments.
                </p>
            </div>
        `;
        
        const bulkActionButtons = document.getElementById('bulk-actions');
        if (bulkActionButtons) {
            bulkActionButtons.style.display = 'none';
        }
        
        return;
    }
    
    try {
        showMessage('Changing modality...', 'info');
        
        const response = await fetch(`/Incoming/TEV/dvrequests/${dvRequestId}/change-modality`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tev_modality: selectedModality
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.reload_required) {
            showMessage('Modality changed! Reloading page...', 'success');
            
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else if (data.success) {
            showMessage('Modality set successfully', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            showMessage(data.message || 'Failed to change modality', 'error');
        }
        
    } catch (error) {
        console.error('Error changing modality:', error);
        showMessage('Error changing modality: ' + error.message, 'error');
        
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }
}

function updateCharCount() {
    const textarea = document.getElementById('rejectRemarks');
    const charCount = document.getElementById('char-count');
    const remarksError = document.getElementById('remarksError');
    if (!textarea || !charCount) return;
    
    const length = textarea.value.length;
    
    charCount.textContent = `${length} / 1000`;
    
    if (length < 10) {
        charCount.classList.add('text-red-500');
        charCount.classList.remove('text-gray-400', 'text-green-600', 'text-yellow-600');
        if (length > 0 && remarksError) {
            remarksError.classList.remove('hidden');
        }
    } else if (length > 900) {
        charCount.classList.add('text-yellow-600');
        charCount.classList.remove('text-gray-400', 'text-green-600', 'text-red-500');
        if (remarksError) remarksError.classList.add('hidden');
    } else {
        charCount.classList.add('text-green-600');
        charCount.classList.remove('text-gray-400', 'text-red-500', 'text-yellow-600');
        if (remarksError) remarksError.classList.add('hidden');
    }
}

function openRejectModal() {
    currentRejectId = dvRequestId;
    const modal = document.getElementById('rejectModal');
    
    modal.classList.remove('hidden');
    document.getElementById('rejectRemarks').value = '';
    document.getElementById('remarksError').classList.add('hidden');
    document.body.style.overflow = 'hidden';
    updateCharCount();
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectRemarks').value = '';
    document.getElementById('remarksError').classList.add('hidden');
    currentRejectId = null;
    document.body.style.overflow = 'auto';
}

function showMessage(message, type = 'success') {
    const container = document.getElementById('message-container');
    if (!container) return;
    
    const messageDiv = document.createElement('div');
    
    let bgColor = 'bg-green-500';
    if (type === 'error') bgColor = 'bg-red-500';
    if (type === 'info') bgColor = 'bg-blue-500';
    if (type === 'warning') bgColor = 'bg-yellow-500';
    
    messageDiv.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg mb-4 transform transition-all duration-300 translate-x-full opacity-0`;
    messageDiv.textContent = message;
    
    container.appendChild(messageDiv);
    
    setTimeout(() => {
        messageDiv.classList.remove('translate-x-full', 'opacity-0');
    }, 100);
    
    setTimeout(() => {
        messageDiv.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 300);
    }, 3000);
}

async function handleReceiveAction(dvRequestId, buttonElement) {
    const spinner = document.getElementById('loading-spinner');
    const buttonText = buttonElement.querySelector('.button-text');
    
    buttonElement.disabled = true;
    buttonText.textContent = 'Receiving...';
    spinner.classList.remove('hidden');
    
    try {
        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        
        const response = await fetch(`/Incoming/TEV/dvrequests/${dvRequestId}/receive`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: formData
        });

        const responseText = await response.text();
        let data = JSON.parse(responseText);

        if (response.ok && data.success) {
            showMessage(data.message, 'success');
            updateButtonsToVerifyMode(dvRequestId);
        } else {
            throw new Error(data.message || 'Error receiving request');
        }
    } catch (error) {
        console.error('Receive Error:', error);
        showMessage(error.message || 'Failed to receive request', 'error');
        buttonElement.disabled = false;
        buttonText.textContent = 'Receive';
        spinner.classList.add('hidden');
    }
}

function updateButtonsToVerifyMode(dvRequestId) {
    const buttonContainer = document.getElementById('button-container');
    if (!buttonContainer) return;
    
    buttonContainer.innerHTML = `
        <div class="flex justify-end space-x-3">
            <button type="submit" 
                    class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200"
                    id="verify-button">
                Verify
            </button>
            <button type="button"
                    onclick="openRejectModal()"
                    class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                Reject
            </button>
            <a href="{{ route('Incoming.TEV.index') }}"
               class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                Back
            </a>
        </div>
    `;
    
    // Re-check required attachments
    setTimeout(checkTEVRequiredAttachments, 100);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    const rejectForm = document.getElementById('rejectForm');
    const verifyForm = document.getElementById('verifyForm');
    
    // Initialize character count
    updateCharCount();
    
    // Initialize TEV attachment summary
    updateTEVAttachmentSummary();
    
    // Initialize required attachments check
    setTimeout(checkTEVRequiredAttachments, 100);
    
    // Handle verify form submission
    if (verifyForm) {
        verifyForm.addEventListener('submit', function(e) {
            // Check modality
            const modalitySelect = document.getElementById('tev_modality_select');
            if (modalitySelect && !modalitySelect.value) {
                e.preventDefault();
                showMessage('Please select a TEV modality first!', 'error');
                modalitySelect.focus();
                return false;
            }
            
            // Validate all attachments remarks
            const { allValid, invalidAttachments } = validateAllAttachmentsRemarks();
            if (!allValid) {
                e.preventDefault();
                showMessage(`Please add remarks for the following attachments:\n${invalidAttachments.join('\n')}`, 'error');
                return false;
            }
            
            // Check required attachments before submission
            if (!checkTEVRequiredAttachments()) {
                e.preventDefault();
                showMessage('Please mark all required attachments as "Present" before verifying.', 'error');
                return false;
            }
            
            // Show confirmation dialog
            const confirmMsg = `Are you sure you want to verify this DV request?\n\n` +
                             `Modality: ${modalitySelect.options[modalitySelect.selectedIndex].text}\n` +
                             `This action cannot be undone.`;
            
            if (!confirm(confirmMsg)) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    }
    
    // Handle reject form submission
    if (rejectForm) {
        rejectForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const remarks = document.getElementById('rejectRemarks').value.trim();
            const remarksError = document.getElementById('remarksError');
            
            if (!remarks || remarks.length < 10) {
                remarksError.classList.remove('hidden');
                document.getElementById('rejectRemarks').focus();
                return;
            }
            
            remarksError.classList.add('hidden');
            
            const confirmBtn = document.getElementById('confirmRejectBtn');
            const btnText = document.getElementById('rejectBtnText');
            const spinner = document.getElementById('rejectSpinner');
            
            confirmBtn.disabled = true;
            btnText.textContent = 'Rejecting...';
            spinner.classList.remove('hidden');
            
            try {
                const response = await fetch(`/Incoming/TEV/dvrequests/${currentRejectId}/reject`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ remarks })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(data.message || 'DV request has been rejected successfully.', 'success');
                    closeRejectModal();
                    
                    setTimeout(() => {
                        window.location.href = "{{ route('Incoming.TEV.index') }}";
                    }, 1500);
                } else {
                    showMessage(data.message || 'Failed to reject the request.', 'error');
                    confirmBtn.disabled = false;
                    btnText.textContent = 'Reject Request';
                    spinner.classList.add('hidden');
                }
            } catch (error) {
                console.error('Reject error:', error);
                showMessage('An error occurred while rejecting the request.', 'error');
                confirmBtn.disabled = false;
                btnText.textContent = 'Reject Request';
                spinner.classList.add('hidden');
            }
        });
    }

    // Close modal handlers
    const rejectModal = document.getElementById('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && rejectModal && !rejectModal.classList.contains('hidden')) {
            closeRejectModal();
        }
    });

    // Add input event listeners to remarks textareas
    document.addEventListener('input', function(e) {
        if (e.target.name && e.target.name.startsWith('tev_attachment_remarks[')) {
            const attachmentId = e.target.name.match(/\[(.*?)\]/)[1];
            validateRemarksForAttachment(attachmentId);
        }
    });

    // Auto-refresh lock (if applicable)
    if (dvRequestId) {
        setInterval(() => {
            fetch(`/Incoming/TEV/dvrequests/${dvRequestId}/refresh-lock`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Accept": "application/json"
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    console.log("Lock refreshed");
                }
            })
            .catch(err => console.error("Refresh lock failed:", err));
        }, 120000);

        window.addEventListener("beforeunload", () => {
            navigator.sendBeacon(`/Incoming/TEV/dvrequests/${dvRequestId}/release-lock`, 
                JSON.stringify({ _token: document.querySelector('meta[name="csrf-token"]').content }));
        });
    }
});
</script>