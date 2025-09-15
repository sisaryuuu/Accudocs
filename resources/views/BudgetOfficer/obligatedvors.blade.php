@extends('layouts.app')

@section('title', 'Obligate DV Request')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Section - ORS Processing -->
        <div class="lg:col-span-2 bg-white shadow rounded-xl p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Budget Officer</h1>

            <form action="{{ route('BudgetOfficer.dvrequests.store', $dvRequest->id) }}" method="POST" class="space-y-6">
                @csrf

                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Control No.</label>
                        <input type="text" value="{{ $dvRequest->id ?? '' }}" 
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name of Payee</label>
                        <input type="text" value="{{ $dvRequest->focalPerson->fname ?? '' }} {{ $dvRequest->focalPerson->lname ?? '' }}"
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Responsibility Center</label>
                        <p class="mt-1 text-sm text-gray-900">
                          {{ $dvRequest->respocenter->rccode ?? '' }} {{ $dvRequest->respocenter->description ?? '' }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Particulars</label>
                    <textarea rows="2" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>{{ $dvRequest->particulars ?? '' }}</textarea>
                </div>

                <!-- Amounts -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="text" value="₱{{ number_format($dvRequest->amount, 2) }}" 
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gross</label>
                        <input type="text" name="gross" 
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Net</label>
                        <input type="text" name="net"
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                </div>

                <!-- ORS Classification Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">UACS Code</label>
                        <select name="uacscode_id" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            <option value="">-- Select UACS Code --</option>
                            @foreach($uacsCodes as $uacs)
                                <option value="{{ $uacs->id }}">{{ $uacs->code }} - {{ $uacs->description }}</option>
                            @endforeach
                        </select>
                    </div>
                   <div>
                        <label class="block text-sm font-medium text-gray-700">ADL Code</label>
                        <input list="adl_codes" name="adl_code" id="adl_code"
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm"
                            placeholder="Type or select ADL Code...">

                        <datalist id="adl_codes">
                            @foreach($adlCodes as $adl)
                                <option value="{{ $adl->code }}">{{ $adl->description }}</option>
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Allotment Class</label>
                        <select name="allotmentclass_id" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            <option value="">-- Select Allotment Class --</option>
                            @foreach($allotment_classes as $allot)
                                <option value="{{ $allot->id }}">{{ $allot->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">MFO/PAP</label>
                        <select name="mfopap_id" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            <option value="">-- Select MFO/PAP --</option>
                            @foreach($mfopaps as $mfo)
                                <option value="{{ $mfo->id }}">{{ $mfo->code }} - {{ $mfo->description }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Continuing/Current</label>
                    <select name="continuingorcurrent_id" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        <option value="">-- Select Type --</option>
                        @foreach($contcurrents as $cc)
                            <option value="{{ $cc->id }}">{{ $cc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Received Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Received By</label>
                        <input type="text" value="{{ auth()->user()->fname ?? '' }} {{ auth()->user()->lname ?? '' }}" 
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Received At</label>
                        <input type="text"  value="{{ $dvRequest->created_at ? $dvRequest->created_at->format('Y-m-d  H:i')  : '' }}"
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                </div>

                <!-- Remarks -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Remarks / Notes</label>
                    <textarea id="remarks" name="remarks" rows="3" 
                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm"
                              placeholder="Enter remarks or notes...">{{ old('remarks') }}</textarea>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Obligate
                    </button>
                    <a href="{{ route('BudgetOfficer.dvrequests.index') }}" 
                       class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow-md rounded-xl p-6">
                                <ul id="recent-requests" class="space-y-3">
                        <li class="text-sm text-gray-500">Loading...</li>
                    </ul>
            </div>
        </div>

    </div>
</div>

@endsection
<script>
async function loadRecentRequests() {
    try {
        const res = await fetch("{{ route('BudgetOfficer.dvrequests.recent') }}");
        const data = await res.json();
        console.log(data); // 👀 debug output

        let html = '';
        if (data.length === 0) {
            html = `<li class="text-sm text-gray-500">No recent requests.</li>`;
        } else {
            data.forEach(r => {
                html += `
                    <li>
                        <a href="/BudgetOfficer/dvrequests/${r.id}" 
                           class="block p-3 bg-gray-50 hover:bg-blue-50 rounded-lg border border-gray-200 transition">
                            <p class="text-sm font-medium text-gray-900">
                                Control #${r.id} • ${r.particulars ?? ''}
                            </p>
                            <p class="text-sm text-gray-600">
                                ${(r.focal_person?.fname ?? 'Unknown')} ${(r.focal_person?.lname ?? '')} • 
                                ${new Date(r.created_at).toLocaleDateString()}
                            </p>
                        </a>
                    </li>`;
            });
        }
        document.getElementById('recent-requests').innerHTML = html;
    } catch (e) {
        console.error("Failed to load recent requests", e);
    }
}
setInterval(loadRecentRequests, 5000);
loadRecentRequests();
</script>