@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="max-w-2xl mx-auto p-8 bg-white rounded-2xl shadow-lg border border-gray-100">
    <!-- Header -->
    <div class="mb-8 text-center">
        <h2 class="text-3xl font-bold text-gray-800">Edit Profile</h2>
        <p class="text-gray-500 mt-2">Update your personal information</p>
    </div>

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-xl border border-red-200">
            <div class="flex items-start">
                <svg class="w-5 h-5 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h3 class="font-semibold">Please fix the following errors:</h3>
                    <ul class="mt-1 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Form -->
    <form method="POST" action="{{ route('FocalPerson.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Personal Information Section -->
        <div class="bg-gray-50 p-6 rounded-xl">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Personal Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- First Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                    <input type="text" name="fname" value="{{ old('fname', $user->fname) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <!-- Middle Initial -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                    <input type="text" name="mi" value="{{ old('mi', $user->mi) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <!-- Last Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                    <input type="text" name="lname" value="{{ old('lname', $user->lname) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>
            </div>
        </div>

        <!-- Contact Information Section -->
        <div class="bg-gray-50 p-6 rounded-xl">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Contact Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <!-- Phone Number -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="tel" name="contactnumber" value="{{ old('contactnumber', $user->contactnumber) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>
            </div>
        </div>

        <!-- Security Section -->
        <div class="bg-gray-50 p-6 rounded-xl">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Security</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="password" name="password"  placeholder="Leave blank to keep current"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>
                  <p id="password-error" class="text-red-500 text-sm mt-2 hidden">Passwords do not match.</p>
            </div>
        </div>

        <!-- Profile Image Section -->
        <div class="bg-gray-50 p-6 rounded-xl">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Profile Image</h3>
            
            <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload New Image</label>
                    <input type="file" name="profile_image" accept="image/*"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                
                @if($user->profile_image)
                    <div class="flex flex-col items-center">
                        <p class="text-sm text-gray-500 mb-2">Current Image</p>
                        <img src="{{ asset('storage/profiles/' . $user->profile_image) }}"
                             class="w-20 h-20 rounded-full object-cover border-2 border-gray-200 shadow-sm">
                    </div>
                @endif
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-between items-center pt-4">
            <a href="{{ url()->previous() }}" 
               class="px-6 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                Save Changes
            </button>
        </div>
      
    </form>
   
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