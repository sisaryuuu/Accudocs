@extends('layouts.app')

@section('title', 'Verify DV Request - TUPAD')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Section - Incoming Form -->
        <div class="lg:col-span-2 bg-white shadow rounded-xl p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Incoming - TUPAD</h1>

            <form action="{{ route('Incoming.TUPAD.dvrequests.verify', $dvRequest->id) }}" method="POST" class="space-y-6">
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

               <!-- TUPAD Attachments Checklist -->
<div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">
            📋 TUPAD Attachments Verification
        </h3>
        
        <!-- Bulk Action Buttons -->
        @if($dvRequest->status === 'Incoming-TUPAD_received' && $tupadSelectedModality && $tupadAttachments && $tupadAttachments->count() > 0)
            <div class="flex items-center space-x-3">
                <button type="button" 
                        onclick="markAllTUPADAsPresent()"
                        class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    All Present
                </button>
                <button type="button" 
                        onclick="markAllTUPADAsNA()"
                        class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    All N/A
                </button>
                <button type="button" 
                        onclick="uncheckAllTUPAD()"
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
            Select TUPAD Modality <span class="text-red-500">*</span>
        </label>
      <select name="tupad_modality" 
        id="tupad_modality_select"
        required 
        {{ $dvRequest->status !== 'Incoming-TUPAD_received' ? 'disabled' : '' }}
        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring focus:ring-blue-200 text-sm"
        onchange="handleModalityChange()">
    <option value="">-- Select Modality --</option>
    <option value="rpmt" {{ $tupadSelectedModality == 'rpmt' ? 'selected' : '' }}>
        RPMT (Direct Administration)
    </option>
    <option value="lgu" {{ $tupadSelectedModality == 'lgu' ? 'selected' : '' }}>
        Local Government Unit (LGU)
    </option>
    <option value="ngo_po" {{ $tupadSelectedModality == 'ngo_po' ? 'selected' : '' }}>
        NGO/People's Organizations
    </option>
    <option value="money_remittance" {{ $tupadSelectedModality == 'money_remittance' ? 'selected' : '' }}>
        Money Remittance
    </option>
    <option value="individual_beneficiaries" {{ $tupadSelectedModality == 'individual_beneficiaries' ? 'selected' : '' }}>
        Individual Beneficiaries
    </option>
    <option value="group_beneficiaries" {{ $tupadSelectedModality == 'group_beneficiaries' ? 'selected' : '' }}>
        Group Beneficiaries
    </option>
</select>
        <p class="text-xs text-gray-500 mt-1">Select the TUPAD modality to verify required attachments</p>
    </div>

  <!-- Dynamic Attachments Container -->
<div id="tupad-attachments-container">
    @if($tupadSelectedModality && $tupadAttachments && $tupadAttachments->count() > 0)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <p class="text-sm text-blue-800">
                <strong>Verification Responsibility:</strong> 
                <span class="font-semibold">Incoming-TUPAD</span> verifies all physical TUPAD attachments.
                Found {{ $tupadAttachments->count() }} required attachments for this modality.
            </p>
        </div>

        <div class="space-y-3">
            @foreach($tupadAttachments as $attachment)
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
                
                // Set the checked status for checkboxes
                $presentChecked = ($isPresent === true || $isPresent === 1) ? 'checked' : '';
                $notPresentChecked = ($isPresent === false || $isPresent === 0) ? 'checked' : '';
                $naChecked = (is_null($isPresent) || $isPresent === '' || $isPresent === 'null' || $isPresent === 'na') ? 'checked' : '';
                
                // Make sure only one is checked
                if ($presentChecked && $notPresentChecked) {
                    // If both are checked (shouldn't happen), default to N/A
                    $presentChecked = '';
                    $notPresentChecked = '';
                    $naChecked = 'checked';
                    $statusClass = 'bg-gray-50 border-gray-200';
                    $borderClass = 'border-gray-300';
                    $textClass = 'text-gray-500';
                    $helperText = 'This document is not applicable for this request.';
                    $placeholderText = 'Not applicable';
                }
            @endphp
                
                <div class="border rounded-lg p-4 transition-all duration-200 tupad-attachment-item {{ $statusClass }}"
                    data-attachment-id="{{ $attachment->id }}">
                    
                    <!-- Document Name & Status Row -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <label class="text-sm font-medium text-gray-900">
                                {{ $attachment->name }}
                            </label>
                        </div>
                                
        <div class="flex items-center space-x-2" id="tupad-status-group-{{ $attachment->id }}">
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" 
                    name="tupad_attachment_{{ $attachment->id }}" 
                    value="1"
                    class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500 tupad-attachment-radio present-radio"
                    data-attachment-id="{{ $attachment->id }}"
                    {{ $isPresent === 1 ? 'checked' : '' }}
                    {{ $dvRequest->status === 'Incoming-TUPAD_received' ? '' : 'disabled' }}
                    onchange="handleTUPADStatusRadioChange(this)">
                <span class="ml-1.5 text-xs font-medium text-green-700">Present</span>
            </label>
            
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" 
                    name="tupad_attachment_{{ $attachment->id }}" 
                    value="0"
                    class="h-4 w-4 text-red-600 border-gray-300 focus:ring-red-500 tupad-attachment-radio not-present-radio"
                    data-attachment-id="{{ $attachment->id }}"
                    {{ $isPresent === 0 ? 'checked' : '' }}
                    {{ $dvRequest->status === 'Incoming-TUPAD_received' ? '' : 'disabled' }}
                    onchange="handleTUPADStatusRadioChange(this)">
                <span class="ml-1.5 text-xs font-medium text-red-700">Not Present</span>
            </label>
            
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" 
                    name="tupad_attachment_{{ $attachment->id }}" 
                    value="null"
                    class="h-4 w-4 text-gray-600 border-gray-300 focus:ring-gray-500 tupad-attachment-radio na-radio"
                    data-attachment-id="{{ $attachment->id }}"
                    {{ is_null($isPresent) ? 'checked' : '' }}
                    {{ $dvRequest->status === 'Incoming-TUPAD_received' ? '' : 'disabled' }}
                    onchange="handleTUPADStatusRadioChange(this)">
                <span class="ml-1.5 text-xs font-medium text-gray-700">N/A</span>
            </label>
            
            <!-- Hidden input to store the actual value -->
            <input type="hidden" 
                name="tupad_attachment_status_value[{{ $attachment->id }}]" 
                value="{{ $isPresent === 1 ? '1' : ($isPresent === 0 ? '0' : 'null') }}" 
                id="tupad-status-value-{{ $attachment->id }}">
        </div>
                    </div>
                    
                    <!-- Remarks/Lacking Input -->
                <!-- Remarks/Lacking Input -->
                <div class="tupad-remarks-container" id="tupad-remarks-container-{{ $attachment->id }}">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Remarks
                    </label>
                    <textarea 
                        name="tupad_attachment_remarks[{{ $attachment->id }}]"
                        id="tupad-remarks-{{ $attachment->id }}"
                        rows="2"
                        class="w-full px-3 py-2 text-xs border {{ $borderClass }} rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"
                        placeholder="{{ $placeholderText }}"
                        {{ $dvRequest->status === 'Incoming-TUPAD_received' && $isPresent !== true && $isPresent !== 1 ? '' : 'readonly' }}
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
                    <span class="text-green-700 font-medium" id="tupad-present-count">
                        Present: {{ $tupadAttachments->whereIn('is_present', [true, 1])->count() }}
                    </span>
                </span>
                <span class="flex items-center">
                    <span class="w-3 h-3 bg-red-500 rounded-full mr-1"></span>
                    <span class="text-red-700 font-medium" id="tupad-not-present-count">
                        Not Present: {{ $tupadAttachments->whereIn('is_present', [false, 0])->count() }}
                    </span>
                </span>
                <span class="flex items-center">
                    <span class="w-3 h-3 bg-gray-400 rounded-full mr-1"></span>
                    <span class="text-gray-700 font-medium" id="tupad-na-count">
                        N/A: {{ $tupadAttachments->whereNotIn('is_present', [true, false, 1, 0])->count() }}
                    </span>
                </span>
            </div>
            <span class="text-gray-600 font-medium" id="tupad-total-count">
                Total: {{ $tupadAttachments->count() }}
            </span>
        </div>
        
        <p class="text-xs text-gray-500 mt-3">
            <strong>Note:</strong> All documents default to "N/A". Mark as "Present" or "Not Present" as needed. 
            When marked as "Present", a default remark "The attachment is present" will be automatically added.
            Use "Uncheck All" to reset all to N/A.
        </p>
        @elseif($tupadSelectedModality && $tupadAttachments->count() === 0)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <span class="text-yellow-800">No attachments found for the "{{ $tupadSelectedModality }}" modality.</span>
                </div>
                <p class="text-sm text-yellow-700 mt-2">
                    Please run the TUPAD attachment seeder or contact the system administrator.
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
                @if($dvRequest->status === 'Incoming-TUPAD_received')
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
                        <a href="{{ route('Incoming.TUPAD.index') }}"
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
                        <a href="{{ route('Incoming.TUPAD.index') }}"
                            class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
                            onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
                    </div>
                @else
                    <a href="{{ route('Incoming.TUPAD.index') }}"
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
.tupad-attachment-checkbox {
    cursor: pointer;
}

.tupad-attachment-checkbox:checked {
    background-color: currentColor;
    border-color: currentColor;
}

/* Textarea styling for different statuses */
.tupad-textarea-present {
    border-color: #10b981 !important;
    background-color: #f0fdf4 !important;
}

.tupad-textarea-not-present {
    border-color: #7e2222ff !important;
    background-color: #fef2f2 !important;
}

.tupad-textarea-na {
    border-color: #d1d5db !important;
    background-color: #f9fafb !important;
}
</style>
<div id="message-container" class="fixed top-4 right-4 z-50"></div>
@endsection

<script>
let currentRejectId = null;

// TUPAD attachments data by modality (frontend only)
const tupadAttachmentsByModality = {
    'rpmt': [
        'RPMT',
        'TUPAD Work Program (Annex B)',
        'Profile of TUPAD Beneficiaries (Annex D)',
        'Contract of Service (Annex E-2)',
        'Payroll',
        'Notice to Proceed',
        'Photocopy of Valid IDs'
    ],
    'lgu': [
        'Letter of Intent (Annex F)',
        'RPMT',
        'TUPAD Work Program (Annex B)',
        'Profile of TUPAD Beneficiaries (Annex D)',
        'Contract of Service (Annex E-2)',
        'Notice to Proceed',
        'Photocopy of Valid IDs',
        'Local Development Plan',
        'Sangguniang Bayan (SB) or Board Resolution',
        'Memorandum of Agreement (Annex G)'
    ],
    'ngo_po': [
        'Letter of Intent (Annex F)',
        'RPMT',
        'TUPAD Work Program (Annex B)',
        'Profile of TUPAD Beneficiaries (Annex D)',
        'Contract of Service (Annex E-2)',
        'Notice to Proceed',
        'Photocopy of Valid IDs',
        'Local Development Plan',
        'Sangguniang Bayan (SB) or Board Resolution',
        'Memorandum of Agreement (Annex G)',
        'Audited Financial Reports for the past three (3) years',
        'Work and Financial Plan',
        'List of photographs of similar implemented projects',
        'Sworn Affidavit',
        'Copy of Accredited as Co-Partner (ACP) Certificate',
        'Constitution and By-Laws Authenticated (if registered with DOLE)',
        'Updated list of officers and members (if registered with DOLE)'
    ],
    'money_remittance': [
        'RPMT',
        'TUPAD Work Program (Annex B)',
        'Profile of TUPAD Beneficiaries (Annex D)',
        'Contract of Service (Annex E-2)',
        'TUPAD Payroll',
        'Money Remittance Payroll',
        'Notice to Proceed',
        'Photocopy of Valid IDs',
        'Daily Time Records (DTRs) / Attendance Sheets',
        'TUPAD Completion Report'
    ],
    'individual_beneficiaries': [
        'Letter Of Intent (Annex F)',
        'RPMT',
        'Business Plan',
        'Beneficiary Profile Form (Annex O)',
        'Photocopy of Valid IDs',
        'Proof of Project Readiness'
    ],
    'group_beneficiaries': [
        'RPMT',
        'Beneficiary Profile Form (Annex O)',
        'Notice to Proceed',
        'Project Proposal',
        'Local Development Plan',
        'Sangguiniang Bayan (SB) or Board Resolution',
        'Memorandum of Agreement (Annex G)',
        'Audited Financial Reports for the past three (3) years',
        'Work and Financial Plan',
        'List or photographs of similar implemented projects',
        'Sworn Affidavit',
        'Copy of Accredited as Co-Partner (ACP) Certificate',
        'Constitution and By-Laws Authenticated (if registered from DOLE)',
        'Updated list of officers and members (if registered from DOLE)',
        'Masterlist',
        'Photocopy of Valid IDs'
    ]
};

function updateTUPADAttachmentUI(attachmentId, status, isCleared = false) {
    console.log('updateTUPADAttachmentUI called', { attachmentId, status, isCleared });
    
    const item = document.querySelector(`[data-attachment-id="${attachmentId}"]`);
    if (!item) return;
    
    const remarksContainer = document.getElementById(`tupad-remarks-container-${attachmentId}`);
    const remarksTextarea = document.getElementById(`tupad-remarks-${attachmentId}`);
    const remarksHelper = remarksContainer ? remarksContainer.querySelector('p.text-xs') : null;
    
    // Remove all color classes
    item.classList.remove('bg-green-50', 'border-green-200', 'bg-red-50', 'border-red-200', 'bg-gray-50', 'border-gray-200');
    
    // Update radio button states
    const presentRadio = item.querySelector('.present-radio');
    const notPresentRadio = item.querySelector('.not-present-radio');
    const naRadio = item.querySelector('.na-radio');
    
    // Uncheck all radios first
    if (presentRadio) presentRadio.checked = false;
    if (notPresentRadio) notPresentRadio.checked = false;
    if (naRadio) naRadio.checked = false;
    
    if (status === 1 || status === '1') {
        // Present - Green
        item.classList.add('bg-green-50', 'border-green-200');
        if (presentRadio) presentRadio.checked = true;
        
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
        if (notPresentRadio) notPresentRadio.checked = true;
        
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
        if (naRadio) naRadio.checked = true;
        
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

// Handle status change for TUPAD radio buttons
function handleTUPADStatusRadioChange(radioElement) {
    console.log('handleTUPADStatusRadioChange called', { 
        radio: radioElement,
        value: radioElement.value,
        checked: radioElement.checked,
        attachmentId: radioElement.dataset.attachmentId
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
    let statusValue = '';
    let displayValue = null;
    
    // Convert radio value to stored value
    if (value === '1') {
        statusValue = '1';
        displayValue = 1;
    } else if (value === '0') {
        statusValue = '0';
        displayValue = 0;
    } else if (value === 'null') {
        statusValue = 'null';
        displayValue = null;
    }
    
    // Update the hidden input
    const hiddenInput = document.getElementById(`tupad-status-value-${attachmentId}`);
    if (hiddenInput) {
        hiddenInput.value = statusValue;
        console.log('Hidden input updated:', { 
            id: hiddenInput.id, 
            value: hiddenInput.value 
        });
    } else {
        console.warn('Hidden input not found for attachment:', attachmentId);
    }
    
    // Update UI
    updateTUPADAttachmentUI(attachmentId, displayValue);
    
    // Update summary
    updateTUPADAttachmentSummary();
}
// Mark all TUPAD attachments as present
function markAllTUPADAsPresent() {
    console.log('markAllTUPADAsPresent called');
    
    const attachmentItems = document.querySelectorAll('.tupad-attachment-item');
    
    attachmentItems.forEach(item => {
        const attachmentId = item.dataset.attachmentId;
        if (!attachmentId) return;
        
        const presentRadio = item.querySelector('.present-radio');
        if (presentRadio && !presentRadio.disabled) {
            presentRadio.checked = true;
            // Manually trigger the change event
            const event = new Event('change');
            presentRadio.dispatchEvent(event);
        }
    });
    
    showMessage('All TUPAD attachments marked as Present', 'success');
    updateTUPADAttachmentSummary();
}

// Mark all TUPAD attachments as N/A
function markAllTUPADAsNA() {
    console.log('markAllTUPADAsNA called');
    
    const attachmentItems = document.querySelectorAll('.tupad-attachment-item');
    
    attachmentItems.forEach(item => {
        const attachmentId = item.dataset.attachmentId;
        if (!attachmentId) return;
        
        const naRadio = item.querySelector('.na-radio');
        if (naRadio && !naRadio.disabled) {
            naRadio.checked = true;
            // Manually trigger the change event
            const event = new Event('change');
            naRadio.dispatchEvent(event);
        }
    });
    
    showMessage('All TUPAD attachments marked as N/A', 'success');
    updateTUPADAttachmentSummary();
}

// Uncheck all TUPAD attachments (set to N/A)
function uncheckAllTUPAD() {
    console.log('uncheckAllTUPAD called');
    markAllTUPADAsNA(); // This does the same thing
}

// Mark all TUPAD attachments as N/A
function markAllTUPADAsNA() {
    console.log('markAllTUPADAsNA called');
    
    const attachmentItems = document.querySelectorAll('.tupad-attachment-item');
    
    attachmentItems.forEach(item => {
        const attachmentId = item.dataset.attachmentId;
        if (!attachmentId) return;
        
        const naRadio = item.querySelector('.na-radio');
        if (naRadio && !naRadio.disabled) {
            naRadio.checked = true;
            naRadio.dispatchEvent(new Event('change'));
        }
    });
    
    showMessage('All TUPAD attachments marked as N/A', 'success');
    updateTUPADAttachmentSummary();
}
function updateTUPADAttachmentSummary() {
    console.log('updateTUPADAttachmentSummary called');
    
    // Get ALL hidden inputs that contain status values
    const allHiddenInputs = document.querySelectorAll('input[name^="tupad_attachment_status_value"]');
    let presentCount = 0;
    let notPresentCount = 0;
    let naCount = 0;
    let unsetCount = 0;
    
    console.log('Found hidden inputs:', allHiddenInputs.length);
    
    allHiddenInputs.forEach(input => {
        const value = input.value;
        console.log('Hidden input:', { name: input.name, value: value });
        
        if (value === '1') {
            presentCount++;
        } else if (value === '0') {
            notPresentCount++;
        } else if (value === 'null' || value === '') {
            naCount++; // Count empty as N/A too
        } else {
            unsetCount++;
            naCount++; // Count unset as N/A
        }
    });
    
    const total = allHiddenInputs.length;
    
    const presentCountEl = document.getElementById('tupad-present-count');
    const notPresentCountEl = document.getElementById('tupad-not-present-count');
    const naCountEl = document.getElementById('tupad-na-count');
    const totalCountEl = document.getElementById('tupad-total-count');
    
    if (presentCountEl) presentCountEl.textContent = `Present: ${presentCount}`;
    if (notPresentCountEl) notPresentCountEl.textContent = `Not Present: ${notPresentCount}`;
    if (naCountEl) naCountEl.textContent = `N/A: ${naCount}`;
    if (totalCountEl) totalCountEl.textContent = `Total: ${total}`;
    
    console.log('TUPAD Summary updated:', { presentCount, notPresentCount, naCount, unsetCount, total });
}
// ✅ FIXED: Handle modality change with correct route
async function handleModalityChange() {
    const modalitySelect = document.getElementById('tupad_modality_select');
    const selectedModality = modalitySelect.value;
    const dvRequestId = "{{ $dvRequest->id }}";
    
    console.log('TUPAD Modality changed to:', selectedModality);
    
    if (!selectedModality) {
        // Show default message
        const container = document.getElementById('tupad-attachments-container');
        container.innerHTML = `
            <div class="bg-gray-100 border border-gray-300 rounded-lg p-4">
                <p class="text-sm text-gray-600 italic">
                    Please select a modality above to view required attachments.
                </p>
            </div>
        `;
        
        const bulkActionButtons = document.querySelector('.flex.items-center.space-x-3');
        if (bulkActionButtons) {
            bulkActionButtons.style.display = 'none';
        }
        
        return;
    }
    
    try {
        showMessage('Changing modality...', 'info');
        
        // ✅ CORRECT: This will generate /Incoming/TUPAD/dvrequests/{id}/change-modality
        const url = "{{ route('Incoming.TUPAD.dvrequests.changeModality', ':id') }}".replace(':id', dvRequestId);
        
        console.log('Calling URL:', url);
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tupad_modality: selectedModality
            })
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Response error:', {
                status: response.status,
                statusText: response.statusText,
                body: errorText
            });
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Response data:', data);
        
        if (data.success && data.reload_required) {
            showMessage('Modality changed! Reloading page...', 'success');
            
            // Reload the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else if (data.success) {
            showMessage('Modality set successfully', 'success');
            // Reload anyway to ensure UI is in sync
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            showMessage(data.message || 'Failed to change modality', 'error');
        }
        
    } catch (error) {
        console.error('Error changing modality:', error);
        showMessage('Error changing modality: ' + error.message, 'error');
        
        // Reload page anyway to reset the form
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }
}
// Function to render TUPAD attachments from names (frontend only)
function renderTUPADAttachmentsFromNames(attachmentNames, modality) {
    const container = document.getElementById('tupad-attachments-container');
    if (!container) return;
    
    // Check if user can edit (based on status)
    const isEditable = {{ $dvRequest->status === 'Incoming-TUPAD_received' ? 'true' : 'false' }};
    
    let attachmentsHTML = `
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <p class="text-sm text-blue-800">
                <strong>Verification Responsibility:</strong> 
                <span class="font-semibold">Incoming-TUPAD</span> verifies all physical TUPAD attachments.
                Found ${attachmentNames.length} required attachments for this modality.
            </p>
        </div>

        <div class="space-y-3">
    `;
    
    attachmentNames.forEach((attachmentName, index) => {
        // Create a safe ID
        const safeName = attachmentName.replace(/[^a-zA-Z0-9]/g, '_').toLowerCase();
        const attachmentId = `${modality}_${safeName}_${index}`;
        
        attachmentsHTML += `
            <div class="border rounded-lg p-4 transition-all duration-200 tupad-attachment-item bg-gray-50 border-gray-200"
                data-attachment-id="${attachmentId}">
                
                <!-- Document Name & Status Row -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <label class="text-sm font-medium text-gray-900">
                            ${attachmentName}
                        </label>
                    </div>
                    
                    <!-- Status Selection - RADIO BUTTONS -->
                    <div class="flex items-center space-x-2" id="tupad-status-group-${attachmentId}">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" 
                                   name="tupad_attachment_${attachmentId}" 
                                   value="1"
                                   class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500 tupad-attachment-radio present-radio"
                                   data-attachment-id="${attachmentId}"
                                   ${isEditable ? '' : 'disabled'}
                                   onchange="handleTUPADStatusRadioChange(this)">
                            <span class="ml-1.5 text-xs font-medium text-green-700">Present</span>
                        </label>
                        
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" 
                                   name="tupad_attachment_${attachmentId}" 
                                   value="0"
                                   class="h-4 w-4 text-red-600 border-gray-300 focus:ring-red-500 tupad-attachment-radio not-present-radio"
                                   data-attachment-id="${attachmentId}"
                                   ${isEditable ? '' : 'disabled'}
                                   onchange="handleTUPADStatusRadioChange(this)">
                            <span class="ml-1.5 text-xs font-medium text-red-700">Not Present</span>
                        </label>
                        
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" 
                                   name="tupad_attachment_${attachmentId}" 
                                   value="null"
                                   class="h-4 w-4 text-gray-600 border-gray-300 focus:ring-gray-500 tupad-attachment-radio na-radio"
                                   data-attachment-id="${attachmentId}"
                                   checked
                                   ${isEditable ? '' : 'disabled'}
                                   onchange="handleTUPADStatusRadioChange(this)">
                            <span class="ml-1.5 text-xs font-medium text-gray-700">N/A</span>
                        </label>
                        
                        <!-- Hidden input to store the actual value -->
                        <input type="hidden" 
                               name="tupad_attachment_status_value[${attachmentId}]" 
                               value="null" 
                               id="tupad-status-value-${attachmentId}">
                    </div>
                </div>
                
                <!-- Remarks/Lacking Input -->
                <div class="tupad-remarks-container" id="tupad-remarks-container-${attachmentId}">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Remarks
                    </label>
                    <textarea 
                        name="tupad_attachment_remarks[${attachmentId}]"
                        id="tupad-remarks-${attachmentId}"
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
                    <span class="text-green-700 font-medium" id="tupad-present-count">
                        Present: 0
                    </span>
                </span>
                <span class="flex items-center">
                    <span class="w-3 h-3 bg-red-500 rounded-full mr-1"></span>
                    <span class="text-red-700 font-medium" id="tupad-not-present-count">
                        Not Present: 0
                    </span>
                </span>
                <span class="flex items-center">
                    <span class="w-3 h-3 bg-gray-400 rounded-full mr-1"></span>
                    <span class="text-gray-700 font-medium" id="tupad-na-count">
                        N/A: ${attachmentNames.length}
                    </span>
                </span>
            </div>
            <span class="text-gray-600 font-medium" id="tupad-total-count">
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
        updateTUPADAttachmentSummary();
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
    console.log('🔴 openRejectModal called for Incoming-TUPAD');
    
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
    
    // Initialize TUPAD attachment summary if there are initial attachments
    updateTUPADAttachmentSummary();
    
    // Handle reject form submission
    if (rejectForm) {
        rejectForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('🟡 Reject form submitted for Incoming-TUPAD');
            
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
                const response = await fetch(`/Incoming/TUPAD/dvrequests/${currentRejectId}/reject`, {
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
        fetch(`/Incoming/TUPAD/dvrequests/${dvRequestId}/refresh-lock`, {
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
        navigator.sendBeacon(`/Incoming/TUPAD/dvrequests/${dvRequestId}/release-lock`, 
            JSON.stringify({ _token: "{{ csrf_token() }}" }));
    });

    // Form submit validation
    const form = document.querySelector('form[action*="verify"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('=== TUPAD FORM SUBMISSION DEBUG ===');
            
            // Check modality
            const modalitySelect = document.getElementById('tupad_modality_select');
            if (modalitySelect && !modalitySelect.value) {
                e.preventDefault();
                alert('Please select a TUPAD modality first!');
                modalitySelect.focus();
                return false;
            }
            
            // Check all hidden inputs
            const allHiddenInputs = document.querySelectorAll('input[name^="tupad_attachment_status_value"]');
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
        
        const response = await fetch(`/Incoming/TUPAD/dvrequests/${dvRequestId}/receive`, {
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
            <a href="{{ route('Incoming.TUPAD.index') }}"
               class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
               onclick="releaseLock(event, ${dvRequestId})">
                Back
            </a>
        </div>
    `;
}

document.addEventListener("DOMContentLoaded", () => {
    const dvRequestId = "{{ $dvRequest->id }}";

    // 🔄 Auto-refresh lock every 2 minutes
    const refreshInterval = setInterval(() => {
        fetch(`/Incoming/TUPAD/dvrequests/${dvRequestId}/refresh-lock`, {
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
    }, 120000); // every 2 minutes (120,000 ms)

    // 🧹 Release lock when user leaves the page
    window.addEventListener("beforeunload", () => {
        navigator.sendBeacon(`/Incoming/TUPAD/dvrequests/${dvRequestId}/release-lock`, 
            JSON.stringify({ _token: "{{ csrf_token() }}" }));
    });
});



// Add form submit debugging
// Add form submit debugging
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action*="verify"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('TUPAD Form submitted - checking data...');
            
            // Check modality
            const modalitySelect = document.getElementById('tupad_modality_select');
            if (modalitySelect && !modalitySelect.value) {
                e.preventDefault();
                showMessage('Please select a TUPAD modality first!', 'error');
                modalitySelect.focus();
                return false;
            }
            
            // Check all hidden inputs for status values
            const allHiddenInputs = document.querySelectorAll('input[name^="tupad_attachment_status_value"]');
            console.log('Total hidden inputs:', allHiddenInputs.length);
            
            let hasEmptyValues = false;
            let debugInfo = [];
            
            allHiddenInputs.forEach(input => {
                const value = input.value;
                debugInfo.push({
                    name: input.name,
                    value: value,
                    hasValue: value !== ''
                });
                
                if (value === '') {
                    hasEmptyValues = true;
                    console.warn('EMPTY VALUE FOUND:', input.name);
                }
            });
            
            console.log('Hidden inputs debug:', debugInfo);
            
            if (hasEmptyValues) {
                e.preventDefault();
                showMessage('Some attachments do not have status values. Please ensure all attachments are marked as Present, Not Present, or N/A.', 'error');
                return false;
            }
            
            // Also check that at least one radio button is checked per group
            const attachmentItems = document.querySelectorAll('.tupad-attachment-item');
            let hasUncheckedGroups = false;
            
            attachmentItems.forEach(item => {
                const attachmentId = item.dataset.attachmentId;
                const radios = item.querySelectorAll('.tupad-attachment-radio');
                let hasCheckedRadio = false;
                
                radios.forEach(radio => {
                    if (radio.checked) hasCheckedRadio = true;
                });
                
                if (!hasCheckedRadio) {
                    hasUncheckedGroups = true;
                    console.warn('No radio checked for attachment:', attachmentId);
                }
            });
            
            if (hasUncheckedGroups) {
                e.preventDefault();
                showMessage('Please select a status (Present, Not Present, or N/A) for all attachments.', 'error');
                return false;
            }
            
            console.log('Form validation passed, submitting...');
            return true;
        });
    }
});

function releaseLock(event, id) {
    event.preventDefault(); 

    fetch(`/Incoming/TUPAD/dvrequests/${id}/release-lock`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json",
            "Content-Type": "application/json"
        }
    }).then(() => {
        window.location.href = "{{ route('Incoming.TUPAD.index') }}";
    }).catch(err => {
        console.error("Release lock failed:", err);
        window.location.href = "{{ route('Incoming.TUPAD.index') }}";
    });
}
</script>