@extends('layouts.app')

@section('title', 'Finalize DV Request')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Section - Finalization -->
        <div class="lg:col-span-2 bg-white shadow rounded-xl p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Outgoing - Finalize DV</h1>

            <form action="{{ route('Outgoing.dvrequests.finalize', $dvRequest->id) }}" method="POST" class="space-y-6" id="finalizeForm">
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
                    <div class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center mb-4">
                            <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h2 class="text-xl font-bold text-gray-800">GIP Attachments Checklist</h2>
                            <span class="ml-auto text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Editable by Outgoing</span>
                        </div>
                        
                        @if($gipAttachments && $gipAttachments->count() > 0)
                            @php
                                $presentCount = $gipAttachments->where('is_present', 1)->count();
                                $notPresentCount = $gipAttachments->where('is_present', 0)->count();
                                $totalCount = $gipAttachments->count();
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
                                @foreach($gipAttachments as $attachment)
                                @php
                                    $isPresent = $attachment->is_present === true;
                                    $isNotPresent = $attachment->is_present === false;
                                @endphp
                                
                                <div class="flex items-center justify-between p-4 bg-white rounded-lg border-2 
                                    {{ $isPresent ? 'border-green-300 bg-green-50' : ($isNotPresent ? 'border-red-300 bg-red-50' : 'border-gray-200') }}"
                                    id="gip-attachment-{{ $attachment->id }}">
                                    
                                    <!-- Hidden form input for this attachment -->
                                    <input type="hidden" 
                                        name="gip_attachments[{{ $attachment->id }}]" 
                                        id="gip_attachment_input_{{ $attachment->id }}"
                                        value="{{ $isPresent ? 'true' : 'false' }}">
                                    
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
                                        <!-- Status Badge -->
                                        <span class="text-xs font-bold px-3 py-1.5 rounded-full 
                                            {{ $isPresent ? 'bg-green-200 text-green-800' : ($isNotPresent ? 'bg-red-200 text-red-800' : 'bg-yellow-200 text-yellow-800') }} status-badge">
                                            {{ $isPresent ? '✓ Present' : ($isNotPresent ? '✗ Not Present' : '— Not Set') }}
                                        </span>
                                        
                                        <!-- Action Buttons -->
                                        <div class="flex items-center space-x-1">
                                            <button type="button" 
                                                    class="present-btn px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-lg transition-colors duration-200 flex items-center"
                                                    data-attachment-id="{{ $attachment->id }}"
                                                    data-attachment-type="gip">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Present
                                            </button>
                                            <button type="button" 
                                                    class="not-present-btn px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-lg transition-colors duration-200 flex items-center"
                                                    data-attachment-id="{{ $attachment->id }}"
                                                    data-attachment-type="gip">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                Not Present
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            <!-- Quick Action Buttons -->
                            <div class="mt-4 flex items-center justify-between">
                                <div class="text-xs text-gray-500">
                                    Click "Present" or "Not Present" buttons to update attachment status
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button type="button" 
                                            onclick="markAllGIPAs('present')"
                                            class="inline-flex items-center px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 text-xs font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Mark All Present
                                    </button>
                                    <button type="button" 
                                            onclick="markAllGIPAs('not_present')"
                                            class="inline-flex items-center px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Mark All Not Present
                                    </button>
                                </div>
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
                    <!-- TUPAD Attachments Checklist -->
                    <div class="mb-6 bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-200 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="text-xl font-bold text-gray-800">TUPAD Attachments Checklist</h3>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">Editable by Outgoing</span>
                                @if($selectedModality)
                                    <span class="text-sm font-semibold px-3 py-1 bg-purple-200 text-purple-800 rounded-full">
                                        📋 {{ ucfirst(str_replace('_', ' ', $selectedModality)) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($selectedModality && $tupadAttachments && $tupadAttachments->count() > 0)
                            @php
                                $presentCount = $tupadAttachments->where('is_present', 1)->count();
                                $notPresentCount = $tupadAttachments->where('is_present', 0)->count();
                                $totalCount = $tupadAttachments->count();
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
                                @foreach($tupadAttachments as $attachment)
                                @php
                                    $isPresent = $attachment->is_present === true;
                                    $isNotPresent = $attachment->is_present === false;
                                @endphp
                                
                                <div class="flex items-center justify-between p-4 bg-white rounded-lg border-2 
                                    {{ $isPresent ? 'border-green-300 bg-green-50' : ($isNotPresent ? 'border-red-300 bg-red-50' : 'border-gray-200') }}"
                                    id="tupad-attachment-{{ $attachment->id }}">
                                    
                                    <!-- Hidden form input for this attachment -->
                                    <input type="hidden" 
                                           name="attachments[{{ $attachment->id }}]" 
                                           id="tupad_attachment_input_{{ $attachment->id }}"
                                           value="{{ $isPresent ? 'true' : 'false' }}">
                                    
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
                                        <!-- Status Badge -->
                                        <span class="text-xs font-bold px-3 py-1.5 rounded-full 
                                            {{ $isPresent ? 'bg-green-200 text-green-800' : ($isNotPresent ? 'bg-red-200 text-red-800' : 'bg-yellow-200 text-yellow-800') }} status-badge">
                                            {{ $isPresent ? '✓ Present' : ($isNotPresent ? '✗ Not Present' : '— Not Set') }}
                                        </span>
                                        
                                        <!-- Action Buttons -->
                                        <div class="flex items-center space-x-1">
                                            <button type="button" 
                                                    class="present-btn px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-lg transition-colors duration-200 flex items-center"
                                                    data-attachment-id="{{ $attachment->id }}"
                                                    data-attachment-type="tupad">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Present
                                            </button>
                                            <button type="button" 
                                                    class="not-present-btn px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-lg transition-colors duration-200 flex items-center"
                                                    data-attachment-id="{{ $attachment->id }}"
                                                    data-attachment-type="tupad">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                Not Present
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            <!-- Quick Action Buttons -->
                            <div class="mt-4 flex items-center justify-between">
                                <div class="text-xs text-gray-500">
                                    Click "Present" or "Not Present" buttons to update attachment status
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button type="button" 
                                            onclick="markAllTUPADAs('present')"
                                            class="inline-flex items-center px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 text-xs font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Mark All Present
                                    </button>
                                    <button type="button" 
                                            onclick="markAllTUPADAs('not_present')"
                                            class="inline-flex items-center px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Mark All Not Present
                                    </button>
                                </div>
                            </div>
                        @elseif(!$selectedModality)
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
                    <!-- TEV Attachments Checklist -->
                    <div class="mb-6 bg-gradient-to-r from-emerald-50 to-teal-50 border-2 border-emerald-200 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-emerald-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="text-xl font-bold text-gray-800">TEV Attachments Checklist</h3>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-1 rounded">Editable by Outgoing</span>
                                @if($tevSelectedModality)
                                    <span class="text-sm font-semibold px-3 py-1 bg-emerald-200 text-emerald-800 rounded-full">
                                        📋 {{ ucfirst(str_replace('_', ' ', $tevSelectedModality)) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($tevSelectedModality && $tevAttachments && $tevAttachments->count() > 0)
                            @php
                                $presentCount = $tevAttachments->where('is_present', 1)->count();
                                $notPresentCount = $tevAttachments->where('is_present', 0)->count();
                                $totalCount = $tevAttachments->count();
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
                                @foreach($tevAttachments as $attachment)
                                @php
                                    $isPresent = $attachment->is_present === true;
                                    $isNotPresent = $attachment->is_present === false;
                                @endphp
                                
                                <div class="flex items-center justify-between p-4 bg-white rounded-lg border-2 
                                    {{ $isPresent ? 'border-green-300 bg-green-50' : ($isNotPresent ? 'border-red-300 bg-red-50' : 'border-gray-200') }}"
                                    id="tev-attachment-{{ $attachment->id }}">
                                    
                                    <!-- Hidden form input for this attachment -->
                                    <input type="hidden" 
                                        name="tev_attachments[{{ $attachment->id }}]" 
                                        id="tev_attachment_input_{{ $attachment->id }}"
                                        value="{{ $isPresent ? 'true' : 'false' }}">
                                    
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
                                        <!-- Status Badge -->
                                        <span class="text-xs font-bold px-3 py-1.5 rounded-full 
                                            {{ $isPresent ? 'bg-green-200 text-green-800' : ($isNotPresent ? 'bg-red-200 text-red-800' : 'bg-yellow-200 text-yellow-800') }} status-badge">
                                            {{ $isPresent ? '✓ Present' : ($isNotPresent ? '✗ Not Present' : '— Not Set') }}
                                        </span>
                                        
                                        <!-- Action Buttons -->
                                        <div class="flex items-center space-x-1">
                                            <button type="button" 
                                                    class="present-btn px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-lg transition-colors duration-200 flex items-center"
                                                    data-attachment-id="{{ $attachment->id }}"
                                                    data-attachment-type="tev">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Present
                                            </button>
                                            <button type="button" 
                                                    class="not-present-btn px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-lg transition-colors duration-200 flex items-center"
                                                    data-attachment-id="{{ $attachment->id }}"
                                                    data-attachment-type="tev">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                Not Present
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            <!-- Quick Action Buttons -->
                            <div class="mt-4 flex items-center justify-between">
                                <div class="text-xs text-gray-500">
                                    Click "Present" or "Not Present" buttons to update attachment status
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button type="button" 
                                            onclick="markAllTEVAs('present')"
                                            class="inline-flex items-center px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 text-xs font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Mark All Present
                                    </button>
                                    <button type="button" 
                                            onclick="markAllTEVAs('not_present')"
                                            class="inline-flex items-center px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Mark All Not Present
                                    </button>
                                </div>
                            </div>
                        @elseif(!$tevSelectedModality)
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
                <div>
                    <label class="block text-sm font-medium text-gray-700">Remarks / Notes</label>
                    <textarea id="remarks" name="remarks" rows="3" 
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm"
                            placeholder="Enter remarks or notes...">{{ old('remarks') }}</textarea>
                </div>

                <!-- Actions Section -->
                @if($dvRequest->status === 'Outgoing_received')
                    <!-- Finalize Mode -->
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
                @else
                    <!-- Default mode -->
                    <div id="button-container" class="flex justify-end space-x-3">
                        <a href="{{ route('Outgoing.dvrequests.index') }}"
                            class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200" 
                            onclick="releaseLock(event, {{ $dvRequest->id }})">
                            Back
                        </a>
                    </div>
                @endif
            </form>
        </div>
        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Additional sidebar content can go here -->
        </div>
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
</div>
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
// ========================================
// ATTACHMENT MANAGEMENT
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    initializeAttachmentButtons();
    initializeRejectModal();
    initializeLockManagement();
});

function initializeAttachmentButtons() {
    // Present buttons
    document.querySelectorAll('.present-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const attachmentId = this.dataset.attachmentId;
            const attachmentType = this.dataset.attachmentType;
            markAttachment(attachmentId, attachmentType, true);
        });
    });

    // Not Present buttons
    document.querySelectorAll('.not-present-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const attachmentId = this.dataset.attachmentId;
            const attachmentType = this.dataset.attachmentType;
            markAttachment(attachmentId, attachmentType, false);
        });
    });
}

async function markAttachment(attachmentId, attachmentType, isPresent) {
    const dvRequestId = '{{ $dvRequest->id }}';
    const container = document.getElementById(`${attachmentType}-attachment-${attachmentId}`);
    const badge = container.querySelector('.status-badge');
    const icon = container.querySelector('svg');
    const hiddenInput = document.getElementById(`${attachmentType}_attachment_input_${attachmentId}`);
    
    // Store original state
    const originalText = badge.textContent;
    const originalBg = badge.className;
    
    // Update UI immediately
    badge.textContent = '⏳ Updating...';
    badge.className = 'text-xs font-bold px-3 py-1.5 rounded-full bg-yellow-200 text-yellow-800';
    
    try {
        // ✅ FIX: Update hidden input with STRING value
        hiddenInput.value = isPresent ? 'true' : 'false';
        
        console.log('Updating attachment:', {
            attachmentId,
            attachmentType,
            isPresent,
            inputValue: hiddenInput.value // Should be 'true' or 'false' as string
        });
        
        // Send AJAX request
        const response = await fetch(`/Outgoing/dvrequests/${dvRequestId}/update-attachment`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                attachment_id: parseInt(attachmentId),
                attachment_type: attachmentType,
                is_present: isPresent, // Send as boolean to backend
                remarks: `Marked as ${isPresent ? 'Present' : 'Not Present'} by Outgoing`
            })
        });

        const data = await response.json();

        if (data.success) {
            // Update UI with new state
            if (isPresent) {
                container.className = container.className.replace(/border-(red|gray|yellow)-[0-9]+/g, 'border-green-300');
                container.className = container.className.replace(/bg-(red|gray|yellow)-[0-9]+/g, 'bg-green-50');
                badge.textContent = '✓ Present';
                badge.className = 'text-xs font-bold px-3 py-1.5 rounded-full bg-green-200 text-green-800';
                
                if (icon) {
                    icon.outerHTML = `<svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>`;
                }
            } else {
                container.className = container.className.replace(/border-(green|gray|yellow)-[0-9]+/g, 'border-red-300');
                container.className = container.className.replace(/bg-(green|gray|yellow)-[0-9]+/g, 'bg-red-50');
                badge.textContent = '✗ Not Present';
                badge.className = 'text-xs font-bold px-3 py-1.5 rounded-full bg-red-200 text-red-800';
                
                if (icon) {
                    icon.outerHTML = `<svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>`;
                }
            }
            
            updateSummaryCounts(attachmentType);
            showMessage(`Attachment marked as ${isPresent ? 'Present' : 'Not Present'}`, 'success');
        } else {
            // Revert UI on error
            badge.textContent = originalText;
            badge.className = originalBg;
            showMessage(data.message || 'Failed to update attachment', 'error');
        }
    } catch (error) {
        console.error('Error updating attachment:', error);
        badge.textContent = originalText;
        badge.className = originalBg;
        showMessage('Failed to update attachment. Please try again.', 'error');
    }
}

function updateSummaryCounts(programType) {
    const containers = {
        'gip': document.querySelector('[data-program-type="gip"]'),
        'tupad': document.querySelector('[data-program-type="tupad"]'),
        'tev': document.querySelector('[data-program-type="tev"]')
    };
    
    const container = containers[programType]?.closest('.bg-gradient-to-r');
    if (!container) return;
    
    const inputs = container.querySelectorAll('input[type="hidden"]');
    let total = inputs.length;
    let present = 0;
    
    inputs.forEach(input => {
        if (input.value === 'true') present++;
    });
    
    const missing = total - present;
    
    // Update summary cards if they exist
    const cards = container.querySelectorAll('.grid.grid-cols-4 > div');
    if (cards[0]) cards[0].querySelector('.text-2xl').textContent = total;
    if (cards[1]) cards[1].querySelector('.text-2xl').textContent = present;
    if (cards[2]) cards[2].querySelector('.text-2xl').textContent = missing;
}

// ========================================
// BULK ACTIONS
// ========================================
function markAllGIPAs(status) {
    markAllAttachments('gip', status === 'present');
}

function markAllTUPADAs(status) {
    markAllAttachments('tupad', status === 'present');
}

function markAllTEVAs(status) {
    markAllAttachments('tev', status === 'present');
}

async function markAllAttachments(programType, isPresent) {
    const buttons = document.querySelectorAll(`.${isPresent ? 'present-btn' : 'not-present-btn'}[data-attachment-type="${programType}"]`);
    
    if (buttons.length === 0) {
        showMessage(`No ${programType.toUpperCase()} attachments found`, 'info');
        return;
    }
    
    showMessage(`Updating ${buttons.length} attachments...`, 'info');
    
    // Process sequentially to avoid overwhelming the server
    for (const btn of buttons) {
        const attachmentId = btn.dataset.attachmentId;
        const attachmentType = btn.dataset.attachmentType;
        await markAttachment(attachmentId, attachmentType, isPresent);
        await new Promise(resolve => setTimeout(resolve, 300)); // Small delay between requests
    }
    
    showMessage(`All ${programType.toUpperCase()} attachments marked as ${isPresent ? 'Present' : 'Not Present'}`, 'success');
}

// ========================================
// REJECT MODAL FUNCTIONS (Keep as is)
// ========================================
let currentRejectId = null;

function initializeRejectModal() {
    const rejectForm = document.getElementById('rejectForm');
    const rejectModal = document.getElementById('rejectModal');
    
    if (rejectForm) {
        rejectForm.addEventListener('submit', handleRejectSubmit);
    }
    
    if (rejectModal) {
        rejectModal.addEventListener('click', function(e) {
            if (e.target === this) closeRejectModal();
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && rejectModal && !rejectModal.classList.contains('hidden')) {
            closeRejectModal();
        }
    });
}

function openRejectModal(dvRequestId) {
    currentRejectId = dvRequestId;
    const modal = document.getElementById('rejectModal');
    modal.classList.remove('hidden');
    document.getElementById('rejectRemarks').value = '';
    document.getElementById('remarksError').classList.add('hidden');
    document.body.style.overflow = 'hidden';
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectRemarks').value = '';
    document.getElementById('remarksError').classList.add('hidden');
    currentRejectId = null;
    document.body.style.overflow = 'auto';
}

async function handleRejectSubmit(e) {
    e.preventDefault();
    
    const remarks = document.getElementById('rejectRemarks').value.trim();
    const remarksError = document.getElementById('remarksError');
    
    if (!remarks) {
        remarksError.classList.remove('hidden');
        return;
    }
    
    remarksError.classList.add('hidden');
    
    const confirmBtn = document.getElementById('confirmRejectBtn');
    const btnText = document.getElementById('rejectBtnText');
    const spinner = document.getElementById('rejectSpinner');
    
    confirmBtn.disabled = true;
    btnText.textContent = 'Rejecting...';
    spinner.classList.remove('hidden');
    
    try {
        const response = await fetch(`/Outgoing/dvrequests/${currentRejectId}/reject`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ remarks })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message || 'DV request rejected successfully.', 'success');
            closeRejectModal();
            setTimeout(() => window.location.href = '/Outgoing/dvrequests', 1500);
        } else {
            showMessage(data.message || 'Failed to reject request.', 'error');
            confirmBtn.disabled = false;
            btnText.textContent = 'Reject Request';
            spinner.classList.add('hidden');
        }
    } catch (error) {
        console.error('Reject error:', error);
        showMessage('An error occurred while rejecting the request.', 'error');
        confirmBtn.disabled = false;
        btnText.textContent = 'Reject Request';
        spinner.classList.add('hidden');
    }
}

// ========================================
// RECEIVE ACTION (Keep as is)
// ========================================
async function handleReceiveAction(dvRequestId, buttonElement) {
    const spinner = buttonElement.querySelector('#loading-spinner');
    const buttonText = buttonElement.querySelector('.button-text');
    
    buttonElement.disabled = true;
    buttonText.textContent = 'Receiving...';
    spinner.classList.remove('hidden');
    
    try {
        const response = await fetch(`/Outgoing/dvrequests/${dvRequestId}/receive`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: new FormData()
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showMessage(data.message, 'success');
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

// ========================================
// LOCK MANAGEMENT (Keep as is)
// ========================================
function initializeLockManagement() {
    const dvRequestId = "{{ $dvRequest->id }}";

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
                console.log("🔒 Lock refreshed");
            }
        })
        .catch(err => console.error("Refresh lock failed:", err));
    }, 120000);

    window.addEventListener("beforeunload", () => {
        navigator.sendBeacon(`/Outgoing/dvrequests/${dvRequestId}/release-lock`, 
            JSON.stringify({ _token: "{{ csrf_token() }}" }));
    });
}

function releaseLock(event, id) {
    event.preventDefault();
    fetch(`/Outgoing/dvrequests/${id}/release-lock`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        }
    }).then(() => {
        window.location.href = "{{ route('Outgoing.dvrequests.index') }}";
    }).catch(err => {
        console.error("Release lock failed:", err);
        window.location.href = "{{ route('Outgoing.dvrequests.index') }}";
    });
}

// ========================================
// TOAST MESSAGES (Keep as is)
// ========================================
function showMessage(message, type = 'success') {
    const container = document.getElementById('message-container');
    if (!container) return;
    
    const messageDiv = document.createElement('div');
    const bgColor = {
        'success': 'bg-green-500',
        'error': 'bg-red-500',
        'warning': 'bg-yellow-500',
        'info': 'bg-blue-500'
    }[type] || 'bg-gray-500';
    
    messageDiv.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg mb-4 transform transition-all duration-300 translate-x-full opacity-0`;
    messageDiv.textContent = message;
     
    container.appendChild(messageDiv);
    
    setTimeout(() => messageDiv.classList.remove('translate-x-full', 'opacity-0'), 100);
    
    setTimeout(() => {
        messageDiv.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => messageDiv.remove(), 300);
    }, 3000);
}
</script>