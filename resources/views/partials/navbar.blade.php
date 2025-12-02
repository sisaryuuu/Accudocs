@auth
    @php
        $authUser = auth()->user();
        $currentRole = session('current_role', $authUser->primary_role ?? $authUser->getRoleNames()->first() ?? 'User');
        $userRoles = $authUser->getRoleNames();
    @endphp
@endauth

<nav class="bg-white shadow-md fixed w-full z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Left Section -->
            <div class="flex items-center">
                <img src="{{ asset('storage/logo/acculogo.png') }}" 
                     alt="App Logo" 
                     class="h-12 md:h-14 lg:h-18 w-auto object-contain">
            </div>

            <!-- Center Section - Dynamic Navigation -->
            <div class="flex space-x-6">
                @include('partials.navigation.dynamic-nav')
            </div>

            <!-- Right Section -->
            <div class="flex items-center space-x-4">
                @if($authUser)
                    <span class="text-gray-600">Hello, {{ $authUser->lname ?? 'Guest' }}</span>
                    
                    <!-- Role Switcher -->
                    @if(($authUser->hasMultipleRoles ?? false) && $authUser->roles->count() > 1)
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" 
                                class="flex items-center space-x-2 px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                            <span class="font-medium text-gray-700">{{ $currentRole }}</span>
                            <i class="fas fa-chevron-down text-gray-600 text-xs"></i>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" 
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
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
                    
                    <!-- Profile Dropdown -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                            <img src="{{ $authUser->profile_image ? asset('storage/profiles/' . $authUser->profile_image) : asset('storage/profiles/default.png') }}"
                                 alt="Profile"
                                 class="h-10 w-10 rounded-full border-2 border-gray-300 object-cover hover:border-blue-500 transition-colors duration-200">
                        </button>
                        
                        <div x-show="open" @click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                            @if(Auth::guard('web')->check())
                                <a href="{{ route('web.profile.show') }}" 
                                   class="block px-4 py-2 text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                    <i class="fas fa-user mr-2 text-gray-400"></i>Profile
                                </a>
                            @elseif(Auth::guard('focal')->check())
                                <a href="{{ route('FocalPerson.profile.show') }}" 
                                   class="block px-4 py-2 text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                    <i class="fas fa-user mr-2 text-gray-400"></i>Profile
                                </a>
                            @endif
                            
                            @if($authUser->hasRole('Admin'))
                            <a href="{{ route('admin.settings.edit') }}" 
                               class="block px-4 py-2 text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-cog mr-2 text-gray-400"></i>Settings
                            </a>
                            @endif
                            
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" 
                                        class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                    <i class="fas fa-sign-out-alt mr-2 text-gray-400"></i>Logout
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <!-- Show login link if no user is authenticated -->
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-800">Login</a>
                @endif
            </div>
        </div>
    </div>
</nav>