@extends('layouts.app')

@section('title', 'DV Request Status')

@section('content')
@php
    $steps = ['Submitted', 'IMSD', 'Incoming', 'Budget_Officer', 'Outgoing', 'Accountant_III', 'Regional_Director', 'Cashier', 'Completed'];
    
    $statusToStepMap = [
        'submitted' => 'Submitted',
        'IMSD_received' => 'IMSD',
        'IMSD_in_process' => 'IMSD',
        'IMSD_verified' => 'IMSD',
        'IMSD_rejected' => 'IMSD',
        'Incoming-TUPAD_received' => 'Incoming',
        'Incoming-TUPAD_in_process' => 'Incoming',
        'Incoming-TUPAD_verified' => 'Incoming',
        'Incoming-TUPAD_rejected' => 'Incoming',
        'Incoming-GIP_received' => 'Incoming',
        'Incoming-GIP_in_process' => 'Incoming',
        'Incoming-GIP_verified' => 'Incoming',
        'Incoming-GIP_rejected' => 'Incoming',
        'Incoming-TEV_received' => 'Incoming',
        'Incoming-TEV_in_process' => 'Incoming',
        'Incoming-TEV_verified' => 'Incoming',
        'Incoming-TEV_rejected' => 'Incoming',
        'ORS_received' => 'Budget_Officer',
        'ORS_in_process' => 'Budget_Officer',
        'ORS_obligated' => 'Budget_Officer',
        'ORS_onhold' => 'Budget_Officer',
        'ORS_for_revision' => 'Budget_Officer',
        'ORS_rejected' => 'Budget_Officer',
        'Outgoing_received' => 'Outgoing',
        'Outgoing_in_process' => 'Outgoing',
        'Outgoing_finalized' => 'Outgoing',
        'Outgoing_rejected' => 'Outgoing',
        'RANCA_received' => 'Accountant_III',
        'RANCA_in_process' => 'Accountant_III',
        'RANCA_validated' => 'Accountant_III',
        'RANCA_amount_updated' => 'Accountant_III',
        'RANCA_rejected' => 'Accountant_III',
        'Regional_Director_received' => 'Regional_Director',
        'Regional_Director_signed_approved' => 'Regional_Director',
        'Cashier_received' => 'Cashier',
        'Cashier_released' => 'Cashier',
        'completed' => 'Completed'
    ];
    
    $currentStep = $statusToStepMap[$dv->status] ?? 'Submitted';
    
    // Find the current step index
    $currentStepIndex = array_search($currentStep, $steps);
    if ($currentStepIndex === false) {
        $currentStepIndex = 0;
    }
    
    $isRejected = stripos($dv->status, 'rejected') !== false;
    $isCompleted = $dv->status === 'Cashier_released' || $dv->status === 'completed';
    
    // Calculate pending days
    $submittedDate = $dv->created_at;
    $currentDate = now();
    $pendingDays = floor($submittedDate->diffInDays($currentDate));
    $isNearThreshold = $pendingDays >= 8 && $pendingDays < 10;
    $isOverThreshold = $pendingDays >= 10;
    
    $formattedStatus = str_replace('_', ' ', $dv->status);
    $formattedStatus = ucwords($formattedStatus);
@endphp

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8">
    <div class="max-w-5xl mx-auto px-4">
        <!-- Pending Days Alert (only show if not completed or rejected) -->
        @if(!$isCompleted && !$isRejected)
            @if($isOverThreshold)
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg animate-pulse-warning">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-red-800 font-semibold">Request Pending: {{ $pendingDays }} Days</h3>
                        <p class="text-red-700 text-sm mt-1">
                            This request has exceeded the 10-day threshold. It has been pending for <strong>{{ $pendingDays }} days</strong> since submission on {{ $submittedDate->format('M d, Y') }}.
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            {{ $pendingDays }} days
                        </span>
                    </div>
                </div>
            </div>
            @elseif($isNearThreshold)
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-yellow-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-yellow-800 font-semibold">Request Approaching Threshold</h3>
                        <p class="text-yellow-700 text-sm mt-1">
                            This request has been pending for <strong>{{ $pendingDays }} days</strong>. It will exceed the 10-day threshold in {{ 10 - $pendingDays }} day(s).
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                            {{ $pendingDays }} days
                        </span>
                    </div>
                </div>
            </div>
            @endif
        @endif
        
        <!-- Completion Success Alert -->
        @if($isCompleted)
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="text-green-800 font-semibold">Request Completed Successfully!</h3>
                    <p class="text-green-700 text-sm mt-1">Payment has been released by the cashier. This DV request is now completed. (Processed in {{ $pendingDays }} days)</p>
                </div>
            </div>
        </div>
        @endif
        
        <!-- Header with QR Code -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-8 text-white">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="text-center md:text-left">
                        <h1 class="text-3xl font-bold mb-2">DV Request Status</h1>
                        <p class="text-blue-100">Reference Number: <span class="font-mono font-semibold">{{ $dv->reference_number }}</span></p>
                        <p class="text-blue-100 text-sm mt-1">Control Number: {{ $dv->accounting_control_number }}</p>
                    </div>
                    
                    @if($dv->qr_code_path)
                    <div class="bg-white p-4 rounded-lg shadow-lg">
                        <img src="{{ Storage::url($dv->qr_code_path) }}" 
                             alt="QR Code" 
                             class="w-32 h-32 object-contain">
                        <p class="text-xs text-gray-600 text-center mt-2">Scan to verify</p>
                    </div>
                    @else
                    <div class="bg-white p-4 rounded-lg shadow-lg">
                        <div class="w-32 h-32 flex items-center justify-center bg-gray-100 rounded">
                            <span class="text-gray-400 text-xs">No QR Code</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Status Badge -->
            <div class="px-6 py-4 bg-gray-50 border-b">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-gray-600">Current Status:</span>
                        @if($isRejected)
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-red-100 text-red-800 border border-red-300">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                Rejected
                            </span>
                        @elseif($isCompleted)
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800 border border-green-300">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                Completed
                            </span>
                        @else
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-blue-100 text-blue-800 border border-blue-300">
                                <svg class="w-4 h-4 mr-2 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                    <circle cx="10" cy="10" r="3"/>
                                </svg>
                                {{ $formattedStatus }}
                            </span>
                        @endif
                        
                        @if(!$isCompleted && !$isRejected)
                            <span class="text-sm text-gray-500">
                                (Day {{ $pendingDays }} of process)
                            </span>
                        @endif
                    </div>
                    
                    <a href="{{ route('qr.scanner') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        ← Scan Another QR
                    </a>
                </div>
            </div>

            <!-- Progress Tracker -->
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Progress Tracker</h2>
                
                <div class="flex items-center justify-between w-full overflow-x-auto pb-4">
                    @foreach($steps as $index => $step)
                    <div class="flex-1 text-center relative min-w-[80px]">
                        <!-- Connecting line -->
                        @if($index < count($steps) - 1)
                        <div class="absolute top-5 left-1/2 w-full h-1 bg-gray-300 overflow-hidden" style="z-index: 0;">
                            <div class="h-1 origin-left transition-transform duration-700 ease-in-out 
                                @if($isCompleted) bg-green-500 @elseif($isRejected) bg-red-500 @else bg-blue-600 @endif"
                                 style="transform: scaleX({{ $index < $currentStepIndex ? 1 : 0 }});">
                            </div>
                        </div>
                        @endif
                        
                        <!-- Circle -->
                        <div class="w-10 h-10 mx-auto rounded-full flex items-center justify-center transition-all duration-500 ease-in-out relative 
                            @if($isCompleted)
                                bg-green-500 text-white scale-110 shadow-lg ring-4 ring-green-100
                            @elseif($isRejected)
                                @if($index <= $currentStepIndex)
                                    bg-red-500 text-white scale-110 shadow-lg ring-4 ring-red-100
                                @else
                                    bg-gray-300 text-gray-600 scale-100
                                @endif
                            @else
                                @if($index <= $currentStepIndex)
                                    bg-blue-600 text-white scale-110 shadow-lg ring-4 ring-blue-100
                                @else
                                    bg-gray-300 text-gray-600 scale-100
                                @endif
                            @endif"
                             style="z-index: 10;">
                            @if($isRejected && $index === $currentStepIndex)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @elseif($isCompleted && $index === count($steps) - 1)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <span>{{ $index + 1 }}</span>
                            @endif
                        </div>
                        
                        <!-- Step label -->
                        <p class="mt-2 text-xs sm:text-sm transition-colors duration-500 relative 
                            @if($isCompleted)
                                text-green-700 font-medium
                            @elseif($isRejected)
                                @if($index <= $currentStepIndex)
                                    text-red-700 font-medium
                                @else
                                    text-gray-500
                                @endif
                            @else
                                @if($index <= $currentStepIndex)
                                    text-blue-700 font-medium
                                @else
                                    text-gray-500
                                @endif
                            @endif"
                           style="z-index: 10;">
                            {{ str_replace('_', ' ', $step) }}
                        </p>
                    </div>
                    @endforeach
                </div>
                
                <!-- Pending Days Summary -->
                @if(!$isCompleted && !$isRejected)
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Process Duration:</span> {{ $pendingDays }} day(s) since submission
                        </div>
                        <div class="text-sm @if($isOverThreshold) text-red-600 @elseif($isNearThreshold) text-yellow-600 @else text-gray-500 @endif">
                            @if($isOverThreshold)
                                ⚠️ Exceeded 10-day threshold
                            @elseif($isNearThreshold)
                                ⚠️ Approaching 10-day threshold
                            @else
                                {{ 10 - $pendingDays }} day(s) remaining to threshold
                            @endif
                        </div>
                    </div>
                    <!-- Progress bar for days -->
                    <div class="mt-2 bg-gray-200 rounded-full h-2 overflow-hidden">
                        <div class="h-full @if($isOverThreshold) bg-red-500 @elseif($isNearThreshold) bg-yellow-500 @else bg-blue-500 @endif" 
                             style="width: {{ min(100, ($pendingDays / 10) * 100) }}%">
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Request Details -->
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Personal Information -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Requestor Information
                </h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                        <dd class="text-base font-semibold text-gray-900">{{ $dv->focalPerson->fname }} {{ $dv->focalPerson->mi ? $dv->focalPerson->mi . '.' : '' }} {{ $dv->focalPerson->lname }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">TIN Number</dt>
                        <dd class="text-base text-gray-900">{{ $dv->focalPerson->TIN_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="text-base text-gray-900">{{ $dv->focalPerson->Address }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Request Details -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Request Details
                </h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Program</dt>
                        <dd class="text-base font-semibold text-gray-900">{{ $dv->program->prcode ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Amount</dt>
                        <dd class="text-2xl font-bold text-green-600">₱{{ number_format($dv->amount, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Particulars</dt>
                        <dd class="text-base text-gray-900">{{ $dv->particulars }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Date Submitted</dt>
                        <dd class="text-base text-gray-900">{{ $dv->created_at->format('M d, Y h:i A') }}</dd>
                    </div>
                    @if($isRejected)
                    <div>
                        <dt class="text-sm font-medium text-red-500">Status</dt>
                        <dd class="text-base font-semibold text-red-600">⚠️ Request Rejected</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 flex gap-4 justify-center">
            <a href="{{ route('qr.scanner') }}" 
               class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition-colors">
                Scan Another QR
            </a>
            <button onclick="window.print()" 
                    class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition-colors">
                Print Details
            </button>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .max-w-5xl, .max-w-5xl * {
        visibility: visible;
    }
    .max-w-5xl {
        position: absolute;
        left: 0;
        top: 0;
    }
    button, a {
        display: none !important;
    }
}

@keyframes pulse-warning {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}
.animate-pulse-warning {
    animation: pulse-warning 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
@endsection