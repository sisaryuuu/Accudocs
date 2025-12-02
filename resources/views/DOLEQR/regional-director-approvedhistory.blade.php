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
        
        .qr-code-container {
            max-width: 200px;
            margin: 0 auto;
        }
        
        .token-display {
            font-family: monospace;
            background-color: #f3f4f6;
            padding: 8px 12px;
            border-radius: 6px;
            word-break: break-all;
            font-size: 0.875rem;
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
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-purple-600 font-medium">QR Scans</p>
                            <p class="text-2xl font-bold text-purple-800">
                                {{ $approvalHistory->where('approval_data', 'like', '%qr_token%')->count() }}
                            </p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                            <i class="fas fa-qrcode text-purple-600"></i>
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
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                                            {{ ucfirst($history->approval_step) }}
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
                                <td colspan="11" class="px-4 py-6 text-center text-gray-500">
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
    <div id="detailsModal{{ $history->id }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Approval Details</h3>
                    <button onclick="closeDetailsModal({{ $history->id }})" class="text-gray-400 hover:text-gray-600 text-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mt-4 space-y-4">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">DV Number</p>
                            <p class="text-base font-semibold text-gray-900">{{ $history->dvRequest->dv_number ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Amount</p>
                            <p class="text-base font-semibold text-green-600">₱{{ number_format($history->dvRequest->amount, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Program</p>
                            <p class="text-base text-gray-900">{{ $history->dvRequest->program->prcode ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Payee</p>
                            <p class="text-base text-gray-900">{{ trim(($history->dvRequest->focalPerson->fname ?? '') . ' ' . ($history->dvRequest->focalPerson->lname ?? '')) }}</p>
                        </div>
                    </div>
                    
                    <!-- Approval Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Approved At</p>
                            <p class="text-base text-gray-900">{{ $history->approved_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Approval Step</p>
                            <p class="text-base">
                                @if($history->approval_step == 'received')
                                    <span class="px-3 py-1 text-sm font-medium rounded-full status-received">
                                        <i class="fas fa-inbox mr-1"></i> Received
                                    </span>
                                @elseif($history->approval_step == 'signed_approved')
                                    <span class="px-3 py-1 text-sm font-medium rounded-full status-signed">
                                        <i class="fas fa-check-circle mr-1"></i> Signed & Approved
                                    </span>
                                @else
                                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-gray-100 text-gray-600">
                                        {{ ucfirst($history->approval_step) }}
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>
                    
                    <!-- Status Change -->
                    <div>
                        <p class="text-sm font-medium text-gray-500">Status Change</p>
                        <div class="flex items-center mt-2 space-x-3">
                            <span class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium">
                                {{ $history->status_before }}
                            </span>
                            <i class="fas fa-arrow-right text-gray-400 text-lg"></i>
                            <span class="px-3 py-2 bg-green-100 text-green-700 rounded-lg text-sm font-medium">
                                {{ $history->status_after }}
                            </span>
                        </div>
                    </div>

                    <!-- QR Code and Token Section -->
                    <div class="border-t pt-4 mt-4">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-qrcode mr-2 text-indigo-600"></i>
                            QR Code Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- QR Code Display -->
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-500 mb-3">QR Code</p>
                                <div class="qr-code-container">
                                    @if($history->dvRequest->qr_code_data)
                                        <img src="{{ $history->dvRequest->qr_code_data }}" 
                                             alt="QR Code for DV {{ $history->dvRequest->dv_number }}" 
                                             class="w-full h-auto border-2 border-gray-200 rounded-lg shadow-md hover:shadow-lg transition duration-200">
                                        <p class="text-xs text-gray-500 mt-2">Scan to verify DV authenticity</p>
                                    @else
                                        <div class="bg-gray-100 rounded-lg p-8 text-center border-2 border-dashed border-gray-300">
                                            <i class="fas fa-qrcode text-gray-400 text-4xl mb-3"></i>
                                            <p class="text-sm text-gray-500 font-medium">QR Code not available</p>
                                            <p class="text-xs text-gray-400 mt-1">No QR code generated for this DV</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Token Information -->
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-3">QR Token & Information</p>
                                <div class="space-y-4">
                                    <!-- Token Display -->
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 mb-1">Unique Token</p>
                                        <div class="token-display bg-gray-50 border border-gray-200">
                                            {{ $history->dvRequest->qr_code_token ?? 'No token available' }}
                                        </div>
                                    </div>
                                    
                                    @if($history->dvRequest->qr_code_token)
                                        <!-- Token Status Information -->
                                        <div class="space-y-2">
                                            <div class="flex justify-between items-center">
                                                <p class="text-xs font-medium text-gray-500">Token Status:</p>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $history->dvRequest->qr_status == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ ucfirst($history->dvRequest->qr_status ?? 'inactive') }}
                                                </span>
                                            </div>
                                            
                                            @if($history->dvRequest->qr_code_generated_at)
                                                <div class="flex justify-between items-center">
                                                    <p class="text-xs font-medium text-gray-500">Generated:</p>
                                                    <span class="text-xs text-gray-600">
                                                        {{ $history->dvRequest->qr_code_generated_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                                                    </span>
                                                </div>
                                            @endif
                                            
                                            <!-- Copy Token Button -->
                                            <button type="button" 
                                                    onclick="copyToken('{{ $history->dvRequest->qr_code_token }}', event)"
                                                    class="w-full mt-2 text-sm bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-200 flex items-center justify-center">
                                                <i class="fas fa-copy mr-2"></i> Copy Token to Clipboard
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-center py-4">
                                            <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl mb-2"></i>
                                            <p class="text-sm text-gray-500">No QR token available for this transaction</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Remarks Section -->
                    @if($history->remarks)
                        <div class="border-t pt-4">
                            <p class="text-sm font-medium text-gray-500 mb-2">Remarks</p>
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <p class="text-sm text-gray-700 leading-relaxed">{{ $history->remarks }}</p>
                            </div>
                        </div>
                    @endif

                </div>
                
                <!-- Modal Footer -->
                <div class="mt-6 flex justify-end space-x-3">
                    <button onclick="closeDetailsModal({{ $history->id }})" 
                            class="px-4 py-2 bg-gray-500 text-white text-sm rounded-lg hover:bg-gray-600 transition duration-200 flex items-center">
                        <i class="fas fa-times mr-2"></i> Close
                    </button>
                    @if($history->dvRequest->qr_code_token)
                        <button onclick="copyToken('{{ $history->dvRequest->qr_code_token }}', event)" 
                                class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition duration-200 flex items-center">
                            <i class="fas fa-copy mr-2"></i> Copy Token
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <script>
        // Modal functions
        function openDetailsModal(id) {
            document.getElementById('detailsModal' + id).classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeDetailsModal(id) {
            document.getElementById('detailsModal' + id).classList.add('hidden');
            document.body.style.overflow = 'auto'; // Restore scrolling
        }

        // Copy token to clipboard - FIXED VERSION
        function copyToken(token, event) {
            // If event is not provided (from footer button), find the first copy button
            let button;
            if (event && event.target) {
                button = event.target.closest('button');
            } else {
                button = document.querySelector('button[onclick*="copyToken"]');
            }
            
            navigator.clipboard.writeText(token).then(function() {
                // Show success message
                const originalText = button.innerHTML;
                const originalClasses = button.className;
                
                button.innerHTML = '<i class="fas fa-check mr-2"></i> Copied!';
                button.className = originalClasses.replace('bg-indigo-600', 'bg-green-600').replace('hover:bg-indigo-700', 'hover:bg-green-700');
                button.disabled = true;
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.className = originalClasses;
                    button.disabled = false;
                }, 2000);
                
                // Show temporary toast notification
                showToast('Token copied to clipboard!', 'success');
            }).catch(function(err) {
                console.error('Failed to copy token: ', err);
                showToast('Failed to copy token to clipboard', 'error');
            });
        }

        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            toast.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-transform duration-300 translate-x-0`;
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-0');
                toast.classList.add('translate-x-full');
            }, 10);
            
            // Remove after 3 seconds
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('fixed')) {
                const modals = document.querySelectorAll('[id^="detailsModal"]');
                modals.forEach(modal => {
                    modal.classList.add('hidden');
                });
                document.body.style.overflow = 'auto';
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.querySelectorAll('[id^="detailsModal"]');
                modals.forEach(modal => {
                    if (!modal.classList.contains('hidden')) {
                        modal.classList.add('hidden');
                        document.body.style.overflow = 'auto';
                    }
                });
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
                
                dateFrom.valueAsDate = thirtyDaysAgo;
                dateTo.valueAsDate = today;
            }
            
            // Add loading state to form submission
            const filterForm = document.querySelector('form');
            if (filterForm) {
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
            }
        });
    </script>
</body>
</html>
@endsection