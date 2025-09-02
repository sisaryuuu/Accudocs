@extends('layouts.app')

@section('title', 'Permissions List')

@section('content')
<div class="max-w-6xl mx-auto py-10">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Permissions</h1>
        <a href="{{ route('admin.permissions.create') }}" 
           class="bg-green-600 text-white px-4 py-2 rounded-lg shadow hover:bg-green-700">
            + Add Permission
        </a>
    </div>

    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <table class="min-w-full text-sm text-gray-700">
            <thead class="bg-gray-100 text-xs uppercase text-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left">Permission Name</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permissions as $permission)
                    <tr class="border-t">
                        <td class="px-6 py-3">{{ $permission->name }}</td>
                        <td class="px-6 py-3 text-right space-x-2">
                            <a href="{{ route('admin.permissions.edit', $permission->id) }}" 
                               class="text-blue-600 hover:underline">Edit</a>
                            <form action="{{ route('admin.permissions.destroy', $permission->id) }}" method="POST" class="inline">
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
                        <td colspan="2" class="px-6 py-3 text-center text-gray-500">No permissions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
