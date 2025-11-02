@extends('layouts.app')

@section('title', 'Permissions List')

@section('content')
<div class="max-w-7xl mx-auto py-10 px-6">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Permissions</h1>
            <p class="text-gray-500 mt-1 text-sm">
                Manage and control access permissions across all user roles.
            </p>
        </div>

        <a href="{{ route('admin.permissions.create') }}" 
           class="mt-4 md:mt-0 inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white 
                  rounded-xl bg-gradient-to-r from-green-500 via-emerald-500 to-green-600 
                  shadow-md hover:shadow-lg hover:scale-[1.02] transition-all duration-200 ease-in-out">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Permission
        </a>
    </div>

    <!-- Card Wrapper -->
    <div class="bg-white shadow-xl rounded-2xl border border-gray-100 overflow-hidden transition-all duration-300">

        <!-- Table Header -->
        <div class="bg-gradient-to-r from-indigo-50 to-indigo-100 px-6 py-3 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-700 tracking-wide uppercase">Permissions Table</h2>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold tracking-wide">Permission Name</th>
                        <th class="px-6 py-3 text-right font-semibold tracking-wide">Actions</th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($permissions as $permission)
                        <tr class="hover:bg-indigo-50 transition-all duration-200 ease-in-out">
                            <td class="px-6 py-4 text-gray-800 font-medium">
                                {{ $permission->name }}
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('admin.permissions.edit', $permission->id) }}" 
                                       class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 font-semibold transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6 6M3 21h6l12-12a2.828 2.828 0 00-4-4L5 17H3v-2z" />
                                        </svg>
                                        Edit
                                    </a>

                                    <form action="{{ route('admin.permissions.destroy', $permission->id) }}" 
                                          method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                                class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 font-semibold transition"
                                                onclick="return confirm('Are you sure you want to delete this permission?')">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7l1-3h8l1 3z" />
                                            </svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-6 py-6 text-center text-gray-500 text-sm">
                                No permissions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer -->
    @if($permissions->count() > 0)
        <div class="flex justify-end mt-6">
            <p class="text-gray-500 text-sm">
                Showing <span class="font-semibold text-gray-700">{{ $permissions->count() }}</span> permission(s)
            </p>
        </div>
    @endif
</div>
@endsection
