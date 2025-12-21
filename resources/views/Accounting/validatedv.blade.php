@extends('layouts.app')

@section('title', 'Validate DV Request - RANCA')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Section - RANCA Processing -->
        <div class="lg:col-span-2 bg-white shadow rounded-xl p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">RANCA - Accountant III</h1>

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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a22 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
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
                    <!-- GIP Attachments - Read-only Display -->
                    <div class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center mb-4">
                            <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h2 class="text-xl font-bold text-gray-800">GIP Attachments Checklist</h2>
                            <span class="ml-auto text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Verified by Outgoing</span>
                        </div>
                        
                        @if($dvRequest->gipAttachments && $dvRequest->gipAttachments->count() > 0)
                            @php
                                $presentCount = $dvRequest->gipAttachments->where('is_present', 1)->count();
                                $notPresentCount = $dvRequest->gipAttachments->where('is_present', 0)->count();
                                $totalCount = $dvRequest->gipAttachments->count();
                            @endphp
                            
                            <!-- Summary Stats -->
                            <div class="grid grid-cols-4 gap-3 mb-4">
                                <div class="bg-white rounded-lg p-3 text-center border border-gray-200">
                                    <div class="text-2xl font-bold text-gray-700">{{ $totalCount }}</div>
                                    <div class="text-xs text-gray-500">Total</div>
                                </div>
                                <div class="bg-green-50 rounded-lg p-3 text-center border border-green-200">
                                    <div class="text-2xl font-bold text-green-700">{{ $presentCount }}</div>
                                    <div class="text-xs text-green-600">Present</div>
                                </div>
                                <div class="bg-red-50 rounded-lg p-3 text-center border border-red-200">
                                    <div class="text-2xl font-bold text-red-700">{{ $notPresentCount }}</div>
                                    <div class="text-xs text-red-600">Not Present</div>
                                </div>
                            </div>
                            
                            <!-- Attachments List -->
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                @foreach($dvRequest->gipAttachments as $attachment)
                                @php
                                    $isPresent = $attachment->is_present === true;
                                    $isNotPresent = $attachment->is_present === false;
                                @endphp
                                
                                <div class="flex items-center justify-between p-4 bg-white rounded-lg border-2 
                                    {{ $isPresent ? 'border-green-300 bg-green-50' : ($isNotPresent ? 'border-red-300 bg-red-50' : 'border-gray-200') }}">
                                    
                                    <div class="flex items-center space-x-3 flex-1">
                                        <div class="flex-shrink-0">
                                            @if($isPresent)
                                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @elseif($isNotPresent)
                                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @else
                                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="flex-1">
                                            <span class="text-sm font-semibold text-gray-900 block">{{ $attachment->name }}</span>
                                            @if($attachment->remarks)
                                                <span class="text-xs text-gray-600 italic">💬 {{ $attachment->remarks }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <!-- Status Badge - Read-only -->
                                        <span class="text-xs font-bold px-3 py-1.5 rounded-full 
                                            {{ $isPresent ? 'bg-green-200 text-green-800' : ($isNotPresent ? 'bg-red-200 text-red-800' : 'bg-yellow-200 text-yellow-800') }}">
                                            {{ $isPresent ? '✓ Present' : ($isNotPresent ? '✗ Not Present' : '— Not Set') }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-gray-500 italic mt-2">No attachments available for this GIP request.</p>
                            </div>
                        @endif
                    </div>
                @endif
                
                @if($dvRequest->program->prcode === 'TUPAD')
                    <!-- TUPAD Attachments - Read-only Display -->
                    <div class="mb-6 bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-200 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="text-xl font-bold text-gray-800">TUPAD Attachments Checklist</h3>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">Verified by Outgoing</span>
                                @if($dvRequest->tupad_modality)
                                    <span class="text-sm font-semibold px-3 py-1 bg-purple-200 text-purple-800 rounded-full">
                                        📋 {{ ucfirst(str_replace('_', ' ', $dvRequest->tupad_modality)) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($dvRequest->tupad_modality && $dvRequest->tupadAttachments && $dvRequest->tupadAttachments->count() > 0)
                            @php
                                $presentCount = $dvRequest->tupadAttachments->where('is_present', 1)->count();
                                $notPresentCount = $dvRequest->tupadAttachments->where('is_present', 0)->count();
                                $totalCount = $dvRequest->tupadAttachments->count();
                            @endphp
                            
                            <!-- Summary Stats -->
                            <div class="grid grid-cols-4 gap-3 mb-4">
                                <div class="bg-white rounded-lg p-3 text-center border border-gray-200">
                                    <div class="text-2xl font-bold text-gray-700">{{ $totalCount }}</div>
                                    <div class="text-xs text-gray-500">Total</div>
                                </div>
                                <div class="bg-green-50 rounded-lg p-3 text-center border border-green-200">
                                    <div class="text-2xl font-bold text-green-700">{{ $presentCount }}</div>
                                    <div class="text-xs text-green-600">Present</div>
                                </div>
                                <div class="bg-red-50 rounded-lg p-3 text-center border border-red-200">
                                    <div class="text-2xl font-bold text-red-700">{{ $notPresentCount }}</div>
                                    <div class="text-xs text-red-600">Not Present</div>
                                </div>
                            </div>
                            
                            <!-- Attachments List -->
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                @foreach($dvRequest->tupadAttachments as $attachment)
                                @php
                                    $isPresent = $attachment->is_present === true;
                                    $isNotPresent = $attachment->is_present === false;
                                @endphp
                                
                                <div class="flex items-center justify-between p-4 bg-white rounded-lg border-2 
                                    {{ $isPresent ? 'border-green-300 bg-green-50' : ($isNotPresent ? 'border-red-300 bg-red-50' : 'border-gray-200') }}">
                                    
                                    <div class="flex items-center space-x-3 flex-1">
                                        <div class="flex-shrink-0">
                                            @if($isPresent)
                                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @elseif($isNotPresent)
                                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @else
                                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="flex-1">
                                            <span class="text-sm font-semibold text-gray-900 block">{{ $attachment->name }}</span>
                                            @if($attachment->remarks)
                                                <span class="text-xs text-gray-600 italic">💬 {{ $attachment->remarks }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <!-- Status Badge - Read-only -->
                                        <span class="text-xs font-bold px-3 py-1.5 rounded-full 
                                            {{ $isPresent ? 'bg-green-200 text-green-800' : ($isNotPresent ? 'bg-red-200 text-red-800' : 'bg-yellow-200 text-yellow-800') }}">
                                            {{ $isPresent ? '✓ Present' : ($isNotPresent ? '✗ Not Present' : '— Not Set') }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @elseif(!$dvRequest->tupad_modality)
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <p class="text-gray-500 italic mt-2">No modality was selected for this request.</p>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-gray-400 mt-2">No attachments found for the selected modality.</p>
                            </div>
                        @endif
                    </div>
                @endif

                @if($dvRequest->program->prcode === 'TEV')
                    <!-- TEV Attachments - Read-only Display -->
                    <div class="mb-6 bg-gradient-to-r from-emerald-50 to-teal-50 border-2 border-emerald-200 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-emerald-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="text-xl font-bold text-gray-800">TEV Attachments Checklist</h3>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-1 rounded">Verified by Outgoing</span>
                                @if($dvRequest->tev_modality)
                                    <span class="text-sm font-semibold px-3 py-1 bg-emerald-200 text-emerald-800 rounded-full">
                                        📋 {{ ucfirst(str_replace('_', ' ', $dvRequest->tev_modality)) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($dvRequest->tev_modality && $dvRequest->tevAttachments && $dvRequest->tevAttachments->count() > 0)
                            @php
                                $presentCount = $dvRequest->tevAttachments->where('is_present', 1)->count();
                                $notPresentCount = $dvRequest->tevAttachments->where('is_present', 0)->count();
                                $totalCount = $dvRequest->tevAttachments->count();
                            @endphp
                            
                            <!-- Summary Stats -->
                            <div class="grid grid-cols-4 gap-3 mb-4">
                                <div class="bg-white rounded-lg p-3 text-center border border-gray-200">
                                    <div class="text-2xl font-bold text-gray-700">{{ $totalCount }}</div>
                                    <div class="text-xs text-gray-500">Total</div>
                                </div>
                                <div class="bg-green-50 rounded-lg p-3 text-center border border-green-200">
                                    <div class="text-2xl font-bold text-green-700">{{ $presentCount }}</div>
                                    <div class="text-xs text-green-600">Present</div>
                                </div>
                                <div class="bg-red-50 rounded-lg p-3 text-center border border-red-200">
                                    <div class="text-2xl font-bold text-red-700">{{ $notPresentCount }}</div>
                                    <div class="text-xs text-red-600">Not Present</div>
                                </div>
                            </div>
                             
                            <!-- Attachments List -->
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                @foreach($dvRequest->tevAttachments as $attachment)
                                @php
                                    $isPresent = $attachment->is_present === true;
                                    $isNotPresent = $attachment->is_present === false;
                                @endphp
                                
                                <div class="flex items-center justify-between p-4 bg-white rounded-lg border-2 
                                    {{ $isPresent ? 'border-green-300 bg-green-50' : ($isNotPresent ? 'border-red-300 bg-red-50' : 'border-gray-200') }}">
                                    
                                    <div class="flex items-center space-x-3 flex-1">
                                        <div class="flex-shrink-0">
                                            @if($isPresent)
                                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @elseif($isNotPresent)
                                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @else
                                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="flex-1">
                                            <span class="text-sm font-semibold text-gray-900 block">{{ $attachment->name }}</span>
                                            @if($attachment->remarks)
                                                <span class="text-xs text-gray-600 italic">💬 {{ $attachment->remarks }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <!-- Status Badge - Read-only -->
                                        <span class="text-xs font-bold px-3 py-1.5 rounded-full 
                                            {{ $isPresent ? 'bg-green-200 text-green-800' : ($isNotPresent ? 'bg-red-200 text-red-800' : 'bg-yellow-200 text-yellow-800') }}">
                                            {{ $isPresent ? '✓ Present' : ($isNotPresent ? '✗ Not Present' : '— Not Set') }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @elseif(!$dvRequest->tev_modality)
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin"round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <p class="text-gray-500 italic mt-2">No modality was selected for this request.</p>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-gray-400 mt-2">No attachments found for the selected modality.</p>
                            </div>
                        @endif
                    </div>
                @endif

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
                                <div class="mt-1 max-h-20 overflow-y-auto w-full rounded-lg border border-blue-300 bg-blue-50 p-2 text-xs">
                                    @if($dvRequest->orsRequest->uacsCodes->isNotEmpty())
                                        @foreach($dvRequest->orsRequest->uacsCodes as $uacs)
                                            <div class="{{ !$loop->first ? 'mt-1' : '' }}">
                                                {{ $uacs->uacscode }} - {{ $uacs->description }}
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-gray-500">N/A</div>
                                    @endif
                                </div>
                            </div>
                          <div>
                        <label class="block text-xs font-medium text-blue-700">ADL Code</label>
                        <div class="mt-1 max-h-20 overflow-y-auto w-full rounded-lg border border-blue-300 bg-blue-50 p-2 text-xs">
                            @if($dvRequest->orsRequest->adlCodes->isNotEmpty())
                                @foreach($dvRequest->orsRequest->adlCodes as $adl)
                                    <div class="{{ !$loop->first ? 'mt-1' : '' }}">
                                        {{ $adl->adlcode }} - {{ $adl->description }}
                                    </div>
                                @endforeach
                            @else
                                <div class="text-gray-500">N/A</div>
                            @endif
                        </div>
                    </div>
                            <div>
                                <label class="block text-xs font-medium text-blue-700">Allotment Class</label>
                                <input type="text" value="{{ $dvRequest->orsRequest->allotmentClass->allotcode ?? 'N/A' }} - {{ $dvRequest->orsRequest->allotmentClass->description ?? 'N/A' }}" 
                                       class="mt-1 w-full rounded-lg border-blue-300 shadow-sm text-xs bg-blue-50" readonly>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-blue-700">MFO/PAP</label>
                                <input type="text" value="{{ $dvRequest->orsRequest->Mfopap ? $dvRequest->orsRequest->Mfopap->mfocode . ' - ' . $dvRequest->orsRequest->Mfopap->mfodescription : 'N/A' }}" 
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
                        <!-- REMOVED: Start Processing button -->
                        <!-- Directly show Validate & Finalize buttons when received -->
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            Validate & Finalize
                        </button>
                        <button type="button"
                                onclick="openRejectModal({{ $dvRequest->id }})"
                                class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Reject
                        </button>
                        <a href="{{ route('Accounting.dvrequests.index') }}"
                           class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
                           onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
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
        </div>

    </div>
</div>

<!-- Amount Edit Modal -->
<div id="amountModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Edit Amount</h3>
            <button onclick="closeAmountModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="rejectForm" class="space-y-4">
            @csrf
            <input type="hidden" id="rejectDvId" name="dv_request_id" value="{{ $dvRequest->id }}">
            
            <!-- Warning Alert -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm font-medium text-yellow-800">Warning</span>
                </div>
                <p class="text-xs text-yellow-700 mt-1">
                    This action cannot be undone. The DV request will be returned to the focal person for correction.
                </p>
            </div>

            <!-- Rejection Reason -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Reason for Rejection
                    <span class="text-red-500">*</span>
                </label>
                <textarea id="reject-reason" 
                          name="remarks"
                          rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                          placeholder="Please provide a detailed reason for rejecting this DV request (minimum 10 characters)..."
                          required
                          minlength="10"></textarea>
                <p id="reject-error" class="text-xs text-red-500 mt-1 hidden"></p>
                <p class="text-xs text-gray-500 mt-1">
                    Minimum 10 characters required. Be specific about what needs to be corrected.
                </p>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" 
                        onclick="closeRejectModal()"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors duration-200">
                    Cancel
                </button>
                <button type="submit"
                        id="submitRejectBtn"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200">
                    <span class="btn-text">Reject DV Request</span>
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

<!-- Add jQuery and Bootstrap for toast functionality -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ====================================
// AMOUNT MODAL FUNCTIONS (GLOBAL)
// ====================================

let currentDvRequestId = null;
let currentOriginalAmount = 0;

function openAmountModal(dvRequestId, originalAmount) {
    currentDvRequestId = dvRequestId;
    currentOriginalAmount = originalAmount;
    
    // Set current amount display
    document.getElementById('current-amount-display').textContent = `₱${formatNumber(originalAmount)}`;
    
    // Set new amount field to current amount
    document.getElementById('new-amount').value = originalAmount.toFixed(2);
    
    // Clear reason field
    document.getElementById('amount-reason').value = '';
    
    // Clear any previous errors
    document.getElementById('amount-error').classList.add('hidden');
    
    // Show modal
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
// ====================================
// REJECT MODAL FUNCTIONS
// ====================================

let currentRejectDvId = null;

function openRejectModal(dvRequestId) {
    currentRejectDvId = dvRequestId;
    
    // Set the hidden input
    document.getElementById('rejectDvId').value = dvRequestId;
    
    // Clear form
    document.getElementById('reject-reason').value = '';
    document.getElementById('reject-error').classList.add('hidden');
    
    // Show modal
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('reject-reason').focus();
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectForm').reset();
    document.getElementById('reject-error').classList.add('hidden');
    
    currentRejectDvId = null;
}

// Reject Form Submission
document.addEventListener('DOMContentLoaded', function() {
    const rejectForm = document.getElementById('rejectForm');
    if (rejectForm) {
        rejectForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitRejectBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('svg');
            
            const rejectReason = document.getElementById('reject-reason').value.trim();
            const errorElement = document.getElementById('reject-error');
            
            // Validation
            if (rejectReason.length < 10) {
                errorElement.textContent = 'Please provide a detailed reason of at least 10 characters.';
                errorElement.classList.remove('hidden');
                return;
            }
            
            submitBtn.disabled = true;
            btnText.textContent = 'Rejecting...';
            spinner.classList.remove('hidden');
            
            try {
                const response = await fetch(`/Accounting/dvrequests/${currentRejectDvId}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        remarks: rejectReason
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showMessage(data.message, 'success');
                    closeRejectModal();
                    
                    // Redirect to index page after successful rejection
                    setTimeout(() => {
                        window.location.href = "{{ route('Accounting.dvrequests.index') }}";
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Failed to reject DV request');
                }
            } catch (error) {
                console.error('❌ Error rejecting DV:', error);
                showMessage(error.message || 'Failed to reject DV request', 'error');
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = 'Reject DV Request';
                spinner.classList.add('hidden');
            }
        });
    }

    // Modal close on outside click
    const rejectModal = document.getElementById('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    }

    // Real-time validation for reject reason
    const rejectReasonInput = document.getElementById('reject-reason');
    if (rejectReasonInput) {
        rejectReasonInput.addEventListener('input', function() {
            const errorElement = document.getElementById('reject-error');
            if (this.value.trim().length >= 10) {
                errorElement.classList.add('hidden');
            }
        });
    }
});
// ====================================
// UTILITY FUNCTIONS (GLOBAL)
// ====================================

function formatNumber(number) {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
}

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

// ====================================
// BUTTON ACTION FUNCTIONS (GLOBAL)
// ====================================

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
            // After receiving, directly show Validate & Finalize buttons
            updateButtonsToValidateMode(dvRequestId);
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

// REMOVED: handleStartProcessing function since we removed the startProcessing method

function updateButtonsToValidateMode(dvRequestId) {
    const buttonContainer = document.getElementById('button-container');
    buttonContainer.innerHTML = `
        <div class="flex justify-end space-x-3">
            <button type="submit" 
                    class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                Validate & Finalize
            </button>
            <button type="button"
                    onclick="openRejectModal({{ $dvRequest->id }})"
                    class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Reject
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

// ====================================
// DOM READY - INITIALIZATION
// ====================================

document.addEventListener("DOMContentLoaded", () => {
    const dvRequestId = "{{ $dvRequest->id }}";
    
    // Amount Form Submission
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
            
            // Validation
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
                const response = await fetch(`/Accounting/dvrequests/${currentDvRequestId}/update-amount`, {
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
                    // Update display
                    const amountDisplay = document.querySelector('.amount-display span');
                    if (amountDisplay) {
                        amountDisplay.textContent = `₱${formatNumber(newAmount)}`;
                    }
                    
                    // Update hidden input for form submission
                    document.getElementById('amount-hidden').value = newAmount;
                    
                    showMessage('Amount updated successfully!', 'success');
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
    
    // Modal close on outside click
    const amountModal = document.getElementById('amountModal');
    if (amountModal) {
        amountModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAmountModal();
            }
        });
    }


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
});
</script>   
<style>
/* Smooth modal transitions */
#rejectModal, #amountModal {
    transition: opacity 0.3s ease-in-out;
}

/* Ensure modal is above everything */
.z-50 {
    z-index: 50;
}

/* Backdrop blur effect */
.bg-opacity-50 {
    backdrop-filter: blur(4px);
}
</style>