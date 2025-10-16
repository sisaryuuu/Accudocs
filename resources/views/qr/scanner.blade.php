@extends('layouts.app')

@section('title', 'Scan DV Request')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Scan QR Code</h1>
            <p class="text-gray-600">Use your phone’s camera to scan and view your DV Request status</p>
            
        </div>

        <!-- Scanner Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                
                <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    QR Code Scanner   <a href="{{ route('FocalPerson.dvrequest.index') }}" 
                       class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-xl font-medium transition-all duration-200 flex items-center space-x-2 backdrop-blur-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>herd link accudocsproject
                        </svg>
                        <span>Back to Active Requests</span>
                    </a>
                </h2>
                
            </div>

            <div class="p-6">
                <!-- Tab Navigation -->
                <div class="flex gap-2 mb-6">
                    <button id="cameraTab" onclick="switchTab('camera')"
                        class="flex-1 py-3 px-4 bg-blue-600 text-white font-semibold rounded-lg transition-all">
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
                    <div id="scanner-container" class="relative">
                        <div id="reader" class="w-full rounded-lg overflow-hidden border-4 border-gray-200"></div>
                        <div id="scanning-overlay"
                            class="absolute inset-0 bg-black bg-opacity-50 hidden items-center justify-center rounded-lg">
                            <div class="text-center">
                                <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-white mx-auto mb-4"></div>
                                <p class="text-white font-semibold">Processing QR Code...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload Section -->
                <div id="uploadSection" class="hidden mb-6">
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
                            class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg cursor-pointer transition-colors">
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
                </div>

                <!-- Manual Input -->
                <div class="border-t pt-6">
                    <p class="text-center text-gray-600 mb-4">Or enter reference number manually:</p>
                    <form action="{{ route('dv.verify.manual') }}" method="POST" class="flex gap-3">
                        @csrf
                        <input type="text" name="reference_number" placeholder="DVORS-XXXXXXXX"
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required>
                        <button type="submit"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                            Check Status
                        </button>
                    </form>
                </div>

                <div id="status-message" class="mt-4 hidden">
                    <div class="p-4 rounded-lg" id="status-content"></div>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="mt-8 bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                How to Use
            </h3>
            <ul class="space-y-2 text-gray-600">
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span><strong>Camera:</strong> Hold the QR code within the scanner frame.</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span><strong>Upload:</strong> Choose a photo of the QR code if camera fails.</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Ensure your site is accessed via <strong>HTTPS</strong> for camera access.</span>
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

function switchTab(tab) {
    const cameraTab = document.getElementById('cameraTab');
    const uploadTab = document.getElementById('uploadTab');
    const cameraSection = document.getElementById('cameraSection');
    const uploadSection = document.getElementById('uploadSection');

    if (tab === 'camera') {
        cameraTab.classList.add('bg-blue-600', 'text-white');
        cameraTab.classList.remove('bg-gray-200', 'text-gray-700');
        uploadTab.classList.remove('bg-blue-600', 'text-white');
        uploadTab.classList.add('bg-gray-200', 'text-gray-700');
        cameraSection.classList.remove('hidden');
        uploadSection.classList.add('hidden');
        if (html5QrCode && !isScanning) startCamera();
    } else {
        uploadTab.classList.add('bg-blue-600', 'text-white');
        uploadTab.classList.remove('bg-gray-200', 'text-gray-700');
        cameraTab.classList.remove('bg-blue-600', 'text-white');
        cameraTab.classList.add('bg-gray-200', 'text-gray-700');
        cameraSection.classList.add('hidden');
        uploadSection.classList.remove('hidden');
        if (html5QrCode && isScanning) {
            html5QrCode.stop().catch(console.error);
            isScanning = false;
        }
    }
}

function extractReferenceNumber(decodedText) {
    console.log('🔍 Extracting from:', decodedText);
    
    try {
        const jsonData = JSON.parse(decodedText);
        if (jsonData.reference_number) {
            console.log('✅ Found in JSON:', jsonData.reference_number);
            return jsonData.reference_number;
        }
        if (jsonData.url) {
            const urlMatch = jsonData.url.match(/DVORS-[A-Z0-9]+/i);
            if (urlMatch) {
                console.log('✅ Found in JSON URL:', urlMatch[0]);
                return urlMatch[0];
            }
        }
    } catch (e) {
        // Not JSON, continue
    }

    // Check if it's a full URL
    if (decodedText.startsWith('http')) {
        const urlMatch = decodedText.match(/DVORS-[A-Z0-9]+/i);
        if (urlMatch) {
            console.log('✅ Found in URL:', urlMatch[0]);
            return urlMatch[0];
        }
    }

    // Check if it contains /dv/verify/
    if (decodedText.includes('/dv/verify/')) {
        const parts = decodedText.split('/dv/verify/');
        if (parts[1]) {
            const ref = parts[1].split(/[?#/]/)[0];
            console.log('✅ Found in path:', ref);
            return ref;
        }
    }

    // Check if it's just a reference number
    const refMatch = decodedText.match(/DVORS-[A-Z0-9]+/i);
    if (refMatch) {
        console.log('✅ Found as reference:', refMatch[0]);
        return refMatch[0];
    }

    console.log('❌ No reference found');
    return decodedText.trim();
}

function processQRData(decodedText) {
    console.log('📱 RAW QR DATA:', decodedText);
    console.log('📱 DATA LENGTH:', decodedText.length);
    
    const baseUrl = window.location.origin;
    const referenceNumber = extractReferenceNumber(decodedText);
    
    console.log('🎯 EXTRACTED REFERENCE:', referenceNumber);
    
    if (!referenceNumber) {
        showError('Invalid QR code format. Please try again or enter manually.');
        isScanning = false;
        startCamera();
        return;
    }

    // Validate format
    if (!referenceNumber.match(/^DVORS-[A-Z0-9]+$/i)) {
        showError('Invalid reference number format. Expected: DVORS-XXXXXXXX');
        isScanning = false;
        startCamera();
        return;
    }

    // Show success message
    showSuccess('QR Code detected! Redirecting...');
    
    // Redirect to verification page
    const verifyUrl = `${baseUrl}/dv/verify/${referenceNumber}`;
    console.log('🚀 REDIRECTING TO:', verifyUrl);
    
    setTimeout(() => {
        window.location.href = verifyUrl;
    }, 1000);
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
                <span class="text-red-800 font-semibold">${message}</span>
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
                <span class="text-green-800 font-semibold">${message}</span>
            </div>
        </div>
    `;
    
    statusDiv.classList.remove('hidden');
}

function onScanSuccess(decodedText, decodedResult) {
    // Prevent multiple scans
    if (isScanning) {
        console.log('⏳ Already processing a scan...');
        return;
    }
    
    scanCount++;
    console.log(`\n🎉 SCAN SUCCESS #${scanCount}`);
    console.log('📊 Decoded Result:', decodedResult);
    
    isScanning = true;
    const overlay = document.getElementById('scanning-overlay');
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    
    // Stop camera and process
    html5QrCode.stop().then(() => {
        console.log('📸 Camera stopped successfully');
        processQRData(decodedText);
    }).catch(err => {
        console.error('❌ Error stopping scanner:', err);
        processQRData(decodedText);
    });
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

function startCamera() {
    if (isScanning) {
        console.log('⏳ Camera already starting/scanning...');
        return;
    }
    
    console.log('📸 Starting camera...');
    isScanning = true;
    
    // Enhanced configuration for better scanning
    const config = { 
        fps: 30,  // Increased FPS for faster detection
        qrbox: { width: 300, height: 300 },  // Larger scan area
        aspectRatio: 1.0,
        disableFlip: false,  // Allow both orientations
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

        // Prefer back camera for phone, any camera for desktop
        let selectedCamera = cameras[0];
        
        const backCamera = cameras.find(c => 
            c.label.toLowerCase().includes('back') || 
            c.label.toLowerCase().includes('rear') ||
            c.label.toLowerCase().includes('environment')
        );
        
        if (backCamera) {
            selectedCamera = backCamera;
            console.log('✅ Using back camera:', selectedCamera.label);
        } else {
            console.log('✅ Using camera:', selectedCamera.label);
        }

        html5QrCode.start(
            selectedCamera.id, 
            config, 
            onScanSuccess,
            (errorMessage) => {
                // This fires constantly while scanning - don't log
            }
        ).then(() => {
            console.log('✅ Camera started successfully');
            isScanning = false; // Allow processing when QR is detected
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
    console.log('🚀 Initializing QR Scanner...');
    html5QrCode = new Html5Qrcode("reader");
    startCamera();
});

window.addEventListener('beforeunload', () => {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().catch(console.error);
    }
});
</script>