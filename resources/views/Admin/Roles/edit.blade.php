@extends('layouts.app')

@section('title', isset($role) ? 'Edit Role' : 'Create Role')

@section('content')
<div class="max-w-2xl mx-auto py-10">
    <h1 class="text-2xl font-bold mb-6">{{ isset($role) ? 'Edit Role' : 'Create Role' }}</h1>

    <form action="{{ isset($role) ? route('admin.roles.update', $role->id) : route('admin.roles.store') }}" method="POST" class="bg-white shadow-md rounded-lg p-6 space-y-6">
        @csrf
        @if(isset($role))
            @method('PUT')
        @endif

        <!-- Role Name -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Role Name</label>
            <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}" required
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Permissions -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Permissions</label>
            <div class="grid grid-cols-2 gap-2 mt-2 max-h-64 overflow-y-auto border p-2 rounded">
                @foreach($permissions as $perm)
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                               {{ isset($role) && $role->permissions->contains('id', $perm->id) ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        <span class="text-gray-700">{{ $perm->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700">
                {{ isset($role) ? 'Update Role' : 'Create Role' }}
            </button>
        </div>
    </form>
</div>
@endsection
