<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RANCA - Verification History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-received { background-color: #dbeafe; color: #1e40af; }
        .status-process { background-color: #fef3c7; color: #d97706; }
        .status-validate { background-color: #dcfce7; color: #16a34a; }
        .status-rejected { background-color: #fee2e2; color: #dc2626; }
        
        .status-ranca-received { background-color: #dbeafe; color: #1e40af; }
        .status-ranca-process { background-color: #fef3c7; color: #d97706; }
        .status-ranca-validated { background-color: #dcfce7; color: #16a34a; }
        .status-ranca-rejected { background-color: #fee2e2; color: #dc2626; }
        
        .pagination .active {
            background-color: #4f46e5;
            color: white;
        }
        
        .action-btn {
            transition: all 0.2s ease-in-out;
        }
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .detail-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 0.375rem;
            background-color: #f3f4f6;
            color: #374151;
            margin: 0.125rem;
        }

        .detail-section {
            border-left: 3px solid #e5e7eb;
            padding-left: 0.75rem;
            margin: 0.5rem 0;
        }

        /* Wider table styles */
        .wide-table {
            min-width: 1800px;
        }
        
        .wide-container {
            max-width: 95%;
        }
        
        .table-cell {
            padding: 0.75rem 1rem;
        }
        
        .details-column {
            min-width: 400px;
            max-width: 500px;
        }
        
        .remarks-column {
            min-width: 200px;
            max-width: 300px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="wide-container mx-auto py-8 px-4">
        <div class="bg-white shadow rounded-xl p-6">
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-6">
                <div class="mt-6">
                <a href="{{ route('Accounting.dvrequests.index') }}" 
                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition duration-200 inline-flex items-center">
                   <i class="fas fa-arrow-left mr-2"></i> Back to RANCA Requests
                </a>
            </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">📜 RANCA - Verification History</h1>
                    <p class="text-sm text-gray-600 mt-1">Showing your personal verification records with detailed DV information</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-700">RANCA Validator</p>
                        <p class="text-xs text-gray-500">{{ auth()->user()->name ?? 'User' }}</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span class="text-indigo-800 font-medium">{{ substr(auth()->user()->name ?? 'RA', 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <form method="GET" action="{{ route('Accounting.history') }}" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search DV #</label>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Search by DV Number..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Program</label>
                        <select name="program" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Programs</option>
                            <option value="GIP" {{ request('program') == 'GIP' ? 'selected' : '' }}>GIP</option>
                            <option value="TUPAD" {{ request('program') == 'TUPAD' ? 'selected' : '' }}>TUPAD</option>
                            <option value="TEV" {{ request('program') == 'TEV' ? 'selected' : '' }}>TEV</option>
                            <option value="CASH_FOR_WORK" {{ request('program') == 'CASH_FOR_WORK' ? 'selected' : '' }}>Cash for Work</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Verification Step</label>
                        <select name="step" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Steps</option>
                            <option value="receive" {{ request('step') == 'receive' ? 'selected' : '' }}>Receive</option>
                            <option value="process" {{ request('step') == 'process' ? 'selected' : '' }}>Process</option>
                            <option value="validate" {{ request('step') == 'validate' ? 'selected' : '' }}>Validate</option>
                            <option value="reject" {{ request('step') == 'reject' ? 'selected' : '' }}>Reject</option>
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
                    <a href="{{ route('Accounting.history') }}" 
                       class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition duration-200 flex items-center">
                        <i class="fas fa-times mr-2"></i> Clear Filters
                    </a>
                </div>
            </form>

           <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-blue-600 font-medium">Total Records</p>
                        <p class="text-2xl font-bold text-blue-800">{{ $verificationHistory->total() }}</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-check text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-purple-600 font-medium">Received</p>
                        <p class="text-2xl font-bold text-purple-800">
                            {{ $verificationHistory->where('verification_step', 'receive')->count() }}
                        </p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-inbox text-purple-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-yellow-600 font-medium">In Process</p>
                        <p class="text-2xl font-bold text-yellow-800">
                            {{ $verificationHistory->where('verification_step', 'process')->count() }}
                        </p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                        <i class="fas fa-sync-alt text-yellow-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-green-600 font-medium">Validated</p>
                        <p class="text-2xl font-bold text-green-800">
                            {{ $verificationHistory->where('verification_step', 'validate')->count() }}
                        </p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="fas fa-clipboard-check text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg border border-red-100">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-red-600 font-medium">Rejected</p>
                        <p class="text-2xl font-bold text-red-800">
                            {{ $verificationHistory->where('verification_step', 'reject')->count() }}
                        </p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>

            <!-- Table Section -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border wide-table">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="table-cell">DV Request ID</th>
                            <th class="table-cell">DV Number</th>
                            <th class="table-cell">ORS Number</th>
                            <th class="table-cell">Verification Step</th>
                            <th class="table-cell">Status Before</th>
                            <th class="table-cell">Status After</th>
                            <th class="table-cell">Program</th>
                            <th class="table-cell">Payee</th>
                            <th class="table-cell">Date & Time</th>
                            <th class="table-cell details-column">DV Details</th>
                            <th class="table-cell remarks-column">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($verificationHistory as $history)
                            <tr class="hover:bg-gray-50">
                                <!-- DV Request ID -->
                                <td class="table-cell font-medium text-gray-900">
                                    #{{ $history->dv_request_id }}
                                </td>
                                
                                <!-- DV Number -->
                                <td class="table-cell">
                                    <div class="font-semibold text-indigo-600">
                                        {{ $history->dvRequest->dv_number ?? 'N/A' }}
                                    </div>
                                </td>
                                
                                <!-- ORS Number -->
                                <td class="table-cell">
                                    @if($history->dvRequest->orsRequest)
                                        <div class="font-semibold text-green-600">
                                            {{ $history->dvRequest->orsRequest->ors_number }}
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                
                                <!-- Verification Step -->
                                <td class="table-cell">
                                    @php
                                        $stepColors = [
                                            'receive' => 'status-received',
                                            'process' => 'status-process',
                                            'validate' => 'status-validate',
                                            'reject' => 'status-rejected',
                                        ];
                                        $color = $stepColors[$history->verification_step] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $color }}">
                                        {{ ucfirst($history->verification_step) }}
                                    </span>
                                </td>
                                
                                <!-- Status Before -->
                                <td class="table-cell">
                                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                                        {{ ucwords(str_replace('_', ' ', $history->status_before ?? 'N/A')) }}
                                    </span>
                                </td>
                                
                                <!-- Status After -->
                                <td class="table-cell">
                                    @php
                                        $statusColors = [
                                            'RANCA_received' => 'status-ranca-received',
                                            'RANCA_in_process' => 'status-ranca-process',
                                            'RANCA_validated' => 'status-ranca-validated',
                                            'RANCA_rejected' => 'status-ranca-rejected',
                                        ];
                                        $statusColor = $statusColors[$history->status_after] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColor }}">
                                        {{ ucwords(str_replace('_',' ', $history->status_after ?? 'N/A')) }}
                                    </span>
                                </td>
                                
                                <!-- Program -->
                                <td class="table-cell">
                                    {{ $history->dvRequest->program->prcode ?? 'N/A' }}
                                </td>
                                
                                <!-- Payee -->
                                <td class="table-cell">
                                    @if($history->dvRequest && $history->dvRequest->focalPerson)
                                        <div class="font-medium">
                                            {{ $history->dvRequest->focalPerson->fname ?? '' }} 
                                            {{ $history->dvRequest->focalPerson->lname ?? '' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $history->dvRequest->focalPerson->position ?? '' }}
                                        </div>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                
                                <!-- Date & Time -->
                                <td class="table-cell text-gray-600">
                                    <div class="font-medium">{{ $history->verified_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $history->verified_at->format('h:i A') }}</div>
                                </td>
                                
                                <!-- DV Details - Enhanced Section -->
                                <td class="table-cell details-column">
                                    @if($history->dvRequest)
                                        <div class="text-xs space-y-2">
                                            <!-- Basic DV Information -->
                                            <div class="detail-section">
                                                <div class="font-semibold text-gray-700 mb-1">
                                                    <i class="fas fa-file-invoice text-blue-500 mr-1"></i> DV Information
                                                </div>
                                                <div class="space-y-1">
                                                    @if($history->dvRequest->dv_number)
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">DV Number:</span>
                                                            <span class="font-medium">{{ $history->dvRequest->dv_number }}</span>
                                                        </div>
                                                    @endif
                                                    @if($history->dvRequest->amount)
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Amount:</span>
                                                            <span class="font-bold text-green-600">₱{{ number_format($history->dvRequest->amount, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if($history->dvRequest->accounting_control_number)
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Control #:</span>
                                                            <span class="font-medium">{{ $history->dvRequest->accounting_control_number }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- ORS Information -->
                                            @if($history->dvRequest->orsRequest)
                                                <div class="detail-section">
                                                    <div class="font-semibold text-gray-700 mb-1">
                                                        <i class="fas fa-receipt text-green-500 mr-1"></i> ORS Details
                                                    </div>
                                                    <div class="space-y-1">
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">ORS Number:</span>
                                                            <span class="font-medium">{{ $history->dvRequest->orsRequest->ors_number }}</span>
                                                        </div>
                                                        @if($history->dvRequest->orsRequest->amount)
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-600">ORS Amount:</span>
                                                                <span class="font-bold">₱{{ number_format($history->dvRequest->orsRequest->amount, 2) }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- UACS Codes -->
                                            @if($history->dvRequest->orsRequest && $history->dvRequest->orsRequest->uacsCodes->isNotEmpty())
                                                <div class="detail-section">
                                                    <div class="font-semibold text-gray-700 mb-1">
                                                        <i class="fas fa-list-ol text-purple-500 mr-1"></i> UACS Codes ({{ $history->dvRequest->orsRequest->uacsCodes->count() }})
                                                    </div>
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($history->dvRequest->orsRequest->uacsCodes->take(3) as $uacs)
                                                            <span class="detail-badge bg-purple-50 text-purple-700 border border-purple-200">
                                                                {{ $uacs->uacscode }} {{$uacs->description}}
                                                            </span>
                                                        @endforeach
                                                        @if($history->dvRequest->orsRequest->uacsCodes->count() > 3)
                                                            <span class="detail-badge bg-purple-100 text-purple-800">+{{ $history->dvRequest->orsRequest->uacsCodes->count() - 3 }} more</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                              <!-- UACS Codes -->
                                            @if($history->dvRequest->orsRequest && $history->dvRequest->orsRequest->adlCodes->isNotEmpty())
                                                <div class="detail-section">
                                                    <div class="font-semibold text-gray-700 mb-1">
                                                        <i class="fas fa-list-ol text-blue-500 mr-1"></i> UACS Codes ({{ $history->dvRequest->orsRequest->adlCodes->count() }})
                                                    </div>
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($history->dvRequest->orsRequest->adlCodes->take(3) as $adl)
                                                            <span class="detail-badge bg-blue-50 text-blue-700 border border-blue-200">
                                                                {{ $uacs->adlcode }} {{$uacs->description}}
                                                            </span>
                                                        @endforeach
                                                        @if($history->dvRequest->orsRequest->uacsCodes->count() > 3)
                                                            <span class="detail-badge bg-blue-100 text-blue-800">+{{ $history->dvRequest->orsRequest->uacsCodes->count() - 3 }} more</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif


                                            <!-- Verification Data -->
                                            @if($history->verification_data)
                                                <div class="detail-section">
                                                    <div class="font-semibold text-gray-700 mb-1">
                                                        <i class="fas fa-clipboard-check text-orange-500 mr-1"></i> Verification Data
                                                    </div>
                                                    <div class="space-y-1">
                                                        @if(isset($history->verification_data['qr_status']))
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-600">QR Status:</span>
                                                                <span class="{{ $history->verification_data['qr_status'] == 'valid' ? 'text-green-600 font-medium' : 'text-red-600 font-medium' }}">
                                                                    {{ ucfirst($history->verification_data['qr_status']) }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(isset($history->verification_data['amount_update']))
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-600">Amount Updated:</span>
                                                                <span class="font-medium text-blue-600">Yes</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($history->verification_data)
                                        <div class="text-xs text-gray-600 space-y-2">
                                            @if(isset($history->verification_data['dv_number']))
                                                <div class="font-semibold">DV #: {{ $history->verification_data['dv_number'] }}</div>
                                            @endif
                                            @if(isset($history->verification_data['amount']))
                                                <div class="font-bold">Amount: ₱{{ number_format($history->verification_data['amount'], 2) }}</div>
                                            @endif
                                            @if(isset($history->verification_data['qr_status']))
                                                <div>
                                                    <strong>QR Status:</strong> 
                                                    <span class="{{ $history->verification_data['qr_status'] == 'valid' ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ ucfirst($history->verification_data['qr_status']) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">No DV data available</span>
                                    @endif
                                </td>
                                
                                <!-- Remarks -->
                                <td class="table-cell remarks-column text-gray-700">
                                    <div class="max-h-24 overflow-y-auto text-xs">
                                        {{ $history->remarks ?? '—' }}
                                    </div>
                                </td>
                                
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="table-cell py-6 text-center text-gray-500">
                                    @if(request()->hasAny(['search', 'program', 'step', 'date_from', 'date_to']))
                                        No verification history found for the applied filters.
                                    @else
                                        No verification history found.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($verificationHistory->hasPages())
                <div class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium">{{ $verificationHistory->firstItem() }}</span> to 
                        <span class="font-medium">{{ $verificationHistory->lastItem() }}</span> of 
                        <span class="font-medium">{{ $verificationHistory->total() }}</span> results
                    </div>
                    <div class="flex space-x-2">
                        {{ $verificationHistory->links() }}
                    </div>
                </div>
            @endif

            <!-- Back Button -->
            <div class="mt-6">
                <a href="{{ route('Accounting.dvrequests.index') }}" 
                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition duration-200 inline-flex items-center">
                   <i class="fas fa-arrow-left mr-2"></i> Back to RANCA Requests
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-set date range to last 30 days if no dates are selected
            const dateFrom = document.querySelector('input[name="date_from"]');
            const dateTo = document.querySelector('input[name="date_to"]');
            
            if (!dateFrom.value && !dateTo.value) {
                const today = new Date();
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(today.getDate() - 30);
                
                dateFrom.valueAsDate = thirtyDaysAgo;
                dateTo.valueAsDate = today;
            }
            
            // Add loading state to form submission
            const filterForm = document.querySelector('form');
            filterForm.addEventListener('submit', function() {
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Applying Filters...';
                submitButton.disabled = true;
            });
        });
    </script>
</body>
</html>