@extends('layouts.app')

@section('title', 'Edit DV Request')

@section('content')
<div class="max-w-6xl mx-auto mt-8" x-data="paymentForm()">
    <h2 class="text-2xl font-semibold mb-6">Edit DV Request</h2>

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
        <form action="{{ route('FocalPerson.dvrequest.update', $dvRequest->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" 
                           value="{{ $dvRequest->focalPerson->fname }} {{ $dvRequest->focalPerson->mi }} {{ $dvRequest->focalPerson->lname }}" 
                           readonly
                           class="w-full mt-1 border rounded-lg px-3 py-2 bg-gray-100 text-gray-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">TIN Number</label>
                    <input type="text" 
                           value="{{ $dvRequest->focalPerson->TIN_number }}" 
                           readonly
                           class="w-full mt-1 border rounded-lg px-3 py-2 bg-gray-100 text-gray-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" 
                           value="{{ $dvRequest->focalPerson->Address }}" 
                           readonly
                           class="w-full mt-1 border rounded-lg px-3 py-2 bg-gray-100 text-gray-700">
                </div>
            </div>

            <!-- Responsibility + Program -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Responsibility Center</label>
                    <input type="text" 
                           value="{{ $dvRequest->respocenter->rccode }} - {{ $dvRequest->respocenter->description }}" 
                           readonly
                           class="w-full mt-1 border rounded-lg px-3 py-2 bg-gray-100 text-gray-700">
                    <input type="hidden" name="respocenter_id" value="{{ $dvRequest->respocenter_id }}">
                </div>
                <div>
                    <label for="program" class="block text-sm font-medium text-gray-700">Program</label>
                    <input type="text" id="program" 
                           value="{{ $dvRequest->program->prcode }} - {{ $dvRequest->program->description }}" 
                           readonly
                           class="w-full mt-1 border rounded-lg px-3 py-2 bg-gray-100 text-gray-700">
                    <input type="hidden" name="program_id" value="{{ $dvRequest->program_id }}">
                </div>
            </div>

            <!-- Particulars + Amounts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Particulars -->
                <div>
                    <label for="particulars" class="block text-sm font-medium text-gray-700">Particulars</label>
                    <textarea id="particulars" name="particulars" rows="8" required
                              class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">{{ old('particulars', $dvRequest->particulars) }}</textarea>
                </div>

                <!-- Amounts -->
                <div>
                    <div class="mb-4">
                        <label for="amount" class="block text-sm font-medium text-gray-700">Gross Amount</label>
                        <input type="number" step="0.01" id="amount" name="amount" 
                               value="{{ old('amount', $dvRequest->amount) }}" required
                               class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                    </div>


            <!-- Mode of Payment -->
            <div>
                <label for="mode_of_payment_id" class="block text-sm font-medium text-gray-700">Mode of Payment</label>
                <select id="mode_of_payment_id" name="mode_of_payment_id" 
                        x-model="selectedMode"
                        class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400" required>
                    <option value="">-- Select Mode of Payment --</option>
                    @foreach(\App\Models\ModeOfPayment::all() as $mode)
                        <option value="{{ $mode->id }}"
                            {{ old('mode_of_payment_id', $dvRequest->modeOfPayments->first()->id ?? '') == $mode->id ? 'selected' : '' }}>
                            {{ $mode->mopname }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Bank Details -->
            <div x-show="showBankFields" x-transition class="mt-4 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                    <input type="text" name="payment_details[bank_name]" 
                           value="{{ old('payment_details.bank_name', $dvRequest->modeOfPayments->first()->paymentDetail->bank_name ?? '') }}"
                           class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bank Account Number</label>
                    <input type="text" name="payment_details[bank_accnum]" 
                           value="{{ old('payment_details.bank_accnum', $dvRequest->modeOfPayments->first()->paymentDetail->bank_accnum ?? '') }}"
                           class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bank Address</label>
                    <input type="text" name="payment_details[bank_address]" 
                           value="{{ old('payment_details.bank_address', $dvRequest->modeOfPayments->first()->paymentDetail->bank_address ?? '') }}"
                           class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end mt-6">
                <a href="{{ route('FocalPerson.dvrequest.index') }}" 
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition mr-3">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-gradient-to-r from-blue-400 to-blue-600 text-white px-6 py-2 rounded-lg shadow hover:opacity-90 transition">
                    Update Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function paymentForm() {
        return {
            selectedMode: '{{ old('mode_of_payment_id', $dvRequest->modeOfPayments->first()->id ?? '') }}',
            showBankFields: false,
            init() {
                this.toggleBankFields();
                this.$watch('selectedMode', () => this.toggleBankFields());
            },
            toggleBankFields() {
                // You can customize this logic — adjust the name check to your actual mode name in DB
                const bankModes = ['bank transfer', 'deposit', 'check'];
                const selectedText = document.querySelector('#mode_of_payment_id')?.selectedOptions[0]?.text?.toLowerCase() || '';
                this.showBankFields = bankModes.some(mode => selectedText.includes(mode));
            }
        }
    }
</script>
@endsection
