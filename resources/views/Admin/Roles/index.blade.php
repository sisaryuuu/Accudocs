@extends('layouts.app')

@section('title', 'Roles Management')

@section('content')
<div class="max-w-6xl mx-auto py-10">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Roles Management</h1>
        <a href="{{ route('admin.roles.create') }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700">
            + Add Role
        </a>
    </div>

    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <table class="min-w-full text-sm text-gray-700">
            <thead class="bg-gray-100 text-xs uppercase text-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left">Role Name</th>
                    <th class="px-6 py-3 text-left">Permissions</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr class="border-t">
                        <td class="px-6 py-3">{{ $role->name }}</td>
                        <td class="px-6 py-3">
                            @foreach($role->permissions as $perm)
                                <span class="bg-gray-200 px-2 py-1 rounded text-xs mr-1">{{ $perm->name }}</span>
                            @endforeach
                        </td>
                        <td class="px-6 py-3 text-right space-x-2">
                            <a href="{{ route('admin.roles.edit', $role->id) }}" 
                               class="text-blue-600 hover:underline">Edit</a>
                            <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" 
                                        class="text-red-600 hover:underline"
                                        onclick="return confirm('Are you sure?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-center text-gray-500">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
