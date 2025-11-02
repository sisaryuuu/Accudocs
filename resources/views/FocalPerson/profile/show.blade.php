@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="max-w-4xl mx-auto mt-10 bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-200">
    
    <!-- Header Section (Plain Blue) -->
<div class="bg-gray-600 px-6 py-8 text-center text-white">
        <img src="{{ $user->profile_image ? asset('storage/profiles/' . $user->profile_image) : asset('storage/profiles/default.png') }}"
             alt="Profile Image"
             class="w-28 h-28 rounded-full object-cover border-4 border-white mx-auto mb-4 shadow-md">
        <h2 class="text-3xl font-bold tracking-wide">{{ $user->fname }} {{ $user->lname }}</h2>
        <p class="text-blue-100 mt-1">{{ $user->email }}</p>
    </div>

    <!-- Profile Info Section -->
    <div class="px-8 py-6 bg-gray-50">
        <h3 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Profile Details</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition">
                <p class="text-sm text-gray-500 mb-1">Full Name</p>
                <p class="font-medium text-gray-800">{{ $user->fname }} {{ $user->mi }} {{ $user->lname }}</p>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition">
                <p class="text-sm text-gray-500 mb-1">Email</p>
                <p class="font-medium text-gray-800">{{ $user->email }}</p>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition">
                <p class="text-sm text-gray-500 mb-1">Phone Number</p>
                <p class="font-medium text-gray-800">{{ $user->contactnumber }}</p>
            </div>

            @if($user->Address)
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition">
                <p class="text-sm text-gray-500 mb-1">Address</p>
                <p class="font-medium text-gray-800">{{ $user->Address }}</p>
            </div>
            @endif
        </div>

        <!-- Action Button -->
       <div class="mt-8 text-center">
    <a href="{{ route('FocalPerson.profile.edit') }}"
       class="inline-block px-6 py-2.5 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 hover:shadow-lg transition duration-200">
       ✏️ Edit Profile
    </a>
</div>
    </div>
</div>
@endsection
