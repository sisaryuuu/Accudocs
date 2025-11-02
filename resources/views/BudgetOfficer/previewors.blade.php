@extends('layouts.app')

@section('title', 'ORS Preview - Budget Officer')

@section('content')
<div class="max-w-7xl mx-auto mt-8 px-4">
    <!-- Success Message -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Header -->
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">ORS Document Preview</h2>
                <p class="text-gray-600 mt-1">ORS Number: <strong>{{ $data['ors_no'] ?? 'N/A' }}</strong></p>
            </div>
            <div class="text-right">
                <span class="inline-block px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                    ✓ Obligated
                </span>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('BudgetOfficer.dvrequests.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to List
        </a>

        <div class="flex gap-3">
            <button onclick="window.print()" 
                    class="no-print inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print
            </button>

            <button onclick="downloadPDF()" 
                    class="no-print inline-flex items-center px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download PDF
            </button>

           <a href="{{ route('BudgetOfficer.dvrequests.loghistory') }}"
               class="no-print inline-flex items-center px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                View History
            </a>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
            <p class="text-gray-700 font-semibold">Generating PDF...</p>
        </div>
    </div>

    <!-- ORS Preview Container -->
    <div class="bg-white shadow-2xl rounded-lg p-8" id="ors-container">
        <!-- Include the ORS document template -->
        @include('vouchers.obligaterequests', $data)
    </div>

    <!-- Information Notice -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mt-6 rounded-r-lg no-print">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    <strong>Note:</strong> This ORS has been obligated and is ready for printing. 
                    You can download the PDF version or print it directly from your browser.
                    All changes have been saved and logged in the system.
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Info Card -->
    <div class="bg-white shadow-lg rounded-lg p-6 mt-6 no-print">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Document Information</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-600">ORS Number</p>
                <p class="font-semibold text-gray-800">{{ $data['ors_no'] ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Control Number</p>
                <p class="font-semibold text-gray-800">{{ isset($dvRequest) ? ('CN #-' . now()->year . '-' . str_pad($dvRequest->accounting_control_number, 5, '0', STR_PAD_LEFT)) : 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Amount</p>
                <p class="font-semibold text-gray-800">₱{{ number_format($data['amount'] ?? 0, 2) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Obligated Date</p>
                <p class="font-semibold text-gray-800">{{ $data['date'] ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Include html2pdf library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    // Download PDF function
    function downloadPDF() {
        const element = document.getElementById('ors-container');
        const orsNumber = '{{ $data["ors_no"] ?? "ORS" }}';
        const filename = `ORS_${orsNumber.replace(/[^a-zA-Z0-9]/g, '_')}.pdf`;
        
        // Show loading overlay
        document.getElementById('loadingOverlay').classList.remove('hidden');
        document.getElementById('loadingOverlay').classList.add('flex');
        
        const opt = {
            margin: 0.5,
            filename: filename,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save().then(() => {
            // Hide loading overlay after PDF is generated
            setTimeout(() => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                document.getElementById('loadingOverlay').classList.remove('flex');
            }, 500);
        }).catch(err => {
            console.error('PDF generation error:', err);
            document.getElementById('loadingOverlay').classList.add('hidden');
            document.getElementById('loadingOverlay').classList.remove('flex');
            alert('Failed to generate PDF. Please try printing instead.');
        });
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(event) {
        // Ctrl+P or Cmd+P for print
        if ((event.ctrlKey || event.metaKey) && event.key === 'p') {
            event.preventDefault();
            window.print();
        }
        
        // Ctrl+S or Cmd+S for download
        if ((event.ctrlKey || event.metaKey) && event.key === 's') {
            event.preventDefault();
            downloadPDF();
        }
    });
</script>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #ors-container, #ors-container * {
            visibility: visible;
        }
        #ors-container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print {
            display: none !important;
        }
        /* Remove shadows and rounded corners for print */
        #ors-container {
            box-shadow: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
        }
    }
</style>

<!-- Auto-scroll animation -->
@if(session('print_modal'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scroll to ORS container
        const orsContainer = document.getElementById('ors-container');
        if (orsContainer) {
            setTimeout(() => {
                orsContainer.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }, 500);
        }
    });
</script>
@endif
@endsection