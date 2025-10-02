@extends('layouts.app')

@section('title', 'Obligate DV Request')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Section - ORS Processing -->
        <div class="lg:col-span-2 bg-white shadow rounded-xl p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Budget Officer</h1>

            <form action="{{ route('BudgetOfficer.dvrequests.store', $dvRequest->id) }}" method="POST" class="space-y-6">
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

                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Control No.</label>
                        <input type="text" value="{{ $dvRequest->id ?? '' }}" 
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

                <div>
                    <label class="block text-sm font-medium text-gray-700">Particulars</label>
                    <textarea rows="2" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>{{ $dvRequest->particulars ?? '' }}</textarea>
                </div>

                <!-- Enhanced Amounts Section -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Gross Amount</label>
                    
                    @if(in_array($dvRequest->status, ['ORS_received', 'ORS_in_process', 'ORS_onhold']))
                        <!-- Inline Editable Amount -->
                        <div class="mt-1 relative">
                            <div class="editable-amount-container flex items-center space-x-2">
                                <span class="editable-amount cursor-pointer hover:bg-gray-50 px-3 py-2 border border-gray-300 rounded-lg transition-colors duration-200" 
                                      data-dv-id="{{ $dvRequest->id }}" 
                                      data-amount="{{ $dvRequest->amount }}"
                                      title="Click to edit amount">
                                    ₱{{ number_format($dvRequest->amount, 2) }}
                                </span>
                                <button type="button" 
                                        class="edit-amount-btn text-blue-600 hover:text-blue-800 text-sm"
                                        data-dv-id="{{ $dvRequest->id }}" 
                                        data-current-amount="{{ $dvRequest->amount }}"
                                        title="Edit amount">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                            </div>
                            <!-- Hidden input for form submission -->
                            <input type="hidden" name="amount" id="amount-hidden" value="{{ $dvRequest->amount }}">
                            <p class="text-xs text-green-600 mt-1 flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Amount is editable while ORS is being processed
                            </p>
                        </div>
                    @else
                        <!-- Read-only Amount -->
                        <input type="text" value="₱{{ number_format($dvRequest->amount, 2) }}"
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm bg-gray-50" readonly>
                        <p class="text-xs text-gray-500 mt-1">Amount cannot be edited in current status</p>
                    @endif
                </div>

                <!-- Payment Details Section -->
                @if($dvRequest->modeOfPayments && $dvRequest->modeOfPayments->count() > 0)
                    @foreach($dvRequest->modeOfPayments as $mop)
                        @if($mop->pivot && $mop->pivot->paymentDetail)
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                            </div>
                            @break
                        @endif
                    @endforeach
                    @if(!$dvRequest->modeOfPayments->contains(function($mop) { return $mop->pivot && $mop->pivot->paymentDetail; }))
                        <p class="text-xs text-gray-400">No payment details available</p>
                    @endif
                @endif

                <!-- UACS Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">UACS Code</label>
                    <input type="text" name="uacscode_id" 
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm"
                        placeholder="Type UACS code..." required>
                </div>

              <!-- ADL Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">ADL Code</label>
                    <select name="adlcode_id" 
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" required>
                        <option value="">-- Select ADL Code --</option>
                        @foreach($adlCodes as $adl)
                            <option value="{{ $adl->id }}">
                                {{ $adl->adlcode }} - {{ $adl->description }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Allotment Class -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Allotment Class</label>
                    <select name="allotment_class_id" 
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" required>
                        <option value="">-- Select Allotment Class --</option>
                        @foreach($allotment_classes as $class)
                            <option value="{{ $class->id }}">
                                {{ $class->allotcode }} - {{ $class->description }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- MFO/PAP -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">MFO/PAP</label>
                    <select name="mfopap_id" 
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" required>
                        <option value="">-- Select MFO/PAP --</option>
                        @foreach($mfopaps as $mfopap)
                            <option value="{{ $mfopap->id }}">
                                {{ $mfopap->mfocode }} - {{ $mfopap->mfodescription }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Continuing/Current -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Continuing/Current</label>
                    <select name="contcurrent_id" 
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" required>
                        <option value="">-- Select --</option>
                        @foreach($contcurrents as $cont)
                            <option value="{{ $cont->id }}">
                                {{ $cont->ccode }} - {{ $cont->description }}
                            </option>
                        @endforeach
                    </select>
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
                        <input type="text"  value="{{ $dvRequest->created_at ? $dvRequest->created_at->format('Y-m-d  H:i')  : '' }}"
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
                
                <div id="button-container" class="flex justify-end space-x-3">
                  @if($dvRequest->status === 'ORS_received')
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
                        <a href="{{ route('BudgetOfficer.dvrequests.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
              @elseif($dvRequest->status === 'Incoming-GIP_verified')
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
                          <a href="{{ route('BudgetOfficer.dvrequests.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
             @elseif($dvRequest->status === 'Incoming-TUPAD_verified')
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
                          <a href="{{ route('BudgetOfficer.dvrequests.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
                @elseif($dvRequest->status === 'Incoming-TUPAD_in_process')
                <div class="flex justify-end space-x-3">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Obligate
                    </button>
                    <a href="{{ route('BudgetOfficer.dvrequests.index') }}"
                        onclick="releaseLock(event, {{ $dvRequest->id }})"
                       class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Cancel
                    </a>
                </div>
                @elseif($dvRequest->status === 'Incoming-GIP_in_process')
                <div class="flex justify-end space-x-3">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Obligate
                    </button>
                    <a href="{{ route('BudgetOfficer.dvrequests.index') }}"
                        onclick="releaseLock(event, {{ $dvRequest->id }})"
                       class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Cancel
                    </a>
                </div>
                   @else
                        <a href="{{ route('BudgetOfficer.dvrequests.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow-md rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Requests</h3>
                <ul id="recent-requests" class="space-y-3">
                    <li class="text-sm text-gray-500">Loading...</li>
                </ul>
            </div>
        </div>

    </div>
</div>  

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toast-container"></div>

<div id="message-container" class="fixed top-4 right-4 z-50"></div>
@endsection
<!-- Add jQuery if not already included -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Add Bootstrap if not already included for toast functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Inline Amount Editor Class
class InlineAmountEditor {
    constructor() {
        this.init();
    }

    init() {
        this.makeAmountsEditable();
        this.bindEvents();
    }

    makeAmountsEditable() {
        $('.editable-amount').each(function() {
            const currentAmount = $(this).data('amount');
            const dvId = $(this).data('dv-id');
            const formattedAmount = parseFloat(currentAmount).toFixed(2);
            
            $(this).html(`
                <span class="amount-display">₱${new Intl.NumberFormat('en-PH').format(currentAmount)}</span>
                <input type="number" class="form-control form-control-sm amount-input d-none w-32" 
                       value="${formattedAmount}" min="0.01" step="0.01" data-dv-id="${dvId}">
            `);
        });
    }

    bindEvents() {
        // Click to edit
        $(document).on('click', '.amount-display, .edit-amount-btn', (e) => {
            e.preventDefault();
            const $target = $(e.target).closest('.editable-amount-container').find('.editable-amount');
            if ($target.length) {
                this.startEditing($target);
            }
        });

        // Save on Enter or blur
        $(document).on('keypress', '.amount-input', (e) => {
            if (e.which === 13) { // Enter key
                this.saveAmount($(e.target));
            }
        });

        $(document).on('blur', '.amount-input', (e) => {
            this.saveAmount($(e.target));
        });

        // Cancel on Escape
        $(document).on('keyup', '.amount-input', (e) => {
            if (e.which === 27) { // Escape key
                this.cancelEditing($(e.target));
            }
        });
    }

    startEditing($container) {
        const $display = $container.find('.amount-display');
        const $input = $container.find('.amount-input');
        
        $display.addClass('d-none');
        $input.removeClass('d-none').focus().select();
        
        // Store original value for cancel
        $input.data('original-value', $input.val());
    }

    cancelEditing($input) {
        const $container = $input.closest('.editable-amount');
        const $display = $container.find('.amount-display');
        
        // Restore original value
        $input.val($input.data('original-value'));
        
        $input.addClass('d-none');
        $display.removeClass('d-none');
    }

    saveAmount($input) {
        const dvId = $input.data('dv-id');
        const newAmount = parseFloat($input.val());
        const originalValue = parseFloat($input.data('original-value'));
        const $container = $input.closest('.editable-amount');
        const $display = $container.find('.amount-display');

        // Check if value actually changed
        if (Math.abs(newAmount - originalValue) < 0.01) {
            this.cancelEditing($input);
            return;
        }

        // Validate amount
        if (!newAmount || newAmount <= 0) {
            this.showError('Please enter a valid amount greater than 0');
            $input.focus();
            return;
        }

        // Show loading state
        $input.prop('disabled', true);
        $container.append('<span class="saving-indicator ml-2"><i class="fas fa-spinner fa-spin text-gray-500"></i></span>');

        // Save via AJAX
        $.ajax({
            url: `/BudgetOfficer/dvrequests/${dvId}/update-amount`,
            method: 'PUT',
            data: {
                amount: newAmount,
                remarks: `Amount updated via inline edit from ₱${this.formatNumber(originalValue)} to ₱${this.formatNumber(newAmount)}`,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                // Update display
                $display.text('₱' + this.formatNumber(newAmount));
                $container.data('amount', newAmount);
                
                // Update hidden input for form submission
                $('#amount-hidden').val(newAmount);
                
                this.showSuccess('Amount updated successfully');
                
                // Hide input, show display
                $input.addClass('d-none').prop('disabled', false);
                $display.removeClass('d-none');
            },
            error: (xhr) => {
                let errorMessage = 'Failed to update amount';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                this.showError(errorMessage);
                $input.prop('disabled', false).focus();
            },
            complete: () => {
                $container.find('.saving-indicator').remove();
            }
        });
    }

    formatNumber(number) {
        return new Intl.NumberFormat('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(number);
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showError(message) {
        this.showToast(message, 'error');
    }

    showToast(message, type = 'info') {
        const bgClass = type === 'success' ? 'bg-green-500' : 
                       type === 'error' ? 'bg-red-500' : 'bg-blue-500';
        const icon = type === 'success' ? 'fa-check-circle' : 
                    type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';

        const toast = $(`
            <div class="${bgClass} text-white px-4 py-3 rounded-lg shadow-lg mb-2 transform transition-all duration-300 translate-x-full opacity-0">
                <div class="flex items-center">
                    <i class="fas ${icon} mr-2"></i>
                    <span>${message}</span>
                </div>
            </div>
        `);

        // Ensure toast container exists
        if ($('#toast-container').length === 0) {
            $('body').append('<div class="fixed top-4 right-4 z-50" id="toast-container"></div>');
        }

        $('#toast-container').append(toast);
        
        // Animate in
        setTimeout(() => {
            toast.removeClass('translate-x-full opacity-0');
        }, 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.addClass('translate-x-full opacity-0');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }
}

// Initialize inline editor
$(document).ready(function() {
    new InlineAmountEditor();
});

// Your existing JavaScript functions...
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
         
        const amountInput = document.querySelector('#amount-hidden') || document.querySelector('input[name="amount"]');
        if (amountInput) {
            formData.append('amount', amountInput.value);
        }
        const response = await fetch(`/BudgetOfficer/dvrequests/${dvRequestId}/receive`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: formData
        });

        const responseText = await response.text();
        const data = JSON.parse(responseText);

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
    } finally {
        // Reset button state
        buttonElement.disabled = false;
        buttonText.textContent = 'Receive';
        spinner.classList.add('hidden');
    }
}

async function handleStartProcessing(dvRequestId, buttonElement) {
    const spinner = document.getElementById('start-loading-spinner');
    const buttonText = buttonElement.querySelector('.button-text');
    
    buttonElement.disabled = true;
    buttonText.textContent = 'Starting...';
    spinner.classList.remove('hidden');
    
    try {
        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);

        // Include amount if editable
        const amountInput = document.querySelector('#amount-hidden') || document.querySelector('input[name="amount"]');
        if (amountInput) {
            formData.append('amount', amountInput.value);
        }

        const response = await fetch(`/BudgetOfficer/dvrequests/${dvRequestId}/start-processing`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: formData
        });

        const data = await response.json();

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
    } finally {
        buttonElement.disabled = false;
        buttonText.textContent = 'Start Processing';
        spinner.classList.add('hidden');
    }
}

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
        <a href="{{ route('BudgetOfficer.dvrequests.index') }}"
            class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" onclick="releaseLock(event, {{ $dvRequest->id }})">
            Back
        </a>
    `;
}

// Function to update buttons to verify mode
function updateButtonsToVerifyMode(dvRequestId) {
    const buttonContainer = document.getElementById('button-container');
    
    buttonContainer.innerHTML = `
        <div class="flex justify-end space-x-3">
            <button type="submit" 
                    class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                Obligate
            </button>
            <a href="{{ route('BudgetOfficer.dvrequests.index') }}"
                onclick="releaseLock(event, {{ $dvRequest->id }})"
               class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                Cancel
            </a>
        </div>
    `;
}

// Manual release lock button
function releaseLock(event, id) {
    event.preventDefault(); // stop normal redirect

    fetch(`/BudgetOfficer/dvrequests/${id}/release-lock`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json",
            "Content-Type": "application/json"
        }
    }).then(() => {
        // Redirect after releasing lock
        window.location.href = "{{ route('BudgetOfficer.dvrequests.index') }}";
    }).catch(err => {
        console.error("Release lock failed:", err);
        // Still redirect to index to avoid stuck state
        window.location.href = "{{ route('BudgetOfficer.dvrequests.index') }}";
    });
}

async function loadRecentRequests() {
    try {
        const res = await fetch("{{ route('BudgetOfficer.dvrequests.recent') }}");
        const data = await res.json();
        console.log('Recent requests data:', data); // Debug output

        let html = '';
        if (data.length === 0) {
            html = `<li class="text-sm text-gray-500">No recent requests.</li>`;
        } else {
            data.forEach(r => {
                html += `
                    <li>
                        <a href="/BudgetOfficer/dvrequests/${r.id}" 
                           class="block p-3 bg-gray-50 hover:bg-blue-50 rounded-lg border border-gray-200 transition">
                            <p class="text-sm font-medium text-gray-900">
                                Control #${r.id} • ${r.particulars || ''}
                            </p>
                            <p class="text-sm text-gray-600">
                                ${(r.focal_person?.fname || 'Unknown')} ${(r.focal_person?.lname || '')} • 
                                ${new Date(r.created_at).toLocaleDateString()}
                            </p>
                        </a>
                    </li>`;
            });
        }
        document.getElementById('recent-requests').innerHTML = html;
    } catch (e) {
        console.error("Failed to load recent requests", e);
        document.getElementById('recent-requests').innerHTML = `<li class="text-sm text-red-500">Failed to load requests</li>`;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const dvRequestId = "{{ $dvRequest->id }}";

    // Load recent requests on page load
    loadRecentRequests();

    // Auto-refresh lock every 2 minutes
    const refreshInterval = setInterval(() => {
        fetch(`/BudgetOfficer/dvrequests/${dvRequestId}/refresh-lock`, {
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

    // Release lock when user leaves the page
    window.addEventListener("beforeunload", () => {
        navigator.sendBeacon(`/BudgetOfficer/dvrequests/${dvRequestId}/release-lock`, 
            JSON.stringify({ _token: "{{ csrf_token() }}" }));
    });

    // Auto-refresh recent requests every 30 seconds
    setInterval(loadRecentRequests, 30000);
});
</script>
