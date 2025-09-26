@extends('layouts.app')

@section('title', 'DV Request Logs')

@section('content')
@php
    $steps = ['Submitted', 'IMSD', 'Incoming', 'Budget Officer', 'Outgoing','Accountant III','Regional Director','Cashier', 'Completed'];
    $lastStep = $logs->last()?->action ?? 'Submitted';

@endphp


    <div class="mb-8">
    <!-- Progress Stepper -->
    <div x-data="stepper()" class="flex items-center justify-between w-full">
        <template x-for="(step, index) in steps" :key="index">
            <div class="flex-1 text-center relative">
                <!-- Circle -->
                <div 
                    class="w-10 h-10 mx-auto rounded-full flex items-center justify-center transition-all duration-500 ease-in-out"
                    :class="index <= steps.indexOf(currentStep) 
                        ? 'bg-blue-600 text-white scale-110 shadow-lg' 
                        : 'bg-gray-300 text-gray-600 scale-100'"
                >
                    <span x-text="index + 1"></span>
                </div>
                <!-- Step label -->
                <p class="mt-2 text-sm transition-colors duration-500"
                :class="index <= steps.indexOf(currentStep) ? 'text-blue-700 font-medium' : 'text-gray-500'">
                <span x-text="translateStep(step)"></span>
                </p>
                <!-- Connecting line -->
                <div x-show="index < steps.length - 1"
                     class="absolute top-4 right-0 w-full h-1 transform translate-x-1/2 -z-10 bg-gray-300 overflow-hidden">
                    <!-- Blue animated fill -->
                    <div class="h-1 bg-blue-600 origin-left transition-transform duration-700 ease-in-out"
                         :style="`transform: scaleX(${index < steps.indexOf(currentStep) ? 1 : 0});`">
                    </div>
                </div>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Requests
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Activity Timeline</h3>
                <span id="logCount" class="text-sm text-gray-500">{{ $logs->count() }} {{ Str::plural('entry', $logs->count()) }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200" id="logsTable">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">#</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actor</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="logsBody">
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
                <span class="text-sm text-gray-500">Total activity entries: <span id="totalEntries">{{ $logs->count() }}</span></span>
                <span class="text-xs text-gray-400">Last updated: {{ $logs->first()?->created_at->diffForHumans() ?? 'Never' }}</span>
            </div>
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
     function stepper() {
        return {
            steps: @json($steps),
            currentStep: 'Submitted',
            lastStep: '{{ $lastStep }}',
            nextStep: 'Submitted',
              translateStep(step) {
            // Convert any IMSD-* variant to Regional Office
            if (step.startsWith('IMSD')) {
                return 'Regional Office';
            }
            return step;
        },
            init() {
                this.currentStep = this.lastStep;
                this.nextStep = this.lastStep;

                setInterval(() => {
                    fetch('{{ route('FocalPerson.dvrequest.latestStep', $dvRequest) }}')
                        .then(res => res.json())
                        .then(data => {
                            this.lastStep = data.lastStep;

                            let targetIndex = this.steps.indexOf(this.lastStep);
                            let currentIndex = this.steps.indexOf(this.currentStep);

                            if (targetIndex > currentIndex) {
                                // Start step-by-step animation
                                this.advanceStep(currentIndex, targetIndex);
                            }
                        });
                }, 1750);
            },
            advanceStep(currentIndex, targetIndex) {
                if (currentIndex < targetIndex) {
                    // Fill line up to the *next* step first
                    this.nextStep = this.steps[currentIndex + 1];

                    setTimeout(() => {
                        // Then highlight the circle
                        this.currentStep = this.steps[currentIndex + 1];
                        this.advanceStep(currentIndex + 1, targetIndex);
                    }, 250); // delay per step
                }
            }
        }
    }
</script>
<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script>
    const pusher = new Pusher("{{ config('broadcasting.connections.pusher.key') }}", {
        cluster: "{{ config('broadcasting.connections.pusher.options.cluster') }}",
        encrypted: true,
    });

    const channel = pusher.subscribe("dv-requests-{{ auth()->id() }}");

    channel.bind("status-updated", function(data) {
        console.log('Received log update:', data);
        
        showImmediateFeedback(data);
        
        setTimeout(() => {
            refreshLogTable();
        }, 2000);
    });

    // Function to show immediate feedback
    function showImmediateFeedback(data) {
        const tbody = document.getElementById("logsBody");
        const newRow = document.createElement("tr");
        newRow.className = "hover:bg-gray-50 transition-colors duration-150 bg-blue-50"; // Highlight new entry
        newRow.id = "temp-row"; 

        const actorName = data.actor_name || 'Processing...';
        const actorRole = data.actor_role || data.action || 'System Action';
        const actorAvatar = data.actor_avatar || '{{ asset("storage/profiles/default.png") }}';
        
        newRow.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-800 rounded-full text-xs font-bold">
                    ${tbody.children.length + 1}
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full overflow-hidden">
                        <img src="${actorAvatar}" 
                             class="w-10 h-10 object-cover"
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
                    : `<span class="text-gray-400 italic">Processing...</span>`
                }
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 font-medium">${data.timestamp || 'Just now'}</div>
                <div class="text-xs text-gray-500">Processing...</div>
            </td>
        `;

        tbody.prepend(newRow);
        
        // Update counts temporarily
        const newCount = tbody.children.length;
        document.getElementById("logCount").textContent = `${newCount} ${newCount === 1 ? "entry" : "entries"}`;
        document.getElementById("totalEntries").textContent = newCount;
    }

    // Function to refresh the entire log table with accurate data
    async function refreshLogTable() {
        try {
            const response = await fetch(`{{ route('FocalPerson.dvrequest.logs', $dvRequest) }}?ajax=1`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Failed to fetch logs');
            
            const data = await response.json();
            
            // Remove temporary row if it exists
            const tempRow = document.getElementById("temp-row");
            if (tempRow) {
                tempRow.remove();
            }
            
            // Update the table body with fresh data
            const tbody = document.getElementById("logsBody");
            tbody.innerHTML = data.html;
            
            // Update counts with accurate data
            document.getElementById("logCount").textContent = `${data.count} ${data.count === 1 ? "entry" : "entries"}`;
            document.getElementById("totalEntries").textContent = data.count;
            
            // Update last updated timestamp
            const lastUpdatedSpan = document.querySelector('.text-xs.text-gray-400');
            if (lastUpdatedSpan && data.lastUpdated) {
                lastUpdatedSpan.textContent = `Last updated: ${data.lastUpdated}`;
            }
            
            console.log('Logs refreshed successfully');
            
        } catch (error) {
            console.error('Failed to refresh logs:', error);
            
            // Remove temp row on error
            const tempRow = document.getElementById("temp-row");
            if (tempRow) {
                tempRow.remove();
            }
        }
    }
</script>
@endsection