<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regional Director QR Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Regional Director QR Scanner</h1>
                <p class="text-gray-600">Scan QR codes to approve and track DV payments</p>
                
                <!-- Back Button -->
                <div class="mt-4">
                    <a href="{{ route('regional.director.dashboard') }}" 
                       class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 px-6 py-3 rounded-xl font-medium transition-all duration-200 shadow-sm border border-gray-200">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Dashboard</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Scanner Card -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                            <h2 class="text-xl font-semibold text-white flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-qrcode"></i>
                                    Regional Director QR Scanner
                                </div>
                                <span class="bg-white/20 px-3 py-1 rounded-full text-sm">Regional Director</span>
                            </h2>
                        </div>

                        <div class="p-6">
                            <!-- Authentication Status -->
                            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-user-shield text-blue-600"></i>
                                    <div>
                                        <p class="text-blue-800 font-semibold">Logged in as: Regional Director</p>
                                        <p class="text-blue-600 text-sm">Role: Regional Director - Ready to scan and approve DVs</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Navigation -->
                            <div class="flex gap-2 mb-6">
                                <button id="cameraTab" onclick="switchTab('camera')"
                                    class="flex-1 py-3 px-4 bg-blue-600 text-white font-semibold rounded-lg transition-all">
                                    <i class="fas fa-camera mr-2"></i>
                                    Camera Scan
                                </button>
                                <button id="uploadTab" onclick="switchTab('upload')"
                                    class="flex-1 py-3 px-4 bg-gray-200 text-gray-700 font-semibold rounded-lg transition-all">
                                    <i class="fas fa-upload mr-2"></i>
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
                                            <p class="text-white font-semibold">Redirecting to DV Details...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Section -->
                            <div id="uploadSection" class="hidden mb-6">
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Upload QR Code Image</h3>
                                    <p class="text-gray-500 mb-4">Supports PNG, JPG, or SVG</p>

                                    <input type="file" id="qrFileInput" accept="image/*,.png,.jpg,.jpeg,.svg" class="hidden"
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

                            <!-- Manual Token Entry -->
                            <div class="border-t pt-6">
                                <p class="text-center text-gray-600 mb-4">Or enter QR token manually:</p>
                                <div class="flex gap-3">
                                    <input type="text" id="manualToken" placeholder="Enter QR token"
                                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <button onclick="processManualToken()"
                                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                        Open DV
                                    </button>
                                </div>
                            </div>

                            <div id="status-message" class="mt-4 hidden">
                                <div class="p-4 rounded-lg" id="status-content"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Instructions -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-info-circle text-blue-600"></i>
                            How to Use
                        </h3>
                        <ul class="space-y-2 text-gray-600">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-blue-500 mt-0.5"></i>
                                <span><strong>Scan QR Code:</strong> Point camera at DV QR code to open approval page</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-blue-500 mt-0.5"></i>
                                <span><strong>Review & Approve:</strong> Check DV details and approve/reject payment</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-blue-500 mt-0.5"></i>
                                <span><strong>Track Status:</strong> Monitor approval status and payment progress</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-blue-500 mt-0.5"></i>
                                <span>Ensure your site is accessed via <strong>HTTPS</strong> for camera access</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-bolt text-yellow-500"></i>
                            Quick Actions
                        </h3>
                        <div class="space-y-3">
                            <button onclick="toggleCamera()" class="w-full flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                <i class="fas fa-camera text-blue-600"></i>
                                <span id="camera-toggle-text">Pause Camera</span>
                            </button>
                        </div>
                    </div>

                    <!-- Recent Scans -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-history text-green-600"></i>
                            Recent Scans
                        </h3>
                        <div id="recent-scans-list" class="space-y-3">
                            <div class="text-center py-4 text-gray-500">
                                <i class="fas fa-inbox text-2xl mb-2"></i>
                                <p>No recent scans</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Processing Modal -->
    <div id="processingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl p-6 max-w-sm w-full mx-4">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Processing DV...</h3>
                <p class="text-gray-600" id="processing-message">Updating DV status...</p>
            </div>
        </div>
    </div>

    <!-- Result Modal -->
    <div id="resultModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-900" id="result-title">Status Update Result</h3>
                <button onclick="closeResultModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-6" id="result-content"></div>
            <div class="flex justify-end gap-3">
                <button onclick="closeResultModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors">
                    Close
                </button>
                <button onclick="location.reload()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    Scan Another
                </button>
            </div>
        </div>
    </div>

    <script>
        let html5QrCode;
        let isScanning = false;
        let isCameraOn = true;
        let scanCount = 0;
        let recentScans = [];

        // Check if user is authenticated and has Regional Director role
        const isAuthenticatedRegionalDirector = true; // In real app, this would check actual auth status

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
                if (html5QrCode && !isScanning && isAuthenticatedRegionalDirector) startCamera();
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
            
            // Show processing modal
            showProcessingModal('Redirecting to DV status page...');
            
            // Build the URL for the QR status update interface
            const statusUpdateUrl = `/dv/qr/update-status/${token}`;
            
            console.log('🚀 Redirecting to:', statusUpdateUrl);
            
            // Redirect after a brief delay
            setTimeout(() => {
                hideProcessingModal();
                window.location.href = statusUpdateUrl;
            }, 1000);
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
                        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
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
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        <div class="text-green-800">${message}</div>
                    </div>
                </div>
            `;
            
            statusDiv.classList.remove('hidden');
            
            setTimeout(() => {
                statusDiv.classList.add('hidden');
            }, 3000);
        }

        function showProcessingModal(message) {
            document.getElementById('processing-message').textContent = message;
            document.getElementById('processingModal').classList.remove('hidden');
        }

        function hideProcessingModal() {
            document.getElementById('processingModal').classList.add('hidden');
        }

        function showResultModal(title, content) {
            document.getElementById('result-title').textContent = title;
            document.getElementById('result-content').innerHTML = content;
            document.getElementById('resultModal').classList.remove('hidden');
        }

        function closeResultModal() {
            document.getElementById('resultModal').classList.add('hidden');
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
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
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
                                    <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
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

        function toggleCamera() {
            if (isCameraOn) {
                html5QrCode.stop().then(() => {
                    document.getElementById('camera-toggle-text').textContent = 'Start Camera';
                    isCameraOn = false;
                }).catch(err => {
                    console.error('Error stopping camera:', err);
                });
            } else {
                startCamera();
                document.getElementById('camera-toggle-text').textContent = 'Pause Camera';
                isCameraOn = true;
            }
        }

        function addToRecentScans(scan) {
            recentScans.unshift(scan);
            if (recentScans.length > 5) {
                recentScans = recentScans.slice(0, 5);
            }
            updateRecentScansDisplay();
        }

        function updateRecentScansDisplay() {
            const container = document.getElementById('recent-scans-list');
            if (recentScans.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-4 text-gray-500">
                        <i class="fas fa-inbox text-2xl mb-2"></i>
                        <p>No recent scans</p>
                    </div>
                `;
                return;
            }

            let html = '';
            recentScans.forEach(scan => {
                const badgeClass = scan.success ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                html += `
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div>
                            <div class="font-semibold">${scan.dv_number}</div>
                            <div class="text-sm text-gray-500">${scan.timestamp}</div>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-medium ${badgeClass}">
                            ${scan.new_status || 'Failed'}
                        </span>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        document.addEventListener('DOMContentLoaded', () => {
            console.log('🚀 Initializing Regional Director QR Scanner...');
            
            if (isAuthenticatedRegionalDirector) {
                html5QrCode = new Html5Qrcode("reader");
                startCamera();
            } else {
                console.log('⚠️ User not authorized as Regional Director, scanner disabled');
            }
        });

        window.addEventListener('beforeunload', () => {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().catch(console.error);
            }
        });
    </script>
</body>
</html>