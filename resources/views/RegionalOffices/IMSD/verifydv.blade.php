@extends('layouts.app')

@section('title', 'Verify DV Request')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Section - IMSD Verification Form -->
        <div class="lg:col-span-2 bg-white shadow rounded-xl p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">IMSD Verification</h1>

            <!-- Single form for everything -->
            <form action="{{ route('IMSD.dvrequests.verify', $dvRequest->id) }}" method="POST" class="space-y-6">
                @csrf

                <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mode of Payment</label>

                    @if($dvRequest->modeOfPayments && $dvRequest->modeOfPayments->count() > 0)
                        <ul class="space-y-2">
                            @foreach($dvRequest->modeOfPayments as $mop)
                                <li class="p-3 bg-white rounded-lg border border-gray-200">
                                    <p class="text-sm font-medium text-gray-900">{{ $mop->mopname ?? 'N/A' }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-400">N/A</p>
                    @endif
                </div>
                <!-- Request Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Control No.</label>
                        <input type="text" value="{{ $dvRequest->id }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name of Payee</label>
                        <input type="text" value="{{ $dvRequest->focalPerson->fname ?? '' }} {{ $dvRequest->focalPerson->lname ?? '' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Program</label>
                        <input type="text" value="{{ $dvRequest->program->prcode ?? 'N/A' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                </div>

                <!-- Particulars -->
               

                <!-- Amount + Received Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                     <div>
                    <label class="block text-sm font-medium text-gray-700">Particulars</label>
                    <textarea class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" rows="2" readonly>{{ $dvRequest->particulars ?? '' }}</textarea>
                </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="text" value="₱{{ number_format($dvRequest->amount, 2) }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Received From</label>
                        <input type="text" value="{{ $dvRequest->focalPerson->fname ?? '' }} {{ $dvRequest->focalPerson->lname ?? '' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                       <div>
                        <label class="block text-sm font-medium text-gray-700">Received By</label>
                        <input type="text" value="{{ $dvRequest->accountingUnit->fname ?? '' }} {{ $dvRequest->accountingUnit->lname ?? '' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date Received</label>
                        <input type="date" value="{{ $dvRequest->created_at ? $dvRequest->created_at->format('Y-m-d') : '' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Time</label>
                        <input type="time" value="{{ $dvRequest->created_at ? $dvRequest->created_at->format('H:i') : '' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 @if($mop->pivot->paymentDetail)
                        <div class="text-xs text-gray-600 mt-1 space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                                    <input type="text" value="{{ $mop->pivot->paymentDetail->bank_name ?? 'N/A' }} " class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                        </div>
                            <div class="text-xs text-gray-600 mt-1 space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Bank Account Number</label>
                                    <input type="text" value="{{ $mop->pivot->paymentDetail->bank_accnum ?? 'N/A' }} " class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                        </div>
                            <div class="text-xs text-gray-600 mt-1 space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Bank Address</label>
                                    <input type="text" value="{{ $mop->pivot->paymentDetail->bank_address ?? 'N/A' }} " class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                        </div>
                @else
                        <p class="text-xs text-gray-400">No payment details available</p>
                @endif
                 </div>
                <!-- Remarks -->
               

              <div>
    <!-- GIP Attachments Section -->
    @if($dvRequest->program->prcode === 'GIP') 
        <label class="block text-sm font-medium text-gray-700 mb-2">GIP Attachments Checklist</label>
        
        <div class="grid grid-cols-6 gap-4 mb-6">
            @foreach($checklistItems as $item)
                @php
                    $attachment = $dvRequest->gipAttachments->firstWhere('name', $item);
                @endphp
                <div class="flex items-center space-x-2">
                    <input 
                        type="checkbox" 
                        name="attachments[{{ $item }}]" 
                        value="1" 
                        @if($attachment?->is_present) checked @endif
                        class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                    >
                    <label class="text-gray-700 font-medium text-sm">{{ $item }}</label>
                </div>
            @endforeach
        </div>
    @endif

    <!-- TUPAD Attachments Section -->
    @if($dvRequest->program->prcode === 'TUPAD')
        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">TUPAD Attachments Checklist</h3>
    
    <!-- Modality Dropdown -->
    <div class="mb-6">
        <label for="modality_select" class="block text-sm font-medium text-gray-700 mb-2">
            Select Modality
        </label>
        <select 
            id="modality_select" 
            name="modality"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
            <option value="">-- Select Modality --</option>
            <option value="rpmt">RPMT (Direct Administration)</option>
            <option value="lgu">Local Government Unit (LGU)</option>
            <option value="ngo_po">Non-Government Organizations (NGO) / People's Organizations (POs)</option>
            <option value="money_remittance">Money Remittance</option>
            <option value="individual_beneficiaries">Individual Beneficiaries</option>
            <option value="group_beneficiaries">Group Beneficiaries</option>
        </select>
    </div>

    <!-- Attachments Checklist (dynamically shown based on modality) -->
    <div id="attachments_container" class="hidden">
        <h4 class="text-md font-medium text-gray-800 mb-3">Required Attachments</h4>
        <div id="attachments_list" class="space-y-2 max-h-96 overflow-y-auto">
            <!-- Checkboxes will be populated here via JavaScript -->
        </div>
    </div>

            <!-- No Selection Message -->
            <div id="no_selection_message" class="text-sm text-gray-500 italic">
                Please select a modality to view required attachments.
            </div>
        </div>
    @endif
</div>


<!-- TEV Attachments Section -->
@if($dvRequest->program->prcode === 'TEV')
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">TEV Attachments Checklist</h3>

        <!-- Modality Dropdown -->
        <div class="mb-6">
            <label for="tev_modality_select" class="block text-sm font-medium text-gray-700 mb-2">
                Select Modality
            </label>
            <select 
                id="tev_modality_select" 
                name="tev_modality"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
                <option value="">-- Select Modality --</option>
                <option value="Cash Advance for Local Travel" {{ $dvRequest->tev_modality === 'Cash Advance for Local Travel' ? 'selected' : '' }}>
                    Cash Advance for Local Travel
                </option>
                <option value="Liquidation of Cash Advances" {{ $dvRequest->tev_modality === 'Liquidation of Cash Advances' ? 'selected' : '' }}>
                    Liquidation of Cash Advances
                </option>
            </select>
        </div>

        <!-- Attachments Checklist (dynamically shown based on modality) -->
        <div id="tev_attachments_container" class="hidden">
            <h4 class="text-md font-medium text-gray-800 mb-3">Required Attachments</h4>
            <div id="tev_attachments_list" class="space-y-2 max-h-96 overflow-y-auto">
                <!-- Checkboxes will be populated here via JavaScript -->
            </div>
        </div>

        <!-- No Selection Message -->
        <div id="tev_no_selection_message" class="text-sm text-gray-500 italic">
            Please select a modality to view required attachments.
        </div>
    </div>
@endif

 <div>
                    <label class="block text-sm font-medium text-gray-700">Remarks / Lacking</label>
                    <textarea id="remarks" name="remarks" rows="3" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" placeholder="Enter remarks or notes..."></textarea>
                </div>
                <!-- Buttons -->
<div class="flex justify-end space-x-3" id="button-container">
    @if($dvRequest->status === 'IMSD_received')
        <button type="button"
            onclick="handleStartProcessing({{ $dvRequest->id }}, this)"
            class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200"
            id="start-btn">
            <span class="button-text">Start Processing</span>
            <svg class="animate-spin -mr-1 ml-3 h-5 w-5 text-white hidden" id="start-loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
        <a href="{{ route('RegionalOffices.IMSD.index') }}"
            class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Back
        </a>
        </div>
        </form> <!-- Close form here -->
    @elseif($dvRequest->status === 'IMSD_in_process')
    <td>
     <div id="button-container" class="flex justify-end space-x-3">
        <button type="submit"
            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Verify
        </button>
    </div>
    </form> 
<div class="flex justify-end space-x-3 mt-3" id="outside-form-buttons">
    <button type="button"
        onclick="openRejectModal()"
        class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
        Reject
    </button>
    <a href="{{ route('RegionalOffices.IMSD.index') }}"
        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
        Back
    </a>
</div>
</td>

    @elseif($dvRequest->status === 'submitted')
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
        <a href="{{ route('RegionalOffices.IMSD.index') }}"
            class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Back
        </a>
        </div>
    </form> <!-- Close form here -->
    @else
        <a href="{{ route('RegionalOffices.IMSD.index') }}"
            class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Back
        </a>
        </form>
    @endif
</div>
</div>

          <!-- Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow-md rounded-xl p-6">
                                <ul id="recent-requests" class="space-y-3">
                        <li class="text-sm text-gray-500">Loading...</li>
                    </ul>
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
<!-- Success/Error Messages -->
<div id="message-container" class="fixed top-4 right-4 z-50"></div>

@endsection
<script>
let currentRejectId = null;

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
    console.log('🔴 openRejectModal called for IMSD');
    
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
            console.log('🟡 Reject form submitted for IMSD');
            
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
                const response = await fetch(`/RegionalOffices/IMSD/dvrequests/${currentRejectId}/reject`, {
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
                    
                    // Redirect to index after short delay
                    setTimeout(() => {
                        window.location.href = "{{ route('RegionalOffices.IMSD.index') }}";
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

// ✅ Receive Action
async function handleReceiveAction(dvRequestId, buttonElement) {
    const spinner = document.getElementById('loading-spinner');
    const buttonText = buttonElement.querySelector('.button-text');
    
    // Show loading state
    buttonElement.disabled = true;
    buttonText.textContent = 'Receiving...';
    spinner.classList.remove('hidden');
    
    try {
        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        
        const response = await fetch(`/RegionalOffices/IMSD/dvrequests/${dvRequestId}/receive`, {
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
            updateButtonsToStartProcessingMode(dvRequestId);
            loadRecentRequests();
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

// ✅ Start Processing Action
async function handleStartProcessing(dvRequestId, buttonElement) {
    const spinner = document.getElementById('start-loading-spinner');
    const buttonText = buttonElement.querySelector('.button-text');
    
    buttonElement.disabled = true;
    buttonText.textContent = 'Starting...';
    spinner.classList.remove('hidden');
    
    try {
        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        
        const response = await fetch(`/RegionalOffices/IMSD/dvrequests/${dvRequestId}/start-processing`, {
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
            loadRecentRequests();
        } else {
            throw new Error(data.message || 'Error starting processing');
        }
    } catch (error) {
        console.error('Start Processing Error:', error);
        showMessage(error.message || 'Failed to start processing', 'error');
        buttonElement.disabled = false;
        buttonText.textContent = 'Start Processing';
        spinner.classList.add('hidden');
    }
}

// ✅ Button Updaters
function updateButtonsToStartProcessingMode(dvRequestId) {
    const buttonContainer = document.getElementById('button-container');
    buttonContainer.innerHTML = `
        <button type="button"
            onclick="handleStartProcessing(${dvRequestId}, this)"
            class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200"
            id="start-btn">
            <span class="button-text">Start Processing</span>
            <svg class="animate-spin -mr-1 ml-3 h-5 w-5 text-white hidden" id="start-loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
    `;
}

function updateButtonsToVerifyMode(dvRequestId) {
    const buttonContainer = document.getElementById('button-container');
    const form = buttonContainer.closest('form');
    
    // Clear existing button container content
    buttonContainer.innerHTML = '';
    
    // Create Verify button container (inside form)
    const verifyContainer = document.createElement('div');
    verifyContainer.className = 'flex justify-end space-x-3';
    
    const verifyBtn = document.createElement('button');
    verifyBtn.type = 'submit';
    verifyBtn.className = 'inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200';
    verifyBtn.textContent = 'Verify';
    
    verifyContainer.appendChild(verifyBtn);
    buttonContainer.appendChild(verifyContainer);
    
    // Create Reject and Back buttons container (outside form)
    const outsideButtonsContainer = document.createElement('div');
    outsideButtonsContainer.className = 'flex justify-end space-x-3 mt-3';
    outsideButtonsContainer.id = 'outside-form-buttons';
    
    // Create Reject button
    const rejectBtn = document.createElement('button');
    rejectBtn.type = 'button';
    rejectBtn.id = 'reject-button';
    rejectBtn.className = 'inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200';
    rejectBtn.textContent = 'Reject';
    
    // Add event listener directly
    rejectBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('🔴 Reject button clicked for IMSD');
        openRejectModal();
    });
    
    // Create Back link
    const backLink = document.createElement('a');
    backLink.href = "{{ route('RegionalOffices.IMSD.index') }}";
    backLink.className = 'inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200';
    backLink.textContent = 'Back';
    
    // Add buttons to container
    outsideButtonsContainer.appendChild(rejectBtn);
    outsideButtonsContainer.appendChild(backLink);
    
    // Remove any existing outside buttons first
    const existingOutsideButtons = form.parentNode.querySelector('#outside-form-buttons');
    if (existingOutsideButtons) {
        existingOutsideButtons.remove();
    }
    
    // Insert outside buttons after the form
    form.parentNode.insertBefore(outsideButtonsContainer, form.nextSibling);
    
    console.log('✅ Buttons updated to verify mode for IMSD with Reject option');
}

// Recent requests loading function
async function loadRecentRequests() {
    try {
        const res = await fetch("{{ route('RegionalOffices.IMSD.dvrequests.fetchimsdRecent') }}");
        const data = await res.json();
        console.log(data); // 👀 debug output

        let html = '';
        if (data.length === 0) {
            html = `<li class="text-sm text-gray-500">No recent requests.</li>`;
        } else {
            data.forEach(r => {
                html += `
                    <li>
                        <a href="/RegionalOffices/IMSD/dvrequests/${r.id}" 
                           class="block p-3 bg-gray-50 hover:bg-blue-50 rounded-lg border border-gray-200 transition">
                            <p class="text-sm font-medium text-gray-900">
                                Control #${r.id} • ${r.particulars ?? ''}
                            </p>
                            <p class="text-sm text-gray-600">
                                ${(r.focal_person?.fname ?? 'Unknown')} ${(r.focal_person?.lname ?? '')} • 
                                ${new Date(r.created_at).toLocaleDateString()}
                            </p>
                        </a>
                    </li>`;
            });
        }
        document.getElementById('recent-requests').innerHTML = html;
    } catch (e) {
        console.error("Failed to load recent requests", e);
    }
}

// Initialize
setInterval(loadRecentRequests, 5000);
loadRecentRequests();

document.addEventListener('DOMContentLoaded', function() {
    if (!document.querySelector('meta[name="csrf-token"]')) {
        const tokenInput = document.querySelector('input[name="_token"]');
        if (tokenInput) {
            const meta = document.createElement('meta');
            meta.name = 'csrf-token';
            meta.content = tokenInput.value;
            document.head.appendChild(meta);
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const modalitySelect = document.getElementById('modality_select');
    
    // Only run if the element exists (TUPAD program)
    if (!modalitySelect) {
        return;
    }
    
    const attachmentsContainer = document.getElementById('attachments_container');
    const attachmentsList = document.getElementById('attachments_list');
    const noSelectionMessage = document.getElementById('no_selection_message');
    const dvRequestId = {{ $dvRequest->id }};
    
       // Fetch attachments from database for this DV request
    async function fetchAttachmentsForModality(modality) {
        try {
            const response = await fetch(`/RegionalOffices/IMSD/dvrequests/${dvRequestId}/tupad-attachments/${modality}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching attachments:', error);
            return [];
        }
    }

    // Fallback attachments data (in case fetch fails)
    const attachmentsByModality = {
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

    // Handle modality selection change
    modalitySelect.addEventListener('change', function() {
        const selectedModality = this.value;
        
        if (!selectedModality) {
    
            return;
        }

        // Show attachments container
        noSelectionMessage.classList.add('hidden');
        attachmentsContainer.classList.remove('hidden');

        // Get attachments for selected modality
        const attachments = attachmentsByModality[selectedModality] || [];

        // Clear previous checkboxes
        attachmentsList.innerHTML = '';

        // Generate checkboxes
        attachments.forEach((attachment, index) => {
            const checkboxId = `attachment_${selectedModality}_${index}`;
            const checkboxDiv = document.createElement('div');
            checkboxDiv.className = 'flex items-start p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition';
            
            checkboxDiv.innerHTML = `
                <input 
                    type="checkbox" 
                    id="${checkboxId}"
                    name="attachments[${attachment}]"
                    value="1"
                    class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                >
                <label 
                    for="${checkboxId}" 
                    class="ml-3 text-sm text-gray-700 cursor-pointer select-none flex-1"
                >
                    ${attachment}
                </label>
            `;
            
            attachmentsList.appendChild(checkboxDiv);
        });
    });
});
document.addEventListener('DOMContentLoaded', function() {
    const tevModalitySelect = document.getElementById('tev_modality_select');
    
    // Only run if the element exists (TEV program)
    if (!tevModalitySelect) {
        return;
    }
    
    const tevAttachmentsContainer = document.getElementById('tev_attachments_container');
    const tevAttachmentsList = document.getElementById('tev_attachments_list');
    const tevNoSelectionMessage = document.getElementById('tev_no_selection_message');
    const dvRequestId = {{ $dvRequest->id }};
    
    // TEV attachments data
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
            'Certificate of Expense Not Requiring Receipts (CENRR) or Reimbursable Expense Receipt (RER), as applicable (per Memorandum No. ROXI-2025-August-603 dated August 1, 2025)',
            'Other supporting documents necessary to justify travel and reimbursement',
            'Official Receipt (OR) in case of refund'
        ]
    };

    // Handle modality selection change
    tevModalitySelect.addEventListener('change', function() {
        const selectedModality = this.value;
        
        if (!selectedModality) {
            tevAttachmentsContainer.classList.add('hidden');
            tevNoSelectionMessage.classList.remove('hidden');
            return;
        }

        // Show attachments container
        tevNoSelectionMessage.classList.add('hidden');
        tevAttachmentsContainer.classList.remove('hidden');

        // Get attachments for selected modality
        const attachments = tevAttachmentsByModality[selectedModality] || [];

        // Clear previous checkboxes
        tevAttachmentsList.innerHTML = '';

        // Generate checkboxes
        attachments.forEach((attachment, index) => {
            const checkboxId = `tev_attachment_${index}`;
            const checkboxDiv = document.createElement('div');
            checkboxDiv.className = 'flex items-start p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition';
            
            checkboxDiv.innerHTML = `
                <input 
                    type="checkbox" 
                    id="${checkboxId}"
                    name="tev_attachments[${attachment}]"
                    value="1"
                    class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                >
                <label 
                    for="${checkboxId}" 
                    class="ml-3 text-sm text-gray-700 cursor-pointer select-none flex-1"
                >
                    ${attachment}
                </label>
            `;
            
            tevAttachmentsList.appendChild(checkboxDiv);
        });
    });

    // Trigger change event if there's a pre-selected modality
    if (tevModalitySelect.value) {
        tevModalitySelect.dispatchEvent(new Event('change'));
    }
});
</script>