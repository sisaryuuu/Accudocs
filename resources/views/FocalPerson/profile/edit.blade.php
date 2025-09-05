@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="max-w-4xl mx-auto p-6 bg-white rounded-xl shadow">
    <h2 class="text-2xl font-bold text-gray-700 mb-6">Edit Profile</h2>

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form -->
    <form method="POST" action="{{ route('FocalPerson.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- First Name -->
        <div>
            <label class="block font-semibold text-gray-700">First Name</label>
            <input type="text" name="fname" value="{{ old('fname', $user->fname) }}"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Middle Initial -->
        <div>
            <label class="block font-semibold text-gray-700">Middle Initial</label>
            <input type="text" name="mi" value="{{ old('mi', $user->mi) }}"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Last Name -->
        <div>
            <label class="block font-semibold text-gray-700">Last Name</label>
            <input type="text" name="lname" value="{{ old('lname', $user->lname) }}"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Email -->
        <div>
            <label class="block font-semibold text-gray-700">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Password -->
        <div>
            <label class="block font-semibold text-gray-700">Password (leave blank to keep current)</label>
            <input type="password" name="password"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Confirm Password -->
        <div>
            <label class="block font-semibold text-gray-700">Confirm Password</label>
            <input type="password" name="password_confirmation"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Profile Image -->
        <div>
            <label class="block font-semibold text-gray-700">Profile Image</label>
            <input type="file" name="profile_image" accept="image/*"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">

            @if($user->profile_image)
                <div class="mt-3">
                    <img src="{{ asset('storage/profiles/' . $user->profile_image) }}"
                         class="w-24 h-24 rounded-full object-cover border">
                </div>
            @endif
        </div>

        <!-- Save Button -->
        <div>
            <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow hover:bg-blue-700">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
