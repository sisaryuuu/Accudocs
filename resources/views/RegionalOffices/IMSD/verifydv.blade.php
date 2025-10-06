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
                    <label class="block text-sm font-medium text-gray-700">Remarks / Lacking</label>
                    <textarea id="remarks" name="remarks" rows="3" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" placeholder="Enter remarks or notes..."></textarea>
                </div>

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
                     @elseif($dvRequest->status === 'IMSD_in_process')
                        {{-- Verify + Back --}}
                        <button type="submit"
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Verify
                        </button>
                        <a href="{{ route('RegionalOffices.IMSD.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Back
                        </a>
                    @elseif($dvRequest->status === 'submitted')
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
                    @else
                        <a href="{{ route('RegionalOffices.IMSD.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Back
                        </a>
                    @endif
                </div>

              
            </form>
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
</div>

<!-- Success/Error Messages -->
<div id="message-container" class="fixed top-4 right-4 z-50"></div>

@endsection

<script>
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
    buttonContainer.innerHTML = `
        <button type="submit"
            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Verify
        </button>
        <a href="{{ route('RegionalOffices.IMSD.index') }}"
           class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Back
        </a>
    `;
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
</script>