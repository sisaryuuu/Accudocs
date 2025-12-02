@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="max-w-5xl mx-auto py-10 px-6">
    <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-5 flex justify-between items-center">
            <h2 class="text-2xl font-semibold text-white">My Profile</h2>
            <a href="{{ route('web.profile.edit') }}"
               class="px-4 py-2 bg-white text-blue-700 font-medium rounded-lg shadow hover:bg-blue-100 transition">
               <i class="fa-solid fa-pen mr-2"></i>Edit Profile
            </a>
        </div>

        <!-- Body -->
        <div class="p-8 grid md:grid-cols-3 gap-8">
            <!-- Left: Profile Image -->
            <div class="flex flex-col items-center text-center">
                <div class="relative">
                    <img src="{{ $user->profile_image ? asset('storage/profiles/' . $user->profile_image) : asset('storage/profiles/default.png') }}"
                         alt="Profile Image"
                         class="w-36 h-36 rounded-full object-cover border-4 border-blue-200 shadow-lg">
                    <div class="absolute bottom-2 right-2 bg-blue-600 text-white p-2 rounded-full">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mt-4">
                    {{ $user->fname }} {{ $user->lname }}
                </h3>
                <p class="text-gray-500 text-sm">{{ $user->email }}</p>
                
                <!-- Spatie Role Badge -->
                @if($user->roles->count() > 0)
                    <div class="mt-3">
                        @foreach($user->roles as $role)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                @if($role->name === 'admin') bg-red-100 text-red-800
                                @elseif($role->name === 'accounting') bg-blue-100 text-blue-800
                                @elseif($role->name === 'user') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800 @endif">
                                <i class="fa-solid fa-user-shield mr-1"></i>
                                {{ ucfirst($role->name) }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 mt-3">
                        <i class="fa-solid fa-user mr-1"></i>
                        No Role Assigned
                    </span>
                @endif
            </div>

            <!-- Right: Profile Details -->
            <div class="md:col-span-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="bg-gray-50 p-4 rounded-lg border hover:shadow-md transition">
                        <span class="block text-sm text-gray-500">Full Name</span>
                        <span class="text-lg font-medium text-gray-800">
                            {{ $user->fname }} {{ $user->mi }} {{ $user->lname }}
                        </span>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border hover:shadow-md transition">
                        <span class="block text-sm text-gray-500">Email</span>
                        <span class="text-lg font-medium text-gray-800">
                            {{ $user->email }}
                        </span>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border hover:shadow-md transition">
                        <span class="block text-sm text-gray-500">Phone Number</span>
                        <span class="text-lg font-medium text-gray-800">
                            {{ $user->phone ?? '—' }}
                        </span>
                    </div>

                    <!-- Spatie Roles Display -->
                    <div class="bg-gray-50 p-4 rounded-lg border hover:shadow-md transition">
                        <span class="block text-sm text-gray-500">Roles</span>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse($user->roles as $role)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    <i class="fa-solid fa-user-shield mr-1 text-xs"></i>
                                    {{ ucfirst($role->name) }}
                                </span>
                            @empty
                                <span class="text-gray-500 text-sm">No roles assigned</span>
                            @endforelse
                        </div>
                    </div>

                    <!-- Spatie Permissions Display -->
                    @if($user->getAllPermissions()->count() > 0)
                    <div class="bg-gray-50 p-4 rounded-lg border hover:shadow-md transition sm:col-span-2">
                        <span class="block text-sm text-gray-500 mb-2">Permissions</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach($user->getAllPermissions() as $permission)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    <i class="fa-solid fa-key mr-1 text-xs"></i>
                                    {{ $permission->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection