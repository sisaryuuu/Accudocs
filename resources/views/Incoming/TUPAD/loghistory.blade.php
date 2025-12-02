<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incoming TUPAD - Verification History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-received { background-color: #dbeafe; color: #1e40af; }
        .status-process { background-color: #fef3c7; color: #d97706; }
        .status-verify { background-color: #dcfce7; color: #16a34a; }
        .status-rejected { background-color: #fee2e2; color: #dc2626; }
        
        .status-incoming-received { background-color: #dbeafe; color: #1e40af; }
        .status-incoming-process { background-color: #fef3c7; color: #d97706; }
        .status-incoming-verified { background-color: #dcfce7; color: #16a34a; }
        .status-incoming-rejected { background-color: #fee2e2; color: #dc2626; }
        
        .pagination .active {
            background-color: #4f46e5;
            color: white;
        }
        
        .modality-badge {
            background-color: #f3f4f6;
            color: #6b7280;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
        }
        
        .modal-close:hover {
            color: #374151;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1rem;
        }
        
        .detail-label {
            font-weight: 600;
            color: #374151;
        }
        
        .detail-value {
            color: #6b7280;
        }
        
        .attachment-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .attachment-status {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-size: 0.75rem;
        }
        
        .attachment-present {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .attachment-missing {
            background-color: #fee2e2;
            color: #dc2626;
        }
        
        .attachments-section {
            margin-top: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .attachments-header {
            background-color: #f9fafb;
            padding: 1rem;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .attachments-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .attachment-details {
            flex: 1;
        }
        
        .attachment-name {
            font-weight: 500;
            color: #374151;
        }
        
        .attachment-notes {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .no-attachments {
            padding: 1.5rem;
            text-align: center;
            color: #9ca3af;
            font-style: italic;
        }
        
        .attachments-summary {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding: 0.75rem;
            background-color: #f9fafb;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        
        .summary-present {
            color: #16a34a;
        }
        
        .summary-missing {
            color: #dc2626;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto py-8 px-4">
        <div class="bg-white shadow rounded-xl p-6">
            <div class="mt-6">
                <a href="{{ route('Incoming.TUPAD.index') }}" 
                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition duration-200 inline-flex items-center">
                   <i class="fas fa-arrow-left mr-2"></i> Back to Incoming TUPAD Request
                </a>
            </div>
            <br>
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">📜 Incoming TUPAD - Verification History</h1>
                    <p class="text-sm text-gray-600 mt-1">Showing verification history records</p>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <form method="GET" action="{{ route('dvrequests.tupadhistory') }}" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search Control #</label>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Search by DV Number..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Program</label>
                        <select name="program" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Programs</option>
                            <option value="TUPAD" {{ request('program') == 'TUPAD' ? 'selected' : '' }}>TUPAD</option>
                            <option value="GIP" {{ request('program') == 'GIP' ? 'selected' : '' }}>GIP</option>
                            <option value="TEV" {{ request('program') == 'TEV' ? 'selected' : '' }}>TEV</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Verification Step</label>
                        <select name="step" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Steps</option>
                            <option value="receive" {{ request('step') == 'receive' ? 'selected' : '' }}>Receive</option>
                            <option value="verify" {{ request('step') == 'verify' ? 'selected' : '' }}>Verify</option>
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
                    <a href="{{ route('dvrequests.tupadhistory') }}" 
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
                            <p class="text-sm text-blue-600 font-medium">Total Records</p>
                            <p class="text-2xl font-bold text-blue-800">{{ $verificationHistory->total() }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-list text-blue-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-green-600 font-medium">Verified</p>
                            <p class="text-2xl font-bold text-green-800">
                                {{ $verificationHistory->where('verification_step', 'verify')->count() }}
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
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-purple-600 font-medium">In Process</p>
                            <p class="text-2xl font-bold text-purple-800">
                                {{ $verificationHistory->where('verification_step', 'process')->count() }}
                            </p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                            <i class="fas fa-sync-alt text-purple-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Control Number</th>
                            <th class="px-4 py-3">Verification Step</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Program</th>
                            <th class="px-4 py-3">Modality</th>
                            <th class="px-4 py-3">Payee</th>
                            <th class="px-4 py-3">Date & Time</th>
                            <th class="px-4 py-3">Attachments Checked</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($verificationHistory as $history)
                            <tr class="hover:bg-gray-50">
                                <!-- Control Number -->
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    @if($history->dvRequest && $history->dvRequest->accounting_control_number)
                                        CN #-{{ now()->year }}-{{ str_pad($history->dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT) }}
                                    @elseif($history->accounting_control_number)
                                        CN #-{{ now()->year }}-{{ str_pad($history->accounting_control_number, 4, '0', STR_PAD_LEFT) }}
                                    @else
                                        <span class="text-gray-400">Pending</span>
                                    @endif
                                </td>
                                
                                <!-- Verification Step -->
                                <td class="px-4 py-3">
                                    @php
                                        $stepColors = [
                                            'receive' => 'status-received',
                                            'process' => 'status-process',
                                            'verify' => 'status-verify',
                                            'reject' => 'status-rejected',
                                        ];
                                        $color = $stepColors[$history->verification_step] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $color }}">
                                        {{ ucfirst($history->verification_step) }}
                                    </span>
                                </td>
                                
                                <!-- Status -->
                                <td class="px-4 py-3">
                                    @php
                                        $statusColors = [
                                            'Incoming-TUPAD_received' => 'status-incoming-received',
                                            'Incoming-TUPAD_in_process' => 'status-incoming-process',
                                            'Incoming-TUPAD_verified' => 'status-incoming-verified',
                                            'Incoming-TUPAD_rejected' => 'status-incoming-rejected',
                                        ];
                                        $statusColor = $statusColors[$history->status_after] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColor }}">
                                        {{ ucwords(str_replace('_',' ', $history->status_after ?? 'N/A')) }}
                                    </span>
                                </td>
                                
                                <!-- Program -->
                                <td class="px-4 py-3">
                                    @php
                                        $programCode = 'N/A';
                                        if ($history->dvRequest && $history->dvRequest->program) {
                                            $programCode = $history->dvRequest->program->prcode ?? 'Program Found';
                                        } elseif ($history->dvRequest && $history->dvRequest->program_id) {
                                            $programCode = 'Program ID: ' . $history->dvRequest->program_id;
                                        }
                                    @endphp
                                    {{ $programCode }}
                                </td>
                                
                                <!-- Modality -->
                                <td class="px-4 py-3">
                                    @php
                                        $modality = 'N/A';
                                        if ($history->dvRequest && $history->dvRequest->tupad_modality) {
                                            $modality = $history->dvRequest->tupad_modality;
                                        } elseif ($history->verification_data && isset($history->verification_data['modality'])) {
                                            $modality = $history->verification_data['modality'];
                                        }
                                    @endphp
                                    <span class="modality-badge">{{ $modality }}</span>
                                </td>
                                
                                <!-- Payee -->
                                <td class="px-4 py-3">
                                    @php
                                        $payeeName = 'N/A';
                                        if ($history->dvRequest && $history->dvRequest->focalPerson) {
                                            $payeeName = trim(($history->dvRequest->focalPerson->fname ?? '') . ' ' . 
                                                            ($history->dvRequest->focalPerson->lname ?? ''));
                                        } elseif ($history->dvRequest && $history->dvRequest->focalperson_id) {
                                            $payeeName = 'Focal ID: ' . $history->dvRequest->focalperson_id;
                                        }
                                    @endphp
                                    {{ $payeeName }}
                                </td>
                                
                                <!-- Date & Time -->
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $history->verified_at->format('M d, Y h:i A') }}
                                </td>
                            
                                
                                <!-- Attachments Checked -->
                                <td class="px-4 py-3">
                                    @if($history->verification_data && isset($history->verification_data['attachments_checked']))
                                        @php
                                            $attachments = $history->verification_data['attachments_checked'];
                                            if (is_array($attachments)) {
                                                $checkedCount = count(array_filter($attachments, function($a) {
                                                    return is_array($a) && ($a['is_present'] ?? false);
                                                }));
                                                $totalCount = count($attachments);
                                            } else {
                                                $checkedCount = 0;
                                                $totalCount = 0;
                                            }
                                        @endphp
                                        <span class="text-xs text-gray-600">
                                            {{ $checkedCount }}/{{ $totalCount }} checked
                                        </span>
                                        @if($checkedCount > 0)
                                            <div class="mt-1 text-xs text-green-600">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                {{ $checkedCount }} present
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                
                                <!-- Action -->
                                <td class="px-4 py-3">
                                    @if($history->dvRequest)
                                        <button 
                                            type="button" 
                                            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium view-details-btn"
                                            data-history-id="{{ $history->id }}"
                                            data-history-data="{{ json_encode($history) }}"
                                        >
                                            View Details
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-400">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-6 text-center text-gray-500">
                                    @if(request()->hasAny(['search', 'program', 'modality', 'step', 'date_from', 'date_to']))
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
                <a href="{{ route('Incoming.TUPAD.index') }}" 
                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition duration-200 inline-flex items-center">
                   <i class="fas fa-arrow-left mr-2"></i> Back to Incoming TUPAD Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Modal for View Details -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-900">Verification Details</h3>
                <button type="button" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="detail-grid">
                    <div class="detail-label">Control Number:</div>
                    <div class="detail-value" id="modal-control-number">-</div>
                    
                    <div class="detail-label">Verification Step:</div>
                    <div class="detail-value" id="modal-verification-step">-</div>
                    
                    <div class="detail-label">Current Status:</div>
                    <div class="detail-value" id="modal-status-after">-</div>
                    
                    <div class="detail-label">Program:</div>
                    <div class="detail-value" id="modal-program">-</div>
                    
                    <div class="detail-label">Modality:</div>
                    <div class="detail-value" id="modal-modality">-</div>
                    
                    <div class="detail-label">Payee:</div>
                    <div class="detail-value" id="modal-payee">-</div>
                    
                    <div class="detail-label">Date & Time:</div>
                    <div class="detail-value" id="modal-date-time">-</div>
                    
                    <div class="detail-label">Remarks:</div>
                    <div class="detail-value" id="modal-remarks">-</div>
                </div>
                
                <!-- Attachments Section -->
                <div class="attachments-section">
                    <div class="attachments-header">
                        <i class="fas fa-paperclip mr-2"></i>Attachments Checked
                    </div>
                    <div class="attachments-list" id="modal-attachments-list">
                        <!-- Attachments will be populated here -->
                    </div>
                    <div class="attachments-summary">
                        <span class="summary-present">
                            <i class="fas fa-check-circle mr-1"></i>
                            <span id="present-count">0</span> present
                        </span>
                        <span class="summary-missing">
                            <i class="fas fa-times-circle mr-1"></i>
                            <span id="missing-count">0</span> missing
                        </span>
                        <span class="text-gray-600">
                            Total: <span id="total-count">0</span> attachments
                        </span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md transition duration-200 modal-close">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Add interactive functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-set date range to last 30 days if no dates are selected
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
            });
            
            // Modal functionality
            const modal = document.getElementById('detailsModal');
            const closeButtons = document.querySelectorAll('.modal-close');
            const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
            
            // Close modal when clicking close button or outside the modal
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            });
            
            // Close modal when clicking outside the modal content
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Handle view details button clicks
            viewDetailsButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const historyData = JSON.parse(this.getAttribute('data-history-data'));
                    showDetailsModal(historyData);
                });
            });
            
          // Function to show details in modal
function showDetailsModal(historyData) {
    console.log('History Data:', historyData); // Debug log
    
    // Populate basic information
    document.getElementById('modal-control-number').textContent = 
        historyData.dv_request && historyData.dv_request.accounting_control_number 
        ? `CN #-${new Date().getFullYear()}-${String(historyData.dv_request.accounting_control_number).padStart(4, '0')}`
        : (historyData.accounting_control_number 
            ? `CN #-${new Date().getFullYear()}-${String(historyData.accounting_control_number).padStart(4, '0')}`
            : 'Pending');

    document.getElementById('modal-verification-step').textContent = 
        historyData.verification_step ? historyData.verification_step.charAt(0).toUpperCase() + historyData.verification_step.slice(1) : 'N/A';
    
    document.getElementById('modal-status-after').textContent = 
        historyData.status_after ? historyData.status_after.replace(/_/g, ' ') : 'N/A';
    
    // Enhanced Program data handling
    let programDisplay = 'N/A';
    if (historyData.dv_request && historyData.dv_request.program) {
        programDisplay = historyData.dv_request.program.prcode || 'Program Found';
    } else if (historyData.dv_request && historyData.dv_request.program_id) {
        programDisplay = 'Program ID: ' + historyData.dv_request.program_id;
    }
    document.getElementById('modal-program').textContent = programDisplay;
    
    // Enhanced Modality data handling
    let modalityDisplay = 'N/A';
    if (historyData.dv_request && historyData.dv_request.tupad_modality) {
        modalityDisplay = historyData.dv_request.tupad_modality;
    } else if (historyData.verification_data && historyData.verification_data.modality) {
        modalityDisplay = historyData.verification_data.modality;
    }
    document.getElementById('modal-modality').textContent = modalityDisplay;
    
    // Enhanced Payee data handling
    let payeeDisplay = 'N/A';
    if (historyData.dv_request && historyData.dv_request.focal_person) {
        payeeDisplay = (historyData.dv_request.focal_person.fname || '') + ' ' + 
                      (historyData.dv_request.focal_person.lname || '');
    } else if (historyData.dv_request && historyData.dv_request.focalPerson) {
        payeeDisplay = (historyData.dv_request.focalPerson.fname || '') + ' ' + 
                      (historyData.dv_request.focalPerson.lname || '');
    }
    document.getElementById('modal-payee').textContent = payeeDisplay.trim() || 'N/A';
    
    document.getElementById('modal-date-time').textContent = 
        historyData.verified_at ? new Date(historyData.verified_at).toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        }) : 'N/A';
    
    document.getElementById('modal-remarks').textContent = historyData.remarks || '—';
    
    // ✅ FIXED: Correctly populate attachments from verification_data
    const attachmentsList = document.getElementById('modal-attachments-list');
    attachmentsList.innerHTML = '';
    
    let presentCount = 0;
    let missingCount = 0;
    let totalCount = 0;
    
    // ✅ FIX: Check for attachments in the correct location
    if (historyData.verification_data && historyData.verification_data.attachments_checked) {
        const attachments = historyData.verification_data.attachments_checked;
        
        console.log('Attachments found in verification_data:', attachments);
        
        if (Array.isArray(attachments) && attachments.length > 0) {
            attachments.forEach((attachment, index) => {
                if (typeof attachment === 'object') {
                    const attachmentItem = document.createElement('div');
                    attachmentItem.className = 'attachment-item';
                    
                    // ✅ FIX: Use the correct property names from your PHP code
                    const isPresent = attachment.is_present || false;
                    const attachmentName = attachment.name || `Attachment ${index + 1}`;
                    const notes = attachment.notes || '';
                    
                    if (isPresent) presentCount++;
                    else missingCount++;
                    totalCount++;
                    
                    attachmentItem.innerHTML = `
                        <div class="attachment-status ${isPresent ? 'attachment-present' : 'attachment-missing'}">
                            <i class="fas ${isPresent ? 'fa-check' : 'fa-times'}"></i>
                        </div>
                        <div class="attachment-details">
                            <div class="attachment-name">${attachmentName}</div>
                            ${notes ? `<div class="attachment-notes">${notes}</div>` : ''}
                        </div>
                    `;
                    
                    attachmentsList.appendChild(attachmentItem);
                }
            });
        } else {
            attachmentsList.innerHTML = '<div class="no-attachments">No attachments were checked during this verification</div>';
        }
    } else {
        console.log('No attachments_checked found in verification_data');
        attachmentsList.innerHTML = '<div class="no-attachments">No attachment data available for this verification</div>';
    }
    
    // Update summary counts
    document.getElementById('present-count').textContent = presentCount;
    document.getElementById('missing-count').textContent = missingCount;
    document.getElementById('total-count').textContent = totalCount;
    
    // Show the modal
    modal.style.display = 'flex';
}
        });
    </script>
</body>
</html>