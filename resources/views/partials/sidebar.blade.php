@php
    $authUser = Auth::guard('web')->user() ?? Auth::guard('focal')->user();
    
    if (!$authUser) {
        return;
    }
    
    // Debug: Check what roles the user actually has
    $userRoles = $authUser->getRoleNames()->toArray();
    $hasAdminRole = $authUser->hasRole('Admin');
    $hasAccountingIIIRole = $authUser->hasRole('Accountant_III');
    $showSidebar = $hasAdminRole || $hasAccountingIIIRole;
    
    $currentRole = session('current_role', $authUser->primary_role ?? $userRoles[0] ?? 'User');
    
    // Debug output - remove after testing
    // \Log::info('Sidebar Debug', compact('userRoles', 'hasAdminRole', 'hasAccountingIIIRole', 'showSidebar'));
@endphp

@if($showSidebar)
@php
    $userMgmtOpen = request()->routeIs('admin.accountingunits.*') || 
                    request()->routeIs('admin.roles.*') || 
                    request()->routeIs('admin.permissions.*');
@endphp

<aside x-data="{ 
    userMgmtOpen: {{ $userMgmtOpen ? 'true' : 'false' }},
    sidebarOpen: true 
}" 
class="bg-white shadow-lg w-64 flex-shrink-0 h-[calc(100vh-4rem)] sticky top-16 transition-all duration-300"
:class="sidebarOpen ? 'w-64' : 'w-20'">

    <!-- Sidebar Header -->
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h1 x-show="sidebarOpen" class="text-xl font-semibold text-gray-700 transition-opacity duration-300">
                Navigation
            </h1>
            <button @click="sidebarOpen = !sidebarOpen" 
                    class="p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                <i class="fas fa-bars text-gray-600"></i>
            </button>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="p-4 space-y-2">
        <!-- Document Tracking -->
        <a href="{{ route('admin.document-tracking') }}" 
           class="group flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:text-gray-900 hover:bg-gray-50 transition-all duration-200"
           :class="sidebarOpen ? 'justify-start' : 'justify-center'">
            <i class="fas fa-search-location text-gray-400 group-hover:text-gray-500 transition-colors duration-200 flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="ml-3 transition-opacity duration-300">Document Tracking</span>
        </a>

        <!-- Dashboard -->
        <a href="{{ route('Admin.dashboard') }}" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-lg transition-all duration-200
                  {{ request()->routeIs('Admin.dashboard') ? 
                     'bg-blue-50 text-blue-700 border-r-2 border-blue-500' : 
                     'text-gray-700 hover:text-gray-900 hover:bg-gray-50' }}"
           :class="sidebarOpen ? 'justify-start' : 'justify-center'">
            <i class="fas fa-tachometer-alt {{ request()->routeIs('Admin.dashboard') ? 'text-blue-500' : 'text-gray-400' }} flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="ml-3 transition-opacity duration-300">Dashboard</span>
        </a>

        <!-- User Management Section (Admin only) -->
        @if($hasAdminRole| $hasAccountingIIIRole)
        <div x-data="{ userMgmtOpen: {{ $userMgmtOpen ? 'true' : 'false' }} }">
            <button @click="userMgmtOpen = !userMgmtOpen" 
                    class="w-full flex items-center px-3 py-3 text-sm font-medium rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-all duration-200 focus:outline-none"
                    :class="sidebarOpen ? 'justify-between' : 'justify-center'">
                <div class="flex items-center">
                    <i class="fas fa-users text-gray-400 flex-shrink-0"></i>
                    <span x-show="sidebarOpen" class="ml-3 transition-opacity duration-300">User Management</span>
                </div>
                <svg x-show="sidebarOpen" :class="{'rotate-90': userMgmtOpen}" 
                     class="h-4 w-4 transition-transform duration-200 flex-shrink-0" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            
            <!-- User Management Submenu -->
            <div x-show="userMgmtOpen && sidebarOpen" x-collapse class="ml-4 mt-1 space-y-1 border-l-2 border-gray-200 pl-4">
                <!-- Accounting Units -->
                <a href="{{ route('admin.accountingunits.index') }}" 
                   class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200
                          {{ request()->routeIs('admin.accountingunits.*') ? 
                             'bg-blue-50 text-blue-700' : 
                             'text-gray-600 hover:text-gray-800 hover:bg-gray-50' }}">
                    <i class="fas fa-user-cog {{ request()->routeIs('admin.accountingunits.*') ? 'text-blue-500' : 'text-gray-400' }} w-4 h-4 mr-2"></i>
                    Accounting Units
                </a>

                <!-- Role Management -->
                <div x-data="{ roleMgmtOpen: {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'true' : 'false' }} }">
                    <button @click="roleMgmtOpen = !roleMgmtOpen" 
                            class="w-full flex items-center justify-between px-3 py-2 text-sm rounded-lg hover:bg-gray-50 hover:text-gray-800 transition-all duration-200 focus:outline-none">
                        <div class="flex items-center">
                            <i class="fas fa-user-shield text-gray-400 w-4 h-4 mr-2"></i>
                            Role Management
                        </div>
                        <svg :class="{'rotate-90': roleMgmtOpen}" 
                             class="h-3 w-3 transition-transform duration-200" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    
                    <div x-show="roleMgmtOpen" x-collapse class="ml-4 mt-1 space-y-1 border-l-2 border-gray-200 pl-4">
                        <!-- Manage Roles -->
                        <a href="{{ route('admin.roles.index') }}" 
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200
                                  {{ request()->routeIs('admin.roles.*') ? 
                                     'bg-blue-50 text-blue-700' : 
                                     'text-gray-600 hover:text-gray-800 hover:bg-gray-50' }}">
                            <i class="fas fa-users-cog {{ request()->routeIs('admin.roles.*') ? 'text-blue-500' : 'text-gray-400' }} w-4 h-4 mr-2"></i>
                            Manage Roles
                        </a>

                        <!-- Manage Permissions -->
                        <a href="{{ route('admin.permissions.index') }}" 
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200
                                  {{ request()->routeIs('admin.permissions.*') ? 
                                     'bg-blue-50 text-blue-700' : 
                                     'text-gray-600 hover:text-gray-800 hover:bg-gray-50' }}">
                            <i class="fas fa-key {{ request()->routeIs('admin.permissions.*') ? 'text-blue-500' : 'text-gray-400' }} w-4 h-4 mr-2"></i>
                            Manage Permissions
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Settings (Admin only) -->
        @if($hasAdminRole)
        <a href="{{ route('admin.settings.edit') }}" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-lg transition-all duration-200
                  {{ request()->routeIs('admin.settings.*') ? 
                     'bg-blue-50 text-blue-700 border-r-2 border-blue-500' : 
                     'text-gray-700 hover:text-gray-900 hover:bg-gray-50' }}"
           :class="sidebarOpen ? 'justify-start' : 'justify-center'">
            <i class="fas fa-cog {{ request()->routeIs('admin.settings.*') ? 'text-blue-500' : 'text-gray-400' }} flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="ml-3 transition-opacity duration-300">Settings</span>
        </a>
        @endif
    </nav>

    <!-- Sidebar Footer -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 bg-white">
        <div class="flex items-center justify-between">
            <div x-show="sidebarOpen" class="text-xs text-gray-500 transition-opacity duration-300">
                <div class="font-medium text-gray-700">{{ $authUser->fname }} {{ $authUser->lname }}</div>
                <div class="text-gray-400">{{ $currentRole }}</div>
            </div>
            <!-- Role Switcher (if multiple roles) -->
            @if($authUser->roles && $authUser->roles->count() > 1)
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" 
                        class="p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200"
                        :class="sidebarOpen ? '' : 'mx-auto'">
                    <i class="fas fa-sync-alt text-gray-600 text-sm"></i>
                </button>
                
                <div x-show="open" @click.away="open = false" 
                     class="absolute bottom-full left-0 mb-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                    <div class="p-2">
                        <div class="text-xs font-semibold text-gray-500 mb-2">Switch Role</div>
                        @foreach($authUser->roles as $role)
                            <form method="POST" action="{{ route('switch-role') }}">
                                @csrf
                                <input type="hidden" name="role" value="{{ $role->name }}">
                                <button type="submit" 
                                        class="block w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-50 transition-colors duration-200
                                               {{ $role->name === $currentRole ? 'bg-blue-50 text-blue-700' : 'text-gray-700' }}">
                                    {{ $role->name }}
                                    @if($role->name === $currentRole)
                                        <i class="fas fa-check ml-2 text-blue-500"></i>
                                    @endif
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</aside>
@endif