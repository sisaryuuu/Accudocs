@extends('layouts.app')

@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regional Director - Approval History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-received { background-color: #dbeafe; color: #1e40af; }
        .status-signed { background-color: #dcfce7; color: #16a34a; }
        
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
                    <h1 class="text-2xl font-bold text-gray-900">📜 Regional Director - Approval History</h1>
                    <p class="text-sm text-gray-600 mt-1">Showing your DV document approval records</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-700">Regional Director</p>
                        <p class="text-xs text-gray-500">{{ auth()->user()->name ?? 'User' }}</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span class="text-indigo-800 font-medium">{{ substr(auth()->user()->name ?? 'RD', 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <form method="GET" action="{{ route('regional.director.history') }}" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search DV Number</label>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Search by DV Number..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Approval Step</label>
                        <select name="step" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Steps</option>
                            <option value="received" {{ request('step') == 'received' ? 'selected' : '' }}>Received</option>
                            <option value="signed_approved" {{ request('step') == 'signed_approved' ? 'selected' : '' }}>Signed & Approved</option>
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
                    <a href="{{ route('regional.director.history') }}" 
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
                            <p class="text-sm text-blue-600 font-medium">Total Approvals</p>
                            <p class="text-2xl font-bold text-blue-800">{{ $approvalHistory->total() }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-file-alt text-blue-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-green-600 font-medium">Signed & Approved</p>
                            <p class="text-2xl font-bold text-green-800">
                                {{ $approvalHistory->where('approval_step', 'signed_approved')->count() }}
                            </p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-indigo-600 font-medium">Received</p>
                            <p class="text-2xl font-bold text-indigo-800">
                                {{ $approvalHistory->where('approval_step', 'received')->count() }}
                            </p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-inbox text-indigo-600"></i>
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
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Action Step</th>
                            <th class="px-4 py-3">Status Before</th>
                            <th class="px-4 py-3">Current Status</th>
                            <th class="px-4 py-3">Remarks</th>
                            <th class="px-4 py-3">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($approvalHistory as $history)
                            <tr class="hover:bg-gray-50">
                                <!-- Date & Time -->
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $history->approved_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}
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
                                
                                <!-- Amount -->
                                <td class="px-4 py-3 font-semibold">
                                    ₱{{ number_format($history->dvRequest->amount, 2) }}
                                </td>
                                
                                <!-- Action Step -->
                                <td class="px-4 py-3">
                                    @if($history->approval_step == 'received')
                                        <span class="px-2 py-1 text-xs font-medium rounded-full status-received">
                                            <i class="fas fa-inbox mr-1"></i> Received
                                        </span>
                                    @elseif($history->approval_step == 'signed_approved')
                                        <span class="px-2 py-1 text-xs font-medium rounded-full status-signed">
                                            <i class="fas fa-check-circle mr-1"></i> Signed & Approved
                                        </span>
                                    @endif
                                </td>
                                
                                <!-- Status Change -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center text-xs">
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded">
                                            {{ $history->status_before }}
                                        </span>
                                        <i class="fas fa-arrow-right mx-2 text-gray-400"></i>
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
                                <td colspan="10" class="px-4 py-6 text-center text-gray-500">
                                    @if(request()->hasAny(['search', 'step', 'program', 'date_from', 'date_to']))
                                        No approval history found for the applied filters.
                                    @else
                                        No approval history found.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($approvalHistory->hasPages())
                <div class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium">{{ $approvalHistory->firstItem() }}</span> to 
                        <span class="font-medium">{{ $approvalHistory->lastItem() }}</span> of 
                        <span class="font-medium">{{ $approvalHistory->total() }}</span> results
                    </div>
                    <div class="flex space-x-2">
                        {{ $approvalHistory->links() }}
                    </div>
                </div>
            @endif

            <!-- Back Button -->
            <div class="mt-6">
                <a href="{{ route('regional.director.dashboard') }}" 
                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition duration-200 inline-flex items-center">
                   <i class="fas fa-arrow-left mr-2"></i> Back to Regional Director Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Details Modals -->
    @foreach($approvalHistory as $history)
    <div id="detailsModal{{ $history->id }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Approval Details</h3>
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
                            <p class="text-sm font-medium text-gray-500">Amount</p>
                            <p class="text-base font-semibold">₱{{ number_format($history->dvRequest->amount, 2) }}</p>
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
                            <p class="text-sm font-medium text-gray-500">Approved At</p>
                            <p class="text-base">{{ $history->approved_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Approval Step</p>
                            <p class="text-base">
                                @if($history->approval_step == 'received')
                                    <span class="px-2 py-1 text-xs font-medium rounded-full status-received">
                                        Received
                                    </span>
                                @elseif($history->approval_step == 'signed_approved')
                                    <span class="px-2 py-1 text-xs font-medium rounded-full status-signed">
                                        Signed & Approved
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

                    @if($history->approval_data)
                        <div>
                            <p class="text-sm font-medium text-gray-500">Additional Information</p>
                            <div class="mt-1 bg-gray-50 p-3 rounded-lg text-sm">
                                @if(isset($history->approval_data['processing_type']))
                                    <p><strong>Processing Type:</strong> 
                                        {{ ucwords(str_replace('_', ' ', $history->approval_data['processing_type'])) }}
                                    </p>
                                @endif
                                @if(isset($history->approval_data['qr_token']))
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