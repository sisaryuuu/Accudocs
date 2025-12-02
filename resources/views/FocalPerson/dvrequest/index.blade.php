@extends('layouts.app')

@section('title', 'My DV Requests')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
  <div class="max-w-[110rem] mx-auto px-6">

        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My DV Requests</h1>
                <p class="mt-2 text-sm text-gray-600">Manage and track your disbursement voucher requests</p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-4">

                <!-- Notification Dropdown -->
                <div class="relative">
                    <button id="notification-button" class="relative focus:outline-none bg-white border rounded-full p-2 shadow">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span id="notification-badge" 
                            class="absolute top-0 right-0 inline-flex items-center justify-center px-1 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">0</span>
                    </button>
                    <ul id="notification-list" 
                        class="hidden absolute right-0 mt-2 w-[500px] max-h-96 overflow-y-auto bg-white shadow-lg rounded-md z-50">
                        <li class="p-2 bg-gray-100 rounded-md hover:bg-gray-200 cursor-pointer font-bold whitespace-normal break-words">
                        </li>
                    </ul>
                </div>

                <!-- New DV Request Button -->
                <a href="{{ route('FocalPerson.dvrequest.submitdvors') }}" 
                   class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200 ease-in-out">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    New DV Request
                </a>
                <a href="{{ route('qr.scanner') }}" 
                     class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    Scan QR Code
                </a>
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($dvRequests->isEmpty())
                <div class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No requests yet</h3>
                    <p class="text-gray-500 mb-6">Get started by creating your first DV request.</p>
                    <a href="{{ route('FocalPerson.dvrequest.submitdvors') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                        Create Request
                    </a>
                </div>
            @else
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h3 class="text-lg font-semibold text-gray-900">Request History</h3>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="relative">
                            <input type="text" id="search" placeholder="Search Control No., Payee, Program..."
                                   class="w-72 pl-10 pr-4 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M21 21l-4.35-4.35M16 10a6 6 0 11-12 0 6 6 0 0112 0z"/>
                            </svg>
                        </div>
                        <!-- Items Per Page Selector -->
                        <div class="flex items-center space-x-2">
                            <label for="perPage" class="text-sm text-gray-600 whitespace-nowrap">Show:</label>
                            <select id="perPage" class="text-sm border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ request('per_page', 10) == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page', 10) == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full table-auto divide-y divide-gray-200" id="dvTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request No.</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DV Number</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payee Name</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TIN/Employee No.</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsibility Center</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Purpose</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Particulars</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Submitted</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($dvRequests as $request)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #{{ $request->user_sequence }}
                                </td>
                                <td id="dv-{{ $request->id }}" class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($request->dv_number)
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">
                                            {{ $request->dv_number }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded text-xs font-medium">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="font-medium">{{ $request->focalperson->fname }} {{ $request->focalperson->mi }} {{ $request->focalperson->lname }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $request->focalperson->TIN_number ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $request->respocenter->description ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $request->program->prcode ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    ₱{{ number_format($request->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs text-center">
                                    <div class="truncate px-3 py-1 font-medium" title="{{ $request->particulars ?? '' }}">
                                        {{ Str::limit($request->particulars ?? '', 50) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span id="status-{{ $request->id }}" 
                                        data-status="{{ $request->status }}" 
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-center">
                                        <span class="status-label">{{ ucwords(str_replace('_', ' ', $request->status)) }}</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $request->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <!-- QR Code Button -->
                                        <button onclick="showQRModal({{ $request->id }})" 
                                                class="inline-flex items-center px-3 py-1.5 bg-purple-100 hover:bg-purple-200 text-purple-700 text-xs font-medium rounded-md transition-colors duration-200">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                            </svg>
                                            QR
                                        </button>

                                        <a href="{{ route('FocalPerson.dvrequest.logs', $request->id) }}" 
                                           class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md transition-colors duration-200">
                                            Track
                                        </a>
                                        <a href="{{ route('FocalPerson.dvrequest.preview', $request->id) }}" 
                                        class="inline-flex items-center px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-md transition-colors duration-200">
                                            Preview
                                        </a>
                                        @if($request->status === 'submitted')
                                        <form id="delete-btn-{{ $request->id }}" action="{{ route('FocalPerson.dvrequest.destroy', $request->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-md transition-colors duration-200"
                                                onclick="return confirm('Are you sure you want to delete this request?')">
                                            Delete
                                        </button>
                                    </form>
                                    <a href="{{ route('FocalPerson.dvrequest.edit', $request->id) }}" 
                                            id="edit-btn-{{ $request->id }}"
                                            class="inline-flex items-center px-3 py-1.5 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 text-xs font-medium rounded-md transition-colors duration-200">
                                                Edit
                                            </a>
                                    @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Section -->
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-500">
                            Showing {{ $dvRequests->firstItem() ?? 0 }} to {{ $dvRequests->lastItem() ?? 0 }} of {{ $dvRequests->total() }} requests
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <!-- Previous Page Link -->
                            @if ($dvRequests->onFirstPage())
                                <span class="px-3 py-1 text-sm text-gray-400 border border-gray-300 rounded-lg cursor-not-allowed">
                                    Previous
                                </span>
                            @else
                                <a href="{{ $dvRequests->previousPageUrl() }}{{ request('per_page') ? '&per_page=' . request('per_page') : '' }}" 
                                   class="px-3 py-1 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                    Previous
                                </a>
                            @endif

                            <!-- Page Numbers -->
                            <div class="flex space-x-1">
                                @foreach ($dvRequests->getUrlRange(1, $dvRequests->lastPage()) as $page => $url)
                                    @if ($page == $dvRequests->currentPage())
                                        <span class="px-3 py-1 text-sm text-white bg-blue-600 border border-blue-600 rounded-lg font-medium">
                                            {{ $page }}
                                        </span>
                                    @else
                                        <a href="{{ $url }}{{ request('per_page') ? '&per_page=' . request('per_page') : '' }}" 
                                           class="px-3 py-1 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                            {{ $page }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>

                            <!-- Next Page Link -->
                            @if ($dvRequests->hasMorePages())
                                <a href="{{ $dvRequests->nextPageUrl() }}{{ request('per_page') ? '&per_page=' . request('per_page') : '' }}" 
                                   class="px-3 py-1 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                    Next
                                </a>
                            @else
                                <span class="px-3 py-1 text-sm text-gray-400 border border-gray-300 rounded-lg cursor-not-allowed">
                                    Next
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- QR Code Modal -->
<div id="qrModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    DV Request QR Code
                </h3>
                <button onclick="closeQRModal()" class="text-white hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div id="qrContent" class="p-6 text-center">
            <div class="flex items-center justify-center mb-4">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
            </div>
            <p class="text-gray-600">Loading QR Code...</p>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-50 rounded-b-2xl flex gap-3 justify-center">
            <button id="downloadQRBtn" onclick="downloadQR()" class="hidden px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download
            </button>
            <button id="printQRBtn" onclick="printQR()" class="hidden px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print
            </button>
        </div>
    </div>
</div>

<!-- Search Script -->
<script>
document.getElementById('search').addEventListener('keyup', function() {
    let query = this.value.toLowerCase();
    let rows = document.querySelectorAll('#dvTable tbody tr');
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
});

// Items per page change handler
document.getElementById('perPage').addEventListener('change', function() {
    const perPage = this.value;
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('per_page', perPage);
    window.location.href = currentUrl.toString();
});

let currentQRData = null;

async function showQRModal(dvId) {
    const modal = document.getElementById('qrModal');
    const content = document.getElementById('qrContent');
    
    modal.classList.remove('hidden');
    content.innerHTML = `
        <div class="flex items-center justify-center mb-4">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
        </div>
        <p class="text-gray-600">Loading QR Code...</p>
    `;
    
    document.getElementById('downloadQRBtn').classList.add('hidden');
    document.getElementById('printQRBtn').classList.add('hidden');
    
    try {
        const response = await fetch(`/FocalPerson/dvrequest/${dvId}/qr`);
        const data = await response.json();
        
        if (data.error) {
            content.innerHTML = `
                <div class="text-center py-8">
                    <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-red-600 font-semibold">${data.error}</p>
                </div>
            `;
            return;
        }
        
        currentQRData = data;
        
        content.innerHTML = `
            <div class="space-y-6">
                <!-- QR Code Container -->
                <div class="flex justify-center">
                    <div class="bg-white p-6 rounded-xl border-2 border-purple-200 shadow-inner 
                                w-full max-w-sm mx-auto overflow-hidden">
                        
                        <div class="qr-image-container flex justify-center items-center">
                            <object 
                                id="qrImage" 
                                data="${data.qr_url}" 
                                type="image/png"
                                class="w-[85%] max-w-[300px] h-auto object-contain transition-all duration-300 ease-in-out"
                                style="max-height: 300px;">
                                
                                <!-- Fallback if <object> fails -->
                                <img 
                                    src="${data.qr_url}" 
                                    alt="QR Code" 
                                    class="w-[85%] max-w-[300px] h-auto object-contain transition-all duration-300 ease-in-out" 
                                    style="max-height: 300px;">
                            </object>
                        </div>
                    </div>
                </div>
                
                <!-- Request Information -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <div class="grid grid-cols-1 gap-4 text-sm">
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                            <span class="text-gray-600 font-medium">Reference Number:</span>
                            <span class="text-gray-900 font-semibold text-right break-all">${data.reference_number}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                            <span class="text-gray-600 font-medium">Control Number:</span>
                            <span class="text-gray-900 font-semibold">${data.control_number}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                            <span class="text-gray-600 font-medium">Amount:</span>
                            <span class="text-green-600 font-bold">₱${data.amount}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Status:</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">${data.status}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
                    <p class="text-sm text-blue-800 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        Scan this QR code to view your DV Request status anytime, anywhere.
                    </p>
                </div>
            </div>
        `;
        
        // Show action buttons
        document.getElementById('downloadQRBtn').classList.remove('hidden');
        document.getElementById('printQRBtn').classList.remove('hidden');
        
    } catch (error) {
        console.error('Error loading QR:', error);
        content.innerHTML = `
            <div class="text-center py-8">
                <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-red-600 font-semibold">Failed to load QR Code</p>
                <p class="text-gray-500 text-sm mt-2">Please try again later</p>
            </div>
        `;
    }
}

function closeQRModal() {
    document.getElementById('qrModal').classList.add('hidden');
    currentQRData = null;
}

function downloadQR() {
    if (!currentQRData) return;
    
    const link = document.createElement('a');
    link.href = currentQRData.qr_url;
    link.download = `DV_QR_${currentQRData.reference_number}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Show success message
    showToast('QR Code downloaded successfully!', 'success');
}

function printQR() {
    if (!currentQRData) return;
    
    const printWindow = window.open('', '_blank', 'width=600,height=700');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>DV Request QR Code - ${currentQRData.reference_number}</title>
            <style>
                @import url('https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css');
                body {
                    font-family: system-ui, -apple-system, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                    background: white;
                }
                .print-container {
                    text-align: center;
                    max-width: 400px;
                    width: 100%;
                }
                .header {
                    background: linear-gradient(135deg, #7c3aed, #4f46e5);
                    color: white;
                    padding: 20px;
                    border-radius: 12px 12px 0 0;
                    margin-bottom: 0;
                }
                .qr-section {
                    border: 3px solid #7c3aed;
                    padding: 25px;
                    border-radius: 0 0 12px 12px;
                    margin: 0 auto 20px;
                    background: white;
                    max-width: 300px;
                }
                .qr-section img {
                    width: 100%;
                    height: auto;
                }
                .info-section {
                    background: #f8fafc;
                    padding: 20px;
                    border-radius: 12px;
                    border: 1px solid #e2e8f0;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 10px 0;
                    border-bottom: 1px solid #e2e8f0;
                }
                .info-row:last-child {
                    border-bottom: none;
                }
                .label {
                    color: #64748b;
                    font-weight: 600;
                    font-size: 14px;
                }
                .value {
                    color: #1e293b;
                    font-weight: 700;
                    text-align: right;
                }
                .amount {
                    color: #059669;
                }
                .status {
                    background: #dbeafe;
                    color: #1e40af;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                }
                .footer {
                    margin-top: 20px;
                    color: #64748b;
                    font-size: 12px;
                }
                @media print {
                    body {
                        padding: 0;
                    }
                    .print-container {
                        box-shadow: none;
                    }
                }
            </style>
        </head>
        <body>
            <div class="print-container">
                <div class="header">
                    <h1 class="text-2xl font-bold">DV Request QR Code</h1>
                    <p class="opacity-90">Scan to verify request status</p>
                </div>
                
                <div class="qr-section">
                    <img src="${currentQRData.qr_url}" alt="QR Code for ${currentQRData.reference_number}">
                </div>
                
                <div class="info-section">
                    <div class="info-row">
                        <span class="label">Reference Number:</span>
                        <span class="value">${currentQRData.reference_number}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Control Number:</span>
                        <span class="value">${currentQRData.control_number}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Amount:</span>
                        <span class="value amount">₱${currentQRData.amount}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Status:</span>
                        <span class="status">${currentQRData.status}</span>
                    </div>
                </div>
                
                <div class="footer">
                    Generated on ${new Date().toLocaleDateString()} at ${new Date().toLocaleTimeString()}
                </div>
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Wait for content to load before printing
    printWindow.onload = function() {
        setTimeout(() => {
            printWindow.print();
            // Optional: Close the print window after printing
            // printWindow.close();
        }, 500);
    };
}

// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium transform transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('transform', 'translate-x-full');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('transform', 'translate-x-full', 'opacity-0');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// Close modal when clicking outside
document.getElementById('qrModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeQRModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('qrModal').classList.contains('hidden')) {
        closeQRModal();
    }
});
</script>

<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const list = document.getElementById('notification-list');
    const badge = document.getElementById('notification-badge');
    const notifButton = document.getElementById('notification-button');

    // Toast setup
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
    });

    // Reusable render function for notifications
    function renderNotification(n) {
    const url = "{{ url('FocalPerson/dvrequest') }}/" + n.dv_request_id + "/logs";
    const timestamp = n.timestamp
        ? n.timestamp
        : new Date(n.created_at).toLocaleString();

    // Determine color by status
    let colorClass = "bg-gray-400";
    if (n.status?.toLowerCase().includes("approved")) colorClass = "bg-green-500";
    else if (n.status?.toLowerCase().includes("rejected")) colorClass = "bg-red-500";
    else if (n.status?.toLowerCase().includes("pending")) colorClass = "bg-yellow-500";
    else if (n.status?.toLowerCase().includes("processed")) colorClass = "bg-purple-500";

    return `
        <li class="p-3 border-b border-gray-100 hover:bg-purple-50 cursor-pointer transition duration-200">
            <a href="${url}" class="flex items-start space-x-2 w-full h-full">
                <div class="flex-shrink-0">
                    <span class="block w-2.5 h-2.5 ${colorClass} rounded-full mt-2"></span>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-800 font-medium">
                        #${n.dv_request_id} ${n.status.toUpperCase()} — ${n.remarks}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">${timestamp}</p>
                </div>
            </a>
        </li>
    `;
}

    // Helper function: update table & show toast if DV number is new
    function updateDVStatus(dv) {
        const statusEl = document.getElementById('status-' + dv.dv_request_id);
        const dvEl = document.getElementById('dv-' + dv.dv_request_id);
        const deleteBtn = document.getElementById('delete-btn-' + dv.dv_request_id);

        // Update status text
        if (statusEl) {
            const label = statusEl.querySelector('.status-label');
            if (label) {
                label.textContent = dv.status.charAt(0).toUpperCase() + dv.status.slice(1).replace('_', ' ');
            }
            statusEl.dataset.status = dv.status.toLowerCase();
        }

        // Update DV number cell
        if (dvEl) {
            if (dv.dv_number) {
                dvEl.innerHTML = `
                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">
                        ${dv.dv_number}
                    </span>
                `;
                dvEl.dataset.dvNumber = dv.dv_number;
            } else {
                dvEl.innerHTML = `
                    <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded text-xs font-medium">
                        Pending
                    </span>
                `;
                dvEl.dataset.dvNumber = '';
            }
        }

        // Handle delete button visibility
        if (deleteBtn) {
            if (dv.status === 'submitted') {
                deleteBtn.style.display = 'inline-block';
            } else {
                deleteBtn.style.display = 'none';
            }
        }
    }

    // Initial fetch notifications
    try {
        const res = await fetch('{{ route("FocalPerson.notifications.fetch") }}');
        const notifications = await res.json();
        let unreadCount = 0;
        list.innerHTML = '';

        notifications.forEach(n => {
            if (!n.read_at) unreadCount++;
            const li = document.createElement('li');
            li.className = `p-2 bg-gray-100 rounded-md hover:bg-gray-200 cursor-pointer ${!n.read_at ? 'font-bold' : ''}`;
            li.dataset.id = n.id;
            li.innerHTML = renderNotification(n);
            list.appendChild(li);
        });

        badge.textContent = unreadCount;
    } catch (err) {
        console.error('Failed to fetch notifications:', err);
        list.innerHTML = '<li class="p-2 text-red-500">Failed to load notifications</li>';
    }

    // Pusher real-time updates
    Pusher.logToConsole = true;
    const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
        cluster: '{{ env("PUSHER_APP_CLUSTER") }}',
        forceTLS: true
    });

    const userId = '{{ Auth::guard("focal")->id() }}';
    const channel = pusher.subscribe('dv-requests-' + userId);

    // Pusher binding - MOVED OUTSIDE the function
    channel.bind('status-updated', function(data) {
        console.log('Received real-time notification:', data);

        // Update notifications list
        if (list && badge) {
            let count = parseInt(badge.textContent) || 0;
            badge.textContent = count + 1;
            const li = document.createElement('li');
            li.className = 'p-2 bg-gray-100 rounded-md hover:bg-gray-200 cursor-pointer font-bold';
            li.dataset.id = data.id;
            li.innerHTML = renderNotification(data);
            list.prepend(li);
        }
        
        Toast.fire({
            icon: 'info',
            title: 'DV Request Status Update',
            text: `#${data.dv_request_id} ${data.status.toUpperCase()} - ${data.remarks || ''}`
        });
        
        // Update the table with new status
        updateDVStatus(data);
    });

    // Mark notifications as read individually
    list.addEventListener('click', async (e) => {
        const li = e.target.closest('li');
        if (!li) return;
        const id = li.dataset.id;
        if (!id) return;

        try {
            const response = await fetch(`{{ url('FocalPerson/notifications/read') }}/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                li.classList.remove('font-bold');
                let count = parseInt(badge.textContent) || 0;
                badge.textContent = Math.max(count - 1, 0);
            }
        } catch (err) {
            console.error('Failed to mark notification as read:', err);
        }
    });

    // Toggle notification dropdown + mark all as read
    if (notifButton) {
        notifButton.addEventListener('click', async () => {
            list.classList.toggle('hidden');
            if (!list.classList.contains('hidden')) {
                try {
                    await fetch('{{ route("FocalPerson.notifications.markAllAsRead") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        }
                    });
                    badge.textContent = '0';
                    list.querySelectorAll('li').forEach(li => li.classList.remove('font-bold'));
                } catch (err) {
                    console.error('Failed to mark all as read:', err);
                }
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!notifButton.contains(e.target) && !list.contains(e.target)) {
            list.classList.add('hidden');
        }
    });

    // Auto-refresh DV requests every 60s
    setInterval(async () => {
        try {
            const res = await fetch('{{ route("FocalPerson.dvrequest.refresh") }}');
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const updated = await res.json();

            updated.forEach(dv => updateDVStatus(dv));
        } catch (err) {
            console.error("Auto-refresh failed:", err);
        }
    }, 60000);
});
</script>
@endsection