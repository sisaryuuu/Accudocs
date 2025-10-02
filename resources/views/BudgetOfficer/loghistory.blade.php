@extends('layouts.app')

@section('title', 'ORS Log History')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="bg-white shadow rounded-xl p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">ORS Log History</h1>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left border border-gray-200 rounded-lg">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-4 py-2 border-b">ORS Number</th>
                        <th class="px-4 py-2 border-b">Control No.</th>
                        <th class="px-4 py-2 border-b">Payee</th>
                        <th class="px-4 py-2 border-b">Program</th>
                        <th class="px-4 py-2 border-b">Responsibility Center</th>
                        <th class="px-4 py-2 border-b">Gross</th>
                        <th class="px-4 py-2 border-b">Net</th>
                        <th class="px-4 py-2 border-b">Status</th>
                        <th class="px-4 py-2 border-b">Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orsRequests as $ors)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border-b font-medium text-gray-900">
                                {{ $ors->ors_number }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ $ors->dvRequest->id ?? '—' }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ $ors->dvRequest->focalPerson->fname ?? '' }}
                                {{ $ors->dvRequest->focalPerson->lname ?? '' }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ $ors->dvRequest->program->prcode ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ $ors->dvRequest->respocenter->rccode ?? '' }}
                                {{ $ors->dvRequest->respocenter->description ?? '' }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                ₱{{ number_format($ors->dvRequest->amount ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                @php
                                    $statusClasses = [
                                        'ORS_obligated'     => 'bg-green-100 text-green-800',
                                        'ORS_onhold'        => 'bg-yellow-100 text-yellow-800',
                                        'ORS_for_revision'  => 'bg-red-100 text-red-800',
                                        'ORS_received'      => 'bg-gray-100 text-gray-800',
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-1 rounded-lg text-xs font-medium {{ $statusClasses[$ors->status] ?? 'bg-gray-200 text-gray-700' }}">
                                    {{ $ors->status }}
                                </span>
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ $ors->created_at->format('M d, Y h:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-4 text-center text-gray-500">
                                No ORS records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
