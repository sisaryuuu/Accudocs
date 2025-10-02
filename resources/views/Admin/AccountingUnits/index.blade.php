@extends('layouts.app')

@section('title', 'Accounting Units')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Accounting Units</h1>

    <!-- Show Add button only if user has permission -->
    @can('accunit.create')
        <a href="{{ route('admin.accountingunits.create') }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700">
            + Add Accounting Unit
        </a>
    @endcan
</div>

<div class="bg-white shadow-lg rounded-xl overflow-hidden">
    <table class="min-w-full text-sm text-gray-700">
        <thead class="bg-gray-100 text-xs uppercase text-gray-600">
            <tr>
                <th class="px-6 py-3 text-left">Name</th>
                <th class="px-6 py-3 text-left">Email</th>
                <th class="px-6 py-3 text-left">Role</th>   
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users->whereNotIn('roles', ['Admin']) as $unit)
                <tr class="border-t">
                    <td class="px-6 py-3">{{ $unit->fname }} {{ $unit->mi }} {{ $unit->lname }}</td>
                    <td class="px-6 py-3">{{ $unit->email }}</td>
                    <td class="px-6 py-3">{{ $unit->roles->pluck('name')->join(', ') ?? '-' }}</td>
                    <td class="px-6 py-3 text-right space-x-2">

                        <!-- Edit button: only if user has permission -->
                        @can('accunit.edit')
                            <a href="{{ route('admin.accountingunits.edit', $unit) }}" 
                               class="text-blue-600 hover:underline">Edit</a>
                        @endcan

                        <!-- Delete button: only if user has permission -->
                        @can('accunit.delete')
                            <form action="{{ route('admin.accountingunits.destroy', $unit) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" 
                                        class="text-red-600 hover:underline"
                                        onclick="return confirm('Are you sure?')">
                                    Delete
                                </button>
                            </form>
                        @endcan

                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-3 text-center text-gray-500">No accounting units found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
