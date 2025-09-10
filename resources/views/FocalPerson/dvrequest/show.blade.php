@extends('layouts.app')

@section('title', 'DV Request Logs')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">DV Request Logs</h1>
                <p class="mt-2 text-sm text-gray-600">Track the complete history and status changes of your request</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('FocalPerson.dvrequest.index') }}" 
                   class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200 ease-in-out">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Requests
                </a>
            </div>
        </div>

        <!-- Main Content Card: Logs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($logs->isEmpty())
                <div class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No logs available</h3>
                    <p class="text-gray-500 mb-6">There are no activity logs for this request yet.</p>
                </div>
            @else
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Activity Timeline</h3>
                    <span class="text-sm text-gray-500">{{ $logs->count() }} {{ Str::plural('entry', $logs->count()) }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">#</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actor</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($logs as $log)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 text-center">{{ $loop->iteration }}</td>
                                    <td class="px-6 py-4 flex items-center gap-3">
                                        @if($log->focalPerson)
                                            <img src="{{ asset('storage/profiles/' . ($log->focalPerson->profile_image ?? 'default.png')) }}"
                                                alt="Profile" class="w-8 h-8 rounded-full object-cover border">
                                            <div>
                                                <div class="font-semibold">{{ $log->focalPerson->full_name }}</div>
                                                <div class="text-xs text-gray-500">Focal Person</div>
                                            </div>
                                        @elseif($log->accountingUnit)
                                            <img src="{{ asset('storage/profiles/' . ($log->accountingUnit->profile_image ?? 'default.png')) }}"
                                                alt="Profile" class="w-8 h-8 rounded-full object-cover border">
                                            <div>
                                                <div class="font-semibold">{{ $log->accountingUnit->name }}</div>
                                                <div class="text-xs text-gray-500">Accounting Unit</div>
                                            </div>
                                        @else
                                            <span class="text-gray-500">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">{{ ucfirst($log->action ?? 'Updated') }}</td>
                                    <td class="px-6 py-4">{{ $log->remarks ?? 'None' }}</td>
                                    <td class="px-6 py-4">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
