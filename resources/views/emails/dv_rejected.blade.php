<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DV Request Rejected</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans">
    <div class="max-w-2xl mx-auto bg-white shadow-sm">
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 to-red-800 text-white px-6 py-8 text-center rounded-t-lg">
            <h2 class="text-2xl font-bold mb-2">DV Request Status Update</h2>
            <p class="text-red-100">ACCUDOCS DOLE XI - IMSD System</p>
        </div>

        <!-- Content -->
        <div class="px-6 py-8">
            <!-- Greeting -->
            <p class="text-gray-700 text-lg mb-6">
                Dear <span class="font-semibold">{{ $focalPerson->fname ?? 'User' }}</span>,
            </p>

            <!-- Alert Banner -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6 border-l-4 border-l-red-500">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-red-800 font-bold text-lg">Your DV Request has been rejected by IMSD</h3>
                </div>
            </div>

            <!-- Request Details -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6 shadow-sm">
                <h3 class="text-red-700 font-bold text-lg mb-4 border-b border-gray-100 pb-2">Request Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Control Number</p>
                            <p class="text-gray-800 font-semibold">
                                {{ $dvRequest->accounting_control_number ? 'CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 5, '0', STR_PAD_LEFT) : 'Pending-CN' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Program</p>
                            <p class="text-gray-800 font-semibold">{{ $dvRequest->program->prcode ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Amount</p>
                            <p class="text-gray-800 font-semibold">₱{{ number_format($dvRequest->amount, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 font-medium">Rejection Date</p>
                            <p class="text-gray-800 font-semibold">{{ $rejectedAt }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rejection Remarks -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                <h3 class="text-yellow-800 font-bold text-lg mb-3">Rejection Remarks</h3>
                <div class="bg-white border border-yellow-300 rounded-md p-4">
                    <p class="text-gray-800 leading-relaxed">{{ $rejectionRemarks }}</p>
                </div>
            </div>

            <!-- Action Required -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-500 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h4 class="text-blue-800 font-bold text-lg mb-2">Action Required</h4>
                        <p class="text-blue-700">
                            Please review the remarks above and resubmit your request with the necessary corrections.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                <h4 class="text-gray-800 font-bold text-lg mb-3">Next Steps</h4>
                <ul class="list-disc list-inside space-y-2 text-gray-700">
                    <li>Review the rejection remarks carefully</li>
                    <li>Make the necessary corrections to your DV request</li>
                    <li>Resubmit the request through the ACCUDOCS system</li>
                    <li>Contact IMSD if you need clarification</li>
                </ul>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 pt-6 mt-6">
                <p class="text-gray-600 mb-2">Best regards,</p>
                <p class="text-red-700 font-bold text-lg">IMSD Team</p>
                <p class="text-gray-600">ACCUDOCS DOLE XI</p>
                
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-500">
                        This is an automated notification. Please do not reply to this email.<br>
                        If you have questions, please contact the IMSD department directly.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>