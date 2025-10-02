<?php

namespace App\Http\Controllers\DVORS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Setting;

class VoucherController extends Controller
{
    // Show as normal web view (for testing layout)
     public function preview()
    {
        $data = [
            'payee' => 'John Doe',
            'dv_no' => 'DV-' . strtoupper(bin2hex(random_bytes(4))), // random code
            'date' => now()->format('F d, Y'),
            'ors_no' => 'ORS-2025-001',
            'address' => 'Davao City',
            'tin' => '123-456-789',
            'mode_of_payment' => 'MDS Check', // test tick box
        ];

        // Load admin-configured names (fallback to placeholders)
        $data['imsd_head'] = Setting::get('imsd_head', 'NAME OF IMSD HEAD');
        $data['accountant_name'] = Setting::get('accountant_name', 'NAME OF ACCOUNTANT');
        $data['regional_director'] = Setting::get('regional_director', 'NAME OF REGIONAL DIRECTOR');
        
        return view('vouchers.disbursement', $data);
    }

    // Generate PDF
    public function generatePDF()
    {
        $data = [
            'payee' => 'John Doe',
            'dv_no' => 'DV-' . strtoupper(bin2hex(random_bytes(4))),
            'date' => now()->format('F d, Y'),
            'ors_no' => 'ORS-2025-001',
            'address' => 'Davao City',
            'tin' => '123-456-789',
            'mode_of_payment' => 'MDS Check',
        ];

        // Load admin-configured names (fallback to placeholders)
        $data['imsd_head'] = Setting::get('imsd_head', 'NAME OF IMSD HEAD');
        $data['accountant_name'] = Setting::get('accountant_name', 'NAME OF ACCOUNTANT');
        $data['regional_director'] = Setting::get('regional_director', 'NAME OF REGIONAL DIRECTOR');

        $pdf = Pdf::loadView('vouchers.disbursement', $data)
                  ->setPaper('A4', 'portrait');

        return $pdf->stream('DisbursementVoucher.pdf');
    }
}