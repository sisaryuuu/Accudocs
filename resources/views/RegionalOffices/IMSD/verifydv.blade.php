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
                  @if($dvRequest->program->prcode === 'GIP') 
                <label class="block text-sm font-medium text-gray-700 mb-2">Attachments Checklist</label>
                 
                <div class="grid grid-cols-6 gap-4">
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
            </div>

          
                <!-- Buttons -->
                <div class="flex justify-end space-x-3" id="button-container">
                    @if($dvRequest->status === 'IMSD_received')
                        <a href="{{ route('RegionalOffices.IMSD.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Cancel
                        </a>
                        <button type="submit"
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Verify
                        </button>
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

// Enhanced receive action function
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
        const response = await fetch(`/RegionalOffices/IMSD/dvrequests/${dvRequestId}/receive`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: formData
        });

        let data;
        const contentType = response.headers.get('content-type');
        
        // Try to parse JSON response
        try {
            const responseText = await response.text();
            console.log('Response:', responseText); // Debug log
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.log('Raw response:', await response.text());
            throw new Error('Server returned invalid JSON response');
        }
        
        if (response.ok && data.success) {
            // Show success message using the message from backend
            showMessage(data.message, 'success');
            
            // Update the button container to show Verify and Cancel buttons
            updateButtonsToVerifyMode(dvRequestId);
            
            // Update any displayed timestamps or status if needed
            if (data.data) {
                console.log('Updated status:', data.data.status);
                console.log('Received at:', data.data.received_at);
            }
            
            // Reload recent requests
            loadRecentRequests();
            
        } else if (!response.ok) {
            // Handle HTTP error responses
            if (response.status === 422) {
                throw new Error(data.message || 'Validation error occurred');
            } else if (response.status === 500) {
                throw new Error(data.message || 'Server error occurred');
            } else {
                throw new Error(data.message || `HTTP ${response.status} error`);
            }
        } else {
            throw new Error(data.message || 'Unknown error occurred');
        }
        
    } catch (error) {
        console.error('Full Error Details:', error);
        
        let errorMessage = 'An error occurred while receiving the request';
        
        if (error.message.includes('JSON')) {
            errorMessage = 'Server response error. Please try refreshing the page.';
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        showMessage(errorMessage, 'error');
        
        // Reset button state on error
        buttonElement.disabled = false;
        buttonText.textContent = 'Receive';
        spinner.classList.add('hidden');
    }
}

// Function to update buttons to verify mode
function updateButtonsToVerifyMode(dvRequestId) {
    const buttonContainer = document.getElementById('button-container');
    
    buttonContainer.innerHTML = `
    
        <a href="{{ route('RegionalOffices.IMSD.index') }}"
           class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Cancel
        </a>
        <button type="submit"
            class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Verify
        </button>
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

// Add CSRF token to head if not present
if (!document.querySelector('meta[name="csrf-token"]')) {
    const meta = document.createElement('meta');
    meta.name = 'csrf-token';
    meta.content = document.querySelector('input[name="_token"]').value;
    document.head.appendChild(meta);
}
</script>