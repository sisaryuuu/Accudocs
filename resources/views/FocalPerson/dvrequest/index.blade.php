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
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Request History</h3>
                    <div class="relative">
                        <input type="text" id="search" placeholder="Search Control No., Payee, Program..."
                               class="w-72 pl-10 pr-4 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M21 21l-4.35-4.35M16 10a6 6 0 11-12 0 6 6 0 0112 0z"/>
                        </svg>
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
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Mode of Payment</th>
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
                                    {{ $request->respocenter->rccode ?? 'N/A' }} - {{ $request->respocenter->description ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $request->program->prcode ?? 'N/A' }}
                                    </span>
                                </td>
                               <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    @if($request->modeOfPayments && $request->modeOfPayments->count() > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1">
                                            {{ $request->modeOfPayments->pluck('mopname')->join(', ') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
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
                                    <span class="status-label">{{ ucfirst(str_replace('_', ' ', $request->status)) }}</span>
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
                                    @endif
                                     @if($request->status === 'ORS_for_revision' || $request->status === 'submitted')
                                            <a href="{{ route('FocalPerson.dvrequest.edit', $request->id) }}"
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

                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ $dvRequests->count() }} of {{ $dvRequests->count() }} requests
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

// QR Modal Functions
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
    <div class="space-y-4">
        <!-- QR Code Image - Updated for SVG support -->
        <div class="bg-white p-4 rounded-lg border-2 border-purple-200 inline-block">
            <object id="qrImage" data="${data.qr_url}" type="image/svg+xml" class="w-64 h-64 mx-auto">
                <img src="${data.qr_url}" alt="QR Code" class="w-64 h-64 mx-auto" onerror="this.parentElement.innerHTML='<p class=\'text-red-500\'>QR Code not found</p>'">
            </object>
        </div>
        
        <!-- Request Info -->
        <div class="bg-gray-50 rounded-lg p-4 text-left">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-gray-500 font-medium">Reference Number:</p>
                    <p class="text-gray-900 font-semibold">${data.reference_number}</p>
                </div>
                <div>
                    <p class="text-gray-500 font-medium">Control Number:</p>
                    <p class="text-gray-900 font-semibold">${data.control_number}</p>
                </div>
                <div>
                    <p class="text-gray-500 font-medium">Amount:</p>
                    <p class="text-green-600 font-bold">₱${data.amount}</p>
                </div>
                <div>
                    <p class="text-gray-500 font-medium">Status:</p>
                    <p class="text-blue-600 font-semibold">${data.status}</p>
                </div>
            </div>
        </div>
        
        <!-- Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-left">
            <p class="text-xs text-blue-800">
                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                Scan this QR code to view your DV Request status anytime, anywhere.
            </p>
        </div>
    </div>
`;
        
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
    link.download = `QR_${currentQRData.reference_number}.svg`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    Swal.fire({
        icon: 'success',
        title: 'Downloaded!',
        text: 'QR Code has been saved to your device',
        timer: 2000,
        showConfirmButton: false
    });
}

function printQR() {
    if (!currentQRData) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print QR Code - ${currentQRData.reference_number}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                }
                .container {
                    text-align: center;
                    max-width: 500px;
                }
                .qr-box {
                    border: 3px solid #7c3aed;
                    padding: 20px;
                    border-radius: 10px;
                    margin: 20px 0;
                    background: white;
                }
                img {
                    max-width: 300px;
                    height: auto;
                }
                .info {
                    background: #f3f4f6;
                    padding: 15px;
                    border-radius: 8px;
                    margin-top: 20px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 10px 0;
                    padding: 8px;
                    border-bottom: 1px solid #e5e7eb;
                }
                .label {
                    font-weight: bold;
                    color: #6b7280;
                }
                .value {
                    color: #111827;
                    font-weight: 600;
                }
                h1 {
                    color: #7c3aed;
                    margin-bottom: 10px;
                }
                .subtitle {
                    color: #6b7280;
                    font-size: 14px;
                }
                @media print {
                    body {
                        padding: 0;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>DV Request QR Code</h1>
                <p class="subtitle">Scan to verify request status</p>
                
                <div class="qr-box">
                    <img src="${currentQRData.qr_url}" alt="QR Code">
                </div>
                
                <div class="info">
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
                        <span class="value">₱${currentQRData.amount}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Status:</span>
                        <span class="value">${currentQRData.status}</span>
                    </div>
                </div>
                
                <p style="margin-top: 20px; color: #6b7280; font-size: 12px;">
                    Generated on ${new Date().toLocaleString()}
                </p>
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    setTimeout(() => {
        printWindow.print();
    }, 500);
}

// Close modal when clicking outside
document.getElementById('qrModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
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
        const timestamp = n.timestamp ? n.timestamp : new Date(n.created_at).toLocaleString();
        return `
            <a href="${url}" class="block w-full h-full">
                [${timestamp}] #${n.dv_request_id} ${n.status.toUpperCase()} - ${n.remarks}
            </a>
        `;
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

    // Pusher binding
    channel.unbind('status-updated');
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