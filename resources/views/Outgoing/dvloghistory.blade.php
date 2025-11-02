@extends('layouts.app')

@section('title', 'Outgoing - Verification History')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="bg-white shadow rounded-xl p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">📜 Outgoing - Verification History</h1>
            <div class="text-sm text-gray-600">
                Showing your personal verification records
            </div>
        </div>

        {{-- Search and Filter --}}
        <form method="GET" action="{{ route('Outgoing.dvrequests.dvloghistory') }}" class="mb-6">
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
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Verification Step</label>
                    <select name="step" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Steps</option>
                        <option value="receive" {{ request('step') == 'receive' ? 'selected' : '' }}>Receive</option>
                        <option value="process" {{ request('step') == 'process' ? 'selected' : '' }}>Process</option>
                        <option value="finalize" {{ request('step') == 'finalize' ? 'selected' : '' }}>Finalize</option>
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
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition duration-200">
                    Apply Filters
                </button>
                <a href="{{ route('Outgoing.dvrequests.dvloghistory') }}" 
                   class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition duration-200">
                    Clear Filters
                </a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">DV Request ID</th>
                        <th class="px-4 py-3">Verification Step</th>
                        <th class="px-4 py-3">Status Before</th>
                        <th class="px-4 py-3">Status After</th>
                        <th class="px-4 py-3">Program</th>
                        <th class="px-4 py-3">Payee</th>
                        <th class="px-4 py-3">MFO/PAP</th>
                        <th class="px-4 py-3">ADL Code</th>
                        <th class="px-4 py-3">UACS Code</th>
                        <th class="px-4 py-3">Allotment Class</th>
                        <th class="px-4 py-3">Date & Time</th>
                        <th class="px-4 py-3">Remarks</th>
                        <th class="px-4 py-3">Details</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($verificationHistory as $history)
                        <tr class="hover:bg-gray-50">
                            {{-- DV Request ID --}}
                            <td class="px-4 py-3 font-medium text-gray-900">
                                #{{ $history->dv_request_id }}
                            </td>
                            
                            {{-- Verification Step --}}
                            <td class="px-4 py-3">
                                @php
                                    $stepColors = [
                                        'receive' => 'bg-blue-100 text-blue-800',
                                        'process' => 'bg-yellow-100 text-yellow-800', 
                                        'finalize' => 'bg-green-100 text-green-800',
                                        'reject' => 'bg-red-100 text-red-800',
                                    ];
                                    $color = $stepColors[$history->verification_step] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $color }}">
                                    {{ ucfirst($history->verification_step) }}
                                </span>
                            </td>
                            
                            {{-- Status Before --}}
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                                    {{ ucwords(str_replace('_', ' ', $history->status_before ?? 'N/A')) }}
                                </span>
                            </td>
                            
                            {{-- Status After --}}
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'Outgoing_received' => 'bg-blue-100 text-blue-800',
                                        'Outgoing_in_process' => 'bg-yellow-100 text-yellow-800',
                                        'Outgoing_finalized' => 'bg-green-100 text-green-800',
                                        'Outgoing_rejected' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusColor = $statusColors[$history->status_after] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColor }}">
                                    {{ ucwords(str_replace('_',' ', $history->status_after ?? 'N/A')) }}
                                </span>
                            </td>
                            
                            {{-- Program --}}
                            <td class="px-4 py-3">
                                {{ $history->dvRequest->program->prcode ?? ($history->verification_data['program'] ?? 'N/A') }}
                            </td>
                            
                            {{-- Payee --}}
                            <td class="px-4 py-3">
                                @if($history->dvRequest && $history->dvRequest->focalPerson)
                                    {{ $history->dvRequest->focalPerson->fname ?? '' }} 
                                    {{ $history->dvRequest->focalPerson->lname ?? '' }}
                                @else
                                    {{ $history->verification_data['payee_name'] ?? 'N/A' }}
                                @endif
                            </td>
                            
                            {{-- MFO/PAP --}}
                            <td class="px-4 py-3 text-gray-700">
                                @if($history->dvRequest && $history->dvRequest->mfoPap)
                                    {{ $history->dvRequest->mfoPap->name ?? 'N/A' }}
                                @else
                                    {{ $history->verification_data['mfo_pap'] ?? 'N/A' }}
                                @endif
                            </td>
                            
                            {{-- ADL Code --}}
                            <td class="px-4 py-3 text-gray-700">
                                @if($history->dvRequest && $history->dvRequest->adlCode)
                                    {{ $history->dvRequest->adlCode->code ?? 'N/A' }}
                                @else
                                    {{ $history->verification_data['adl_code'] ?? 'N/A' }}
                                @endif
                            </td>
                            
                            {{-- UACS Code --}}
                            <td class="px-4 py-3 text-gray-700">
                                @if($history->dvRequest && $history->dvRequest->uacsCode)
                                    {{ $history->dvRequest->uacsCode->code ?? 'N/A' }}
                                @else
                                    {{ $history->verification_data['uacs_code'] ?? 'N/A' }}
                                @endif
                            </td>
                            
                            {{-- Allotment Class --}}
                            <td class="px-4 py-3 text-gray-700">
                                @if($history->dvRequest && $history->dvRequest->allotmentClass)
                                    {{ $history->dvRequest->allotmentClass->name ?? 'N/A' }}
                                @else
                                    {{ $history->verification_data['allotment_class'] ?? 'N/A' }}
                                @endif
                            </td>
                            
                            {{-- Date & Time --}}
                            <td class="px-4 py-3 text-gray-600">
                                {{ $history->verified_at->format('M d, Y h:i A') }}
                            </td>
                            
                            {{-- Remarks --}}
                            <td class="px-4 py-3 text-gray-700 max-w-xs truncate">
                                {{ $history->remarks ?? '—' }}
                            </td>
                            
                            {{-- Details --}}
                            <td class="px-4 py-3">
                                @if($history->verification_data)
                                    <div class="text-xs text-gray-600 space-y-1">
                                        @if(isset($history->verification_data['dv_number']))
                                            <div><strong>DV #:</strong> {{ $history->verification_data['dv_number'] }}</div>
                                        @endif
                                        @if(isset($history->verification_data['attachments_checked']))
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
                                            <div><strong>Attachments:</strong> {{ $checkedCount }}/{{ $totalCount }} checked</div>
                                        @endif
                                        @if(isset($history->verification_data['amount']))
                                            <div><strong>Amount:</strong> ₱{{ number_format($history->verification_data['amount'], 2) }}</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            
                            {{-- Action --}}
                            <td class="px-4 py-3">
                                @if($history->status_after === 'Outgoing_finalized')
                                    <a href="{{ route('Outgoing.dvrequests.preview', $history->dv_request_id) }}" 
                                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                    View DV Request
                                    </a>
                                @else
                                    <span class="text-gray-400 text-sm">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-4 py-6 text-center text-gray-500">
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

        {{-- Pagination --}}
        @if($verificationHistory->hasPages())
            <div class="mt-6">
                {{ $verificationHistory->links() }}
            </div>
        @endif

        <div class="mt-6">
            <a href="{{ route('Outgoing.dvrequests.index') }}" 
               class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition duration-200">
               ← Back to Outgoing Dashboard
            </a>
        </div>
    </div>
</div>
@endsection