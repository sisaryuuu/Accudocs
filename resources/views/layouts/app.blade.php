<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  
  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <title>@yield('title', 'Dashboard')</title>
</head>
<div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col space-y-2"></div>

<body class="bg-gray-100 font-sans">
<script>
    @if (session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: "{{ session('success') }}",
            timer: 2000,
            showConfirmButton: false
        });
    @endif

    @if (session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: "{{ session('error') }}",
        });
    @endif

    @if (session('warning'))
        Swal.fire({
            icon: 'warning',
            title: 'Warning',
            text: "{{ session('warning') }}",
        });
    @endif
     @if (session('info'))
        Swal.fire({
            icon: 'info',
            title: 'info',
            text: "{{ session('info') }}",
            timer: 2000,
            showConfirmButton: true
        });
    @endif


    document.addEventListener("DOMContentLoaded", function () {
    // Handle delete buttons
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            let form = this.closest('form');

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>

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
                  <a href="{{ route('FocalPerson.dvrequest.history') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('FocalPerson.dvrequest.history') ? 'bg-gray-200 font-semibold' : '' }}">
                    Request History
                </a>
            </div>

            @endrole
                @role('IMSD')
        <div class="flex justify-center space-x-6 w-full">
            <a href="{{ route('RegionalOffices.IMSD.index') }}" 
            class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('RegionalOffices.IMSD.index') ? 'bg-gray-200 font-semibold' : '' }}">
                Received Documents
            </a>
                <!-- First Pending DV -->
           <!-- First Pending DV -->
            <a href="{{ route('RegionalOffices.IMSD.redirectFirst') }}"
            class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-md">
                Verify Disbursement Documents
            </a>

            <a href="{{ route('history') }}" 
            class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('history') ? 'bg-gray-200 font-semibold' : '' }}">
                IMSD Log History
            </a>
        </div>
        @endrole

            @role('Incoming-GIP')
             <div class="flex space-x-3">
                <a href="{{ route('Incoming.GIP.index') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Incoming.GIP.index') ? 'bg-gray-200 font-semibold' : '' }}">
                    Received Documents
                </a>
                                        <!-- Verify Documents -->
                <a href="{{ route('Incoming.GIP.verifygipdv') }}" 
                class="block py-2 px-4 rounded hover:bg-gray-200
                        {{ request()->routeIs('Incoming.GIP.verifygipdv.withid') ? 'bg-gray-200 font-semibold' : '' }}">
                    Verify Documents
                </a>
                <a href="{{ route('dvrequests.giphistory') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('dvrequests.giphistory') ? 'bg-gray-200 font-semibold' : '' }}">
                    Request History
                </a>
        
            </div>
            @endrole
            @role('Incoming-TUPAD')
             <div class="flex space-x-3">
                <a href="{{ route('Incoming.TUPAD.index') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Incoming.TUPAD.index') ? 'bg-gray-200 font-semibold' : '' }}">
                    Received Documents
                </a>
                                        <!-- Verify Documents -->
                <a href="{{ route('Incoming.TUPAD.verifytupaddv') }}" 
                class="block py-2 px-4 rounded hover:bg-gray-200
                        {{ request()->routeIs('Incoming.TUPAD.verifytupaddv.withid') ? 'bg-gray-200 font-semibold' : '' }}">
                    Verify Documents
                </a>
                <a href="{{ route('dvrequests.tupadhistory') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('dvrequests.tupadhistory') ? 'bg-gray-200 font-semibold' : '' }}">
                    Request History
                </a>
        
            </div>
            @endrole
                    @role('Incoming-TEV')
                <div class="flex space-x-3">
                    <!-- Received Documents -->
                    <a href="{{ route('Incoming.TEV.index') }}" 
                    class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Incoming.TEV.index') ? 'bg-gray-200 font-semibold' : '' }}">
                        Received Documents
                    </a>

                    <!-- Verify Documents -->
                    <a href="{{ route('Incoming.TEV.verifytevdv') }}" 
                    class="block py-2 px-4 rounded hover:bg-gray-200
                            {{ request()->routeIs('Incoming.TEV.dvrequests.withid') ? 'bg-gray-200 font-semibold' : '' }}">
                        Verify Documents
                    </a>

                    <!-- Request History -->
                    <a href="" 
                    class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('') ? 'bg-gray-200 font-semibold' : '' }}">
                        Request History
                    </a>
                </div>
            @endrole


            @role('Budget_Officer')
                 <div class="flex space-x-3">
                <a href="{{ route('BudgetOfficer.dvrequests.index') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('BudgetOfficer.dvrequests.index') ? 'bg-gray-200 font-semibold' : '' }}">
                    Received Documents
                </a>
                                        <!-- Verify Documents -->
                <a href="{{ route('BudgetOfficer.dvrequests.verifyors') }}" 
                class="block py-2 px-4 rounded hover:bg-gray-200
                        {{ request()->routeIs('BudgetOfficer.dvrequests.withid') ? 'bg-gray-200 font-semibold' : '' }}">
                    Verify Documents
                </a>
                <a href="{{ route('BudgetOfficer.dvrequests.loghistory') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('BudgetOfficer.dvrequests.loghistory') ? 'bg-gray-200 font-semibold' : '' }}">
                    ORS Log History
                </a>
        
            </div>
            @endrole

            @role('Outgoing')
              <a href="{{ route('Outgoing.dvrequests.index') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Outgoing.dvrequests.index') ? 'bg-gray-200 font-semibold' : '' }}">
                    Pending DV Documents
                </a>
            <a href="{{ route('Outgoing.dvrequests.verifydv') }}"
                class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Outgoing.dvrequests.withid') ? 'bg-gray-200 font-semibold' : '' }}">
                    Finalize DV Documents
                </a>
                <a href="{{ route('Outgoing.dvrequests.dvloghistory') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Outgoing.dvrequests.dvloghistory') ? 'bg-gray-200 font-semibold' : '' }}">
                    DV Log History
                </a>
            @endrole

               @role('Accounting_III')
              <a href="{{ route('Accounting.dvrequests.index') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Accounting.dvrequests.index') ? 'bg-gray-200 font-semibold' : '' }}">
                    Pending DV Documents
                </a>
            <a href="{{ route('Accounting.dvrequest.redirectFirstPending') }}"
                class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Accounting.dvrequest.redirectFirstPending') ? 'bg-gray-200 font-semibold' : '' }}">
                    Finalize DV Documents
                </a>
                <a href="{{ route('Accounting.history') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Accounting.history') ? 'bg-gray-200 font-semibold' : '' }}">
                    DV Log History
                </a>
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
                        </svg>                    </button>
                    
                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2">
                                @if(Auth::guard('web')->check())
                                <a href="{{ route('web.profile.show') }}" 
                                class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                Profile
                                </a>
                            @elseif(Auth::guard('focal')->check())
                                <a href="{{ route('FocalPerson.profile.show') }}" 
                                class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                Profile
                                </a>
                            @endif
                       @role('Admin')
                            <a href="{{ route('admin.settings.edit') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                Settings
                            </a>
                        @endrole
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
             <a href="{{ route('Accounting.dvrequests.index') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Accounting.dvrequests.index') ? 'bg-gray-200 font-semibold' : '' }}">
                    Pending DV Documents
                </a>
            <a href="{{ route('Accounting.dvrequest.redirectFirstPending') }}"
                class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Accounting.dvrequest.redirectFirstPending') ? 'bg-gray-200 font-semibold' : '' }}">
                    Finalize DV Documents
                </a>
                <a href="{{ route('Accounting.history') }}" 
                   class="block py-2 px-4 rounded hover:bg-gray-200 {{ request()->routeIs('Accounting.dvrequests.rancahistory') ? 'bg-gray-200 font-semibold' : '' }}">
                    DV Log History
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
