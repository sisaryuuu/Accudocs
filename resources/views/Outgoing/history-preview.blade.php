.@extends('layouts.app')

@section('title', 'DV Request Preview - History')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="bg-white shadow rounded-xl p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">📄 DV Request Preview</h1>
            <div class="text-sm">
                <span class="px-3 py-1 rounded-full {{ $dvRequest->status === 'Outgoing_finalized' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ $dvRequest->status }}
                </span>
            </div>
        </div>

        @if($warning)
            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-yellow-800">{{ $warning }}</span>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">DV Number</label>
                    <p class="mt-1 text-lg font-semibold text-gray-900">
                        {{ $dvRequest->dv_number ?? 'Not assigned' }}
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Program</label>
                    <p class="mt-1 text-gray-900">{{ $dvRequest->program->prcode ?? 'N/A' }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Payee</label>
                    <p class="mt-1 text-gray-900">
                        {{ $dvRequest->focalPerson->fname ?? '' }} {{ $dvRequest->focalPerson->lname ?? '' }}
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Amount</label>
                    <p class="mt-1 text-lg font-semibold text-green-600">
                        ₱{{ number_format($dvRequest->amount, 2) }}
                    </p>
                </div>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Responsibility Center</label>
                    <p class="mt-1 text-gray-900">
                        {{ $dvRequest->respocenter->rccode ?? '' }} - {{ $dvRequest->respocenter->description ?? '' }}
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">ORS Number</label>
                    <p class="mt-1 text-gray-900">{{ $dvRequest->orsRequest->ors_number ?? 'N/A' }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Created Date</label>
                    <p class="mt-1 text-gray-900">{{ $dvRequest->created_at->format('M d, Y h:i A') }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Purpose</label>
                    <p class="mt-1 text-gray-900">{{ $dvRequest->purpose ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        @if($dvRequest->status === 'Outgoing_finalized')
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="flex gap-4">
                    <a href="{{ route('Outgoing.dvrequests.preview', $dvRequest->id) }}" 
                       class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200">
                       View Full DV Document
                    </a>
                    
                    <a href="{{ route('Outgoing.dvrequests.generatePdf', $dvRequest->id) }}" 
                       class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200">
                       Download PDF
                    </a>
                </div>
            </div>
        @endif

        <div class="mt-6 pt-6 border-t border-gray-200">
            <a href="{{ route('Outgoing.dvrequests.dvloghistory') }}" 
               class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-200">
               ← Back to Verification History
            </a>
        </div>
    </div>
</div>
@endsection