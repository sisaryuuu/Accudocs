@extends('layouts.app')

@section('title', 'Verify DV Request')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Section - IMSD Verification Form -->
        <div class="lg:col-span-2 bg-white shadow rounded-xl p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">IMSD Verification</h1>

            <form action="{{ route('IMSD.dvrequests.verify', $dvRequest->id) }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Control No.</label>
                        <input type="text" value="{{ $dvRequest->id }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name of Payee</label>
                        <input type="text" value="{{ $dvRequest->focalPerson->fname ?? '' }} {{ $dvRequest->focalPerson->lname ?? '' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Program</label>
                        <input type="text" value="{{ $dvRequest->program->prcode ?? 'N/A' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Particulars</label>
                    <textarea class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" rows="2" readonly>{{ $dvRequest->particulars ?? '' }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="text" value="₱{{ number_format($dvRequest->amount, 2) }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Remarks / Lacking</label>
                        <textarea id="remarks" name="remarks" rows="3" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm" placeholder="Enter remarks or notes..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Verify
                    </button>
                </div>
       

            </form>
                     <a href="{{ route('RegionalOffices.IMSD.index') }}">
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                        Cancel
                    </button>
                </div>
                </a>
        </div>
<!-- Sidebar: Recent Submissions -->
<div class="lg:col-span-1">
    <div class="bg-white shadow-md rounded-xl p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">📑 Recent Submissions</h2>
        <ul class="space-y-3">
            @forelse($recentDvRequests as $recent)
                @if($recent->focalPerson) {{-- Ensure focal person exists --}}
                    <li>
                        <a href="{{ route('RegionalOffices.IMSD.verifydv', $recent->id) }}" 
                           class="block p-3 bg-gray-50 hover:bg-blue-50 rounded-lg border border-gray-200 transition">
                            <p class="text-sm font-medium text-gray-900">
                                Control #{{ $recent->id }} • {{ $recent->particulars ?? 'No Particulars' }}
                            </p>
                            <p class="text-sm text-gray-900">
                                {{ $recent->focalPerson->fname }} {{ $recent->focalPerson->lname }} • 
                                {{ $recent->created_at->format('M d, Y') }}
                            </p>
                        </a>
                    </li>
                @endif
            @empty
                <li class="text-sm text-gray-500">No recent submissions.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
