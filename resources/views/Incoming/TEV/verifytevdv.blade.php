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

                <!-- TEV Attachments Checklist -->
                <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            📋 TEV Attachments Verification
                        </h3>
                        
                        <!-- Check All/Uncheck All Buttons -->
                        <div id="bulk-action-buttons" class="flex items-center space-x-3 hidden">
                            <button type="button" 
                                    onclick="checkAllTEVAttachments()"
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Check All
                            </button>
                            <button type="button" 
                                    onclick="uncheckAllTEVAttachments()"
                                    class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Uncheck All
                            </button>
                        </div>
                    </div>

                    <!-- Modality Selection -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select TEV Modality <span class="text-red-500">*</span>
                        </label>
                        <select name="tev_modality" required 
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:ring focus:ring-blue-200 text-sm">
                            <option value="">-- Select Modality --</option>
                            <option value="Cash Advance for Local Travel" {{ $tevSelectedModality == 'Cash Advance for Local Travel' ? 'selected' : '' }}>Cash Advance for Local Travel</option>
                            <option value="Liquidation of Cash Advances" {{ $tevSelectedModality == 'Liquidation of Cash Advances' ? 'selected' : '' }}>Liquidation of Cash Advances</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Select the TEV modality to verify required attachments</p>
                    </div>

                    <!-- Dynamic Attachments Container -->
                    <div id="dynamic-attachments-container">
                        @if($tevSelectedModality && $tevAttachments && $tevAttachments->count() > 0)
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                                <p class="text-sm text-blue-800">
                                    <strong>Verification Responsibility:</strong> 
                                    <span class="font-semibold">Incoming-TEV</span> verifies all physical TEV attachments.
                                    Found {{ $tevAttachments->count() }} required attachments for this modality.
                                </p>
                            </div>
                    
                            <ul class="space-y-2">
                                @foreach($tevAttachments as $item)
                                    <li class="flex items-center p-3 rounded-lg transition-all duration-200 
                                        {{ $item->is_present ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                                        <input type="checkbox" 
                                            name="tev_attachments[{{ $item->id }}]" 
                                            value="1"
                                            id="tev_attachment_{{ $item->id }}"
                                            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3 attachment-checkbox"
                                            {{ $item->is_present ? 'checked' : '' }}>
                                        <label for="tev_attachment_{{ $item->id }}" class="text-sm text-gray-900 cursor-pointer flex-1">
                                            {{ $item->name }}
                                        </label>
                                        <span class="text-xs font-medium px-2 py-1 rounded-full 
                                            {{ $item->is_present ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $item->is_present ? '✓ Present' : '✗ Missing' }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                            
                            <!-- Summary -->
                            <div class="mt-4 flex items-center justify-between text-sm">
                                <div class="flex items-center space-x-4">
                                    <span class="flex items-center">
                                        <span class="w-3 h-3 bg-green-500 rounded-full mr-1"></span>
                                        <span class="text-green-700" id="present-count">Present: {{ $tevAttachments->where('is_present', true)->count() }}</span>
                                    </span>
                                    <span class="flex items-center">
                                        <span class="w-3 h-3 bg-red-500 rounded-full mr-1"></span>
                                        <span class="text-red-700" id="missing-count">Missing: {{ $tevAttachments->where('is_present', false)->count() }}</span>
                                    </span>
                                </div>
                                <span class="text-gray-600" id="total-count">Total: {{ $tevAttachments->count() }}</span>
                            </div>
                            
                            <p class="text-xs text-gray-500 mt-4">
                                <strong>Note:</strong> Check all physical documents that are present. 
                                All attachments are required for TEV verification.
                            </p>
                        @elseif($tevSelectedModality && $tevAttachments->count() === 0)
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <p class="text-sm text-yellow-800">
                                    <strong>No attachments found!</strong> 
                                    There are no attachments configured for the "{{ $tevSelectedModality }}" modality.
                                    Please run the TEV attachment seeder or contact administrator.
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
</style>
<div id="message-container" class="fixed top-4 right-4 z-50"></div>
@endsection

<script>
let currentRejectId = null;

// Check All TEV Attachments
function checkAllTEVAttachments() {
    const checkboxes = document.querySelectorAll('#dynamic-attachments-container .attachment-checkbox');
    checkboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.checked = true;
            // Trigger change event to update UI
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    showMessage('All TEV attachments marked as present', 'success');
}

// Uncheck All TEV Attachments
function uncheckAllTEVAttachments() {
    const checkboxes = document.querySelectorAll('#dynamic-attachments-container .attachment-checkbox');
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            checkbox.checked = false;
            // Trigger change event to update UI
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    showMessage('All TEV attachments marked as missing', 'success');
}

// Update character count
function updateCharCount() {
    const textarea = document.getElementById('rejectRemarks');
    const charCount = document.getElementById('char-count');
    const remarksError = document.getElementById('remarksError');
    const length = textarea.value.length;
    
    charCount.textContent = `${length} / 1000`;
    
    if (length < 10) {
        charCount.classList.add('text-red-500');
        charCount.classList.remove('text-gray-400', 'text-green-600', 'text-yellow-600');
        if (length > 0) {
            remarksError.classList.remove('hidden');
        }
    } else if (length > 900) {
        charCount.classList.add('text-yellow-600');
        charCount.classList.remove('text-gray-400', 'text-green-600', 'text-red-500');
        remarksError.classList.add('hidden');
    } else {
        charCount.classList.add('text-green-600');
        charCount.classList.remove('text-gray-400', 'text-red-500', 'text-yellow-600');
        remarksError.classList.add('hidden');
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

// Handle reject form submission
document.addEventListener('DOMContentLoaded', () => {
    const rejectForm = document.getElementById('rejectForm');
    
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

    // Handle TEV attachment checkboxes - Update colors when changed
    const tevCheckboxes = document.querySelectorAll('input[name^="tev_attachments["]');
    tevCheckboxes.forEach(cb => {
        cb.addEventListener('change', (e) => {
            const listItem = e.target.closest('li');
            const statusSpan = listItem.querySelector('span.text-xs');
            
            if (e.target.checked) {
                // ✅ Present - Green
                listItem.classList.remove('bg-red-50', 'border-red-200');
                listItem.classList.add('bg-green-50', 'border-green-200');
                statusSpan.textContent = '✓ Present';
                statusSpan.classList.remove('bg-red-100', 'text-red-800');
                statusSpan.classList.add('bg-green-100', 'text-green-800');
            } else {
                // ❌ Missing - Red
                listItem.classList.remove('bg-green-50', 'border-green-200');
                listItem.classList.add('bg-red-50', 'border-red-200');
                statusSpan.textContent = '✗ Missing';
                statusSpan.classList.remove('bg-green-100', 'text-green-800');
                statusSpan.classList.add('bg-red-100', 'text-red-800');
            }
            
            // Update summary counts
            updateTEVAttachmentSummary();
        });
    });
    
    // Function to update TEV attachment summary
    function updateTEVAttachmentSummary() {
        const tevCheckboxes = document.querySelectorAll('input[name^="tev_attachments["]');
        const total = tevCheckboxes.length;
        const present = Array.from(tevCheckboxes).filter(cb => cb.checked).length;
        const missing = total - present;
        
        // Update the summary display if it exists
        const presentSpan = document.querySelector('.text-green-700');
        const missingSpan = document.querySelector('.text-red-700');
        
        if (presentSpan) {
            presentSpan.textContent = `Present: ${present}`;
        }
        if (missingSpan) {
            missingSpan.textContent = `Missing: ${missing}`;
        }
    }
});

// Function to show toast messages
function showMessage(message, type = 'success') {
    const container = document.getElementById('message-container');
    const messageDiv = document.createElement('div');
    
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    
    messageDiv.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg mb-4 transform transition-all duration-300 translate-x-full opacity-0`;
    messageDiv.textContent = message;
    
    container.appendChild(messageDiv);
    
    // Animate in
    setTimeout(() => {
        messageDiv.classList.remove('translate-x-full', 'opacity-0');
    }, 100);
    
    // Remove after 3 seconds
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
    
    // Show loading state
    buttonElement.disabled = true;
    buttonText.textContent = 'Receiving...';
    spinner.classList.remove('hidden');
    
    try {
        // Create form data for traditional form submission
        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        
        // Make AJAX request to receive the DV request
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

document.addEventListener("DOMContentLoaded", () => {
    const dvRequestId = "{{ $dvRequest->id }}";

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
    }, 120000); // every 2 minutes (120,000 ms)

    // 🧹 Release lock when user leaves the page
    window.addEventListener("beforeunload", () => {
        navigator.sendBeacon(`/Incoming/TEV/dvrequests/${dvRequestId}/release-lock`, 
            JSON.stringify({ _token: "{{ csrf_token() }}" }));
    });
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

// TEV Attachments handling with dynamic color updates
document.addEventListener('DOMContentLoaded', function() {
    const modalitySelect = document.querySelector('select[name="tev_modality"]');
    const bulkActionButtons = document.getElementById('bulk-action-buttons');
    
    if (!modalitySelect) {
        return;
    }
    
    // TEV attachments data by modality
    const attachmentsByModality = {
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
            'Certificate of Expense Not Requiring Receipts (CENRR) or Reimbursable Expense Receipt (RER), as applicable (per Memorandum No. ROXI-2025-August-603 dated August 1, 2025)',
            'Other supporting documents necessary to justify travel and reimbursement',
            'Official Receipt (OR) in case of refund'
        ]
    };

    // Handle modality selection change
    modalitySelect.addEventListener('change', function() {
        const selectedModality = this.value;
        const attachmentsContainer = document.getElementById('dynamic-attachments-container');
        
        if (!selectedModality) {
            // Show placeholder
            attachmentsContainer.innerHTML = `
                <div class="bg-gray-100 border border-gray-300 rounded-lg p-4">
                    <p class="text-sm text-gray-600 italic">
                        Please select a modality above to view required attachments.
                    </p>
                </div>
            `;
            // Hide bulk action buttons
            if (bulkActionButtons) {
                bulkActionButtons.classList.add('hidden');
            }
            return;
        }

        // Get attachments for selected modality
        const attachments = attachmentsByModality[selectedModality] || [];

        if (attachments.length === 0) {
            attachmentsContainer.innerHTML = `
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm text-yellow-800">
                        <strong>No attachments found!</strong> 
                        There are no attachments configured for the "${selectedModality}" modality.
                        Please run the TEV attachment seeder or contact administrator.
                    </p>
                </div>
            `;
            // Hide bulk action buttons
            if (bulkActionButtons) {
                bulkActionButtons.classList.add('hidden');
            }
            return;
        }

        // Show bulk action buttons
        if (bulkActionButtons) {
            bulkActionButtons.classList.remove('hidden');
        }

        // Generate attachments checklist with color coding
        let attachmentsHTML = `
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                <p class="text-sm text-blue-800">
                    <strong>Verification Responsibility:</strong> 
                    <span class="font-semibold">Incoming-TEV</span> verifies all physical TEV attachments.
                    Found ${attachments.length} required attachments for this modality.
                </p>
            </div>

            <ul class="space-y-2">
        `;

        attachments.forEach((attachment, index) => {
            const checkboxId = `tev_attachment_${selectedModality.replace(/\s+/g, '_')}_${index}`;
            attachmentsHTML += `
                <li class="flex items-center p-3 rounded-lg transition-all duration-200 bg-red-50 border border-red-200">
                    <input 
                        type="checkbox" 
                        id="${checkboxId}"
                        name="tev_attachments[${attachment}]"
                        value="1"
                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3 attachment-checkbox"
                    >
                    <label 
                        for="${checkboxId}" 
                        class="text-sm text-gray-900 cursor-pointer flex-1"
                    >
                        ${attachment}
                    </label>
                    <span class="text-xs font-medium px-2 py-1 rounded-full bg-red-100 text-red-800">✗ Missing</span>
                </li>
            `;
        });

        attachmentsHTML += `
            </ul>
            
            <!-- Summary -->
            <div class="mt-4 flex items-center justify-between text-sm">
                <div class="flex items-center space-x-4">
                    <span class="flex items-center">
                        <span class="w-3 h-3 bg-green-500 rounded-full mr-1"></span>
                        <span class="text-green-700" id="dynamic-present-count">Present: 0</span>
                    </span>
                    <span class="flex items-center">
                        <span class="w-3 h-3 bg-red-500 rounded-full mr-1"></span>
                        <span class="text-red-700" id="dynamic-missing-count">Missing: ${attachments.length}</span>
                    </span>
                </div>
                <span class="text-gray-600" id="dynamic-total-count">Total: ${attachments.length}</span>
            </div>
            
            <p class="text-xs text-gray-500 mt-4">
                <strong>Note:</strong> Check all physical documents that are present. 
                All attachments are required for TEV verification.
            </p>
        `;

        attachmentsContainer.innerHTML = attachmentsHTML;

        // Add event listeners to new checkboxes
        const newCheckboxes = attachmentsContainer.querySelectorAll('.attachment-checkbox');
        newCheckboxes.forEach(cb => {
            cb.addEventListener('change', (e) => {
                const listItem = e.target.closest('li');
                const statusSpan = listItem.querySelector('span.text-xs');
                
                if (e.target.checked) {
                    // ✅ Present - Green
                    listItem.classList.remove('bg-red-50', 'border-red-200');
                    listItem.classList.add('bg-green-50', 'border-green-200');
                    statusSpan.textContent = '✓ Present';
                    statusSpan.classList.remove('bg-red-100', 'text-red-800');
                    statusSpan.classList.add('bg-green-100', 'text-green-800');
                } else {
                    // ❌ Missing - Red
                    listItem.classList.remove('bg-green-50', 'border-green-200');
                    listItem.classList.add('bg-red-50', 'border-red-200');
                    statusSpan.textContent = '✗ Missing';
                    statusSpan.classList.remove('bg-green-100', 'text-green-800');
                    statusSpan.classList.add('bg-red-100', 'text-red-800');
                }
                
                // Update summary counts
                updateDynamicTEVSummary();
            });
        });
    });

    // Function to update dynamic TEV summary
    function updateDynamicTEVSummary() {
        const dynamicCheckboxes = document.querySelectorAll('#dynamic-attachments-container .attachment-checkbox');
        const total = dynamicCheckboxes.length;
        const present = Array.from(dynamicCheckboxes).filter(cb => cb.checked).length;
        const missing = total - present;
        
        // Update the summary display
        const presentSpan = document.getElementById('dynamic-present-count');
        const missingSpan = document.getElementById('dynamic-missing-count');
        const totalSpan = document.getElementById('dynamic-total-count');
        
        if (presentSpan) {
            presentSpan.textContent = `Present: ${present}`;
        }
        if (missingSpan) {
            missingSpan.textContent = `Missing: ${missing}`;
        }
        if (totalSpan) {
            totalSpan.textContent = `Total: ${total}`;
        }
    }

    // Trigger change event if there's a pre-selected modality
    if (modalitySelect.value) {
        modalitySelect.dispatchEvent(new Event('change'));
    }
});
</script>