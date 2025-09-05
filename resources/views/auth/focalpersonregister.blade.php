@extends('layouts.guest')

@section('title', 'Register Focal Person')

@section('content')
<div class="flex items-center justify-center h-screen">
    <div class="max-w-lg mx-auto bg-white p-8 rounded-xl shadow">
        <h2 class="text-2xl font-bold mb-6 text-gray-700">Register Focal Person</h2>
        <!-- form here -->
        <!-- Show Global Validation Errors -->
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                <ul class="list-disc pl-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('focalperson.register') }}">
            @csrf

            <!-- First Name -->
            <div class="mb-4">
                <label class="block text-gray-700">First Name</label>
                <input type="text" name="fname" value="{{ old('fname') }}"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200 @error('fname') border-red-500 @enderror">
                @error('fname')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Middle Initial -->
            <div class="mb-4">
                <label class="block text-gray-700">Middle Initial</label>
                <input type="text" name="mi" value="{{ old('mi') }}"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200 @error('mi') border-red-500 @enderror">
                @error('mi')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Last Name -->
            <div class="mb-4">
                <label class="block text-gray-700">Last Name</label>
                <input type="text" name="lname" value="{{ old('lname') }}"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200 @error('lname') border-red-500 @enderror">
                @error('lname')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- TIN Number -->
            <div class="mb-4">
                <label class="block text-gray-700">TIN Number</label>
                <input type="text" name="TIN_number" value="{{ old('TIN_number') }}"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200 @error('TIN_number') border-red-500 @enderror">
                @error('TIN_number')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Address -->
            <div class="mb-4">
                <label class="block text-gray-700">Address</label>
                <input type="text" name="Address" value="{{ old('Address') }}"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200 @error('Address') border-red-500 @enderror">
                @error('Address')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div class="mb-4">
                <label class="block text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200 @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Contact Number -->
            <div class="mb-4">
                <label class="block text-gray-700">Contact Number</label>
                <input type="text" name="contactnumber" value="{{ old('contactnumber') }}"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200 @error('contactnumber') border-red-500 @enderror">
                @error('contactnumber')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Program -->
            <div class="mb-4">
                <label class="block text-gray-700">Program</label>
                <select name="program_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200">

                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" {{ old('program_id') == $program->id ? 'selected' : '' }}>
                            {{ $program->prcode }} - {{ $program->description }}
                        </option>
                    @endforeach
                </select>
                @error('program_id')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Responsibility Center -->
            <div class="mb-4">
                <label class="block text-gray-700">Responsibility Center</label>
                <select name="respocenter_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200">
        
                    @foreach ($responCenters as $responCenter)
                        <option value="{{ $responCenter->id }}" {{ old('respocenter_id') == $responCenter->id ? 'selected' : '' }}>
                            {{ $responCenter->rccode }} - {{ $responCenter->description }}
                        </option>
                    @endforeach
                </select>
                @error('respocenter_id')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label class="block text-gray-700">Password</label>
                <input type="password" name="password"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200 @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="mb-6">
                <label class="block text-gray-700">Confirm Password</label>
                <input type="password" name="password_confirmation"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200">
            </div>

            <!-- Submit Button -->
          <!-- Submit + Cancel Buttons -->
<div class="flex justify-center space-x-4">
    <!-- Register -->
    <button type="submit"
        class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow hover:bg-blue-700">
        Register
    </button>

    <!-- Cancel -->
    <a href="{{ route('login') }}">
        <button type="button"
            class="px-6 py-2 bg-gray-500 text-white font-semibold rounded-lg shadow hover:bg-gray-600">
            Cancel
        </button>
    </a>
</div>
    </div>
</div>
@endsection
