@extends('layouts.app')

@section('title', 'Admin - Roles Management')
@section('content')

    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .permission-tag {
            transition: all 0.2s ease;
        }
        .permission-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .action-btn {
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 fade-in">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div class="mb-4 md:mb-0">
                <h1 class="text-3xl font-bold text-gray-900">Roles Management</h1>
                <p class="text-gray-600 mt-2">Manage user roles and their permissions</p>
            </div>
            <a href="{{ route('admin.roles.create') }}" 
               class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-5 py-3 rounded-lg shadow-md hover:from-blue-600 hover:to-indigo-700 flex items-center action-btn">
                <i class="fas fa-plus-circle mr-2"></i> Add New Role
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-blue-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Total Roles</p>
                        <p class="text-2xl font-bold text-gray-800">{{ count($roles) }}</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-user-tag text-blue-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-green-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Admin Roles</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $roles->where('name', 'Admin')->count() }}</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-user-shield text-green-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-purple-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Custom Roles</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $roles->where('name', '!=', 'Admin')->count() }}</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-users-cog text-purple-500 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="bg-white p-4 rounded-xl shadow-sm mb-6">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="w-full md:w-auto">
                    <div class="relative">
                        <input type="text" placeholder="Search roles..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center">
                        <i class="fas fa-sort mr-2"></i> Sort
                    </button>
                </div>
            </div>
        </div>

        <!-- Roles Table -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden fade-in">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-gray-700">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 text-gray-600">
                        <tr>
                            <th class="px-6 py-4 text-left font-medium">Role Name</th>
                            <th class="px-6 py-4 text-left font-medium">Permissions</th>
                            <th class="px-6 py-4 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($roles as $role)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                            <i class="fas fa-user-tag text-blue-500"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $role->name }}</div>
                                            <div class="text-gray-500 text-xs">Created {{ $role->created_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($role->permissions as $perm)
                                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium permission-tag">
                                                {{ $perm->name }}
                                            </span>
                                        @endforeach
                                        @if($role->permissions->count() === 0)
                                            <span class="text-gray-400 text-xs">No permissions assigned</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <a href="{{ route('admin.roles.edit', $role->id) }}" 
                                           class="bg-blue-100 text-blue-600 hover:bg-blue-200 px-3 py-2 rounded-lg text-sm font-medium flex items-center action-btn">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" 
                                                    class="bg-red-100 text-red-600 hover:bg-red-200 px-3 py-2 rounded-lg text-sm font-medium flex items-center action-btn"
                                                    onclick="return confirm('Are you sure you want to delete this role?')">
                                                <i class="fas fa-trash-alt mr-1"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-500">
                                        <i class="fas fa-user-tag text-4xl mb-4 text-gray-300"></i>
                                        <p class="text-lg">No roles found</p>
                                        <p class="text-sm mt-2">Get started by creating your first role</p>
                                        <a href="{{ route('admin.roles.create') }}" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                                            Create Role
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection
    <script>
        // Simple search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[type="text"]');
            const tableRows = document.querySelectorAll('tbody tr');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                tableRows.forEach(row => {
                    const roleName = row.querySelector('td:first-child .font-medium').textContent.toLowerCase();
                    if (roleName.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
