@extends('layouts.app')

@section('title', 'Accounting Log History')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="bg-white shadow rounded-xl p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Accounting Log History</h1>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left border border-gray-200 rounded-lg">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-4 py-2 border-b">Control No.</th>
                        <th class="px-4 py-2 border-b">DV Number</th>
                        <th class="px-4 py-2 border-b">ORS Number</th>
                        <th class="px-4 py-2 border-b">Payee</th>
                        <th class="px-4 py-2 border-b">Program</th>
                        <th class="px-4 py-2 border-b">Responsibility Center</th>
                        <th class="px-4 py-2 border-b">Amount</th>
                        <th class="px-4 py-2 border-b">Status</th>
                        <th class="px-4 py-2 border-b">Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dvRequests as $dv)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border-b font-medium text-gray-900">
                                #{{ $dv->id }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ $dv->dv_number ?? '—' }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ $dv->orsRequest->ors_number ?? '—' }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ $dv->focalPerson->fname ?? '' }} {{ $dv->focalPerson->lname ?? '' }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ $dv->program->prcode ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ $dv->respocenter->rccode ?? '' }}
                                {{ $dv->respocenter->description ?? '' }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                ₱{{ number_format($dv->amount ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                @php
                                    $statusClasses = [
                                        'RANCA_received'            => 'bg-gray-100 text-gray-800',
                                        'RANCA_in_process' => 'bg-yellow-100 text-yellow-800',
                                        'RANCA_validated'  => 'bg-green-100 text-green-800',
                                        'Outgoing_finalized'    => 'bg-purple-100 text-purple-800',
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-1 rounded-lg text-xs font-medium {{ $statusClasses[$dv->status] ?? 'bg-gray-200 text-gray-700' }}">
                                    {{ $dv->status }}
                                </span>
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ $dv->created_at->format('M d, Y h:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-4 text-center text-gray-500">
                                No Accounting records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
