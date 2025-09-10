@extends('layouts.app')

@section('title', 'IMSD - Log History')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="bg-white shadow rounded-xl p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">📜 IMSD - Log History</h1>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Control #</th>
                        <th class="px-4 py-3">Payee</th>
                        <th class="px-4 py-3">Program</th>
                        <th class="px-4 py-3">Resp. Center</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Processed By</th>
                        <th class="px-4 py-3">Date Verified</th>
                        <th class="px-4 py-3">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($dvRequests as $request)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">#{{ $request->id }}</td>
                            <td class="px-4 py-3">
                                {{ $request->focalPerson->fname ?? '' }} {{ $request->focalPerson->lname ?? '' }}
                            </td>
                            <td class="px-4 py-3">{{ $request->program->prcode ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $request->respocenter->description ?? 'N/A' }} - {{ $request->respocenter->rccode ?? 'N/A' }}</td>
                            <td class="px-4 py-3">₱{{ number_format($request->amount, 2) }}</td>
                            <td class="px-4 py-3">
                                {{ $request->accountingUnit->fname ?? '' }} {{ $request->accountingUnit->lname ?? '' }}
                            </td>
                            <td class="px-4 py-3">{{ $request->updated_at->format('M d, Y h:i A') }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $lastLog = $request->logs()->latest()->first();
                                @endphp
                                {{ $lastLog->remarks ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                No verified DV requests yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <a href="{{ route('RegionalOffices.IMSD.index') }}" 
               class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg">
               ← Back to IMSD Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
