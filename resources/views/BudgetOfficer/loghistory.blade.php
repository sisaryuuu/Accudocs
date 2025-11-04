<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Officer - Verification History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-received { background-color: #dbeafe; color: #1e40af; }
        .status-process { background-color: #fef3c7; color: #d97706; }
        .status-obligated { background-color: #dcfce7; color: #16a34a; }
        .status-onhold { background-color: #ffedd5; color: #ea580c; }
        .status-revision { background-color: #f3e8ff; color: #9333ea; }
        .status-rejected { background-color: #fee2e2; color: #dc2626; }
        
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
            min-width: 1600px;
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
            <div class="mb-6">
                <a href="{{ route('BudgetOfficer.dvrequests.index') }}" 
                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition duration-200 inline-flex items-center">
                   <i class="fas fa-arrow-left mr-2"></i> Back to Budget Officer Dashboard
                </a>
            </div>
            
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">📜 Budget Officer - Verification History</h1>
                    <p class="text-sm text-gray-600 mt-1">Showing your personal verification records with detailed ORS information</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-700">Budget Officer</p>
                        <p class="text-xs text-gray-500">{{ auth()->user()->name ?? 'User' }}</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span class="text-indigo-800 font-medium">{{ substr(auth()->user()->name ?? 'BO', 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <form method="GET" action="{{ route('BudgetOfficer.dvrequests.loghistory') }}" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-4">
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
                            @foreach($programs as $program)
                                <option value="{{ $program->prcode }}" {{ request('program') == $program->prcode ? 'selected' : '' }}>
                                    {{ $program->prcode }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Verification Step</label>
                        <select name="step" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Steps</option>
                            <option value="receive" {{ request('step') == 'receive' ? 'selected' : '' }}>Receive</option>
                            <option value="process" {{ request('step') == 'process' ? 'selected' : '' }}>Process</option>
                            <option value="obligate" {{ request('step') == 'obligate' ? 'selected' : '' }}>Obligate</option>
                            <option value="onhold" {{ request('step') == 'onhold' ? 'selected' : '' }}>On Hold</option>
                            <option value="revise" {{ request('step') == 'revise' ? 'selected' : '' }}>Revise</option>
                            <option value="reject" {{ request('step') == 'reject' ? 'selected' : '' }}>Reject</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ORS Number</label>
                        <input type="text" name="ors_search" value="{{ request('ors_search') }}" 
                               placeholder="Search by ORS Number..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
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
                    <a href="{{ route('BudgetOfficer.dvrequests.loghistory') }}" 
                       class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition duration-200 flex items-center">
                        <i class="fas fa-times mr-2"></i> Clear Filters
                    </a>
                </div>
            </form>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-blue-600 font-medium">Total Verified</p>
                            <p class="text-2xl font-bold text-blue-800">{{ $verificationHistory->total() }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-check text-blue-600"></i>
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
                            <p class="text-sm text-green-600 font-medium">Obligated</p>
                            <p class="text-2xl font-bold text-green-800">
                                {{ $verificationHistory->where('verification_step', 'obligate')->count() }}
                            </p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fas fa-file-invoice-dollar text-green-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg border border-orange-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-orange-600 font-medium">On Hold</p>
                            <p class="text-2xl font-bold text-orange-800">
                                {{ $verificationHistory->where('verification_step', 'onhold')->count() }}
                            </p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-orange-100 flex items-center justify-center">
                            <i class="fas fa-pause-circle text-orange-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-purple-600 font-medium">For Revision</p>
                            <p class="text-2xl font-bold text-purple-800">
                                {{ $verificationHistory->where('verification_step', 'revise')->count() }}
                            </p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                            <i class="fas fa-edit text-purple-600"></i>
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
                            <th class="table-cell">Control No.</th>
                            <th class="table-cell">ORS Number</th>
                            <th class="table-cell">Verification Step</th>
                            <th class="table-cell">Status Before</th>
                            <th class="table-cell">Status After</th>
                            <th class="table-cell">Program</th>
                            <th class="table-cell">Payee</th>
                            <th class="table-cell">Date & Time</th>
                            <th class="table-cell details-column">ORS Details</th>
                            <th class="table-cell remarks-column">Remarks</th>
                            <th class="table-cell">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($verificationHistory as $history)
                            <tr class="hover:bg-gray-50">
                                <!-- Control Number -->
                                <td class="table-cell font-medium text-gray-900">
                                    #{{ $history->dvRequest->accounting_control_number }}
                                </td>
                                
                                <!-- ORS Number -->
                                <td class="table-cell text-gray-700">
                                    @if($history->orsRequest && $history->orsRequest->ors_number)
                                        <div class="font-semibold text-indigo-600">{{ $history->orsRequest->ors_number }}</div>
                                    @elseif($history->verification_data && isset($history->verification_data['ors_number']))
                                        <div class="font-semibold text-indigo-600">{{ $history->verification_data['ors_number'] }}</div>
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
                                            'obligate' => 'status-obligated',
                                            'onhold' => 'status-onhold',
                                            'revise' => 'status-revision',
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
                                            'ORS_received' => 'status-received',
                                            'ORS_in_process' => 'status-process',
                                            'ORS_obligated' => 'status-obligated',
                                            'ORS_onhold' => 'status-onhold',
                                            'ORS_for_revision' => 'status-revision',
                                            'ORS_rejected' => 'status-rejected',
                                        ];
                                        $statusColor = $statusColors[$history->status_after] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColor }}">
                                         {{$history->status_after ?? 'N/A' }}
                                    </span>
                                </td>
                                
                                <!-- Program -->
                                <td class="table-cell">
                                    {{ $history->dvRequest->program->prcode ?? ($history->verification_data['program'] ?? 'N/A') }}
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
                                        {{ $history->verification_data['payee_name'] ?? 'N/A' }}
                                    @endif
                                </td>
                                
                                <!-- Date & Time -->
                                <td class="table-cell text-gray-600">
                                    <div class="font-medium">{{ $history->verified_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $history->verified_at->format('h:i A') }}</div>
                                </td>
                                
                                <!-- ORS Details - Enhanced Section -->
                                <td class="table-cell details-column">
                                    @if($history->orsRequest)
                                        <div class="text-xs space-y-2">
                                            <!-- UACS Codes -->
                                            @if($history->orsRequest->uacsCodes && $history->orsRequest->uacsCodes->count() > 0)
                                                <div class="detail-section">
                                                    <div class="font-semibold text-gray-700 mb-1">
                                                        <i class="fas fa-list-ol text-blue-500 mr-1"></i> UACS Codes ({{ $history->orsRequest->uacsCodes->count() }})
                                                    </div>
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($history->orsRequest->uacsCodes->take(5) as $uacs)
                                                            <span class="detail-badge bg-blue-50 text-blue-700 border border-blue-200">
                                                                {{ $uacs->uacscode }} {{$uacs->description}}
                                                            </span>
                                                        @endforeach
                                                        @if($history->orsRequest->uacsCodes->count() > 5)
                                                            <span class="detail-badge bg-blue-100 text-blue-800">+{{ $history->orsRequest->uacsCodes->count() - 5 }} more</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- ADL Codes -->
                                            @if($history->orsRequest->adlCodes && $history->orsRequest->adlCodes->count() > 0)
                                                <div class="detail-section">
                                                    <div class="font-semibold text-gray-700 mb-1">
                                                        <i class="fas fa-code text-green-500 mr-1"></i> ADL Codes ({{ $history->orsRequest->adlCodes->count() }})
                                                    </div>
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($history->orsRequest->adlCodes->take(4) as $adl)
                                                            <span class="detail-badge bg-green-50 text-green-700 border border-green-200">
                                                                {{ $adl->adlcode }} {{$adl->description}}
                                                            </span>
                                                        @endforeach
                                                        @if($history->orsRequest->adlCodes->count() > 4)
                                                            <span class="detail-badge bg-green-100 text-green-800">+{{ $history->orsRequest->adlCodes->count() - 4 }} more</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- MFO/PAP -->
                                            @if($history->orsRequest->Mfopap)
                                                <div class="detail-section">
                                                    <div class="font-semibold text-gray-700 mb-1">
                                                        <i class="fas fa-sitemap text-purple-500 mr-1"></i> MFO/PAP
                                                    </div>
                                                    <div class="flex flex-wrap gap-1">
                                                        <span class="detail-badge bg-purple-50 text-purple-700 border border-purple-200">
                                                            {{ $history->orsRequest->Mfopap->mfocode ?? 'N/A' }}
                                                        </span>
                                                        <span class="detail-badge bg-purple-50 text-purple-700 border border-purple-200">
                                                            {{ $history->orsRequest->Mfopap->mfodescription ?? '' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Allotment Class -->
                                            @if($history->orsRequest->AllotmentClass)
                                                <div class="detail-section">
                                                    <div class="font-semibold text-gray-700 mb-1">
                                                        <i class="fas fa-tag text-orange-500 mr-1"></i> Allotment Class
                                                    </div>
                                                    <span class="detail-badge bg-orange-50 text-orange-700 border border-orange-200">
                                                        {{ $history->orsRequest->AllotmentClass->allotmentclass_code ?? 'N/A' }}
                                                    </span>
                                                </div>
                                            @endif

                                            <!-- Amount Information -->
                                            @if($history->orsRequest->amount)
                                                <div class="detail-section">
                                                    <div class="font-semibold text-gray-700 mb-1">
                                                        <i class="fas fa-money-bill-wave text-teal-500 mr-1"></i> Amount
                                                    </div>
                                                    <span class="detail-badge bg-teal-50 text-teal-700 border border-teal-200 font-bold">
                                                        ₱{{ number_format($history->orsRequest->amount, 2) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($history->verification_data)
                                        <div class="text-xs text-gray-600 space-y-2">
                                            @if(isset($history->verification_data['ors_number']))
                                                <div class="font-semibold">ORS #: {{ $history->verification_data['ors_number'] }}</div>
                                            @endif
                                            @if(isset($history->verification_data['uacs_count']))
                                                <div>UACS Codes: {{ $history->verification_data['uacs_count'] }}</div>
                                            @endif
                                            @if(isset($history->verification_data['amount']))
                                                <div class="font-bold">Amount: ₱{{ number_format($history->verification_data['amount'], 2) }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">No ORS data available</span>
                                    @endif
                                </td>
                                
                                <!-- Remarks -->
                                <td class="table-cell remarks-column text-gray-700">
                                    <div class="max-h-24 overflow-y-auto">
                                        {{ $history->remarks ?? '—' }}
                                    </div>
                                </td>
                                
                                <!-- Action Column - ORS Preview -->
                                <td class="table-cell">
                                      @if($history->status_after === 'ORS_obligated' && $history->orsRequest)
                                        <a href="{{ route('BudgetOfficer.dvrequests.previewOrs', $history->dvRequest->id) }}" 
                                           target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg transition duration-200 action-btn"
                                           title="Preview ORS Document">
                                            <i class="fas fa-eye mr-1"></i>
                                            Preview ORS
                                        </a>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1.5 bg-gray-300 text-gray-600 text-xs rounded-lg cursor-not-allowed"
                                              title="ORS not available for preview">
                                            <i class="fas fa-eye-slash mr-1"></i>
                                            No ORS
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="table-cell py-6 text-center text-gray-500">
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
                <a href="{{ route('BudgetOfficer.dvrequests.index') }}" 
                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition duration-200 inline-flex items-center">
                   <i class="fas fa-arrow-left mr-2"></i> Back to Budget Officer Dashboard
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

            // Add confirmation for ORS preview
            const previewButtons = document.querySelectorAll('a[href*="preview-ors"]');
            previewButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    console.log('Opening ORS preview...');
                });
            });
        });
    </script>
</body>
</html>