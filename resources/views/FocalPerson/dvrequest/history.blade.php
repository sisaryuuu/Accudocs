@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-gray-600 to-gray-800 px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Request History</h1>
                            <p class="text-gray-200 text-sm mt-1">View completed and rejected requests</p>
                        </div>
                    </div>
                    <a href="{{ route('FocalPerson.dvrequest.index') }}" 
                       class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-xl font-medium transition-all duration-200 flex items-center space-x-2 backdrop-blur-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Back to Active Requests</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-xl shadow-md mb-6 animate-fade-in">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-green-800 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-xl shadow-md mb-6 animate-fade-in">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-red-800 font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <!-- Search and Filter Section -->
        <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                <!-- Search Bar -->
                <div class="flex-1 max-w-2xl">
                    <div class="relative">
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search by control number, ORS number, particulars, amount, program..." 
                               class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-500 focus:border-transparent transition-all duration-200">
                        <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Stats -->
                <div class="flex items-center space-x-4">
                    <div class="bg-gray-50 px-6 py-3 rounded-xl border-2 border-gray-200">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="text-gray-600 font-medium">Total: <span id="totalCount" class="text-gray-800 font-bold">{{ $dvRequests->count() }}</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            @if($dvRequests->isEmpty())
                <div class="text-center py-16">
                    <svg class="w-24 h-24 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No History Found</h3>
                    <p class="text-gray-500">You don't have any completed or rejected requests yet.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Control #</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ORS #</th> <!-- NEW COLUMN -->
                                 <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">DV #</th> <!-- NEW COLUMN -->
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Program</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Particulars</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="requestsTableBody" class="divide-y divide-gray-200 bg-white">
                            @foreach($dvRequests as $request)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <!-- Control Number -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="bg-gray-100 px-3 py-1 rounded-lg">
                                                <span class="text-sm font-bold text-gray-700">
                                                    CN #-{{ now()->year }}-{{ str_pad($request->accounting_control_number, 4, '0', STR_PAD_LEFT) }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- ORS Number --> <!-- NEW COLUMN -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($request->orsRequest && $request->orsRequest->ors_number)
                                            <div class="bg-blue-50 px-3 py-1 rounded-lg">
                                                <span class="text-sm font-medium text-blue-700">
                                                    {{ $request->orsRequest->ors_number }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">No ORS</span>
                                        @endif
                                    </td>
                                     <td class="px-6 py-4 whitespace-nowrap">
                                      
                                    @if($request->dv_number)
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">
                                            {{ $request->dv_number }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded text-xs font-medium">
                                            No DV Number
                                        </span>
                                    @endif
                                    </td>

                                    <!-- Program -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $request->program->prcode ?? 'N/A' }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Particulars -->
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $request->particulars }}">
                                            {{ Str::limit($request->particulars, 40) }}
                                        </div>
                                    </td>

                                    <!-- Amount -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">
                                            ₱{{ number_format($request->amount, 2) }}
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $isRejected = stripos($request->status, 'rejected') !== false;
                                            $isCompleted = $request->status === 'completed';
                                        @endphp
                                        
                                        @if($isRejected)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                Rejected
                                            </span>
                                        @elseif($isCompleted)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Completed
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-800">
                                                {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Date -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $request->created_at->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $request->created_at->format('h:i A') }}</div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <!-- View Logs -->
                                            <a href="{{ route('FocalPerson.dvrequest.logs', $request->id) }}" 
                                               class="inline-flex items-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors duration-200"
                                               title="View Logs">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>

                                            <!-- Download PDF -->
                                            <a href="{{ route('FocalPerson.dvrequest.generatePDF', $request->id) }}" 
                                               class="inline-flex items-center px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors duration-200"
                                               title="Download PDF">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Search JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('requestsTableBody');
    const totalCount = document.getElementById('totalCount');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value);
            }, 300);
        });
    }

    function performSearch(query) {
        fetch(`{{ route('FocalPerson.dvrequest.history.search') }}?query=${encodeURIComponent(query)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            updateTable(data);
            totalCount.textContent = data.length;
        })
        .catch(error => {
            console.error('Search error:', error);
        });
    }

    function updateTable(requests) {
        if (requests.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center"> <!-- Changed colspan to 8 -->
                        <div class="flex flex-col items-center">
                            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <p class="text-gray-500 text-lg font-medium">No results found</p>
                            <p class="text-gray-400 text-sm mt-1">Try adjusting your search terms</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = requests.map(request => {
            const isRejected = request.status.toLowerCase().includes('rejected');
            const isCompleted = request.status === 'completed';
            
            let statusBadge;
            if (isRejected) {
                statusBadge = `
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Rejected
                    </span>
                `;
            } else if (isCompleted) {
                statusBadge = `
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Completed
                    </span>
                `;
            } else {
                statusBadge = `
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-800">
                        ${request.status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                    </span>
                `;
            }

            const createdDate = new Date(request.created_at);
            const formattedDate = createdDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const formattedTime = createdDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

            // ORS Number display
            const orsNumber = request.ors_request?.ors_number ? 
                `<div class="bg-blue-50 px-3 py-1 rounded-lg">
                    <span class="text-sm font-medium text-blue-700">${request.ors_request.ors_number}</span>
                </div>` : 
                `<span class="text-sm text-gray-400">No ORS</span>`;

            return `
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="bg-gray-100 px-3 py-1 rounded-lg">
                                <span class="text-sm font-bold text-gray-700">
                                    ${request.accounting_control_number || 'N/A'}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${orsNumber}
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            ${request.program?.prcode || 'N/A'}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 max-w-xs truncate" title="${request.particulars}">
                            ${request.particulars.substring(0, 40)}${request.particulars.length > 40 ? '...' : ''}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-900">
                            ₱${parseFloat(request.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${statusBadge}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">${formattedDate}</div>
                        <div class="text-xs text-gray-500">${formattedTime}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            <a href="/FocalPerson/dvrequest/${request.id}/logs" 
                               class="inline-flex items-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors duration-200"
                               title="View Logs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="/FocalPerson/dvrequest/${request.id}/pdf" 
                               class="inline-flex items-center px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors duration-200"
                               title="Download PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }
});
</script>

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>
@endsection
