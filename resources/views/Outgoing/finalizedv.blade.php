@extends('layouts.app')

@section('title', 'Finalize DV Request')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Section - Finalization -->
        <div class="lg:col-span-2 bg-white shadow rounded-xl p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Outgoing - Finalize DV</h1>

            <form action="{{ route('Outgoing.dvrequests.finalize', $dvRequest->id) }}" method="POST" class="space-y-6">
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
                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Control No.</label>
                        <input type="text" value="{{ $dvRequest->id }}" 
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name of Payee</label>
                        <input type="text" value="{{ $dvRequest->focalPerson->fname ?? '' }} {{ $dvRequest->focalPerson->lname ?? '' }}"
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Responsibility Center</label>
                        <p class="mt-1 text-sm text-gray-900">
                          {{ $dvRequest->respocenter->rccode ?? '' }} {{ $dvRequest->respocenter->description ?? '' }}
                        </p>
                    </div>
                </div>

                <!-- ORS + Particulars -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">ORS Number</label>
                    <input type="text" value="{{ $dvRequest->orsRequest->ors_number ?? '—' }}" 
                           class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Particulars</label>
                    <textarea rows="2" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>{{ $dvRequest->particulars ?? '' }}</textarea>
                </div>
            @if($dvRequest->program->prcode === 'GIP')
                <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-3">GIP Attachments Checklist</h2>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                        @foreach($dvRequest->gipAttachments as $attachment)
                        <div class="flex items-center space-x-2">
                            <input 
                                type="checkbox" 
                                name="gip_attachments[{{ $attachment->id }}]"
                                id="gip_attachment_{{ $attachment->id }}"
                                class="h-4 w-4 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                                style="accent-color: #16a34a;"
                                {{ $attachment->is_present ? 'checked' : '' }}>
                            <label 
                                for="gip_attachment_{{ $attachment->id }}"
                                class="text-sm cursor-pointer {{ $attachment->is_present ? 'text-green-600 font-semibold' : 'text-gray-700' }}">
                                {{ $attachment->name }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                    
                </div>
                @endif

                @if($dvRequest->program->prcode === 'TUPAD')
                <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        TUPAD Attachments Checklist
                        @if($selectedModality)
                            <span class="text-sm font-normal text-blue-600">
                                (Modality: {{ ucfirst(str_replace('_', ' ', $selectedModality)) }})
                            </span>
                        @endif
                    </h3>
                
                    @if($selectedModality && $attachments && $attachments->count() > 0)
                        <ul class="space-y-2">
                            @foreach($attachments as $item)
                                <li class="flex items-center p-3 bg-white rounded-lg hover:bg-gray-50 transition">
                                    <input type="checkbox" 
                                        name="attachments[{{ $item->id }}]" 
                                        value="1"
                                        id="attachment_{{ $item->id }}"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3"
                                        {{ $item->is_present ? 'checked' : '' }}>
                                    <label for="attachment_{{ $item->id }}" class="text-sm text-gray-900 cursor-pointer flex-1">
                                        {{ $item->name }}
                                    </label>
                                    @if($item->is_present)
                                        <span class="text-xs text-green-600 font-medium">✓ Present</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                
                        <p class="text-xs text-gray-500 mt-4">
                            <strong>Note:</strong> Check/uncheck items to update attachment status when new physical documents arrive.
                        </p>
                    @elseif(!$selectedModality)
                        <p class="text-gray-500 italic">No modality was selected by IMSD for this request.</p>
                    @else
                        <p class="text-gray-400">No attachments found for the selected modality.</p>
                    @endif
                </div>
                @endif

                        @if($dvRequest->program->prcode === 'TEV')
                <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        TEV Attachments Checklist
                        @if($dvRequest->tev_modality)
                            <span class="text-sm font-normal text-blue-600">
                                (Modality: {{ ucfirst(str_replace('_', ' ', $dvRequest->tev_modality)) }})
                            </span>
                        @endif
                    </h3>

                    @if($dvRequest->tev_modality && $tevAttachments && $tevAttachments->count() > 0)
                        <ul class="space-y-2">
                            @foreach($tevAttachments as $item)
                                <li class="flex items-center p-3 bg-white rounded-lg hover:bg-gray-50 transition">
                                    <input type="checkbox" 
                                        name="tev_attachments[{{ $item->id }}]" 
                                        value="1"
                                        id="tev_attachment_{{ $item->id }}"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3"
                                        {{ $item->is_present ? 'checked' : '' }}>
                                    <label for="tev_attachment_{{ $item->id }}" class="text-sm text-gray-900 cursor-pointer flex-1">
                                        {{ $item->name }}
                                    </label>
                                    @if($item->is_present)
                                        <span class="text-xs text-green-600 font-medium">✓ Present</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        <p class="text-xs text-gray-500 mt-4">
                            <strong>Note:</strong> Check/uncheck items to update attachment status when new physical documents arrive.
                        </p>
                    @elseif(!$dvRequest->tev_modality)
                        <p class="text-gray-500 italic">
                            No modality was selected by IMSD for this request.
                        </p>
                    @else
                        <p class="text-gray-400">
                            No attachments found for the selected modality.
                        </p>
                    @endif
                </div>
            @endif
                <!-- Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Amount</label>
                    <input type="text" value="₱{{ number_format($dvRequest->amount, 2) }}" 
                           class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                </div>

                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 @if($dvRequest->modeOfPayments && $dvRequest->modeOfPayments->count() > 0)
                    @foreach($dvRequest->modeOfPayments as $mop)
                        @if($mop->pivot && $mop->pivot->paymentDetail)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                                <input type="text" value="{{ $mop->pivot->paymentDetail->bank_name ?? 'N/A' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bank Account Number</label>
                                <input type="text" value="{{ $mop->pivot->paymentDetail->bank_accnum ?? 'N/A' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bank Address</label>
                                <input type="text" value="{{ $mop->pivot->paymentDetail->bank_address ?? 'N/A' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                            </div>
                            @break
                        @endif
                    @endforeach
                    @if(!$dvRequest->modeOfPayments->contains(function($mop) { return $mop->pivot && $mop->pivot->paymentDetail; }))
                        <p class="text-xs text-gray-400">No payment details available</p>
                    @endif
                @endif
                 </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">DV Number</label>
                    <input type="text" value="{{ $dvRequest->dv_number ?? 'Will be generated upon finalization' }}" 
                           class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                </div>

                <!-- Received Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Received By</label>
                        <input type="text" value="{{ auth()->user()->fname ?? '' }} {{ auth()->user()->lname ?? '' }}" 
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Received At</label>
                        <input type="text"  value="{{ $dvRequest->created_at ? $dvRequest->created_at->format('Y-m-d  H:i') : '' }}"
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                </div>

                <!-- Remarks -->
                <!-- Remarks (This is still inside the form) -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Remarks / Notes</label>
                <textarea id="remarks" name="remarks" rows="3" 
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm"
                        placeholder="Enter remarks or notes...">{{ old('remarks') }}</textarea>
            </div>

<!-- Actions Section -->
@if($dvRequest->status === 'Outgoing_received')
    <!-- Finalize Mode - Directly show Finalize/Reject buttons when received -->
    <div id="button-container" class="flex justify-end space-x-3">
        <button type="submit" 
                class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Finalize
        </button>
        <button type="button"
                onclick="openRejectModal({{ $dvRequest->id }})"
                class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Reject
        </button>
        <a href="{{ route('Outgoing.dvrequests.index') }}"
           class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Cancel
        </a>
    </div>
    </form> <!-- Close form here -->
    
@elseif($dvRequest->status === 'ORS_obligated')
    <!-- Receive Mode -->
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
            <a href="{{ route('Outgoing.dvrequests.index') }}"
            class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
            onclick="releaseLock(event, {{ $dvRequest->id }})">
                Back
            </a>
    </div>
    </form> <!-- Close form here -->
    
@else
    <!-- Default mode -->
    <div id="button-container" class="flex justify-end space-x-3">
        <a href="{{ route('Outgoing.dvrequests.index') }}"
            class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
            onclick="releaseLock(event, {{ $dvRequest->id }})">
            Back
        </a>
    </div>
    </form> <!-- Close form here -->
@endif
</div>
        <!-- Sidebar -->
        <div class="lg:col-span-1">

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
                    maxlength="1000"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"
                    placeholder="Please provide a detailed reason for rejection..."></textarea>
                <p class="text-xs text-gray-500 mt-1">Maximum 1000 characters</p>
                <p id="remarksError" class="text-xs text-red-600 mt-1 hidden">This field is required</p>
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

<div id="message-container" class="fixed top-4 right-4 z-50"></div>
@endsection

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

<script>
let currentRejectId = null;

// Open reject modal with debugging
function openRejectModal(dvRequestId) {
    console.log('🔴 openRejectModal called with ID:', dvRequestId);
    console.log('🔴 Event object:', event);
    
    // Prevent any default behavior
    if (event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
    }
    
    currentRejectId = dvRequestId;
    const modal = document.getElementById('rejectModal');
    
    console.log('🔴 Modal element:', modal);
    console.log('🔴 Modal classes before:', modal.className);
    
    modal.classList.remove('hidden');
    document.getElementById('rejectRemarks').value = '';
    document.getElementById('remarksError').classList.add('hidden');
    document.body.style.overflow = 'hidden';
    
    console.log('🔴 Modal classes after:', modal.className);
    console.log('🔴 Modal should be visible now');
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
    
    // Add click handler to ALL reject buttons (both static and dynamic)
    document.addEventListener('click', function(e) {
        // Check if clicked element or its parent is a reject button
        const rejectBtn = e.target.closest('button[onclick*="openRejectModal"]');
        if (rejectBtn) {
            console.log('🔴 Reject button clicked via event delegation');
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            // Extract ID from onclick attribute
            const onclickAttr = rejectBtn.getAttribute('onclick');
            const match = onclickAttr.match(/openRejectModal\((\d+)\)/);
            if (match) {
                const dvRequestId = match[1];
                openRejectModal(dvRequestId);
            }
            return false;
        }
    }, true); // Use capture phase
    
    if (rejectForm) {
        rejectForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('🟡 Reject form submitted');
            
            const remarks = document.getElementById('rejectRemarks').value.trim();
            const remarksError = document.getElementById('remarksError');
            
            // Validation
            if (!remarks) {
                remarksError.classList.remove('hidden');
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
                const response = await fetch(`/Outgoing/dvrequests/${currentRejectId}/reject`, {
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
                    
                    // Reload page after short delay
                    setTimeout(() => {
                        window.location.href = '/Outgoing/dvrequests';
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

// TUPAD Attachments Checkbox Handler
document.addEventListener('DOMContentLoaded', () => {
    const tupadCheckboxes = document.querySelectorAll('input[name^="attachments["]');

    tupadCheckboxes.forEach(cb => {
        cb.addEventListener('change', (e) => {
            const listItem = e.target.closest('li');
            let statusSpan = listItem.querySelector('span.text-xs');

            if (!statusSpan) {
                statusSpan = document.createElement('span');
                statusSpan.classList.add('text-xs', 'ml-2');
                listItem.appendChild(statusSpan);
            }

            if (e.target.checked) {
                statusSpan.textContent = '✓ Present';
                statusSpan.classList.remove('text-gray-400');
                statusSpan.classList.add('text-green-600', 'font-medium');
            } else {
                statusSpan.textContent = 'Missing';
                statusSpan.classList.remove('text-green-600', 'font-medium');
                statusSpan.classList.add('text-gray-400');
            }
        });
    });
});

// Handle Receive Action
async function handleReceiveAction(dvRequestId, buttonElement) {
    const spinner = document.getElementById('loading-spinner');
    const buttonText = buttonElement.querySelector('.button-text');
    
    buttonElement.disabled = true;
    buttonText.textContent = 'Receiving...';
    spinner.classList.remove('hidden');
    
    try {
        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        
        const response = await fetch(`/Outgoing/dvrequests/${dvRequestId}/receive`, {
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
            // After receiving, directly show Finalize/Reject buttons
            updateButtonsToFinalizeMode(dvRequestId);
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

// Update Buttons to Finalize Mode (after receiving)
function updateButtonsToFinalizeMode(dvRequestId) {
    const buttonContainer = document.getElementById('button-container');
    buttonContainer.innerHTML = `
        <button type="submit" 
                class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Finalize
        </button>
        <button type="button"
                onclick="openRejectModal(${dvRequestId})"
                class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Reject
        </button>
        <a href="{{ route('Outgoing.dvrequests.index') }}"
           class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Cancel
        </a>
    `;
}

// Lock Management
document.addEventListener("DOMContentLoaded", () => {
    const dvRequestId = "{{ $dvRequest->id }}";

    // Auto-refresh lock every 2 minutes
    const refreshInterval = setInterval(() => {
        fetch(`/Outgoing/dvrequests/${dvRequestId}/refresh-lock`, {
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
    }, 120000);

    // Release lock when user leaves the page
    window.addEventListener("beforeunload", () => {
        navigator.sendBeacon(`/Outgoing/dvrequests/${dvRequestId}/release-lock`, 
            JSON.stringify({ _token: "{{ csrf_token() }}" }));
    });
});

// Release Lock Function
function releaseLock(event, id) {
    event.preventDefault();

    fetch(`/Outgoing/dvrequests/${id}/release-lock`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json",
            "Content-Type": "application/json"
        }
    }).then(() => {
        window.location.href = "{{ route('Outgoing.dvrequests.index') }}";
    }).catch(err => {
        console.error("Release lock failed:", err);
        window.location.href = "{{ route('Outgoing.dvrequests.index') }}";
    });
}
</script>