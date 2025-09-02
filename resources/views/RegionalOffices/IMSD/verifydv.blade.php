@extends('layouts.app')

@section('title', 'Verify DV Request')

@section('content')
<div class="max-w-4xl mx-auto py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Verify DV Request #{{ $dvRequest->id }}</h1>

    <div class="bg-white shadow rounded-lg p-6 space-y-6">
        <!-- Request Details -->
        <div class="space-y-2">
            <p><strong>DV Number:</strong> {{ $dvRequest->dv_number ?? '—' }}</p>
            <p><strong>Submitted By:</strong> 
                {{ $dvRequest->focalPerson->fname ?? '' }} 
                {{ $dvRequest->focalPerson->mi ?? '' }} 
                {{ $dvRequest->focalPerson->lname ?? '' }}
            </p>
            <p><strong>Program:</strong> 
                {{ $dvRequest->program->prcode ?? 'N/A' }} - {{ $dvRequest->program->description ?? '' }}
            </p>
            <p><strong>Amount:</strong> ₱{{ number_format($dvRequest->amount, 2) }}</p>
            <p><strong>Particulars:</strong> {{ $dvRequest->particulars ?? '—' }}</p>
        </div>

        <!-- Verification Form -->
        <form action="{{ route('IMSD.dvrequests.verify', $dvRequest->id) }}" method="POST" class="space-y-4">
            @csrf
            <div class="flex space-x-4">
                <button type="submit" 
                        class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                    Verify Request
                </button>
                <a href="{{ route('RegionalOffices.IMSD.index') }}" 
                   class="inline-flex items-center px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg shadow-sm transition-colors duration-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
