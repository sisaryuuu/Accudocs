@extends('layouts.app')

@section('title', 'Verify DV Request')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Section - Incoming Form -->
        <div class="lg:col-span-2 bg-white shadow rounded-xl p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Incoming</h1>

            <form action="{{ route('Incoming.GIP.dvrequests.verify', $dvRequest->id) }}" method="POST" class="space-y-6">
                @csrf


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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Control No.</label>
                        <input type="text" value="{{ $dvRequest->id ?? '' }}" 
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring focus:ring-blue-200 text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name of Payee</label>
                        <input type="text" value="{{ $dvRequest->focalPerson->fname ?? '' }} {{ $dvRequest->focalPerson->lname ?? '' }}"
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring focus:ring-blue-200 text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Program</label>
                        <input type="text" value="{{ $dvRequest->program->prcode ?? 'N/A'}}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring focus:ring-blue-200 text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Responsibility Center</label>
                        <p class="mt-1 text-sm text-gray-900">
                          {{ $dvRequest->respocenter->rccode}} {{$dvRequest->respocenter->description}}
                        </p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Particulars</label>
                    <textarea rows="2" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring focus:ring-blue-200 text-sm" readonly>{{ $dvRequest->particulars ?? '' }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="text" value="₱{{ number_format($dvRequest->amount, 2) }}" 
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring focus:ring-blue-200 text-sm" readonly>
                    </div>
                   <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                     @if($mop->pivot->paymentDetail)
                        <div>
                                <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                                    <input type="text" value="{{ $mop->pivot->paymentDetail->bank_name ?? 'N/A' }} " class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                        </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bank Account Number</label>
                                    <input type="text" value="{{ $mop->pivot->paymentDetail->bank_accnum ?? 'N/A' }} " class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                        </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bank Address</label>
                                    <input type="text" value="{{ $mop->pivot->paymentDetail->bank_address ?? 'N/A' }} " class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                        </div>
                @else
                        <p class="text-xs text-gray-400">No payment details available</p>
                @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Received By</label>
                        <input type="text" value="{{ $dvRequest->accountingUnit->fname ?? '' }} {{ $dvRequest->accountingUnit->lname ?? '' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring focus:ring-blue-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Received From</label>
                        <input type="text"  value="{{ $dvRequest->focalPerson->fname ?? 'DILI MAKIT.AN' }} {{ $dvRequest->focalPerson->lname ?? 'WALA' }}"  class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring focus:ring-blue-200 text-sm" readonly>
                    </div>
                </div>

               <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date Received</label>
                    <input type="date" 
                        value="{{ $dvRequest->created_at ? $dvRequest->created_at->format('Y-m-d') : '' }}"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring focus:ring-blue-200 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Time</label>
                    <input type="time"   
                        value="{{ $dvRequest->created_at ? $dvRequest->created_at->format('H:i') : '' }}"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring focus:ring-blue-200 text-sm">
                </div>
            </div>

                    
                <div class="bg-gray-50 border rounded-lg p-4">
                <h2 class="text-md font-semibold text-green-800 mb-3"> Attachments Verified by IMSD</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                    @foreach($dvRequest->gipAttachments as $attachment)
                                    <div class="flex items-center space-x-2">
                        <input type="checkbox" disabled
                            class="h-4 w-4 border-gray-300 rounded"
                            style="accent-color: #16a34a;" {{-- Force Tailwind green-600 --}}
                            @if($attachment->is_present) checked @endif>
                        <span class="{{ $attachment->is_present ? 'text-green-600 font-semibold' : 'text-gray-400 line-through' }}">
                            {{ $attachment->name }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Remarks / Lacking</label>
                    <textarea id="remarks" name="remarks" rows="3" 
                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm"
                              placeholder="Enter remarks or notes..."></textarea>
                </div>
                    <div class="flex justify-end space-x-3" id="button-container">
                    @if($dvRequest->status === 'Incoming-GIP_received')
                <div class="flex justify-end">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Approve
                    </button>
                </div>
            </form>

            <form action="{{ route('Incoming.GIP.dvrequests.reject', $dvRequest->id) }}" method="POST">
                @csrf
                 <div class="flex justify-end">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Reject
                    </button>
                </div>
                </form>
                </a>
                <a href="{{ route('Incoming.GIP.index') }}" onclick="releaseLock(event, {{ $dvRequest->id }})">
                 <div class="flex justify-end">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Cancel
                    </button>
                </div>
                </a>
             @elseif($dvRequest->status === 'IMSD_verified')
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
                          <a href="{{ route('Incoming.GIP.index') }} "
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
                    @else
                        <a href="{{ route('Incoming.GIP.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
                    @endif
                </div>
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
        const response = await fetch(`/Incoming/GIP/dvrequests/${dvRequestId}/receive`, {
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
    
         <a href="{{ route('Incoming.GIP.index') }}" onclick="releaseLock(event, {{ $dvRequest->id }})">
                 <div class="flex justify-end">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Cancel
                    </button>
                </div>
                </a>
                      <form action="{{ route('Incoming.GIP.dvrequests.reject', $dvRequest->id) }}" method="POST">
                @csrf
                 <div class="flex justify-end">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Reject
                    </button>
                </div>
                </form>
                </a>
         <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Approve
        </button>
    `;
}

document.addEventListener("DOMContentLoaded", () => {
    const dvRequestId = "{{ $dvRequest->id }}";

    // 🔄 Auto-refresh lock every 2 minutes
    const refreshInterval = setInterval(() => {
        fetch(`/Incoming/GIP/dvrequests/${dvRequestId}/refresh-lock`, {
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
        navigator.sendBeacon(`/Incoming/GIP/dvrequests/${dvRequestId}/release-lock`, 
            JSON.stringify({ _token: "{{ csrf_token() }}" }));
    });
});

// 🔘 Manual release lock button (if you need it in blade)
function releaseLock(event, id) {
    event.preventDefault(); // stop normal redirect

    fetch(`/Incoming/GIP/dvrequests/${id}/release-lock`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json",
            "Content-Type": "application/json"
        }
    }).then(() => {
        // Redirect after releasing lock
        window.location.href = "{{ route('Incoming.GIP.index') }}";
    }).catch(err => {
        console.error("Release lock failed:", err);
        // Still redirect to index to avoid stuck state
        window.location.href = "{{ route('Incoming.GIP.index') }}";
    });
}
async function loadRecentRequests() {
    try {
        const res = await fetch("{{ route('Incoming.GIP.dvrequests.fetchgipRecent') }}");
        const data = await res.json();
        console.log(data); // 👀 debug output

        let html = '';
        if (data.length === 0) {
            html = `<li class="text-sm text-gray-500">No recent requests.</li>`;
        } else {
            data.forEach(r => {
                html += `
                    <li>
                        <a href="/Incoming/GIP/dvrequests/${r.id}" 
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
setInterval(loadRecentRequests, 5000);
loadRecentRequests();
</script>
