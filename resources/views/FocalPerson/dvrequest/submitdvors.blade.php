@extends('layouts.app')

@section('title', 'Submit DV Request')

@section('content')
<div class="max-w-6xl mx-auto mt-8">
    <h2 class="text-2xl font-semibold mb-6">Submit DV Request</h2>

    <!-- Error messages -->
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc ml-6">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Success flash -->
    @if(session('success'))
        <div class="bg-green-100 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg p-6">
        <form action="{{ route('FocalPerson.dvrequest.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Top Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" 
                           value="{{ $focalPerson->fname }} {{ $focalPerson->mi }} {{ $focalPerson->lname }}" 
                           readonly
                           class="w-full mt-1 border rounded-lg px-3 py-2 bg-gray-100 text-gray-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">TIN Number</label>
                    <input type="text" 
                           value="{{ $focalPerson->TIN_number }}" 
                           readonly
                           class="w-full mt-1 border rounded-lg px-3 py-2 bg-gray-100 text-gray-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" 
                           value="{{ $focalPerson->Address }}" 
                           readonly
                           class="w-full mt-1 border rounded-lg px-3 py-2 bg-gray-100 text-gray-700">
                </div>
            </div>

            <!-- Responsibility + Program -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Responsibility Center</label>
                 <input type="text" 
                        value="{{ $focalPerson->respocenter->rccode }} - {{ $focalPerson->respocenter->description }}" 
                        readonly
                           class="w-full mt-1 border rounded-lg px-3 py-2 bg-gray-100 text-gray-700">
                           <input type="hidden" name="respocenter_id" value="{{ $focalPerson->respocenter_id }}">
                </div>
            <div>
            <label for="program" class="block text-sm font-medium text-gray-700">Purpose</label>
            <select id="program" name="program_id" 
                    class="w-full mt-1 border rounded-lg px-3 py-2 bg-white text-gray-700">
                <option value="">Select Purpose of Request</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}" 
                        {{ $focalPerson->program_id == $program->id ? 'selected' : '' }}>
                        {{ $program->prcode }} - {{ $program->description }}
                    </option>
                @endforeach
            </select>
            </div>
            </div>

            <!-- Middle Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Particulars -->
                <div>
                    <label for="particulars" class="block text-sm font-medium text-gray-700">Particulars</label>
                    <textarea id="particulars" name="particulars" rows="8" required
                              class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">{{ old('particulars') }}</textarea>
                </div>

                <!-- Amounts -->
                <div>
                    <div class="mb-4">
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="number" step="0.01" id="amount" name="amount" 
                               value="{{ old('amount') }}" required
                               class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div class="mb-4">
                        <label for="gross" class="block text-sm font-medium text-gray-700">Gross</label>
                        <input type="number" step="0.01" id="gross" name="gross" 
                               value="{{ old('gross') }}"
                               class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label for="net" class="block text-sm font-medium text-gray-700">Net</label>
                        <input type="number" step="0.01" id="net" name="net" 
                               value="{{ old('net') }}"
                               class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end">
                <a href="{{ route('FocalPerson.dvrequest.index') }}" 
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition mr-3">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-gradient-to-r from-green-400 to-green-600 text-white px-6 py-2 rounded-lg shadow hover:opacity-90 transition">
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
