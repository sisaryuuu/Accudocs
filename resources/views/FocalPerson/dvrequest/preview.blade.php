@extends('layouts.app')

@section('title', 'DV Request Preview')

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
                <h2 class="text-3xl font-bold text-gray-800">DV Request Preview</h2>
                <p class="text-gray-600 mt-1">Review your disbursement voucher before finalizing</p>
            </div>
            <div class="text-right">
                <span class="inline-block px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                    ✓ Submitted
                </span>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('FocalPerson.dvrequest.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-blue-200 text-gray-700 rounded-lg hover:bg-blue-300 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Requests
        </a>

        <div class="flex gap-3">
            <button onclick="printBothDocuments()" 
                    class="no-print inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Both Documents
            </button> 

                        <!-- Download DV PDF Button -->
                <a href="{{ route('FocalPerson.dvrequest.generatePDF', $dvRequest->id) }}" 
                class="inline-flex items-center px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download DV PDF
                </a>

                <!-- Download ORS PDF Button -->
                <a href="{{ route('FocalPerson.dvrequest.generateORSPDF', $dvRequest->id) }}" 
                class="inline-flex items-center px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download ORS PDF
                </a>
        </div>
    </div>

    <!-- Document Preview Container -->
    <div class="bg-white shadow-2xl rounded-lg p-8" id="document-container">
        <!-- Page 1: ORS Document -->
        <div class="mb-8 page-break">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-700">ORS Document</h3>
                <button onclick="printORS()" 
                        class="no-print inline-flex items-center px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print ORS Only
                </button>
            </div>
            <div id="ors-container">
                @include('vouchers.focalors', $data)
            </div>
        </div>

        <!-- Page 2: DV Document -->
        <div class="page-break">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-700">DV Document</h3>
                <button onclick="printDV()" 
                        class="no-print inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print DV Only
                </button>
            </div>
            <div id="dv-container">
                @include('vouchers.disbursement', $data)
            </div>
        </div>
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
                    <strong>Note:</strong> This preview shows both the ORS and DV documents. You can print them individually or together. 
                    The request has been submitted and is now pending review by the accounting department.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #document-container, #document-container * {
            visibility: visible;
        }
        #document-container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 0;
            margin: 0;
            background: white;
        }
        .no-print {
            display: none !important;
        }
        .page-break {
            page-break-after: always;
            margin-bottom: 0;
        }
        .page-break:last-child {
            page-break-after: auto;
        }
    }

    /* Screen-only styles */
    @media screen {
        .page-break {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 2rem;
            background: white;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
    }
</style>

<script>
    function printBothDocuments() {
        window.print();
    }

    function printORS() {
        const orsContent = document.getElementById('ors-container').innerHTML;
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>ORS Document </title>
                <style>
                    body { 
                        margin: 0.3cm;
                        padding: 0;
                        font-family: "Times New Roman", Times, serif;
                        background: white;
                    }
                    @media print {
                        body { margin: 0.3cm; }
                    }
                </style>
            </head>
            <body>
                ${orsContent}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    }

    function printDV() {
        const dvContent = document.getElementById('dv-container').innerHTML;
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>DV Document - {{ $data['dv_no'] ?? 'N/A' }}</title>
                <style>
                    body { 
                        margin: 0.3cm;
                        padding: 0;
                        font-family: "Times New Roman", Times, serif;
                        background: white;
                    }
                    @media print {
                        body { margin: 0.3cm; }
                    }
                </style>
            </head>
            <body>
                ${dvContent}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    }

    // Auto-adjust print layout
    document.addEventListener('DOMContentLoaded', function() {
        // Add print styles for better printing
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                @page {
                    margin: 0.5cm;
                    size: letter;
                }
                body {
                    margin: 0;
                    padding: 0;
                    background: white !important;
                }
            }
        `;
        document.head.appendChild(style);
    });
</script>
@endsection