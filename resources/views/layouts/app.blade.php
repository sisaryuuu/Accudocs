<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <title>@yield('title', 'Dashboard')</title>
</head>
<body class="bg-gray-100 font-sans">

<!-- Top Navbar -->
<nav class="bg-white shadow-md fixed w-full z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            <!-- Left Section -->
            <div class="flex items-center">
                <span class="text-xl font-bold text-gray-800">My App</span>
            </div>

            <!-- Center Section (Focal Person Links) -->
            @role('Focal_Person')
            <div class="flex space-x-6">
                <a href="{{ route('FocalPerson.dvrequest.index') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('FocalPerson.dvrequest.index') ? 'bg-gray-200 font-semibold' : '' }}">
                    My Documents
                </a>
                <a href="{{ route('FocalPerson.dvrequest.submitdvors') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('FocalPerson.dvrequest.submitdvors') ? 'bg-gray-200 font-semibold' : '' }}">
                    Submit Document
                </a>
            </div>
            @endrole
            <!-- Right Section -->
            <div class="flex items-center space-x-4">
                @php    
                    // Check if user is logged in as AccountingUnit or FocalPerson
                    $authUser = Auth::user() ?? Auth::guard('focal')->user();
                @endphp
                <span class="text-gray-600">Hello, {{ $authUser->lname ?? 'Guest' }}</span>

                <!-- Profile Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                       <img src="{{ $authUser?->profile_image_url }}" 
                        alt="Profile Image" 
                        class="h-12 w-12 rounded-full border object-cover">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2">
                        <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile</a>
                        <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Settings</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</nav>

<!-- Spacer to prevent overlap -->
<div class="h-16"></div> 

<!-- Page Layout -->
<div class="flex">
    @hasanyrole('Admin|Accounting_III')
    <!-- Sidebar -->
    @php
        $userMgmtOpen = request()->routeIs('admin.accountingunits.*') || request()->routeIs('admin.roles.*');
    @endphp
    <aside x-data="{ userMgmtOpen: {{ $userMgmtOpen ? 'true' : 'false' }} }"
           class="w-64 bg-white shadow-lg flex-shrink-0 h-[calc(100vh-4rem)]"> <!-- height excludes navbar -->
        <div class="p-6">
            <h1 class="text-xl font-semibold text-gray-700">Navigation</h1>
        </div>

        <nav class="px-4 space-y-1">
            <a href="{{ route('Admin.dashboard') }}" 
               class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Admin.dashboard') ? 'bg-gray-200 font-semibold' : '' }}">
                Dashboard
            </a>

            <button @click="userMgmtOpen = !userMgmtOpen" 
                    class="w-full flex justify-between items-center py-2 px-4 rounded hover:bg-gray-200 focus:outline-none">
                <span>User Management</span>
                <svg :class="{'rotate-90': userMgmtOpen}" class="h-4 w-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="userMgmtOpen" x-transition class="pl-6 mt-1 space-y-1">
                <a href="{{ route('admin.accountingunits.index') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('admin.accountingunits.*') ? 'bg-gray-200 font-semibold' : '' }}">
                    Accounting Units
                </a>
                @role('Admin')
                <div x-data="{ roleMgmtOpen: {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'true' : 'false' }} }">
                    <button @click="roleMgmtOpen = !roleMgmtOpen" 
                            class="w-full flex justify-between items-center py-2 px-4 rounded hover:bg-gray-200 focus:outline-none">
                        <span>Role Management</span>
                        <svg :class="{'rotate-90': roleMgmtOpen}" class="h-4 w-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div x-show="roleMgmtOpen" x-transition class="pl-6 mt-1 space-y-1">
                        <a href="{{ route('admin.roles.index') }}" 
                           class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('admin.roles.*') ? 'bg-gray-200 font-semibold' : '' }}">
                           Manage Roles
                        </a>
                        <a href="{{ route('admin.permissions.index') }}" 
                           class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('admin.permissions.*') ? 'bg-gray-200 font-semibold' : '' }}">
                            Manage Permissions
                        </a>
                    </div>
                </div>
                @endrole
            </div>
        </nav>
    </aside>
    @endhasanyrole

    <!-- Page Content -->
    <main class="p-6 overflow-auto flex-1">
        @yield('content')
    </main>
</div>

</body>
</html>
