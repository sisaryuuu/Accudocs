@extends('layouts.app')

@section('title', 'ORS Log History')

@section('content')
<div class="max-w-7xl mx-auto bg-white shadow rounded-xl p-6">
    <h1 class="text-xl font-bold mb-4">ORS & DV Log History</h1>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">ORS Number</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">DV Number</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Payee</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Program</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Resp. Center</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Status</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($dvRequests as $req)
                    <tr>
                        <td class="px-4 py-2">
                            {{ $req->orsRequest->ors_number ?? '—' }}
                        </td>
                        <td class="px-4 py-2">
                            {{ $req->dv_number ?? '—' }}
                        </td>
                        <td class="px-4 py-2">
                            {{ optional($req->focalPerson)->fname }} {{ optional($req->focalPerson)->lname }}
                        </td>
                        <td class="px-4 py-2">
                            {{ $req->program->prcode ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-2">
                            @if($req->respocenter)
                                {{ $req->respocenter->rccode }} - {{ $req->respocenter->description }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 text-xs rounded-lg bg-green-100 text-green-800">
                                {{ $req->status }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-600">
                            {{ $req->updated_at->format('M d, Y h:i A') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                            No finalized DV requests yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
