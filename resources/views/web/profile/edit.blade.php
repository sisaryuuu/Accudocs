@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="max-w-5xl mx-auto py-10 px-6">
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-5 flex justify-between items-center">
            <h2 class="text-2xl font-semibold text-white">Edit Profile</h2>
            <a href="{{ url()->previous() }}"
               class="px-4 py-2 bg-white text-blue-700 font-medium rounded-lg shadow hover:bg-blue-100 transition">
               <i class="fa-solid fa-arrow-left mr-2"></i>Back
            </a>
        </div>

        <!-- Body -->
        <div class="p-8">
            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    <h3 class="font-semibold mb-2">Please fix the following:</h3>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('web.profile.update') }}" enctype="multipart/form-data" class="space-y-8">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- First Name -->
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">
                            <i class="fa-solid fa-user mr-1 text-blue-600"></i>First Name
                        </label>
                        <input type="text" name="fname" value="{{ old('fname', $user->fname) }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Middle Initial -->
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">
                            <i class="fa-solid fa-font mr-1 text-blue-600"></i>Middle Name
                        </label>
                        <input type="text" name="mi" value="{{ old('mi', $user->mi) }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">
                            <i class="fa-solid fa-user-tag mr-1 text-blue-600"></i>Last Name
                        </label>
                        <input type="text" name="lname" value="{{ old('lname', $user->lname) }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">
                            <i class="fa-solid fa-envelope mr-1 text-blue-600"></i>Email
                        </label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">
                            <i class="fa-solid fa-phone mr-1 text-blue-600"></i>Phone Number
                        </label>
                        <input type="number" name="phone" value="{{ old('phone', $user->phone) }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Password Section -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">
                            <i class="fa-solid fa-lock mr-1 text-blue-600"></i>Password 
                            <span class="text-gray-500 text-sm">(leave blank to keep current)</span>
                        </label>
                        <input type="password" name="password"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">
                            <i class="fa-solid fa-lock mr-1 text-blue-600"></i>Confirm Password
                        </label>
                        <input type="password" name="password_confirmation"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Profile Image -->
                <div>
                    <label class="block font-semibold text-gray-700 mb-2">
                        <i class="fa-solid fa-image mr-1 text-blue-600"></i>Profile Image
                    </label>
                    <div class="flex items-center gap-6">
                        <input type="file" name="profile_image" accept="image/*"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">

                        @if($user->profile_image)
                            <img src="{{ asset('storage/profiles/' . $user->profile_image) }}"
                                 class="w-20 h-20 rounded-full object-cover border shadow">
                        @endif
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end pt-4">
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow hover:bg-blue-700 transition">
                        <i class="fa-solid fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
