@extends('layouts.app')

@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier - Transaction History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-received { background-color: #dbeafe; color: #1e40af; }
        .status-processed { background-color: #fef3c7; color: #d97706; }
        .status-completed { background-color: #dcfce7; color: #16a34a; }
        .status-rejected { background-color: #fee2e2; color: #dc2626; }
        
        .pagination .active {
            background-color: #4f46e5;
            color: white;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8fafc;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto py-8 px-4">
        <div class="bg-white shadow rounded-xl p-6">
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">💰 Cashier - Transaction History</h1>
                    <p class="text-sm text-gray-600 mt-1">Showing your cashier transaction records</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-700">Cashier</p>
                        <p class="text-xs text-gray-500">{{ auth()->user()->name ?? 'User' }}</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                        <span class="text-green-800 font-medium">{{ substr(auth()->user()->name ?? 'CA', 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <form method="GET" action="{{ route('cashier.history') }}" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search DV Number</label>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Search by DV Number..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Step</label>
                        <select name="step" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Steps</option>
                            <option value="received" {{ request('step') == 'received' ? 'selected' : '' }}>Received</option>
                            <option value="processed" {{ request('step') == 'processed' ? 'selected' : '' }}>Processed</option>
                            <option value="completed" {{ request('step') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="rejected" {{ request('step') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Program</label>
                        <select name="program" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Programs</option>
                            @foreach($programs ?? [] as $program)
                                <option value="{{ $program->prcode }}" {{ request('program') == $program->prcode ? 'selected' : '' }}>
                                    {{ $program->prcode }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition duration-200 flex items-center">
                        <i class="fas fa-filter mr-2"></i> Apply Filters
                    </button>
                    <a href="{{ route('cashier.history') }}" 
                       class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition duration-200 flex items-center">
                        <i class="fas fa-times mr-2"></i> Clear Filters
                    </a>
                </div>
            </form>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-blue-600 font-medium">Total Transactions</p>
                            <p class="text-2xl font-bold text-blue-800">{{ $releaseHistory->total() }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-receipt text-blue-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-green-600 font-medium">Released</p>
                            <p class="text-2xl font-bold text-green-800">
                                {{ $releaseHistory->where('release_step', 'completed')->count() }}
                            </p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-yellow-600 font-medium">In Process</p>
                            <p class="text-2xl font-bold text-yellow-800">
                                {{ $releaseHistory->where('transaction_step', 'processed')->count() }}
                            </p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                            <i class="fas fa-sync-alt text-yellow-600"></i>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Table Section -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Date & Time</th>
                            <th class="px-4 py-3">Control Number</th>
                            <th class="px-4 py-3">DV Number</th>
                            <th class="px-4 py-3">Payee</th>
                            <th class="px-4 py-3">Program</th>
                            <th class="px-4 py-3">Transaction Step</th>
                            <th class="px-4 py-3">Status Before</th>
                            <th class="px-4 py-3">Current Status</th>
                            <th class="px-4 py-3">Remarks</th>
                            <th class="px-4 py-3">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($releaseHistory as $history)
                            <tr class="hover:bg-gray-50">
                                <!-- Date & Time -->
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $history->released_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                                </td>
                                
                                <!-- Control Number -->
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    @if($history->dvRequest->accounting_control_number)
                                        CN #-{{ now()->year }}-{{ str_pad($history->dvRequest->accounting_control_number, 5, '0', STR_PAD_LEFT) }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                
                                <!-- DV Number -->
                                <td class="px-4 py-3 font-medium">
                                    {{ $history->dvRequest->dv_number ?? '—' }}
                                </td>
                                
                                <!-- Payee -->
                                <td class="px-4 py-3">
                                    {{ trim(($history->dvRequest->focalPerson->fname ?? '') . ' ' . ($history->dvRequest->focalPerson->lname ?? '')) }}
                                </td>
                                
                                <!-- Program -->
                                <td class="px-4 py-3">
                                    {{ $history->dvRequest->program->prcode ?? 'N/A' }}
                                </td>
                                
                                <!-- Transaction Step -->
                                <!-- Transaction Step (FIXED) -->
            <td class="px-4 py-3">
                @if($history->release_step == 'received')
                    <span class="px-2 py-1 text-xs font-medium rounded-full status-received">
                        <i class="fas fa-inbox mr-1"></i> Received
                    </span>
                @elseif($history->release_step == 'processed')
                    <span class="px-2 py-1 text-xs font-medium rounded-full status-processed">
                        <i class="fas fa-cog mr-1"></i> Processed
                    </span>
                @elseif($history->release_step == 'released')
                    <span class="px-2 py-1 text-xs font-medium rounded-full status-completed">
                        <i class="fas fa-check-circle mr-1"></i> Released
                    </span>
                @else
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                        {{ ucfirst($history->release_step) }}
                    </span>
                @endif
            </td>
                                <!-- Status Change -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center text-xs">
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded">
                                            {{ $history->status_before }}
                                        </span>
                                  
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded font-medium">
                                            {{ $history->status_after }}
                                        </span>
                                    </div>
                                </td>
                                  <td class="px-4 py-3">
                                    <div class="flex items-center text-xs">
                                     
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded font-medium">
                                            {{ $history->status_after }}
                                        </span>
                                    </div>
                                </td>
                                <!-- Remarks -->
                                <td class="px-4 py-3 text-gray-700 max-w-xs">
                                    {{ Str::limit($history->remarks, 50) ?: '—' }}
                                </td>
                                
                                <!-- Details -->
                                <td class="px-4 py-3">
                                    <button type="button" 
                                            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center"
                                            onclick="openDetailsModal({{ $history->id }})">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-gray-500">
                                    @if(request()->hasAny(['search', 'step', 'program', 'date_from', 'date_to']))
                                        No transaction history found for the applied filters.
                                    @else
                                        No transaction history found.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($releaseHistory->hasPages())
                <div class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium">{{ $releaseHistory->firstItem() }}</span> to 
                        <span class="font-medium">{{ $releaseHistory->lastItem() }}</span> of 
                        <span class="font-medium">{{ $releaseHistory->total() }}</span> results
                    </div>
                    <div class="flex space-x-2">
                        {{ $releaseHistory->links() }}
                    </div>
                </div>
            @endif

            <!-- Back Button -->
            <div class="mt-6">
                <a href="{{ route('cashier.dashboard') }}" 
                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition duration-200 inline-flex items-center">
                   <i class="fas fa-arrow-left mr-2"></i> Back to Cashier Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Details Modals -->
    @foreach($releaseHistory as $history)
    <div id="detailsModal{{ $history->id }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Transaction Details</h3>
                    <button onclick="closeDetailsModal({{ $history->id }})" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mt-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">DV Number</p>
                            <p class="text-base">{{ $history->dvRequest->dv_number ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Control Number</p>
                            <p class="text-base">
                                @if($history->dvRequest->accounting_control_number)
                                    CN #-{{ now()->year }}-{{ str_pad($history->dvRequest->accounting_control_number, 5, '0', STR_PAD_LEFT) }}
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Program</p>
                            <p class="text-base">{{ $history->dvRequest->program->prcode ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Payee</p>
                            <p class="text-base">{{ trim(($history->dvRequest->focalPerson->fname ?? '') . ' ' . ($history->dvRequest->focalPerson->lname ?? '')) }}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Processed At</p>
                            <p class="text-base">{{ $history->released_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Transaction Step</p>
                            <p class="text-base">
                                @if($history->transaction_step == 'received')
                                    <span class="px-2 py-1 text-xs font-medium rounded-full status-received">
                                        Received
                                    </span>
                                @elseif($history->transaction_step == 'processed')
                                    <span class="px-2 py-1 text-xs font-medium rounded-full status-processed">
                                        Processed
                                    </span>
                                @elseif($history->transaction_step == 'completed')
                                    <span class="px-2 py-1 text-xs font-medium rounded-full status-completed">
                                        Completed
                                    </span>
                                @elseif($history->transaction_step == 'rejected')
                                    <span class="px-2 py-1 text-xs font-medium rounded-full status-rejected">
                                        Rejected
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500">Status Change</p>
                        <div class="flex items-center mt-1">
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-sm">
                                {{ $history->status_before }}
                            </span>
                            <i class="fas fa-arrow-right mx-3 text-gray-400"></i>
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-lg text-sm font-medium">
                                {{ $history->status_after }}
                            </span>
                        </div>
                    </div>
                    
                    @if($history->remarks)
                        <div>
                            <p class="text-sm font-medium text-gray-500">Remarks</p>
                            <p class="mt-1 bg-gray-50 p-3 rounded-lg text-sm">{{ $history->remarks }}</p>
                        </div>
                    @endif

                    @if($history->transaction_data)
                        <div>
                            <p class="text-sm font-medium text-gray-500">Transaction Information</p>
                            <div class="mt-1 bg-gray-50 p-3 rounded-lg text-sm">
                                @if(isset($history->transaction_data['processing_type']))
                                    <p><strong>Processing Type:</strong> 
                                        {{ ucwords(str_replace('_', ' ', $history->transaction_data['processing_type'])) }}
                                    </p>
                                @endif
                                @if(isset($history->transaction_data['payment_method']))
                                    <p><strong>Payment Method:</strong> 
                                        {{ ucwords(str_replace('_', ' ', $history->transaction_data['payment_method'])) }}
                                    </p>
                                @endif
                                @if(isset($history->transaction_data['qr_token']))
                                    <p><strong>QR Scan:</strong> 
                                        <span class="text-green-600">Yes</span>
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button onclick="closeDetailsModal({{ $history->id }})" 
                            class="px-4 py-2 bg-gray-500 text-white text-sm rounded-lg hover:bg-gray-600 transition duration-200">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <script>
        // Modal functions
        function openDetailsModal(id) {
            document.getElementById('detailsModal' + id).classList.remove('hidden');
        }

        function closeDetailsModal(id) {
            document.getElementById('detailsModal' + id).classList.add('hidden');
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('fixed')) {
                event.target.classList.add('hidden');
            }
        });

        // Auto-set date range to last 30 days if no dates are selected
        document.addEventListener('DOMContentLoaded', function() {
            const dateFrom = document.querySelector('input[name="date_from"]');
            const dateTo = document.querySelector('input[name="date_to"]');
            
            if (!dateFrom.value && !dateTo.value) {
                const today = new Date();
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(today.getDate() - 30);
                
                dateFrom.value = thirtyDaysAgo.toISOString().split('T')[0];
                dateTo.value = today.toISOString().split('T')[0];
            }
            
            // Add loading state to form submission
            const filterForm = document.querySelector('form');
            filterForm.addEventListener('submit', function() {
                const submitButton = this.querySelector('button[type="submit"]');
                const originalText = submitButton.innerHTML;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Applying Filters...'; 
                submitButton.disabled = true;
                
                // Re-enable after 3 seconds in case of error
                setTimeout(() => {
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                }, 3000);
            });
        });
    </script>
</body>
</html>
@endsection