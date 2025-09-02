@extends('layouts.app')

@section('title', 'Create Permission')

@section('content')
<div class="max-w-xl mx-auto py-10">
    <h1 class="text-2xl font-bold mb-6">Create Permission</h1>

    <form action="{{ route('admin.permissions.store') }}" method="POST" class="bg-white shadow-md rounded-lg p-6 space-y-6">
        @csrf

        
        {{-- Assign to Role --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Assign to Role</label>
            <select name="roles[]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="">-- Select Role --</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ (collect(old('roles'))->contains($role->id)) ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Permission Name --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Permission Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>


        {{-- Buttons --}}
        <div class="flex justify-end space-x-2">
            <a href="{{ route('admin.permissions.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Save</button>
        </div>
    </form>
</div>
@endsection
