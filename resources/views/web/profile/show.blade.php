@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="max-w-4xl mx-auto p-6 bg-white rounded-xl shadow">
    <h2 class="text-2xl font-bold text-gray-700 mb-6">My Profile</h2>

    <!-- Profile Image -->
    <div class="flex items-center gap-6 mb-6">
        <img src="{{ $user->profile_image ? asset('storage/profiles/' . $user->profile_image) : asset('storage/profiles/default.png') }}"
             alt="Profile Image"
             class="w-24 h-24 rounded-full object-cover border">

        <div>
            <h3 class="text-xl font-semibold">{{ $user->fname }} {{ $user->lname }}</h3>
            <p class="text-gray-600">{{ $user->email }}</p>
        </div>
    </div>

    <!-- Profile Details -->
    <div class="space-y-4">
        <div>
            <span class="font-semibold">Full Name:</span> {{ $user->fname }} {{ $user->mi }} {{ $user->lname }}
        </div>
        <div>
            <span class="font-semibold">Email:</span> {{ $user->email }}
        </div>
           <div>
            <span class="font-semibold">Phone Number:</span> {{ $user->phone }}
        </div>
        <div>
            <span class="font-semibold">Role:</span> Accounting Unit
        </div>
    </div>

    <!-- Edit Profile Button -->
    <div class="mt-6">
        <a href="{{ route('web.profile.edit') }}"
           class="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700">
           Edit Profile
        </a>
    </div>
</div>
@endsection
