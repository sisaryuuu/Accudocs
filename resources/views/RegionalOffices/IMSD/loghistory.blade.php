@extends('layouts.app')

@section('title', 'IMSD - Verification History')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="bg-white shadow rounded-xl p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">📜 IMSD - Verification History</h1>
            <div class="text-sm text-gray-600">
                Showing your personal verification records
            </div>
        </div>

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
                        <th class="px-4 py-3">Date & Time</th>
                        <th class="px-4 py-3">Remarks</th>
                        <th class="px-4 py-3">Attachments Checked</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($verificationHistory as $history)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                #{{ $history->dv_request_id }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $stepColors = [
                                        'receive' => 'bg-blue-100 text-blue-800',
                                        'process' => 'bg-yellow-100 text-yellow-800',
                                        'verify' => 'bg-green-100 text-green-800',
                                        'reject' => 'bg-red-100 text-red-800',
                                    ];
                                    $color = $stepColors[$history->verification_step] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $color }}">
                                    {{ ucfirst($history->verification_step) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                                    {{ $history->status_before }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'IMSD_received' => 'bg-blue-100 text-blue-800',
                                        'IMSD_in_process' => 'bg-yellow-100 text-yellow-800',
                                        'IMSD_verified' => 'bg-green-100 text-green-800',
                                        'IMSD_rejected' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusColor = $statusColors[$history->status_after] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColor }}">
                                    {{ $history->status_after }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                {{ $history->dvRequest->program->prcode ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $history->dvRequest->focalPerson->fname ?? '' }} 
                                {{ $history->dvRequest->focalPerson->lname ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $history->verified_at->format('M d, Y h:i A') }}
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $history->remarks ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($history->verification_data && isset($history->verification_data['attachments_checked']))
                                    @php
                                        $attachments = $history->verification_data['attachments_checked'];
                                        $checkedCount = count(array_filter($attachments, fn($a) => $a['is_present']));
                                        $totalCount = count($attachments);
                                    @endphp
                                    <span class="text-xs text-gray-600">
                                        {{ $checkedCount }}/{{ $totalCount }} checked
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-gray-500">
                                No verification history found.
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
            <a href="{{ route('RegionalOffices.IMSD.index') }}" 
               class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition duration-200">
               ← Back to IMSD Dashboard
            </a>
        </div>
    </div>
</div>
@endsection