@extends('layouts.guest')

@section('title', 'Register Focal Person')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-blue-100 p-6">
    <div class="w-full max-w-2xl bg-white shadow-xl rounded-2xl p-10 border border-gray-100">
        <!-- Header -->
        <div class="text-center mb-10">
            <img src="{{ asset('storage/logo/accudocslogo.png') }}" 
                 alt="Accudocs Logo" 
                 class="w-56 mx-auto mb-4 drop-shadow-lg">
            <h2 class="text-4xl font-extrabold text-gray-800 tracking-tight">Register Focal Person</h2>
            <p class="text-gray-500 text-base mt-2">Fill out the form below to create your account</p>
        </div>

        <!-- Show Validation Errors -->
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                <ul class="list-disc pl-5 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('focalperson.register') }}" class="space-y-6">
            @csrf

            <!-- Grid Layout for Inputs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- First Name -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1">First Name</label>
                    <input type="text" name="fname" placeholder="Enter your first name" value="{{ old('fname') }}"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm">
                </div>

                            <!-- Middle Name -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Middle Name</label>
                    <input type="text" name="mi" placeholder="Enter your middle name" value="{{ old('mi') }}"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm">
                </div>
                <!-- Last Name -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Last Name</label>
                    <input type="text" name="lname" placeholder="Enter your last name" value="{{ old('lname') }}"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm">
                </div>

                <!-- TIN Number -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1">TIN Number</label>
                    <input type="text" name="TIN_number" placeholder="000-000-000" value="{{ old('TIN_number') }}"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm">
                </div>
            </div>

            <!-- Address -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Address</label>
                <input type="text" name="Address" placeholder="Enter your address" value="{{ old('Address') }}"
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm">
            </div>

            <!-- Email -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Email</label>
                <input type="email" name="email" placeholder="Enter your email" value="{{ old('email') }}"
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm">
            </div>

           <!-- Contact Number -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Contact Number</label>
                    <input type="text" name="contactnumber" placeholder="09XXXXXXXXX" value="{{ old('contactnumber') }}"
                        maxlength="11" pattern="[0-9]{11}"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm">
                    <p class="text-gray-400 text-xs mt-1">Must be exactly 11 digits (e.g., 09123456789)</p>
                </div>


            <!-- Responsibility Center -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Responsibility Center</label>
                <select name="respocenter_id"
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm">
                    <option value="">Select Responsibility Center</option>
                    @foreach ($responCenters as $responCenter)
                        <option value="{{ $responCenter->id }}" {{ old('respocenter_id') == $responCenter->id ? 'selected' : '' }}>
                            {{ $responCenter->rccode }} - {{ $responCenter->description }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Password -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Password -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password"
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-4">
            <!-- Confirm Password -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                    placeholder="Re-enter your password"
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm">
                <!-- Error message -->
                <p id="password-error" class="text-red-500 text-sm mt-2 hidden">Passwords do not match.</p>
            </div>
        </div>
            <!-- Buttons -->
            <div class="flex justify-center space-x-4 pt-6">
                <button type="submit"
                    class="px-8 py-2.5 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition transform hover:-translate-y-0.5">
                    Register
                </button>

                <a href="{{ route('login') }}">
                    <button type="button"
                        class="px-8 py-2.5 bg-gray-500 text-white font-semibold rounded-lg shadow-md hover:bg-gray-600 transition transform hover:-translate-y-0.5">
                        Cancel
                    </button>
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const password = document.getElementById('password');
    const confirm = document.getElementById('password_confirmation');
    const errorMsg = document.getElementById('password-error');

    function validatePassword() {
        if (confirm.value && password.value !== confirm.value) {
            confirm.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-200');
            confirm.classList.remove('border-gray-300', 'focus:border-blue-400', 'focus:ring-blue-200');
            errorMsg.classList.remove('hidden');
        } else {
            confirm.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-200');
            confirm.classList.add('border-gray-300', 'focus:border-blue-400', 'focus:ring-blue-200');
            errorMsg.classList.add('hidden');
        }
    }

    password.addEventListener('input', validatePassword);
    confirm.addEventListener('input', validatePassword);
});
</script>
@endsection
