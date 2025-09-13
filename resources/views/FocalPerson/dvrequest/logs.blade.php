@extends('layouts.app')

@section('title', 'DV Request Logs')

@section('content')
@php
    $steps = ['Submitted', 'IMSD', 'Incoming', 'Budget Officer', 'Outgoing', 'Completed'];
    $lastStep = $logs->last()?->action ?? 'Submitted';
@endphp


<div class="mb-8">
    <!-- Progress Stepper -->
    <div x-data="stepper()" class="flex items-center justify-between w-full">
        <template x-for="(step, index) in steps" :key="index">
            <div class="flex-1 text-center relative">
                <!-- Circle -->
                <div :class="index <= steps.indexOf(lastStep) ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'"
                     class="w-10 h-10 mx-auto rounded-full flex items-center justify-center">
                    <span x-text="index + 1"></span>
                </div>
                <!-- Step label -->
                <p class="mt-2 text-sm" x-text="step.replace('_', ' ')"></p>
                <!-- Connecting line -->
                <div x-show="index < steps.length - 1"
                     :class="index < steps.indexOf(lastStep) ? 'bg-blue-600' : 'bg-gray-300'"
                     class="absolute top-4 right-0 w-full h-1 transform translate-x-1/2 -z-10"></div>
            </div>
        </template>
    </div>
</div>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Requests
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($logs->isEmpty())
                <div class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No logs available</h3>
                    <p class="text-gray-500 mb-6">There are no activity logs for this request yet.</p>
                    
                </div>
            @else
            
                <!-- Logs Table -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Activity Timeline</h3>
                    <span class="text-sm text-gray-500">{{ $logs->count() }} {{ Str::plural('entry', $logs->count()) }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">#</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actor</th>
                          
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($logs as $log)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <!-- Step Number -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-800 rounded-full text-xs font-bold">
                                    {{ $loop->iteration }}
                                </div>
                            </td>

                            <!-- Actor -->
                        <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full overflow-hidden">
                                @if($log->focalPerson)
                                    <img src="{{ $log->focalPerson->profile_image 
                                        ? asset('storage/profiles/' . $log->focalPerson->profile_image) 
                                        : asset('storage/profiles/default.png') }}" 
                                        alt="Focal Person" class="w-10 h-10 object-cover">
                                @elseif($log->accountingUnit)
                                    <img src="{{ $log->accountingUnit->profile_image 
                                        ? asset('storage/profiles/' . $log->accountingUnit->profile_image) 
                                        : asset('storage/profiles/default.png') }}" 
                                        alt="Accounting Unit" class="w-10 h-10 object-cover">
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
                                             @if($log->action == 'submitted')
                                                   {{ trim($log->dvRequest->focalPerson->fname . ' ' . $log->dvRequest->focalPerson->mi . ' ' . $log->dvRequest->focalPerson->lname) }}
                                                @else
                                                   {{ optional($log->dvRequest->accountingUnit)->fname }} {{ optional($log->dvRequest->accountingUnit)->lname }}
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                @if($log->action === 'submitted')
                                                    Focal Person
                                                @else
                                                    Accounting Unit
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                    </td>


                            <!-- Remarks -->
                            <td class="px-6 py-4">
                                @if($log->remarks)
                                    <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-blue-400">
                                        <p class="text-gray-700">{{ ucfirst($log->remarks) }}</p>
                                    </div>
                                @else
                                    <span class="text-gray-400 italic">No remarks</span>
                                @endif
                            </td>

                            <!-- Date & Time -->
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

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-between">
                    <span class="text-sm text-gray-500">Total activity entries: {{ $logs->count() }}</span>
                    <span class="text-xs text-gray-400">Last updated: {{ $logs->first()?->created_at->diffForHumans() ?? 'Never' }}</span>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function stepper() {
        return {
            steps: @json($steps),
            lastStep: '{{ $lastStep }}',
            init() {
                setInterval(() => {
                fetch('{{ route('FocalPerson.dvrequest.latestStep', $dvRequest) }}')
                .then(res => res.json())
                .then(data => this.lastStep = data.lastStep);
                }, 5000);
            }
        }
    }
</script>

@endsection