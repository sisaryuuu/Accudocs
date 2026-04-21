@extends('layouts.app')

@section('title', 'Verify DV Request')

@section('content')
<div class="min-h-screen flex items-center justify-center py-8 px-4">
    <div class="w-full max-w-6xl">
        
        <!-- Main Content Container -->
        <div class="bg-white shadow rounded-xl p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">IMSD Verification</h1>

            <!-- Single form for everything -->
            <form action="{{ route('IMSD.dvrequests.verify', $dvRequest->id) }}" method="POST" class="space-y-6">
                @csrf

                <!-- Document Preview Section -->
                <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">DV/ORS Document Previews</h2>
                        <div class="space-x-2">
                            <button type="button" 
                                    onclick="previewDV()"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Preview DV Form
                            </button>
                            
                            <button type="button" 
                                    onclick="previewORS()"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Preview ORS Form
                            </button>
                        </div>
                    </div>
                    
                    <!-- Document Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="bg-white p-4 rounded-lg border border-gray-300 hover:border-blue-400 transition-colors cursor-pointer" onclick="previewDV()">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">Disbursement Voucher (DV)</p>
                                    <p class="text-xs text-gray-500 mt-1">Standard DV form with payment details, signatories, and program information</p>
                                    <div class="mt-2 flex items-center text-xs text-blue-600">
                                        <span>Click to preview</span>
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border border-gray-300 hover:border-green-400 transition-colors cursor-pointer" onclick="previewORS()">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">Obligation Request & Status (ORS)</p>
                                    <p class="text-xs text-gray-500 mt-1">ORS form with budget allocation, project codes, and obligation details</p>
                                    <div class="mt-2 flex items-center text-xs text-green-600">
                                        <span>Click to preview</span>
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rest of the form remains the same -->
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
                        <input type="text" value="{{ $dvRequest->accounting_control_number }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name of Payee</label>
                        <input type="text" value="{{ $dvRequest->payee_fname ?? $dvRequest->focalPerson->fname ?? '' }} {{ $dvRequest->payee_lname ?? $dvRequest->focalPerson->lname ?? '' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Program</label>
                        <input type="text" value="{{ $dvRequest->program->prcode ?? 'N/A' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                </div>

                <!-- Amount + Received Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Particulars</label>
                        <textarea 
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm h-48" 
                            readonly>{{ $dvRequest->particulars ?? '' }}
                        </textarea>
                    </div>
                </div>
                
                <!-- Dates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    @if($dvRequest->modeOfPayments->isNotEmpty())
                        @foreach($dvRequest->modeOfPayments as $mop)
                            @if($mop->pivot && $mop->pivot->paymentDetail)
                                <div class="text-xs text-gray-600 mt-1 space-y-1">
                                    <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                                    <input type="text" value="{{ $mop->pivot->paymentDetail->bank_name ?? 'N/A' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                                </div>
                                <div class="text-xs text-gray-600 mt-1 space-y-1">
                                    <label class="block text-sm font-medium text-gray-700">Bank Account Number</label>
                                    <input type="text" value="{{ $mop->pivot->paymentDetail->bank_accnum ?? 'N/A' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                                </div>
                                <div class="text-xs text-gray-600 mt-1 space-y-1">
                                    <label class="block text-sm font-medium text-gray-700">Bank Address</label>
                                    <input type="text" value="{{ $mop->pivot->paymentDetail->bank_address ?? 'N/A' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="col-span-2">
                            <p class="text-xs text-gray-400">No payment details available</p>
                        </div>
                    @endif
                </div>

                <!-- Remarks -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Remarks / Lacking</label>
                    <textarea id="remarks" name="remarks" rows="3" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" placeholder="Enter remarks or notes..."></textarea>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end space-x-3" id="button-container">
                    @if($dvRequest->status === 'submitted')
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
                    @elseif($dvRequest->status === 'IMSD_received')
                        <button type="submit"
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Signed by IMSD HEAD
                        </button>
                        <button type="button"
                            onclick="openRejectModal()"
                            class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Reject
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
    </div>
</div>

<!-- Document Preview Modal -->
<div id="documentPreviewModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-2 mx-auto p-2 border w-full max-w-7xl shadow-lg rounded-lg bg-white">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-2 px-4 pt-3 border-b">
            <div class="flex items-center space-x-3">
                <button onclick="closeDocumentPreview()" 
                        class="text-gray-400 hover:text-gray-600 transition p-1 rounded-full hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
                <div>
                    <h3 class="text-xl font-semibold text-gray-900" id="documentPreviewTitle">Document Preview</h3>
                    <p class="text-xs text-gray-500" id="documentPreviewSubtitle">Live preview of the form</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button onclick="printPreview()" 
                        class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg text-sm transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print
                </button>
                <button onclick="closeDocumentPreview()" 
                        class="text-gray-400 hover:text-gray-600 transition p-1 rounded-full hover:bg-gray-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="mt-2">
            <!-- Document Type Tabs -->
            <div class="border-b border-gray-200 mb-2 px-4">
                <nav class="flex space-x-2" aria-label="Document types">
                    <button type="button"
                            onclick="switchToDVPreview()"
                            id="dvPreviewTab"
                            class="px-3 py-2 text-sm font-medium border-b-2 border-blue-600 text-blue-600">
                        DV Form
                    </button>
                    <button type="button"
                            onclick="switchToORSPreview()"
                            id="orsPreviewTab"
                            class="px-3 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        ORS Form
                    </button>
                </nav>
            </div>

<!-- Preview Content -->
<div class="h-[calc(100vh-180px)] overflow-auto bg-gray-100 p-2">
    <div id="dvPreviewContent" class="bg-white shadow-lg">
        <!-- DV Preview will be loaded here -->
        @php
            // Prepare DV data from the controller
            $modeOfPayment = $dvRequest->modeOfPayments->first();
            
            // Get payment details correctly
            $paymentDetail = null;
            if ($modeOfPayment) {
                // Access through the pivot relationship
                $dvRequestMop = $dvRequest->ModeOfPayments()
                    ->where('mode_of_payment_id', $modeOfPayment->id)
                    ->first();
                
                if ($dvRequestMop) {
                    $paymentDetail = $dvRequestMop->paymentDetail;
                }
            }
            
            $focalPerson = $dvRequest->focalPerson;
            
            $payee = trim($focalPerson->fname . ' ' . ($focalPerson->mi ? $focalPerson->mi . '. ' : '') . $focalPerson->lname);
            $date = $dvRequest->created_at->format('F d, Y');
            $address = $focalPerson->Address;
            $tin = $focalPerson->TIN_number;
            $mode_of_payment = $modeOfPayment?->mopname ?? 'Not specified';
            $particulars = $dvRequest->particulars;
            $amount = number_format($dvRequest->amount, 2);
            $respocenter_code = $dvRequest->respocenter->rccode ?? '';
            $respocenter_desc = $dvRequest->respocenter->description ?? '';
            $program_code = $dvRequest->program->prcode ?? '';
            $program_desc = $dvRequest->program->description ?? '';
            $bank_name = $paymentDetail?->bank_name ?? '';
            $bank_accnum = $paymentDetail?->bank_accnum ?? '';
            $bank_address = $paymentDetail?->bank_address ?? '';
            $imsd_head = \App\Models\Setting::get('imsd_head', 'NAME OF IMSD HEAD');
            $accountant_name = \App\Models\Setting::get('accountant_name', 'NAME OF ACCOUNTANT');
            $regional_director = \App\Models\Setting::get('regional_director', 'NAME OF REGIONAL DIRECTOR');
            $budget_officer = \App\Models\Setting::get('budget_officer', 'NAME OF BUDGET OFFICER');
        @endphp
        
        <!-- Include DV Template -->
        @include('pdf.initdv', [
            'payee' => $payee,
            'date' => $date,
            'address' => $address,
            'tin' => $tin,
            'mode_of_payment' => $mode_of_payment,
            'particulars' => $particulars,
            'amount' => $amount,
            'respocenter_code' => $respocenter_code,
            'respocenter_desc' => $respocenter_desc,
            'program_code' => $program_code,
            'program_desc' => $program_desc,
            'bank_name' => $bank_name,
            'bank_accnum' => $bank_accnum,
            'bank_address' => $bank_address,
            'ors_no' => $dvRequest->ors_number ?? '',
            'dv_no' => $dvRequest->dv_number ?? '',
            'logo_url' => $logo_url, // PASS THE ACTUAL $logo_url VARIABLE
            'imsd_head' => $imsd_head,
            'accountant_name' => $accountant_name,
            'regional_director' => $regional_director,
            'budget_officer' => $budget_officer
        ])
    </div>
    
    <div id="orsPreviewContent" class="bg-white shadow-lg hidden">
        <!-- ORS Preview will be loaded here -->
        @php
            $data = [
                'payee' => $payee,
                'ors_no' => $dvRequest->ors_number ?? '',
                'date' => $date,
                'address' => $address,
                'tin' => $tin,
                'particulars' => $particulars,
                'mfo' => '',
                'uacs' => '',
                'amount' => $dvRequest->amount,
            ];
        @endphp
        
        <!-- Include ORS Template -->
        @include('pdf.initors', [
            'data' => $data,
            'respocenter_code' => $respocenter_code,
            'respocenter_desc' => $respocenter_desc,
            'imsd_head' => $imsd_head,
            'budget_officer' => $budget_officer,
            'logo_url' => $logo_url // PASS THE ACTUAL $logo_url VARIABLE
        ])
    </div>
</div>  
            <!-- Footer Actions -->
            <div class="border-t border-gray-200 px-4 py-3">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-500">
                        <span id="currentDocumentInfo">Previewing DV Form</span>
                        <span class="mx-2">•</span>
                        <span>Generated: {{ now()->format('M d, Y h:i A') }}</span>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="downloadAsPDF()" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download PDF
                        </button>
                        <button onclick="closeDocumentPreview()" 
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg text-sm transition-colors duration-200">
                            Close Preview
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal (keep existing) -->
<div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <!-- Existing reject modal content... -->
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
#documentPreviewModal, #rejectModal {
    transition: opacity 0.3s ease-in-out;
}

#documentPreviewModal > div, #rejectModal > div {
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

/* PDF template styles */
.pdf-template {
    font-family: 'Times New Roman', Times, serif;
}

.pdf-template table {
    width: 100%;
    border-collapse: collapse;
}

.pdf-template th, .pdf-template td {
    border: 1px solid #000;
    padding: 4px 8px;
    font-size: 12px;
}

.pdf-template .header {
    text-align: center;
    margin-bottom: 20px;
}

.pdf-template .section {
    margin-bottom: 15px;
}
</style>

<!-- Success/Error Messages -->
<div id="message-container" class="fixed top-4 right-4 z-50"></div>

@endsection

<script>
let currentRejectId = null;
let currentPreviewType = 'dv'; // 'dv' or 'ors'

// Document Preview Functions
function previewDV() {
    currentPreviewType = 'dv';
    showDocumentPreview('dv', 'Disbursement Voucher (DV) Form');
}

function previewORS() {
    currentPreviewType = 'ors';
    showDocumentPreview('ors', 'Obligation Request & Status (ORS) Form');
}

function showDocumentPreview(type, title) {
    const modal = document.getElementById('documentPreviewModal');
    const titleElement = document.getElementById('documentPreviewTitle');
    const subtitleElement = document.getElementById('documentPreviewSubtitle');
    const currentDocumentInfo = document.getElementById('currentDocumentInfo');
    
    // Update UI
    titleElement.textContent = title;
    subtitleElement.textContent = 'Live preview - ' + (type === 'dv' ? 'DV Form' : 'ORS Form');
    currentDocumentInfo.textContent = 'Previewing ' + (type === 'dv' ? 'DV Form' : 'ORS Form');
    
    // Switch to the appropriate tab
    if (type === 'dv') {
        switchToDVPreview();
    } else {
        switchToORSPreview();
    }
    
    // Show modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Scroll to top
    const previewContent = type === 'dv' ? 
        document.getElementById('dvPreviewContent') : 
        document.getElementById('orsPreviewContent');
    previewContent.scrollTop = 0;
}

function switchToDVPreview() {
    currentPreviewType = 'dv';
    document.getElementById('dvPreviewTab').classList.add('border-blue-600', 'text-blue-600');
    document.getElementById('dvPreviewTab').classList.remove('border-transparent', 'text-gray-500');
    document.getElementById('orsPreviewTab').classList.remove('border-blue-600', 'text-blue-600');
    document.getElementById('orsPreviewTab').classList.add('border-transparent', 'text-gray-500');
    
    document.getElementById('dvPreviewContent').classList.remove('hidden');
    document.getElementById('orsPreviewContent').classList.add('hidden');
    
    document.getElementById('currentDocumentInfo').textContent = 'Previewing DV Form';
}

function switchToORSPreview() {
    currentPreviewType = 'ors';
    document.getElementById('orsPreviewTab').classList.add('border-blue-600', 'text-blue-600');
    document.getElementById('orsPreviewTab').classList.remove('border-transparent', 'text-gray-500');
    document.getElementById('dvPreviewTab').classList.remove('border-blue-600', 'text-blue-600');
    document.getElementById('dvPreviewTab').classList.add('border-transparent', 'text-gray-500');
    
    document.getElementById('orsPreviewContent').classList.remove('hidden');
    document.getElementById('dvPreviewContent').classList.add('hidden');
    
    document.getElementById('currentDocumentInfo').textContent = 'Previewing ORS Form';
}

function closeDocumentPreview() {
    const modal = document.getElementById('documentPreviewModal');
    
    // Hide modal
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function printPreview() {
    const printContent = currentPreviewType === 'dv' ? 
        document.getElementById('dvPreviewContent').innerHTML : 
        document.getElementById('orsPreviewContent').innerHTML;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print ${currentPreviewType === 'dv' ? 'DV' : 'ORS'} Form</title>
            <style>
                body { margin: 0; padding: 20px; font-family: 'Times New Roman', Times, serif; }
                @media print {
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            ${printContent}
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() {
                        window.close();
                    }, 100);
                }
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function downloadAsPDF() {
    // Redirect to the actual PDF generation endpoint
    if (currentPreviewType === 'dv') {
        window.open('/focalperson/dvrequests/{{ $dvRequest->id }}/generate-pdf', '_blank');
    } else {
        window.open('/focalperson/dvrequests/{{ $dvRequest->id }}/generate-ors-pdf', '_blank');
    }
}

// Close document preview when clicking outside
document.addEventListener('DOMContentLoaded', () => {
    const documentModal = document.getElementById('documentPreviewModal');
    const rejectModal = document.getElementById('rejectModal');
    
    // Close document preview when clicking outside
    if (documentModal) {
        documentModal.addEventListener('click', function(e) {
            // Only close if clicking on the backdrop (not the content)
            if (e.target === this && !e.target.closest('.bg-white')) {
                closeDocumentPreview();
            }
        });
    }
    
    // Close reject modal when clicking outside
    if (rejectModal) {
        rejectModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    }
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (documentModal && !documentModal.classList.contains('hidden')) {
                closeDocumentPreview();
            }
            if (rejectModal && !rejectModal.classList.contains('hidden')) {
                closeRejectModal();
            }
        }
    });

    // Handle reject form submission
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
});

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
            updateButtonsToVerifyMode(dvRequestId);
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

// ✅ Button Updater - directly to Verify mode after Receive
function updateButtonsToVerifyMode(dvRequestId) {
    const buttonContainer = document.getElementById('button-container');
    buttonContainer.innerHTML = `
        <button type="submit" 
                class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Signed by IMSD HEAD
        </button>
        <button type="button"
                onclick="openRejectModal()"
                class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Reject
        </button>
        <a href="{{ route('RegionalOffices.IMSD.index') }}"
           class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
            Back
        </a>
    `;
    
    console.log('✅ Buttons updated to verify mode for IMSD');
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Ensure CSRF token is available
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
</script>