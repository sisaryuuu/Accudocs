@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <!-- Welcome Card -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <h2 class="text-2xl font-semibold text-gray-700 mb-2">
            Welcome, {{ Auth::user()->full_name }}
        </h2>
        <p class="text-gray-600">Email: <span class="font-medium">{{ Auth::user()->email }}</span></p>
        <p class="text-gray-600">Program: <span class="font-medium">{{ Auth::user()->program->name ?? 'N/A' }}</span></p>
    </div>

    <!-- Dashboard Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-blue-500 text-white rounded-xl shadow p-6">
            <h3 class="text-lg font-medium">Total Users</h3>
            <p class="text-4xl font-bold mt-2">{{ \App\Models\AccountingUnit::count() }}</p>
        </div>
        <div class="bg-green-500 text-white rounded-xl shadow p-6">
            <h3 class="text-lg font-medium">Programs</h3>
            <p class="text-4xl font-bold mt-2">{{ \App\Models\Program::count() }}</p>
        </div>
        <div class="bg-yellow-500 text-white rounded-xl shadow p-6">
            <h3 class="text-lg font-medium">Roles</h3>
            <p class="text-4xl font-bold mt-2">{{ \Spatie\Permission\Models\Role::count() }}</p>
        </div>
        <div class="bg-purple-500 text-white rounded-xl shadow p-6">
            <h3 class="text-lg font-medium">Permissions</h3>
            <p class="text-4xl font-bold mt-2">{{ \Spatie\Permission\Models\Permission::count() }}</p>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-xl font-semibold text-gray-700 mb-4">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
            @can('accunit.view')
                <a href="{{ route('admin.accountingunits.index') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow">
                   Manage Accounting Units
                </a>
            @endcan>
        </div>
    </div>
@endsection
