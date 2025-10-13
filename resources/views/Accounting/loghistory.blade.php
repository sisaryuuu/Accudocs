@extends('layouts.app')

@section('title', 'RANCA - Log History')

@section('content')
<div class="min-h-screen bg-gray-50 pt-24 pb-8">
    <div class="max-w-[95rem] mx-auto px-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">RANCA Log History</h1>
                <p class="mt-2 text-sm text-gray-600">Complete history of all processed DV requests</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('Accounting.dvrequests.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-200">
                    ← Back to Active Requests
                </a>
            </div>
        </div>

        <!-- Search -->
        <div class="mb-4">
            <input type="text" id="search-input" 
                   placeholder="🔍 Search History (Control No, DV No, ORS No, Program, Respocenter, Submitted By)..."
                   class="w-full md:w-1/2 px-4 py-2 border rounded-lg shadow-sm focus:ring focus:ring-blue-200 focus:outline-none">
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div id="history-table-container">
                @if($dvRequests->isEmpty())
                    <div class="text-center py-16">
                        <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No history records found</h3>
                        <p class="text-gray-500">All processed requests will appear here.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Control No.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">DV Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ORS Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Respocenter</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">UACS Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ADL Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">MFO/PAP</th>
                                    <th class="px-6- py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date Processed</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($dvRequests as $dv)
                                @php
                                    $statusColors = [
                                        'Outgoing_finalized' => ['bg-gray-100 text-gray-800', 'bg-gray-400', 'Pending'],
                                        'RANCA_received' => ['bg-yellow-100 text-yellow-800', 'bg-yellow-400', 'Received'],
                                        'RANCA_in_process' => ['bg-blue-100 text-blue-800', 'bg-blue-400', 'In Process'],
                                        'RANCA_validated' => ['bg-green-100 text-green-800', 'bg-green-400', 'Validated'],
                                    ];
                                    $statusConfig = $statusColors[$dv->status] ?? ['bg-gray-100 text-gray-800', 'bg-gray-400', $dv->status];
                                    
                                    // Get UACS codes with descriptions
                                    $uacsDisplay = $dv->orsRequest && $dv->orsRequest->uacsCodes->isNotEmpty() 
                                        ? $dv->orsRequest->uacsCodes->map(function($uacs) {
                                            return $uacs->uacscode . ' - ' . $uacs->description;
                                        })->implode('<br>') 
                                        : '—';

                                    // Get ADL code with description
                                    $adlDisplay = $dv->orsRequest && $dv->orsRequest->adlCode 
                                        ? $dv->orsRequest->adlCode->adlcode . ' - ' . $dv->orsRequest->adlCode->description
                                        : '—';

                                    // Get MFO/PAP with description
                                    $mfopapDisplay = $dv->orsRequest && $dv->orsRequest->mfopap 
                                        ? $dv->orsRequest->mfopap->mfocode . ' - ' . $dv->orsRequest->mfopap->mfodescription
                                        : '—';

                                    // Truncate function for long descriptions
                                    $truncate = function($text, $length = 40) {
                                        if (strlen($text) <= $length) return $text;
                                        return substr($text, 0, $length) . '...';
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        @if($dv->accounting_control_number)
                                            CN #-{{ now()->year }}-{{ str_pad($dv->accounting_control_number, 5, '0', STR_PAD_LEFT) }}
                                        @else
                                            #{{ $dv->id }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                        {{ $dv->dv_number ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                        {{ $dv->orsRequest->ors_number ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $dv->focalPerson->fname ?? '' }} {{ $dv->focalPerson->lname ?? '' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $dv->program->prcode ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                        <div class="text-xs">
                                            @if($dv->respocenter)
                                                <div class="font-medium">{{ $dv->respocenter->rccode ?? '' }}</div>
                                                <div class="text-gray-500 mt-1">{{ $truncate($dv->respocenter->description ?? '') }}</div>
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                        <div class="space-y-1">
                                            @if($uacsDisplay !== '—')
                                                @foreach(explode('<br>', $uacsDisplay) as $uacsLine)
                                                <div class="text-xs">
                                                    <span class="font-mono font-medium">{{ explode(' - ', $uacsLine)[0] }}</span>
                                                    <div class="text-gray-500 mt-1">{{ $truncate(explode(' - ', $uacsLine)[1] ?? '') }}</div>
                                                </div>
                                                @endforeach
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                        @if($adlDisplay !== '—')
                                        <div class="text-xs">
                                            <span class="font-mono font-medium">{{ explode(' - ', $adlDisplay)[0] }}</span>
                                            <div class="text-gray-500 mt-1">{{ $truncate(explode(' - ', $adlDisplay)[1] ?? '') }}</div>
                                        </div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                        @if($mfopapDisplay !== '—')
                                        <div class="text-xs">
                                            <span class="font-mono font-medium">{{ explode(' - ', $mfopapDisplay)[0] }}</span>
                                            <div class="text-gray-500 mt-1">{{ $truncate(explode(' - ', $mfopapDisplay)[1] ?? '') }}</div>
                                        </div>
                                        @else
                                            —
                                        @endif
                                    </td> 
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                        ₱{{ number_format($dv->amount ?? 0, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusConfig[0] }}">
                                            <span class="w-2 h-2 mr-2 rounded-full {{ $statusConfig[1] }}"></span>
                                            {{ $statusConfig[2] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $dv->created_at->format('M d, Y h:i A') }}
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const historyTableContainer = document.getElementById('history-table-container');
    const rows = historyTableContainer.querySelectorAll('tbody tr');

    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let hasVisibleRows = false;

        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            if (rowText.includes(searchTerm)) {
                row.style.display = '';
                hasVisibleRows = true;
            } else {
                row.style.display = 'none';
            }
        });

        // Show no results message if no rows are visible
        const noResults = historyTableContainer.querySelector('.no-results-message');
        if (!hasVisibleRows && rows.length > 0) {
            if (!noResults) {
                const noResultsHTML = `
                    <tr class="no-results-message">
                        <td colspan="12" class="px-6 py-8 text-center">
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No results found</h3>
                                <p class="mt-1 text-sm text-gray-500">Try adjusting your search terms.</p>
                            </div>
                        </td>
                    </tr>
                `;
                historyTableContainer.querySelector('tbody').insertAdjacentHTML('beforeend', noResultsHTML);
            }
        } else if (noResults) {
            noResults.remove();
        }
    });
});
</script>
@endsection