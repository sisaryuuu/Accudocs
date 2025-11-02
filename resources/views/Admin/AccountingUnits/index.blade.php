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
        .verification-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .verification-badge:hover {
            transform: translateY(-1px);
        }
        .verified {
            background-color: #dcfce7;
            color: #166534;
        }
        .unverified {
            background-color: #fef3c7;
            color: #92400e;
        }
        .pulse-icon {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
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
                        <p class="text-2xl font-bold text-gray-800">{{ $users->count() }}</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-users text-blue-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-green-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Verified</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $users->filter(fn($u) => $u->hasVerifiedEmail())->count() }}</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-amber-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Unverified</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $users->filter(fn($u) => !$u->hasVerifiedEmail())->count() }}</p>
                    </div>
                    <div class="bg-amber-100 p-3 rounded-full">
                        <i class="fas fa-exclamation-triangle text-amber-500 text-xl pulse-icon"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-purple-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Roles</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $users->pluck('roles')->flatten()->unique('id')->count() }}</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-user-tag text-purple-500 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="bg-white p-4 rounded-xl shadow-sm mb-6">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="w-full md:w-auto">
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Search accounting units..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <select id="verificationFilter" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all">All Status</option>
                        <option value="verified">Verified Only</option>
                        <option value="unverified">Unverified Only</option>
                    </select>
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
                            <th class="px-6 py-4 text-left font-medium">Email Status</th>
                            <th class="px-6 py-4 text-left font-medium">Role</th>   
                            <th class="px-6 py-4 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($users as $unit)
                            <tr class="table-row" data-verification-status="{{ $unit->hasVerifiedEmail() ? 'verified' : 'unverified' }}">
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
                                    <div class="text-gray-900 flex items-center">
                                        <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                        {{ $unit->email }}
                                    </div>
                                    <div class="text-gray-500 text-xs mt-1 flex items-center">
                                        <i class="fas fa-phone-alt mr-2"></i>
                                        {{ $unit->phone ?? 'No phone provided' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($unit->hasVerifiedEmail())
                                        <span class="verification-badge verified" title="Email verified on {{ $unit->email_verified_at->format('M d, Y') }}">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Verified
                                        </span>
                                    @else
                                        <span class="verification-badge unverified" title="Email not verified yet">
                                            <i class="fas fa-exclamation-circle mr-1 pulse-icon"></i>
                                            Pending
                                        </span>
                                        @can('accunit.edit')
                                        <form action="{{ route('admin.accountingunits.resend-verification', $unit) }}" method="POST" class="inline-block mt-1">
                                            @csrf
                                            <button type="submit" 
                                                    class="text-xs text-blue-600 hover:text-blue-800 hover:underline flex items-center"
                                                    title="Resend verification email">
                                                <i class="fas fa-paper-plane mr-1"></i>
                                                Resend
                                            </button>
                                        </form>
                                        @endcan
                                    @endif
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
                                        @can('accunit.edit')
                                            <a href="{{ route('admin.accountingunits.edit', $unit) }}" 
                                               class="bg-blue-100 text-blue-600 hover:bg-blue-200 px-3 py-2 rounded-lg text-sm font-medium flex items-center action-btn"
                                               title="Edit user">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </a>
                                        @endcan

                                        @can('accunit.delete')
                                            <form action="{{ route('admin.accountingunits.destroy', $unit) }}" method="POST" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" 
                                                        class="bg-red-100 text-red-600 hover:bg-red-200 px-3 py-2 rounded-lg text-sm font-medium flex items-center action-btn"
                                                        onclick="return confirm('Are you sure you want to delete this accounting unit?')"
                                                        title="Delete user">
                                                    <i class="fas fa-trash-alt mr-1"></i> Delete
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
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
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const verificationFilter = document.getElementById('verificationFilter');
    const tableRows = document.querySelectorAll('tbody tr[data-verification-status]');
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterValue = verificationFilter.value;
        
        tableRows.forEach(row => {
            const name = row.cells[0].textContent.toLowerCase();
            const email = row.cells[1].textContent.toLowerCase();
            const verificationStatus = row.getAttribute('data-verification-status');
            
            const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
            const matchesFilter = filterValue === 'all' || filterValue === verificationStatus;
            
            if (matchesSearch && matchesFilter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    searchInput.addEventListener('input', filterTable);
    verificationFilter.addEventListener('change', filterTable);
});
</script>
