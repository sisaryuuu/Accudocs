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
                <!-- GIP Attachments Checklist -->
                <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">GIP Attachments Checklist</h3>
                        
                        <!-- Check All/Uncheck All Buttons -->
                        @if($dvRequest->gipAttachments && $dvRequest->gipAttachments->count() > 0)
                            <div class="flex items-center space-x-3">
                                <button type="button" 
                                        onclick="checkAllGIPAttachments()"
                                        class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Check All
                                </button>
                                <button type="button" 
                                        onclick="uncheckAllGIPAttachments()"
                                        class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Uncheck All
                                </button>
                            </div>
                        @endif
                    </div>

                    @if($dvRequest->gipAttachments && $dvRequest->gipAttachments->count() > 0)
                        <ul class="space-y-2">
                            @foreach($dvRequest->gipAttachments as $attachment)
                                <li class="flex items-center p-3 rounded-lg transition-all duration-200 
                                    {{ $attachment->is_present ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                                    <input type="checkbox"
                                        name="gip_attachments[{{ $attachment->id }}]"
                                        id="gip_attachment_{{ $attachment->id }}"
                                        value="1"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3 attachment-checkbox"
                                        {{ $attachment->is_present ? 'checked' : '' }}
                                        {{ $dvRequest->status === 'Incoming-GIP_received' ? '' : 'disabled' }}>
                                    <label for="gip_attachment_{{ $attachment->id }}" class="text-sm text-gray-900 cursor-pointer flex-1">
                                        {{ $attachment->name }}
                                    </label>
                                    <span class="text-xs font-medium px-2 py-1 rounded-full 
                                        {{ $attachment->is_present ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $attachment->is_present ? '✓ Present' : '✗ Missing' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                        
                        <!-- Summary -->
                        <div class="mt-4 flex items-center justify-between text-sm">
                            <div class="flex items-center space-x-4">
                                <span class="flex items-center">
                                    <span class="w-3 h-3 bg-green-500 rounded-full mr-1"></span>
                                    <span class="text-green-700" id="gip-present-count">Present: {{ $dvRequest->gipAttachments->where('is_present', true)->count() }}</span>
                                </span>
                                <span class="flex items-center">
                                    <span class="w-3 h-3 bg-red-500 rounded-full mr-1"></span>
                                    <span class="text-red-700" id="gip-missing-count">Missing: {{ $dvRequest->gipAttachments->where('is_present', false)->count() }}</span>
                                </span>
                            </div>
                            <span class="text-gray-600" id="gip-total-count">Total: {{ $dvRequest->gipAttachments->count() }}</span>
                        </div>
                        
                        <p class="text-xs text-gray-500 mt-4">
                            <strong>Note:</strong> Check/uncheck items to update attachment status when new physical documents arrive.
                        </p>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <span class="text-yellow-800">No attachments available for this GIP request.</span>
                            </div>
                            <p class="text-sm text-yellow-700 mt-2">
                                The attachments checklist should be initialized automatically. 
                                @if($dvRequest->status === 'IMSD_verified')
                                    Please receive this document first to initialize the checklist.
                                @else
                                    Please contact system administrator if this issue persists.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
                @endif

                @if($dvRequest->program->prcode === 'TUPAD')
                <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            TUPAD Attachments Checklist
                            @if($selectedModality)
                                <span class="text-sm font-normal text-blue-600">
                                    (Modality: {{ ucfirst(str_replace('_', ' ', $selectedModality)) }})
                                </span>
                            @endif
                        </h3>
                        
                        <!-- Check All/Uncheck All Buttons -->
                        @if($selectedModality && $attachments && $attachments->count() > 0)
                            <div class="flex items-center space-x-3">
                                <button type="button" 
                                        onclick="checkAllTUPADAttachments()"
                                        class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Check All
                                </button>
                                <button type="button" 
                                        onclick="uncheckAllTUPADAttachments()"
                                        class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Uncheck All
                                </button>
                            </div>
                        @endif
                    </div>
                
                    @if($selectedModality && $attachments && $attachments->count() > 0)
                        <ul class="space-y-2">
                            @foreach($attachments as $item)
                                <li class="flex items-center p-3 rounded-lg transition-all duration-200 
                                    {{ $item->is_present ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                                    <input type="checkbox" 
                                        name="attachments[{{ $item->id }}]" 
                                        value="1"
                                        id="attachment_{{ $item->id }}"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3 attachment-checkbox"
                                        {{ $item->is_present ? 'checked' : '' }}>
                                    <label for="attachment_{{ $item->id }}" class="text-sm text-gray-900 cursor-pointer flex-1">
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
                                    <span class="text-green-700" id="tupad-present-count">Present: {{ $attachments->where('is_present', true)->count() }}</span>
                                </span>
                                <span class="flex items-center">
                                    <span class="w-3 h-3 bg-red-500 rounded-full mr-1"></span>
                                    <span class="text-red-700" id="tupad-missing-count">Missing: {{ $attachments->where('is_present', false)->count() }}</span>
                                </span>
                            </div>
                            <span class="text-gray-600" id="tupad-total-count">Total: {{ $attachments->count() }}</span>
                        </div>
                
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
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            TEV Attachments Checklist
                            @if($dvRequest->tev_modality)
                                <span class="text-sm font-normal text-blue-600">
                                    (Modality: {{ ucfirst(str_replace('_', ' ', $dvRequest->tev_modality)) }})
                                </span>
                            @endif
                        </h3>
                        
                        <!-- Check All/Uncheck All Buttons -->
                        @if($dvRequest->tev_modality && $tevAttachments && $tevAttachments->count() > 0)
                            <div class="flex items-center space-x-3">
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
                        @endif
                    </div>

                    @if($dvRequest->tev_modality && $tevAttachments && $tevAttachments->count() > 0)
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
                                    <span class="text-green-700" id="tev-present-count">Present: {{ $tevAttachments->where('is_present', true)->count() }}</span>
                                </span>
                                <span class="flex items-center">
                                    <span class="w-3 h-3 bg-red-500 rounded-full mr-1"></span>
                                    <span class="text-red-700" id="tev-missing-count">Missing: {{ $tevAttachments->where('is_present', false)->count() }}</span>
                                </span>
                            </div>
                            <span class="text-gray-600" id="tev-total-count">Total: {{ $tevAttachments->count() }}</span>
                        </div>

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

// Check All GIP Attachments
function checkAllGIPAttachments() {
    const checkboxes = document.querySelectorAll('input[name^="gip_attachments["]');
    checkboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    showMessage('All GIP attachments marked as present', 'success');
}

// Uncheck All GIP Attachments
function uncheckAllGIPAttachments() {
    const checkboxes = document.querySelectorAll('input[name^="gip_attachments["]');
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            checkbox.checked = false;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    showMessage('All GIP attachments marked as missing', 'success');
}

// Check All TUPAD Attachments
function checkAllTUPADAttachments() {
    const checkboxes = document.querySelectorAll('input[name^="attachments["]');
    checkboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    showMessage('All TUPAD attachments marked as present', 'success');
}

// Uncheck All TUPAD Attachments
function uncheckAllTUPADAttachments() {
    const checkboxes = document.querySelectorAll('input[name^="attachments["]');
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            checkbox.checked = false;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    showMessage('All TUPAD attachments marked as missing', 'success');
}

// Check All TEV Attachments
function checkAllTEVAttachments() {
    const checkboxes = document.querySelectorAll('input[name^="tev_attachments["]');
    checkboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    showMessage('All TEV attachments marked as present', 'success');
}

// Uncheck All TEV Attachments
function uncheckAllTEVAttachments() {
    const checkboxes = document.querySelectorAll('input[name^="tev_attachments["]');
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            checkbox.checked = false;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    showMessage('All TEV attachments marked as missing', 'success');
}

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

    // Handle attachment checkboxes - Update colors when changed
    const attachmentCheckboxes = document.querySelectorAll('.attachment-checkbox');
    attachmentCheckboxes.forEach(cb => {
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
            
            // Update summary counts based on program type
            updateAttachmentSummary();
        });
    });
    
    // Function to update attachment summary
    function updateAttachmentSummary() {
        // GIP Summary
        const gipCheckboxes = document.querySelectorAll('input[name^="gip_attachments["]');
        if (gipCheckboxes.length > 0) {
            const gipTotal = gipCheckboxes.length;
            const gipPresent = Array.from(gipCheckboxes).filter(cb => cb.checked).length;
            const gipMissing = gipTotal - gipPresent;
            
            const gipPresentSpan = document.getElementById('gip-present-count');
            const gipMissingSpan = document.getElementById('gip-missing-count');
            const gipTotalSpan = document.getElementById('gip-total-count');
            
            if (gipPresentSpan) gipPresentSpan.textContent = `Present: ${gipPresent}`;
            if (gipMissingSpan) gipMissingSpan.textContent = `Missing: ${gipMissing}`;
            if (gipTotalSpan) gipTotalSpan.textContent = `Total: ${gipTotal}`;
        }
        
        // TUPAD Summary
        const tupadCheckboxes = document.querySelectorAll('input[name^="attachments["]');
        if (tupadCheckboxes.length > 0) {
            const tupadTotal = tupadCheckboxes.length;
            const tupadPresent = Array.from(tupadCheckboxes).filter(cb => cb.checked).length;
            const tupadMissing = tupadTotal - tupadPresent;
            
            const tupadPresentSpan = document.getElementById('tupad-present-count');
            const tupadMissingSpan = document.getElementById('tupad-missing-count');
            const tupadTotalSpan = document.getElementById('tupad-total-count');
            
            if (tupadPresentSpan) tupadPresentSpan.textContent = `Present: ${tupadPresent}`;
            if (tupadMissingSpan) tupadMissingSpan.textContent = `Missing: ${tupadMissing}`;
            if (tupadTotalSpan) tupadTotalSpan.textContent = `Total: ${tupadTotal}`;
        }
        
        // TEV Summary
        const tevCheckboxes = document.querySelectorAll('input[name^="tev_attachments["]');
        if (tevCheckboxes.length > 0) {
            const tevTotal = tevCheckboxes.length;
            const tevPresent = Array.from(tevCheckboxes).filter(cb => cb.checked).length;
            const tevMissing = tevTotal - tevPresent;
            
            const tevPresentSpan = document.getElementById('tev-present-count');
            const tevMissingSpan = document.getElementById('tev-missing-count');
            const tevTotalSpan = document.getElementById('tev-total-count');
            
            if (tevPresentSpan) tevPresentSpan.textContent = `Present: ${tevPresent}`;
            if (tevMissingSpan) tevMissingSpan.textContent = `Missing: ${tevMissing}`;
            if (tevTotalSpan) tevTotalSpan.textContent = `Total: ${tevTotal}`;
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