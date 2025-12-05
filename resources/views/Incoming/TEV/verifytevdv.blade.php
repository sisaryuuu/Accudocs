@extends('layouts.app')

@section('title', 'Verify DV Request - TEV')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Section - Incoming Form -->
        <div class="lg:col-span-2 bg-white shadow rounded-xl p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Incoming - TEV</h1>

            <form action="{{ route('Incoming.TEV.dvrequests.verify', $dvRequest->id) }}" method="POST" class="space-y-6">
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
                        <input type="text" value="{{ $dvRequest->id ?? '' }}" readonly
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

 
<!-- Replace the TEV Attachments section (starting from line 87) with this: -->

<!-- TEV Attachments Checklist -->
<div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">
            📋 TEV Attachments Verification
        </h3>
        
        <!-- Bulk Action Buttons -->
        @if($dvRequest->status === 'Incoming-TEV_received' && $tevSelectedModality && $tevAttachments && $tevAttachments->count() > 0)
            <div class="flex items-center space-x-3">
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
                <button type="button" 
                        onclick="uncheckAllTEV()"
                        class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Uncheck All
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
                        // Determine the current status - default to null (N/A)
                        $isPresent = $attachment->is_present ?? null;
                        
                        // Set color classes based on status
                        if ($isPresent === true || $isPresent === 1) {
                            $statusClass = 'bg-green-50 border-green-200';
                            $borderClass = 'border-green-300 bg-green-50';
                            $textClass = 'text-green-600';
                            $helperText = '✓ This document is complete and present.';
                            $placeholderText = 'The attachment is present';
                        } elseif ($isPresent === false || $isPresent === 0) {
                            $statusClass = 'bg-red-50 border-red-200';
                            $borderClass = 'border-red-300';
                            $textClass = 'text-gray-500';
                            $helperText = 'Describe what information or entries are missing from this document.';
                            $placeholderText = '';
                        } else {
                            // Default: N/A (null or empty)
                            $statusClass = 'bg-gray-50 border-gray-200';
                            $borderClass = 'border-gray-300';
                            $textClass = 'text-gray-500';
                            $helperText = 'This document is not applicable for this request.';
                            $placeholderText = 'Not applicable';
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
                                
                                <!-- Remove this hidden input since the radio buttons will now submit directly -->
                                <!-- <input type="hidden" 
                                    name="tev_attachment_status_value[{{ $attachment->id }}]" 
                                    value="{{ $isPresent === 1 ? '1' : ($isPresent === 0 ? '0' : 'null') }}" 
                                    id="tev-status-value-{{ $attachment->id }}"> -->
                            </div>
                        </div>
                        
                        <!-- Remarks/Lacking Input -->
                        <div class="tev-remarks-container" id="tev-remarks-container-{{ $attachment->id }}">
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Remarks
                            </label>
                            <textarea 
                                name="tev_attachment_remarks[{{ $attachment->id }}]"
                                id="tev-remarks-{{ $attachment->id }}"
                                rows="2"
                                class="w-full px-3 py-2 text-xs border {{ $borderClass }} rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"
                                placeholder="{{ $placeholderText }}"
                                {{ $dvRequest->status === 'Incoming-TEV_received' && $isPresent !== true && $isPresent !== 1 ? '' : 'readonly' }}
                                maxlength="500">{{ $attachment->remarks ?? $placeholderText }}</textarea>
                            <p class="text-xs {{ $textClass }} mt-1">
                                {{ $helperText }}
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
            
            <p class="text-xs text-gray-500 mt-3">
                <strong>Note:</strong> All documents default to "N/A". Mark as "Present" or "Not Present" as needed. 
                When marked as "Present", a default remark "The attachment is present" will be automatically added.
                Use "Uncheck All" to reset all to N/A.
            </p>
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
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Verify
                        </button>
                        <button 
                            type="button"
                            onclick="openRejectModal()"
                            class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Reject
                        </button>
                        <a href="{{ route('Incoming.TEV.index') }}"
                            class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
                            onclick="releaseLock(event, {{ $dvRequest->id }})">
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
                            class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
                            onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
                    </div>
                @else
                    <a href="{{ route('Incoming.TEV.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
                        onclick="releaseLock(event, {{ $dvRequest->id }})">
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
/* Smooth transitions */
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

/* Custom checkbox styling */
.tev-attachment-checkbox {
    cursor: pointer;
}

.tev-attachment-checkbox:checked {
    background-color: currentColor;
    border-color: currentColor;
}

/* Textarea styling for different statuses */
.tev-textarea-present {
    border-color: #10b981 !important;
    background-color: #f0fdf4 !important;
}

.tev-textarea-not-present {
    border-color: #ef4444 !important;
    background-color: #fef2f2 !important;
}

.tev-textarea-na {
    border-color: #d1d5db !important;
    background-color: #f9fafb !important;
}
</style>
<div id="message-container" class="fixed top-4 right-4 z-50"></div>
@endsection

<script>
let currentRejectId = null;

// TEV attachments data by modality (frontend only)
const tevAttachmentsByModality = {
    'Cash Advance for Local Travel': [
        '2 Copies of Obligation Request and Status (ORS)',
        '3 copies of Disbursement Vouchers (DVs)',
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
        'Other supporting documents necessary to justify travel and reimbursement',
        'Official Receipt (OR) in case of refund'
    ]
};

function handleTEVStatusRadioChange(radioElement) {
    console.log('handleTEVStatusRadioChange called', { 
        radio: radioElement,
        value: radioElement.value,
        checked: radioElement.checked,
        name: radioElement.name
    });
    
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
    
    console.log('Setting display value:', displayValue, 'for attachment:', attachmentId);
    
    // Update UI
    updateTEVAttachmentUI(attachmentId, displayValue);
    
    // Update summary
    updateTEVAttachmentSummary();
}

function updateTEVAttachmentUI(attachmentId, status, isCleared = false) {
    console.log('updateTEVAttachmentUI called', { attachmentId, status, isCleared });
    
    const item = document.querySelector(`[data-attachment-id="${attachmentId}"]`);
    if (!item) {
        console.error('Attachment item not found:', attachmentId);
        return;
    }
    
    const remarksContainer = document.getElementById(`tev-remarks-container-${attachmentId}`);
    const remarksTextarea = document.getElementById(`tev-remarks-${attachmentId}`);
    const remarksHelper = remarksContainer ? remarksContainer.querySelector('p.text-xs') : null;
    
    // Remove all color classes
    item.classList.remove('bg-green-50', 'border-green-200', 'bg-red-50', 'border-red-200', 'bg-gray-50', 'border-gray-200');
    
    if (status === 1 || status === '1') {
        // Present - Green
        item.classList.add('bg-green-50', 'border-green-200');
        
        if (remarksTextarea) {
            remarksTextarea.value = 'The attachment is present';
            remarksTextarea.classList.add('border-green-300', 'bg-green-50');
            remarksTextarea.classList.remove('border-red-300', 'border-gray-300', 'bg-red-50', 'bg-gray-50');
            remarksTextarea.placeholder = 'The attachment is present';
            remarksTextarea.readOnly = true;
        }
        if (remarksHelper) {
            remarksHelper.textContent = '✓ This document is complete and present.';
            remarksHelper.className = 'text-xs text-green-600 mt-1';
        }
    } else if (status === 0 || status === '0') {
        // Not Present - Red
        item.classList.add('bg-red-50', 'border-red-200');
        
        if (remarksTextarea) {
            // Clear if it has default messages
            if (remarksTextarea.value === 'The attachment is present' || remarksTextarea.value === 'Not applicable') {
                remarksTextarea.value = '';
            }
            remarksTextarea.classList.add('border-red-300', 'bg-red-50');
            remarksTextarea.classList.remove('border-green-300', 'border-gray-300', 'bg-green-50', 'bg-gray-50');
            remarksTextarea.placeholder = 'Specify what is lacking or missing in this document...';
            remarksTextarea.readOnly = false;
        }
        if (remarksHelper) {
            remarksHelper.textContent = 'Describe what information or entries are missing from this document.';
            remarksHelper.className = 'text-xs text-gray-500 mt-1';
        }
    } else {
        // N/A - Gray (default)
        item.classList.add('bg-gray-50', 'border-gray-200');
        
        if (remarksTextarea) {
            remarksTextarea.value = 'Not applicable';
            remarksTextarea.classList.add('border-gray-300', 'bg-gray-50');
            remarksTextarea.classList.remove('border-green-300', 'border-red-300', 'bg-green-50', 'bg-red-50');
            remarksTextarea.placeholder = 'Not applicable';
            remarksTextarea.readOnly = true;
        }
        if (remarksHelper) {
            remarksHelper.textContent = 'This document is not applicable for this request.';
            remarksHelper.className = 'text-xs text-gray-500 mt-1';
        }
    }
}

// Update the summary counts
function updateTEVAttachmentSummary() {
    console.log('updateTEVAttachmentSummary called');
    
    // Get ALL radio buttons (not hidden inputs)
    const allRadioGroups = document.querySelectorAll('[name^="tev_attachment_status_value"]');
    
    // Group by attachment ID to get unique attachments
    const attachmentIds = new Set();
    allRadioGroups.forEach(radio => {
        const match = radio.name.match(/\[([^\]]+)\]/);
        if (match) {
            attachmentIds.add(match[1]);
        }
    });
    
    let presentCount = 0;
    let notPresentCount = 0;
    let naCount = 0;
    
    console.log('Found unique attachment IDs:', Array.from(attachmentIds));
    
    // Count each attachment's status
    attachmentIds.forEach(attachmentId => {
        const radios = document.querySelectorAll(`input[name="tev_attachment_status_value[${attachmentId}]"]`);
        
        radios.forEach(radio => {
            if (radio.checked) {
                const value = radio.value;
                console.log('Checked radio:', { attachmentId, value });
                
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
    
    const total = attachmentIds.size;
    
    const presentCountEl = document.getElementById('tev-present-count');
    const notPresentCountEl = document.getElementById('tev-not-present-count');
    const naCountEl = document.getElementById('tev-na-count');
    const totalCountEl = document.getElementById('tev-total-count');
    
    if (presentCountEl) presentCountEl.textContent = `Present: ${presentCount}`;
    if (notPresentCountEl) notPresentCountEl.textContent = `Not Present: ${notPresentCount}`;
    if (naCountEl) naCountEl.textContent = `N/A: ${naCount}`;
    if (totalCountEl) totalCountEl.textContent = `Total: ${total}`;
    
    console.log('TEV Summary updated:', { presentCount, notPresentCount, naCount, total });
}

// Bulk action buttons
function markAllTEVAsPresent() {
    console.log('markAllTEVAsPresent called');
    
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
}

function markAllTEVAsNA() {
    console.log('markAllTEVAsNA called');
    
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
}
// Uncheck all TEV attachments (set to N/A)
function uncheckAllTEV() {
    console.log('uncheckAllTEV called');
    markAllTEVAsNA(); // This does the same thing
}


// Function to handle modality change
// ✅ FIXED: Handle modality change with AJAX call to backend
async function handleModalityChange() {
    const modalitySelect = document.getElementById('tev_modality_select');
    const selectedModality = modalitySelect.value;
    const dvRequestId = "{{ $dvRequest->id }}";
    
    console.log('Modality changed to:', selectedModality);
    
    if (!selectedModality) {
        // If no modality selected, show the default message
        const container = document.getElementById('tev-attachments-container');
        container.innerHTML = `
            <div class="bg-gray-100 border border-gray-300 rounded-lg p-4">
                <p class="text-sm text-gray-600 italic">
                    Please select a modality above to view required attachments.
                </p>
            </div>
        `;
        
        // Hide bulk action buttons
        const bulkActionButtons = document.querySelector('.flex.items-center.space-x-3');
        if (bulkActionButtons) {
            bulkActionButtons.style.display = 'none';
        }
        
        return;
    }
    
    // ✅ CRITICAL: Call backend to change modality and reload page
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
            
            // Reload the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            console.log('Modality unchanged or no reload needed');
            // Page already has correct attachments, no need to reload
        }
        
    } catch (error) {
        console.error('Error changing modality:', error);
        showMessage('Error changing modality. Please refresh the page.', 'error');
    }
}

// ✅ FIXED: Function to render TEV attachments - USES CORRECT NAME ATTRIBUTE
function renderTEVAttachmentsFromNames(attachmentNames, modality) {
    const container = document.getElementById('tev-attachments-container');
    if (!container) return;
    
    // Check if user can edit (based on status)
    const isEditable = {{ $dvRequest->status === 'Incoming-TEV_received' ? 'true' : 'false' }};
    
    let attachmentsHTML = `
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <p class="text-sm text-blue-800">
                <strong>Verification Responsibility:</strong> 
                <span class="font-semibold">Incoming-TEV</span> verifies all physical TEV attachments.
                Found ${attachmentNames.length} required attachments for this modality.
            </p>
        </div>

        <div class="space-y-3">
    `;
    
    attachmentNames.forEach((attachmentName, index) => {
        // ✅ CRITICAL FIX: Create a unique but consistent ID for form submission
        const safeName = attachmentName.replace(/[^a-zA-Z0-9]/g, '_').toLowerCase();
        const attachmentId = `${modality.replace(/\s+/g, '_')}_${safeName}_${index}`;
        
        attachmentsHTML += `
            <div class="border rounded-lg p-4 transition-all duration-200 tev-attachment-item bg-gray-50 border-gray-200"
                data-attachment-id="${attachmentId}">
                
                <!-- Document Name & Status Row -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <label class="text-sm font-medium text-gray-900">
                            ${attachmentName}
                        </label>
                    </div>
                    
                    <!-- Status Selection - RADIO BUTTONS -->
                    <div class="flex items-center space-x-2" id="tev-status-group-${attachmentId}">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" 
                                   name="tev_attachment_status_value[${attachmentId}]" 
                                   value="1"
                                   class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500 tev-attachment-radio present-radio"
                                   data-attachment-id="${attachmentId}"
                                   ${isEditable ? '' : 'disabled'}
                                   onchange="handleTEVStatusRadioChange(this)">
                            <span class="ml-1.5 text-xs font-medium text-green-700">Present</span>
                        </label>
                        
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" 
                                   name="tev_attachment_status_value[${attachmentId}]" 
                                   value="0"
                                   class="h-4 w-4 text-red-600 border-gray-300 focus:ring-red-500 tev-attachment-radio not-present-radio"
                                   data-attachment-id="${attachmentId}"
                                   ${isEditable ? '' : 'disabled'}
                                   onchange="handleTEVStatusRadioChange(this)">
                            <span class="ml-1.5 text-xs font-medium text-red-700">Not Present</span>
                        </label>
                        
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" 
                                   name="tev_attachment_status_value[${attachmentId}]" 
                                   value="null"
                                   class="h-4 w-4 text-gray-600 border-gray-300 focus:ring-gray-500 tev-attachment-radio na-radio"
                                   data-attachment-id="${attachmentId}"
                                   checked
                                   ${isEditable ? '' : 'disabled'}
                                   onchange="handleTEVStatusRadioChange(this)">
                            <span class="ml-1.5 text-xs font-medium text-gray-700">N/A</span>
                        </label>
                    </div>
                </div>
                
                <!-- Remarks/Lacking Input -->
                <div class="tev-remarks-container" id="tev-remarks-container-${attachmentId}">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Remarks
                    </label>
                    <textarea 
                        name="tev_attachment_remarks[${attachmentId}]"
                        id="tev-remarks-${attachmentId}"
                        rows="2"
                        class="w-full px-3 py-2 text-xs border border-gray-300 bg-gray-50 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"
                        placeholder="Not applicable"
                        readonly
                        maxlength="500">Not applicable</textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        This document is not applicable for this request.
                    </p>
                </div>
            </div>
        `;
    });
    
    attachmentsHTML += `
        </div>
        
        <!-- Summary -->
        <div class="mt-4 flex items-center justify-between text-sm p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center space-x-4">
                <span class="flex items-center">
                    <span class="w-3 h-3 bg-green-500 rounded-full mr-1"></span>
                    <span class="text-green-700 font-medium" id="tev-present-count">
                        Present: 0
                    </span>
                </span>
                <span class="flex items-center">
                    <span class="w-3 h-3 bg-red-500 rounded-full mr-1"></span>
                    <span class="text-red-700 font-medium" id="tev-not-present-count">
                        Not Present: 0
                    </span>
                </span>
                <span class="flex items-center">
                    <span class="w-3 h-3 bg-gray-400 rounded-full mr-1"></span>
                    <span class="text-gray-700 font-medium" id="tev-na-count">
                        N/A: ${attachmentNames.length}
                    </span>
                </span>
            </div>
            <span class="text-gray-600 font-medium" id="tev-total-count">
                Total: ${attachmentNames.length}
            </span>
        </div>
        
        <p class="text-xs text-gray-500 mt-3">
            <strong>Note:</strong> All documents default to "N/A". Mark as "Present" or "Not Present" as needed. 
            When marked as "Present", a default remark "The attachment is present" will be automatically added.
            Use "Uncheck All" to reset all to N/A.
        </p>
    `;
    
    container.innerHTML = attachmentsHTML;
    
    // Update bulk action buttons visibility
    const bulkActionButtons = document.querySelector('.flex.items-center.space-x-3');
    if (bulkActionButtons) {
        if (isEditable && attachmentNames.length > 0) {
            bulkActionButtons.style.display = 'flex';
        } else {
            bulkActionButtons.style.display = 'none';
        }
    }
    
    // Initialize the attachment summary
    setTimeout(() => {
        updateTEVAttachmentSummary();
    }, 100);
}

// Update character count
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

// Open reject modal
function openRejectModal() {
    console.log('🔴 openRejectModal called for Incoming-TEV');
    
    currentRejectId = {{ $dvRequest->id }};
    const modal = document.getElementById('rejectModal');
    
    modal.classList.remove('hidden');
    document.getElementById('rejectRemarks').value = '';
    document.getElementById('remarksError').classList.add('hidden');
    document.body.style.overflow = 'hidden';
    updateCharCount();
    
    console.log('🔴 Modal opened for DV Request ID:', currentRejectId);
}

// Close reject modal
function closeRejectModal() {
    console.log('🟢 closeRejectModal called');
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectRemarks').value = '';
    document.getElementById('remarksError').classList.add('hidden');
    currentRejectId = null;
    document.body.style.overflow = 'auto';
}

document.addEventListener('DOMContentLoaded', () => {
    const rejectForm = document.getElementById('rejectForm');
    const dvRequestId = "{{ $dvRequest->id }}";
    
    // Initialize character count
    updateCharCount();
    
    // Initialize TEV attachment summary if there are initial attachments
    updateTEVAttachmentSummary();
    
    // Handle reject form submission
    if (rejectForm) {
        rejectForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('🟡 Reject form submitted for Incoming-TEV');
            
            const remarks = document.getElementById('rejectRemarks').value.trim();
            const remarksError = document.getElementById('remarksError');
            
            // Validation
            if (!remarks || remarks.length < 10) {
                remarksError.classList.remove('hidden');
                document.getElementById('rejectRemarks').focus();
                return;
            }
            
            remarksError.classList.add('hidden');
            
            // Show loading state
            const confirmBtn = document.getElementById('confirmRejectBtn');
            const btnText = document.getElementById('rejectBtnText');
            const spinner = document.getElementById('rejectSpinner');
            
            confirmBtn.disabled = true;
            btnText.textContent = 'Rejecting...';
            spinner.classList.remove('hidden');
            
            try {
                console.log('🟡 Sending reject request for ID:', currentRejectId);
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
                console.log('🟡 Reject response:', data);
                
                if (data.success) {
                    showMessage(data.message || 'DV request has been rejected successfully.', 'success');
                    closeRejectModal();
                    
                    // Release lock and redirect after short delay
                    setTimeout(() => {
                        releaseLock(new Event('click'), currentRejectId);
                    }, 1500);
                } else {
                    showMessage(data.message || 'Failed to reject the request.', 'error');
                    confirmBtn.disabled = false;
                    btnText.textContent = 'Reject Request';
                    spinner.classList.add('hidden');
                }
            } catch (error) {
                console.error('❌ Reject error:', error);
                showMessage('An error occurred while rejecting the request.', 'error');
                confirmBtn.disabled = false;
                btnText.textContent = 'Reject Request';
                spinner.classList.add('hidden');
            }
        });
    }

    // Close modal when clicking outside
    const rejectModal = document.getElementById('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && rejectModal && !rejectModal.classList.contains('hidden')) {
            closeRejectModal();
        }
    });

    // 🔄 Auto-refresh lock every 2 minutes
    const refreshInterval = setInterval(() => {
        fetch(`/Incoming/TEV/dvrequests/${dvRequestId}/refresh-lock`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log("🔒 Lock refreshed at:", data.locked_at);
            } else {
                console.warn("⚠️ Lock not refreshed:", data.error);
            }
        })
        .catch(err => console.error("Refresh lock failed:", err));
    }, 120000); // every 2 minutes

    // 🧹 Release lock when user leaves the page
    window.addEventListener("beforeunload", () => {
        navigator.sendBeacon(`/Incoming/TEV/dvrequests/${dvRequestId}/release-lock`, 
            JSON.stringify({ _token: "{{ csrf_token() }}" }));
    });

    // Form submit validation
    const form = document.querySelector('form[action*="verify"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('=== TEV FORM SUBMISSION DEBUG ===');
            
            // Check modality
            const modalitySelect = document.getElementById('tev_modality_select');
            if (modalitySelect && !modalitySelect.value) {
                e.preventDefault();
                alert('Please select a TEV modality first!');
                modalitySelect.focus();
                return false;
            }
            
            // Check all hidden inputs
            const allHiddenInputs = document.querySelectorAll('input[name^="tev_attachment_status_value"]');
            console.log('Total hidden inputs:', allHiddenInputs.length);
            
            let hasSelection = false;
            let debugInfo = [];
            
            allHiddenInputs.forEach(input => {
                const value = input.value;
                debugInfo.push({
                    name: input.name,
                    value: value,
                    hasValue: value !== ''
                });
                
                if (value !== '') {
                    hasSelection = true;
                }
            });
            
            console.log('Hidden inputs debug:', debugInfo);
            
            if (!hasSelection) {
                e.preventDefault();
                alert('Please mark at least one attachment status (Present, Not Present, or N/A) before verifying.');
                return false;
            }
            
            console.log('Form validation passed, submitting...');
            return true;
        });
    }
});

// Function to show toast messages
function showMessage(message, type = 'success') {
    const container = document.getElementById('message-container');
    if (!container) return;
    
    const messageDiv = document.createElement('div');
    
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    
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
                    class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                Verify
            </button>
            <button type="button"
                    onclick="openRejectModal()"
                    class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                Reject
            </button>
            <a href="{{ route('Incoming.TEV.index') }}"
               class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
               onclick="releaseLock(event, ${dvRequestId})">
                Back
            </a>
        </div>
    `;
}
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action*="verify"]');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('=== FORM SUBMISSION DEBUG ===');
            
            // Prevent default to check data first
            e.preventDefault();
            
            // Get all form data
            const formData = new FormData(form);
            const formObject = {};
            
            formData.forEach((value, key) => {
                if (formObject[key]) {
                    if (Array.isArray(formObject[key])) {
                        formObject[key].push(value);
                    } else {
                        formObject[key] = [formObject[key], value];
                    }
                } else {
                    formObject[key] = value;
                }
            });
            
            console.log('Complete form data:', formObject);
            
            // Specifically check attachment data
            const attachmentStatus = {};
            const attachmentRemarks = {};
            
            for (let key in formObject) {
                if (key.startsWith('tev_attachment_status_value')) {
                    attachmentStatus[key] = formObject[key];
                }
                if (key.startsWith('tev_attachment_remarks')) {
                    attachmentRemarks[key] = formObject[key];
                }
            }
            
            console.log('Attachment Status Values:', attachmentStatus);
            console.log('Attachment Remarks:', attachmentRemarks);
            
            // Count statistics
            let statusCount = Object.keys(attachmentStatus).length;
            let presentCount = Object.values(attachmentStatus).filter(v => v === '1').length;
            let notPresentCount = Object.values(attachmentStatus).filter(v => v === '0').length;
            let naCount = Object.values(attachmentStatus).filter(v => v === 'null' || v === '').length;
            
            console.log('Form Statistics:', {
                total_attachments: statusCount,
                present: presentCount,
                not_present: notPresentCount,
                na: naCount
            });
            
            // Validation
            if (!formObject.tev_modality) {
                alert('Please select a TEV modality first!');
                return false;
            }
            
            if (statusCount === 0) {
                alert('No attachment data found! Please check the form.');
                return false;
            }
            
            // Show confirmation dialog
            const confirmMsg = `Ready to submit:\n\n` +
                `Modality: ${formObject.tev_modality}\n` +
                `Present: ${presentCount}\n` +
                `Not Present: ${notPresentCount}\n` +
                `N/A: ${naCount}\n\n` +
                `Continue with submission?`;
            
            if (confirm(confirmMsg)) {
                console.log('✅ User confirmed, submitting form...');
                form.submit(); // Actually submit the form
            } else {
                console.log('❌ User cancelled submission');
            }
        });
    }
});
function releaseLock(event, id) {
    event.preventDefault(); 

    fetch(`/Incoming/TEV/dvrequests/${id}/release-lock`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json",
            "Content-Type": "application/json"
        }
    }).then(() => {
        window.location.href = "{{ route('Incoming.TEV.index') }}";
    }).catch(err => {
        console.error("Release lock failed:", err);
        window.location.href = "{{ route('Incoming.TEV.index') }}";
    });
}
</script>