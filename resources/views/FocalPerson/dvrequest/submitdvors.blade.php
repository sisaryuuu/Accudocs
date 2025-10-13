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
        <form action="{{ route('FocalPerson.dvrequest.store') }}" method="POST" class="space-y-6" id="dv-request-form">
            @csrf

            <!-- Mode of Payment -->
            <div class="border rounded-lg p-4 bg-gray-50">
                <label class="block text-sm font-medium text-gray-700 mb-3">Mode of Payment</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach($mops as $mop)
                    <div class="border p-3 rounded-lg bg-white">
                        <label class="inline-flex items-center">
                            <input type="radio" 
                                    name="mode_of_payment_id" 
                                    value="{{ $mop->id }}" required 
                                    class="mop-radio rounded text-green-600 focus:ring-green-500">
                            <span class="ml-2 font-medium text-gray-700">{{ $mop->mopname }} </span>
                        </label>
                    </div>
                    @endforeach
                </div>

                <!-- Payment Details (appears when mode of payment is selected) -->
                <div id="payment-details" class="hidden mt-4 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="bank_name" class="block text-sm font-medium text-gray-700">Bank Name</label>
                            <input type="text" 
                                name="payment_details[bank_name]" 
                                placeholder="Bank / Account Name"
                                class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                        </div>
                        
                        <div>
                            <label for="bank_accnum" class="block text-sm font-medium text-gray-700">Bank Account Number</label>
                            <input type="text" 
                                name="payment_details[bank_accnum]" 
                                placeholder="Account Number"
                                class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                        </div>
                    
                        <div>
                            <label for="bank_address" class="block text-sm font-medium text-gray-700">Bank Address</label>
                            <input type="text" 
                                name="payment_details[bank_address]" 
                                placeholder="Bank Address / Check No."
                                class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
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
            <select id="program" name="program_id"  required 
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

                <!-- Amount -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                    <input type="number" step="0.01" id="amount" name="amount" 
                           value="{{ old('amount') }}" required
                           class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end">
                <a href="{{ route('FocalPerson.dvrequest.index') }}" 
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition mr-3">
                    Cancel
                </a>
                <button type="submit" id="submit-btn"
                        class="bg-gradient-to-r from-green-400 to-green-600 text-white px-6 py-2 rounded-lg shadow hover:opacity-90 transition">
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="mode_of_payment_id"]');
    const paymentDetails = document.getElementById('payment-details');
    const form = document.getElementById('dv-request-form');
    const submitBtn = document.getElementById('submit-btn');
    
    // Show payment details when mode of payment is selected
    radios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.checked) {
                paymentDetails.classList.remove('hidden');
            }
        });
    });

    // Handle form submission with loading animation
    form.addEventListener('submit', function(e) {
        const originalText = submitBtn.innerHTML;

        // Add spinner + text
        submitBtn.innerHTML = `
            <div class="flex items-center justify-center space-x-2">
                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" 
                        d="M4 12a8 8 0 018-8V0C5.373 
                        0 0 5.373 0 12h4zm2 
                        5.291A7.962 7.962 0 014 
                        12H0c0 3.042 1.135 
                        5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span>Submitting & Generating Preview...</span>
            </div>
        `;

        // Disable the button
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');

        // Note: Form will actually submit, no need to restore button
        // The restore is just a safety fallback
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
        }, 15000); // 15 seconds fallback
    });
});
</script>
@endsection