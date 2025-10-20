@extends('layouts.app')

@section('title', 'Cashier QR Scanner')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 to-teal-100 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Cashier Payment Scanner</h1>
            <p class="text-gray-600">Scan QR codes to process DV payments and update payment status</p>
            
            <!-- Back Button -->
            <div class="mt-4">
                <a href="{{ route('cashier.dashboard') }}" 
                   class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 px-6 py-3 rounded-xl font-medium transition-all duration-200 shadow-sm border border-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Scanner Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-teal-600 px-6 py-4">
                <h2 class="text-xl font-semibold text-white flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        Payment QR Code Scanner
                    </div>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-sm">Cashier</span>
                </h2>
            </div>

            <div class="p-6">
                <!-- Authentication Status -->
                @auth
                    @if(auth()->user()->hasRole('Cashier'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <div>
                                    <p class="text-green-800 font-semibold">Logged in as: {{ auth()->user()->name }}</p>
                                    <p class="text-green-600 text-sm">Role: Cashier - Ready to scan QR codes</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="text-red-800 font-semibold">Insufficient Permissions</p>
                                    <p class="text-red-600 text-sm">You need Cashier role to scan and process payments</p>
                                    <p class="text-red-600 text-sm">Current role: {{ auth()->user()->getRoleNames()->first() ?? 'No role assigned' }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <div>
                                <p class="text-blue-800 font-semibold">Authentication Required</p>
                                <p class="text-blue-600 text-sm">Please log in to scan QR codes and process payments</p>
                                <a href="{{ route('login') }}" class="inline-block mt-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    Login Now
                                </a>
                            </div>
                        </div>
                    </div>
                @endauth

                <!-- Tab Navigation -->
                <div class="flex gap-2 mb-6">
                    <button id="cameraTab" onclick="switchTab('camera')"
                        class="flex-1 py-3 px-4 bg-green-600 text-white font-semibold rounded-lg transition-all">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Camera Scan
                    </button>
                    <button id="uploadTab" onclick="switchTab('upload')"
                        class="flex-1 py-3 px-4 bg-gray-200 text-gray-700 font-semibold rounded-lg transition-all">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        Upload QR
                    </button>
                </div>

                <!-- Camera Scanner Section -->
                <div id="cameraSection" class="mb-6">
                    @auth
                        @if(auth()->user()->hasRole('Cashier'))
                            <div id="scanner-container" class="relative">
                                <div id="reader" class="w-full rounded-lg overflow-hidden border-4 border-gray-200"></div>
                                <div id="scanning-overlay"
                                    class="absolute inset-0 bg-black bg-opacity-50 hidden items-center justify-center rounded-lg">
                                    <div class="text-center">
                                        <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-white mx-auto mb-4"></div>
                                        <p class="text-white font-semibold">Redirecting to DV Details...</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8 bg-gray-50 rounded-lg">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-700 mb-2">Permission Required</h3>
                                <p class="text-gray-500">You need Cashier role to access the scanner</p>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Please Log In</h3>
                            <p class="text-gray-500 mb-4">You need to be logged in to use the QR scanner</p>
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                Login to Continue
                            </a>
                        </div>
                    @endauth
                </div>

                <!-- Upload Section -->
                <div id="uploadSection" class="hidden mb-6">
                    @auth
                        @if(auth()->user()->hasRole('Cashier'))
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-700 mb-2">Upload QR Code Image</h3>
                                <p class="text-gray-500 mb-4">Supports PNG, JPG, or SVG</p>

                                <input type="file" id="qrFileInput" accept="image/*,.svg" class="hidden"
                                    onchange="handleFileUpload(event)">
                                <label for="qrFileInput"
                                    class="inline-block px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg cursor-pointer transition-colors">
                                    Choose File
                                </label>

                                <div id="uploadPreview" class="hidden mt-6">
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <img id="previewImage" class="max-w-xs mx-auto rounded-lg shadow-md">
                                        <p class="text-sm text-gray-600 mt-3" id="fileName"></p>
                                    </div>
                                </div>

                                <div id="uploadResult" class="hidden mt-4"></div>
                            </div>
                        @else
                            <div class="text-center py-8 bg-gray-50 rounded-lg">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-700 mb-2">Permission Required</h3>
                                <p class="text-gray-500">You need Cashier role to upload QR codes</p>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Please Log In</h3>
                            <p class="text-gray-500 mb-4">You need to be logged in to upload QR codes</p>
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                Login to Continue
                            </a>
                        </div>
                    @endauth
                </div>

                <!-- Manual Token Entry -->
                <div class="border-t pt-6">
                    <p class="text-center text-gray-600 mb-4">Or enter QR token manually:</p>
                    <div class="flex gap-3">
                        <input type="text" id="manualToken" placeholder="Enter QR token"
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            @guest disabled @endguest>
                        <button onclick="processManualToken()"
                            class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                            @guest disabled @endguest>
                            Open DV
                        </button>
                    </div>
                    @guest
                        <p class="text-center text-gray-500 text-sm mt-2">Please log in to use manual token entry</p>
                    @endguest
                </div>

                <div id="status-message" class="mt-4 hidden">
                    <div class="p-4 rounded-lg" id="status-content"></div>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="mt-8 bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                How to Use
            </h3>
            <ul class="space-y-2 text-gray-600">
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span><strong>Login Required:</strong> You must be logged in with Cashier role to scan QR codes</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span><strong>Scan QR Code:</strong> Point camera at DV QR code to open status update page</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span><strong>Update Status:</strong> On the status page, mark as received or released</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Ensure your site is accessed via <strong>HTTPS</strong> for camera access</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
let html5QrCode;
let isScanning = false;
let scanCount = 0;

// Check if user is authenticated and has Cashier role
const isAuthenticatedCashier = {{ auth()->check() && auth()->user()->hasRole('Cashier') ? 'true' : 'false' }};
const isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};

function switchTab(tab) {
    const cameraTab = document.getElementById('cameraTab');
    const uploadTab = document.getElementById('uploadTab');
    const cameraSection = document.getElementById('cameraSection');
    const uploadSection = document.getElementById('uploadSection');

    if (tab === 'camera') {
        cameraTab.classList.add('bg-green-600', 'text-white');
        cameraTab.classList.remove('bg-gray-200', 'text-gray-700');
        uploadTab.classList.remove('bg-green-600', 'text-white');
        uploadTab.classList.add('bg-gray-200', 'text-gray-700');
        cameraSection.classList.remove('hidden');
        uploadSection.classList.add('hidden');
        if (html5QrCode && !isScanning && isAuthenticatedCashier) startCamera();
    } else {
        uploadTab.classList.add('bg-green-600', 'text-white');
        uploadTab.classList.remove('bg-gray-200', 'text-gray-700');
        cameraTab.classList.remove('bg-green-600', 'text-white');
        cameraTab.classList.add('bg-gray-200', 'text-gray-700');
        cameraSection.classList.add('hidden');
        uploadSection.classList.remove('hidden');
        if (html5QrCode && isScanning) {
            html5QrCode.stop().catch(console.error);
            isScanning = false;
        }
    }
}

function extractToken(decodedText) {
    console.log('🔍 Extracting token from:', decodedText);
    
    try {
        const jsonData = JSON.parse(decodedText);
        if (jsonData.token) {
            console.log('✅ Found token in JSON:', jsonData.token);
            return jsonData.token;
        }
        if (jsonData.action_url) {
            const urlMatch = jsonData.action_url.match(/\/dv\/qr\/update-status\/([a-f0-9]+)/);
            if (urlMatch) {
                console.log('✅ Found token in action_url:', urlMatch[1]);
                return urlMatch[1];
            }
        }
    } catch (e) {
        // Not JSON, continue
    }

    // Check if it's a full URL
    if (decodedText.includes('/dv/qr/update-status/')) {
        const tokenMatch = decodedText.match(/\/dv\/qr\/update-status\/([a-f0-9]+)/);
        if (tokenMatch) {
            console.log('✅ Found token in URL:', tokenMatch[1]);
            return tokenMatch[1];
        }
    }

    // Check if it's just a token (64 char hex)
    const tokenMatch = decodedText.match(/^[a-f0-9]{64}$/i);
    if (tokenMatch) {
        console.log('✅ Found as token:', tokenMatch[0]);
        return tokenMatch[0];
    }

    console.log('❌ No token found');
    return decodedText.trim();
}

function processQRData(decodedText) {
    console.log('📱 RAW QR DATA:', decodedText);
    
    const token = extractToken(decodedText);
    
    console.log('🎯 EXTRACTED TOKEN:', token);
    
    if (!token) {
        showError('Invalid QR code format. Please scan a valid DV QR code.');
        isScanning = false;
        startCamera();
        return;
    }

    // Redirect to the status update page
    redirectToStatusUpdate(token);
}

function redirectToStatusUpdate(token) {
    console.log('🎯 Redirecting to status update page with token:', token);
    
    // Show processing overlay
    const overlay = document.getElementById('scanning-overlay');
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    
    // Build the URL for the QR status update interface
    const statusUpdateUrl = `/dv/qr/update-status/${token}`;
    
    console.log('🚀 Redirecting to:', statusUpdateUrl);
    
    // Check if user is authenticated and has correct role
    if (isAuthenticatedCashier) {
        // User is authenticated as Cashier, proceed directly
        window.location.href = statusUpdateUrl;
    } else if (isAuthenticated) {
        // User is authenticated but not as Cashier
        showError('You need Cashier role to process payments. Please log in with Cashier account.');
        isScanning = false;
        startCamera();
    } else {
        // User is not authenticated, redirect to login with token
        const loginUrl = `/login?qr_redirect=${token}&role=cashier`;
        console.log('🔐 Redirecting to login:', loginUrl);
        window.location.href = loginUrl;
    }
}

function processManualToken() {
    const manualToken = document.getElementById('manualToken').value.trim();
    
    if (!manualToken) {
        showError('Please enter a QR token');
        return;
    }

    console.log('📝 Processing manual token:', manualToken);
    redirectToStatusUpdate(manualToken);
}

function showError(message) {
    const statusDiv = document.getElementById('status-message');
    const statusContent = document.getElementById('status-content');
    
    statusContent.innerHTML = `
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-red-800">${message}</span>
            </div>
        </div>
    `;
    
    statusDiv.classList.remove('hidden');
    
    setTimeout(() => {
        statusDiv.classList.add('hidden');
    }, 5000);
}

function showSuccess(message) {
    const statusDiv = document.getElementById('status-message');
    const statusContent = document.getElementById('status-content');
    
    statusContent.innerHTML = `
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <div class="text-green-800">${message}</div>
            </div>
        </div>
    `;
    
    statusDiv.classList.remove('hidden');
    
    setTimeout(() => {
        statusDiv.classList.add('hidden');
    }, 3000);
}

function handleFileUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    const preview = document.getElementById('uploadPreview');
    const previewImage = document.getElementById('previewImage');
    const fileName = document.getElementById('fileName');
    const resultDiv = document.getElementById('uploadResult');

    fileName.textContent = file.name;
    
    const reader = new FileReader();
    reader.onload = (e) => {
        previewImage.src = e.target.result;
        preview.classList.remove('hidden');
        
        resultDiv.classList.remove('hidden');
        resultDiv.innerHTML = `
            <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
                <div class="flex items-center">
                    <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600 mr-2"></div>
                    <span class="text-blue-800 font-semibold">Scanning QR code...</span>
                </div>
            </div>
        `;

        const tempScanner = new Html5Qrcode("reader");
        tempScanner.scanFile(file, true)
            .then(decodedText => {
                console.log('📁 File scan success:', decodedText);
                resultDiv.innerHTML = `
                    <div class="bg-green-50 border-l-4 border-green-500 p-3 rounded">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-green-800 font-semibold">QR detected! Redirecting...</span>
                        </div>
                    </div>
                `;
                setTimeout(() => processQRData(decodedText), 1000);
            })
            .catch(err => {
                console.error('❌ File scan error:', err);
                resultDiv.innerHTML = `
                    <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-red-800 font-semibold">No valid QR code found in image</span>
                        </div>
                    </div>
                `;
            });
    };
    
    reader.readAsDataURL(file);
}

function onScanSuccess(decodedText, decodedResult) {
    if (isScanning) {
        console.log('⏳ Already processing a scan...');
        return;
    }
    
    scanCount++;
    console.log(`\n🎉 SCAN SUCCESS #${scanCount}`);
    
    isScanning = true;
    
    // Stop camera and process
    html5QrCode.stop().then(() => {
        console.log('📸 Camera stopped successfully');
        processQRData(decodedText);
    }).catch(err => {
        console.error('❌ Error stopping scanner:', err);
        processQRData(decodedText);
    });
}

function startCamera() {
    if (isScanning) {
        console.log('⏳ Camera already starting/scanning...');
        return;
    }
    
    console.log('📸 Starting camera...');
    isScanning = true;
    
    const config = { 
        fps: 30,
        qrbox: { width: 300, height: 300 },
        aspectRatio: 1.0,
        disableFlip: false,
        rememberLastUsedCamera: true,
        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
    };

    Html5Qrcode.getCameras().then(cameras => {
        console.log('📷 Available cameras:', cameras);
        
        if (!cameras || cameras.length === 0) {
            showError('No cameras found on this device.');
            isScanning = false;
            return;
        }

        let selectedCamera = cameras[0];
        
        const backCamera = cameras.find(c => 
            c.label.toLowerCase().includes('back') || 
            c.label.toLowerCase().includes('rear') ||
            c.label.toLowerCase().includes('environment')
        );
        
        if (backCamera) {
            selectedCamera = backCamera;
        }

        html5QrCode.start(
            selectedCamera.id, 
            config, 
            onScanSuccess,
            (errorMessage) => {
                // Ignore continuous scan errors
            }
        ).then(() => {
            console.log('✅ Camera started successfully');
            isScanning = false;
        }).catch(err => {
            console.error('❌ Camera start error:', err);
            showError('Camera access denied or unavailable. Please use HTTPS or try uploading a QR image.');
            isScanning = false;
        });
    }).catch(err => {
        console.error('❌ Get cameras error:', err);
        showError('Unable to access camera. Please check permissions.');
        isScanning = false;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Initializing Cashier QR Scanner...');
    
    if (isAuthenticatedCashier) {
        html5QrCode = new Html5Qrcode("reader");
        startCamera();
    } else {
        console.log('⚠️ User not authorized as Cashier, scanner disabled');
    }
});

window.addEventListener('beforeunload', () => {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().catch(console.error);
    }
});
</script>
@endsection