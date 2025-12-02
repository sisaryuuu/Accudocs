@extends('layouts.app')

@section('title', 'Admin Document Tracking')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Document Tracking</h1>
                <p class="mt-2 text-sm text-gray-600">Monitor all DV requests across the system</p>
            </div>
        </div>
    
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Requests</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalRequests }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Completed</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $completedRequests }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">In Progress</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $inProgressRequests }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Rejected</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $rejectedRequests }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <form method="GET" action="{{ route('admin.document-tracking') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Reference, Payee, Particulars...">
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="IMSD_received" {{ request('status') == 'IMSD_received' ? 'selected' : '' }}>IMSD Received</option>
                        <option value="IMSD_verified" {{ request('status') == 'IMSD_verified' ? 'selected' : '' }}>IMSD Verified</option>
                         <option value="Incoming-TUPAD_received" {{ request('status') == 'Incoming-TUPAD_received' ? 'selected' : '' }}>Incoming TUPAD</option>
                          <option value="Incoming-GIP_received" {{ request('status') == 'Incoming-GIP_received' ? 'selected' : '' }}>Incoming GIP</option>
                        <option value="Incoming-TEV_received" {{ request('status') == 'Incoming-TEV_received' ? 'selected' : '' }}>Incoming TEV</option>
                        <option value="ORS_received" {{ request('status') == 'ORS_received' ? 'selected' : '' }}>ORS Received</option>
                        <option value="Outgoing_received" {{ request('status') == 'Outgoing_received' ? 'selected' : '' }}>Outgoing</option>
                        <option value="RANCA_received" {{ request('status') == 'RANCA_received' ? 'selected' : '' }}>RANCA</option>
                        <option value="Regional_Director_received" {{ request('status') == 'Regional_Director_received' ? 'selected' : '' }}>Regional Director</option>
                        <option value="Cashier_received" {{ request('status') == 'Cashier_received' ? 'selected' : '' }}>Cashier</option>
                        <option value="Cashier_released" {{ request('status') == 'Cashier_released' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Date To -->
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Action Buttons -->
                <div class="md:col-span-4 flex gap-3 pt-2">
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.document-tracking') }}" 
                       class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Requests Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                   @if($dvRequests->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $dvRequests->links() }}
            </div>
            @endif
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900">All DV Requests</h3> <!-- Pagination -->
            </div>
            

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Particulars</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Submitted</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($dvRequests as $request)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $request->reference_number }}</div>
                            </td>
                            <td class="px-6 py-4">
                                 <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full overflow-hidden">
                                        <img src="{{ $request->focalPerson->profile_image ? asset('storage/profiles/' . $request->focalPerson->profile_image) : asset('storage/profiles/default.png') }}" 
                                             class="w-8 h-8 object-cover">
                                    </div>
                                <div class="text-sm font-medium text-gray-900">  {{ $request->focalPerson->fname }} {{ $request->focalPerson->lname }}</div>
                                
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                               
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">
                                           <div class="text-sm text-gray-500 truncate max-w-xs">{{ $request->particulars }}</div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    ₱{{ number_format($request->amount, 2) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'submitted' => 'bg-blue-100 text-blue-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'default' => 'bg-yellow-100 text-yellow-800'
                                    ];
                                    
                                    $color = $statusColors['default'];
                                    if (stripos($request->status, 'rejected') !== false) {
                                        $color = $statusColors['rejected'];
                                    } elseif ($request->status === 'Cashier_released') {
                                        $color = $statusColors['completed'];
                                    } elseif ($request->status === 'submitted') {
                                        $color = $statusColors['submitted'];
                                    }
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                                    {{ str_replace('_', ' ', $request->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $request->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $request->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.document-tracking.show', $request) }}" 
                                   class="text-blue-600 hover:text-blue-900 font-medium">
                                    Track Document
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No DV requests found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($dvRequests->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $dvRequests->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection