@extends('layouts.app')

@section('title', 'DV Request Logs')

@section('content')
@php
    $steps = ['Submitted', 'IMSD', 'Incoming', 'Budget_Officer', 'Outgoing', 'Accounting_III', 'Regional Director', 'Cashier', 'Completed'];
    
    // Get the last log entry's action
    $lastLogAction = $logs->last()?->action ?? 'Submitted';
    
    // Comprehensive status-to-step mapping
    $statusToStepMap = [
        // Submitted
        'submitted' => 'Submitted',
        
        // IMSD statuses
        'IMSD_received' => 'IMSD',
        'IMSD_in_process' => 'IMSD',
        'IMSD_verified' => 'IMSD',
        
        // Incoming statuses (TUPAD)
        'Incoming-TUPAD_received' => 'Incoming',
        'Incoming-TUPAD_in_process' => 'Incoming',
        'Incoming-TUPAD_verified' => 'Incoming',
        'Incoming-TUPAD_rejected' => 'Incoming',
        
        // Incoming statuses (GIP)
        'Incoming-GIP_received' => 'Incoming',
        'Incoming-GIP_in_process' => 'Incoming',
        'Incoming-GIP_verified' => 'Incoming',
        'Incoming-GIP_rejected' => 'Incoming',
        
        // Incoming statuses (TEV)
        'Incoming-TEV_received' => 'Incoming',
        'Incoming-TEV_in_process' => 'Incoming',
        'Incoming-TEV_verified' => 'Incoming',
        'Incoming-TEV_rejected' => 'Incoming',
        
        // Budget Officer statuses
        'ORS_received' => 'Budget_Officer',
        'ORS_in_process' => 'Budget_Officer',
        'ORS_obligated' => 'Budget_Officer',
        'ORS_onhold' => 'Budget_Officer',
        'ORS_for_revision' => 'Budget_Officer',
        
        // Outgoing statuses
        'Outgoing_received' => 'Outgoing',
        'Outgoing_in_process' => 'Outgoing',
        'Outgoing_finalized' => 'Outgoing',
        
        // RANCA (Accounting_III) statuses
        'RANCA_received' => 'Accounting_III',
        'RANCA_in_process' => 'Accounting_III',
        'RANCA_validated' => 'Accounting_III',
        'RANCA_amount_updated' => 'Accounting_III',
    ];
    
    // Determine last step from either status or action
    if (isset($statusToStepMap[$dvRequest->status])) {
        $lastStep = $statusToStepMap[$dvRequest->status];
    } elseif (isset($statusToStepMap[$lastLogAction])) {
        $lastStep = $statusToStepMap[$lastLogAction];
    } elseif (in_array($lastLogAction, $steps)) {
        $lastStep = $lastLogAction;
    } else {
        $lastStep = 'Submitted';
    }
    
    // Check for rejection
    $isRejected = stripos($dvRequest->status, 'rejected') !== false || 
                  stripos($lastStep, 'rejected') !== false;
@endphp

<style>
    [x-cloak] { display: none !important; }
</style>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">DV Request Logs</h1>
                <p class="mt-2 text-sm text-gray-600">Track the complete history and status changes of your request</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('FocalPerson.dvrequest.index') }}" 
                   class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200 ease-in-out">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Requests
                </a>
            </div>
        </div>

        <!-- Horizontal Progress Bar (shown on all screens) -->
        <div class="mb-8">
            <!-- Rejection Alert -->
            @if($isRejected)
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="text-red-800 font-semibold">Request Rejected</h3>
                        <p class="text-red-700 text-sm mt-1">This DV request has been rejected. Please check the remarks for more details.</p>
                    </div>
                </div>
            </div>
            @endif

            <div x-data="stepper()" class="flex items-center justify-between w-full overflow-x-auto pb-4">
                <template x-for="(step, index) in steps" :key="index">
                    <div class="flex-1 text-center relative min-w-[80px]">
                        <!-- Connecting line -->
                        <div x-show="index < steps.length - 1"
                             class="absolute top-5 left-1/2 w-full h-1 bg-gray-300 overflow-hidden"
                             style="z-index: 0;">
                            <div class="h-1 origin-left transition-transform duration-700 ease-in-out"
                                 :class="isRejected ? 'bg-red-500' : 'bg-blue-600'"
                                 :style="`transform: scaleX(${index < steps.indexOf(currentStep) ? 1 : 0});`">
                            </div>
                        </div>
                        
                        <!-- Circle -->
                        <div 
                            class="w-10 h-10 mx-auto rounded-full flex items-center justify-center transition-all duration-500 ease-in-out relative"
                            style="z-index: 10;"
                            :class="isRejected 
                                ? (index <= steps.indexOf(currentStep) ? 'bg-red-500 text-white scale-110 shadow-lg' : 'bg-gray-300 text-gray-600 scale-100')
                                : (index <= steps.indexOf(currentStep) ? 'bg-blue-600 text-white scale-110 shadow-lg' : 'bg-gray-300 text-gray-600 scale-100')">
                            <span x-show="!isRejected || index !== steps.indexOf(currentStep)" x-text="index + 1"></span>
                            <svg x-show="isRejected && index === steps.indexOf(currentStep)" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <!-- Step label -->
                        <p class="mt-2 text-xs sm:text-sm transition-colors duration-500 relative"
                           style="z-index: 10;"
                           :class="isRejected
                                ? (index <= steps.indexOf(currentStep) ? 'text-red-700 font-medium' : 'text-gray-500')
                                : (index <= steps.indexOf(currentStep) ? 'text-blue-700 font-medium' : 'text-gray-500')">
                            <span x-text="translateStep(step)"></span>
                        </p>
                    </div>
                </template>
            </div>
        </div>

        <!-- Main Content Area with Flex Layout -->
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- LEFT/MAIN CONTENT (Timeline/Table) -->
            <div class="flex-1">
                <!-- Mobile Timeline View -->
                <div class="lg:hidden bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4 text-white">
                        <h2 class="text-xl font-bold">Document Tracer</h2>
                        <p class="text-sm mt-1 opacity-90">Reference #{{ $dvRequest->reference_number }}</p>
                    </div>

                    <div class="p-6">
                        @foreach($logs as $log)
                        <div class="flex gap-4 mb-6 last:mb-0">
                            <!-- Avatar with connecting line -->
                            <div class="relative flex flex-col items-center">
                                <div class="w-12 h-12 rounded-full overflow-hidden ring-2 ring-white shadow-lg flex-shrink-0 z-10">
                                    @if($log->focalPerson)
                                        <img src="{{ $log->focalPerson->profile_image 
                                            ? asset('storage/profiles/' . $log->focalPerson->profile_image) 
                                            : asset('storage/profiles/default.png') }}" 
                                            class="w-full h-full object-cover">
                                    @elseif($log->accountingUnit)
                                        <img src="{{ $log->accountingUnit->profile_image 
                                            ? asset('storage/profiles/' . $log->accountingUnit->profile_image) 
                                            : asset('storage/profiles/default.png') }}" 
                                            class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                
                                @if(!$loop->last)
                                <div class="w-0.5 h-full bg-gray-200 absolute top-14" style="left: 50%; transform: translateX(-50%);"></div>
                                @endif
                            </div>

                            <!-- Content -->
                            <div class="flex-1 pb-6">
                                <div class="bg-white">
                                    <p class="text-base text-teal-600 font-medium leading-relaxed">
                                        @if($log->remarks)
                                            {{ ucfirst($log->remarks) }}
                                        @else
                                            Document was {{ strtolower($log->action) }}.
                                        @endif
                                    </p>
                                    
                                    <div class="flex items-center gap-2 mt-2 text-sm text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                    
                                    <div class="text-xs text-gray-400 mt-1">
                                        By: 
                                        @if($log->focalperson)
                                            {{ trim($log->focalperson->fname . ' ' . $log->focalperson->lname) }}
                                        @elseif($log->accountingUnit)
                                            {{ $log->accountingUnit->fname }} {{ $log->accountingUnit->lname }}
                                        @elseif($log->external_actor)
                                            {{ $log->external_actor }}
                                        @else
                                            System
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden lg:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Activity Timeline — Ref Number: {{ $dvRequest->reference_number }}
                        </h3>
                        <span id="logCount" class="text-sm text-gray-500">{{ $logs->count() }} {{ Str::plural('entry', $logs->count()) }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200" id="logsTable">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actor</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="logsBody">
                                @foreach($logs as $log)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 w-10 h-10 rounded-full overflow-hidden">
                                                    @if($log->focalPerson)
                                                        <img src="{{ $log->focalPerson->profile_image 
                                                            ? asset('storage/profiles/' . $log->focalPerson->profile_image) 
                                                            : asset('storage/profiles/default.png') }}" 
                                                            class="w-10 h-10 object-cover">
                                                    @elseif($log->accountingUnit)
                                                        <img src="{{ $log->accountingUnit->profile_image 
                                                            ? asset('storage/profiles/' . $log->accountingUnit->profile_image) 
                                                            : asset('storage/profiles/default.png') }}" 
                                                            class="w-10 h-10 object-cover">
                                                    @else
                                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center rounded-full">
                                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        @if($log->focalperson)
                                                            {{ trim($log->focalperson->fname . ' ' . $log->focalperson->mi . ' ' . $log->focalperson->lname) }}
                                                        @elseif($log->accountingUnit)
                                                            {{ $log->accountingUnit->fname }} {{ $log->accountingUnit->lname }}
                                                        @elseif($log->external_actor)
                                                            {{ $log->external_actor }}
                                                        @else
                                                            System
                                                        @endif
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ ucfirst($log->action) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            @if($log->remarks)
                                                <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-blue-400">
                                                    <p class="text-gray-700">{{ ucfirst($log->remarks) }}</p>
                                                </div>
                                            @else
                                                <span class="text-gray-400 italic">No remarks</span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 font-medium">{{ $log->created_at->format('M d, Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ $log->created_at->format('h:i A') }}</div>
                                            <div class="text-xs text-gray-400 mt-1">{{ $log->created_at->diffForHumans() }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-between">
                        <span class="text-sm text-gray-500">Total activity entries: <span id="totalEntries">{{ $logs->count() }}</span></span>
                        <span class="text-xs text-gray-400">Last updated: {{ $logs->first()?->created_at->diffForHumans() ?? 'Never' }}</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDE (Vertical Stepper Sidebar) - Desktop Only -->
            <div class="hidden lg:block">
                <div x-data="{ sidebarExpanded: true, ...stepper() }" class="sticky top-8">
                    <!-- Minimized Sidebar -->
                    <div x-show="!sidebarExpanded" 
                         class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <button @click="sidebarExpanded = true"
                                class="w-full flex items-center justify-between text-sm font-semibold text-gray-700 hover:text-blue-600 transition-colors">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                <span>Progress</span>
                            </div>
                            @if($isRejected)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            @endif
                        </button>
                        
                        <!-- Mini progress indicators -->
                        <div class="mt-4 space-y-2">
                            <template x-for="(step, miniIndex) in steps" :key="'mini-' + miniIndex">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full flex-shrink-0"
                                         :class="isRejected 
                                            ? (miniIndex <= steps.indexOf(currentStep) ? 'bg-red-500' : 'bg-gray-300')
                                            : (miniIndex <= steps.indexOf(currentStep) ? 'bg-blue-600' : 'bg-gray-300')">
                                    </div>
                                    <div class="text-xs truncate"
                                         :class="miniIndex <= steps.indexOf(currentStep) ? 'text-gray-700 font-medium' : 'text-gray-400'">
                                        <span x-text="translateStep(step)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Expanded Sidebar -->
                    <div x-show="sidebarExpanded" 
                         x-cloak
                         class="w-80 bg-white rounded-xl shadow-sm border border-gray-200 max-h-[calc(100vh-4rem)] overflow-y-auto">
                        <div class="sticky top-0 bg-white border-b border-gray-200 p-4 flex items-center justify-between z-20">
                            <h2 class="text-lg font-semibold text-gray-800">Progress Tracker</h2>
                            <div class="flex items-center gap-2">
                                @if($isRejected)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    Rejected
                                </span>
                                @endif
                                <button @click="sidebarExpanded = false"
                                        class="p-1 hover:bg-gray-100 rounded transition-colors"
                                        title="Minimize">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <template x-for="(step, stepIndex) in steps" :key="'step-' + stepIndex">
                                <div class="mb-6 relative last:mb-0" 
                                     x-data="{ 
                                         stepExpanded: stepIndex === steps.indexOf(currentStep),
                                         stepIdx: stepIndex 
                                     }">
                                    <!-- Vertical Line -->
                                    <div class="absolute top-14 left-6 w-0.5 h-full transition-colors duration-500"
                                         :class="isRejected 
                                            ? (stepIdx < steps.indexOf(currentStep) ? 'bg-red-500' : 'bg-gray-300')
                                            : (stepIdx < steps.indexOf(currentStep) ? 'bg-blue-600' : 'bg-gray-300')"
                                         x-show="stepIdx < steps.length - 1"></div>

                                    <!-- Step Header with Icon -->
                                    <div class="flex items-start gap-4">
                                        <!-- Icon/Avatar Circle -->
                                        <div 
                                            class="relative z-10 flex items-center justify-center w-12 h-12 rounded-full transition-all duration-500 ease-in-out flex-shrink-0"
                                            :class="isRejected
                                                ? (stepIdx <= steps.indexOf(currentStep) ? 'bg-red-500 text-white shadow-lg ring-4 ring-red-100' : 'bg-gray-300 text-gray-600')
                                                : (stepIdx <= steps.indexOf(currentStep) ? 'bg-blue-600 text-white shadow-lg ring-4 ring-blue-100' : 'bg-gray-300 text-gray-600')">
                                            <!-- Icons for each step -->
                                            <template x-if="step === 'Submitted'">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </template>
                                            <template x-if="step === 'IMSD'">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                            </template>
                                            <template x-if="step === 'Incoming'">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                                </svg>
                                            </template>
                                            <template x-if="step === 'Budget_Officer'">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </template>
                                            <template x-if="step === 'Outgoing'">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                                </svg>
                                            </template>
                                            <template x-if="step === 'Accounting_III'">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                </svg>
                                            </template>
                                            <template x-if="step === 'Regional Director'">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </template>
                                            <template x-if="step === 'Cashier'">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                            </template>
                                            <template x-if="step === 'Completed'">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </template>
                                        </div>

                                        <!-- Step Module Card -->
                                        <div class="flex-1 bg-white border rounded-lg shadow-sm overflow-hidden transition-all duration-300"
                                             :class="isRejected
                                                ? (stepIdx <= steps.indexOf(currentStep) ? 'border-red-300' : 'border-gray-200')
                                                : (stepIdx <= steps.indexOf(currentStep) ? 'border-blue-300' : 'border-gray-200')">
                                            
                                            <!-- Module Header (Clickable) -->
                                            <button @click="stepExpanded = !stepExpanded" 
                                                    class="w-full px-4 py-3 border-b flex items-center justify-between hover:bg-opacity-80 transition-colors"
                                                    :class="isRejected
                                                        ? (stepIdx <= steps.indexOf(currentStep) ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200')
                                                        : (stepIdx <= steps.indexOf(currentStep) ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200')">
                                                <div class="flex items-center gap-3 flex-1">
                                                    <h3 class="text-sm font-semibold transition-colors duration-500 text-left"
                                                       :class="isRejected
                                                            ? (stepIdx <= steps.indexOf(currentStep) ? 'text-red-700' : 'text-gray-500')
                                                            : (stepIdx <= steps.indexOf(currentStep) ? 'text-blue-700' : 'text-gray-500')">
                                                        <span x-text="translateStep(step)"></span>
                                                    </h3>
                                                    
                                                    <!-- Status Badge -->
                                                    <div>
                                                        <span x-show="!isRejected && stepIdx < steps.indexOf(currentStep)"
                                                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                            Completed
                                                        </span>
                                                        <span x-show="isRejected && stepIdx < steps.indexOf(currentStep)"
                                                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                            Processed
                                                        </span>
                                                        <span x-show="stepIdx === steps.indexOf(currentStep) && !isRejected"
                                                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                            <svg class="w-3 h-3 mr-1 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                                                <circle cx="10" cy="10" r="3"/>
                                                            </svg>
                                                            In Progress
                                                        </span>
                                                        <span x-show="stepIdx === steps.indexOf(currentStep) && isRejected"
                                                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                            </svg>
                                                            Rejected
                                                        </span>
                                                        <span x-show="stepIdx > steps.indexOf(currentStep)"
                                                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                            Pending
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Expand/Collapse Icon -->
                                                <svg class="w-5 h-5 transition-transform duration-300 ml-2" 
                                                     :class="stepExpanded ? 'rotate-180' : 'rotate-0'"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>

                                            <!-- Module Body - Activities (Collapsible) -->
                                            <div x-show="stepExpanded" 
                                                 x-transition:enter="transition ease-out duration-300"
                                                 x-transition:enter-start="opacity-0 max-h-0"
                                                 x-transition:enter-end="opacity-100 max-h-screen"
                                                 x-transition:leave="transition ease-in duration-200"
                                                 x-transition:leave-start="opacity-100 max-h-screen"
                                                 x-transition:leave-end="opacity-0 max-h-0"
                                                 x-cloak>
                                                <template x-if="stepIdx <= steps.indexOf(currentStep)">
                                                    <div class="p-4 space-y-3 bg-gray-50">
                                                        @foreach($logs as $log)
                                                            @php
                                                                $logAction = strtolower($log->action);
                                                            @endphp
                                                            <template x-if="'{{ $logAction }}' === step.toLowerCase() || 
                                                                            (step === 'IMSD' && '{{ $logAction }}'.includes('imsd'))">
                                                                <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-gray-200 hover:shadow-sm transition-shadow">
                                                                    <!-- Small Avatar -->
                                                                    <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0 ring-2 ring-white">
                                                                        @if($log->focalPerson)
                                                                            <img src="{{ $log->focalPerson->profile_image 
                                                                                ? asset('storage/profiles/' . $log->focalPerson->profile_image) 
                                                                                : asset('storage/profiles/default.png') }}" 
                                                                                class="w-full h-full object-cover">
                                                                        @elseif($log->accountingUnit)
                                                                            <img src="{{ $log->accountingUnit->profile_image 
                                                                                ? asset('storage/profiles/' . $log->accountingUnit->profile_image) 
                                                                                : asset('storage/profiles/default.png') }}" 
                                                                                class="w-full h-full object-cover">
                                                                        @else
                                                                            <div class="w-full h-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                                                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                                                </svg>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                    
                                                                    <!-- Activity Details -->
                                                                    <div class="flex-1 min-w-0">
                                                                        <p class="text-xs text-gray-600 mb-1">
                                                                            <span class="font-semibold text-gray-800">
                                                                                @if($log->focalperson)
                                                                                    {{ trim($log->focalperson->fname . ' ' . $log->focalperson->lname) }}
                                                                                @elseif($log->accountingUnit)
                                                                                    {{ $log->accountingUnit->fname }} {{ $log->accountingUnit->lname }}
                                                                                @elseif($log->external_actor)
                                                                                    {{ $log->external_actor }}
                                                                                @else
                                                                                    System
                                                                                @endif
                                                                            </span>
                                                                        </p>
                                                                        @if($log->remarks)
                                                                            <p class="text-sm text-gray-700 mb-1">{{ $log->remarks }}</p>
                                                                        @else
                                                                            <p class="text-sm text-gray-500 italic mb-1">No remarks provided</p>
                                                                        @endif
                                                                        <div class="flex items-center text-xs text-gray-400 mt-1">
                                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                            </svg>
                                                                            {{ $log->created_at->diffForHumans() }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        @endforeach
                                                        
                                                        <!-- Empty state when no activities -->
                                                        <div x-show="!hasActivitiesForStep(step)" class="text-center py-4 text-gray-400 text-sm italic">
                                    
                                                    </div>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- Collapsed state for future steps -->
                                                <template x-if="stepIdx > steps.indexOf(currentStep)">
                                                    <div class="p-4 text-center text-gray-400 text-sm italic">
                                                        Awaiting processing...
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================================
// ALPINE.JS STEPPER COMPONENT
// ============================================================
function stepper() {
    return {
        steps: @json($steps),
        currentStep: 'Submitted',
        lastStep: '{{ $lastStep }}',
        nextStep: '{{ $lastStep }}',
        isRejected: {{ $isRejected ? 'true' : 'false' }},
        sidebarExpanded: true,
        lastKnownStatus: '{{ $dvRequest->status }}',
        
        statusToStepMap: {
            'submitted': 'Submitted',
            'IMSD_received': 'IMSD',
            'IMSD_in_process': 'IMSD',
            'IMSD_verified': 'IMSD',
            'Incoming-TUPAD_received': 'Incoming',
            'Incoming-TUPAD_in_process': 'Incoming',
            'Incoming-TUPAD_verified': 'Incoming',
            'Incoming-TUPAD_rejected': 'Incoming',
            'Incoming-GIP_received': 'Incoming',
            'Incoming-GIP_in_process': 'Incoming',
            'Incoming-GIP_verified': 'Incoming',
            'Incoming-GIP_rejected': 'Incoming',
            'Incoming-TEV_received': 'Incoming',
            'Incoming-TEV_in_process': 'Incoming',
            'Incoming-TEV_verified': 'Incoming',
            'Incoming-TEV_rejected': 'Incoming',
            'ORS_received': 'Budget_Officer',
            'ORS_in_process': 'Budget_Officer',
            'ORS_obligated': 'Budget_Officer',
            'ORS_onhold': 'Budget_Officer',
            'ORS_for_revision': 'Budget_Officer',
            'Outgoing_received': 'Outgoing',
            'Outgoing_in_process': 'Outgoing',
            'Outgoing_finalized': 'Outgoing',
            'RANCA_received': 'Accounting_III',
            'RANCA_in_process': 'Accounting_III',
            'RANCA_validated': 'Accounting_III',
            'RANCA_amount_updated': 'Accounting_III',
        },
        
        translateStep(step) {
            if (step.startsWith('IMSD')) {
                return 'Regional Office';
            }
            return step.replace(/_/g, ' ');
        },
        
        hasActivitiesForStep(step) {
            const logs = @json($logs->pluck('action')->map(function($action) { return strtolower($action); }));
            const stepLower = step.toLowerCase();
            
            return logs.some(log => {
                if (log === stepLower) return true;
                if (step === 'IMSD' && log.includes('imsd')) return true;
                if (step === 'Incoming' && log.includes('incoming')) return true;
                if (step === 'Budget_Officer' && (log.includes('budget') || log.includes('ors'))) return true;
                if (step === 'Outgoing' && log.includes('outgoing')) return true;
                if (step === 'Accounting_III' && (log.includes('accounting') || log.includes('ranca'))) return true;
                return false;
            });
        },
        
        mapStatusToStep(status) {
            if (this.statusToStepMap[status]) {
                return this.statusToStepMap[status];
            }
            if (this.steps.includes(status)) {
                return status;
            }
            return 'Submitted';
        },
        
            updateStep(newStep, newIsRejected) {
            if (!newStep) return;

            // Normalize incoming step for comparison
            const normalizedStep = newStep.replace(/\s/g, '_');

            // Find the step in the steps array
            let targetIndex = this.steps.findIndex(s => s.toLowerCase() === normalizedStep.toLowerCase());

            if (targetIndex === -1) {
                console.warn(`⚠️ Step '${newStep}' not found in steps array`);
                return;
            }

            // Skip if already at this step
            if (this.currentStep === this.steps[targetIndex]) return;

            const previousStep = this.currentStep;
            this.currentStep = this.steps[targetIndex];

            if (newIsRejected !== undefined) {
                this.isRejected = newIsRejected;
            }

            console.log(`Advancing from step ${previousStep} to ${this.currentStep}`);

            // Animate stepper if needed
            const currentIndex = this.steps.indexOf(previousStep);
            if (targetIndex > currentIndex) {
                this.advanceStep(currentIndex, targetIndex);
            }
        },
        
        init() {
            console.log('Stepper initialized');
            this.currentStep = this.lastStep;
            this.nextStep = this.lastStep;
            
            // Your working polling code
            setInterval(() => {
                fetch('{{ route("FocalPerson.dvrequest.latestStep", $dvRequest) }}')
                    .then(res => res.json())
                    .then(data => {
                        this.updateStep(data.lastStep, data.isRejected);
                    });
            }, 10000);
            
            window.stepperComponent = this;
        },
        
        advanceStep(currentIndex, targetIndex) {
            if (currentIndex < targetIndex) {
                this.nextStep = this.steps[currentIndex + 1];
                setTimeout(() => {
                    this.currentStep = this.steps[currentIndex + 1];
                    this.advanceStep(currentIndex + 1, targetIndex);
                }, 250);
            }
        }
    }
}
</script>

<script src="https://js.pusher.com/8.2/pusher.min.js"></script>

<script>
const LogUpdateManager = {
    pusherConnected: false,
    pusherUnavailable: false,
    lastPusherEvent: Date.now(),
    lastPollingCheck: Date.now(),
    pollingInterval: null,
    healthCheckInterval: null,
    consecutivePusherFailures: 0,
    
    config: {
        pollingInterval: 10000,      // Poll every 10 seconds (only when Pusher fails)
        healthCheckInterval: 20000,  // Check Pusher health every 20 seconds
        pusherTimeout: 30000,        // Consider Pusher stale after 30 seconds
        maxConsecutiveFailures: 3,
    },

    init() {
        console.log('🚀 Log Update Manager initializing...');
        
        // Check if Pusher is available
        if (typeof Pusher === 'undefined') {
            console.warn('⚠️ Pusher library not loaded - using polling only');
            this.pusherUnavailable = true;
            this.startPolling();
            return;
        }
        
        this.initPusher();
        this.startHealthCheck();
    },

    initPusher() {
        try {
            this.pusher = new Pusher("{{ config('broadcasting.connections.pusher.key') }}", {
                cluster: "{{ config('broadcasting.connections.pusher.options.cluster') }}",
                encrypted: true,
                enabledTransports: ['ws', 'wss'],
                disabledTransports: ['sockjs'],
                activityTimeout: 30000,
                pongTimeout: 10000,
            });

            this.channel = this.pusher.subscribe("dv-requests-{{ auth()->id() }}");

            // MAIN EVENT: Listen for status updates
            this.channel.bind("status-updated", (data) => {
                console.log('📡 Pusher event received:', data);
                this.onPusherEvent(data);
            });

            this.pusher.connection.bind('connected', () => {
                console.log('✅ Pusher connected');
                this.onPusherConnected();
            });

            this.pusher.connection.bind('disconnected', () => {
                console.warn('🚫 Pusher disconnected');
                this.onPusherDisconnected();
            });

            this.pusher.connection.bind('error', (err) => {
                console.error('❌ Pusher error:', err);
                this.consecutivePusherFailures++;
                this.onPusherDisconnected();
            });

        } catch (error) {
            console.error('❌ Failed to initialize Pusher:', error);
            this.pusherUnavailable = true;
            this.startPolling();
        }
    },

    onPusherEvent(data) {
    console.log('Pusher event:', data);
    
    // If stepper exists and we have status, update it
    if (window.stepperComponent && data.status) {
        const mappedStep = window.stepperComponent.mapStatusToStep(data.status);
        window.stepperComponent.updateStep(mappedStep, data.isRejected);
    }
    
    // Show feedback
    if (data.actor_name || data.remarks) {
        showImmediateFeedback(data);
    }
    
    // Refresh logs
    setTimeout(() => this.refreshLogs(), 1500);
},

    onPusherConnected() {
        this.pusherConnected = true;
        this.lastPusherEvent = Date.now();
        this.consecutivePusherFailures = 0;
        this.stopPolling();
    },

    onPusherDisconnected() {
        this.pusherConnected = false;
        this.consecutivePusherFailures++;
        
        if (!this.pollingInterval) {
            console.warn('⚠️ Starting polling fallback');
            this.startPolling();
        }
    },

    // Polling fallback (only when Pusher fails)
    async startPolling() {
        if (this.pollingInterval) return;
        
        console.log('⏱️ Polling started');
        
        // Immediate first check
        await this.pollForUpdates();
        
        // Then continue at interval
        this.pollingInterval = setInterval(() => {
            this.pollForUpdates();
        }, this.config.pollingInterval);
    },

    stopPolling() {
        if (this.pollingInterval) {
            console.log('🛑 Polling stopped');
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    },

    async pollForUpdates() {
        try {
            const response = await fetch(`{{ route('FocalPerson.dvrequest.latestStep', $dvRequest) }}`, {
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Polling request failed');

            const data = await response.json();
            
            // Update stepper if status changed
           if (window.stepperComponent && data.lastStep) {
                const mappedStep = window.stepperComponent.mapStatusToStep(data.lastStep);

                // Only update if the step has actually changed
                if (mappedStep !== window.stepperComponent.currentStep) {
                    window.stepperComponent.updateStep(mappedStep, data.isRejected || false);
                    console.log('✅ Stepper updated from polling');
                    await this.refreshLogs();
                }
            }
            
            this.lastPollingCheck = Date.now();
            
        } catch (error) {
            console.error('❌ Polling failed:', error);
        }
    },

    async refreshLogs() {
        try {
            const response = await fetch(`{{ route('FocalPerson.dvrequest.logs', $dvRequest) }}?ajax=1&include_step=1`, {
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to fetch logs');

            const data = await response.json();
            
            // Remove temporary row if exists
            const tempRow = document.getElementById("temp-row");
            if (tempRow) tempRow.remove();

            // Update table
            const tbody = document.getElementById("logsBody");
            if (tbody && data.html) {
                tbody.innerHTML = data.html;
                updateLogCounts(data.count);

                const lastUpdatedSpan = document.querySelector('.text-xs.text-gray-400');
                if (lastUpdatedSpan && data.lastUpdated) {
                    lastUpdatedSpan.textContent = `Last updated: ${data.lastUpdated}`;
                }
            }

            // Reload on mobile for full experience
            if (window.innerWidth < 1024) {
                location.reload();
            }

        } catch (error) {
            console.error('❌ Failed to refresh logs:', error);
        }
    },

    startHealthCheck() {
        if (this.pusherUnavailable) return;
        
        this.healthCheckInterval = setInterval(() => {
            this.checkSystemHealth();
        }, this.config.healthCheckInterval);
    },

    checkSystemHealth() {
        const timeSinceLastPusher = Date.now() - this.lastPusherEvent;
        const isPusherStale = timeSinceLastPusher > this.config.pusherTimeout;
        const tooManyFailures = this.consecutivePusherFailures >= this.config.maxConsecutiveFailures;

        // Start polling if Pusher seems dead
        if ((isPusherStale || tooManyFailures) && !this.pollingInterval) {
            console.warn('⚠️ Pusher inactive, starting polling');
            this.startPolling();
        }

        // Stop polling if Pusher comes back to life
        if (this.pusherConnected && this.pollingInterval && !isPusherStale) {
            console.log('✅ Pusher active again, stopping polling');
            this.stopPolling();
        }

        console.log(`💓 Health: Pusher=${this.pusherConnected ? 'ON' : 'OFF'}, Polling=${this.pollingInterval ? 'ON' : 'OFF'}, LastEvent=${Math.round(timeSinceLastPusher/1000)}s ago`);
    },

    destroy() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        if (this.healthCheckInterval) clearInterval(this.healthCheckInterval);
        if (this.pusher) this.pusher.disconnect();
    }
};

// ============================================================
// HELPER FUNCTIONS
// ============================================================
function showImmediateFeedback(data) {
    const tbody = document.getElementById("logsBody");
    if (!tbody) return;

    const existingTemp = document.getElementById("temp-row");
    if (existingTemp) existingTemp.remove();

    const newRow = document.createElement("tr");
    newRow.className = "hover:bg-gray-50 transition-colors duration-150 bg-blue-50 animate-pulse";
    newRow.id = "temp-row";

    const actorName = data.actor_name || 'Processing...';
    const actorRole = data.action || 'System Action';
    const actorAvatar = data.actor_avatar || '{{ asset("storage/profiles/default.png") }}';

    newRow.innerHTML = `
        <td class="px-6 py-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-10 h-10 rounded-full overflow-hidden">
                    <img src="${actorAvatar}" class="w-10 h-10 object-cover" 
                         onerror="this.src='{{ asset("storage/profiles/default.png") }}'">
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">${actorName}</div>
                    <div class="text-xs text-gray-500">${actorRole}</div>
                </div>
            </div>
        </td>
        <td class="px-6 py-4">
            ${data.remarks && data.remarks.trim() !== '' 
                ? `<div class="bg-gray-50 rounded-lg p-3 border-l-4 border-blue-400">
                       <p class="text-gray-700">${data.remarks}</p>
                   </div>` 
                : `<span class="text-gray-400 italic">Processing...</span>`}
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900 font-medium">Just now</div>
            <div class="text-xs text-gray-500">Via Pusher ⚡</div>
        </td>
    `;

    tbody.prepend(newRow);
    updateLogCounts(tbody.children.length);
}

function updateLogCounts(count) {
    const logCount = document.getElementById("logCount");
    const totalEntries = document.getElementById("totalEntries");
    
    if (logCount) {
        logCount.textContent = `${count} ${count === 1 ? "entry" : "entries"}`;
    }
    if (totalEntries) {
        totalEntries.textContent = count;
    }
}

// ============================================================
// INITIALIZATION
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    const checkReady = setInterval(() => {
        const pusherReady = typeof Pusher !== 'undefined';
        const stepperReady = typeof window.stepperComponent !== 'undefined';
        
        if (stepperReady) {
            console.log('✅ Stepper ready, starting Update Manager');
            clearInterval(checkReady);
            LogUpdateManager.init();
        } else {
            console.log('⏳ Waiting for stepper...');
        }
    }, 100);
    
    setTimeout(() => {
        clearInterval(checkReady);
        if (!window.stepperComponent) {
            console.error('❌ Stepper never initialized');
        }
    }, 10000);
});

window.addEventListener('beforeunload', () => {
    LogUpdateManager.destroy();
});
</script>
@endsection
