@extends('layouts.app')

@section('title', 'Obligate DV Request')

@section('content')
<div class="min-h-screen flex items-center justify-center py-8 px-4"> <!-- Centered wrapper -->
    <div class="w-full max-w-screen-2xl"> <!-- Full width container -->
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
            
            <!-- Left Section - ORS Processing - Now spans 4 columns -->
            <div class="xl:col-span-4 bg-white shadow rounded-xl p-6">
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

                    <!-- Basic Info - Now 4 columns -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Responsibility Center</label>
                            <p class="mt-1 text-sm text-gray-900">
                              {{ $dvRequest->respocenter->rccode ?? '' }} {{ $dvRequest->respocenter->description ?? '' }}
                            </p>
                        </div>
                    </div>

                    
                
                    <!-- Enhanced Amounts Section -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gross Amount</label>
                        
                        @if(in_array($dvRequest->status, ['ORS_received', 'ORS_in_process', 'ORS_onhold']))
                            <!-- Amount Display with Edit Button -->
                            <div class="mt-1">
                                <div class="flex items-center space-x-3">
                                    <div class="amount-display bg-white border border-gray-300 rounded-lg px-4 py-2 min-w-[150px]">
                                        <span class="text-lg font-medium">₱{{ number_format($dvRequest->amount, 2) }}</span>
                                    </div>
                                    <button type="button" 
                                            onclick="openAmountModal({{ $dvRequest->id }}, {{ $dvRequest->amount }})"
                                            class="edit-amount-btn text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-colors duration-200"
                                            title="Edit amount">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Particulars</label>
                            <textarea 
                                class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm h-48" 
                                readonly>{{ $dvRequest->particulars ?? '' }}
                            </textarea>
                        </div>
                    </div>
                    <!-- Payment Details Section - Now 4 columns -->
                    @if($dvRequest->modeOfPayments && $dvRequest->modeOfPayments->count() > 0)
                        @foreach($dvRequest->modeOfPayments as $mop)
                            @if($mop->pivot && $mop->pivot->paymentDetail)
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                                        <input type="text" value="{{ $mop->pivot->paymentDetail->bank_name ?? 'N/A' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Bank Account Number</label>
                                        <input type="text" value="{{ $mop->pivot->paymentDetail->bank_accnum ?? 'N/A' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                                    </div>
                                    <div class="md:col-span-2">
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

                    <!-- Attachments Display Section -->
                    @if($dvRequest->program->prcode === 'GIP')
                    <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h2 class="text-lg font-semibold text-gray-800 mb-3">GIP Attachments</h2>
                        
                        @if($attachments && $attachments->count() > 0)
                            <div class="space-y-2">
                                @foreach($attachments as $attachment)
                                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                    <div class="flex items-center space-x-3">
                                        @if($attachment->is_present)
                                            <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        @endif
                                        <span class="text-sm font-medium text-gray-900 {{ $attachment->is_present ? '' : 'text-gray-500' }}">
                                            {{ $attachment->name }}
                                        </span>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full {{ $attachment->is_present ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $attachment->is_present ? 'Present' : 'Missing' }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 italic">No attachments available for this GIP request.</p>
                        @endif
                    </div>
                    @endif

                    @if($dvRequest->program->prcode === 'TUPAD')
                    <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            TUPAD Attachments
                            @if($selectedModality)
                                <span class="text-sm font-normal text-blue-600">
                                    (Modality: {{ ucfirst(str_replace('_', ' ', $selectedModality)) }})
                                </span>
                            @endif
                        </h3>

                        @if($selectedModality && $attachments && $attachments->count() > 0)
                            <div class="space-y-2">
                                @foreach($attachments as $attachment)
                                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                    <div class="flex items-center space-x-3">
                                        @if($attachment->is_present)
                                            <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18-6M6 6l12 12"></path>
                                            </svg>
                                        @endif
                                        <span class="text-sm font-medium text-gray-900 {{ $attachment->is_present ? '' : 'text-gray-500' }}">
                                            {{ $attachment->name }}
                                        </span>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full {{ $attachment->is_present ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $attachment->is_present ? 'Present' : 'Missing' }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
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
                            TEV Attachments
                            @if($tevSelectedModality)
                                <span class="text-sm font-normal text-blue-600">
                                    (Modality: {{ ucfirst(str_replace('_', ' ', $tevSelectedModality)) }})
                                </span>
                            @endif
                        </h3>

                        @if($tevSelectedModality && $tevAttachments && $tevAttachments->count() > 0)
                            <div class="space-y-2">
                                @foreach($tevAttachments as $attachment)
                                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                                    <div class="flex items-center space-x-3">
                                        @if($attachment->is_present)
                                            <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18-6M6 6l12 12"></path>
                                            </svg>
                                        @endif
                                        <span class="text-sm font-medium text-gray-900 {{ $attachment->is_present ? '' : 'text-gray-500' }}">
                                            {{ $attachment->name }}
                                        </span>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full {{ $attachment->is_present ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $attachment->is_present ? 'Present' : 'Missing' }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        @elseif(!$tevSelectedModality)
                            <p class="text-gray-500 italic">No modality was selected by IMSD for this request.</p>
                        @else
                            <p class="text-gray-400">No attachments found for the selected modality.</p>
                        @endif
                    </div>
                    @endif

                    <!-- UACS Code Section -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            UACS Codes
                            <span class="text-red-500">*</span>
                        </label>
                        
                        <!-- Dynamic UACS Rows Container -->
                        <div id="uacs-container" class="space-y-3">
                            <!-- Rows will be added by JavaScript -->
                        </div>
                        
                        <!-- Add More Button -->
                        <button type="button" 
                                onclick="addUACSRow()"
                                class="mt-3 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Another UACS Code
                        </button>
                        
                        <p class="text-xs text-gray-500 mt-2">
                            <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            Select UACS codes that apply to this ORS request
                        </p>
                    </div>  
                                
                    <!-- ADL Code Section with Multiple Entry -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            ADL Codes
                            <span class="text-red-500">*</span>
                        </label>
                        
                        <!-- Dynamic ADL Rows Container -->
                        <div id="adl-container" class="space-y-3">
                            <!-- Rows will be added by JavaScript -->
                        </div>
                        
                        <!-- Add More Button -->
                        <button type="button" 
                                onclick="addADLRow()"
                                class="mt-3 inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Another ADL Code
                        </button>
                        
                        <!-- Add New ADL Code Quick Button -->
                        <button type="button" 
                                onclick="openAddADLModal()"
                                class="mt-2 ml-2 inline-flex items-center px-3 py-1.5 text-sm bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create New ADL Code
                        </button>
                        
                        <p class="text-xs text-gray-500 mt-2">
                            <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            Select ADL codes that apply to this ORS request
                        </p>
                    </div>

                    <!-- Allotment Class -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Allotment Class</label>
                        <select name="allotment_class_id" 
                            id="allotment-class-select"
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
                            id="mfopap-select"
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
                            id="contcurrent-select"
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
                    
                  @if(in_array($dvRequest->status, ['Incoming-GIP_verified', 'Incoming-TUPAD_verified', 'Incoming-TEV_verified']))
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
                    @elseif($dvRequest->status === 'ORS_received')
                    <div class="flex justify-end space-x-3">
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Obligate
                        </button>
                        <button type="button"
                                onclick="openRejectModal({{ $dvRequest->id }})"
                                class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Reject
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

            <!-- Right Section - If needed for additional content -->
            <div class="xl:col-span-1">
                <!-- You can add any sidebar content here if needed -->
            </div>
        </div>
    </div>
</div>

<!-- Add ADL Modal -->
<div id="addADLModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Add New ADL Code</h3>
            <button onclick="closeAddADLModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18-6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="addADLForm" class="space-y-4">
            <!-- ADL Code Input -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ADL Code <span class="text-red-500">*</span></label>
                <input type="text" 
                       id="new-adl-code" 
                       name="adlcode"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="e.g., 01-001"
                       required>
                <p id="adl-code-error" class="text-xs text-red-500 mt-1 hidden"></p>
            </div>
            
            <!-- Description Input -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
                <textarea id="new-adl-description" 
                          name="description"
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Enter ADL code description"
                          required></textarea>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" 
                        onclick="closeAddADLModal()"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors duration-200">
                    Cancel
                </button>
                <button type="submit"
                        id="saveADLBtn"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200">
                    <span class="btn-text">Save ADL Code</span>
                    <svg class="animate-spin hidden h-4 w-4 text-white inline ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Amount Edit Modal -->
<div id="amountModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Edit Amount</h3>
            <button onclick="closeAmountModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18-6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="amountForm" class="space-y-4">
            <!-- Current Amount Display -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Amount</label>
                <div class="bg-gray-50 border border-gray-300 rounded-lg px-4 py-2">
                    <span id="current-amount-display" class="text-lg font-medium text-gray-900">₱0.00</span>
                </div>
            </div>
            
            <!-- New Amount Input -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    New Amount
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
                    <input type="number" 
                           id="new-amount" 
                           name="amount"
                           step="0.01"
                           min="0.01"
                           class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="0.00"
                           required>
                </div>
                <p id="amount-error" class="text-xs text-red-500 mt-1 hidden"></p>
            </div>
            
            <!-- Reason for Change -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Reason for Change
                    <span class="text-red-500">*</span>
                </label>
                <textarea id="amount-reason" 
                          name="reason"
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Please provide a reason for changing the amount..."
                          required></textarea>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" 
                        onclick="closeAmountModal()"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors duration-200">
                    Cancel
                </button>
                <button type="submit"
                        id="saveAmountBtn"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200">
                    <span class="btn-text">Update Amount</span>
                    <svg class="animate-spin hidden h-4 w-4 text-white inline ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Reject DV Request</h3>
            <button onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18-6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="rejectForm" class="space-y-4">
            <!-- Rejection Reason -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Reason for Rejection
                    <span class="text-red-500">*</span>
                </label>
                <textarea id="reject-reason" 
                          name="reject_reason"
                          rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                          placeholder="Please provide a detailed reason for rejecting this DV request..."
                          required></textarea>
                <p id="reject-error" class="text-xs text-red-500 mt-1 hidden"></p>
            </div>
            
            <!-- Additional Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes (Optional)</label>
                <textarea id="reject-notes" 
                          name="reject_notes"
                          rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                          placeholder="Any additional notes or instructions..."></textarea>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" 
                        onclick="closeRejectModal()"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors duration-200">
                    Cancel
                </button>
                <button type="submit"
                        id="rejectBtn"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200">
                    <span class="btn-text">Confirm Reject</span>
                    <svg class="animate-spin hidden h-4 w-4 text-white inline ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toast-container"></div>
<div id="message-container" class="fixed top-4 right-4 z-50"></div>

@endsection

<!-- jQuery - REQUIRED for Select2 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- Bootstrap Bundle - For toast functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
/* Select2 Custom Styling */
.select2-container--default .select2-selection--single {
    height: 42px;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 40px;
    padding-left: 12px;
    color: #374151;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px;
    right: 8px;
}

.select2-dropdown {
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #3b82f6;
}

.select2-search__field {
    border: 1px solid #d1d5db !important;
    border-radius: 0.375rem !important;
}

/* Animation styles */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideOut {
    to {
        opacity: 0;
        transform: translateX(20px);
    }
}

.remove-btn:hover {
    transform: scale(1.1);

    
}
/* UACS Amount Validation Styles */
#uacs-amount-validation {
    transition: all 0.3s ease-in-out;
}

.uacs-amount-input:invalid {
    border-color: #ef4444;
}

.uacs-amount-input:focus:invalid {
    ring-color: #ef4444;
}

button[type="submit"]:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

/* Highlight effect for amount inputs */
.uacs-amount-input:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>

<script>
// ====================================
// GLOBAL VARIABLES
// ====================================

// Pass UACS data from Laravel to JavaScript
const uacsSelectableData = @json($selectableUacsCodes ?? []);

// Pass ADL data from Laravel to JavaScript
const adlSelectableData = @json($adlCodes->map(function($adl) {
    return [
        'id' => $adl->id,
        'code' => $adl->adlcode,
        'description' => $adl->description
    ];
}) ?? []);

let uacsRowCounter = 0;
let selectedUACSIds = new Set();

let adlRowCounter = 0;
let selectedADLIds = new Set();

let currentRejectDvRequestId = null;
let currentDvRequestId = null;
let currentOriginalAmount = 0;
// ====================================
// UACS DYNAMIC FORM FUNCTIONS WITH AMOUNT SUPPORT
// ====================================
// Function to get current gross amount dynamically
function getGrossAmount() {
    // Try to get from hidden input first (updated amount)
    const hiddenAmount = document.getElementById('amount-hidden');
    if (hiddenAmount && hiddenAmount.value) {
        return parseFloat(hiddenAmount.value);
    }
    
    // Try to get from amount display
    const amountDisplay = document.querySelector('.amount-display span');
    if (amountDisplay) {
        const amountText = amountDisplay.textContent.replace(/[₱,]/g, '').trim();
        return parseFloat(amountText) || 0;
    }
    
    // Fallback to initial value
    return {{ $dvRequest->amount }};
}

let grossAmount = getGrossAmount(); // Initialize with current amount

function addUACSRow(selectedId = null, amount = null) {
    uacsRowCounter++;
    const rowId = `uacs-row-${uacsRowCounter}`;
    
    const container = document.getElementById('uacs-container');
    
    const rowHTML = `
        <div class="uacs-row flex items-start gap-3 p-4 bg-gray-50 rounded-lg border border-gray-200" 
             id="${rowId}" 
             data-row-id="${uacsRowCounter}"
             style="animation: slideIn 0.3s ease-out;">
            <div class="flex-shrink-0 w-8 pt-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-medium row-number">
                    ${uacsRowCounter}
                </span>
            </div>
            
            <div class="flex-1 space-y-2">
                <!-- UACS Code Selector -->
                <select name="uacscode_ids[]" 
                        id="uacs-select-${uacsRowCounter}"
                        class="uacs-select w-full rounded-lg border-gray-300 shadow-sm text-sm" 
                        data-row-id="${uacsRowCounter}"
                        required>
                    <option value="">-- Select UACS Code --</option>
                </select>
                
                <!-- Amount Input -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Amount:</label>
                    <div class="relative flex-1">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
                        <input type="number" 
                               name="uacs_amounts[]"
                               id="uacs-amount-${uacsRowCounter}"
                               step="0.01"
                               min="0.01"
                               value="${amount || ''}"
                               placeholder="0.00"
                               class="uacs-amount-input w-full pl-8 pr-3 py-1.5 rounded-lg border-gray-300 shadow-sm text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                               oninput="validateUACSAmounts()"
                               required>
                    </div>
                </div>
            </div>
            
            <button type="button" 
                    onclick="removeUACSRow('${rowId}', ${uacsRowCounter})"
                    class="remove-btn flex-shrink-0 p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200"
                    title="Remove this UACS code">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', rowHTML);
    initializeUACSSelect2(uacsRowCounter, selectedId);
    updateUACSRowNumbers();
    validateUACSAmounts(); // Validate after adding new row
}

function generateUACSOptions(selectedId = null) {
    if (!uacsSelectableData || uacsSelectableData.length === 0) {
        return '<option value="">No UACS codes available</option>';
    }
    
    return uacsSelectableData
        .filter(uacs => !selectedUACSIds.has(uacs.id) || uacs.id === selectedId)
        .map(uacs => {
            let indent = '&nbsp;&nbsp;&nbsp;'.repeat(uacs.level || 0);
            
            return `<option value="${uacs.id}" 
                            ${uacs.id === selectedId ? 'selected' : ''}
                            data-level="${uacs.level || 0}"
                            data-full-path="${uacs.full_path || ''}">
                ${indent}${uacs.code} - ${uacs.description}
            </option>`;
        })
        .join('');
}

function initializeUACSSelect2(rowId, selectedId = null) {
    const selectElement = $(`#uacs-select-${rowId}`);
    
    selectElement.html('<option value="">-- Select UACS Code --</option>' + generateUACSOptions(selectedId));
    
    selectElement.select2({
        placeholder: '-- Select UACS Code --',
        allowClear: true,
        width: '100%',
        templateResult: formatUACSOption,
        templateSelection: formatUACSSelection,
        escapeMarkup: function(markup) { return markup; }
    });
    
    if (selectedId) {
        selectElement.val(selectedId).trigger('change');
        selectedUACSIds.add(selectedId);
    }
    
    selectElement.on('select2:select', function(e) {
        const newValue = parseInt(e.params.data.id);
        const oldValue = parseInt($(this).data('previous-value') || 0);
        
        if (oldValue) {
            selectedUACSIds.delete(oldValue);
        }
        
        selectedUACSIds.add(newValue);
        $(this).data('previous-value', newValue);
        refreshAllUACSDropdowns();
    });
    
    selectElement.on('select2:unselect select2:clear', function(e) {
        const oldValue = parseInt($(this).data('previous-value') || 0);
        if (oldValue) {
            selectedUACSIds.delete(oldValue);
        }
        $(this).data('previous-value', null);
        refreshAllUACSDropdowns();
    });
}

function formatUACSOption(option) {
    if (!option.id) return option.text;
    
    const $option = $(option.element);
    const fullPath = $option.data('full-path');
    const level = $option.data('level') || 0;
    
    let icon = '';
    if (level === 0) icon = '📁 ';
    else if (level === 1) icon = '├─ ';
    else if (level === 2) icon = '│&nbsp;&nbsp;├─ ';
    else if (level >= 3) icon = '│&nbsp;&nbsp;│&nbsp;&nbsp;└─ ';
    
    return $(`
        <div title="${fullPath || option.text}" style="white-space: nowrap;">
            ${icon}${option.text}
        </div>
    `);
}

function formatUACSSelection(option) {
    if (!option.id) return option.text;
    
    const text = option.text.replace(/^[│├└─\s]+/, '');
    const parts = text.split(' - ');
    const code = parts[0] || '';
    const desc = parts.slice(1).join(' - ') || '';
    
    return $(`<span><strong>${code}</strong> ${desc ? '- ' + desc : ''}</span>`);
}

function refreshAllUACSDropdowns() {
    $('.uacs-select').each(function() {
        const currentValue = $(this).val();
        const selectedValue = currentValue ? parseInt(currentValue) : null;
        
        const newOptions = '<option value="">-- Select UACS Code --</option>' + 
            generateUACSOptions(selectedValue);
        
        $(this).html(newOptions);
        
        if (selectedValue) {
            $(this).val(selectedValue).trigger('change.select2');
        }
    });
}

function removeUACSRow(rowId, rowCounter) {
    const row = document.getElementById(rowId);
    const selectElement = $(`#uacs-select-${rowCounter}`);
    const selectedValue = parseInt(selectElement.val() || 0);
    
    if (selectedValue) {
        selectedUACSIds.delete(selectedValue);
    }
    
    selectElement.select2('destroy');
    
    row.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => {
        row.remove();
        updateUACSRowNumbers();
        refreshAllUACSDropdowns();
        validateUACSAmounts(); // Validate after removing row
        
        if (document.querySelectorAll('.uacs-row').length === 0) {
            addUACSRow();
        }
    }, 300);
}

function updateUACSRowNumbers() {
    const rows = document.querySelectorAll('.uacs-row');
    rows.forEach((row, index) => {
        const numberBadge = row.querySelector('.row-number');
        if (numberBadge) {
            numberBadge.textContent = index + 1;
        }
    });
}

// ====================================
// UACS AMOUNT VALIDATION FUNCTIONS
// ====================================

function validateUACSAmounts() {
    const amountInputs = document.querySelectorAll('.uacs-amount-input');
    let total = 0;
    let allFilled = true;
    
    amountInputs.forEach(input => {
        const value = parseFloat(input.value) || 0;
        total += value;
        if (!input.value || value <= 0) {
            allFilled = false;
        }
    });
    
    // Update or create validation message
    let validationDiv = document.getElementById('uacs-amount-validation');
    
    if (!validationDiv) {
        validationDiv = document.createElement('div');
        validationDiv.id = 'uacs-amount-validation';
        validationDiv.className = 'mt-3 p-3 rounded-lg text-sm';
        
        // Insert after the last button in UACS section
        const uacsContainer = document.getElementById('uacs-container');
        const nextElement = uacsContainer.nextElementSibling;
        
        // Find the last button (Auto-distribute or Add button)
        let insertAfter = nextElement;
        while (insertAfter && insertAfter.nextElementSibling && 
               (insertAfter.nextElementSibling.tagName === 'BUTTON' || 
                insertAfter.nextElementSibling.classList.contains('inline-flex'))) {
            insertAfter = insertAfter.nextElementSibling;
        }
        
        insertAfter.insertAdjacentElement('afterend', validationDiv);
    }
    
    const difference = Math.abs(total - grossAmount);
    const isValid = difference < 0.01 && allFilled; // 1 cent tolerance
    
    if (amountInputs.length === 0) {
        validationDiv.style.display = 'none';
        return true;
    }
    
    validationDiv.style.display = 'block';
    
    if (isValid) {
        validationDiv.className = 'mt-3 p-3 rounded-lg text-sm bg-green-50 border border-green-200';
        validationDiv.innerHTML = `
            <div class="flex items-center text-green-700">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-medium">✓ Total UACS Amount: ₱${formatNumber(total)} equals Gross Amount: ₱${formatNumber(grossAmount)}</span>
            </div>
        `;
    } else if (!allFilled) {
        validationDiv.className = 'mt-3 p-3 rounded-lg text-sm bg-red-50 border border-red-200';
        validationDiv.innerHTML = `
            <div class="flex items-center text-red-700">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-medium">Please enter amounts for all UACS codes</span>
            </div>
        `;
    } else {
        validationDiv.className = 'mt-3 p-3 rounded-lg text-sm bg-yellow-50 border border-yellow-200';
        const remaining = grossAmount - total;
        validationDiv.innerHTML = `
            <div class="flex items-center text-yellow-700">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <div class="font-medium mb-1">UACS amounts must equal gross amount</div>
                    <div class="text-sm space-x-3">
                        <span>Total UACS: ₱${formatNumber(total)}</span>
                        <span class="text-gray-400">|</span>
                        <span>Gross: ₱${formatNumber(grossAmount)}</span>
                        <span class="text-gray-400">|</span>
                        <span class="${remaining >= 0 ? 'text-yellow-800' : 'text-red-600'} font-semibold">
                            ${remaining >= 0 ? 'Remaining' : 'Excess'}: ₱${formatNumber(Math.abs(remaining))}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Enable/disable submit button based on validation
    const submitBtn = document.querySelector('form button[type="submit"]');
    if (submitBtn && !submitBtn.hasAttribute('data-other-validation')) {
        if (!isValid) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            submitBtn.title = 'Total UACS amounts must equal gross amount';
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            submitBtn.title = '';
        }
    }
    
    return isValid;
}

// Auto-distribute amounts equally across all UACS codes
function autoDistributeAmounts() {
    const amountInputs = document.querySelectorAll('.uacs-amount-input');
    const count = amountInputs.length;
    
    if (count === 0) {
        showMessage('Please add UACS codes first', 'error');
        return;
    }
    
    // Calculate equal distribution
    const amountPerCode = (grossAmount / count).toFixed(2);
    const lastAmount = (grossAmount - (amountPerCode * (count - 1))).toFixed(2);
    
    amountInputs.forEach((input, index) => {
        input.value = index === count - 1 ? lastAmount : amountPerCode;
    });
    
    validateUACSAmounts();
    showMessage('Amounts distributed equally across ' + count + ' UACS code(s)', 'success');
}

// Add button initialization
function initializeAutoDistributeButton() {
    const uacsContainer = document.getElementById('uacs-container');
    if (!uacsContainer) return;
    
    // Check if button already exists
    if (document.getElementById('auto-distribute-btn')) return;
    
    const autoDistributeBtn = document.createElement('button');
    autoDistributeBtn.type = 'button';
    autoDistributeBtn.id = 'auto-distribute-btn';
    autoDistributeBtn.onclick = autoDistributeAmounts;
    autoDistributeBtn.className = 'mt-2 ml-2 inline-flex items-center px-3 py-1.5 text-sm bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg transition-colors duration-200';
    autoDistributeBtn.innerHTML = `
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
        </svg>
        Auto-Distribute Equally
    `;
    
    // Insert after the "Add Another UACS Code" button
    const addUacsBtn = uacsContainer.nextElementSibling;
    if (addUacsBtn) {
        addUacsBtn.insertAdjacentElement('afterend', autoDistributeBtn);
    }
}

// ====================================
// ADL DYNAMIC FORM FUNCTIONS
// ====================================

function addADLRow(selectedId = null) {
    adlRowCounter++;
    const rowId = `adl-row-${adlRowCounter}`;
    
    const container = document.getElementById('adl-container');
    
    const rowHTML = `
        <div class="adl-row flex items-start gap-3 p-4 bg-gray-50 rounded-lg border border-gray-200" 
             id="${rowId}" 
             data-row-id="${adlRowCounter}"
             style="animation: slideIn 0.3s ease-out;">
            <div class="flex-shrink-0 w-8 pt-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-600 text-white text-xs font-medium adl-row-number">
                    ${adlRowCounter}
                </span>
            </div>
            
            <div class="flex-1">
                <select name="adlcode_ids[]" 
                        id="adl-select-${adlRowCounter}"
                        class="adl-select w-full rounded-lg border-gray-300 shadow-sm text-sm" 
                        data-row-id="${adlRowCounter}"
                        required>
                    <option value="">-- Select ADL Code --</option>
                </select>
            </div>
            
            <button type="button" 
                    onclick="removeADLRow('${rowId}', ${adlRowCounter})"
                    class="remove-btn flex-shrink-0 p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200"
                    title="Remove this ADL code">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', rowHTML);
    initializeADLSelect2(adlRowCounter, selectedId);
    updateADLRowNumbers();
}

function generateADLOptions(selectedId = null) {
    if (!adlSelectableData || adlSelectableData.length === 0) {
        return '<option value="">No ADL codes available</option>';
    }
    
    return adlSelectableData
        .filter(adl => !selectedADLIds.has(adl.id) || adl.id === selectedId)
        .map(adl => {
            return `<option value="${adl.id}" 
                            ${adl.id === selectedId ? 'selected' : ''}>
                ${adl.code} - ${adl.description}
            </option>`;
        })
        .join('');
}

function initializeADLSelect2(rowId, selectedId = null) {
    const selectElement = $(`#adl-select-${rowId}`);
    
    selectElement.html('<option value="">-- Select ADL Code --</option>' + generateADLOptions(selectedId));
    
    selectElement.select2({
        placeholder: '-- Select ADL Code --',
        allowClear: true,
        width: '100%',
        templateSelection: formatADLSelection,
        escapeMarkup: function(markup) { return markup; }
    });
    
    if (selectedId) {
        selectElement.val(selectedId).trigger('change');
        selectedADLIds.add(selectedId);
    }
    
    selectElement.on('select2:select', function(e) {
        const newValue = parseInt(e.params.data.id);
        const oldValue = parseInt($(this).data('previous-value') || 0);
        
        if (oldValue) {
            selectedADLIds.delete(oldValue);
        }
        
        selectedADLIds.add(newValue);
        $(this).data('previous-value', newValue);
        refreshAllADLDropdowns();
    });
    
    selectElement.on('select2:unselect select2:clear', function(e) {
        const oldValue = parseInt($(this).data('previous-value') || 0);
        if (oldValue) {
            selectedADLIds.delete(oldValue);
        }
        $(this).data('previous-value', null);
        refreshAllADLDropdowns();
    });
}

function formatADLSelection(option) {
    if (!option.id) return option.text;
    
    const text = option.text;
    const parts = text.split(' - ');
    const code = parts[0] || '';
    const desc = parts.slice(1).join(' - ') || '';
    
    return $(`<span><strong>${code}</strong> ${desc ? '- ' + desc : ''}</span>`);
}

function refreshAllADLDropdowns() {
    $('.adl-select').each(function() {
        const currentValue = $(this).val();
        const selectedValue = currentValue ? parseInt(currentValue) : null;
        
        const newOptions = '<option value="">-- Select ADL Code --</option>' + 
            generateADLOptions(selectedValue);
        
        $(this).html(newOptions);
        
        if (selectedValue) {
            $(this).val(selectedValue).trigger('change.select2');
        }
    });
}

function removeADLRow(rowId, rowCounter) {
    const row = document.getElementById(rowId);
    const selectElement = $(`#adl-select-${rowCounter}`);
    const selectedValue = parseInt(selectElement.val() || 0);
    
    if (selectedValue) {
        selectedADLIds.delete(selectedValue);
    }
    
    selectElement.select2('destroy');
    
    row.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => {
        row.remove();
        updateADLRowNumbers();
        refreshAllADLDropdowns();
        
        if (document.querySelectorAll('.adl-row').length === 0) {
            addADLRow();
        }
    }, 300);
}

function updateADLRowNumbers() {
    const rows = document.querySelectorAll('.adl-row');
    rows.forEach((row, index) => {
        const numberBadge = row.querySelector('.adl-row-number');
        if (numberBadge) {
            numberBadge.textContent = index + 1;
        }
    });
}

// ====================================
// ADDITIONAL SELECT2 INITIALIZATION
// ====================================

function initializeAdditionalSelect2() {
    // Allotment Class Select2
    $('#allotment-class-select').select2({
        placeholder: '-- Select Allotment Class --',
        allowClear: true,
        width: '100%',
        templateSelection: formatAdditionalSelection,
        escapeMarkup: function(markup) { return markup; }
    });

    // MFO/PAP Select2
    $('#mfopap-select').select2({
        placeholder: '-- Select MFO/PAP --',
        allowClear: true,
        width: '100%',
        templateSelection: formatAdditionalSelection,
        escapeMarkup: function(markup) { return markup; }
    });

    // Continuing/Current Select2
    $('#contcurrent-select').select2({
        placeholder: '-- Select --',
        allowClear: true,
        width: '100%',
        templateSelection: formatAdditionalSelection,
        escapeMarkup: function(markup) { return markup; }
    });
}

function formatAdditionalSelection(option) {
    if (!option.id) return option.text;
    
    const text = option.text;
    const parts = text.split(' - ');
    const code = parts[0] || '';
    const desc = parts.slice(1).join(' - ') || '';
    
    return $(`<span><strong>${code}</strong> ${desc ? '- ' + desc : ''}</span>`);
}

// ====================================
// MODAL FUNCTIONS
// ====================================

function openRejectModal(dvRequestId) {
    currentRejectDvRequestId = dvRequestId;
    document.getElementById('reject-reason').value = '';
    document.getElementById('reject-notes').value = '';
    document.getElementById('reject-error').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('reject-reason').focus();
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectForm').reset();
    document.getElementById('reject-error').classList.add('hidden');
    currentRejectDvRequestId = null;
}

function openAmountModal(dvRequestId, originalAmount) {
    currentDvRequestId = dvRequestId;
    currentOriginalAmount = originalAmount;
    document.getElementById('current-amount-display').textContent = `₱${formatNumber(originalAmount)}`;
    document.getElementById('new-amount').value = originalAmount.toFixed(2);
    document.getElementById('amount-reason').value = '';
    document.getElementById('amount-error').classList.add('hidden');
    document.getElementById('amountModal').classList.remove('hidden');
    document.getElementById('new-amount').focus();
}

function closeAmountModal() {
    document.getElementById('amountModal').classList.add('hidden');
    document.getElementById('amountForm').reset();
    document.getElementById('amount-error').classList.add('hidden');
    currentDvRequestId = null;
    currentOriginalAmount = 0;
}

function openAddADLModal() {
    document.getElementById('addADLModal').classList.remove('hidden');
    document.getElementById('new-adl-code').focus();
}

function closeAddADLModal() {
    document.getElementById('addADLModal').classList.add('hidden');
    document.getElementById('addADLForm').reset();
    document.getElementById('adl-code-error').classList.add('hidden');
}

// ====================================
// UTILITY FUNCTIONS
// ====================================

function formatNumber(number) {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
}

// Updated showMessage function with warning support
function showMessage(message, type = 'success') {
    const container = document.getElementById('message-container');
    const messageDiv = document.createElement('div');
    
    let bgColor, icon;
    switch(type) {
        case 'success':
            bgColor = 'bg-green-500';
            icon = `<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>`;
            break;
        case 'error':
            bgColor = 'bg-red-500';
            icon = `<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>`;
            break;
        case 'warning':
            bgColor = 'bg-yellow-500';
            icon = `<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>`;
            break;
        default:
            bgColor = 'bg-blue-500';
            icon = `<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>`;
    }
    
    messageDiv.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg mb-4 transform transition-all duration-300 translate-x-full opacity-0 flex items-center`;
    messageDiv.innerHTML = icon + `<span>${message}</span>`;
    
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
    }, 4000); // Longer display time for warnings
}

// ====================================
// FORM SUBMISSION HANDLERS
// ====================================

document.addEventListener("DOMContentLoaded", () => {
    const dvRequestId = "{{ $dvRequest->id }}";
    
    // Initialize Select2 and add first rows
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        // Add first UACS row
        addUACSRow();
        
        // Add first ADL row
        addADLRow();

        // Initialize additional Select2 dropdowns
        initializeAdditionalSelect2();

         // ✅ NEW: Initialize auto-distribute button
        initializeAutoDistributeButton();

        console.log('✅ Select2 initialized successfully');
        console.log('✅ First UACS and ADL rows added');
        console.log('✅ Additional Select2 dropdowns initialized');
    } else {
        console.error('❌ jQuery or Select2 not loaded');
    }
    
    // Add this to your existing Amount Form Submission handler

    const amountForm = document.getElementById('amountForm');
    if (amountForm) {
        amountForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('saveAmountBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('svg');

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('❌ CSRF token not found in meta tag');
                showMessage('Security token not found. Please refresh the page.', 'error');
                return;
            }
            
            const newAmount = parseFloat(document.getElementById('new-amount').value);
            const reason = document.getElementById('amount-reason').value;
            
            if (!newAmount || newAmount <= 0 || isNaN(newAmount)) {
                document.getElementById('amount-error').textContent = 'Please enter a valid amount greater than 0';
                document.getElementById('amount-error').classList.remove('hidden');
                return;
            }
            
            if (!reason.trim()) {
                document.getElementById('amount-error').textContent = 'Please provide a reason for the amount change';
                document.getElementById('amount-error').classList.remove('hidden');
                return;
            }
            
            submitBtn.disabled = true;
            btnText.textContent = 'Updating...';
            spinner.classList.remove('hidden');
            
            try {
                const response = await fetch(`/BudgetOfficer/dvrequests/${currentDvRequestId}/update-amount`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        amount: newAmount,
                        reason: reason,
                        remarks: `Amount updated from ₱${formatNumber(currentOriginalAmount)} to ₱${formatNumber(newAmount)}. Reason: ${reason}`
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Update amount display
                    const amountDisplay = document.querySelector('.amount-display span');
                    if (amountDisplay) {
                        amountDisplay.textContent = `₱${formatNumber(newAmount)}`;
                    }
                    
                    // Update hidden input
                    document.getElementById('amount-hidden').value = newAmount;
                    
                    // ✅ CRITICAL: Update grossAmount and re-validate UACS amounts
                    grossAmount = newAmount;
                    
                    // Re-validate UACS amounts with new gross amount
                    if (document.querySelectorAll('.uacs-amount-input').length > 0) {
                        validateUACSAmounts();
                        
                        // Show warning if UACS amounts no longer match
                        const uacsTotal = Array.from(document.querySelectorAll('.uacs-amount-input'))
                            .reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
                        
                        if (Math.abs(uacsTotal - newAmount) > 0.01) {
                            showMessage('Amount updated successfully!', 'success');
                        } else {
                            showMessage('Amount updated successfully!', 'success');
                        }
                    } else {
                        showMessage('Amount updated successfully!', 'success');
                    }
                    
                    closeAmountModal();
                } else {
                    throw new Error(data.message || 'Failed to update amount');
                }
            } catch (error) {
                console.error('❌ Error updating amount:', error);
                showMessage(error.message || 'Failed to update amount', 'error');
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = 'Update Amount';
                spinner.classList.add('hidden');
            }
        });
    }
    
    // ADL Form Submission
    const addADLForm = document.getElementById('addADLForm');
    if (addADLForm) {
        addADLForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('saveADLBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('svg');
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('❌ CSRF token not found in meta tag');
                showMessage('Security token not found. Please refresh the page.', 'error');
                return;
            }
            
            submitBtn.disabled = true;
            btnText.textContent = 'Saving...';
            spinner.classList.remove('hidden');
            
            const adlCode = document.getElementById('new-adl-code').value;
            const description = document.getElementById('new-adl-description').value;
            
            console.log('📤 Submitting ADL Code:', { adlCode, description });
            
            try {
                const response = await fetch('/BudgetOfficer/adl/store', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        adlcode: adlCode,
                        description: description
                    })
                });
                
                console.log('📥 Response status:', response.status);
                
                const data = await response.json();
                console.log('📦 Response data:', data);
                
                if (response.ok && data.success) {
                    // Add to adlSelectableData array
                    adlSelectableData.push({
                        id: data.adlCode.id,
                        code: data.adlCode.adlcode,
                        description: data.adlCode.description
                    });
                    
                    // Refresh all ADL dropdowns
                    refreshAllADLDropdowns();
                    
                    console.log('✅ ADL Code added to dropdown');
                    
                    showMessage('ADL Code added successfully!', 'success');
                    closeAddADLModal();
                } else {
                    throw new Error(data.message || 'Failed to add ADL Code');
                }
            } catch (error) {
                console.error('❌ Error adding ADL Code:', error);
                showMessage(error.message || 'Failed to add ADL Code', 'error');
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = 'Save ADL Code';
                spinner.classList.add('hidden');
            }
        });
    }
    
    // Reject Form Submission
    const rejectForm = document.getElementById('rejectForm');
    if (rejectForm) {
        rejectForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('rejectBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('svg');
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('❌ CSRF token not found in meta tag');
                showMessage('Security token not found. Please refresh the page.', 'error');
                return;
            }
            
            const rejectReason = document.getElementById('reject-reason').value;
            
            if (!rejectReason.trim()) {
                document.getElementById('reject-error').textContent = 'Please provide a reason for rejection';
                document.getElementById('reject-error').classList.remove('hidden');
                return;
            }
            
            submitBtn.disabled = true;
            btnText.textContent = 'Rejecting...';
            spinner.classList.remove('hidden');
            
            try {
                const rejectNotes = document.getElementById('reject-notes').value;
                
                const response = await fetch(`/BudgetOfficer/dvrequests/${currentRejectDvRequestId}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        reject_reason: rejectReason,
                        reject_notes: rejectNotes,
                        remarks: `Request rejected. Reason: ${rejectReason}${rejectNotes ? '. Notes: ' + rejectNotes : ''}`
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showMessage('DV Request rejected successfully!', 'success');
                    closeRejectModal();
                    
                    setTimeout(() => {
                        window.location.href = '/BudgetOfficer/dvrequests';
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Failed to reject request');
                }
            } catch (error) {
                console.error('Reject Error:', error);
                showMessage(error.message || 'Failed to reject request', 'error');
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = 'Confirm Reject';
                spinner.classList.add('hidden');
            }
        });
    }
    
    // Modal close on outside click
    const amountModal = document.getElementById('amountModal');
    if (amountModal) {
        amountModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAmountModal();
            }
        });
    }
    
    const adlModal = document.getElementById('addADLModal');
    if (adlModal) {
        adlModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddADLModal();
            }
        });
    }
    
    const rejectModal = document.getElementById('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    }

    // Auto-refresh lock every 2 minutes
    const refreshInterval = setInterval(() => {
        fetch(`/BudgetOfficer/dvrequests/${dvRequestId}/refresh-lock`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
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

    // Release lock on page unload
    window.addEventListener("beforeunload", () => {
        navigator.sendBeacon(`/BudgetOfficer/dvrequests/${dvRequestId}/release-lock`, 
            JSON.stringify({ _token: document.querySelector('meta[name="csrf-token"]').content }));
    });

});

// ====================================
// BUTTON ACTION FUNCTIONS
// ====================================

async function handleReceiveAction(dvRequestId, buttonElement) {
    const spinner = document.getElementById('loading-spinner');
    const buttonText = buttonElement.querySelector('.button-text');
    
    buttonElement.disabled = true;
    buttonText.textContent = 'Receiving...';
    spinner.classList.remove('hidden');
    
    try {
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
            updateButtonsToVerifyMode(dvRequestId);
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

function updateButtonsToVerifyMode(dvRequestId) {
    const buttonContainer = document.getElementById('button-container');
    
    buttonContainer.innerHTML = `
    <td>
        <div class="flex justify-end space-x-3">
            <button type="submit" 
                    class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                Obligate
            </button>
            <button type="button"
                    onclick="openRejectModal(${dvRequestId})"
                    class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Reject
            </button>
            <a href="/BudgetOfficer/dvrequests"
                onclick="releaseLock(event, ${dvRequestId})"
               class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                Cancel
            </a>
        </div>
    </td>
    `;
}

// ====================================
// LOCK MANAGEMENT FUNCTIONS
// ====================================

function releaseLock(event, id) {
    event.preventDefault();

    fetch(`/BudgetOfficer/dvrequests/${id}/release-lock`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            "Accept": "application/json",
            "Content-Type": "application/json"
        }
    }).then(() => {
        window.location.href = "/BudgetOfficer/dvrequests";
    }).catch(err => {
        console.error("Release lock failed:", err);
        window.location.href = "/BudgetOfficer/dvrequests";
    });
}
</script>