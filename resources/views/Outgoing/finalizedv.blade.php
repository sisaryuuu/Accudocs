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

                <!-- Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Amount</label>
                    <input type="text" value="₱{{ number_format($dvRequest->amount, 2) }}" 
                           class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
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
                <div>
                    <label class="block text-sm font-medium text-gray-700">Remarks / Notes</label>
                    <textarea id="remarks" name="remarks" rows="3" 
                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm"
                              placeholder="Enter remarks or notes...">{{ old('remarks') }}</textarea>
                </div>

                <!-- Actions -->
               <div id="button-container" class="flex justify-end space-x-3">
                  @if($dvRequest->status === 'Outgoing_received')
                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Finalize
                    </button>
                    <a href="{{ route('Outgoing.dvrequests.index') }}"
                        onclick="releaseLock(event, {{ $dvRequest->id }})"
                       class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Cancel
                    </a>
                </div>
            </form>
              @elseif($dvRequest->status === 'ORS_obligated')
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
                          <a href="{{ route('Outgoing.dvrequests.index') }} "
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
                   @else
                        <a href="{{ route('Outgoing.dvrequests.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
                    @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow-md rounded-xl p-6">
                <h2 class="text-lg font-semibold mb-4">Recent DV Requests</h2>
                <ul id="recent-requests" class="space-y-3">
                    <li class="text-sm text-gray-500">Loading...</li>
                </ul>
            </div>
        </div>

    </div>
</div>
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
        const response = await fetch(`/Outgoing/dvrequests/${dvRequestId}/receive`, {
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
    
       <div class="flex justify-end space-x-3">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Finalize
                    </button>
                    <a href="{{ route('Outgoing.dvrequests.index') }}"
                        onclick="releaseLock(event, {{ $dvRequest->id }})"
                       class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Cancel
                    </a>
                </div>
    `;
}


document.addEventListener("DOMContentLoaded", () => {
    const dvRequestId = "{{ $dvRequest->id }}";

    // 🔄 Auto-refresh lock every 2 minutes
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
    }, 120000); // every 2 minutes (120,000 ms)

    // 🧹 Optional: release lock when user leaves the page
    window.addEventListener("beforeunload", () => {
        navigator.sendBeacon(`/Outgoing/dvrequests/${dvRequestId}/release-lock`, 
            JSON.stringify({ _token: "{{ csrf_token() }}" }));
    });
});
function releaseLock(event, id) {
    event.preventDefault(); // stop normal redirect

    fetch(`/Outgoing/dvrequests/${id}/release-lock`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json",
            "Content-Type": "application/json"
        }
    }).then(() => {
        // Redirect after releasing lock
        window.location.href = "{{ route('Outgoing.dvrequests.index') }}";
    }).catch(err => {
        console.error("Release lock failed:", err);
        // Still redirect to index to avoid stuck state
        window.location.href = "{{ route('Outgoing.dvrequests.index') }}";
    });
}

async function loadRecentRequests() {
    try {
        const res = await fetch("{{ route('Outgoing.dvrequests.recent') }}");
        const data = await res.json();

        let html = '';
        if (data.length === 0) {
            html = `<li class="text-sm text-gray-500">No recent requests.</li>`;
        } else {
            data.forEach(r => {
                html += `
                    <li>
                        <a href="/Outgoing/dvrequests/${r.id}" 
                           class="block p-3 bg-gray-50 hover:bg-indigo-50 rounded-lg border border-gray-200 transition">
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
setInterval(loadRecentRequests, 5000);
loadRecentRequests();
</script>
