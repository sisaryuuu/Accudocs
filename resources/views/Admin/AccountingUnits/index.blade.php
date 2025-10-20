@extends('layouts.app')

@section('title', 'Admin - Accounting Units')
@section('content')
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .action-btn {
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: scale(1.05);
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        .role-badge {
            transition: all 0.2s ease;
        }
        .role-badge:hover {
            transform: translateY(-2px);
        }
        .table-row {
            transition: all 0.2s ease;
        }
        .table-row:hover {
            background-color: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 fade-in">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div class="mb-4 md:mb-0">
                <h1 class="text-3xl font-bold text-gray-900">Accounting Units</h1>
                <p class="text-gray-600 mt-2">Manage accounting staff and their permissions</p>
            </div>
            
            <!-- Show Add button only if user has permission -->
            @can('accunit.create')
            <a href="{{ route('admin.accountingunits.create') }}" 
               class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-5 py-3 rounded-lg shadow-md hover:from-blue-600 hover:to-indigo-700 flex items-center action-btn">
                <i class="fas fa-plus-circle mr-2"></i> Add Accounting Unit
            </a>
            @endcan
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-blue-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Total Staff</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $users->whereNotIn('roles', ['Admin'])->count() }}</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-users text-blue-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-green-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Active</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $users->whereNotIn('roles', ['Admin'])->where('status', 'active')->count() }}</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-user-check text-green-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-purple-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Roles</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $users->whereNotIn('roles', ['Admin'])->pluck('roles')->unique()->count() }}</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-user-tag text-purple-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-amber-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">This Month</p>
                        <p class="text-2xl font-bold text-gray-800">+2</p>
                    </div>
                    <div class="bg-amber-100 p-3 rounded-full">
                        <i class="fas fa-chart-line text-amber-500 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="bg-white p-4 rounded-xl shadow-sm mb-6">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="w-full md:w-auto">
                    <div class="relative">
                        <input type="text" placeholder="Search accounting units..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                    <button class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 flex items-center">
                        <i class="fas fa-download mr-2"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Accounting Units Table -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden fade-in">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-gray-700">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 text-gray-600">
                        <tr>
                            <th class="px-6 py-4 text-left font-medium">Staff Member</th>
                            <th class="px-6 py-4 text-left font-medium">Contact Information</th>
                            <th class="px-6 py-4 text-left font-medium">Role</th>   
                            <th class="px-6 py-4 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($users->whereNotIn('roles', ['Admin']) as $unit)
                            <tr class="table-row">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="user-avatar bg-blue-500 mr-3">
                                            {{ substr($unit->fname, 0, 1) }}{{ substr($unit->lname, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $unit->fname }} {{ $unit->mi }} {{ $unit->lname }}</div>
                                            <div class="text-gray-500 text-xs">ID: {{ $unit->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-900">{{ $unit->email }}</div>
                                    <div class="text-gray-500 text-xs mt-1 flex items-center">
                                        <i class="fas fa-phone-alt mr-1"></i>
                                        {{ $unit->phone ?? 'No phone provided' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($unit->roles && $unit->roles->count() > 0)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($unit->roles as $role)
                                                <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-xs font-medium role-badge">
                                                    {{ $role->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <!-- Edit button: only if user has permission -->
                                        @can('accunit.edit')
                                            <a href="{{ route('admin.accountingunits.edit', $unit) }}" 
                                               class="bg-blue-100 text-blue-600 hover:bg-blue-200 px-3 py-2 rounded-lg text-sm font-medium flex items-center action-btn">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </a>
                                        @endcan

                                        <!-- Delete button: only if user has permission -->
                                        @can('accunit.delete')
                                            <form action="{{ route('admin.accountingunits.destroy', $unit) }}" method="POST" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" 
                                                        class="bg-red-100 text-red-600 hover:bg-red-200 px-3 py-2 rounded-lg text-sm font-medium flex items-center action-btn"
                                                        onclick="return confirm('Are you sure you want to delete this accounting unit?')">
                                                    <i class="fas fa-trash-alt mr-1"></i> Delete
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-500">
                                        <i class="fas fa-user-times text-4xl mb-4 text-gray-300"></i>
                                        <p class="text-lg">No accounting units found</p>
                                        <p class="text-sm mt-2">Get started by adding your first accounting staff member</p>
                                        @can('accunit.create')
                                        <a href="{{ route('admin.accountingunits.create') }}" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                                            Add Accounting Unit
                                        </a>
                                        @endcan
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
                    if (row.cells.length < 3) return; // Skip empty row
                    
                    const name = row.cells[0].textContent.toLowerCase();
                    const email = row.cells[1].textContent.toLowerCase();
                    
                    if (name.includes(searchTerm) || email.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
