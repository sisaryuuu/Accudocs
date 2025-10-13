@extends('layouts.app')

@section('title', 'Validate DV Request - RANCA')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Section - RANCA Processing -->
        <div class="lg:col-span-2 bg-white shadow rounded-xl p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">RANCA - Accounting Unit III</h1>

            <form action="{{ route('Accounting.dvrequests.validate', $dvRequest->id) }}" method="POST" class="space-y-6">
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">DV Number</label>
                        <input type="text" value="{{ $dvRequest->dv_number ?? 'Not assigned' }}" 
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm bg-gray-50" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ORS Number</label>
                        <input type="text" value="{{ $dvRequest->orsRequest->ors_number ?? 'Not assigned' }}" 
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm bg-gray-50" readonly>
                    </div>
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
             <!-- Enhanced Amounts Section for RANCA -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Gross Amount</label>
                    
                    @if(in_array($dvRequest->status, ['RANCA_received', 'RANCA_in_process']))
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
                                Amount is editable while being processed by RANCA
                            </p>
                        </div>
                    @else
                        <!-- Read-only Amount -->
                        <input type="text" value="₱{{ number_format($dvRequest->amount, 2) }}"
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm bg-gray-50" readonly>
                        <p class="text-xs text-gray-500 mt-1">Amount cannot be edited in current status</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Particulars</label>
                    <textarea rows="2" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>{{ $dvRequest->particulars ?? '' }}</textarea>
                </div>
                 @if($dvRequest->program->prcode === 'GIP')

                        <h2 class="text-md font-semibold text-gray-800 mb-3">Attachments Checklist</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                @foreach($dvRequest->gipAttachments as $attachment)
                <div class="flex items-center space-x-2">
                    <input 
                        type="checkbox" 
                        name="attachments[{{ $attachment->name }}]"
                        id="attachment_{{ $loop->index }}"
                        class="h-4 w-4 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                        style="accent-color: #16a34a;"
                         {{ $attachment->is_present ? 'checked' : '' }} disabled
                        @if($dvRequest->status !== 'Incoming-GIP_in_process') @endif>
                    <label 
                        for="attachment_{{ $loop->index }}"
                        class="text-sm cursor-pointer {{ $attachment->is_present ? 'text-green-600 font-semibold' : 'text-gray-700' }}"
                    >
                        {{ $attachment->name }}
                    </label>
                </div>
                @endforeach
            </div>
                @endif
                @if($dvRequest->program->prcode === 'TUPAD' && $attachments->count() > 0)
                    <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            TUPAD Attachments Checklist
                            @if($selectedModality)
                                <span class="text-sm text-blue-600">
                                    (Modality: {{ ucfirst(str_replace('_', ' ', $selectedModality)) }})
                                </span>
                            @endif
                        </h3>

                        <ul class="space-y-2">
                            @foreach($attachments as $item)
                                <li class="flex items-center p-3 bg-white rounded-lg hover:bg-gray-50 transition">
                                    <input type="checkbox" 
                                        id="attachment_{{ $item->id }}"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3"
                                        {{ $item->is_present ? 'checked' : '' }} disabled>
                                    <label for="attachment_{{ $item->id }}" class="text-sm text-gray-900 flex-1">
                                        {{ $item->name }}
                                    </label>
                                    @if($item->is_present)
                                        <span class="text-xs text-green-600 font-medium">✓ Present</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                 @if($dvRequest->program->prcode === 'TEV')
                   {{-- TEV Attachments Section --}}
        <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-700 mb-3">
                TEV Attachments 
                @if($dvRequest->tev_modality)
                    <span class="text-blue-600">({{ ucfirst($dvRequest->tev_modality) }})</span>
                @endif
            </label>

            @if($dvRequest->tev_modality && $tevAttachments && $tevAttachments->count() > 0)
                <ul class="space-y-2">
                    @foreach($tevAttachments as $item)
                        <li class="flex items-center p-3 bg-white rounded-lg hover:bg-gray-50 transition border border-gray-200">
                            <input type="checkbox" 
                                name="tev_attachments[{{ $item->id }}]" 
                                value="1"
                                id="tev_attachment_{{ $item->id }}"
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3"
                                {{ $item->is_present ? 'checked' : '' }} disabled>
                            <label for="tev_attachment_{{ $item->id }}" class="text-sm text-gray-900 cursor-pointer flex-1">
                                {{ $item->name }}
                            </label>
                            @if($item->is_present)
                                <span class="text-xs text-green-600 font-medium bg-green-50 px-2 py-1 rounded">✓ Present</span>
                            @else
                                <span class="text-xs text-gray-400 font-medium">Not Present</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                
                <div class="mt-4 p-3 bg-blue-50 border-l-4 border-blue-400 rounded">
                    <p class="text-xs text-blue-700">
                        <strong>Note:</strong> Check/uncheck items to update attachment status when new physical documents arrive.
                    </p>
                </div>
            @elseif(!$dvRequest->tev_modality)
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800">
                        <strong>⚠️ No Modality Selected</strong><br>
                        No modality was selected by IMSD for this request. Please contact IMSD to select a modality first.
                    </p>
                </div>
            @else
                <div class="p-4 bg-gray-100 border border-gray-300 rounded-lg">
                    <p class="text-sm text-gray-600">
                        No attachments found for the selected modality ({{ $dvRequest->tev_modality }}).
                    </p>
                </div>
            @endif
        </div>    
        @endif
               

                <!-- DV and ORS Numbers -->
                

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

                <!-- ORS Details (Read-only for RANCA) -->
                @if($dvRequest->orsRequest)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-900 mb-3">ORS Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-blue-700">UACS Code</label>
                            <input type="text" value="{{ $dvRequest->orsRequest->uacscode_id ?? 'N/A' }}" 
                                   class="mt-1 w-full rounded-lg border-blue-300 shadow-sm text-xs bg-blue-50" readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-blue-700">ADL Code</label>
                            <input type="text" value="{{ $dvRequest->orsRequest->adlcode_id->adlcode ?? 'N/A' }}" 
                                   class="mt-1 w-full rounded-lg border-blue-300 shadow-sm text-xs bg-blue-50" readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-blue-700">Allotment Class</label>
                            <input type="text" value="{{ $dvRequest->orsRequest->allotment_class_id->allotcode ?? 'N/A' }}" 
                                   class="mt-1 w-full rounded-lg border-blue-300 shadow-sm text-xs bg-blue-50" readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-blue-700">MFO/PAP</label>
                            <input type="text" value="{{ $dvRequest->orsRequest->mfopap_id ?? 'N/A' }}" 
                                   class="mt-1 w-full rounded-lg border-blue-300 shadow-sm text-xs bg-blue-50" readonly>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Processing Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Processed By</label>
                        <input type="text" value="{{ auth()->user()->fname ?? '' }} {{ auth()->user()->lname ?? '' }}" 
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                </div>
  
                <!-- Remarks -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">RANCA Validation Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3" 
                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm"
                              placeholder="Enter validation remarks or notes...">{{ old('remarks') }}</textarea>
                </div>
                
                <div id="button-container" class="flex justify-end space-x-3">
                    @if($dvRequest->status === 'Outgoing_finalized')
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
                        <a href="{{ route('Accounting.dvrequests.index') }}"
                           class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
                           onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
                    @elseif($dvRequest->status === 'RANCA_received')
                        <button type="button"
                                onclick="handleStartProcessing({{ $dvRequest->id }}, this)"
                                class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200"
                                id="start-btn">
                                <span class="button-text">Start Processing</span>
                                <svg class="animate-spin -mr-1 ml-3 h-5 w-5 text-white hidden" id="start-loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                        </button>
                        <a href="{{ route('Accounting.dvrequests.index') }}"
                           class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
                           onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
                    @elseif($dvRequest->status === 'RANCA_in_process')
                        <div class="flex justify-end space-x-3">
                            <button type="submit" 
                                    class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                                Validate & Finalize
                            </button>
                            <a href="{{ route('Accounting.dvrequests.index') }}"
                               onclick="releaseLock(event, {{ $dvRequest->id }})"
                               class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                                Cancel
                            </a>
                        </div>
                    @else
                        <a href="{{ route('Accounting.dvrequests.index') }}"
                           class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
                           onclick="releaseLock(event, {{ $dvRequest->id }})">
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

<!-- Add jQuery and Bootstrap for toast functionality -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Inline Amount Editor for RANCA
class RANCAAmountEditor {
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
            if (e.which === 13) {
                this.saveAmount($(e.target));
            }
        });

        $(document).on('blur', '.amount-input', (e) => {
            this.saveAmount($(e.target));
        });

        // Cancel on Escape
        $(document).on('keyup', '.amount-input', (e) => {
            if (e.which === 27) {
                this.cancelEditing($(e.target));
            }
        });
    }

    startEditing($container) {
        const $display = $container.find('.amount-display');
        const $input = $container.find('.amount-input');
        
        $display.addClass('d-none');
        $input.removeClass('d-none').focus().select();
        $input.data('original-value', $input.val());
    }

    cancelEditing($input) {
        const $container = $input.closest('.editable-amount');
        const $display = $container.find('.amount-display');
        
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

        if (Math.abs(newAmount - originalValue) < 0.01) {
            this.cancelEditing($input);
            return;
        }

        if (!newAmount || newAmount <= 0) {
            this.showError('Please enter a valid amount greater than 0');
            $input.focus();
            return;
        }

        $input.prop('disabled', true);
        $container.append('<span class="saving-indicator ml-2"><i class="fas fa-spinner fa-spin text-gray-500"></i></span>');

        $.ajax({
            url: `/Accounting/dvrequests/${dvId}/update-amount`,
            method: 'PUT',
            data: {
                amount: newAmount,
                remarks: `Amount updated by RANCA from ₱${this.formatNumber(originalValue)} to ₱${this.formatNumber(newAmount)}`,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                $display.text('₱' + this.formatNumber(newAmount));
                $container.data('amount', newAmount);
                $('#amount-hidden').val(newAmount);
                
                this.showSuccess('Amount updated successfully');
                $input.addClass('d-none').prop('disabled', false);
                $display.removeClass('d-none');
            },
            error: (xhr) => {
                let errorMessage = 'Failed to update amount';
                if (xhr.responseJSON?.message) {
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

        if ($('#toast-container').length === 0) {
            $('body').append('<div class="fixed top-4 right-4 z-50" id="toast-container"></div>');
        }

        $('#toast-container').append(toast);
        setTimeout(() => toast.removeClass('translate-x-full opacity-0'), 100);
        setTimeout(() => {
            toast.addClass('translate-x-full opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize
$(document).ready(function() {
    new RANCAAmountEditor();
});

// RANCA-specific functions
function showMessage(message, type = 'success') {
    const container = document.getElementById('message-container');
    const messageDiv = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    
    messageDiv.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg mb-4 transform transition-all duration-300 translate-x-full opacity-0`;
    messageDiv.textContent = message;
    container.appendChild(messageDiv);
    
    setTimeout(() => messageDiv.classList.remove('translate-x-full', 'opacity-0'), 100);
    setTimeout(() => {
        messageDiv.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => messageDiv.remove(), 300);
    }, 3000);
}

async function handleReceiveAction(dvRequestId, buttonElement) {
    const spinner = document.getElementById('loading-spinner');
    const buttonText = buttonElement.querySelector('.button-text');
    
    buttonElement.disabled = true;
    buttonText.textContent = 'Receiving...';
    spinner.classList.remove('hidden');
    
    try {
        const response = await fetch(`/Accounting/dvrequests/${dvRequestId}/receive`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const data = await response.json();

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
        const response = await fetch(`/Accounting/dvrequests/${dvRequestId}/start-processing`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showMessage(data.message, 'success');
            updateButtonsToValidateMode(dvRequestId);
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
        <a href="{{ route('Accounting.dvrequests.index') }}"
           class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
           onclick="releaseLock(event, {{ $dvRequest->id }})">
            Back
        </a>
    `;
}

function updateButtonsToValidateMode(dvRequestId) {
    const buttonContainer = document.getElementById('button-container');
    buttonContainer.innerHTML = `
        <div class="flex justify-end space-x-3">
            <button type="submit" 
                    class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                Validate & Finalize
            </button>
            <a href="{{ route('Accounting.dvrequests.index') }}"
               onclick="releaseLock(event, {{ $dvRequest->id }})"
               class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                Cancel
            </a>
        </div>
    `;
}

function releaseLock(event, id) {
    event.preventDefault();

    fetch(`/Accounting/dvrequests/${id}/release-lock`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            "Accept": "application/json",
            "Content-Type": "application/json"
        }
    }).then(() => {
        window.location.href = "{{ route('Accounting.dvrequests.index') }}";
    }).catch(err => {
        console.error("Release lock failed:", err);
        window.location.href = "{{ route('Accounting.dvrequests.index') }}";
    });
}

async function loadRecentRequests() {
    try {
        const res = await fetch("{{ route('Accounting.dvrequests.recent') }}");
        const data = await res.json();

        let html = '';
        if (data.length === 0) {
            html = `<li class="text-sm text-gray-500">No recent requests.</li>`;
        } else {
            data.forEach(r => {
                html += `
                    <li>
                        <a href="/Accounting/dvrequests/${r.id}" 
                           class="block p-3 bg-gray-50 hover:bg-blue-50 rounded-lg border border-gray-200 transition">
                            <p class="text-sm font-medium text-gray-900">
                                Control #${r.id} • DV: ${r.dv_number || 'Pending'}
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
    setInterval(() => {
        fetch(`/Accounting/dvrequests/${dvRequestId}/refresh-lock`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
        navigator.sendBeacon(`/Accounting/dvrequests/${dvRequestId}/release-lock`, 
            JSON.stringify({ _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content') }));
    });

    // Auto-refresh recent requests every 30 seconds
    setInterval(loadRecentRequests, 30000);
});
</script>
